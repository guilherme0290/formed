<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Proposta;
use App\Models\PropostaItens;
use App\Models\Servico;
use Illuminate\Http\Request;
use App\Models\ClienteTabelaPreco;
use App\Models\ClienteTabelaPrecoItem;
use Illuminate\Support\Facades\DB;

class PropostaController extends Controller
{
    public function create()
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id ?? null;

        $clientes = Cliente::where('empresa_id', $empresaId)
            ->orderBy('razao_social', 'asc')
            ->get();

        $servicos = Servico::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        $formasPagamento = [
            'À vista',
            'Faturado todo dia 10',
            'Faturado todo dia 15',
            'Faturado todo dia 20',
            'Cartão de crédito à vista',
            'Cartão de crédito 3x sem juros',
        ];

        return view('comercial.propostas.create', [
            'user' => $user,
            'clientes' => $clientes,
            'servicos' => $servicos,
            'formasPagamento' => $formasPagamento,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'forma_pagamento' => ['required', 'string', 'max:255'],
            'incluir_esocial' => ['nullable', 'boolean'],
            'esocial_qtd_funcionarios' => ['nullable', 'integer', 'min:0'],

            'itens' => ['required', 'array', 'min:1'],
            'itens.*.tipo' => ['required', 'string', 'max:50'],
            'itens.*.nome' => ['required', 'string', 'max:255'],
            'itens.*.descricao' => ['nullable', 'string'],
            'itens.*.valor_unitario' => ['required', 'numeric', 'min:0'],
            'itens.*.acrescimo' => ['nullable', 'numeric', 'min:0'],
            'itens.*.desconto' => ['nullable', 'numeric', 'min:0'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            'itens.*.prazo' => ['nullable', 'string', 'max:255'],
            'itens.*.meta' => ['nullable', 'array'],
        ]);

        return DB::transaction(function () use ($data, $user, $empresaId) {

            // 1) total itens
            $totalItens = 0;

            foreach ($data['itens'] as $item) {
                $unit = (float) $item['valor_unitario'];
                $acr  = (float) ($item['acrescimo'] ?? 0);
                $desc = (float) ($item['desconto']  ?? 0);
                $qtd  = (int) $item['quantidade'];

                $valorTotalItem = max(0, ($unit + $acr - $desc) * $qtd);
                $totalItens += $valorTotalItem;
            }

            // 2) eSocial (mantive sua regra exemplo)
            $esocialMensal = 0;
            if (!empty($data['incluir_esocial']) && $data['esocial_qtd_funcionarios']) {
                $qtd = $data['esocial_qtd_funcionarios'];

                if ($qtd <= 10) $esocialMensal = 100;
                elseif ($qtd <= 20) $esocialMensal = 200;
                elseif ($qtd <= 30) $esocialMensal = 300;
                elseif ($qtd <= 50) $esocialMensal = 400;
                else $esocialMensal = 3 * $qtd;
            }

            // 3) cria proposta
            $proposta = Proposta::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $data['cliente_id'],
                'vendedor_id' => $user->id,
                'forma_pagamento' => $data['forma_pagamento'],
                'incluir_esocial' => $data['incluir_esocial'] ?? false,
                'esocial_qtd_funcionarios' => $data['esocial_qtd_funcionarios'] ?? null,
                'esocial_valor_mensal' => $esocialMensal,
                'valor_total' => $totalItens + $esocialMensal,
                'status' => 'rascunho',
                'observacoes' => $data['observacoes'] ?? null,
            ]);

            // 4) cria itens (com acrescimo/desconto)
            foreach ($data['itens'] as $item) {
                $unit = (float) $item['valor_unitario'];
                $acr  = (float) ($item['acrescimo'] ?? 0);
                $desc = (float) ($item['desconto']  ?? 0);
                $qtd  = (int) $item['quantidade'];

                $valorTotalItem = max(0, ($unit + $acr - $desc) * $qtd);

                PropostaItens::create([
                    'proposta_id' => $proposta->id,
                    'tipo' => $item['tipo'],
                    'nome' => $item['nome'],
                    'descricao' => $item['descricao'] ?? null,
                    'valor_unitario' => $unit,
                    'acrescimo' => $acr,
                    'desconto' => $desc,
                    'quantidade' => $qtd,
                    'prazo' => $item['prazo'] ?? null,
                    'valor_total' => $valorTotalItem,
                    'meta' => $item['meta'] ?? null,
                ]);
            }

            return redirect()
                ->route('comercial.propostas.show', $proposta)
                ->with('ok', 'Proposta criada com sucesso.');
        });
    }

    public function show(Proposta $proposta)
    {
        $proposta->load(['cliente', 'empresa', 'vendedor', 'itens', 'unidades']);

        return view('comercial.propostas.show', [
            'proposta' => $proposta,
        ]);
    }


    public function fechar(Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        if ($proposta->status === 'fechada') {
            return back()->with('ok','Proposta já está fechada.');
        }

        return DB::transaction(function () use ($proposta, $user) {

            // 1) muda status
            $proposta->update(['status' => 'fechada']);

            // 2) encerra tabela vigente do cliente (se existir)
            ClienteTabelaPreco::where('empresa_id', $proposta->empresa_id)
                ->where('cliente_id', $proposta->cliente_id)
                ->where('ativa', true)
                ->update([
                    'ativa' => false,
                    'vigencia_fim' => now(),
                ]);

            // 3) cria nova tabela vigente
            $tabela = ClienteTabelaPreco::create([
                'empresa_id' => $proposta->empresa_id,
                'cliente_id' => $proposta->cliente_id,
                'origem_proposta_id' => $proposta->id,
                'vigencia_inicio' => now(),
                'vigencia_fim' => null,
                'ativa' => true,
                'observacoes' => 'Gerada automaticamente ao fechar proposta '.$proposta->codigo,
            ]);

            // 4) copia itens da proposta (snapshot -> tabela cliente)
            $proposta->load('itens');

            foreach ($proposta->itens as $pi) {
                ClienteTabelaPrecoItem::create([
                    'cliente_tabela_preco_id' => $tabela->id,
                    'servico_id' => $pi->servico_id,
                    'tipo' => $pi->tipo,
                    'codigo' => $pi->meta['codigo'] ?? null, // opcional
                    'nome' => $pi->nome,
                    'descricao' => $pi->descricao,
                    'valor_unitario' => $pi->valor_unitario,
                    'meta' => $pi->meta,
                    'ativo' => true,
                ]);
            }

            return redirect()->route('comercial.propostas.show', $proposta)
                ->with('ok','Proposta fechada e tabela de preço do cliente criada com vigência a partir de hoje.');
        });
    }
}
