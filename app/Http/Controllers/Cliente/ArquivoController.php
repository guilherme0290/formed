<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
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

        $arquivos = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('path_documento_cliente')
            ->orderByDesc('finalizado_em')
            ->orderByDesc('updated_at')
            ->with(['servico', 'coluna'])
            ->get();

        return view('clientes.arquivos.index', [
            'cliente' => $cliente,
            'user' => $user,
            'arquivos' => $arquivos,
        ]);
    }
}
