<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AgendaTarefa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaVendedorController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $dataSelecionada = $this->dataSelecionada($request);
        $periodo = $this->periodoSelecionado($request);
        [$inicio, $fim] = $this->intervaloDoPeriodo($dataSelecionada, $periodo);

        $vendedores = User::query()
            ->where('empresa_id', $empresaId)
            ->whereHas('papel', function ($q) {
                $q->whereRaw('LOWER(nome) = ?', ['comercial']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $vendedorSelecionado = $request->query('vendedor', 'todos');
        $vendedorId = null;
        if ($vendedorSelecionado !== 'todos') {
            $vendedorId = $vendedores->firstWhere('id', (int) $vendedorSelecionado)?->id;
            $vendedorSelecionado = $vendedorId ? (string) $vendedorId : 'todos';
        }

        $tarefas = AgendaTarefa::query()
            ->where('empresa_id', $empresaId)
            ->when(
                $periodo === 'dia',
                fn ($q) => $q->whereDate('data', $dataSelecionada->toDateString()),
                fn ($q) => $q->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            )
            ->when($vendedorId, fn ($q) => $q->where('user_id', $vendedorId))
            ->with('usuario:id,name')
            ->orderBy('user_id')
            ->orderBy('hora')
            ->orderBy('id')
            ->get();

        $pendentes = $tarefas->where('status', 'PENDENTE');
        $concluidas = $tarefas->where('status', 'CONCLUIDA');

        $kpis = [
            'total' => $tarefas->count(),
            'pendentes' => $pendentes->count(),
            'concluidas' => $concluidas->count(),
        ];

        $inicioMes = $dataSelecionada->copy()->startOfMonth();
        $fimMes = $dataSelecionada->copy()->endOfMonth();

        $tarefasPorData = collect();
        $contagensPorData = [];
        $datasCalendario = [];
        $calendariosAno = [];

        if ($periodo === 'ano') {
            $inicioAno = $dataSelecionada->copy()->startOfYear();
            $fimAno = $dataSelecionada->copy()->endOfYear();

            $tarefasAno = AgendaTarefa::query()
                ->where('empresa_id', $empresaId)
                ->when($vendedorId, fn ($q) => $q->where('user_id', $vendedorId))
                ->whereBetween('data', [$inicioAno->toDateString(), $fimAno->toDateString()])
                ->orderBy('data')
                ->orderBy('hora')
                ->orderBy('id')
                ->get();

            $tarefasPorData = $tarefasAno->groupBy(fn ($tarefa) => $tarefa->data->toDateString());
            foreach ($tarefasPorData as $data => $itens) {
                $contagensPorData[$data] = [
                    'pendentes' => $itens->where('status', 'PENDENTE')->count(),
                    'concluidas' => $itens->where('status', 'CONCLUIDA')->count(),
                ];
            }

            for ($mes = 1; $mes <= 12; $mes++) {
                $inicio = $dataSelecionada->copy()->month($mes)->startOfMonth();
                $fim = $inicio->copy()->endOfMonth();
                $datas = [];
                $offset = $inicio->dayOfWeek;
                for ($i = 0; $i < $offset; $i++) {
                    $datas[] = null;
                }
                for ($dia = $inicio->copy(); $dia->lte($fim); $dia->addDay()) {
                    $datas[] = $dia->copy();
                }
                while (count($datas) % 7 !== 0) {
                    $datas[] = null;
                }
                $calendariosAno[] = [
                    'titulo' => $inicio->locale('pt_BR')->translatedFormat('F'),
                    'datas' => $datas,
                ];
            }
        } else {
            $tarefasMes = AgendaTarefa::query()
                ->where('empresa_id', $empresaId)
                ->when($vendedorId, fn ($q) => $q->where('user_id', $vendedorId))
                ->whereBetween('data', [$inicioMes->toDateString(), $fimMes->toDateString()])
                ->orderBy('data')
                ->orderBy('hora')
                ->orderBy('id')
                ->get();

            $tarefasPorData = $tarefasMes->groupBy(fn ($tarefa) => $tarefa->data->toDateString());
            foreach ($tarefasPorData as $data => $itens) {
                $contagensPorData[$data] = [
                    'pendentes' => $itens->where('status', 'PENDENTE')->count(),
                    'concluidas' => $itens->where('status', 'CONCLUIDA')->count(),
                ];
            }

            $primeiroDiaSemana = $inicioMes->dayOfWeek;
            for ($i = 0; $i < $primeiroDiaSemana; $i++) {
                $datasCalendario[] = null;
            }
            for ($dia = $inicioMes->copy(); $dia->lte($fimMes); $dia->addDay()) {
                $datasCalendario[] = $dia->copy();
            }
            while (count($datasCalendario) % 7 !== 0) {
                $datasCalendario[] = null;
            }
        }

        return view('master.agenda-vendedores.index', compact(
            'vendedores',
            'vendedorSelecionado',
            'tarefas',
            'pendentes',
            'concluidas',
            'kpis',
            'dataSelecionada',
            'periodo',
            'inicio',
            'fim',
            'tarefasPorData',
            'contagensPorData',
            'datasCalendario',
            'calendariosAno'
        ));
    }

    private function dataSelecionada(Request $request): Carbon
    {
        $data = $request->query('data');
        try {
            return $data ? Carbon::parse($data) : Carbon::today();
        } catch (\Throwable $e) {
            return Carbon::today();
        }
    }

    private function periodoSelecionado(Request $request): string
    {
        $periodo = strtolower((string) $request->query('periodo', 'mes'));
        return in_array($periodo, ['mes', 'ano'], true) ? $periodo : 'mes';
    }

    private function intervaloDoPeriodo(Carbon $base, string $periodo): array
    {
        if ($periodo === 'mes') {
            $inicio = $base->copy()->startOfMonth();
            $fim = $base->copy()->endOfMonth();
            return [$inicio, $fim];
        }

        if ($periodo === 'ano') {
            $inicio = $base->copy()->startOfYear();
            $fim = $base->copy()->endOfYear();
            return [$inicio, $fim];
        }

        $inicio = $base->copy()->startOfMonth();
        $fim = $base->copy()->endOfMonth();
        return [$inicio, $fim];
    }
}
