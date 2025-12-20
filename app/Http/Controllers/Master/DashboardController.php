<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Proposta;
use App\Models\Tarefa;
use App\Models\Venda;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $empresaId = auth()->user()->empresa_id ?? null;

        $visaoEmpresa  = $this->metricasEmpresa($empresaId);
        $operacionais = $this->metricasOperacionais($empresaId);
        $comerciais   = $this->metricasComerciais($empresaId);

        return view('master.dashboard', [
            'visaoEmpresa'  => $visaoEmpresa,
            'operacionais' => $operacionais,
            'comerciais'   => $comerciais,
        ]);
    }

    private function metricasEmpresa(?int $empresaId): array
    {
        $clientesAtivos = \App\Models\Cliente::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->where('ativo', true)
            ->count();

        $faturamentoGlobal = (float) Venda::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->sum('total');

        // tempo médio fica fixo como solicitado
        $tempoMedio = '48h';

        $servicosConsumidos = \App\Models\VendaItem::query()
            ->whereHas('venda', function ($q) use ($empresaId) {
                $q->when($empresaId, fn ($qq) => $qq->where('empresa_id', $empresaId));
            })
            ->count();

        return [
            'clientes_ativos'     => $clientesAtivos,
            'faturamento_global'  => $faturamentoGlobal,
            'tempo_medio'         => $tempoMedio,
            'servicos_consumidos' => $servicosConsumidos,
        ];
    }

    private function metricasOperacionais(?int $empresaId): array
    {
        $hoje = Carbon::today()->toDateString();

        $base = Tarefa::query()
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId));

        $total = (clone $base)->count();
        $finalizadas = (clone $base)->where('kanban_colunas.finaliza', true)->count();

        $taxaConclusao = $total > 0 ? round(($finalizadas / $total) * 100) : 0;

        $atrasadas = (clone $base)
            ->where('kanban_colunas.finaliza', false)
            ->whereDate('tarefas.fim_previsto', '<', $hoje)
            ->count();

        $slaBase = (clone $base)
            ->whereNotNull('tarefas.fim_previsto')
            ->whereNotNull('tarefas.finalizado_em')
            ->where('kanban_colunas.finaliza', true);

        $slaTotal = (clone $slaBase)->count();
        $slaDentro = (clone $slaBase)
            ->whereColumn('tarefas.finalizado_em', '<=', 'tarefas.fim_previsto')
            ->count();

        $slaPercentual = $slaTotal > 0 ? round(($slaDentro / $slaTotal) * 100) : null;

        return [
            'taxa_conclusao' => $taxaConclusao,
            'atrasadas'      => $atrasadas,
            'sla_percentual' => $slaPercentual,
        ];
    }

    private function metricasComerciais(?int $empresaId): array
    {
        $vendas = Venda::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId));

        $ticketMedio = (float) $vendas->avg('total');

        $propostas = Proposta::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId));

        $totalPropostas = (clone $propostas)->count();
        $propostasFechadas = (clone $propostas)
            ->where('status', 'FECHADA')
            ->count();
        $propostasEmAberto = (clone $propostas)
            ->whereNotIn('status', ['FECHADA', 'CANCELADA'])
            ->count();

        $taxaConversao = $totalPropostas > 0 ? round(($propostasFechadas / $totalPropostas) * 100) : 0;

        return [
            'ticket_medio'        => $ticketMedio,
            'taxa_conversao'      => $taxaConversao,
            'propostas_em_aberto' => $propostasEmAberto,
        ];
    }
}
