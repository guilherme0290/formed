<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AgendaTarefa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgendaVendedorController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id ?? 1;

        $agendaDataSelecionada = $this->mesSelecionado($request);
        $inicioMes = $agendaDataSelecionada->copy()->startOfMonth();
        $fimMes = $agendaDataSelecionada->copy()->endOfMonth();

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

        $agendaDiaSelecionado = $this->diaSelecionado($request, $agendaDataSelecionada);

        $agendaTarefasMes = AgendaTarefa::query()
            ->where('empresa_id', $empresaId)
            ->when($vendedorId, fn ($q) => $q->where('user_id', $vendedorId))
            ->whereBetween('data', [$inicioMes->toDateString(), $fimMes->toDateString()])
            ->with('usuario:id,name')
            ->orderBy('data')
            ->orderBy('hora')
            ->orderBy('id')
            ->get();

        $agendaTarefasPorData = $agendaTarefasMes->groupBy(fn ($tarefa) => $tarefa->data->toDateString());

        $agendaContagensPorData = [];
        foreach ($agendaTarefasPorData as $data => $itens) {
            $agendaContagensPorData[$data] = [
                'pendentes' => $itens->where('status', 'PENDENTE')->count(),
                'concluidas' => $itens->where('status', 'CONCLUIDA')->count(),
            ];
        }

        $agendaKpis = [
            'aberto_total' => AgendaTarefa::query()
                ->where('empresa_id', $empresaId)
                ->when($vendedorId, fn ($q) => $q->where('user_id', $vendedorId))
                ->where('status', 'PENDENTE')
                ->count(),
            'pendentes_dia' => AgendaTarefa::query()
                ->where('empresa_id', $empresaId)
                ->when($vendedorId, fn ($q) => $q->where('user_id', $vendedorId))
                ->whereDate('data', $agendaDiaSelecionado)
                ->where('status', 'PENDENTE')
                ->count(),
            'concluidas_dia' => AgendaTarefa::query()
                ->where('empresa_id', $empresaId)
                ->when($vendedorId, fn ($q) => $q->where('user_id', $vendedorId))
                ->whereDate('data', $agendaDiaSelecionado)
                ->where('status', 'CONCLUIDA')
                ->count(),
        ];

        $agendaMesAnterior = $agendaDataSelecionada->copy()->subMonthNoOverflow()->startOfMonth();
        $agendaMesProximo = $agendaDataSelecionada->copy()->addMonthNoOverflow()->startOfMonth();

        $agendaDias = [];
        $primeiroDiaSemana = $inicioMes->dayOfWeek;

        for ($i = 0; $i < $primeiroDiaSemana; $i++) {
            $agendaDias[] = null;
        }

        for ($dia = $inicioMes->copy(); $dia->lte($fimMes); $dia->addDay()) {
            $agendaDias[] = $dia->copy();
        }

        while (count($agendaDias) % 7 !== 0) {
            $agendaDias[] = null;
        }

        return view('master.agenda-vendedores.index', compact(
            'vendedores',
            'vendedorSelecionado',
            'agendaDataSelecionada',
            'agendaDiaSelecionado',
            'agendaTarefasPorData',
            'agendaContagensPorData',
            'agendaKpis',
            'agendaMesAnterior',
            'agendaMesProximo',
            'agendaDias'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $data = $this->validateTarefa($request, $empresaId);
        $vendedor = $this->buscarVendedor($empresaId, (int) $data['user_id']);

        if (!$vendedor) {
            return back()->withInput()->with('erro', 'Vendedor invalido.');
        }

        AgendaTarefa::create([
            'empresa_id' => $empresaId,
            'user_id' => $vendedor->id,
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
            ->route('master.agenda-vendedores.index', [
                'agenda_data' => $request->input('agenda_data', $data['data']),
                'agenda_dia' => $data['data'],
                'vendedor' => $request->input('vendedor', 'todos'),
            ])
            ->with('ok', 'Tarefa criada.');
    }

    public function update(Request $request, AgendaTarefa $tarefa): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->authorizeEmpresa($tarefa, $empresaId);

        if ($tarefa->status !== 'PENDENTE') {
            return back()->with('erro', 'So e possivel editar tarefas pendentes.');
        }

        $data = $this->validateTarefa($request, $empresaId);
        $vendedor = $this->buscarVendedor($empresaId, (int) $data['user_id']);

        if (!$vendedor) {
            return back()->withInput()->with('erro', 'Vendedor invalido.');
        }

        $tarefa->update([
            'user_id' => $vendedor->id,
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'tipo' => $data['tipo'],
            'prioridade' => $data['prioridade'],
            'data' => $data['data'],
            'hora' => $data['hora'] ?? null,
            'cliente' => $data['cliente'] ?? null,
        ]);

        return redirect()
            ->route('master.agenda-vendedores.index', [
                'agenda_data' => $request->input('agenda_data', $data['data']),
                'agenda_dia' => $data['data'],
                'vendedor' => $request->input('vendedor', 'todos'),
            ])
            ->with('ok', 'Tarefa atualizada.');
    }

    public function concluir(Request $request, AgendaTarefa $tarefa): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->authorizeEmpresa($tarefa, $empresaId);

        $tarefa->update([
            'status' => 'CONCLUIDA',
            'concluida_em' => now(),
        ]);

        return redirect()
            ->route('master.agenda-vendedores.index', [
                'agenda_data' => $request->input('agenda_data', $tarefa->data->toDateString()),
                'agenda_dia' => $tarefa->data->toDateString(),
                'vendedor' => $request->input('vendedor', 'todos'),
            ])
            ->with('ok', 'Tarefa concluida.');
    }

    public function destroy(Request $request, AgendaTarefa $tarefa): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->authorizeEmpresa($tarefa, $empresaId);

        if ($tarefa->status !== 'PENDENTE') {
            return back()->with('erro', 'So e possivel remover tarefas pendentes.');
        }

        $data = $tarefa->data->toDateString();
        $tarefa->delete();

        return redirect()
            ->route('master.agenda-vendedores.index', [
                'agenda_data' => $request->input('agenda_data', $data),
                'agenda_dia' => $data,
                'vendedor' => $request->input('vendedor', 'todos'),
            ])
            ->with('ok', 'Tarefa removida.');
    }

    private function mesSelecionado(Request $request): Carbon
    {
        $agendaData = (string) $request->query('agenda_data', $request->query('data', now()->toDateString()));

        try {
            if (preg_match('/^\d{4}-\d{2}$/', $agendaData) === 1) {
                return Carbon::createFromFormat('Y-m', $agendaData)->startOfMonth();
            }

            return Carbon::parse($agendaData)->startOfMonth();
        } catch (\Throwable $e) {
            return Carbon::today()->startOfMonth();
        }
    }

    private function diaSelecionado(Request $request, Carbon $mes): string
    {
        $agendaDia = (string) $request->query('agenda_dia', '');

        if ($agendaDia === '') {
            $hoje = now();
            if ($hoje->isSameMonth($mes)) {
                return $hoje->toDateString();
            }

            return $mes->copy()->startOfMonth()->toDateString();
        }

        try {
            $dia = Carbon::parse($agendaDia);
        } catch (\Throwable $e) {
            return $mes->copy()->startOfMonth()->toDateString();
        }

        if (!$dia->isSameMonth($mes)) {
            return $mes->copy()->startOfMonth()->toDateString();
        }

        return $dia->toDateString();
    }

    private function validateTarefa(Request $request, int $empresaId): array
    {
        return $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('empresa_id', $empresaId),
            ],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'tipo' => ['required', 'string', 'max:50'],
            'prioridade' => ['required', 'string', 'max:20'],
            'data' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'agenda_data' => ['nullable', 'string', 'max:20'],
            'agenda_dia' => ['nullable', 'date'],
            'vendedor' => ['nullable', 'string', 'max:20'],
        ]);
    }

    private function buscarVendedor(int $empresaId, int $userId): ?User
    {
        return User::query()
            ->where('empresa_id', $empresaId)
            ->where('id', $userId)
            ->whereHas('papel', function ($q) {
                $q->whereRaw('LOWER(nome) = ?', ['comercial']);
            })
            ->first();
    }

    private function authorizeEmpresa(AgendaTarefa $tarefa, int $empresaId): void
    {
        if ($tarefa->empresa_id !== $empresaId) {
            abort(403);
        }
    }
}
