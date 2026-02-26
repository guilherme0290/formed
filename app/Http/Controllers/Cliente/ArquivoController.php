<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Services\FuncionarioArquivosZipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArquivoController extends Controller
{
    public function downloadFuncionario(Request $request, Funcionario $funcionario, FuncionarioArquivosZipService $zipService)
    {
        $user = $request->user();
        if (!$user || !$user->id) {
            return redirect()->route('login', ['redirect' => 'cliente']);
        }

        $clienteId = (int) $request->session()->get('portal_cliente_id');
        if ($clienteId <= 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Nenhum cliente selecionado. Faca login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::findOrFail($clienteId);
        abort_unless((int) $funcionario->cliente_id === (int) $cliente->id, 403);

        try {
            $tarefaIds = Tarefa::query()
                ->where('cliente_id', $cliente->id)
                ->where(function ($q) use ($funcionario) {
                    $q->where('funcionario_id', $funcionario->id)
                        ->orWhereHas('treinamentoNr', function ($trQ) use ($funcionario) {
                            $trQ->where('funcionario_id', $funcionario->id);
                        });
                })
                ->where(function ($q) {
                    $q->whereNotNull('path_documento_cliente')
                        ->orWhereHas('anexos', function ($aq) {
                            $aq->whereRaw('LOWER(COALESCE(servico, "")) = ?', ['certificado_treinamento']);
                        });
                })
                ->pluck('id')
                ->all();

            $zipPath = $zipService->gerarZipPorIds($cliente, $tarefaIds, null, false);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        $zipName = 'arquivos-' . str_replace(' ', '-', mb_strtolower($funcionario->nome)) . '.zip';

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    public function downloadPorFuncionario(Request $request, FuncionarioArquivosZipService $zipService)
    {
        $user = $request->user();
        if (!$user || !$user->id) {
            return redirect()->route('login', ['redirect' => 'cliente']);
        }

        $clienteId = (int) $request->session()->get('portal_cliente_id');
        if ($clienteId <= 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Nenhum cliente selecionado. Faca login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $funcionarioId = (int) $request->input('funcionario_id', 0);

        try {
            if ($funcionarioId > 0) {
                $funcionario = Funcionario::query()
                    ->where('id', $funcionarioId)
                    ->where('cliente_id', $cliente->id)
                    ->firstOrFail();

                $tarefaIds = Tarefa::query()
                    ->where('cliente_id', $cliente->id)
                    ->where('funcionario_id', $funcionario->id)
                    ->whereNotNull('path_documento_cliente')
                    ->whereHas('servico', function ($sq) {
                        $sq->where(function ($w) {
                            $w->whereRaw('LOWER(TRIM(nome)) = ?', ['pcmso'])
                                ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['pgr'])
                                ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['ltcat'])
                                ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['apr']);
                        });
                    })
                    ->pluck('id')
                    ->all();

                $zipPath = $zipService->gerarZipPorIds($cliente, $tarefaIds, $funcionario, false);
                $zipName = 'arquivos-' . str_replace(' ', '-', mb_strtolower($funcionario->nome)) . '.zip';

                return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
            }

            $tarefaIds = Tarefa::query()
                ->where('cliente_id', $cliente->id)
                ->whereNotNull('funcionario_id')
                ->whereNotNull('path_documento_cliente')
                ->whereHas('servico', function ($sq) {
                    $sq->where(function ($w) {
                        $w->whereRaw('LOWER(TRIM(nome)) = ?', ['pcmso'])
                            ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['pgr'])
                            ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['ltcat'])
                            ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['apr']);
                    });
                })
                ->pluck('id')
                ->all();

            $zipPath = $zipService->gerarZipPorIds($cliente, $tarefaIds, null, false);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()->download($zipPath, 'arquivos-todos-funcionarios.zip')->deleteFileAfterSend(true);
    }

    public function index(Request $request)
    {
        // valida sessao do portal cliente
        $user = $request->user();
        if (!$user || !$user->id) {
            return redirect()->route('login', ['redirect' => 'cliente']);
        }

        $clienteId = (int) $request->session()->get('portal_cliente_id');
        if ($clienteId <= 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Nenhum cliente selecionado. Faca login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::find($clienteId);
        if (!$cliente) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Cliente invalido. Acesse novamente pelo portal do cliente.');
        }

        $arquivosQuery = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->whereHas('servico', function ($sq) {
                $sq->where(function ($w) {
                    $w->whereRaw('LOWER(TRIM(nome)) = ?', ['pcmso'])
                        ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['pgr'])
                        ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['ltcat'])
                        ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['apr']);
                });
            })
            ->where(function ($q) {
                $q->whereNotNull('path_documento_cliente')
                    ->orWhereHas('anexos', function ($aq) {
                        $aq->whereRaw('LOWER(COALESCE(servico, "")) = ?', ['certificado_treinamento']);
                    });
            })
            ->with([
                'servico',
                'coluna',
                'anexos',
                'treinamentoNrDetalhes',
                'treinamentoNr.funcionario:id,nome',
            ]);

        if ($request->filled('data_inicio')) {
            $arquivosQuery->whereDate('finalizado_em', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $arquivosQuery->whereDate('finalizado_em', '<=', $request->input('data_fim'));
        }

        if ($request->filled('servico')) {
            $arquivosQuery->where('servico_id', (int) $request->input('servico'));
        }

        if ($request->filled('funcionario_id')) {
            $arquivosQuery->where('funcionario_id', (int) $request->input('funcionario_id'));
        }

        $arquivos = $arquivosQuery
            ->orderByDesc('finalizado_em')
            ->orderByDesc('updated_at')
            ->get();

        $funcionariosComArquivosIds = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->whereHas('servico', function ($sq) {
                $sq->where(function ($w) {
                    $w->whereRaw('LOWER(TRIM(nome)) = ?', ['pcmso'])
                        ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['pgr'])
                        ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['ltcat'])
                        ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['apr']);
                });
            })
            ->whereNotNull('funcionario_id')
            ->whereNotNull('path_documento_cliente')
            ->distinct()
            ->pluck('funcionario_id')
            ->filter()
            ->values();

        $funcionariosComArquivos = $funcionariosComArquivosIds->isNotEmpty()
            ? Funcionario::query()
                ->whereIn('id', $funcionariosComArquivosIds->all())
                ->orderBy('nome')
                ->get(['id', 'nome'])
            : collect();

        $servicos = Servico::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where(function ($w) {
                $w->whereRaw('LOWER(TRIM(nome)) = ?', ['pcmso'])
                    ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['pgr'])
                    ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['ltcat'])
                    ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['apr']);
            })
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('clientes.arquivos.index', [
            'cliente' => $cliente,
            'user' => $user,
            'arquivos' => $arquivos,
            'servicos' => $servicos,
            'funcionariosComArquivos' => $funcionariosComArquivos,
        ]);
    }

    public function downloadSelecionados(Request $request, FuncionarioArquivosZipService $zipService)
    {
        $user = $request->user();
        if (!$user || !$user->id) {
            return redirect()->route('login', ['redirect' => 'cliente']);
        }

        $clienteId = (int) $request->session()->get('portal_cliente_id');
        if ($clienteId <= 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Nenhum cliente selecionado. Faca login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $tarefaIds = (array) $request->input('tarefa_ids', []);
        $includeAnexos = $request->boolean('include_anexos');
        $tarefaIds = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->whereIn('id', collect($tarefaIds)->map(fn ($id) => (int) $id)->all())
            ->whereHas('servico', function ($sq) {
                $sq->where(function ($w) {
                    $w->whereRaw('LOWER(TRIM(nome)) = ?', ['pcmso'])
                        ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['pgr'])
                        ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['ltcat'])
                        ->orWhereRaw('LOWER(TRIM(nome)) = ?', ['apr']);
                });
            })
            ->pluck('id')
            ->all();

        try {
            $zipPath = $zipService->gerarZipPorIds($cliente, $tarefaIds, null, $includeAnexos);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()->download($zipPath, 'arquivos-selecionados.zip')->deleteFileAfterSend(true);
    }

    public function downloadSelecionadosFuncionario(
        Request $request,
        Funcionario $funcionario,
        FuncionarioArquivosZipService $zipService
    ) {
        $user = $request->user();
        if (!$user || !$user->id) {
            return redirect()->route('login', ['redirect' => 'cliente']);
        }

        $clienteId = (int) $request->session()->get('portal_cliente_id');
        if ($clienteId <= 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Nenhum cliente selecionado. Faca login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::findOrFail($clienteId);
        abort_unless((int) $funcionario->cliente_id === (int) $cliente->id, 403);
        $tarefaIdsEntrada = collect((array) $request->input('tarefa_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $tarefaIds = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->whereIn('id', $tarefaIdsEntrada)
            ->where(function ($q) use ($funcionario) {
                $q->where('funcionario_id', $funcionario->id)
                    ->orWhereHas('treinamentoNr', function ($trQ) use ($funcionario) {
                        $trQ->where('funcionario_id', $funcionario->id);
                    });
            })
            ->pluck('id')
            ->all();

        try {
            $zipPath = $zipService->gerarZipPorIds($cliente, $tarefaIds, null, false);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $zipName = 'arquivos-' . str_replace(' ', '-', mb_strtolower($funcionario->nome)) . '-selecionados.zip';
        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
}
