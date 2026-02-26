<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\AgendaTarefa;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function index(Request $request): RedirectResponse
    {
        $data = $request->query('data', now()->toDateString());

        return redirect()->route('comercial.dashboard', [
            'agenda_data' => $data,
            'agenda_dia' => $data,
        ]);
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

        if ($request->input('origem') === 'dashboard') {
            return redirect()
                ->route('comercial.dashboard', [
                    'agenda_data' => $request->input('agenda_data', $data['data']),
                    'agenda_dia' => $data['data'],
                ])
                ->with('ok', 'Tarefa criada.');
        }

        return redirect()
            ->route('comercial.dashboard', [
                'agenda_data' => $data['data'],
                'agenda_dia' => $data['data'],
            ])
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

        if ($request->input('origem') === 'dashboard') {
            return redirect()
                ->route('comercial.dashboard', [
                    'agenda_data' => $request->input('agenda_data', $data['data']),
                    'agenda_dia' => $data['data'],
                ])
                ->with('ok', 'Tarefa atualizada.');
        }

        return redirect()
            ->route('comercial.dashboard', [
                'agenda_data' => $data['data'],
                'agenda_dia' => $data['data'],
            ])
            ->with('ok', 'Tarefa atualizada.');
    }

    public function concluir(Request $request, AgendaTarefa $tarefa): RedirectResponse
    {
        $this->authorizeOwner($request, $tarefa);

        $tarefa->update([
            'status' => 'CONCLUIDA',
            'concluida_em' => now(),
        ]);

        if ($request->input('origem') === 'dashboard') {
            return redirect()
                ->route('comercial.dashboard', [
                    'agenda_data' => $request->input('agenda_data', $tarefa->data->toDateString()),
                    'agenda_dia' => $tarefa->data->toDateString(),
                ])
                ->with('ok', 'Tarefa concluida.');
        }

        return redirect()
            ->route('comercial.dashboard', ['agenda_data' => $tarefa->data->toDateString(), 'agenda_dia' => $tarefa->data->toDateString()])
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

        if ($request->input('origem') === 'dashboard') {
            return redirect()
                ->route('comercial.dashboard', [
                    'agenda_data' => $request->input('agenda_data', $data),
                    'agenda_dia' => $data,
                ])
                ->with('ok', 'Tarefa removida.');
        }

        return redirect()
            ->route('comercial.dashboard', ['agenda_data' => $data, 'agenda_dia' => $data])
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

