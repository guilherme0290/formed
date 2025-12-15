<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\TabelaPrecoPadrao;
use App\Models\TabelaPrecoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TabelaPrecoController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id;

        $padrao = TabelaPrecoPadrao::firstOrCreate(
            ['empresa_id' => $empresaId, 'ativa' => true],
            ['nome' => 'Tabela Padrão', 'ativa' => true]
        );

        $itens = TabelaPrecoItem::with('servico')
            ->where('tabela_preco_padrao_id', $padrao->id)
            ->orderBy('descricao')
            ->get();

        return view('comercial.tabela-precos.index', compact('padrao','itens'));
    }

    public function update(Request $r)
    {
        $user = $r->user();
        $empresaId = $user->empresa_id;

        $data = $r->validate([
            'itens' => ['required','array'],
            'itens.*.id' => ['required','integer','exists:tabela_preco_items,id'],
            'itens.*.preco' => ['required','numeric','min:0'],
            'itens.*.ativo' => ['nullable','boolean'],
        ]);

        $padrao = TabelaPrecoPadrao::where('empresa_id',$empresaId)->where('ativa',true)->firstOrFail();

        DB::transaction(function () use ($data, $padrao) {
            foreach ($data['itens'] as $i) {
                $item = TabelaPrecoItem::where('id',$i['id'])
                    ->where('tabela_preco_padrao_id',$padrao->id)
                    ->firstOrFail();

                $item->update([
                    'preco' => $i['preco'],
                    'ativo' => !empty($i['ativo']),
                ]);
            }
        });

        return redirect()->route('tabela-precos.index')->with('ok','Tabela padrão atualizada.');
    }
}
