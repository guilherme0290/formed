<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ClienteAsoGrupo;
use Illuminate\Http\Request;

class ClienteAsoGrupoController extends Controller
{
    public function indexJson(Request $request)
    {
        $empresaId = $request->user()->empresa_id;
        $clienteId = (int) $request->query('cliente_id');

        if ($clienteId <= 0) {
            return response()->json(['data' => []]);
        }

        $rows = ClienteAsoGrupo::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->with(['clienteGhe', 'grupo'])
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'id' => $row->id,
                'cliente_ghe_id' => $row->cliente_ghe_id,
                'ghe_id' => $row->clienteGhe?->ghe_id,
                'ghe_nome' => $row->clienteGhe?->nome,
                'tipo_aso' => $row->tipo_aso,
                'grupo_id' => $row->grupo_exames_id,
                'grupo_titulo' => $row->grupo?->titulo,
                'total_exames' => (float) $row->total_exames,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }
}
