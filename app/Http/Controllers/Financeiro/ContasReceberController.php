<?php

namespace App\Http\Controllers\Financeiro;

use App\Helpers\S3Helper;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\ContaReceberItem;
use App\Models\ParametroCliente;
use App\Models\Servico;
use App\Models\VendaItem;
use App\Services\ContaReceberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ContasReceberController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $action = $request->route()?->getActionMethod();
            if (in_array($action, ['impressaoPublica'], true)) {
                return $next($request);
            }

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
        $abaAtiva = $request->input('aba', 'vendas');

        $clientes = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('razao_social')
            ->get();

        $tipoData = $request->input('tipo_data', 'venda');
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');
        $clienteId = $request->input('cliente_id');
        $clienteBusca = trim((string) $request->input('cliente', ''));

        if (!$clienteId && $clienteBusca !== '') {
            $clienteId = Cliente::query()
                ->where('empresa_id', $empresaId)
                ->where('razao_social', $clienteBusca)
                ->value('id');
        }

        $vendaItensQuery = VendaItem::query()
            ->with(['venda.cliente', 'servico', 'venda.tarefa.funcionario'])
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

        $faturasClienteId = $request->input('faturas_cliente_id');
        $faturasClienteBusca = trim((string) $request->input('faturas_cliente', ''));
        if (!$faturasClienteId && $faturasClienteBusca !== '') {
            $faturasClienteId = Cliente::query()
                ->where('empresa_id', $empresaId)
                ->where('razao_social', $faturasClienteBusca)
                ->value('id');
        }

        if ($faturasClienteId) {
            $contasQuery->where('cliente_id', $faturasClienteId);
        }

        $faturasNumero = trim((string) $request->input('faturas_numero', ''));
        if ($faturasNumero !== '') {
            preg_match('/(\d+)\s*$/', $faturasNumero, $numeroTailMatch);
            $numeroTailDigits = $numeroTailMatch[1] ?? '';
            $numeroDigits = preg_replace('/\D+/', '', $faturasNumero);
            $numeroBuscaId = $numeroTailDigits !== '' ? (int) ltrim($numeroTailDigits, '0') : null;
            if ($numeroBuscaId === 0 && $numeroTailDigits !== '') {
                $numeroBuscaId = 0;
            }
            if ($numeroDigits !== '') {
                $contasQuery->where(function ($q) use ($numeroDigits, $numeroBuscaId) {
                    if ($numeroBuscaId !== null) {
                        $q->whereKey($numeroBuscaId);
                    }
                    $q->orWhere('id', 'like', '%'.$numeroDigits.'%');
                });
            } else {
                $contasQuery->whereRaw('1 = 0');
            }
        }

        $statusConta = trim((string) $request->input('faturas_status', ''));
        if ($statusConta !== '') {
            $statusConta = strtolower($statusConta);
            if ($statusConta === 'cancelada') {
                $contasQuery->where('status', 'CANCELADO');
            } elseif ($statusConta === 'baixada') {
                $contasQuery
                    ->where('status', '!=', 'CANCELADO')
                    ->whereRaw('COALESCE(total, 0) > 0')
                    ->whereRaw('COALESCE(total_baixado, 0) >= COALESCE(total, 0)');
            } elseif ($statusConta === 'parcial') {
                $contasQuery
                    ->where('status', '!=', 'CANCELADO')
                    ->whereRaw('COALESCE(total_baixado, 0) > 0')
                    ->whereRaw('COALESCE(total_baixado, 0) < COALESCE(total, 0)');
            } elseif ($statusConta === 'aberta') {
                $contasQuery
                    ->where('status', '!=', 'CANCELADO')
                    ->whereRaw('COALESCE(total_baixado, 0) <= 0');
            } else {
                $contasQuery->where('status', strtoupper($statusConta));
            }
        }

        $faturasTipoPeriodo = $request->input('faturas_tipo_periodo', 'vencimento');
        if (!in_array($faturasTipoPeriodo, ['emissao', 'vencimento', 'ambas'], true)) {
            $faturasTipoPeriodo = 'vencimento';
        }
        $faturasDataInicio = $request->input('faturas_data_inicio');
        $faturasDataFim = $request->input('faturas_data_fim');
        if ($faturasDataInicio || $faturasDataFim) {
            if ($faturasTipoPeriodo === 'emissao') {
                if ($faturasDataInicio) {
                    $contasQuery->whereDate('created_at', '>=', $faturasDataInicio);
                }
                if ($faturasDataFim) {
                    $contasQuery->whereDate('created_at', '<=', $faturasDataFim);
                }
            } elseif ($faturasTipoPeriodo === 'vencimento') {
                if ($faturasDataInicio) {
                    $contasQuery->whereDate('vencimento', '>=', $faturasDataInicio);
                }
                if ($faturasDataFim) {
                    $contasQuery->whereDate('vencimento', '<=', $faturasDataFim);
                }
            } else {
                $contasQuery->where(function ($q) use ($faturasDataInicio, $faturasDataFim) {
                    $q->where(function ($q2) use ($faturasDataInicio, $faturasDataFim) {
                        if ($faturasDataInicio) {
                            $q2->whereDate('created_at', '>=', $faturasDataInicio);
                        }
                        if ($faturasDataFim) {
                            $q2->whereDate('created_at', '<=', $faturasDataFim);
                        }
                    })->orWhere(function ($q2) use ($faturasDataInicio, $faturasDataFim) {
                        if ($faturasDataInicio) {
                            $q2->whereDate('vencimento', '>=', $faturasDataInicio);
                        }
                        if ($faturasDataFim) {
                            $q2->whereDate('vencimento', '<=', $faturasDataFim);
                        }
                    });
                });
            }
        }

        $contas = $contasQuery->paginate(10)->withQueryString();

        $clienteAutocomplete = $clientes
            ->pluck('razao_social')
            ->filter()
            ->unique()
            ->values();

        $clienteSelecionadoLabel = '';
        if ($clienteId) {
            $clienteSelecionadoLabel = (string) optional($clientes->firstWhere('id', (int) $clienteId))->razao_social;
        } elseif ($clienteBusca !== '') {
            $clienteSelecionadoLabel = $clienteBusca;
        }

        $faturasClienteSelecionadoLabel = '';
        if ($faturasClienteId) {
            $faturasClienteSelecionadoLabel = (string) optional($clientes->firstWhere('id', (int) $faturasClienteId))->razao_social;
        } elseif ($faturasClienteBusca !== '') {
            $faturasClienteSelecionadoLabel = $faturasClienteBusca;
        }

        $contaDetalhe = null;
        $contaDetalheVencimentoSugerido = null;
        $contaDetalheVencimentoDiaParametro = null;
        $faturaId = $request->input('fatura_id');
        if ($faturaId && is_numeric($faturaId)) {
            $contaDetalhe = ContaReceber::query()
                ->where('empresa_id', $empresaId)
                ->whereKey((int) $faturaId)
                ->with(['cliente', 'empresa', 'itens.venda', 'itens.vendaItem', 'itens.servico', 'baixas'])
                ->first();
            if ($contaDetalhe && $abaAtiva !== 'detalhe') {
                $abaAtiva = 'detalhe';
            }
            if ($contaDetalhe) {
                $vencimentoMeta = $this->resolverVencimentoPropostoCliente($contaDetalhe->cliente_id, $empresaId, $contaDetalhe->created_at);
                $contaDetalheVencimentoSugerido = $vencimentoMeta['data'];
                $contaDetalheVencimentoDiaParametro = $vencimentoMeta['dia'];
            }
        }

        $contaDetalheEmailOpcoes = [];
        if ($contaDetalhe) {
            $parametroCliente = ParametroCliente::query()
                ->where('empresa_id', $empresaId)
                ->where('cliente_id', $contaDetalhe->cliente_id)
                ->latest('id')
                ->first();

            $emailFinanceiro = trim((string) ($contaDetalhe->empresa?->email ?? ''));
            $emailClienteFatura = trim((string) ($parametroCliente?->email_envio_fatura ?? ''));

            if ($emailFinanceiro !== '') {
                $contaDetalheEmailOpcoes[] = [
                    'value' => mb_strtolower($emailFinanceiro),
                    'label' => 'Financeiro (' . mb_strtolower($emailFinanceiro) . ')',
                    'tipo' => 'financeiro',
                ];
            }
            if ($emailClienteFatura !== '') {
                $normalized = mb_strtolower($emailClienteFatura);
                $jaExiste = collect($contaDetalheEmailOpcoes)->contains(fn ($opt) => ($opt['value'] ?? '') === $normalized);
                if (!$jaExiste) {
                    $contaDetalheEmailOpcoes[] = [
                        'value' => $normalized,
                        'label' => 'Cliente (fatura) (' . $normalized . ')',
                        'tipo' => 'cliente_fatura',
                    ];
                }
            }
        }

        return view('financeiro.contas-receber.index', [
            'clientes' => $clientes,
            'vendaItens' => $vendaItens,
            'contas' => $contas,
            'cliente_autocomplete' => $clienteAutocomplete,
            'abaAtiva' => in_array($abaAtiva, ['vendas', 'faturas', 'detalhe'], true) ? $abaAtiva : 'vendas',
            'contaDetalhe' => $contaDetalhe,
            'contaDetalheEmailOpcoes' => $contaDetalheEmailOpcoes,
            'contaDetalheVencimentoSugerido' => $contaDetalheVencimentoSugerido,
            'contaDetalheVencimentoDiaParametro' => $contaDetalheVencimentoDiaParametro,
            'formasPagamento' => $this->formasPagamento(),
            'filtros' => [
                'tipo_data' => $tipoData,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'cliente_id' => $clienteId,
                'cliente' => $clienteSelecionadoLabel,
            ],
            'filtrosFaturas' => [
                'cliente_id' => $faturasClienteId,
                'cliente' => $faturasClienteSelecionadoLabel,
                'status' => $statusConta,
                'numero' => $faturasNumero,
                'tipo_periodo' => $faturasTipoPeriodo,
                'data_inicio' => $faturasDataInicio,
                'data_fim' => $faturasDataFim,
            ],
        ]);
    }

    private function formasPagamento(): array
    {
        return [
            'Pix',
            'Boleto',
            'Cartão de crédito',
            'Cartão de débito',
            'Transferência',
        ];
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
            'vencimento' => ['nullable', 'date'],
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

        $emissaoReferencia = now();
        $vencimentoCalculado = $data['vencimento'] ?? null;
        if (!$vencimentoCalculado) {
            $metaVencimento = $this->resolverVencimentoPropostoCliente((int) $data['cliente_id'], $empresaId, $emissaoReferencia);
            $vencimentoCalculado = optional($metaVencimento['data'])->toDateString() ?? $emissaoReferencia->toDateString();
        }

        $conta = DB::transaction(function () use ($data, $empresaId, $vendaItens, $manualItems, $service) {
            $conta = ContaReceber::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $data['cliente_id'],
                'status' => 'FECHADA',
                'total' => 0,
                'total_baixado' => 0,
                'vencimento' => null,
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

        $conta->update([
            'vencimento' => $vencimentoCalculado,
        ]);

        ContaReceberItem::query()
            ->where('conta_receber_id', $conta->id)
            ->where('status', '!=', 'CANCELADO')
            ->update(['vencimento' => $vencimentoCalculado]);

        return redirect()
            ->route('financeiro.contas-receber', [
                'aba' => 'detalhe',
                'fatura_id' => $conta->id,
            ])
            ->with('success', 'Conta a receber gerada com sucesso.');
    }

    public function updateDatas(Request $request, ContaReceber $contaReceber): RedirectResponse
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        $data = $request->validate([
            'emissao' => ['required', 'date'],
            'vencimento' => ['required', 'date'],
        ]);

        $emissao = Carbon::parse($data['emissao'])->startOfDay();
        $vencimento = Carbon::parse($data['vencimento'])->startOfDay();

        $contaReceber->forceFill([
            'created_at' => $emissao,
            'vencimento' => $vencimento->toDateString(),
        ])->save();

        $contaReceber->itens()
            ->where('status', '!=', 'CANCELADO')
            ->update(['vencimento' => $vencimento->toDateString()]);

        return back()->with('success', 'Datas da fatura atualizadas com sucesso.');
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
            'formasPagamento' => $this->formasPagamento(),
        ]);
    }

    public function impressao(Request $request, ContaReceber $contaReceber)
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        $contaReceber->load(['cliente', 'empresa', 'itens.venda', 'itens.vendaItem', 'itens.servico', 'baixas']);

        $pdf = $this->buildFaturaPdf($contaReceber);

        return $pdf->stream('fatura-' . $contaReceber->id . '.pdf');
    }

    public function impressaoPublica(Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $token = (string) $request->query('token', '');
        if ($token === '') {
            abort(404);
        }

        try {
            $contaReceberId = (int) Crypt::decryptString($token);
        } catch (\Throwable $e) {
            abort(403);
        }

        $contaReceber = ContaReceber::query()->findOrFail($contaReceberId);
        $contaReceber->load(['cliente', 'empresa', 'itens.venda', 'itens.vendaItem', 'itens.servico', 'baixas']);

        $pdf = $this->buildFaturaPdf($contaReceber);

        return $pdf->stream('fatura-' . $contaReceber->id . '.pdf');
    }

    public function whatsapp(Request $request, ContaReceber $contaReceber): RedirectResponse
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        $contaReceber->loadMissing(['cliente', 'empresa']);

        $telefone = preg_replace('/\D+/', '', (string) ($contaReceber->cliente->telefone ?? ''));
        if (in_array(strlen($telefone), [10, 11], true)) {
            $telefone = '55' . $telefone;
        }

        if ($telefone === '') {
            return back()->with('error', 'Telefone 1 do cliente não informado para envio via WhatsApp.');
        }

        $downloadUrl = URL::temporarySignedRoute(
            'financeiro.contas-receber.impressao-publica',
            now()->addDays(7),
            ['token' => Crypt::encryptString((string) $contaReceber->id)]
        );
        $mensagem = rawurlencode(sprintf(
            "Olá! Segue a fatura #%d.\nEmissão: %s\nVencimento: %s\nValor total: R$ %s\nLink para download: %s",
            (int) $contaReceber->id,
            optional($contaReceber->created_at)->format('d/m/Y') ?? '—',
            optional($contaReceber->vencimento)->format('d/m/Y') ?? '—',
            number_format((float) ($contaReceber->total ?? 0), 2, ',', '.'),
            $downloadUrl
        ));

        return redirect()->away('https://wa.me/' . $telefone . '?text=' . $mensagem);
    }

    public function enviarFaturaEmail(Request $request, ContaReceber $contaReceber): RedirectResponse
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        $data = $request->validate([
            'email_destino' => ['required', 'email', 'max:255'],
        ]);

        $contaReceber->load(['cliente', 'empresa']);

        $clienteNome = $contaReceber->cliente->razao_social ?? $contaReceber->cliente->nome_fantasia ?? 'Cliente';
        $assunto = 'Fatura #' . $contaReceber->id . ' - ' . $clienteNome;
        $pdf = $this->buildFaturaPdf($contaReceber);
        $pdfBinary = $pdf->output();
        $pdfName = 'fatura-' . $contaReceber->id . '.pdf';

        Mail::send('financeiro.contas-receber.mail-fatura', [
            'conta' => $contaReceber,
        ], function ($message) use ($data, $assunto, $pdfBinary, $pdfName) {
            $message->to($data['email_destino'])->subject($assunto);
            $message->attachData($pdfBinary, $pdfName, ['mime' => 'application/pdf']);
        });

        return back()->with('success', 'Fatura enviada por e-mail com sucesso.');
    }

    private function buildFaturaPdf(ContaReceber $contaReceber)
    {
        $contaReceber->loadMissing([
            'cliente.cidade',
            'empresa.cidade',
            'itens.venda.contrato.propostaOrigem',
            'itens.venda.tarefa.funcionario',
            'itens.vendaItem',
            'itens.servico',
            'baixas',
        ]);

        return Pdf::loadView('financeiro.contas-receber.print', [
            'conta' => $contaReceber,
        ])->setPaper('a4', 'portrait');
    }

    public function excluirBaixa(Request $request, ContaReceber $contaReceber, ContaReceberService $service): RedirectResponse
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        if (!$contaReceber->baixas()->exists()) {
            return back()->with('error', 'Esta fatura não possui baixa para excluir.');
        }

        DB::transaction(function () use ($contaReceber, $service) {
            $service->excluirBaixas($contaReceber);
        });

        return back()->with('success', 'Baixa excluída com sucesso. A fatura foi reaberta para edição.');
    }

    public function destroy(Request $request, ContaReceber $contaReceber, ContaReceberService $service): RedirectResponse
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        if ($contaReceber->baixas()->exists()) {
            return back()->with('error', 'Para remover esta fatura, primeiro exclua a baixa.');
        }

        DB::transaction(function () use ($contaReceber, $service) {
            $contaReceber->load('itens');
            $vendaIds = $contaReceber->itens
                ->pluck('venda_id')
                ->filter()
                ->unique()
                ->values();
            $contaReceber->itens()->delete();
            $contaReceber->delete();

            foreach ($vendaIds as $vendaId) {
                $service->atualizarStatusVenda((int) $vendaId);
            }
        });

        return redirect()
            ->route('financeiro.contas-receber')
            ->with('success', 'Recebimento excluído e itens retornados para vendas pendentes.');
    }

    public function baixar(Request $request, ContaReceber $contaReceber, ContaReceberService $service): RedirectResponse
    {
        if ($contaReceber->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }

        $formasPagamento = $this->formasPagamento();
        $data = $request->validate([
            'valor' => ['required', 'numeric', 'min:0.01'],
            'pago_em' => ['nullable', 'date'],
            'meio_pagamento' => ['required', 'string', 'in:'.implode(',', $formasPagamento)],
            'observacao' => ['nullable', 'string', 'max:1000'],
            'comprovante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $comprovante = $request->file('comprovante');
        $comprovantePath = S3Helper::upload($comprovante, 'contas-receber/' . $contaReceber->empresa_id);

        $valorAplicado = $service->aplicarBaixa(
            $contaReceber,
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

    private function resolverVencimentoPropostoCliente(int $clienteId, ?int $empresaId, $baseDate = null): array
    {
        $base = $baseDate ? Carbon::parse($baseDate) : now();

        $parametro = ParametroCliente::query()
            ->where('cliente_id', $clienteId)
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->latest('id')
            ->first();

        $dia = (int) ($parametro?->vencimento_servicos ?? 0);
        if ($dia < 1 || $dia > 31) {
            return ['dia' => null, 'data' => null];
        }

        $proposta = $base->copy()->startOfDay();
        $diasNoMes = $proposta->daysInMonth;
        $diaAplicado = min($dia, $diasNoMes);
        $proposta->day($diaAplicado);

        if ($proposta->lt($base->copy()->startOfDay())) {
            $proposta = $base->copy()->addMonthNoOverflow()->startOfMonth();
            $diaAplicado = min($dia, $proposta->daysInMonth);
            $proposta->day($diaAplicado);
        }

        return ['dia' => $dia, 'data' => $proposta];
    }
}
