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

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $data = $this->validateTarefa($request, $empresaId);
        $vendedor = $this->buscarVendedor($empresaId, (int) $data['user_id']);

        if (!$vendedor) {
            return back()->withInput()->with('erro', 'Vendedor inválido.');
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
            ->route('master.agenda-vendedores.index', ['data' => $data['data']])
            ->with('ok', 'Tarefa criada.');
    }

    public function update(Request $request, AgendaTarefa $tarefa): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->authorizeEmpresa($tarefa, $empresaId);

        if ($tarefa->status !== 'PENDENTE') {
            return back()->with('erro', 'Só é possível editar tarefas pendentes.');
        }

        $data = $this->validateTarefa($request, $empresaId);
        $vendedor = $this->buscarVendedor($empresaId, (int) $data['user_id']);

        if (!$vendedor) {
            return back()->withInput()->with('erro', 'Vendedor inválido.');
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
                'data' => $data['data'],
                'periodo' => $request->query('periodo', 'mes'),
                'vendedor' => $request->query('vendedor', 'todos'),
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
            ->route('master.agenda-vendedores.index', ['data' => $tarefa->data->toDateString()])
            ->with('ok', 'Tarefa concluída.');
    }

    public function destroy(Request $request, AgendaTarefa $tarefa): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->authorizeEmpresa($tarefa, $empresaId);

        if ($tarefa->status !== 'PENDENTE') {
            return back()->with('erro', 'Só é possível remover tarefas pendentes.');
        }

        $data = $tarefa->data->toDateString();
        $tarefa->delete();

        return redirect()
            ->route('master.agenda-vendedores.index', ['data' => $data])
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
