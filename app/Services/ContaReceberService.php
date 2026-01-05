<?php

namespace App\Services;

use App\Models\ContaReceber;
use App\Models\ContaReceberBaixa;
use App\Models\ContaReceberItem;
use App\Models\Venda;

class ContaReceberService
{
    public function recalcularConta(ContaReceber $conta): void
    {
        $totais = $conta->itens()
            ->where('status', '!=', 'CANCELADO')
            ->selectRaw('COALESCE(SUM(valor), 0) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'BAIXADO' THEN valor ELSE 0 END), 0) as total_baixado")
            ->first();

        $total = (float) ($totais?->total ?? 0);
        $totalBaixado = (float) ContaReceberBaixa::query()
            ->where('conta_receber_id', $conta->id)
            ->sum('valor');

        $conta->total = $total;
        $conta->total_baixado = $totalBaixado;

        if ($total > 0 && $totalBaixado >= $total) {
            $conta->status = 'FATURADA';
        } else {
            $conta->status = 'FECHADA';
        }

        $conta->save();
    }

    public function atualizarStatusVenda(?int $vendaId): void
    {
        if (!$vendaId) {
            return;
        }

        $venda = Venda::find($vendaId);
        if (!$venda) {
            return;
        }

        $itensQuery = ContaReceberItem::query()
            ->where('venda_id', $vendaId)
            ->where('status', '!=', 'CANCELADO');

        if (!$itensQuery->exists()) {
            $venda->update(['status' => 'ABERTA']);
            return;
        }

        $total = (clone $itensQuery)->count();
        $baixados = (clone $itensQuery)->where('status', 'BAIXADO')->count();
        $abertos = (clone $itensQuery)->where('status', 'ABERTO')->exists();

        if ($total > 0 && $baixados === $total && !$abertos) {
            $venda->update(['status' => 'FATURADA']);
            return;
        }

        $venda->update(['status' => 'FECHADA']);
    }

    public function aplicarBaixa(ContaReceber $conta, float $valor, ?string $pagoEm = null): float
    {
        $restante = $valor;
        if ($restante <= 0) {
            return 0;
        }

        $itens = $conta->itens()
            ->where('status', '!=', 'CANCELADO')
            ->orderBy('vencimento')
            ->orderBy('id')
            ->get();

        foreach ($itens as $item) {
            if ($restante <= 0) {
                break;
            }

            $baixado = (float) $item->baixas()->sum('valor');
            $saldo = (float) $item->valor - $baixado;

            if ($saldo <= 0) {
                $item->update(['status' => 'BAIXADO']);
                continue;
            }

            $aplicar = min($saldo, $restante);
            if ($aplicar <= 0) {
                continue;
            }

            ContaReceberBaixa::create([
                'conta_receber_id' => $conta->id,
                'conta_receber_item_id' => $item->id,
                'empresa_id' => $conta->empresa_id,
                'cliente_id' => $conta->cliente_id,
                'valor' => $aplicar,
                'pago_em' => $pagoEm,
            ]);

            $novoSaldo = $saldo - $aplicar;
            if ($novoSaldo <= 0.0001) {
                $item->update(['status' => 'BAIXADO']);
            } else {
                $item->update(['status' => 'ABERTO']);
            }

            $restante -= $aplicar;
        }

        $this->recalcularConta($conta->fresh());

        $vendaIds = $itens->pluck('venda_id')->filter()->unique();
        foreach ($vendaIds as $vendaId) {
            $this->atualizarStatusVenda($vendaId);
        }

        return $valor - $restante;
    }
}
