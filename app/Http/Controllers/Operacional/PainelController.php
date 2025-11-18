<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tarefa;
use App\Models\User;
use App\Models\Servico;

class PainelController extends Controller
{
    public function index(Request $r)
    {
        // contadores/listas (placeholders por enquanto)
        $totais = [
            'pendente' => 2,
            'execucao' => 0,
            'aguardando_cliente' => 0,
            'concluido' => 1,
            'atrasado' => 0,
        ];

        $pendentes  = collect([]);
        $execucao   = collect([]);
        $aguardando = collect([]);
        $concluidos = collect([]);
        $atrasados  = collect([]);

        // >>> carregar serviços para o parcial
        $servicos = Servico::orderBy('nome')->get(['id','nome']);

        return view('operacional.painel.index', [
            'totais'     => $totais,
            'pendentes'  => $pendentes,
            'execucao'   => $execucao,
            'aguardando' => $aguardando,
            'concluidos' => $concluidos,
            'atrasados'  => $atrasados,
            'servicos'   => $servicos,  // <<< necessário para _campos_tarefa.blade.php
        ]);
    }
}
