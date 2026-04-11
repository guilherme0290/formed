<?php

namespace App\Console\Commands;

use App\Models\Comissao;
use App\Models\Venda;
use App\Services\ComissaoService;
use Illuminate\Console\Command;

class BackfillComissoesHistoricasCommand extends Command
{
    protected $signature = 'comissoes:backfill
        {--empresa_id= : Filtra por empresa}
        {--venda_id= : Reprocessa apenas uma venda}
        {--limit=0 : Limita a quantidade de vendas processadas}
        {--dry-run : Apenas mostra o que seria reprocessado sem salvar}';

    protected $description = 'Recalcula comissoes historicas por venda, incluindo itens de treinamento e competencia.';

    public function handle(ComissaoService $comissaoService): int
    {
        $empresaId = (int) ($this->option('empresa_id') ?: 0);
        $vendaId = (int) ($this->option('venda_id') ?: 0);
        $limit = max(0, (int) ($this->option('limit') ?: 0));
        $dryRun = (bool) $this->option('dry-run');

        $query = Venda::query()
            ->with([
                'cliente:id,vendedor_id',
                'tarefa:id,vendedor_snapshot_id',
                'itens:id,venda_id,servico_id,descricao_snapshot,subtotal_snapshot,preco_unitario_snapshot,quantidade',
            ])
            ->whereHas('itens');

        if ($empresaId > 0) {
            $query->where('empresa_id', $empresaId);
        }

        if ($vendaId > 0) {
            $query->whereKey($vendaId);
        }

        $processadas = 0;
        $reprocessadas = 0;
        $semVendedor = 0;

        $query->orderBy('id')->chunkById(100, function ($vendas) use (
            $comissaoService,
            $dryRun,
            $limit,
            &$processadas,
            &$reprocessadas,
            &$semVendedor
        ) {
            foreach ($vendas as $venda) {
                if ($limit > 0 && $processadas >= $limit) {
                    return false;
                }

                $processadas++;

                $vendedorId = $this->resolverVendedor($venda);
                $qtdItens = $venda->itens->count();
                $qtdComissoesAntes = Comissao::query()->where('venda_id', $venda->id)->count();

                if (!$vendedorId) {
                    $semVendedor++;
                    $this->warn(sprintf(
                        'Venda #%d ignorada: sem vendedor resolvido. Itens: %d | Comissoes atuais: %d',
                        (int) $venda->id,
                        $qtdItens,
                        $qtdComissoesAntes
                    ));
                    continue;
                }

                if ($dryRun) {
                    $this->line(sprintf(
                        'Venda #%d | vendedor=%d | itens=%d | comissoes atuais=%d',
                        (int) $venda->id,
                        $vendedorId,
                        $qtdItens,
                        $qtdComissoesAntes
                    ));
                    $reprocessadas++;
                    continue;
                }

                $comissaoService->gerarPorVenda($venda, null, $vendedorId);
                $comissaoService->sincronizarStatusPorVendas(collect([(int) $venda->id]));
                $reprocessadas++;
            }

            return null;
        });

        $this->newLine();
        $this->info(sprintf(
            'Vendas processadas: %d | Reprocessadas: %d | Sem vendedor: %d | Modo: %s',
            $processadas,
            $reprocessadas,
            $semVendedor,
            $dryRun ? 'dry-run' : 'execucao'
        ));

        return self::SUCCESS;
    }

    private function resolverVendedor(Venda $venda): ?int
    {
        $vendedorComissaoExistente = Comissao::query()
            ->where('venda_id', $venda->id)
            ->whereNotNull('vendedor_id')
            ->value('vendedor_id');

        if ($vendedorComissaoExistente) {
            return (int) $vendedorComissaoExistente;
        }

        if (!empty($venda->tarefa?->vendedor_snapshot_id)) {
            return (int) $venda->tarefa->vendedor_snapshot_id;
        }

        if (!empty($venda->cliente?->vendedor_id)) {
            return (int) $venda->cliente->vendedor_id;
        }

        return null;
    }
}
