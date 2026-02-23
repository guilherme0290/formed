<?php

namespace App\Http\Controllers\Financeiro;

use App\Helpers\S3Helper;
use App\Http\Controllers\Controller;
use App\Models\ContaPagar;
use App\Models\ContaPagarBaixa;
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
        $subAba = $request->input('subaba', 'contas');
        if (!in_array($subAba, ['contas', 'fornecedores'], true)) {
            $subAba = 'contas';
        }

        $contaIdFiltro = trim((string) $request->input('conta_id', ''));
        $fornecedorId = $request->input('fornecedor_id');
        $statusConta = strtolower(trim((string) $request->input('status_conta', '')));
        $descricao = trim((string) $request->input('descricao', ''));
        $tipoPeriodo = strtolower(trim((string) $request->input('tipo_periodo', 'vencimento')));
        if (!in_array($tipoPeriodo, ['vencimento', 'pagamento', 'criacao'], true)) {
            $tipoPeriodo = 'vencimento';
        }
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');
        $detalheId = $request->input('detalhe_id');
        $fornecedorBusca = trim((string) $request->input('fornecedor_busca', ''));
        $fornecedorModal = (string) $request->input('fornecedor_modal', '');
        $fornecedorModalId = $request->input('fornecedor');

        $fornecedores = Fornecedor::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('razao_social')
            ->get();

        $applyContaFilters = function ($query) use (
            $contaIdFiltro,
            $fornecedorId,
            $statusConta,
            $descricao,
            $tipoPeriodo,
            $dataInicio,
            $dataFim
        ) {
            $query
                ->when($contaIdFiltro !== '', function ($q) use ($contaIdFiltro) {
                    $digits = preg_replace('/\D+/', '', $contaIdFiltro);
                    if ($digits === '') {
                        $q->whereRaw('1 = 0');
                        return;
                    }
                    $q->where('id', (int) $digits);
                })
                ->when($fornecedorId, fn ($q) => $q->where('fornecedor_id', $fornecedorId))
                ->when($descricao !== '', function ($q) use ($descricao) {
                    $q->where(function ($q2) use ($descricao) {
                        $q2->where('observacao', 'like', '%' . $descricao . '%')
                            ->orWhereHas('itens', function ($q3) use ($descricao) {
                                $q3->where('descricao', 'like', '%' . $descricao . '%');
                            });
                    });
                })
                ->when($statusConta !== '', function ($q) use ($statusConta) {
                    if ($statusConta === 'paga') {
                        $q->whereRaw('COALESCE(total, 0) > 0')
                            ->whereRaw('COALESCE(total_baixado, 0) >= COALESCE(total, 0)');
                        return;
                    }

                    if ($statusConta === 'parcial') {
                        $q->whereRaw('COALESCE(total_baixado, 0) > 0')
                            ->whereRaw('COALESCE(total_baixado, 0) < COALESCE(total, 0)');
                        return;
                    }

                    if ($statusConta === 'aberta') {
                        $q->whereRaw('COALESCE(total_baixado, 0) <= 0');
                        return;
                    }

                    $q->where('status', strtoupper($statusConta));
                });

            if ($dataInicio || $dataFim) {
                if ($tipoPeriodo === 'criacao') {
                    if ($dataInicio) {
                        $query->whereDate('created_at', '>=', $dataInicio);
                    }
                    if ($dataFim) {
                        $query->whereDate('created_at', '<=', $dataFim);
                    }
                } elseif ($tipoPeriodo === 'vencimento') {
                    if ($dataInicio) {
                        $query->whereDate('vencimento', '>=', $dataInicio);
                    }
                    if ($dataFim) {
                        $query->whereDate('vencimento', '<=', $dataFim);
                    }
                } else {
                    $query->whereHas('baixas', function ($q) use ($dataInicio, $dataFim) {
                        if ($dataInicio) {
                            $q->whereDate(DB::raw('COALESCE(pago_em, created_at)'), '>=', $dataInicio);
                        }
                        if ($dataFim) {
                            $q->whereDate(DB::raw('COALESCE(pago_em, created_at)'), '<=', $dataFim);
                        }
                    });
                }
            }
        };

        $contasBase = ContaPagar::query()
            ->where('empresa_id', $empresaId);
        $applyContaFilters($contasBase);

        $contasTotaisBase = clone $contasBase;

        $totaisContas = [
            'valor_total' => (float) (clone $contasTotaisBase)->sum('total'),
            'valor_pago' => (float) (clone $contasTotaisBase)->sum('total_baixado'),
            'valor_aberto' => (float) (clone $contasTotaisBase)->sum(DB::raw('CASE WHEN COALESCE(total,0) - COALESCE(total_baixado,0) > 0 THEN COALESCE(total,0) - COALESCE(total_baixado,0) ELSE 0 END')),
            'qtd_contas' => (int) (clone $contasTotaisBase)->count(),
            'qtd_pagas' => (int) (clone $contasTotaisBase)
                ->whereRaw('COALESCE(total, 0) > 0')
                ->whereRaw('COALESCE(total_baixado, 0) >= COALESCE(total, 0)')
                ->count(),
        ];

        $baixasNoPeriodoQuery = ContaPagarBaixa::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('conta_pagar_id', (clone $contasBase)->select('contas_pagar.id'));
        if ($dataInicio) {
            $baixasNoPeriodoQuery->whereDate(DB::raw('COALESCE(pago_em, created_at)'), '>=', $dataInicio);
        }
        if ($dataFim) {
            $baixasNoPeriodoQuery->whereDate(DB::raw('COALESCE(pago_em, created_at)'), '<=', $dataFim);
        }
        $totaisContas['valor_pago_periodo'] = (float) $baixasNoPeriodoQuery->sum('valor');

        $contas = (clone $contasBase)
            ->with([
                'fornecedor',
                'itens' => fn ($q) => $q->select('id', 'conta_pagar_id', 'descricao', 'valor')->orderBy('id'),
            ])
            ->withCount('itens')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $contaDetalhe = null;
        if ($detalheId && is_numeric($detalheId)) {
            $contaDetalhe = ContaPagar::query()
                ->where('empresa_id', $empresaId)
                ->whereKey((int) $detalheId)
                ->with(['fornecedor', 'itens.baixas', 'baixas'])
                ->first();
        }

        $fornecedoresLista = Fornecedor::query()
            ->where('empresa_id', $empresaId)
            ->when($fornecedorBusca !== '', function ($query) use ($fornecedorBusca) {
                $query->where(function ($q) use ($fornecedorBusca) {
                    $q->where('razao_social', 'like', '%' . $fornecedorBusca . '%')
                        ->orWhere('nome_fantasia', 'like', '%' . $fornecedorBusca . '%')
                        ->orWhere('cpf_cnpj', 'like', '%' . $fornecedorBusca . '%');
                });
            })
            ->orderBy('razao_social')
            ->paginate(12, ['*'], 'fornecedores_page')
            ->withQueryString();

        $fornecedorEdicao = null;
        if ($fornecedorModal === 'edit' && is_numeric($fornecedorModalId)) {
            $fornecedorEdicao = Fornecedor::query()
                ->where('empresa_id', $empresaId)
                ->whereKey((int) $fornecedorModalId)
                ->first();
        }

        $modalFornecedorAberto = $subAba === 'fornecedores' && (
            in_array($fornecedorModal, ['create', 'edit'], true)
            || (bool) old('fornecedor_modal_context')
            || $request->session()->has('errors')
        );

        $oldModalContexto = old('fornecedor_modal_context');
        if (in_array($oldModalContexto, ['create', 'edit'], true)) {
            $fornecedorModal = $oldModalContexto;
        }
        if ($fornecedorModal === '' && $modalFornecedorAberto) {
            $fornecedorModal = 'create';
        }
        if (!$fornecedorEdicao && $fornecedorModal === 'edit') {
            $oldFornecedorEdicaoId = old('fornecedor_modal_edit_id');
            if (is_numeric($oldFornecedorEdicaoId)) {
                $fornecedorEdicao = Fornecedor::query()
                    ->where('empresa_id', $empresaId)
                    ->whereKey((int) $oldFornecedorEdicaoId)
                    ->first();
            }
        }
        if ($fornecedorModal === 'edit' && !$fornecedorEdicao) {
            $fornecedorModal = 'create';
        }

        return view('financeiro.contas-pagar.index', [
            'fornecedores' => $fornecedores,
            'fornecedoresLista' => $fornecedoresLista,
            'contas' => $contas,
            'subAba' => $subAba,
            'contaDetalhe' => $contaDetalhe,
            'totaisContas' => $totaisContas,
            'fornecedorArea' => [
                'busca' => $fornecedorBusca,
                'modalAberto' => $modalFornecedorAberto,
                'modalModo' => $fornecedorModal,
                'fornecedorEdicao' => $fornecedorEdicao,
            ],
            'filtros' => [
                'conta_id' => $contaIdFiltro,
                'fornecedor_id' => $fornecedorId,
                'status_conta' => $statusConta,
                'descricao' => $descricao,
                'tipo_periodo' => $tipoPeriodo,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'detalhe_id' => $detalheId,
                'fornecedor_busca' => $fornecedorBusca,
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
