<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\TreinamentoNrsTabPreco;
use Illuminate\Http\Request;

class TreinamentoNrController extends Controller
{
    public function indexJson(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $query = TreinamentoNrsTabPreco::query()
            ->where('empresa_id', $empresaId);

        // opcional: filtrar só ativos
        if ($request->boolean('somente_ativos', true)) {
            $query->where('ativo', true);
        }

        // opcional: busca
        if ($term = trim((string)$request->get('q'))) {
            $query->where(function ($q2) use ($term) {
                $q2->where('codigo', 'like', "%{$term}%")
                    ->orWhere('titulo', 'like', "%{$term}%");
            });
        }

        $nrs = $query
            ->orderByRaw("CAST(REPLACE(REPLACE(codigo,'NR',''),'-','') AS UNSIGNED) asc")
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'titulo', 'ativo']);

        return response()->json([
            'data' => $nrs,
        ]);
    }
}
