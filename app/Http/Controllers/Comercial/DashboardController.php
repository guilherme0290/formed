<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $empresaId = auth()->user()->empresa_id ?? null;

        // TODO: ranking de vendedores (por enquanto só um placeholder)
        $ranking = []; // depois você substitui por query real

        return view('comercial.dashboard', [
            'ranking' => $ranking,
        ]);
    }
}
