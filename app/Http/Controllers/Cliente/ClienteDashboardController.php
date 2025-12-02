<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1 cliente : N usuários  -> pegamos o cliente pela empresa_id
        $cliente = Cliente::where('empresa_id', $user->empresa_id)->firstOrFail();

        return view('clientes.dashboard', [
            'user'    => $user,
            'cliente' => $cliente,
        ]);
    }
}
