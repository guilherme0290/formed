<?php

namespace App\Http\Controllers\Financeiro;

use App\Helpers\S3Helper;
use App\Http\Controllers\Controller;
use App\Models\ContaPagar;
use App\Models\ContaPagarItem;
use App\Models\Fornecedor;
use App\Services\ContaPagarService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContasPagarController extends Controller
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
        $fornecedorId = $request->input('fornecedor_id');
        $statusConta = $request->input('status_conta');

        $fornecedores = Fornecedor::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('razao_social')
            ->get();

        $contas = ContaPagar::query()
            ->with('fornecedor')
            ->where('empresa_id', $empresaId)
            ->when($fornecedorId, fn ($q) => $q->where('fornecedor_id', $fornecedorId))
            ->when($statusConta, fn ($q) => $q->where('status', $statusConta))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('financeiro.contas-pagar.index', [
            'fornecedores' => $fornecedores,
            'contas' => $contas,
            'filtros' => [
                'fornecedor_id' => $fornecedorId,
                'status_conta' => $statusConta,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $empresaId = $request->user()->empresa_id;

        $fornecedores = Fornecedor::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('razao_social')
            ->get();

        return view('financeiro.contas-pagar.create', [
            'fornecedores' => $fornecedores,
        ]);
    }

    public function store(Request $request, ContaPagarService $service): RedirectResponse
    {
        $data = $request->validate([
            'fornecedor_id' => ['required', 'integer', 'exists:fornecedores,id'],
            'vencimento' => ['required', 'date'],
            'pago_em' => ['nullable', 'date'],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.categoria' => ['nullable', 'string', 'max:80'],
            'itens.*.descricao' => ['required', 'string', 'max:255'],
            'itens.*.data_competencia' => ['nullable', 'date'],
            'itens.*.vencimento' => ['nullable', 'date'],
            'itens.*.valor' => ['required', 'numeric', 'min:0.01'],
        ]);

        $empresaId = $request->user()->empresa_id;
        $fornecedor = Fornecedor::query()
            ->where('id', $data['fornecedor_id'])
            ->where('empresa_id', $empresaId)
            ->first();

        if (!$fornecedor) {
            return back()->with('error', 'Fornecedor inválido para a empresa selecionada.')->withInput();
        }

        $conta = DB::transaction(function () use ($data, $empresaId, $service) {
            $conta = ContaPagar::create([
                'empresa_id' => $empresaId,
                'fornecedor_id' => $data['fornecedor_id'],
                'status' => 'FECHADA',
                'total' => 0,
                'total_baixado' => 0,
                'vencimento' => $data['vencimento'],
                'pago_em' => $data['pago_em'] ?? null,
                'observacao' => $data['observacao'] ?? null,
            ]);

            foreach ($data['itens'] as $item) {
                ContaPagarItem::create([
                    'conta_pagar_id' => $conta->id,
                    'empresa_id' => $empresaId,
                    'fornecedor_id' => $data['fornecedor_id'],
                    'categoria' => $item['categoria'] ?? null,
                    'descricao' => $item['descricao'],
                    'data_competencia' => $item['data_competencia'] ?? null,
                    'vencimento' => $item['vencimento'] ?? $data['vencimento'],
                    'status' => 'ABERTO',
                    'valor' => $item['valor'],
                ]);
            }

            $service->recalcularConta($conta);

            return $conta;
        });

        return redirect()
            ->route('financeiro.contas-pagar.show', $conta)
            ->with('success', 'Conta a pagar gerada com sucesso.');
    }

    public function show(Request $request, ContaPagar $contaPagar): View
    {
        $this->authorizeConta($request, $contaPagar);

        $contaPagar->load(['fornecedor', 'itens.fornecedor', 'baixas']);

        return view('financeiro.contas-pagar.show', [
            'conta' => $contaPagar,
            'formasPagamento' => $this->formasPagamento(),
        ]);
    }

    public function baixar(Request $request, ContaPagar $contaPagar, ContaPagarService $service): RedirectResponse
    {
        $this->authorizeConta($request, $contaPagar);

        $formasPagamento = $this->formasPagamento();
        $data = $request->validate([
            'valor' => ['required', 'numeric', 'min:0.01'],
            'pago_em' => ['nullable', 'date'],
            'meio_pagamento' => ['required', 'string', 'in:' . implode(',', $formasPagamento)],
            'observacao' => ['nullable', 'string', 'max:1000'],
            'comprovante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $comprovante = $request->file('comprovante');
        $comprovantePath = S3Helper::upload($comprovante, 'contas-pagar/' . $contaPagar->empresa_id);

        $valorAplicado = $service->aplicarBaixa(
            $contaPagar,
            (float) $data['valor'],
            $data['pago_em'] ?? null,
            [
                'meio_pagamento' => $data['meio_pagamento'],
                'observacao' => $data['observacao'] ?? null,
                'comprovante_path' => $comprovantePath,
                'comprovante_nome' => $comprovante->getClientOriginalName(),
                'comprovante_mime' => $comprovante->getClientMimeType(),
                'comprovante_tamanho' => $comprovante->getSize(),
            ]
        );

        if ($valorAplicado <= 0) {
            return back()->with('error', 'Não foi possível aplicar a baixa.');
        }

        return back()->with('success', 'Pagamento registrado com sucesso.');
    }

    public function reabrir(Request $request, ContaPagar $contaPagar): RedirectResponse
    {
        $this->authorizeConta($request, $contaPagar);

        $contaPagar->update(['status' => 'FECHADA']);

        return back()->with('success', 'Conta reaberta.');
    }

    public function storeItem(Request $request, ContaPagar $contaPagar, ContaPagarService $service): RedirectResponse
    {
        $this->authorizeConta($request, $contaPagar);

        $data = $request->validate([
            'categoria' => ['nullable', 'string', 'max:80'],
            'descricao' => ['required', 'string', 'max:255'],
            'data_competencia' => ['nullable', 'date'],
            'vencimento' => ['nullable', 'date'],
            'valor' => ['required', 'numeric', 'min:0.01'],
        ]);

        ContaPagarItem::create([
            'conta_pagar_id' => $contaPagar->id,
            'empresa_id' => $contaPagar->empresa_id,
            'fornecedor_id' => $contaPagar->fornecedor_id,
            'categoria' => $data['categoria'] ?? null,
            'descricao' => $data['descricao'],
            'data_competencia' => $data['data_competencia'] ?? null,
            'vencimento' => $data['vencimento'] ?? $contaPagar->vencimento,
            'status' => 'ABERTO',
            'valor' => $data['valor'],
        ]);

        $service->recalcularConta($contaPagar->fresh());

        return back()->with('success', 'Item adicionado com sucesso.');
    }

    private function formasPagamento(): array
    {
        return [
            'Pix',
            'Boleto',
            'Cartão de crédito',
            'Cartão de débito',
            'Transferência',
            'Dinheiro',
        ];
    }

    private function authorizeConta(Request $request, ContaPagar $contaPagar): void
    {
        if ($contaPagar->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }
    }
}
