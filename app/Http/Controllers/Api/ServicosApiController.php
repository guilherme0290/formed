<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Servico;
use Illuminate\Http\Request;

class ServicosApiController extends Controller
{
    public function index(Request $r)
    {
        $q = Servico::query()
            ->when($r->filled('search'), fn($qq)=>$qq->where('nome','like','%'.$r->search.'%'))
            ->where('ativo', true)
            ->orderBy('nome')
            ->limit(20)
            ->get(['id','nome','tipo','esocial']);

        return $q->map(fn($s)=>[
            'id'   => $s->id,
            'text' => $s->nome . ($s->tipo ? " • {$s->tipo}" : '') . ($s->esocial ? " • {$s->esocial}" : ''),
        ]);
    }
}
