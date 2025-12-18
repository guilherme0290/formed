<?php

namespace App\Services;

use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use App\Models\Tarefa;
use App\Models\Venda;
use App\Models\VendaItem;
use Illuminate\Support\Facades\DB;

class VendaService
{
    /**
     * Cria venda e item a partir de tarefa concluída e contrato ativo.
     */
    public function criarVendaPorTarefa(Tarefa $tarefa, ClienteContrato $contrato, ClienteContratoItem $item): Venda
    {
        // evita duplicidade
        $existing = Venda::where('tarefa_id', $tarefa->id)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($tarefa, $contrato, $item) {
            $total = (float) $item->preco_unitario_snapshot;

            $venda = Venda::create([
                'empresa_id' => $tarefa->empresa_id,
                'cliente_id' => $tarefa->cliente_id,
                'tarefa_id' => $tarefa->id,
                'contrato_id' => $contrato->id,
                'total' => $total,
                'status' => 'ABERTA',
            ]);

            VendaItem::create([
                'venda_id' => $venda->id,
                'servico_id' => $item->servico_id,
                'descricao_snapshot' => $item->descricao_snapshot,
                'preco_unitario_snapshot' => $item->preco_unitario_snapshot,
                'quantidade' => 1,
                'subtotal_snapshot' => $total,
            ]);

            return $venda;
        });
    }
}
