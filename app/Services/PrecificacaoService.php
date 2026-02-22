<?php

namespace App\Services;

use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use App\Models\PgrSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TreinamentoNrDetalhes;
use App\Models\TreinamentoNR;
use App\Models\AsoSolicitacoes;
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
        $tipoAsoLabel = $this->descricaoAsoTipoLabel((string) $aso->tipo_aso);
        $funcionarioNome = trim((string) ($aso->funcionario?->nome ?? ''));
        $detalheAso = implode(' - ', array_values(array_filter([
            $tipoAsoLabel,
            $funcionarioNome,
        ])));

        $descricao = $this->montarDescricaoVenda(
            $this->resolverNomeServico($tarefa, (int) $tarefa->servico_id) ?: 'ASO',
            $detalheAso !== '' ? $detalheAso : ($itemContrato?->descricao_snapshot ?: null)
        );

        $itensVenda = [[
                'servico_id' => $tarefa->servico_id,
                'descricao_snapshot' => $descricao,
                'preco_unitario_snapshot' => $valorTotal,
                'quantidade' => 1,
            ]];

        if ($aso->vai_fazer_treinamento) {
            $servicoTreinamentoId = (int) Servico::query()
                ->where('empresa_id', $tarefa->empresa_id)
                ->where('nome', 'Treinamentos NRs')
                ->value('id');

            if (!$servicoTreinamentoId) {
                throw ValidationException::withMessages([
                    'contrato' => 'Serviço de Treinamentos NRs não cadastrado para esta empresa.',
                ]);
            }

            $treinoItens = $this->precificarTreinamentosAso($contrato, $servicoTreinamentoId, $aso);
            $itensVenda = array_merge($itensVenda, $treinoItens);
        }

        return [
            'contrato' => $contrato,
            'itemContrato' => $itemContrato,
            'itensVenda' => $itensVenda,
        ];
    }

    private function descricaoAsoTipoLabel(string $tipoAso): string
    {
        return match ($tipoAso) {
            'admissional' => 'Admissional',
            'periodico' => 'Periódico',
            'demissional' => 'Demissional',
            'mudanca_funcao' => 'Mudança de Função',
            'retorno_trabalho' => 'Retorno ao Trabalho',
            default => '',
        };
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

        $detalhes = TreinamentoNrDetalhes::where('tarefa_id', $tarefa->id)->first();
        $treinamentosPayload = $detalhes?->treinamentos ?? [];
        $quantidadeParticipantes = TreinamentoNR::where('tarefa_id', $tarefa->id)->count();
        if ($quantidadeParticipantes <= 0) {
            throw ValidationException::withMessages([
                'contrato' => 'Treinamento sem participantes para precificação.',
            ]);
        }

        if (is_array($treinamentosPayload) && ($treinamentosPayload['modo'] ?? null) === 'pacote') {
            $pacote = (array) ($treinamentosPayload['pacote'] ?? []);
            $contratoItemId = (int) ($pacote['contrato_item_id'] ?? 0);

            $itemContrato = $contrato->itens()
                ->where('id', $contratoItemId)
                ->where('ativo', true)
                ->first();

            if (!$itemContrato) {
                throw ValidationException::withMessages([
                    'contrato' => 'Não foi possível localizar o pacote de treinamentos no contrato ativo.',
                ]);
            }

            if ((float) $itemContrato->preco_unitario_snapshot <= 0) {
                throw ValidationException::withMessages([
                    'contrato' => 'Não é possível concluir esta tarefa porque o pacote não possui valor válido no contrato ativo.',
                ]);
            }

            $codigosPacote = array_values((array) ($pacote['codigos'] ?? []));
            $codigosPacote = array_values(array_filter(array_map(
                fn ($codigo) => $this->normalizarCodigoTreinamento((string) $codigo),
                $codigosPacote
            )));

            if (!empty($codigosPacote)) {
                $mapaContrato = $this->buildMapaContratoTreinamentos($contrato, (int) $tarefa->servico_id);
                $rateioUnitario = $this->ratearValorEmPartes((float) $itemContrato->preco_unitario_snapshot, count($codigosPacote));
                $itensVendaPacote = [];

                foreach ($codigosPacote as $idx => $codigo) {
                    $itemContratoTreino = $mapaContrato[$codigo] ?? null;
                    $detalhe = $itemContratoTreino?->descricao_snapshot ?: $codigo;
                    $descricao = $this->montarDescricaoVenda(
                        $this->resolverNomeServico($tarefa, (int) $tarefa->servico_id) ?: 'Treinamentos NRs',
                        $detalhe
                    );

                    $itensVendaPacote[] = [
                        'servico_id' => $tarefa->servico_id,
                        'descricao_snapshot' => $descricao,
                        'preco_unitario_snapshot' => (float) ($rateioUnitario[$idx] ?? 0),
                        'quantidade' => $quantidadeParticipantes,
                    ];
                }

                return [
                    'contrato' => $contrato,
                    'itemContrato' => $itemContrato,
                    'itensVenda' => $itensVendaPacote,
                ];
            }

            $descricao = $this->montarDescricaoVenda(
                $this->resolverNomeServico($tarefa, (int) $tarefa->servico_id) ?: 'Treinamentos NRs',
                $itemContrato->descricao_snapshot ?: ($pacote['nome'] ?? 'Pacote de Treinamentos')
            );

            return [
                'contrato' => $contrato,
                'itemContrato' => $itemContrato,
                'itensVenda' => [[
                    'servico_id' => $tarefa->servico_id,
                    'descricao_snapshot' => $descricao,
                    'preco_unitario_snapshot' => (float) $itemContrato->preco_unitario_snapshot,
                    'quantidade' => $quantidadeParticipantes,
                ]],
            ];
        }

        $treinamentos = [];
        if (is_array($treinamentosPayload) && ($treinamentosPayload['modo'] ?? null) === 'avulso') {
            $treinamentos = array_values($treinamentosPayload['codigos'] ?? []);
        } else {
            $treinamentos = array_values((array) $treinamentosPayload);
        }

        if (empty($treinamentos)) {
            throw ValidationException::withMessages([
                'contrato' => 'Treinamento sem NRs informadas para precificação.',
            ]);
        }

        $contrato->loadMissing('itens', 'parametroOrigem.itens');
        $itensContrato = $contrato->itens
            ->where('servico_id', $tarefa->servico_id)
            ->where('ativo', true)
            ->values();

        if ($itensContrato->isEmpty()) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui preço definido para este serviço na proposta/contrato ativo. Solicite ao Comercial para ajustar a proposta e fechar novamente, ou cadastrar o valor do serviço no contrato do cliente.',
            ]);
        }

        $mapaContrato = $this->buildMapaContratoTreinamentos($contrato, (int) $tarefa->servico_id);

        $itensVenda = [];
        foreach ($treinamentos as $codigo) {
            $codigo = $this->normalizarCodigoTreinamento((string) $codigo);

            $itemContrato = $mapaContrato[$codigo] ?? null;
            if (!$itemContrato) {
                throw ValidationException::withMessages([
                    'contrato' => 'Treinamento sem preço no contrato ativo: ' . $codigo . '.',
                ]);
            }

            if ((float) $itemContrato->preco_unitario_snapshot <= 0) {
                throw ValidationException::withMessages([
                    'contrato' => 'Treinamento sem preço válido no contrato ativo: ' . $codigo . '.',
                ]);
            }

            $descricao = $this->montarDescricaoVenda(
                $this->resolverNomeServico($tarefa, (int) $tarefa->servico_id) ?: 'Treinamentos NRs',
                $itemContrato->descricao_snapshot ?: $codigo
            );
            $itensVenda[] = [
                'servico_id' => $tarefa->servico_id,
                'descricao_snapshot' => $descricao,
                'preco_unitario_snapshot' => (float) $itemContrato->preco_unitario_snapshot,
                'quantidade' => $quantidadeParticipantes,
            ];
        }

        $itemContratoBase = $mapaContrato[array_key_first($mapaContrato)] ?? $itensContrato->first();

        return [
            'contrato' => $contrato,
            'itemContrato' => $itemContratoBase,
            'itensVenda' => $itensVenda,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function precificarTreinamentosAso(ClienteContrato $contrato, int $servicoTreinamentoId, AsoSolicitacoes $aso): array
    {
        $treinamentoPacote = (array) ($aso->treinamento_pacote ?? []);
        if (!empty($treinamentoPacote)) {
            $contratoItemId = (int) ($treinamentoPacote['contrato_item_id'] ?? 0);
            $itemContrato = $contrato->itens()
                ->where('id', $contratoItemId)
                ->where('ativo', true)
                ->first();

            if (!$itemContrato) {
                throw ValidationException::withMessages([
                    'contrato' => 'Não foi possível localizar o pacote de treinamentos no contrato ativo.',
                ]);
            }

            if ((float) $itemContrato->preco_unitario_snapshot <= 0) {
                throw ValidationException::withMessages([
                    'contrato' => 'Não é possível concluir esta tarefa porque o pacote não possui valor válido no contrato ativo.',
                ]);
            }

            $codigosPacote = array_values((array) ($treinamentoPacote['codigos'] ?? []));
            $codigosPacote = array_values(array_filter(array_map(
                fn ($codigo) => $this->normalizarCodigoTreinamento((string) $codigo),
                $codigosPacote
            )));

            if (!empty($codigosPacote)) {
                $mapaContrato = $this->buildMapaContratoTreinamentos($contrato, $servicoTreinamentoId);
                $rateioUnitario = $this->ratearValorEmPartes((float) $itemContrato->preco_unitario_snapshot, count($codigosPacote));
                $itensVendaPacote = [];

                foreach ($codigosPacote as $idx => $codigo) {
                    $itemContratoTreino = $mapaContrato[$codigo] ?? null;
                    $detalhe = $itemContratoTreino?->descricao_snapshot ?: $codigo;
                    $descricao = $this->montarDescricaoVenda(
                        $this->resolverNomeServico(null, $servicoTreinamentoId) ?: 'Treinamentos NRs',
                        $detalhe
                    );

                    $itensVendaPacote[] = [
                        'servico_id' => $servicoTreinamentoId,
                        'descricao_snapshot' => $descricao,
                        'preco_unitario_snapshot' => (float) ($rateioUnitario[$idx] ?? 0),
                        'quantidade' => 1,
                    ];
                }

                return $itensVendaPacote;
            }

            $descricao = $this->montarDescricaoVenda(
                $this->resolverNomeServico(null, $servicoTreinamentoId) ?: 'Treinamentos NRs',
                $itemContrato->descricao_snapshot ?: ($treinamentoPacote['nome'] ?? 'Pacote de Treinamentos')
            );

            return [[
                'servico_id' => $servicoTreinamentoId,
                'descricao_snapshot' => $descricao,
                'preco_unitario_snapshot' => (float) $itemContrato->preco_unitario_snapshot,
                'quantidade' => 1,
            ]];
        }

        $treinamentos = array_values((array) ($aso->treinamentos ?? []));
        if (empty($treinamentos)) {
            throw ValidationException::withMessages([
                'contrato' => 'Treinamento sem NRs informadas para precificação.',
            ]);
        }

        $contrato->loadMissing('itens', 'parametroOrigem.itens');
        $itensContrato = $contrato->itens
            ->where('servico_id', $servicoTreinamentoId)
            ->where('ativo', true)
            ->values();

        if ($itensContrato->isEmpty()) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui preço definido para treinamentos no contrato ativo.',
            ]);
        }

        $mapaContrato = $this->buildMapaContratoTreinamentos($contrato, $servicoTreinamentoId);

        $itensVenda = [];
        foreach ($treinamentos as $codigo) {
            $codigo = $this->normalizarCodigoTreinamento((string) $codigo);

            $itemContrato = $mapaContrato[$codigo] ?? null;
            if (!$itemContrato) {
                throw ValidationException::withMessages([
                    'contrato' => 'Treinamento sem preço no contrato ativo: ' . $codigo . '.',
                ]);
            }

            if ((float) $itemContrato->preco_unitario_snapshot <= 0) {
                throw ValidationException::withMessages([
                    'contrato' => 'Treinamento sem preço válido no contrato ativo: ' . $codigo . '.',
                ]);
            }

            $descricao = $this->montarDescricaoVenda(
                $this->resolverNomeServico(null, $servicoTreinamentoId) ?: 'Treinamentos NRs',
                $itemContrato->descricao_snapshot ?: $codigo
            );
            $itensVenda[] = [
                'servico_id' => $servicoTreinamentoId,
                'descricao_snapshot' => $descricao,
                'preco_unitario_snapshot' => (float) $itemContrato->preco_unitario_snapshot,
                'quantidade' => 1,
            ];
        }

        return $itensVenda;
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
        $obraNome = trim((string) ($pgr->obra_nome ?? ''));
        $detalhePgr = $obraNome !== '' ? $obraNome : $tipoLabel;
        $descricao = $this->montarDescricaoVenda(
            $this->resolverNomeServico($tarefa, (int) $tarefa->servico_id) ?: 'PGR',
            $detalhePgr
        );

        $itensVenda = [[
            'servico_id' => $tarefa->servico_id,
            'descricao_snapshot' => $descricao,
            'preco_unitario_snapshot' => (float) $itemContrato->preco_unitario_snapshot,
            'quantidade' => 1,
        ]];

        if ($pgr->com_pcms0) {
            $servicoPcmsoId = Servico::query()
                ->where('empresa_id', $tarefa->empresa_id)
                ->where('nome', 'PCMSO')
                ->value('id');

            if (!$servicoPcmsoId) {
                throw ValidationException::withMessages([
                    'contrato' => 'Serviço PCMSO não cadastrado para esta empresa.',
                ]);
            }

            $itemPcmso = $contrato->itens()
                ->where('servico_id', $servicoPcmsoId)
                ->where('ativo', true)
                ->first();

            if (!$itemPcmso || (float) $itemPcmso->preco_unitario_snapshot <= 0) {
                throw ValidationException::withMessages([
                    'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui PCMSO com valor válido no contrato ativo.',
                ]);
            }

            $itensVenda[] = [
                'servico_id' => $servicoPcmsoId,
                'descricao_snapshot' => $this->montarDescricaoVenda(
                    $this->resolverNomeServico($tarefa, (int) $servicoPcmsoId) ?: 'PCMSO',
                    $obraNome !== '' ? $obraNome : null
                ),
                'preco_unitario_snapshot' => (float) $itemPcmso->preco_unitario_snapshot,
                'quantidade' => 1,
            ];
        }

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
                'descricao_snapshot' => $this->montarDescricaoVenda(
                    $this->resolverNomeServico($tarefa, (int) $servicoArtId) ?: 'ART',
                    $obraNome !== '' ? $obraNome : null
                ),
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

    private function resolverNomeServico(?Tarefa $tarefa, int $servicoId): ?string
    {
        if ($servicoId <= 0) {
            return null;
        }

        if ($tarefa && (int) $tarefa->servico_id === $servicoId) {
            $nome = trim((string) ($tarefa->servico?->nome ?? ''));
            if ($nome !== '') {
                return $nome;
            }
        }

        $nome = trim((string) (Servico::query()->whereKey($servicoId)->value('nome') ?? ''));
        return $nome !== '' ? $nome : null;
    }

    private function montarDescricaoVenda(?string $servicoNome, ?string $detalhe): string
    {
        $servico = trim((string) $servicoNome);
        $desc = trim((string) $detalhe);

        if ($servico === '') {
            return $desc !== '' ? $desc : 'Serviço';
        }

        if ($desc === '') {
            return $servico;
        }

        $servicoNorm = mb_strtolower($servico);
        $descNorm = mb_strtolower($desc);
        if ($descNorm === $servicoNorm || str_starts_with($descNorm, $servicoNorm . ' -')) {
            return $desc;
        }

        return $servico . ' - ' . $desc;
    }

    /**
     * @return array<string, ClienteContratoItem>
     */
    private function buildMapaContratoTreinamentos(ClienteContrato $contrato, int $servicoId): array
    {
        $contrato->loadMissing('itens', 'parametroOrigem.itens');
        $itensContrato = $contrato->itens
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->values();

        $mapaContrato = [];
        $itensOrigem = $contrato->parametroOrigem?->itens ?? collect();
        if ($itensOrigem->isNotEmpty()) {
            foreach ($itensOrigem as $origem) {
                if (strtoupper((string) ($origem->tipo ?? '')) !== 'TREINAMENTO_NR') {
                    continue;
                }

                $codigo = $origem->meta['codigo'] ?? null;
                if (!$codigo) {
                    $nome = (string) ($origem->nome ?? $origem->descricao ?? '');
                    if ($nome !== '' && preg_match('/^(NR[-\\s]?\\d+[A-Z]?)/i', $nome, $m)) {
                        $codigo = str_replace(' ', '-', $m[1]);
                    }
                }

                $codigo = $this->normalizarCodigoTreinamento((string) $codigo);
                if ($codigo === '') {
                    continue;
                }

                $descricaoSnapshot = $origem->descricao ?? $origem->nome;
                $contratoItem = $itensContrato->first(function ($item) use ($descricaoSnapshot) {
                    return trim((string) ($item->descricao_snapshot ?? '')) === trim((string) $descricaoSnapshot);
                });

                if ($contratoItem) {
                    $mapaContrato[$codigo] = $contratoItem;
                }
            }
        }

        foreach ($itensContrato as $item) {
            $descricao = (string) ($item->descricao_snapshot ?? '');
            if ($descricao !== '' && preg_match('/(NR[-\\s]?\\d+[A-Z]?)/i', $descricao, $m)) {
                $codigo = $this->normalizarCodigoTreinamento((string) str_replace(' ', '-', $m[1]));
                if ($codigo !== '') {
                    $mapaContrato[$codigo] = $item;
                }
            }
        }

        return $mapaContrato;
    }

    private function normalizarCodigoTreinamento(string $codigo): string
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') {
            return '';
        }

        if (preg_match('/^NR[-_]?\\d+$/i', $codigo)) {
            $numero = preg_replace('/\\D/', '', $codigo);
            $codigo = 'NR-' . str_pad((string) $numero, 2, '0', STR_PAD_LEFT);
        }

        return $codigo;
    }

    /**
     * @return array<int, float>
     */
    private function ratearValorEmPartes(float $valor, int $partes): array
    {
        if ($partes <= 0) {
            return [];
        }

        $totalCentavos = (int) round($valor * 100);
        $base = intdiv($totalCentavos, $partes);
        $resto = $totalCentavos % $partes;

        $rateio = [];
        for ($i = 0; $i < $partes; $i++) {
            $centavos = $base + ($i < $resto ? 1 : 0);
            $rateio[] = $centavos / 100;
        }

        return $rateio;
    }
}
