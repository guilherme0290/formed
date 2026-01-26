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
    public function index(\Illuminate\Http\Request $request)
    {
        $empresaId = auth()->user()->empresa_id ?? null;

        $visaoEmpresa  = $this->metricasEmpresa($empresaId);
        $operacionais = $this->metricasOperacionais($empresaId);
        $comerciais   = $this->metricasComerciais($empresaId);
        $agendamentosHoje = $this->resumoAgendamentosHoje($empresaId);

        return view('master.dashboard', [
            'visaoEmpresa'  => $visaoEmpresa,
            'operacionais' => $operacionais,
            'comerciais'   => $comerciais,
            'agendamentosHoje' => $agendamentosHoje,
        ]);
    }

    public function agendamentos(\Illuminate\Http\Request $request)
    {
        $empresaId = auth()->user()->empresa_id ?? null;
        $agendamentos = $this->metricasAgendamentos($empresaId, $request);

        return view('master.agendamentos', [
            'agendamentos' => $agendamentos,
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

    private function resumoAgendamentosHoje(?int $empresaId): array
    {
        $hoje = Carbon::today()->toDateString();

        $base = Tarefa::query()
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->whereDate('tarefas.inicio_previsto', $hoje);

        $abertas = (clone $base)->where('kanban_colunas.finaliza', false)->count();
        $fechadas = (clone $base)->where('kanban_colunas.finaliza', true)->count();

        return [
            'abertas' => $abertas,
            'fechadas' => $fechadas,
            'total' => $abertas + $fechadas,
            'data' => $hoje,
        ];
    }

    private function metricasAgendamentos(?int $empresaId, \Illuminate\Http\Request $request): array
    {
        $hoje = Carbon::today();
        $dataInicio = $this->parseDate($request->query('data_inicio'), $hoje);
        $dataFim = $this->parseDate($request->query('data_fim'), $dataInicio);
        if ($dataFim->lt($dataInicio)) {
            $dataFim = $dataInicio->copy();
        }

        $servicosSelecionados = $request->query('servicos', []);
        $servicosSelecionados = is_array($servicosSelecionados) ? $servicosSelecionados : [$servicosSelecionados];
        $servicosSelecionados = array_values(array_filter(array_map('intval', $servicosSelecionados)));

        $servicosDisponiveis = \App\Models\Servico::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $base = Tarefa::query()
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->whereDate('tarefas.inicio_previsto', '>=', $dataInicio->toDateString())
            ->whereDate('tarefas.inicio_previsto', '<=', $dataFim->toDateString());

        if (!empty($servicosSelecionados)) {
            $base->whereIn('tarefas.servico_id', $servicosSelecionados);
        }

        $abertas = (clone $base)->where('kanban_colunas.finaliza', false)->count();
        $fechadas = (clone $base)->where('kanban_colunas.finaliza', true)->count();
        $total = $abertas + $fechadas;

        $porServico = (clone $base)
            ->leftJoin('servicos', 'servicos.id', '=', 'tarefas.servico_id')
            ->selectRaw("COALESCE(servicos.nome, 'Sem servico') as servico_nome, COUNT(*) as total")
            ->groupBy('servico_nome')
            ->orderBy('servico_nome')
            ->get();

        return [
            'data_inicio' => $dataInicio->toDateString(),
            'data_fim' => $dataFim->toDateString(),
            'servicos_disponiveis' => $servicosDisponiveis,
            'servicos_selecionados' => $servicosSelecionados,
            'abertas' => $abertas,
            'fechadas' => $fechadas,
            'total' => $total,
            'por_servico' => $porServico,
        ];
    }

    private function parseDate(?string $raw, Carbon $fallback): Carbon
    {
        try {
            if ($raw) {
                return Carbon::parse($raw)->startOfDay();
            }
        } catch (\Throwable $e) {
            // fallback below
        }

        return $fallback->copy()->startOfDay();
    }
}
