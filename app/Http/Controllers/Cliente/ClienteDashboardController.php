<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteDashboardController extends Controller
{
    /**
     * Tela inicial do Portal do Cliente
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Pega o cliente que foi escolhido ao clicar no card
        $clienteId = $request->session()->get('portal_cliente_id');

        if (!$clienteId) {
            abort(403, 'NENHUM CLIENTE FOI SELECIONADO PARA O PORTAL.');
        }

        $cliente = Cliente::findOrFail($clienteId);

        return view('cliente.dashboard', [
            'user'    => $user,    // só para mostrar no topo, se quiser
            'cliente' => $cliente, // usa pra nome, cnpj, etc.
        ]);
    }
}
