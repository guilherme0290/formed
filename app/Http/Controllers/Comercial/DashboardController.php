<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\AgendaTarefa;
use App\Models\Comissao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $empresaId = $user?->empresa_id ?? null;
        $agendaDataSelecionada = $this->agendaDataSelecionada($request);
        $agendaInicioMes = $agendaDataSelecionada->copy()->startOfMonth();
        $agendaFimMes = $agendaDataSelecionada->copy()->endOfMonth();
        $agendaDiaSelecionado = $request->query('agenda_dia');

        $ranking = $this->rankingVendedores($empresaId);
        $agendaTarefas = AgendaTarefa::query()
            ->where('user_id', $user?->id)
            ->whereBetween('data', [$agendaInicioMes->toDateString(), $agendaFimMes->toDateString()])
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

        if (!$agendaDiaSelecionado || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $agendaDiaSelecionado)) {
            $agendaDiaSelecionado = $agendaDataSelecionada->toDateString();
        }

        $agendaKpis = [
            'aberto_total' => AgendaTarefa::query()
                ->where('user_id', $user?->id)
                ->where('status', 'PENDENTE')
                ->count(),
            'pendentes_dia' => AgendaTarefa::query()
                ->where('user_id', $user?->id)
                ->whereDate('data', $agendaDiaSelecionado)
                ->where('status', 'PENDENTE')
                ->count(),
            'concluidas_dia' => AgendaTarefa::query()
                ->where('user_id', $user?->id)
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
}
