<?php

namespace App\Http\Controllers;

use App\Models\TabelaPrecoItem;
use App\Models\Servico;
use Illuminate\Http\Request;

class TabelaPrecoController extends Controller
{
    public function index(Request $r)
    {
        $busca  = $r->query('q', '');
        $status = $r->query('status', '');   // '' | 'ativos' | 'inativos'
        $tipo   = $r->query('tipo', '');

        $query = TabelaPrecoItem::with('servico')
            ->when($busca !== '', function($q) use ($busca){
                $q->where(function($qq) use ($busca){
                    $qq->whereHas('servico', fn($s)=>$s->where('nome','like',"%{$busca}%"))
                        ->orWhere('codigo','like',"%{$busca}%");
                });
            })
            ->when($status !== '', function($q) use ($status){
                if($status === 'ativos')   $q->where('ativo', true);
                if($status === 'inativos') $q->where('ativo', false);
            })
            ->when($tipo !== '', fn($q)=>$q->whereHas('servico', fn($s)=>$s->where('tipo',$tipo)))
            ->orderByRaw('ativo desc')
            ->orderBy(
                Servico::select('nome')->whereColumn('servicos.id','tabela_preco_items.servico_id')
            );

        $itens = $query->paginate(15)->withQueryString();
        $tipos = Servico::select('tipo')->whereNotNull('tipo')->distinct()->orderBy('tipo')->pluck('tipo');

        // >>> ENVIAR AS VARIÁVEIS QUE A VIEW USA
        return view('tabela-precos.index', compact('itens','tipos','busca','status','tipo'));
    }


}
