<?php

namespace App\Services;

use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use App\Models\PgrSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Models\TreinamentoNrDetalhes;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PrecificacaoService
{
    public function __construct(
        private readonly ContratoClienteService $contratoClienteService,
        private readonly AsoGheService $asoGheService
    )
    {
    }

    /**
     * Valida se existe contrato ativo e item de serviço para o cliente na data.
     *
     * @return array{contrato: ClienteContrato, item: ClienteContratoItem}
     */
    public function validarServicoNoContrato(int $clienteId, int $servicoId, int $empresaId, ?Carbon $dataRef = null): array
    {
        $contrato = $this->contratoClienteService->getContratoAtivo($clienteId, $empresaId, $dataRef);

        if (!$contrato) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui contrato ativo.',
            ]);
        }

        $item = $contrato->itens()
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->first();

        if (!$item) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui preço definido para este serviço na proposta/contrato ativo. Solicite ao Comercial para ajustar a proposta e fechar novamente, ou cadastrar o valor do serviço no contrato do cliente.',
            ]);
        }

        if ((float) $item->preco_unitario_snapshot <= 0) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o serviço não possui valor válido no contrato ativo.',
            ]);
        }

        return ['contrato' => $contrato, 'item' => $item];
    }

    /**
     * Precifica ASO e treinamentos a partir da tabela de preços.
     *
     * @return array{contrato: ClienteContrato, itemContrato: ClienteContratoItem, itensVenda: array<int, array<string, mixed>>}
     */
    public function precificarAso(Tarefa $tarefa): array
    {
        $contrato = $this->contratoClienteService->getContratoAtivo($tarefa->cliente_id, $tarefa->empresa_id, null);

        if (!$contrato) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui contrato ativo.',
            ]);
        }

        $aso = $tarefa->asoSolicitacao;
        if (!$aso) {
            throw ValidationException::withMessages([
                'contrato' => 'Não foi possível localizar os dados do ASO para precificar esta tarefa.',
            ]);
        }

        $funcaoId = $aso->funcionario?->funcao_id;
        if (!$funcaoId) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível precificar o ASO porque a função do colaborador não foi informada.',
            ]);
        }

        $itensContrato = $this->asoGheService->resolveItensContratoAsoPorFuncaoTipo(
            $contrato,
            (int) $tarefa->servico_id,
            (int) $funcaoId,
            (string) $aso->tipo_aso
        );

        if ($itensContrato->isEmpty()) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível precificar o ASO porque não existe configuração para o tipo e função informados. Solicite ao Comercial para ajustar a proposta e fechar novamente.',
            ]);
        }

        $valorTotal = (float) $itensContrato->sum(fn ($item) => (float) $item->preco_unitario_snapshot);

        if ($valorTotal <= 0) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o serviço não possui valor válido no contrato ativo.',
            ]);
        }

        $itemContrato = $itensContrato->first();
        $descricao = $itensContrato->count() === 1
            ? ($itemContrato?->descricao_snapshot ?: 'ASO')
            : $this->descricaoAsoPorTipo((string) $aso->tipo_aso);

        return [
            'contrato' => $contrato,
            'itemContrato' => $itemContrato,
            'itensVenda' => [[
                'servico_id' => $tarefa->servico_id,
                'descricao_snapshot' => $descricao,
                'preco_unitario_snapshot' => $valorTotal,
                'quantidade' => 1,
            ]],
        ];
    }

    private function descricaoAsoPorTipo(string $tipoAso): string
    {
        $label = match ($tipoAso) {
            'admissional' => 'Admissional',
            'periodico' => 'Periódico',
            'demissional' => 'Demissional',
            'mudanca_funcao' => 'Mudança de Função',
            'retorno_trabalho' => 'Retorno ao Trabalho',
            default => '',
        };

        return $label !== '' ? 'ASO - ' . $label : 'ASO';
    }

    /**
     * Precifica Treinamentos NRs usando tabela_preco_items por código.
     *
     * @return array{contrato: ClienteContrato, itemContrato: ClienteContratoItem, itensVenda: array<int, array<string, mixed>>}
     */
    public function precificarTreinamentosNr(Tarefa $tarefa): array
    {
        $contrato = $this->contratoClienteService->getContratoAtivo($tarefa->cliente_id, $tarefa->empresa_id, null);

        if (!$contrato) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui contrato ativo.',
            ]);
        }

        $itemContrato = $contrato->itens()
            ->where('servico_id', $tarefa->servico_id)
            ->where('ativo', true)
            ->first();

        if (!$itemContrato) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui preço definido para este serviço na proposta/contrato ativo. Solicite ao Comercial para ajustar a proposta e fechar novamente, ou cadastrar o valor do serviço no contrato do cliente.',
            ]);
        }

        if ((float) $itemContrato->preco_unitario_snapshot <= 0) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o serviço não possui valor válido no contrato ativo.',
            ]);
        }

        $detalhes = TreinamentoNrDetalhes::where('tarefa_id', $tarefa->id)->first();
        $treinamentos = array_values($detalhes?->treinamentos ?? []);

        if (empty($treinamentos)) {
            throw ValidationException::withMessages([
                'contrato' => 'Treinamento sem NRs informadas para precificação.',
            ]);
        }

        $padrao = TabelaPrecoPadrao::where('empresa_id', $tarefa->empresa_id)
            ->where('ativa', true)
            ->first();

        if (!$padrao) {
            throw ValidationException::withMessages([
                'contrato' => 'Tabela de preço padrão não encontrada para esta empresa.',
            ]);
        }

        $itensTreinamentos = TabelaPrecoItem::query()
            ->where('tabela_preco_padrao_id', $padrao->id)
            ->where('servico_id', $tarefa->servico_id)
            ->whereIn('codigo', $treinamentos)
            ->where('ativo', true)
            ->get();

        $encontrados = $itensTreinamentos->pluck('codigo')->all();
        $faltantes = array_values(array_diff($treinamentos, $encontrados));

        if (!empty($faltantes)) {
            throw ValidationException::withMessages([
                'contrato' => 'Treinamento(s) sem preço definido na tabela: ' . implode(', ', $faltantes) . '.',
            ]);
        }

        $itensVenda = [];
        foreach ($itensTreinamentos as $item) {
            if ((float) $item->preco <= 0) {
                throw ValidationException::withMessages([
                    'contrato' => 'Treinamento sem preço válido na tabela: ' . ($item->codigo ?? 'NR') . '.',
                ]);
            }

            $descricao = trim(($item->codigo ?? '') . ' - ' . ($item->descricao ?? ''));
            $itensVenda[] = [
                'servico_id' => $tarefa->servico_id,
                'descricao_snapshot' => $descricao ?: ($item->codigo ?? 'Treinamento'),
                'preco_unitario_snapshot' => (float) $item->preco,
                'quantidade' => 1,
            ];
        }

        return [
            'contrato' => $contrato,
            'itemContrato' => $itemContrato,
            'itensVenda' => $itensVenda,
        ];
    }

    /**
     * Precifica PGR e adiciona ART quando solicitado.
     *
     * @return array{contrato: ClienteContrato, itemContrato: ClienteContratoItem, itensVenda: array<int, array<string, mixed>>}
     */
    public function precificarPgr(Tarefa $tarefa): array
    {
        $contrato = $this->contratoClienteService->getContratoAtivo($tarefa->cliente_id, $tarefa->empresa_id, null);

        if (!$contrato) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui contrato ativo.',
            ]);
        }

        $itemContrato = $contrato->itens()
            ->where('servico_id', $tarefa->servico_id)
            ->where('ativo', true)
            ->first();

        if (!$itemContrato) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui preço definido para este serviço na proposta/contrato ativo. Solicite ao Comercial para ajustar a proposta e fechar novamente, ou cadastrar o valor do serviço no contrato do cliente.',
            ]);
        }

        if ((float) $itemContrato->preco_unitario_snapshot <= 0) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o serviço não possui valor válido no contrato ativo.',
            ]);
        }

        $pgr = $tarefa->pgr ?? PgrSolicitacoes::where('tarefa_id', $tarefa->id)->first();
        if (!$pgr) {
            throw ValidationException::withMessages([
                'contrato' => 'Não foi possível localizar os dados do PGR para precificação.',
            ]);
        }

        $tipoLabel = $pgr->tipo === 'especifico' ? 'Específico' : 'Matriz';
        $descricao = "PGR - {$tipoLabel}" . ($pgr->com_art ? ' (COM ART)' : '');

        $itensVenda = [[
            'servico_id' => $tarefa->servico_id,
            'descricao_snapshot' => $descricao,
            'preco_unitario_snapshot' => (float) $itemContrato->preco_unitario_snapshot,
            'quantidade' => 1,
        ]];

        if ($pgr->com_art) {
            $servicoArtId = Servico::query()
                ->where('empresa_id', $tarefa->empresa_id)
                ->where('nome', 'ART')
                ->value('id');

            if (!$servicoArtId) {
                throw ValidationException::withMessages([
                    'contrato' => 'Serviço ART não cadastrado para esta empresa.',
                ]);
            }

            $itemArt = $contrato->itens()
                ->where('servico_id', $servicoArtId)
                ->where('ativo', true)
                ->first();

            if (!$itemArt || (float) $itemArt->preco_unitario_snapshot <= 0) {
                throw ValidationException::withMessages([
                    'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui ART com valor válido no contrato ativo.',
                ]);
            }

            $itensVenda[] = [
                'servico_id' => $servicoArtId,
                'descricao_snapshot' => 'ART',
                'preco_unitario_snapshot' => (float) $itemArt->preco_unitario_snapshot,
                'quantidade' => 1,
            ];
        }

        return [
            'contrato' => $contrato,
            'itemContrato' => $itemContrato,
            'itensVenda' => $itensVenda,
        ];
    }
}
