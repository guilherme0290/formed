@extends('layouts.financeiro')
@section('title', 'Contas a Pagar')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $subAba = $subAba ?? 'contas';
        $contaDetalhe = $contaDetalhe ?? null;
        $totais = $totaisContas ?? [];
        $fornecedorArea = $fornecedorArea ?? ['busca' => '', 'modalAberto' => false, 'modalModo' => 'create', 'fornecedorEdicao' => null];
        $fornecedoresLista = $fornecedoresLista ?? collect();

        $statusBadge = function (float $total, float $pago): array {
            $saldo = max(0, $total - $pago);
            if ($saldo <= 0.0001 && $total > 0) {
                return ['label' => 'Paga', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-100'];
            }
            if ($pago > 0.0001) {
                return ['label' => 'Parcial', 'class' => 'bg-sky-50 text-sky-700 border-sky-100'];
            }
            return ['label' => 'Aberta', 'class' => 'bg-amber-50 text-amber-700 border-amber-100'];
        };

        $querySemDetalhe = request()->except(['detalhe_id', 'page']);
        $urlLimparContas = route('financeiro.contas-pagar.index', ['subaba' => 'contas']);
        $urlSubContas = route('financeiro.contas-pagar.index', array_merge(request()->except(['fornecedor_modal', 'fornecedor']), ['subaba' => 'contas']));
        $urlSubFornecedores = route('financeiro.contas-pagar.index', array_merge(request()->except(['detalhe_id', 'page']), ['subaba' => 'fornecedores']));
        $urlNovoFornecedorSubaba = route('financeiro.contas-pagar.index', array_merge(request()->except(['fornecedor']), ['subaba' => 'fornecedores', 'fornecedor_modal' => 'create']));
        $urlNovaContaSubaba = route('financeiro.contas-pagar.create');

        $detalheTotal = $contaDetalhe ? (float) $contaDetalhe->total : 0;
        $detalhePago = $contaDetalhe ? (float) $contaDetalhe->total_baixado : 0;
        $detalheSaldo = $contaDetalhe ? (float) $contaDetalhe->total_aberto : 0;
        $detalheBadge = $contaDetalhe ? $statusBadge($detalheTotal, $detalhePago) : null;
        $detalheDescricao = $contaDetalhe ? trim((string) ($contaDetalhe->observacao ?? '')) : '';
        if ($contaDetalhe && $detalheDescricao === '') {
            $primeiroItem = $contaDetalhe->itens->sortBy('id')->first();
            $detalheDescricao = (string) ($primeiroItem->descricao ?? 'Sem descrição');
        }

        $fornModalAberto = (bool) ($fornecedorArea['modalAberto'] ?? false);
        $fornModalModo = (string) ($fornecedorArea['modalModo'] ?? 'create');
        $fornEdicao = $fornecedorArea['fornecedorEdicao'] ?? null;
        $fornIsEdit = $fornModalModo === 'edit' && $fornEdicao;
        $fornModalBaseUrl = route('financeiro.contas-pagar.index', array_merge(
            request()->except(['fornecedor_modal', 'fornecedor']),
            ['subaba' => 'fornecedores']
        ));
        $fornModalAction = $fornIsEdit ? route('financeiro.fornecedores.update', $fornEdicao) : route('financeiro.fornecedores.store');
        $fornEnderecoOpen = collect([
            old('cep', $fornEdicao->cep ?? ''),
            old('logradouro', $fornEdicao->logradouro ?? ''),
            old('numero', $fornEdicao->numero ?? ''),
            old('complemento', $fornEdicao->complemento ?? ''),
            old('bairro', $fornEdicao->bairro ?? ''),
            old('cidade', $fornEdicao->cidade ?? ''),
            old('uf', $fornEdicao->uf ?? ''),
        ])->contains(fn ($value) => filled($value));
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-[0.2em] text-indigo-400">Financeiro</div>
                <h1 class="text-3xl font-semibold text-slate-900">Contas a Pagar</h1>
                <p class="text-sm text-slate-500">Painel de despesas, pagamentos e fornecedores</p>
            </div>
        </div>

        @include('financeiro.partials.tabs')

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-2">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <a href="{{ $urlSubContas }}"
                       class="px-3 py-2 rounded-xl text-sm font-semibold whitespace-nowrap {{ $subAba === 'contas' ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        1) Contas
                    </a>
                    <a href="{{ $urlSubFornecedores }}"
                       class="px-3 py-2 rounded-xl text-sm font-semibold whitespace-nowrap {{ $subAba === 'fornecedores' ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        2) Fornecedores
                    </a>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if($subAba === 'contas')
                        <a href="{{ $urlNovoFornecedorSubaba }}"
                           class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50">
                            Novo Fornecedor
                        </a>
                        <a href="{{ $urlNovaContaSubaba }}"
                           class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            Nova Conta
                        </a>
                    @else
                        <a href="{{ $urlNovoFornecedorSubaba }}"
                           class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            Novo Fornecedor
                        </a>
                    @endif
                </div>
            </div>
        </section>

        @if(session('error'))
            <div class="rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        @if($subAba === 'contas')
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <header class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-800">Filtros do contas a pagar</h2>
                    <p class="text-xs text-slate-500 mt-1">Filtre por conta, fornecedor, status, descrição e período.</p>
                </header>

                <form method="GET" class="px-5 py-4 grid gap-4 md:grid-cols-12 items-end">
                    <input type="hidden" name="subaba" value="contas">

                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">ID conta</label>
                        <input type="text" name="conta_id" value="{{ $filtros['conta_id'] ?? '' }}" placeholder="Ex.: 245"
                               class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                    </div>

                    <div class="md:col-span-3">
                        <label class="text-xs font-semibold text-slate-600">Fornecedor</label>
                        <select name="fornecedor_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                            <option value="">Todos</option>
                            @foreach($fornecedores as $fornecedor)
                                <option value="{{ $fornecedor->id }}" @selected((string) ($filtros['fornecedor_id'] ?? '') === (string) $fornecedor->id)>
                                    {{ $fornecedor->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Status</label>
                        <select name="status_conta" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                            <option value="">Todos</option>
                            <option value="aberta" @selected(($filtros['status_conta'] ?? '') === 'aberta')>Aberta</option>
                            <option value="parcial" @selected(($filtros['status_conta'] ?? '') === 'parcial')>Parcial</option>
                            <option value="paga" @selected(($filtros['status_conta'] ?? '') === 'paga')>Paga</option>
                        </select>
                    </div>

                    <div class="md:col-span-5">
                        <label class="text-xs font-semibold text-slate-600">Descrição</label>
                        <input type="text" name="descricao" value="{{ $filtros['descricao'] ?? '' }}"
                               placeholder="Busca em observação da conta e descrição dos itens"
                               class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                    </div>

                    <div class="md:col-span-3">
                        <label class="text-xs font-semibold text-slate-600">Período por</label>
                        <select name="tipo_periodo" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                            <option value="vencimento" @selected(($filtros['tipo_periodo'] ?? 'vencimento') === 'vencimento')>Vencimento</option>
                            <option value="pagamento" @selected(($filtros['tipo_periodo'] ?? '') === 'pagamento')>Pagamento</option>
                            <option value="criacao" @selected(($filtros['tipo_periodo'] ?? '') === 'criacao')>Criação</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Data inicial</label>
                        <input type="date" name="data_inicio" value="{{ $filtros['data_inicio'] ?? '' }}"
                               class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Data final</label>
                        <input type="date" name="data_fim" value="{{ $filtros['data_fim'] ?? '' }}"
                               class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                    </div>

                    <div class="md:col-span-5 flex items-center justify-between gap-2">
                        <p class="text-xs text-slate-500">O total pago no período considera as baixas registradas na faixa filtrada.</p>
                        <div class="flex items-center gap-2">
                            <button class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Filtrar</button>
                            <a href="{{ $urlLimparContas }}" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50">Limpar</a>
                        </div>
                    </div>
                </form>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-amber-700 font-semibold">Em aberto</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-900">R$ {{ number_format((float) ($totais['valor_aberto'] ?? 0), 2, ',', '.') }}</p>
                    <p class="text-xs text-amber-700/80 mt-1">Saldo das contas filtradas</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-emerald-700 font-semibold">Pago no período</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-900">R$ {{ number_format((float) ($totais['valor_pago_periodo'] ?? 0), 2, ',', '.') }}</p>
                    <p class="text-xs text-emerald-700/80 mt-1">Baixas dentro da faixa de datas</p>
                </div>
                <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-indigo-700 font-semibold">Total das contas</p>
                    <p class="mt-1 text-2xl font-semibold text-indigo-900">R$ {{ number_format((float) ($totais['valor_total'] ?? 0), 2, ',', '.') }}</p>
                    <p class="text-xs text-indigo-700/80 mt-1">{{ (int) ($totais['qtd_contas'] ?? 0) }} contas filtradas</p>
                </div>
                <div class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-sky-700 font-semibold">Contas pagas</p>
                    <p class="mt-1 text-2xl font-semibold text-sky-900">{{ (int) ($totais['qtd_pagas'] ?? 0) }}</p>
                    <p class="text-xs text-sky-700/80 mt-1">Valor pago acumulado: R$ {{ number_format((float) ($totais['valor_pago'] ?? 0), 2, ',', '.') }}</p>
                </div>
            </section>

            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Contas geradas</h2>
                        <p class="text-xs text-slate-500">Lista principal de contas a pagar com status, descrição e totais.</p>
                    </div>
                    <p class="text-xs text-slate-500">Clique em <strong class="text-slate-700">Ver detalhe</strong> para abrir cabeçalho e itens abaixo.</p>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">ID</th>
                            <th class="px-4 py-3 text-left font-semibold">Fornecedor</th>
                            <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                            <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                            <th class="px-4 py-3 text-right font-semibold">Total</th>
                            <th class="px-4 py-3 text-right font-semibold">Pago</th>
                            <th class="px-4 py-3 text-right font-semibold">Saldo</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Ações</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($contas as $conta)
                            @php
                                $total = (float) $conta->total;
                                $pago = (float) $conta->total_baixado;
                                $saldo = max(0, $total - $pago);
                                $badge = $statusBadge($total, $pago);
                                $descricaoLinha = trim((string) ($conta->observacao ?? ''));
                                $primeiroItem = $conta->itens->sortBy('id')->first();
                                $itemCount = (int) ($conta->itens_count ?? $conta->itens->count());
                                if ($descricaoLinha === '') {
                                    $descricaoLinha = (string) ($primeiroItem->descricao ?? 'Sem descrição');
                                }
                                if ($itemCount > 1 && $primeiroItem) {
                                    $descricaoLinha .= ' (+' . ($itemCount - 1) . ' item(ns))';
                                }
                                $detalheUrl = route('financeiro.contas-pagar.index', array_merge($querySemDetalhe, ['detalhe_id' => $conta->id, 'subaba' => 'contas']));
                            @endphp
                            <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/70">
                                <td class="px-4 py-3 text-slate-800 font-semibold">#{{ $conta->id }}</td>
                                <td class="px-4 py-3 text-slate-800">{{ $conta->fornecedor->razao_social ?? 'Fornecedor' }}</td>
                                <td class="px-4 py-3 text-slate-700 max-w-[26rem]">
                                    <div class="truncate" title="{{ $descricaoLinha }}">{{ $descricaoLinha }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($conta->vencimento)->format('d/m/Y') ?: '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($total, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($pago, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $saldo > 0.0001 ? 'text-amber-700' : 'text-emerald-700' }}">R$ {{ number_format($saldo, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $badge['class'] }}">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ $detalheUrl }}"
                                           class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">
                                            Ver detalhe
                                        </a>
                                        <a href="{{ route('financeiro.contas-pagar.show', $conta) }}"
                                           class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                                            Abrir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">Nenhuma conta a pagar encontrada.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <footer class="px-5 py-4 border-t border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <p class="text-sm text-slate-600">
                        Total nesta página: <strong>{{ $contas->count() }}</strong>
                        <span class="text-slate-400">•</span>
                        Página {{ $contas->currentPage() }} de {{ max(1, $contas->lastPage()) }}
                    </p>
                    <div>{{ $contas->links() }}</div>
                </footer>
            </section>

            @if($contaDetalhe)
                <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                    <header class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-xs uppercase tracking-[0.2em] text-indigo-400">Detalhe selecionado</div>
                            <h2 class="text-xl font-semibold text-slate-900">Conta #{{ $contaDetalhe->id }}</h2>
                            <p class="text-sm text-slate-500">{{ $contaDetalhe->fornecedor->razao_social ?? 'Fornecedor' }}</p>
                        </div>
                        <a href="{{ route('financeiro.contas-pagar.show', $contaDetalhe) }}"
                           class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            Abrir tela completa
                        </a>
                    </header>

                    <div class="px-5 pt-4">
                        <div class="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto">
                            <button type="button" data-cp-detail-tab-btn="cabecalho"
                                    class="px-3 py-2 rounded-xl text-sm font-semibold bg-indigo-600 text-white whitespace-nowrap">
                                Cabeçalho
                            </button>
                            <button type="button" data-cp-detail-tab-btn="itens"
                                    class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 whitespace-nowrap">
                                Itens ({{ $contaDetalhe->itens->count() }})
                            </button>
                        </div>
                    </div>

                    <section data-cp-detail-tab="cabecalho" class="p-5 space-y-4">
                        <div class="grid gap-4 md:grid-cols-12">
                            <div class="md:col-span-7 rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 to-white p-5">
                                <p class="text-xs uppercase tracking-wide font-semibold text-indigo-700">Descrição da conta</p>
                                <p class="mt-1 text-lg md:text-xl font-semibold text-slate-900">{{ $detalheDescricao ?: 'Sem descrição' }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
                                    <span>Fornecedor: <strong class="text-slate-900">{{ $contaDetalhe->fornecedor->razao_social ?? 'Fornecedor' }}</strong></span>
                                    <span>ID: <strong class="text-slate-900">#{{ $contaDetalhe->id }}</strong></span>
                                </div>
                                @if(filled($contaDetalhe->observacao))
                                    <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Observação</p>
                                        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ $contaDetalhe->observacao }}</p>
                                    </div>
                                @endif
                            </div>

                            <div class="md:col-span-5 grid gap-3 content-start">
                                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                    <p class="text-xs text-slate-500">Status</p>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $detalheBadge['class'] }}">
                                            {{ $detalheBadge['label'] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                        <p class="text-xs text-slate-500">Emissão</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($contaDetalhe->created_at)->format('d/m/Y') ?: '—' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                        <p class="text-xs text-slate-500">Vencimento</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($contaDetalhe->vencimento)->format('d/m/Y') ?: '—' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                                        <p class="text-xs text-indigo-700">Total</p>
                                        <p class="mt-1 text-sm font-semibold text-indigo-900">R$ {{ number_format($detalheTotal, 2, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                                        <p class="text-xs text-emerald-700">Pago</p>
                                        <p class="mt-1 text-sm font-semibold text-emerald-900">R$ {{ number_format($detalhePago, 2, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
                                        <p class="text-xs text-amber-700">Saldo</p>
                                        <p class="mt-1 text-sm font-semibold text-amber-900">R$ {{ number_format($detalheSaldo, 2, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section data-cp-detail-tab="itens" class="hidden p-5">
                        <div class="overflow-x-auto rounded-2xl border border-slate-100">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Categoria</th>
                                    <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                                    <th class="px-4 py-3 text-left font-semibold">Competência</th>
                                    <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                                    <th class="px-4 py-3 text-right font-semibold">Valor</th>
                                    <th class="px-4 py-3 text-right font-semibold">Pago</th>
                                    <th class="px-4 py-3 text-right font-semibold">Saldo</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                @forelse($contaDetalhe->itens->sortBy('id') as $item)
                                    @php
                                        $itemPago = (float) $item->baixas->sum('valor');
                                        $itemSaldo = max(0, (float) $item->valor - $itemPago);
                                        $itemStatus = strtoupper((string) $item->status);
                                        $itemBadge = match (true) {
                                            $itemSaldo <= 0.0001 && (float) $item->valor > 0 => ['label' => 'Baixado', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-100'],
                                            $itemPago > 0.0001 => ['label' => 'Parcial', 'class' => 'bg-sky-50 text-sky-700 border-sky-100'],
                                            $itemStatus === 'CANCELADO' => ['label' => 'Cancelado', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
                                            default => ['label' => 'Aberto', 'class' => 'bg-amber-50 text-amber-700 border-amber-100'],
                                        };
                                    @endphp
                                    <tr class="odd:bg-white even:bg-slate-50">
                                        <td class="px-4 py-3 text-slate-700">{{ $item->categoria ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-800">{{ $item->descricao }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ optional($item->data_competencia)->format('d/m/Y') ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ optional($item->vencimento)->format('d/m/Y') ?: '—' }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format((float) $item->valor, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($itemPago, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-semibold {{ $itemSaldo > 0.0001 ? 'text-amber-700' : 'text-emerald-700' }}">R$ {{ number_format($itemSaldo, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $itemBadge['class'] }}">
                                                {{ $itemBadge['label'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-500">Nenhum item encontrado para esta conta.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </section>
            @endif
        @else
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <header class="px-5 py-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Fornecedores</h2>
                        <p class="text-xs text-slate-500">Operações de cadastro, edição e exclusão sem sair do contas a pagar.</p>
                    </div>
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="subaba" value="fornecedores">
                        <input type="text" name="fornecedor_busca" value="{{ $fornecedorArea['busca'] ?? '' }}"
                               placeholder="Razão social, fantasia ou CPF/CNPJ"
                               class="w-72 max-w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                        <button class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Filtrar</button>
                        <a href="{{ route('financeiro.contas-pagar.index', ['subaba' => 'fornecedores']) }}"
                           class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50">
                            Limpar
                        </a>
                    </form>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Razão social</th>
                            <th class="px-4 py-3 text-left font-semibold">Documento</th>
                            <th class="px-4 py-3 text-left font-semibold">Contato</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Ações</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($fornecedoresLista as $fornecedorRow)
                            @php
                                $fornEditUrl = route('financeiro.contas-pagar.index', array_merge(
                                    request()->except(['fornecedor']),
                                    ['subaba' => 'fornecedores', 'fornecedor_modal' => 'edit', 'fornecedor' => $fornecedorRow->id]
                                ));
                            @endphp
                            <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ $fornecedorRow->razao_social }}</div>
                                    <div class="text-xs text-slate-500">{{ $fornecedorRow->nome_fantasia ?: '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $fornecedorRow->cpf_cnpj ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div>{{ $fornecedorRow->contato_nome ?: '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ $fornecedorRow->email ?: '—' }} · {{ $fornecedorRow->telefone ?: '—' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($fornecedorRow->ativo)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">Ativo</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="{{ $fornEditUrl }}"
                                           class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('financeiro.fornecedores.destroy', $fornecedorRow) }}"
                                              onsubmit="return confirm('Deseja excluir este fornecedor?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="_return_url" value="{{ $fornModalBaseUrl }}">
                                            <button class="px-3 py-2 rounded-lg bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700">
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum fornecedor encontrado.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($fornecedoresLista, 'links'))
                    <footer class="px-5 py-4 border-t border-slate-100">
                        {{ $fornecedoresLista->links() }}
                    </footer>
                @endif
            </section>

            @if($fornModalAberto)
                <div class="fixed inset-0 z-[95] overflow-y-auto">
                    <a href="{{ $fornModalBaseUrl }}" class="absolute inset-0 bg-slate-900/60 backdrop-blur-[1px]" aria-label="Fechar modal"></a>
                    <div class="relative min-h-screen px-3 py-6 md:px-6 md:py-10 flex items-start justify-center">
                        <div class="relative w-full max-w-4xl rounded-3xl border border-slate-200 bg-white shadow-2xl overflow-hidden">
                            <div class="px-5 md:px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.2em] text-indigo-500">Fornecedores</div>
                                        <h2 class="text-xl md:text-2xl font-semibold text-slate-900">{{ $fornIsEdit ? 'Editar fornecedor' : 'Novo fornecedor' }}</h2>
                                        <p class="text-sm text-slate-500 mt-1">{{ $fornIsEdit ? ($fornEdicao->razao_social ?? '') : 'Cadastro rápido dentro do contas a pagar' }}</p>
                                    </div>
                                    <a href="{{ $fornModalBaseUrl }}"
                                       class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:bg-slate-50"
                                       aria-label="Fechar modal">✕</a>
                                </div>
                            </div>

                            @if($errors->any())
                                <div class="mx-5 md:mx-6 mt-4 rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">
                                    <div class="font-semibold">Revise os campos obrigatórios.</div>
                                    <div class="mt-1">{{ $errors->first() }}</div>
                                </div>
                            @endif

                            <form method="POST" action="{{ $fornModalAction }}" class="px-5 md:px-6 py-5 space-y-5">
                                @csrf
                                @if($fornIsEdit)
                                    @method('PUT')
                                @endif
                                <input type="hidden" name="_return_url" value="{{ $fornModalBaseUrl }}">
                                <input type="hidden" name="fornecedor_modal_context" value="{{ $fornIsEdit ? 'edit' : 'create' }}">
                                <input type="hidden" name="fornecedor_modal_edit_id" value="{{ $fornIsEdit ? $fornEdicao->id : '' }}">

                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                    <div class="grid gap-4 md:grid-cols-12 items-start">
                                        <div class="md:col-span-8">
                                            <label class="text-xs font-semibold text-slate-600">Razão social *</label>
                                            <input type="text" name="razao_social" required autofocus
                                                   value="{{ old('razao_social', $fornEdicao->razao_social ?? '') }}"
                                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-xs font-semibold text-slate-600">Tipo *</label>
                                            <select name="tipo_pessoa" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                                <option value="PJ" @selected(old('tipo_pessoa', $fornEdicao->tipo_pessoa ?? 'PJ') === 'PJ')>PJ</option>
                                                <option value="PF" @selected(old('tipo_pessoa', $fornEdicao->tipo_pessoa ?? 'PJ') === 'PF')>PF</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-xs font-semibold text-slate-600">Status</label>
                                            <div class="mt-1 h-[42px] rounded-xl border border-slate-200 bg-white px-3 flex items-center justify-center">
                                                <x-toggle-ativo
                                                    name="ativo"
                                                    :checked="(bool) old('ativo', $fornEdicao?->ativo ?? true)"
                                                    on-label="Ativo"
                                                    off-label="Inativo"
                                                    text-class="text-sm font-medium text-slate-700"
                                                />
                                            </div>
                                        </div>

                                        <div class="md:col-span-4">
                                            <label class="text-xs font-semibold text-slate-600">CPF/CNPJ</label>
                                            <input type="text" name="cpf_cnpj" value="{{ old('cpf_cnpj', $fornEdicao->cpf_cnpj ?? '') }}"
                                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                        <div class="md:col-span-4">
                                            <label class="text-xs font-semibold text-slate-600">Contato</label>
                                            <input type="text" name="contato_nome" value="{{ old('contato_nome', $fornEdicao->contato_nome ?? '') }}"
                                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                        <div class="md:col-span-4">
                                            <label class="text-xs font-semibold text-slate-600">Telefone</label>
                                            <input type="text" name="telefone" value="{{ old('telefone', $fornEdicao->telefone ?? '') }}"
                                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>

                                        <div class="md:col-span-6">
                                            <label class="text-xs font-semibold text-slate-600">E-mail</label>
                                            <input type="email" name="email" value="{{ old('email', $fornEdicao->email ?? '') }}"
                                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                        <div class="md:col-span-6">
                                            <label class="text-xs font-semibold text-slate-600">Nome fantasia</label>
                                            <input type="text" name="nome_fantasia" value="{{ old('nome_fantasia', $fornEdicao->nome_fantasia ?? '') }}"
                                                   class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                    </div>
                                </div>

                                <details class="rounded-2xl border border-slate-200 bg-white" @if($fornEnderecoOpen) open @endif>
                                    <summary class="cursor-pointer list-none px-4 py-3 flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">Dados complementares (endereço)</p>
                                            <p class="text-xs text-slate-500">Opcional.</p>
                                        </div>
                                        <span class="text-xs text-indigo-600 font-semibold">Expandir</span>
                                    </summary>
                                    <div class="px-4 pb-4 pt-1 grid gap-4 md:grid-cols-6 border-t border-slate-100">
                                        <div class="md:col-span-2">
                                            <label class="text-xs font-semibold text-slate-600">CEP</label>
                                            <input type="text" name="cep" value="{{ old('cep', $fornEdicao->cep ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                        <div class="md:col-span-4">
                                            <label class="text-xs font-semibold text-slate-600">Logradouro</label>
                                            <input type="text" name="logradouro" value="{{ old('logradouro', $fornEdicao->logradouro ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                        <div class="md:col-span-1">
                                            <label class="text-xs font-semibold text-slate-600">Nº</label>
                                            <input type="text" name="numero" value="{{ old('numero', $fornEdicao->numero ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-xs font-semibold text-slate-600">Complemento</label>
                                            <input type="text" name="complemento" value="{{ old('complemento', $fornEdicao->complemento ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-xs font-semibold text-slate-600">Bairro</label>
                                            <input type="text" name="bairro" value="{{ old('bairro', $fornEdicao->bairro ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                        <div class="md:col-span-1">
                                            <label class="text-xs font-semibold text-slate-600">UF</label>
                                            <input type="text" name="uf" maxlength="2" value="{{ old('uf', $fornEdicao->uf ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5 uppercase">
                                        </div>
                                        <div class="md:col-span-6">
                                            <label class="text-xs font-semibold text-slate-600">Cidade</label>
                                            <input type="text" name="cidade" value="{{ old('cidade', $fornEdicao->cidade ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        </div>
                                    </div>
                                </details>

                                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-1 border-t border-slate-100">
                                    <a href="{{ $fornModalBaseUrl }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                        Cancelar
                                    </a>
                                    <button class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                                        {{ $fornIsEdit ? 'Salvar alterações' : 'Cadastrar fornecedor' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('keydown', function (event) {
                        if (event.key !== 'Escape') return;
                        window.location.href = @json($fornModalBaseUrl);
                    });
                </script>
            @endif
        @endif
    </div>

    @if($subAba === 'contas' && $contaDetalhe)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const buttons = document.querySelectorAll('[data-cp-detail-tab-btn]');
                const sections = document.querySelectorAll('[data-cp-detail-tab]');
                if (!buttons.length || !sections.length) return;

                buttons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const target = button.dataset.cpDetailTabBtn;
                        sections.forEach(function (section) {
                            section.classList.toggle('hidden', section.dataset.cpDetailTab !== target);
                        });
                        buttons.forEach(function (btn) {
                            const active = btn.dataset.cpDetailTabBtn === target;
                            btn.classList.toggle('bg-indigo-600', active);
                            btn.classList.toggle('text-white', active);
                            btn.classList.toggle('text-slate-700', !active);
                            btn.classList.toggle('hover:bg-slate-100', !active);
                        });
                    });
                });
            });
        </script>
    @endif
@endsection
