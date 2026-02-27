<?php

namespace App\Console\Commands;

use App\Models\ClienteContrato;
use App\Models\ContaReceber;
use App\Models\ContaReceberItem;
use App\Models\Venda;
use App\Models\VendaItem;
use App\Services\ContaReceberService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GerarEsocialMensal extends Command
{
    protected $signature = 'esocial:gerar-vendas-mensais {--data=}';

    protected $description = 'Gera vendas mensais de eSocial para contratos ativos.';

    public function handle(): int
    {
        $servicoEsocialId = (int) config('services.esocial_id');
        if ($servicoEsocialId <= 0) {
            $this->error('Serviço eSocial não configurado. Defina FORMED_SERVICO_ESOCIAL_ID.');
            return self::FAILURE;
        }

        $referencia = $this->option('data')
            ? Carbon::parse($this->option('data'))->startOfDay()
            : now()->startOfDay();

        $inicioMes = $referencia->copy()->startOfMonth();
        $fimMes = $referencia->copy()->endOfMonth();

        $contratos = ClienteContrato::query()
            ->where('status', 'ATIVO')
            ->where(function ($q) use ($referencia) {
                $q->whereNull('vigencia_inicio')->orWhereDate('vigencia_inicio', '<=', $referencia);
            })
            ->where(function ($q) use ($referencia) {
                $q->whereNull('vigencia_fim')->orWhereDate('vigencia_fim', '>=', $referencia);
            })
            ->whereHas('itens', function ($q) use ($servicoEsocialId) {
                $q->where('servico_id', $servicoEsocialId)->where('ativo', true);
            })
            ->with(['itens' => function ($q) use ($servicoEsocialId) {
                $q->where('servico_id', $servicoEsocialId)->where('ativo', true);
            }])
            ->get();

        $criados = 0;
        $ignorados = 0;
        $clientesProcessados = [];

        $service = app(ContaReceberService::class);
        $contasPorCliente = [];

        foreach ($contratos as $contrato) {
            $clientesProcessados[$contrato->cliente_id] = true;
            $vencimentoDia = (int) ($contrato->vencimento_servicos ?? 0);
            if ($vencimentoDia < 1 || $vencimentoDia > 31) {
                $ignorados++;
                continue;
            }

            $item = $contrato->itens->first();
            $valor = (float) ($item?->preco_unitario_snapshot ?? 0);
            if ($valor <= 0) {
                $ignorados++;
                continue;
            }

            $jaExisteConta = ContaReceberItem::query()
                ->where('cliente_id', $contrato->cliente_id)
                ->where('empresa_id', $contrato->empresa_id)
                ->where('servico_id', $servicoEsocialId)
                ->whereBetween('data_realizacao', [$inicioMes, $fimMes])
                ->where('status', '!=', 'CANCELADO')
                ->exists();
            if ($jaExisteConta) {
                $ignorados++;
                continue;
            }

            $jaExisteVenda = VendaItem::query()
                ->where('servico_id', $servicoEsocialId)
                ->whereHas('venda', function ($q) use ($contrato, $inicioMes, $fimMes) {
                    $q->where('empresa_id', $contrato->empresa_id)
                        ->where('cliente_id', $contrato->cliente_id)
                        ->whereBetween('created_at', [$inicioMes, $fimMes]);
                })
                ->exists();
            if ($jaExisteVenda) {
                $ignorados++;
                continue;
            }

            $vencimentoBase = $referencia->copy()->startOfMonth();
            $diaVencimento = min($vencimentoDia, $vencimentoBase->daysInMonth);
            $vencimento = $vencimentoBase->copy()->day($diaVencimento);

            DB::transaction(function () use ($contrato, $valor, $referencia, $servicoEsocialId, $vencimento, $service, &$contasPorCliente) {
                $venda = Venda::create([
                    'empresa_id' => $contrato->empresa_id,
                    'cliente_id' => $contrato->cliente_id,
                    'contrato_id' => $contrato->id,
                    'total' => $valor,
                    'status' => 'ABERTA',
                ]);

                $descricao = 'eSocial - ' . $referencia->format('m/Y');
                VendaItem::create([
                    'venda_id' => $venda->id,
                    'servico_id' => $servicoEsocialId,
                    'descricao_snapshot' => $descricao,
                    'preco_unitario_snapshot' => $valor,
                    'quantidade' => 1,
                    'subtotal_snapshot' => $valor,
                ]);


                $service->atualizarStatusVenda($venda->id);
            });

            $criados++;
        }

        $this->info(sprintf('Vendas criadas: %d | Ignorados: %d', $criados, $ignorados));
        Log::info('eSocial mensal processado', [
            'referencia' => $referencia->toDateString(),
            'clientes_processados' => count($clientesProcessados),
            'vendas_criadas' => $criados,
            'ignorados' => $ignorados,
        ]);

        return self::SUCCESS;
    }
}
