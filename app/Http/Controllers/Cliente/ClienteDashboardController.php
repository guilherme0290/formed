<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteDashboardController extends Controller
{
    /**
     * Tela inicial do Portal do Cliente
     */
    public function index(Request $request)
    {
        // 1) Se NÃO tiver usuário autenticado -> manda pro login
        $user = $request->user();

        if (!$user || !$user->id) { // cobre null, falso, etc.
            return redirect()
                ->route('login', ['redirect' => 'cliente']);
        }

        // 2) Recupera o ID do cliente guardado na sessão
        $clienteId = (int) $request->session()->get('portal_cliente_id');

        // Se for null, vazio, 0 ou não numérico -> força novo login
        if ($clienteId <= 0) {

            // opcional: deslogar de vez
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Nenhum cliente selecionado. Faça login novamente pelo portal do cliente.');
        }

        // 3) Verifica se o cliente realmente existe
        $cliente = Cliente::find($clienteId);

        if (!$cliente) {
            // limpa ID inválido e manda pro login de novo
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Cliente inválido. Acesse novamente pelo portal do cliente.');
        }

        // 4) Tudo certo -> mostra o dashboard
        return view('clientes.dashboard', [
            'user'    => $user,
            'cliente' => $cliente,
        ]);
    }
}
