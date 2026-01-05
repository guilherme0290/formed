<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\ContaReceberItem;
use App\Models\Servico;
use App\Models\VendaItem;
use App\Services\ContaReceberService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContasReceberController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $user = $request->user();
            if (!$user || (!$user->hasPapel('Master') && !$user->hasPapel('Financeiro'))) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id;

        $clientes = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('razao_social')
            ->get();

        $tipoData = $request->input('tipo_data', 'venda');
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');
        $clienteId = $request->input('cliente_id');

        $vendaItensQuery = VendaItem::query()
            ->with(['venda.cliente', 'servico', 'venda.tarefa'])
            ->whereHas('venda', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId)
                    ->where('status', 'ABERTA');
            })
            ->whereDoesntHave('contasReceberItens', function ($q) {
                $q->where('status', '!=', 'CANCELADO');
            });

        if ($clienteId) {
            $vendaItensQuery->whereHas('venda', function ($q) use ($clienteId) {
                $q->where('cliente_id', $clienteId);
            });
        }

        if ($dataInicio || $dataFim) {
            if ($tipoData === 'finalizacao') {
                $vendaItensQuery->whereHas('venda.tarefa', function ($q) use ($dataInicio, $dataFim) {
                    $q->whereNotNull('finalizado_em');
                    if ($dataInicio) {
                        $q->whereDate('finalizado_em', '>=', $dataInicio);
                    }
                    if ($dataFim) {
                        $q->whereDate('finalizado_em', '<=', $dataFim);
                    }
                });
            } else {
                $vendaItensQuery->whereHas('venda', function ($q) use ($dataInicio, $dataFim) {
                    if ($dataInicio) {
                        $q->whereDate('created_at', '>=', $dataInicio);
                    }
                    if ($dataFim) {
                        $q->whereDate('created_at', '<=', $dataFim);
                    }
                });
            }
        }

        $vendaItens = $vendaItensQuery
            ->orderByDesc('id')
            ->get();

        $contasQuery = ContaReceber::query()
            ->with('cliente')
            ->where('empresa_id', $empresaId)
            ->orderByDesc('id');

        if ($clienteId) {
            $contasQuery->where('cliente_id', $clienteId);
        }

        $statusConta = $request->input('status_conta');
        if ($statusConta) {
            $contasQuery->where('status', $statusConta);
        }

        $contas = $contasQuery->paginate(10)->withQueryString();

        return view('financeiro.contas-receber.index', [
            'clientes' => $clientes,
            'vendaItens' => $vendaItens,
            'contas' => $contas,
            'filtros' => [
                'tipo_data' => $tipoData,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'cliente_id' => $clienteId,
                'status_conta' => $statusConta,
            ],
        ]);
    }

    public function itens(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'itens' => ['required', 'array'],
            'itens.*' => ['integer', 'exists:venda_itens,id'],
        ]);

        $empresaId = $request->user()->empresa_id;
        $itens = VendaItem::query()
            ->with(['venda.cliente', 'servico', 'venda.tarefa'])
            ->whereIn('id', $data['itens'])
            ->whereHas('venda', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->get();

        if ($itens->isEmpty()) {
            return back()->with('error', 'Selecione ao menos um item de venda.');
        }

        $clienteId = $itens->first()->venda?->cliente_id;
        $mesmoCliente = $itens->every(function (VendaItem $item) use ($clienteId) {
            return $item->venda?->cliente_id === $clienteId;
        });

        if (!$mesmoCliente || !$clienteId) {
            return back()->with('error', 'Selecione itens de um único cliente.');
        }

        $statusInvalido = $itens->contains(function (VendaItem $item) {
            return strtoupper((string) $item->venda?->status) !== 'ABERTA';
        });
        if ($statusInvalido) {
            return back()->with('error', 'Algumas vendas selecionadas não estão em aberto.');
        }

        $jaUsados = ContaReceberItem::query()
            ->whereIn('venda_item_id', $data['itens'])
            ->where('status', '!=', 'CANCELADO')
            ->exists();

        if ($jaUsados) {
            return back()->with('error', 'Alguns itens já estão vinculados a contas a receber.');
        }

        $cliente = Cliente::find($clienteId);
        $servicos = Servico::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        return view('financeiro.contas-receber.itens', [
            'cliente' => $cliente,
            'itens' => $itens,
            'servicos' => $servicos,
        ]);
    }

    public function store(Request $request, ContaReceberService $service): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'itens' => ['nullable', 'array'],
            'itens.*' => ['integer', 'exists:venda_itens,id'],
            'vencimento' => ['required', 'date'],
            'pago_em' => ['nullable', 'date'],
            'manual_items' => ['nullable', 'array'],
            'manual_items.*.servico_id' => ['nullable', 'exists:servicos,id'],
            'manual_items.*.descricao' => ['nullable', 'string', 'max:255'],
            'manual_items.*.data_realizacao' => ['nullable', 'date'],
            'manual_items.*.vencimento' => ['nullable', 'date'],
            'manual_items.*.valor' => ['required_with:manual_items', 'numeric', 'min:0.01'],
        ]);

        $empresaId = $request->user()->empresa_id;
        $itensIds = $data['itens'] ?? [];
        $manualItems = $data['manual_items'] ?? [];

        $cliente = Cliente::query()
            ->where('id', $data['cliente_id'])
            ->where('empresa_id', $empresaId)
            ->first();

        if (!$cliente) {
            return back()->with('error', 'Cliente inválido para a empresa selecionada.');
        }

        if (empty($itensIds) && empty($manualItems)) {
            return back()->with('error', 'Inclua ao menos um item para gerar a conta a receber.');
        }

        foreach ($manualItems as $index => $manualItem) {
            $temServico = !empty($manualItem['servico_id']);
            $temDescricao = !empty($manualItem['descricao']);
            if (!$temServico && !$temDescricao) {
                return back()->with('error', 'Informe um serviço ou descrição no item avulso '.($index + 1).'.');
            }
        }

        $vendaItens = VendaItem::query()
            ->with(['venda.tarefa', 'servico'])
            ->whereIn('id', $itensIds)
            ->whereHas('venda', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->get();

        if ($vendaItens->isNotEmpty()) {
            $clienteId = $vendaItens->first()->venda?->cliente_id;
            $mesmoCliente = $vendaItens->every(function (VendaItem $item) use ($clienteId) {
                return $item->venda?->cliente_id === $clienteId;
            });

            if (!$mesmoCliente || $clienteId != $data['cliente_id']) {
                return back()->with('error', 'Os itens selecionados precisam pertencer ao mesmo cliente.');
            }

            $statusInvalido = $vendaItens->contains(function (VendaItem $item) {
                return strtoupper((string) $item->venda?->status) !== 'ABERTA';
            });
            if ($statusInvalido) {
                return back()->with('error', 'Algumas vendas selecionadas não estão em aberto.');
            }

            $jaUsados = ContaReceberItem::query()
                ->whereIn('venda_item_id', $itensIds)
                ->where('status', '!=', 'CANCELADO')
                ->exists();

            if ($jaUsados) {
                return back()->with('error', 'Alguns itens já estão vinculados a contas a receber.');
            }
        }

        $conta = DB::transaction(function () use ($data, $empresaId, $vendaItens, $manualItems, $service) {
            $conta = ContaReceber::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $data['cliente_id'],
                'status' => 'FECHADA',
                'total' => 0,
                'total_baixado' => 0,
                'vencimento' => $data['vencimento'],
                'pago_em' => $data['pago_em'] ?? null,
            ]);

            foreach ($vendaItens as $vendaItem) {
                $venda = $vendaItem->venda;
                $dataRealizacao = $venda?->tarefa?->finalizado_em ?? $venda?->created_at;
                $descricao = $vendaItem->servico?->nome ?? $vendaItem->descricao_snapshot;

                ContaReceberItem::create([
                    'conta_receber_id' => $conta->id,
                    'empresa_id' => $empresaId,
                    'cliente_id' => $data['cliente_id'],
                    'venda_id' => $venda?->id,
                    'venda_item_id' => $vendaItem->id,
                    'servico_id' => $vendaItem->servico_id,
                    'descricao' => $descricao,
                    'data_realizacao' => optional($dataRealizacao)->format('Y-m-d'),
                    'vencimento' => $data['vencimento'],
                    'status' => 'ABERTO',
                    'valor' => $vendaItem->subtotal_snapshot ?? 0,
                ]);

                if ($venda) {
                    $venda->update(['status' => 'FECHADA']);
                }
            }

            foreach ($manualItems as $manualItem) {
                ContaReceberItem::create([
                    'conta_receber_id' => $conta->id,
                    'empresa_id' => $empresaId,
                    'cliente_id' => $data['cliente_id'],
                    'servico_id' => $manualItem['servico_id'] ?? null,
                    'descricao' => $manualItem['descricao'] ?? null,
                    'data_realizacao' => $manualItem['data_realizacao'] ?? null,
                    'vencimento' => $manualItem['vencimento'] ?? $data['vencimento'],
                    'status' => 'ABERTO',
                    'valor' => $manualItem['valor'] ?? 0,
                ]);
            }

            $service->recalcularConta($conta);

            $vendaIds = $vendaItens->pluck('venda_id')->filter()->unique();
            foreach ($vendaIds as $vendaId) {
                $service->atualizarStatusVenda($vendaId);
            }

            return $conta;
        });

        return redirect()
            ->route('financeiro.contas-receber.show', $conta)
            ->with('success', 'Conta a receber gerada com sucesso.');
    }

    public function show(Request $request, ContaReceber $contaReceber): View
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        $contaReceber->load(['cliente', 'itens.venda', 'itens.vendaItem', 'itens.servico', 'baixas']);

        $servicos = Servico::query()
            ->where('empresa_id', $contaReceber->empresa_id)
            ->orderBy('nome')
            ->get();

        return view('financeiro.contas-receber.show', [
            'conta' => $contaReceber,
            'servicos' => $servicos,
        ]);
    }

    public function baixar(Request $request, ContaReceber $contaReceber, ContaReceberService $service): RedirectResponse
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        $data = $request->validate([
            'valor' => ['required', 'numeric', 'min:0.01'],
            'pago_em' => ['nullable', 'date'],
        ]);

        $valorAplicado = $service->aplicarBaixa($contaReceber, (float) $data['valor'], $data['pago_em'] ?? null);
        if ($valorAplicado <= 0) {
            return back()->with('error', 'Não foi possível aplicar a baixa.');
        }

        return back()->with('success', 'Baixa registrada com sucesso.');
    }

    public function reabrir(Request $request, ContaReceber $contaReceber): RedirectResponse
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        $contaReceber->update(['status' => 'FECHADA']);

        return back()->with('success', 'Conta reaberta.');
    }

    public function emitirBoleto(Request $request, ContaReceber $contaReceber): RedirectResponse
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        $contaReceber->update([
            'boleto_status' => 'PENDENTE',
            'boleto_emitido_em' => now(),
        ]);

        return back()->with('success', 'Boleto encaminhado para emissão.');
    }

    public function storeItem(Request $request, ContaReceber $contaReceber, ContaReceberService $service): RedirectResponse
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        $data = $request->validate([
            'servico_id' => ['nullable', 'exists:servicos,id'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'data_realizacao' => ['nullable', 'date'],
            'vencimento' => ['nullable', 'date'],
            'valor' => ['required', 'numeric', 'min:0.01'],
        ]);

        $temServico = !empty($data['servico_id']);
        $temDescricao = !empty($data['descricao']);
        if (!$temServico && !$temDescricao) {
            return back()->with('error', 'Informe um serviço ou descrição para o item avulso.');
        }

        ContaReceberItem::create([
            'conta_receber_id' => $contaReceber->id,
            'empresa_id' => $contaReceber->empresa_id,
            'cliente_id' => $contaReceber->cliente_id,
            'servico_id' => $data['servico_id'] ?? null,
            'descricao' => $data['descricao'] ?? null,
            'data_realizacao' => $data['data_realizacao'] ?? null,
            'vencimento' => $data['vencimento'] ?? $contaReceber->vencimento,
            'status' => 'ABERTO',
            'valor' => $data['valor'] ?? 0,
        ]);

        $service->recalcularConta($contaReceber->fresh());

        return back()->with('success', 'Item avulso adicionado.');
    }
}
