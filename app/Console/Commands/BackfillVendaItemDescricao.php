<?php

namespace App\Console\Commands;

use App\Models\ContaReceberItem;
use App\Models\VendaItem;
use Illuminate\Console\Command;

class BackfillVendaItemDescricao extends Command
{
    protected $signature = 'vendas:backfill-descricoes
        {--dry-run : Apenas mostra o que seria alterado}
        {--only-missing : Atualiza somente descricoes vazias}
        {--limit=0 : Limita a quantidade de venda_itens processados}';

    protected $description = 'Padroniza descricao dos itens de venda no formato SERVICO - DETALHE e atualiza contas vinculadas.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyMissing = (bool) $this->option('only-missing');
        $limit = max(0, (int) $this->option('limit'));

        $query = VendaItem::query()
            ->with([
                'servico:id,nome',
                'venda:id,tarefa_id',
                'venda.tarefa:id,servico_id',
                'venda.tarefa.treinamentoNrDetalhes:id,tarefa_id,treinamentos',
                'venda.tarefa.asoSolicitacao:id,tarefa_id,funcionario_id,tipo_aso,treinamento_pacote',
                'venda.tarefa.asoSolicitacao.funcionario:id,nome',
                'venda.tarefa.pgr:id,tarefa_id,tipo,obra_nome',
                'venda.tarefa.pgrSolicitacao:id,tarefa_id,tipo,obra_nome',
            ]);

        if ($onlyMissing) {
            $query->where(function ($q) {
                $q->whereNull('descricao_snapshot')
                    ->orWhereRaw("TRIM(COALESCE(descricao_snapshot, '')) = ''");
            });
        }

        $processados = 0;
        $alteradosVenda = 0;
        $alteradosConta = 0;

        $query->chunkById(200, function ($itens) use ($dryRun, $limit, &$processados, &$alteradosVenda, &$alteradosConta) {
            foreach ($itens as $item) {
                if ($limit > 0 && $processados >= $limit) {
                    return false;
                }

                $processados++;

                $novaDescricao = $this->resolverDescricaoVendaItem($item);
                $novaDescricao = trim($novaDescricao);

                if ($novaDescricao === '') {
                    continue;
                }

                $atualDescricao = trim((string) ($item->descricao_snapshot ?? ''));
                if ($atualDescricao !== $novaDescricao) {
                    $alteradosVenda++;

                    if ($dryRun) {
                        $this->line(sprintf(
                            '[VENDA_ITEM %d] "%s" => "%s"',
                            $item->id,
                            $atualDescricao,
                            $novaDescricao
                        ));
                    } else {
                        $item->forceFill(['descricao_snapshot' => $novaDescricao])->save();
                    }
                }

                $contas = ContaReceberItem::query()
                    ->where('venda_item_id', $item->id)
                    ->get(['id', 'descricao']);

                foreach ($contas as $contaItem) {
                    $descContaAtual = trim((string) ($contaItem->descricao ?? ''));
                    if ($descContaAtual === $novaDescricao) {
                        continue;
                    }

                    $alteradosConta++;
                    if ($dryRun) {
                        $this->line(sprintf(
                            '[CONTA_ITEM %d <- VENDA_ITEM %d] "%s" => "%s"',
                            $contaItem->id,
                            $item->id,
                            $descContaAtual,
                            $novaDescricao
                        ));
                    } else {
                        $contaItem->forceFill(['descricao' => $novaDescricao])->save();
                    }
                }
            }

            return null;
        });

        $this->newLine();
        $this->info(sprintf(
            'Processados: %d | Venda itens alterados: %d | Contas alteradas: %d | Modo: %s',
            $processados,
            $alteradosVenda,
            $alteradosConta,
            $dryRun ? 'dry-run' : 'execucao'
        ));

        return self::SUCCESS;
    }

    private function resolverDescricaoVendaItem(VendaItem $item): string
    {
        $servicoNome = trim((string) ($item->servico?->nome ?? ''));
        $descricaoAtual = trim((string) ($item->descricao_snapshot ?? ''));
        $tarefa = $item->venda?->tarefa;

        $servicoUpper = mb_strtoupper($servicoNome);

        if ($tarefa && str_contains($servicoUpper, 'ASO')) {
            $aso = $tarefa->asoSolicitacao;
            $tipo = $this->descricaoAsoTipoLabel((string) ($aso->tipo_aso ?? ''));
            $funcionario = trim((string) ($aso->funcionario?->nome ?? ''));
            $detalhe = implode(' - ', array_values(array_filter([$tipo, $funcionario])));

            return $this->montarDescricaoVenda($servicoNome ?: 'ASO', $detalhe !== '' ? $detalhe : $descricaoAtual);
        }

        $pgr = $tarefa?->pgr ?? $tarefa?->pgrSolicitacao;
        if ($pgr) {
            $obraNome = trim((string) ($pgr->obra_nome ?? ''));
            $tipoLabel = ((string) ($pgr->tipo ?? '')) === 'especifico' ? 'Específico' : 'Matriz';

            if ($servicoUpper === 'PGR') {
                return $this->montarDescricaoVenda($servicoNome ?: 'PGR', $obraNome !== '' ? $obraNome : $tipoLabel);
            }

            if ($servicoUpper === 'PCMSO' || $servicoUpper === 'ART') {
                return $this->montarDescricaoVenda($servicoNome, $obraNome !== '' ? $obraNome : $descricaoAtual);
            }
        }

        if (str_contains($servicoUpper, 'TREINAMENTO')) {
            $pacoteNome = $this->resolverNomePacoteTreinamento($item);
            if ($pacoteNome !== null) {
                return $this->montarDescricaoVenda($servicoNome ?: 'Treinamentos NRs', $pacoteNome);
            }
        }

        return $this->montarDescricaoVenda($servicoNome ?: null, $descricaoAtual);
    }

    private function resolverNomePacoteTreinamento(VendaItem $item): ?string
    {
        $tarefa = $item->venda?->tarefa;
        if (!$tarefa) {
            return null;
        }

        $treinamentosPayload = (array) ($tarefa->treinamentoNrDetalhes?->treinamentos ?? []);
        if (($treinamentosPayload['modo'] ?? null) === 'pacote') {
            $pacote = (array) ($treinamentosPayload['pacote'] ?? []);
            $nome = trim((string) ($pacote['nome'] ?? ''));
            if ($nome !== '') {
                return $nome;
            }

            $descricao = trim((string) ($pacote['descricao'] ?? ''));
            if ($descricao !== '') {
                return $descricao;
            }
        }

        $asoPacote = (array) ($tarefa->asoSolicitacao?->treinamento_pacote ?? []);
        if (!empty($asoPacote)) {
            $nome = trim((string) ($asoPacote['nome'] ?? ''));
            if ($nome !== '') {
                return $nome;
            }

            $descricao = trim((string) ($asoPacote['descricao'] ?? ''));
            if ($descricao !== '') {
                return $descricao;
            }
        }

        return null;
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
}
