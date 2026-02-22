<?php

namespace App\Services;

use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use App\Models\Servico;
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
            $descricaoSnapshot = $this->montarDescricaoVenda(
                $item->servico?->nome ?? $tarefa->servico?->nome,
                $item->descricao_snapshot
            );

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
                'descricao_snapshot' => $descricaoSnapshot,
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

            $servicoIds = collect($itens)
                ->pluck('servico_id')
                ->filter(fn ($id) => !empty($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            $nomesServico = $servicoIds->isEmpty()
                ? collect()
                : Servico::query()->whereIn('id', $servicoIds)->pluck('nome', 'id');

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
                $servicoId = (int) ($item['servico_id'] ?? 0);
                $servicoNome = $servicoId > 0
                    ? ($nomesServico[$servicoId] ?? null)
                    : null;
                if (!$servicoNome && $servicoId > 0 && (int) $tarefa->servico_id === $servicoId) {
                    $servicoNome = $tarefa->servico?->nome;
                }
                $descricaoSnapshot = $this->montarDescricaoVenda(
                    is_string($servicoNome) ? $servicoNome : null,
                    $item['descricao_snapshot'] ?? null
                );

                VendaItem::create([
                    'venda_id' => $venda->id,
                    'servico_id' => $servicoId > 0 ? $servicoId : null,
                    'descricao_snapshot' => $descricaoSnapshot,
                    'preco_unitario_snapshot' => $preco,
                    'quantidade' => $quantidade,
                    'subtotal_snapshot' => $subtotal,
                ]);
            }

            return $venda;
        });
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
