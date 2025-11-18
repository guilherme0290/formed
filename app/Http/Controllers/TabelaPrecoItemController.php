<?php

namespace App\Http\Controllers;

use App\Models\Servico;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoItemHistorico;
use Illuminate\Http\Request;

class TabelaPrecoItemController extends Controller
{
    public function store(Request $request)
    {
        $modo = $request->input('modo', 'existente');

        // ===== REGRAS DE VALIDAÇÃO =====
        $rules = [
            'codigo'    => ['nullable', 'string', 'max:50'],
            'descricao' => ['nullable', 'string'],
            'preco'     => ['required', 'string'],
            'ativo'     => ['required', 'in:0,1'],
        ];

        if ($modo === 'existente') {
            // serviço já cadastrado
            $rules['servico_id'] = ['required', 'exists:servicos,id'];
        } else {
            // novo serviço
            $rules['novo_servico.nome']       = ['required', 'string', 'max:255'];
            $rules['novo_servico.tipo']       = ['nullable', 'string', 'max:100'];
            $rules['novo_servico.esocial']    = ['nullable', 'string', 'max:50'];
            $rules['novo_servico.valor_base'] = ['nullable', 'string'];
        }

        $data = $request->validate($rules);

        // ===== RESOLVE O servico_id =====
        if ($modo === 'novo') {
            $novo = $request->input('novo_servico', []);

            // cria o serviço "na mão" para garantir empresa_id
            $servico = new Servico();
            $servico->empresa_id = 1; // empresa fixa só para não dar erro no banco
            $servico->nome       = $novo['nome'] ?? '';
            $servico->tipo       = $novo['tipo'] ?? null;
            $servico->esocial    = $novo['esocial'] ?? null;
            // sempre manda string pra brToDecimal
            $servico->valor_base = $this->brToDecimal($novo['valor_base'] ?? '');
            $servico->ativo      = true;
            $servico->save();

            $servicoId = $servico->id;
        } else {
            $servicoId = $data['servico_id'];
        }

        // ===== CRIA O ITEM NA tabela_preco_items =====
        $item = TabelaPrecoItem::create([
            'servico_id' => $servicoId,
            'codigo'     => $data['codigo']    ?? null,
            'descricao'  => $data['descricao'] ?? null,
            'preco'      => $this->brToDecimal($data['preco']),
            'ativo'      => (bool) $data['ativo'],
        ]);

        // ===== HISTÓRICO DO PREÇO (primeiro lançamento) =====
        TabelaPrecoItemHistorico::create([
            'item_id'        => $item->id,
            'preco_anterior' => $item->preco,                 // primeiro preço
            'preco_novo'     => $item->preco,                 // igual ao anterior na criação
            'user_id'        => optional($request->user())->id,
        ]);

        return back()->with('ok', 'Preço salvo com sucesso!');
    }

    public function update(Request $request, TabelaPrecoItem $item)
    {
        $modo = $request->input('modo', 'existente');

        $rules = [
            'modo'      => ['required', 'in:existente,novo'],
            'codigo'    => ['nullable', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
            'preco'     => ['required', 'string'],
            'ativo'     => ['nullable'],
        ];

        if ($modo === 'existente') {
            $rules['servico_id'] = ['required', 'exists:servicos,id'];
        }

        if ($modo === 'novo') {
            $rules['novo_servico.nome']       = ['required', 'string', 'max:255'];
            $rules['novo_servico.tipo']       = ['nullable', 'string', 'max:100'];
            $rules['novo_servico.esocial']    = ['nullable', 'string', 'max:50'];
            $rules['novo_servico.valor_base'] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules);

        // resolve o serviço
        if ($modo === 'novo') {
            $novo = $request->input('novo_servico', []);

            $servico = new Servico();
            $servico->empresa_id = 1;
            $servico->nome       = $novo['nome'] ?? '';
            $servico->tipo       = $novo['tipo'] ?? null;
            $servico->esocial    = $novo['esocial'] ?? null;
            // aqui também sempre string
            $servico->valor_base = $this->brToDecimal($novo['valor_base'] ?? '');
            $servico->ativo      = true;
            $servico->save();

            $servicoId = $servico->id;
        } else {
            $servicoId = $request->input('servico_id');
        }

        $precoAntigo = $item->preco;
        $precoNovo   = $this->brToDecimal($request->input('preco'));

        $item->update([
            'servico_id' => $servicoId,
            'codigo'     => $request->input('codigo'),
            'descricao'  => $request->input('descricao'),
            'preco'      => $precoNovo,
            'ativo'      => $request->input('ativo', 1) ? 1 : 0,
        ]);

        // histórico de alteração de preço
        if ($precoAntigo != $precoNovo) {
            TabelaPrecoItemHistorico::create([
                'item_id'        => $item->id,
                'preco_anterior' => $precoAntigo,
                'preco_novo'     => $precoNovo,
                'user_id'        => optional($request->user())->id,
            ]);
        }

        return redirect()
            ->route('tabela-precos.index')
            ->with('ok', 'Preço atualizado com sucesso.');
    }

    public function toggle(TabelaPrecoItem $item)
    {
        $item->update(['ativo' => !$item->ativo]);

        return back()->with('ok', 'Status atualizado.');
    }

    public function destroy(TabelaPrecoItem $item)
    {
        $item->delete();

        return back()->with('ok', 'Item excluído.');
    }

    private function brToDecimal(?string $v): float
    {
        // se vier vazio, sempre devolve 0.00
        if (!$v) {
            return 0.0;
        }

        // tira R$, espaços e pontos
        $v = str_replace(['R$', ' ', '.'], '', $v);

        // troca vírgula por ponto
        $v = str_replace(',', '.', $v);

        return (float) $v;
    }
}
