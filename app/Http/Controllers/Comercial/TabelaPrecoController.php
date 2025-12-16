<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\EsocialTabPreco;
use App\Models\Servico;
use App\Models\TabelaPrecoPadrao;
use App\Models\TabelaPrecoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TabelaPrecoController extends Controller
{
    public function index()
    {
        $empresaId = auth()->user()->empresa_id;

        $padrao = TabelaPrecoPadrao::firstOrCreate(
            ['empresa_id' => $empresaId, 'ativa' => true],
            ['nome' => 'Tabela Padrão', 'ativa' => true]
        );

        $itens = $padrao->itens()
            ->with('servico') // opcional
            ->orderBy('descricao')
            ->get();

        return view('comercial.tabela-precos.index', compact('padrao','itens'));
    }

    public function createItem()
    {
        $empresaId = auth()->user()->empresa_id;

        $servicos = Servico::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view('comercial.tabela-precos.itens.index', compact('servicos'));
    }

    public function storeItem(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'servico_id' => ['nullable','integer','exists:servicos,id'],
            'codigo'     => ['nullable','string','max:50'],
//            'nome'       => ['required','string','max:255'],
            'descricao'  => ['nullable','string'],
            'preco'      => ['required','numeric','min:0'],
            'ativo'      => ['nullable','boolean'],
        ]);

        // garante que servico_id (se veio) é da empresa
        if (!empty($data['servico_id'])) {
            $ok = Servico::where('id', $data['servico_id'])
                ->where('empresa_id', $empresaId)
                ->exists();
            abort_if(!$ok, 403);
        }

        $padrao = TabelaPrecoPadrao::where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->firstOrFail();

        $data['tabela_preco_padrao_id'] = $padrao->id;
        $data['ativo'] = $data['ativo'] ?? true;

        TabelaPrecoItem::create($data);

        return redirect()
            ->route('comercial.tabela-precos.itens.index')
            ->with('ok', 'Item adicionado à tabela padrão.');
    }


    public function updateItem(Request $request, TabelaPrecoItem $item)
    {
        $this->authorizeItem($item);

        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'servico_id' => ['nullable','integer','exists:servicos,id'],
            'codigo'     => ['nullable','string','max:50'],
//            'nome'       => ['required','string','max:255'],
            'descricao'  => ['nullable','string'],
            'preco'      => ['required','numeric','min:0'],
            'ativo'      => ['nullable','boolean'],
        ]);

        if (!empty($data['servico_id'])) {
            $ok = Servico::where('id', $data['servico_id'])
                ->where('empresa_id', $empresaId)
                ->exists();
            abort_if(!$ok, 403);
        }

        $item->update([
            'servico_id' => $data['servico_id'] ?? null,
            'codigo'     => $data['codigo'] ?? null,
//            'nome'       => $data['nome'],
            'descricao'  => $data['descricao'] ?? null,
            'preco'      => $data['preco'],
            'ativo'      => $data['ativo'] ?? false,
        ]);

        return redirect()
            ->route('comercial.tabela-precos.itens.index')
            ->with('ok', 'Item atualizado com sucesso.');
    }

    public function destroyItem(TabelaPrecoItem $item)
    {
        $this->authorizeItem($item);

        $item->delete();

        return redirect()
            ->route('comercial.tabela-precos.index')
            ->with('ok', 'Item removido da tabela padrão.');
    }

    public function update(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'itens' => ['required','array'],
            'itens.*.id'        => ['required','exists:tabela_preco_items,id'],
            'itens.*.preco'     => ['required','numeric','min:0'],
            'itens.*.ativo'     => ['nullable','boolean'],
            'itens.*.servico_id'=> ['nullable','integer','exists:servicos,id'],
        ]);

        $padrao = TabelaPrecoPadrao::where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->firstOrFail();

        DB::transaction(function () use ($data, $padrao, $empresaId) {
            foreach ($data['itens'] as $row) {

                // valida serviço por empresa se veio
                if (!empty($row['servico_id'])) {
                    $ok = Servico::where('id', $row['servico_id'])
                        ->where('empresa_id', $empresaId)
                        ->exists();
                    abort_if(!$ok, 403);
                }

                TabelaPrecoItem::where('id', $row['id'])
                    ->where('tabela_preco_padrao_id', $padrao->id)
                    ->update([
                        'preco'     => $row['preco'],
                        'ativo'     => !empty($row['ativo']),
                        'servico_id'=> $row['servico_id'] ?? null,
                    ]);
            }
        });

        return redirect()
            ->route('comercial.tabela-precos.index')
            ->with('ok', 'Tabela padrão atualizada.');
    }

    private function authorizeItem(TabelaPrecoItem $item): void
    {
        abort_if(
            $item->tabelaPadrao->empresa_id !== auth()->user()->empresa_id,
            403
        );
    }

    public function itensIndex()
    {
        $empresaId = auth()->user()->empresa_id;

        $padrao = TabelaPrecoPadrao::where('empresa_id', $empresaId)
            ->where('ativa', true)

            ->firstOrFail();

        $esocialId = config('services.esocial_id');

        $servicos = Servico::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->when($esocialId, fn($q) => $q->where('id', '!=', $esocialId))
            ->orderBy('nome')
            ->get();

        $itens = $padrao->itens()->with('servico')->orderBy('descricao')->get();

        return view('comercial.tabela-precos.itens.index', compact('itens','servicos'));
    }

    private function hasOverlap($empresaId, $inicio, $fim, $ignoreId = null)
    {
        return EsocialTabPreco::where('empresa_id', $empresaId)
            ->when($ignoreId, fn($q) => $q->where('id','!=',$ignoreId))
            ->where(function ($q) use ($inicio, $fim) {
                $q->whereBetween('inicio', [$inicio, $fim])
                    ->orWhereBetween('fim', [$inicio, $fim])
                    ->orWhere(function ($q2) use ($inicio, $fim) {
                        $q2->where('inicio','<=',$inicio)
                            ->where('fim','>=',$fim);
                    });
            })
            ->exists();
    }


}
