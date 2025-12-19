<?php

namespace App\Services;

use App\Models\ClienteContratoItem;
use App\Models\Comissao;
use App\Models\ServicoComissao;
use App\Models\Venda;
use Carbon\Carbon;
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

    public function gerarPorVenda(Venda $venda, ClienteContratoItem $contratoItem, ?int $vendedorId = null): ?Comissao
    {
        $empresaId = (int) $venda->empresa_id;
        $servicoId = (int) $contratoItem->servico_id;
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

        $percentual = $this->percentualVigente($empresaId, $servicoId, $data);

        $valorBase = (float) $contratoItem->preco_unitario_snapshot;
        $valorComissao = round($valorBase * ($percentual / 100), 2);

        return Comissao::create([
            'empresa_id' => $empresaId,
            'venda_id' => $venda->id,
            'venda_item_id' => $venda->itens()->value('id'),
            'vendedor_id' => $vendedorId,
            'cliente_id' => $clienteId,
            'servico_id' => $servicoId,
            'valor_base' => $valorBase,
            'percentual' => $percentual,
            'valor_comissao' => $valorComissao,
            'status' => 'PENDENTE',
            'gerada_em' => $data,
        ]);
    }
}
