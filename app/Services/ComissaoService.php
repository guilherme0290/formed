<?php

namespace App\Services;

use App\Models\Comissao;
use App\Models\ServicoComissao;
use App\Models\Tarefa;
use App\Models\Venda;
use App\Models\VendaItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComissaoService
{
    public function percentualVigente(int $empresaId, int $servicoId, Carbon $data): float
    {
        $dataRef = $data->copy()->toDateString();

        $regra = ServicoComissao::query()
            ->where('empresa_id', $empresaId)
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->whereDate('vigencia_inicio', '<=', $dataRef)
            ->where(function ($q) use ($dataRef) {
                $q->whereNull('vigencia_fim')
                    ->orWhereDate('vigencia_fim', '>=', $dataRef);
            })
            ->orderByDesc('vigencia_inicio')
            ->first();

        return (float) ($regra?->percentual ?? 0);
    }

    public function gerarPorVenda(Venda $venda, $contratoItem = null, ?int $vendedorId = null): ?Comissao
    {
        $empresaId = (int) $venda->empresa_id;
        $clienteId = (int) $venda->cliente_id;
        $data = Carbon::now();

        // se não houver vendedor, apenas loga e não cria comissão
        if (!$vendedorId) {
            Log::info('Comissão não gerada: cliente sem vendedor vinculado', [
                'cliente_id' => $clienteId,
                'venda_id' => $venda->id,
            ]);
            return null;
        }

        $venda->loadMissing('itens');
        $itens = $venda->itens
            ->filter(fn (VendaItem $item) => !empty($item->servico_id))
            ->values();

        if ($itens->isEmpty()) {
            Log::info('Comissão não gerada: venda sem itens com serviço vinculado', [
                'venda_id' => $venda->id,
                'cliente_id' => $clienteId,
            ]);

            return null;
        }

        $venda->loadMissing('tarefa');
        $competencia = $this->resolverCompetencia($venda);
        $comissoesExistentes = Comissao::query()
            ->where('venda_id', $venda->id)
            ->get()
            ->keyBy('venda_item_id');

        Comissao::query()->where('venda_id', $venda->id)->delete();

        $comissoes = collect();

        foreach ($itens as $vendaItem) {
            $servicoId = (int) $vendaItem->servico_id;
            $percentual = $this->percentualVigente($empresaId, $servicoId, $data);
            $valorBase = (float) ($vendaItem->subtotal_snapshot ?: 0);
            if ($valorBase <= 0) {
                $valorBase = (float) $vendaItem->preco_unitario_snapshot * max(1, (int) $vendaItem->quantidade);
            }
            $valorComissao = round($valorBase * ($percentual / 100), 2);
            /** @var Comissao|null $anterior */
            $anterior = $comissoesExistentes->get($vendaItem->id);
            $status = (string) ($anterior?->status ?? 'PENDENTE');
            $geradaEm = $anterior?->gerada_em ?? $data;
            $competenciaEm = $anterior?->competencia_em ?? $competencia;
            $pagaEm = $anterior?->paga_em;

            $comissoes->push(Comissao::create([
                'empresa_id' => $empresaId,
                'venda_id' => $venda->id,
                'venda_item_id' => $vendaItem->id,
                'vendedor_id' => $vendedorId,
                'cliente_id' => $clienteId,
                'servico_id' => $servicoId,
                'valor_base' => $valorBase,
                'percentual' => $percentual,
                'valor_comissao' => $valorComissao,
                'status' => $status,
                'competencia_em' => $competenciaEm,
                'gerada_em' => $geradaEm,
                'paga_em' => $status === 'PAGA' ? ($pagaEm ?? $data) : null,
            ]));
        }

        return $comissoes->first();
    }

    public function sincronizarStatusPorVendas(Collection $vendaIds, ?Carbon $pagaEm = null): void
    {
        $ids = $vendaIds
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $vendas = Venda::query()
            ->with(['itens.contasReceberItens', 'tarefa'])
            ->whereIn('id', $ids)
            ->get();

        foreach ($vendas as $venda) {
            $todosPagos = $venda->itens->isNotEmpty() && $venda->itens->every(function (VendaItem $item) {
                $itensReceber = $item->contasReceberItens->where('status', '!=', 'CANCELADO');
                return $itensReceber->isNotEmpty() && $itensReceber->every(fn ($receberItem) => (string) $receberItem->status === 'BAIXADO');
            });

            Comissao::query()
                ->where('venda_id', $venda->id)
                ->update([
                    'status' => $todosPagos ? 'PAGA' : 'PENDENTE',
                    'competencia_em' => DB::raw('COALESCE(competencia_em, DATE(COALESCE(gerada_em, created_at)))'),
                    'paga_em' => $todosPagos ? ($pagaEm?->toDateTimeString() ?? now()->toDateTimeString()) : null,
                ]);
        }
    }

    private function resolverCompetencia(Venda $venda): string
    {
        $tarefa = $venda->tarefa;
        if ($tarefa instanceof Tarefa) {
            if ($tarefa->finalizado_em) {
                return $tarefa->finalizado_em->toDateString();
            }

            if ($tarefa->inicio_previsto) {
                return $tarefa->inicio_previsto->toDateString();
            }
        }

        return ($venda->created_at ?? now())->toDateString();
    }
}
