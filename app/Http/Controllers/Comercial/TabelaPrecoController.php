<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\EsocialTabPreco;
use App\Models\Cliente;
use App\Models\Funcao;
use App\Models\Servico;
use App\Models\TabelaPrecoPadrao;
use App\Models\TabelaPrecoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TabelaPrecoController extends Controller
{
    public function index()
    {
        return view($this->viewPath(), $this->tabelaData());
    }

    public function createItem()
    {
        return view($this->viewPath(), $this->tabelaData());
    }

    public function storeItem(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $padrao = TabelaPrecoPadrao::where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->firstOrFail();

        $data = $request->validate([
            'servico_id' => ['nullable','integer','exists:servicos,id'],
            'codigo'     => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('tabela_preco_items', 'codigo')
                    ->where('tabela_preco_padrao_id', $padrao->id)
                    ->where('servico_id', $request->input('servico_id')),
            ],
//            'nome'       => ['required','string','max:255'],
            'descricao'  => ['nullable','string'],
            'preco'      => ['required','numeric','min:0'],
            'ativo'      => ['nullable','boolean'],
        ], [
            'codigo.unique' => 'Já existe um item com este código para esse serviço nesta tabela.',
        ]);

        // garante que servico_id (se veio) é da empresa
        if (!empty($data['servico_id'])) {
            $ok = Servico::where('id', $data['servico_id'])
                ->where('empresa_id', $empresaId)
                ->exists();
            abort_if(!$ok, 403);
        }

        $data['tabela_preco_padrao_id'] = $padrao->id;
        $data['ativo'] = $data['ativo'] ?? true;

        TabelaPrecoItem::create($data);

        return redirect()
            ->route($this->routeName('itens.index'))
            ->with('ok', 'Item adicionado à tabela padrão.');
    }


    public function updateItem(Request $request, TabelaPrecoItem $item)
    {
        $this->authorizeItem($item);

        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'servico_id' => ['nullable','integer','exists:servicos,id'],
            'codigo'     => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('tabela_preco_items', 'codigo')
                    ->where('tabela_preco_padrao_id', $item->tabela_preco_padrao_id)
                    ->where('servico_id', $request->input('servico_id'))
                    ->ignore($item->id),
            ],
//            'nome'       => ['required','string','max:255'],
            'descricao'  => ['nullable','string'],
            'preco'      => ['required','numeric','min:0'],
            'ativo'      => ['nullable','boolean'],
        ], [
            'codigo.unique' => 'Já existe um item com este código para esse serviço nesta tabela.',
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
            ->route($this->routeName('itens.index'))
            ->with('ok', 'Item atualizado com sucesso.');
    }

    public function destroyItem(TabelaPrecoItem $item)
    {
        $this->authorizeItem($item);

        $item->delete();

        return redirect()
            ->route($this->routeName('index'))
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
            ->route($this->routeName('index'))
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
        return view($this->viewPath(), $this->tabelaData());
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

    private function tabelaData(): array
    {
        $empresaId = auth()->user()->empresa_id;

        $padrao = TabelaPrecoPadrao::firstOrCreate(
            ['empresa_id' => $empresaId, 'ativa' => true],
            ['nome' => 'Tabela Padrão', 'ativa' => true]
        );

        $esocialId = config('services.esocial_id');
        $treinamentoId = config('services.treinamento_id');

        $servicos = Servico::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->when($esocialId, fn($q) => $q->where('id', '!=', $esocialId))
            ->when($treinamentoId, fn($q) => $q->orderByRaw('CASE WHEN id = ? THEN 1 ELSE 0 END', [(int) $treinamentoId]))
            ->orderBy('nome')
            ->get();

        $itens = $padrao->itens()
            ->with('servico')
            ->orderBy('descricao')
            ->get();

        $clientes = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('razao_social')
            ->get(['id', 'razao_social']);

        $funcoes = Funcao::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $routePrefix = $this->contextPrefix();
        $dashboardRoute = $routePrefix === 'master'
            ? route('master.dashboard')
            : route('comercial.dashboard');

        return compact('padrao', 'servicos', 'itens', 'clientes', 'funcoes', 'routePrefix', 'dashboardRoute');
    }

    private function viewPath(): string
    {
        return $this->contextPrefix() === 'master'
            ? 'master.tabela-precos.index'
            : 'comercial.tabela-precos.itens.index';
    }

    private function routeName(string $suffix): string
    {
        return $this->contextPrefix() . '.tabela-precos.' . $suffix;
    }

    private function contextPrefix(): string
    {
        return request()->routeIs('master.*') ? 'master' : 'comercial';
    }
}
