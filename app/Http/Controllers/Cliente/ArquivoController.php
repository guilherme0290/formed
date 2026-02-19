<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Servico;
use App\Models\Tarefa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArquivoController extends Controller
{
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
            ->where(function ($q) {
                $q->whereNotNull('path_documento_cliente')
                    ->orWhereHas('anexos', function ($aq) {
                        $aq->whereRaw('LOWER(COALESCE(servico, "")) = ?', ['certificado_treinamento']);
                    });
            })
            ->with(['servico', 'coluna', 'anexos']);

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

        $servicosIds = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->where(function ($q) {
                $q->whereNotNull('path_documento_cliente')
                    ->orWhereHas('anexos', function ($aq) {
                        $aq->whereRaw('LOWER(COALESCE(servico, "")) = ?', ['certificado_treinamento']);
                    });
            })
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
        ]);
    }
}
