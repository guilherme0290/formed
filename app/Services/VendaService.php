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

    /**
     * Cria venda com múltiplos itens a partir de tarefa concluída e contrato ativo.
     *
     * @param array<int, array<string, mixed>> $itens
     */
    public function criarVendaPorTarefaItens(Tarefa $tarefa, ClienteContrato $contrato, array $itens): Venda
    {
        $existing = Venda::where('tarefa_id', $tarefa->id)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($tarefa, $contrato, $itens) {
            $total = 0.0;
            foreach ($itens as $item) {
                $total += (float) ($item['preco_unitario_snapshot'] ?? 0) * (int) ($item['quantidade'] ?? 1);
            }

            $venda = Venda::create([
                'empresa_id' => $tarefa->empresa_id,
                'cliente_id' => $tarefa->cliente_id,
                'tarefa_id' => $tarefa->id,
                'contrato_id' => $contrato->id,
                'total' => $total,
                'status' => 'ABERTA',
            ]);

            foreach ($itens as $item) {
                $preco = (float) ($item['preco_unitario_snapshot'] ?? 0);
                $quantidade = (int) ($item['quantidade'] ?? 1);
                $subtotal = $preco * $quantidade;

                VendaItem::create([
                    'venda_id' => $venda->id,
                    'servico_id' => $item['servico_id'] ?? null,
                    'descricao_snapshot' => $item['descricao_snapshot'] ?? null,
                    'preco_unitario_snapshot' => $preco,
                    'quantidade' => $quantidade,
                    'subtotal_snapshot' => $subtotal,
                ]);
            }

            return $venda;
        });
    }
}
