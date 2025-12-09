<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Proposta;
use App\Models\PropostaItens;
use App\Models\Servico;
use Illuminate\Http\Request;

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
            'itens.*.servico_id' => ['nullable', 'exists:servicos,id'],
            'itens.*.tipo' => ['required', 'string'],
            'itens.*.nome' => ['required', 'string', 'max:255'],
            'itens.*.valor_unitario' => ['required', 'numeric', 'min:0'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            'itens.*.prazo' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($data, $user, $empresaId) {

            // cálculo simples; depois você troca por service se quiser
            $totalItens = 0;
            foreach ($data['itens'] as $item) {
                $totalItens += $item['valor_unitario'] * $item['quantidade'];
            }

            $esocialMensal = 0;
            if (!empty($data['incluir_esocial']) && $data['esocial_qtd_funcionarios']) {
                $qtd = $data['esocial_qtd_funcionarios'];

                // regra de faixa – só exemplo
                if ($qtd <= 10) $esocialMensal = 100;
                elseif ($qtd <= 20) $esocialMensal = 200;
                elseif ($qtd <= 30) $esocialMensal = 300;
                elseif ($qtd <= 50) $esocialMensal = 400;
                else                  $esocialMensal = 3 * $qtd;
            }

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
            ]);

            foreach ($data['itens'] as $item) {
                $valorTotalItem = $item['valor_unitario'] * $item['quantidade'];

                PropostaItens::create([
                    'proposta_id' => $proposta->id,
                    'servico_id' => $item['servico_id'] ?? null,
                    'tipo' => $item['tipo'],
                    'nome' => $item['nome'],
                    'descricao' => $item['descricao'] ?? null,
                    'valor_unitario' => $item['valor_unitario'],
                    'quantidade' => $item['quantidade'],
                    'prazo' => $item['prazo'] ?? null,
                    'valor_total' => $valorTotalItem,
                    'meta' => $item['meta'] ?? null,
                ]);
            }

            return redirect()
                ->route('comercial.blade.php.propostas.show', $proposta)
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
}
