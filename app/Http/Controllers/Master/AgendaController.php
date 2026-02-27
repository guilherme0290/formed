<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AgendaTarefa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $user = $request->user();
            if (!$user || !$user->isMaster()) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index(Request $request): RedirectResponse
    {
        $data = $request->query('data', now()->toDateString());

        return redirect()->route('master.dashboard', [
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

        return redirect()
            ->route('master.dashboard', [
                'agenda_data' => $request->input('agenda_data', $data['data']),
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

        return redirect()
            ->route('master.dashboard', [
                'agenda_data' => $request->input('agenda_data', $data['data']),
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

        return redirect()
            ->route('master.dashboard', [
                'agenda_data' => $request->input('agenda_data', $tarefa->data->toDateString()),
                'agenda_dia' => $tarefa->data->toDateString(),
            ])
            ->with('ok', 'Tarefa concluida.');
    }

    public function destroy(Request $request, AgendaTarefa $tarefa): RedirectResponse
    {
        $this->authorizeOwner($request, $tarefa);
        if ($tarefa->status !== 'PENDENTE') {
            return back()->with('erro', 'So e possivel remover tarefas pendentes.');
        }

        $data = $tarefa->data->toDateString();
        $tarefa->delete();

        return redirect()
            ->route('master.dashboard', [
                'agenda_data' => $request->input('agenda_data', $data),
                'agenda_dia' => $data,
            ])
            ->with('ok', 'Tarefa removida.');
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
        $user = $request->user();
        if ($tarefa->empresa_id !== $user->empresa_id || $tarefa->user_id !== $user->id) {
            abort(403);
        }
    }
}
