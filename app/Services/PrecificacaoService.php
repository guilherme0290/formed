<?php

namespace App\Services;

use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Models\Tarefa;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PrecificacaoService
{
    public function __construct(private readonly ContratoClienteService $contratoClienteService)
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

        $itemContrato = $contrato->itens()
            ->where('servico_id', $tarefa->servico_id)
            ->where('ativo', true)
            ->first();

        if (!$itemContrato) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui preço definido para este serviço na proposta/contrato ativo. Solicite ao Comercial para ajustar a proposta e fechar novamente, ou cadastrar o valor do serviço no contrato do cliente.',
            ]);
        }

        $aso = $tarefa->asoSolicitacao;
        if (!$aso) {
            throw ValidationException::withMessages([
                'contrato' => 'Não foi possível localizar os dados do ASO para precificar esta tarefa.',
            ]);
        }

        $codigoAso = match ($aso->tipo_aso) {
            'admissional' => 'ASO-ADM',
            'demissional' => 'ASO-DEM',
            'periodico' => 'ASO-PER',
            'mudanca_funcao' => 'ASO-FUN',
            'retorno_trabalho' => 'ASO-TRA',
            default => null,
        };

        if (!$codigoAso) {
            throw ValidationException::withMessages([
                'contrato' => 'Tipo de ASO inválido para precificação.',
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

        $itemAso = TabelaPrecoItem::query()
            ->where('tabela_preco_padrao_id', $padrao->id)
            ->where('servico_id', $tarefa->servico_id)
            ->where('codigo', $codigoAso)
            ->where('ativo', true)
            ->first();

        if (!$itemAso) {
            throw ValidationException::withMessages([
                'contrato' => 'Preço do ASO não encontrado na tabela de preços. Verifique o código ' . $codigoAso . '.',
            ]);
        }

        $itensVenda = [[
            'servico_id' => $tarefa->servico_id,
            'descricao_snapshot' => $itemAso->descricao ?? $codigoAso,
            'preco_unitario_snapshot' => (float) $itemAso->preco,
            'quantidade' => 1,
        ]];

        $treinamentos = array_values($aso->treinamentos ?? []);
        if (!empty($treinamentos)) {
            $treinamentoServicoId = (int) (config('services.treinamento_id') ?? 0);
            if ($treinamentoServicoId <= 0) {
                $treinamentoServicoId = (int) Servico::where('empresa_id', $tarefa->empresa_id)
                    ->where('nome', 'Treinamentos NRs')
                    ->value('id');
            }

            if ($treinamentoServicoId <= 0) {
                throw ValidationException::withMessages([
                    'contrato' => 'Serviço de Treinamentos NRs não configurado para esta empresa.',
                ]);
            }

            $itensTreinamentos = TabelaPrecoItem::query()
                ->where('tabela_preco_padrao_id', $padrao->id)
                ->where('servico_id', $treinamentoServicoId)
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

            foreach ($itensTreinamentos as $item) {
                $descricao = trim(($item->codigo ?? '') . ' - ' . ($item->descricao ?? ''));
                $itensVenda[] = [
                    'servico_id' => $treinamentoServicoId,
                    'descricao_snapshot' => $descricao ?: ($item->codigo ?? 'Treinamento'),
                    'preco_unitario_snapshot' => (float) $item->preco,
                    'quantidade' => 1,
                ];
            }
        }

        return [
            'contrato' => $contrato,
            'itemContrato' => $itemContrato,
            'itensVenda' => $itensVenda,
        ];
    }
}
