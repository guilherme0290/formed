<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClientesApiController extends Controller
{
    public function index(Request $r)
    {
        $q = trim($r->get('q',''));

        $base = Cliente::query()->orderBy('razao_social');

        $base->when($r->user()->empresa_id, fn($qq) =>
        $qq->where('empresa_id', $r->user()->empresa_id)
        );

        $base->when($q, fn($qq) =>
        $qq->where(function($w) use ($q){
            $w->where('razao_social','like',"%{$q}%")
                ->orWhere('nome_fantasia','like',"%{$q}%")
                ->orWhere('cnpj','like',"%{$q}%");
        })
        );

        return $base->limit(20)->get([
            'id','razao_social','nome_fantasia','cnpj'
        ]);
    }
}
