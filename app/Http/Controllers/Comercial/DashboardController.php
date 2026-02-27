<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\AgendaTarefa;
use App\Models\Comissao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $empresaId = $user?->empresa_id ?? null;
        $isMaster = (bool) ($user?->isMaster());
        $agendaDataSelecionada = $this->agendaDataSelecionada($request);
        $agendaInicioMes = $agendaDataSelecionada->copy()->startOfMonth();
        $agendaFimMes = $agendaDataSelecionada->copy()->endOfMonth();
        $agendaDiaSelecionado = $this->agendaDiaSelecionado($request, $agendaDataSelecionada);

        $ranking = $this->rankingVendedores($empresaId);
        $vendedores = collect();
        $vendedorSelecionado = $isMaster ? (string) $request->query('vendedor', 'todos') : (string) ($user?->id ?? '');
        $vendedorId = $isMaster ? null : $user?->id;

        if ($isMaster) {
            $vendedores = User::query()
                ->where('empresa_id', $empresaId)
                ->whereHas('papel', function ($q) {
                    $q->whereRaw('LOWER(nome) = ?', ['comercial']);
                })
                ->orderBy('name')
                ->get(['id', 'name']);

            if ($vendedorSelecionado !== 'todos') {
                $vendedorId = $vendedores->firstWhere('id', (int) $vendedorSelecionado)?->id;
                $vendedorSelecionado = $vendedorId ? (string) $vendedorId : 'todos';
            }
        }

        $agendaQuery = AgendaTarefa::query()
            ->where('empresa_id', $empresaId)
            ->when(
                $isMaster && $vendedorSelecionado === 'todos',
                fn ($query) => $query->whereIn('user_id', $vendedores->pluck('id'))
            )
            ->when($vendedorId, fn ($query) => $query->where('user_id', $vendedorId));

        $agendaTarefas = (clone $agendaQuery)
            ->whereBetween('data', [$agendaInicioMes->toDateString(), $agendaFimMes->toDateString()])
            ->with('usuario:id,name')
            ->orderBy('data')
            ->orderBy('hora')
            ->orderBy('id')
            ->get();

        $agendaTarefasPorData = $agendaTarefas->groupBy(fn ($tarefa) => $tarefa->data->toDateString());

        $agendaContagensPorData = [];
        foreach ($agendaTarefasPorData as $data => $itens) {
            $agendaContagensPorData[$data] = [
                'pendentes' => $itens->where('status', 'PENDENTE')->count(),
                'concluidas' => $itens->where('status', 'CONCLUIDA')->count(),
            ];
        }

        $agendaDias = [];
        $primeiroDiaSemana = $agendaInicioMes->dayOfWeek;
        for ($i = 0; $i < $primeiroDiaSemana; $i++) {
            $agendaDias[] = null;
        }
        for ($dia = $agendaInicioMes->copy(); $dia->lte($agendaFimMes); $dia->addDay()) {
            $agendaDias[] = $dia->copy();
        }
        while (count($agendaDias) % 7 !== 0) {
            $agendaDias[] = null;
        }

        $agendaKpis = [
            'aberto_total' => (clone $agendaQuery)
                ->where('status', 'PENDENTE')
                ->count(),
            'pendentes_dia' => (clone $agendaQuery)
                ->whereDate('data', $agendaDiaSelecionado)
                ->where('status', 'PENDENTE')
                ->count(),
            'concluidas_dia' => (clone $agendaQuery)
                ->whereDate('data', $agendaDiaSelecionado)
                ->where('status', 'CONCLUIDA')
                ->count(),
        ];

        return view('comercial.dashboard', [
            'ranking' => $ranking,
            'agendaDataSelecionada' => $agendaDataSelecionada,
            'agendaDiaSelecionado' => $agendaDiaSelecionado,
            'agendaDias' => $agendaDias,
            'agendaContagensPorData' => $agendaContagensPorData,
            'agendaTarefasPorData' => $agendaTarefasPorData,
            'agendaMesAnterior' => $agendaDataSelecionada->copy()->subMonthNoOverflow(),
            'agendaMesProximo' => $agendaDataSelecionada->copy()->addMonthNoOverflow(),
            'agendaKpis' => $agendaKpis,
            'isMaster' => $isMaster,
            'vendedores' => $vendedores,
            'vendedorSelecionado' => $vendedorSelecionado,
        ]);
    }

    private function rankingVendedores(?int $empresaId): array
    {
        $hoje = Carbon::now();
        $inicioMes = $hoje->copy()->startOfMonth()->toDateString();
        $fimMes = $hoje->copy()->endOfMonth()->toDateString();

        // Soma do faturamento das vendas do mês, agrupado por vendedor (cliente->vendedor_id)
        $rows = Comissao::query()
            ->join('users', 'users.id', '=', 'comissoes.vendedor_id')
            ->join('papeis', 'papeis.id', '=', 'users.papel_id')
            ->where('comissoes.empresa_id', $empresaId)
            ->whereBetween(DB::raw('DATE(COALESCE(comissoes.gerada_em, comissoes.created_at))'), [$inicioMes, $fimMes])
            ->whereRaw('LOWER(papeis.nome) LIKE ?', ['%comercial%'])
            ->where('comissoes.status', '!=', 'CANCELADA')
            ->select(
                'users.id as vendedor_id',
                'users.name as vendedor_nome',
                DB::raw('SUM(comissoes.valor_comissao) as comissao')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('comissao')
            ->limit(3)
            ->get();

        $posicoes = [];
        $medalhas = ['ouro', 'prata', 'bronze'];

        foreach ($rows as $idx => $row) {
            $posicoes[] = [
                'posicao' => $idx + 1,
                'medalha' => $medalhas[$idx] ?? 'ouro',
                'nome' => $row->vendedor_nome,
                'comissao' => (float) $row->comissao,
            ];
        }

        return [
            'mesAtual' => $hoje->locale('pt_BR')->translatedFormat('F Y'),
            'itens' => $posicoes,
            'semDados' => $rows->isEmpty(),
        ];
    }

    private function agendaDataSelecionada(Request $request): Carbon
    {
        $data = (string) $request->query('agenda_data', '');

        try {
            return $data !== '' ? Carbon::parse($data) : Carbon::today();
        } catch (\Throwable $e) {
            return Carbon::today();
        }
    }

    private function agendaDiaSelecionado(Request $request, Carbon $agendaDataSelecionada): string
    {
        $agendaDia = (string) $request->query('agenda_dia', '');

        if ($agendaDia === '') {
            $hoje = Carbon::today();
            if ($hoje->isSameMonth($agendaDataSelecionada)) {
                return $hoje->toDateString();
            }

            return $agendaDataSelecionada->copy()->startOfMonth()->toDateString();
        }

        try {
            $dia = Carbon::parse($agendaDia);
        } catch (\Throwable $e) {
            return $agendaDataSelecionada->copy()->startOfMonth()->toDateString();
        }

        if (!$dia->isSameMonth($agendaDataSelecionada)) {
            return $agendaDataSelecionada->copy()->startOfMonth()->toDateString();
        }

        return $dia->toDateString();
    }
}
