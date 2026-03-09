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
            $qDigits = preg_replace('/\D+/', '', $q);
            $w->where('razao_social','like',"%{$q}%")
                ->orWhere('nome_fantasia','like',"%{$q}%")
                ->orWhere('cnpj','like',"%{$q}%")
                ->orWhere('cpf','like',"%{$q}%");

            if ($qDigits !== '') {
                $w->orWhereRaw("REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '-', ''), '/', '') LIKE ?", ["%{$qDigits}%"])
                    ->orWhereRaw("REPLACE(REPLACE(cpf, '.', ''), '-', '') LIKE ?", ["%{$qDigits}%"]);
            }
        })
        );

        return $base->limit(20)->get([
            'id', 'razao_social', 'nome_fantasia', 'cnpj', 'cpf', 'tipo_pessoa'
        ])->map(function (Cliente $cliente) {
            return [
                'id' => $cliente->id,
                'razao_social' => $cliente->razao_social,
                'nome_fantasia' => $cliente->nome_fantasia,
                'cnpj' => $cliente->cnpj,
                'cpf' => $cliente->cpf,
                'tipo_pessoa' => $cliente->tipo_pessoa,
                'documento' => $cliente->documento_principal,
                'documento_label' => $cliente->documento_label,
            ];
        })->values();
    }
}
