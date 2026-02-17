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
                ->with('error', 'Nenhum cliente selecionado. Faça login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::findOrFail($clienteId);
        abort_unless((int) $funcionario->cliente_id === (int) $cliente->id, 403);

        try {
            $zipPath = $zipService->gerarZip($cliente, $funcionario);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        $zipName = 'arquivos-' . str_replace(' ', '-', mb_strtolower($funcionario->nome)) . '.zip';

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    public function index(Request $request)
    {
        // valida sessão do portal cliente
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
                ->with('error', 'Nenhum cliente selecionado. Faça login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::find($clienteId);
        if (!$cliente) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Cliente inválido. Acesse novamente pelo portal do cliente.');
        }

        $arquivosQuery = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('path_documento_cliente')
            ->with(['servico', 'coluna']);

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $arquivosQuery->where('titulo', 'like', '%' . $q . '%');
        }

        if ($request->filled('data_inicio')) {
            $arquivosQuery->whereDate('finalizado_em', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $arquivosQuery->whereDate('finalizado_em', '<=', $request->input('data_fim'));
        }

        if ($request->filled('servico')) {
            $arquivosQuery->where('servico_id', (int) $request->input('servico'));
        }

        $arquivos = $arquivosQuery
            ->orderByDesc('finalizado_em')
            ->orderByDesc('updated_at')
            ->get();

        $funcionariosComArquivosIds = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('funcionario_id')
            ->where(function ($q) {
                $q->whereNotNull('path_documento_cliente')
                    ->orWhereHas('anexos');
            })
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

        $servicosIds = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('path_documento_cliente')
            ->distinct()
            ->pluck('servico_id')
            ->filter()
            ->values();

        $servicos = $servicosIds->isNotEmpty()
            ? Servico::query()
                ->whereIn('id', $servicosIds->all())
                ->orderBy('nome')
                ->get()
            : collect();

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
                ->with('error', 'Nenhum cliente selecionado. FaÃ§a login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $tarefaIds = (array) $request->input('tarefa_ids', []);

        try {
            $zipPath = $zipService->gerarZipPorIds($cliente, $tarefaIds);
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
                ->with('error', 'Nenhum cliente selecionado. FaÃ§a login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::findOrFail($clienteId);
        abort_unless((int) $funcionario->cliente_id === (int) $cliente->id, 403);
        $tarefaIds = (array) $request->input('tarefa_ids', []);

        try {
            $zipPath = $zipService->gerarZipPorIds($cliente, $tarefaIds, $funcionario);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $zipName = 'arquivos-' . str_replace(' ', '-', mb_strtolower($funcionario->nome)) . '-selecionados.zip';
        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
}
