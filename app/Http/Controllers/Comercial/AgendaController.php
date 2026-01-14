<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\AgendaTarefa;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $user = $request->user();
            if ($user?->isMaster()) {
                return redirect()->route('master.agenda-vendedores.index');
            }
            if (!$user || mb_strtolower(optional($user->papel)->nome ?? '') !== 'comercial') {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $dataSelecionada = $this->dataSelecionada($request);
        $periodo = $this->periodoSelecionado($request);
        [$inicio, $fim] = $this->intervaloDoPeriodo($dataSelecionada, $periodo);

        $tarefas = AgendaTarefa::query()
            ->where('user_id', $user->id)
            ->when(
                $periodo === 'dia',
                fn ($q) => $q->whereDate('data', $dataSelecionada->toDateString()),
                fn ($q) => $q->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            )
            ->orderBy('hora')
            ->orderBy('id')
            ->get();

        $pendentes = $tarefas->where('status', 'PENDENTE');
        $concluidas = $tarefas->where('status', 'CONCLUIDA');

        $kpis = [
            'aberto_total' => AgendaTarefa::query()
                ->where('user_id', $user->id)
                ->where('status', 'PENDENTE')
                ->count(),
            'pendentes_periodo' => $pendentes->count(),
            'concluidas_periodo' => $concluidas->count(),
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
                ->where('user_id', $user->id)
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
                ->where('user_id', $user->id)
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

        return view('comercial.agenda.index', compact(
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

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validateTarefa($request);

        AgendaTarefa::create([
            'empresa_id' => $user->empresa_id,
            'user_id' => $user->id,
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'tipo' => $data['tipo'],
            'prioridade' => $data['prioridade'],
            'data' => $data['data'],
            'hora' => $data['hora'] ?? null,
            'cliente' => $data['cliente'] ?? null,
            'status' => 'PENDENTE',
        ]);

        return redirect()
            ->route('comercial.agenda.index', ['data' => $data['data']])
            ->with('ok', 'Tarefa criada.');
    }

    public function update(Request $request, AgendaTarefa $tarefa): RedirectResponse
    {
        $this->authorizeOwner($request, $tarefa);
        if ($tarefa->status !== 'PENDENTE') {
            return back()->with('erro', 'So e possivel editar tarefas pendentes.');
        }

        $data = $this->validateTarefa($request);

        $tarefa->update([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'tipo' => $data['tipo'],
            'prioridade' => $data['prioridade'],
            'data' => $data['data'],
            'hora' => $data['hora'] ?? null,
            'cliente' => $data['cliente'] ?? null,
        ]);

        return redirect()
            ->route('comercial.agenda.index', ['data' => $data['data'], 'periodo' => $request->query('periodo', 'dia')])
            ->with('ok', 'Tarefa atualizada.');
    }

    public function concluir(Request $request, AgendaTarefa $tarefa): RedirectResponse
    {
        $this->authorizeOwner($request, $tarefa);

        $tarefa->update([
            'status' => 'CONCLUIDA',
            'concluida_em' => now(),
        ]);

        return redirect()
            ->route('comercial.agenda.index', ['data' => $tarefa->data->toDateString()])
            ->with('ok', 'Tarefa concluída.');
    }

    public function destroy(Request $request, AgendaTarefa $tarefa): RedirectResponse
    {
        $this->authorizeOwner($request, $tarefa);
        if ($tarefa->status !== 'PENDENTE') {
            return back()->with('erro', 'Só é possível remover tarefas pendentes.');
        }

        $data = $tarefa->data->toDateString();
        $tarefa->delete();

        return redirect()
            ->route('comercial.agenda.index', ['data' => $data])
            ->with('ok', 'Tarefa removida.');
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
        $periodo = strtolower((string) $request->query('periodo', 'dia'));
        return in_array($periodo, ['dia', 'semana', 'mes', 'ano'], true) ? $periodo : 'dia';
    }

    private function intervaloDoPeriodo(Carbon $base, string $periodo): array
    {
        if ($periodo === 'semana') {
            $inicio = $base->copy()->startOfWeek();
            $fim = $base->copy()->endOfWeek();
            return [$inicio, $fim];
        }

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

        $inicio = $base->copy()->startOfDay();
        $fim = $base->copy()->endOfDay();
        return [$inicio, $fim];
    }

    private function validateTarefa(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'tipo' => ['required', 'string', 'max:50'],
            'prioridade' => ['required', 'string', 'max:20'],
            'data' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'cliente' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function authorizeOwner(Request $request, AgendaTarefa $tarefa): void
    {
        if ($tarefa->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
