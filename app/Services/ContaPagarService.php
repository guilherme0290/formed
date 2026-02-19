<?php

namespace App\Services;

use App\Models\ContaPagar;
use App\Models\ContaPagarBaixa;

class ContaPagarService
{
    public function recalcularConta(ContaPagar $conta): void
    {
        $total = (float) $conta->itens()
            ->where('status', '!=', 'CANCELADO')
            ->sum('valor');

        $totalBaixado = (float) ContaPagarBaixa::query()
            ->where('conta_pagar_id', $conta->id)
            ->sum('valor');

        $conta->total = $total;
        $conta->total_baixado = $totalBaixado;
        $conta->status = ($total > 0 && $totalBaixado >= $total) ? 'PAGA' : 'FECHADA';
        $conta->save();
    }

    public function aplicarBaixa(ContaPagar $conta, float $valor, ?string $pagoEm = null, array $extras = []): float
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

            ContaPagarBaixa::create([
                'conta_pagar_id' => $conta->id,
                'conta_pagar_item_id' => $item->id,
                'empresa_id' => $conta->empresa_id,
                'fornecedor_id' => $item->fornecedor_id ?? $conta->fornecedor_id,
                'valor' => $aplicar,
                'pago_em' => $pagoEm,
                'meio_pagamento' => $extras['meio_pagamento'] ?? null,
                'observacao' => $extras['observacao'] ?? null,
                'comprovante_path' => $extras['comprovante_path'] ?? null,
                'comprovante_nome' => $extras['comprovante_nome'] ?? null,
                'comprovante_mime' => $extras['comprovante_mime'] ?? null,
                'comprovante_tamanho' => $extras['comprovante_tamanho'] ?? null,
            ]);

            $novoSaldo = $saldo - $aplicar;
            $item->update([
                'status' => ($novoSaldo <= 0.0001) ? 'BAIXADO' : 'ABERTO',
                'baixado_em' => ($novoSaldo <= 0.0001) ? now() : null,
            ]);

            $restante -= $aplicar;
        }

        $this->recalcularConta($conta->fresh());

        return $valor - $restante;
    }
}
