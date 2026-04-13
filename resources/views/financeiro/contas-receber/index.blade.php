@extends('layouts.financeiro')
@section('title', 'Contas a Receber')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canCreate = $isMaster || isset($permissionMap['financeiro.contas-receber.create']);
        $canUpdate = $isMaster || isset($permissionMap['financeiro.contas-receber.update']);
        $canDelete = $isMaster || isset($permissionMap['financeiro.contas-receber.delete']);

        $vendasAgrupadas = $vendaItens
            ->filter(fn ($item) => $item->venda)
            ->groupBy('venda_id')
            ->map(function ($itensVenda) use ($filtros) {
                $primeiro = $itensVenda->first();
                $venda = $primeiro?->venda;
                $dataVenda = $venda?->created_at;
                $dataFinalizacao = $venda?->tarefa?->finalizado_em;
                $dataReferencia = ($filtros['tipo_data'] ?? 'venda') === 'finalizacao' ? $dataFinalizacao : $dataVenda;
                $isFinalizada = !is_null($dataFinalizacao);

                return [
                    'venda' => $venda,
                    'itens' => $itensVenda,
                    'is_finalizada' => $isFinalizada,
                    'status_label' => $isFinalizada ? 'Finalizada (sem fatura)' : 'Venda não finalizada',
                    'status_badge' => $isFinalizada
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                        : 'bg-amber-50 text-amber-700 border-amber-100',
                    'cliente_nome' => $venda?->cliente?->razao_social ?? $venda?->cliente?->nome_fantasia ?? 'Cliente',
                    'data_referencia' => $dataReferencia,
                    'total' => (float) $itensVenda->sum(fn ($item) => (float) ($item->subtotal_snapshot ?? 0)),
                    'qtd_itens' => $itensVenda->count(),
                ];
            })
            ->values();
        $tarefasNaoFinalizadasAgrupadas = collect($tarefasNaoFinalizadasAgrupadas ?? []);
        $registrosFinalizados = $vendasAgrupadas
            ->where('is_finalizada', true)
            ->values()
            ->map(fn ($grupo) => ['tipo' => 'venda', 'grupo' => $grupo]);
        $registrosNaoFinalizados = $tarefasNaoFinalizadasAgrupadas
            ->map(fn ($grupo) => ['tipo' => 'tarefa', 'grupo' => $grupo])
            ->values();
        $totalItensSemFatura = (int) $registrosFinalizados->sum(fn ($registro) => (int) ($registro['grupo']['qtd_itens'] ?? 0))
            + (int) $registrosNaoFinalizados->sum(fn ($registro) => (int) ($registro['grupo']['qtd_itens'] ?? 0));
        $valorTotalVendasSemFatura = (float) $registrosFinalizados->sum(fn ($registro) => (float) ($registro['grupo']['total'] ?? 0));
        $valorTotalServicosNaoFinalizados = (float) $registrosNaoFinalizados->sum(fn ($registro) => (float) ($registro['grupo']['total'] ?? 0));
        $statusFinalizacaoFiltro = $filtros['status_finalizacao'] ?? 'todas';
        $mostrarVendasSemFatura = $statusFinalizacaoFiltro !== 'nao_finalizadas';
        $mostrarServicosNaoFinalizados = in_array($statusFinalizacaoFiltro, ['todas', 'nao_finalizadas'], true);
        $tituloContainerVendas = match ($statusFinalizacaoFiltro) {
            'nao_finalizadas' => 'Serviços não finalizados',
            'finalizadas' => 'Vendas finalizadas (sem fatura)',
            default => 'Vendas e serviços sem faturamento',
        };
        $descricaoContainerVendas = match ($statusFinalizacaoFiltro) {
            'nao_finalizadas' => 'Tarefas abertas, não canceladas e ainda sem venda gerada.',
            'finalizadas' => 'Apenas vendas finalizadas, prontas para gerar fatura.',
            default => 'Vendas finalizadas podem ser faturadas. Serviços não finalizados ficam para acompanhamento.',
        };
        $registrosPainelVendas = match ($statusFinalizacaoFiltro) {
            'finalizadas' => $registrosFinalizados,
            'nao_finalizadas' => $registrosNaoFinalizados,
            default => $registrosFinalizados->concat($registrosNaoFinalizados)->values(),
        };
        $registrosPainelVendas = $registrosPainelVendas
            ->sortByDesc(function ($registro) {
                return optional($registro['grupo']['data_referencia'] ?? null)?->timestamp ?? 0;
            })
            ->values();
        $contadorContainerVendas = $registrosPainelVendas->count();

        $abaAtiva = $abaAtiva ?? 'vendas';

        $contaDetalheSelecionada = $contaDetalhe ?? null;
        $detalheStatusRaw = strtoupper((string) ($contaDetalheSelecionada->status ?? ''));
        $detalheTotal = $contaDetalheSelecionada ? (float) $contaDetalheSelecionada->total : 0;
        $detalhePago = $contaDetalheSelecionada ? (float) $contaDetalheSelecionada->total_baixado : 0;
        $detalheSaldo = $contaDetalheSelecionada ? (float) $contaDetalheSelecionada->total_aberto : 0;
        $detalheHasBaixa = $contaDetalheSelecionada ? $contaDetalheSelecionada->baixas->isNotEmpty() : false;
        $detalheIsBaixada = $contaDetalheSelecionada ? ($detalheSaldo <= 0.0001 && $detalheTotal > 0) : false;
        $detalheIsFaturada = in_array($detalheStatusRaw, ['FATURADA', 'FATURADO'], true);
        $detalheStatus = $contaDetalheSelecionada ? match (true) {
            strtoupper((string) $contaDetalheSelecionada->status) === 'CANCELADO' => 'Cancelada',
            $detalheIsBaixada => 'Baixada',
            $detalheHasBaixa => 'Parcial',
            default => 'Aberta',
        } : null;
        $detalheStatusBadge = match ($detalheStatus) {
            'Baixada' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'Parcial' => 'bg-sky-50 text-sky-700 border-sky-100',
            'Aberta' => 'bg-amber-50 text-amber-700 border-amber-100',
            'Cancelada' => 'bg-slate-100 text-slate-700 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
        $detalheItensComData = $contaDetalheSelecionada
            ? $contaDetalheSelecionada->itens
                ->filter(fn ($item) => !is_null($item->data_realizacao))
                ->sortByDesc(fn ($item) => optional($item->data_realizacao)?->timestamp ?? 0)
                ->values()
            : collect();
        $detalheVencimentoSugerido = $contaDetalheVencimentoSugerido ?? null;
        $detalheDiaParametro = $contaDetalheVencimentoDiaParametro ?? null;
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex flex-col gap-2">
            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-indigo-400">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-2xl bg-indigo-500/20 text-pink-100 text-lg">💳</span>
                Contas a Receber
            </div>
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Faturas de clientes</h1>
                <p class="text-sm text-slate-500 mt-1">Monte faturas a partir de vendas sem fatura e acompanhe o painel de faturas</p>
            </div>
        </div>

        @include('financeiro.partials.tabs')

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-2">
            <div class="flex items-center gap-2 overflow-x-auto">
                <button type="button" data-cr-tab-btn="vendas" class="px-3 py-2 rounded-xl text-sm font-semibold whitespace-nowrap {{ $abaAtiva === 'vendas' ? 'bg-emerald-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    1) Vendas
                </button>
                <button type="button" data-cr-tab-btn="faturas" class="px-3 py-2 rounded-xl text-sm font-semibold whitespace-nowrap {{ $abaAtiva === 'faturas' ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    2) Faturas
                </button>
                @if($contaDetalheSelecionada)
                    <button type="button" data-cr-tab-btn="detalhe" class="px-3 py-2 rounded-xl text-sm font-semibold whitespace-nowrap {{ $abaAtiva === 'detalhe' ? 'bg-amber-500 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        3) Detalhe da Fatura
                    </button>
                @else
                    <span class="px-3 py-2 rounded-xl text-sm font-semibold whitespace-nowrap bg-slate-100 text-slate-400 cursor-not-allowed"
                          title="Abra o detalhe clicando em Ver na aba Faturas.">
                        3) Detalhe da Fatura
                    </span>
                @endif
            </div>
        </section>

        <section id="cr-filtros-vendas" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 {{ $abaAtiva === 'vendas' ? '' : 'hidden' }}">
            <form method="GET" class="grid gap-4 md:grid-cols-8 items-end">
                <input type="hidden" name="aba" value="vendas">
                @if($contaDetalheSelecionada)
                    <input type="hidden" name="fatura_id" value="{{ $contaDetalheSelecionada->id }}">
                @endif
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Período</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="data_inicio" value="{{ $filtros['data_inicio'] }}"
                               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                        <span class="text-slate-400">a</span>
                        <input type="date" name="data_fim" value="{{ $filtros['data_fim'] }}"
                               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Cliente</label>
                    <input type="hidden" name="cliente_id" id="cr-cliente-id" value="{{ $filtros['cliente_id'] ?? '' }}">
                    <div class="relative">
                        <input type="text"
                               name="cliente"
                               id="cr-cliente-autocomplete-input"
                               autocomplete="off"
                               value="{{ $filtros['cliente'] ?? '' }}"
                               placeholder="Todos os clientes"
                               class="w-full rounded-xl border border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 text-sm px-3 py-2 h-[42px]">
                        <div id="cr-cliente-autocomplete-list"
                             class="absolute z-20 mt-1 w-full max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg hidden"></div>
                    </div>
                </div>
                <div class="md:col-span-3">
                    <label class="text-xs font-semibold text-slate-600">Tipo de data</label>
                    <div class="flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="tipo_data" value="venda" @checked(($filtros['tipo_data'] ?? 'venda') === 'venda')>
                            Data da venda
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="tipo_data" value="finalizacao" @checked(($filtros['tipo_data'] ?? 'venda') === 'finalizacao')>
                            Data de finalização
                        </label>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Status da Venda</label>
                    <select name="status_finalizacao"
                            class="w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2 h-[42px]">
                        <option value="todas" @selected(($filtros['status_finalizacao'] ?? 'todas') === 'todas')>Todas</option>
                        <option value="finalizadas" @selected(($filtros['status_finalizacao'] ?? '') === 'finalizadas')>Finalizadas</option>
                        <option value="nao_finalizadas" @selected(($filtros['status_finalizacao'] ?? '') === 'nao_finalizadas')>Não finalizadas</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button class="inline-flex h-[42px] items-center justify-center px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 text-white text-sm font-semibold shadow-sm hover:from-indigo-700 hover:to-indigo-600">
                        Filtrar
                    </button>
                    <a href="{{ route('financeiro.contas-receber') }}"
                       class="inline-flex h-[42px] items-center justify-center px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Limpar
                    </a>
                </div>
            </form>
        </section>

        <section data-cr-tab="vendas" class="space-y-4 {{ $abaAtiva === 'vendas' ? '' : 'hidden' }}">
            <section class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                <article class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide font-semibold text-emerald-700">Itens sem fatura</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-900">
                        {{ number_format($totalItensSemFatura, 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-emerald-700/80">Quantidade de itens gerados a partir das tarefas</p>
                </article>

                <article class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide font-semibold text-indigo-700">Valores a faturar</p>
                    <p class="mt-1 text-2xl font-semibold text-indigo-900">
                        R$ {{ number_format($valorTotalVendasSemFatura, 2, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-indigo-700/80">Total das vendas prontas para gerar fatura</p>
                </article>

                <article class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide font-semibold text-amber-700">Valores a receber de serviços não finalizados</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-900">
                        R$ {{ number_format($valorTotalServicosNaoFinalizados, 2, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-amber-700/80">Total estimado dos serviços que ainda não foram concluídos</p>
                </article>
            </section>

            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">{{ $tituloContainerVendas }}</h2>
                        <p class="text-xs text-slate-500">{{ $descricaoContainerVendas }}</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ $contadorContainerVendas }} registros</span>
                </header>

                <form method="POST" action="{{ route('financeiro.contas-receber.store') }}" class="m-4 flex flex-col h-[68vh]" id="formCriarFatura">
                    @csrf
                    <input type="hidden" name="cliente_id" id="crClienteIdFatura" value="">
                    <input type="hidden" name="vencimento" value="">

                    <div class="flex-1 min-h-0">
                        <div class="h-full min-h-0 overflow-auto rounded-lg border border-slate-200 bg-white">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-slate-600 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-3 py-3 text-left font-semibold w-16">
                                            @if($mostrarVendasSemFatura)
                                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                                    <input type="checkbox"
                                                           id="crSelecionarTodosHeader"
                                                           class="rounded border-slate-300"
                                                           @if(!$canCreate) disabled @endif>
                                                </label>
                                            @endif
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold">Registro</th>
                                        <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                                        <th class="px-4 py-3 text-left font-semibold">Data</th>
                                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                                        <th class="px-4 py-3 text-right font-semibold">Itens</th>
                                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($registrosPainelVendas as $idx => $registro)
                                        @php
                                            $grupo = $registro['grupo'];
                                            $isVenda = $registro['tipo'] === 'venda';
                                            $expandId = ($isVenda ? 'cr-venda-expand-' : 'cr-tarefa-expand-') . $idx;
                                            $venda = $isVenda ? ($grupo['venda'] ?? null) : null;
                                            $tarefa = !$isVenda ? ($grupo['tarefa'] ?? null) : null;
                                            $vendaFinalizada = (bool) ($grupo['is_finalizada'] ?? false);
                                            $canSelectVenda = $isVenda && $canCreate && $vendaFinalizada;
                                        @endphp
                                        <tr class="odd:bg-white even:bg-slate-50/60 {{ $isVenda ? 'hover:bg-indigo-50/40' : 'hover:bg-amber-50/40' }} cursor-pointer transition"
                                            data-expand-toggle="{{ $expandId }}"
                                            aria-expanded="false"
                                            @if($isVenda)
                                                data-venda-total="{{ number_format($grupo['total'], 2, '.', '') }}"
                                                data-venda-itens="{{ $grupo['qtd_itens'] }}"
                                            @endif
                                            title="Clique para ver os itens">
                                            <td class="px-3 py-3 align-middle">
                                                @if($isVenda)
                                                    <input type="checkbox"
                                                           class="rounded border-slate-300 js-venda-master {{ $canSelectVenda ? '' : 'opacity-60 cursor-not-allowed' }}"
                                                           data-venda-target="{{ $expandId }}"
                                                           @if(!$canSelectVenda) disabled title="{{ !$canCreate ? 'Usuário sem permissão' : 'Venda não finalizada não pode ser faturada.' }}" @endif>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 align-middle">
                                                <div class="inline-flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-slate-900 px-2 text-[11px] font-bold text-white">
                                                        {{ $isVenda ? '#' . ($venda->id ?? '—') : 'T#' . ($tarefa->id ?? '—') }}
                                                    </span>
                                                    <button type="button"
                                                            class="inline-flex items-center gap-1 rounded-full border {{ $isVenda ? 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100' : 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' }} px-2.5 py-1 text-[11px] font-semibold"
                                                            data-expand-action="{{ $expandId }}"
                                                            data-expand-icon="{{ $expandId }}"
                                                            aria-expanded="false"
                                                            title="Mostrar itens">
                                                        Itens ▸
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-800 align-middle">{{ $grupo['cliente_nome'] }}</td>
                                            <td class="px-4 py-3 text-slate-700 align-middle">{{ $grupo['data_referencia']?->format('d/m/Y H:i') ?? '—' }}</td>
                                            <td class="px-4 py-3 align-middle">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold border {{ $grupo['status_badge'] }}">
                                                    {{ $grupo['status_label'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right text-slate-700 align-middle">{{ $grupo['qtd_itens'] }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-slate-900 align-middle">R$ {{ number_format($grupo['total'], 2, ',', '.') }}</td>
                                        </tr>
                                        <tr id="{{ $expandId }}" class="hidden {{ $isVenda ? 'bg-indigo-50/30' : 'bg-amber-50/30' }}">
                                            <td colspan="7" class="px-4 pb-4 pt-0">
                                                <div class="mt-2 rounded-xl border {{ $isVenda ? 'border-indigo-100' : 'border-amber-100' }} bg-white p-3">
                                                    <div class="flex items-center justify-between gap-2 mb-3">
                                                        <p class="text-xs font-semibold uppercase tracking-wide {{ $isVenda ? 'text-indigo-700' : 'text-amber-700' }}">
                                                            {{ $isVenda ? 'Itens da venda #' . ($venda->id ?? '—') : 'Itens estimados da tarefa #' . ($tarefa->id ?? '—') }}
                                                        </p>
                                                        <span class="text-xs text-slate-500">
                                                            {{ $grupo['qtd_itens'] }} itens {{ $isVenda ? ($vendaFinalizada ? 'elegíveis' : 'somente visualização') : 'estimados' }}
                                                        </span>
                                                    </div>

                                                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                                                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                                                            <thead class="bg-slate-50 text-slate-600">
                                                                <tr>
                                                                    <th class="px-3 py-2 text-left font-semibold w-10"></th>
                                                                    <th class="px-3 py-2 text-left font-semibold">Serviço</th>
                                                                    <th class="px-3 py-2 text-left font-semibold">Descrição</th>
                                                                    <th class="px-3 py-2 text-left font-semibold">Data</th>
                                                                    <th class="px-3 py-2 text-right font-semibold">Subtotal</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                                @if($isVenda)
                                                                    @foreach($grupo['itens'] as $itemVenda)
                                                                        @php
                                                                            $servicoNome = $itemVenda->servico?->nome ?? $itemVenda->descricao_snapshot ?? 'Serviço';
                                                                            $isAso = strtolower((string) ($itemVenda->servico?->nome ?? '')) === 'aso';
                                                                            $funcionarioNome = $itemVenda->venda?->tarefa?->funcionario?->nome;
                                                                            if ($isAso && $funcionarioNome) {
                                                                                $servicoNome = 'ASO - ' . $funcionarioNome;
                                                                            }
                                                                            $dataLinha = (($filtros['tipo_data'] ?? 'venda') === 'finalizacao')
                                                                                ? $itemVenda->venda?->tarefa?->finalizado_em
                                                                                : $itemVenda->venda?->created_at;
                                                                        @endphp
                                                                        <tr>
                                                                            <td class="px-3 py-2">
                                                                                <input type="checkbox"
                                                                                       name="itens[]"
                                                                                       value="{{ $itemVenda->id }}"
                                                                                       class="rounded border-slate-300 js-venda-item-checkbox {{ $canSelectVenda ? '' : 'opacity-60 cursor-not-allowed' }}"
                                                                                       data-parent-venda="{{ $expandId }}"
                                                                                       data-cliente-id="{{ (int) ($venda->cliente_id ?? 0) }}"
                                                                                       data-item-valor="{{ number_format((float) ($itemVenda->subtotal_snapshot ?? 0), 2, '.', '') }}"
                                                                                       @if(!$canSelectVenda) disabled title="{{ !$canCreate ? 'Usuário sem permissão' : 'Venda não finalizada não pode ser faturada.' }}" @endif>
                                                                            </td>
                                                                            <td class="px-3 py-2 text-slate-700">{{ $servicoNome }}</td>
                                                                            <td class="px-3 py-2 text-slate-700">{{ $itemVenda->descricao_snapshot ?? '—' }}</td>
                                                                            <td class="px-3 py-2 text-slate-700">{{ $dataLinha?->format('d/m/Y H:i') ?? '—' }}</td>
                                                                            <td class="px-3 py-2 text-right font-semibold text-slate-900">R$ {{ number_format((float) ($itemVenda->subtotal_snapshot ?? 0), 2, ',', '.') }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                @else
                                                                    @foreach($grupo['itens'] as $itemEstimado)
                                                                        <tr>
                                                                            <td class="px-3 py-2"></td>
                                                                            <td class="px-3 py-2 text-slate-700">{{ $tarefa->servico?->nome ?? 'Serviço' }}</td>
                                                                            <td class="px-3 py-2 text-slate-700">{{ $itemEstimado['descricao_snapshot'] ?? '—' }}</td>
                                                                            <td class="px-3 py-2 text-slate-700">{{ !empty($itemEstimado['data_referencia']) ? \Carbon\Carbon::parse($itemEstimado['data_referencia'])->format('d/m/Y H:i') : '—' }}</td>
                                                                            <td class="px-3 py-2 text-right font-semibold text-slate-900">R$ {{ number_format((float) ($itemEstimado['subtotal_snapshot'] ?? 0), 2, ',', '.') }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
                                                Nenhum registro encontrado com os filtros atuais.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($mostrarVendasSemFatura)
                    <footer class="border-t border-slate-100 bg-white px-5 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-white px-4 py-3 shadow-sm">
                            <p class="text-[11px] uppercase tracking-wide text-indigo-700 font-semibold">Resumo da seleção</p>
                            <div class="mt-1 flex flex-wrap items-center gap-3">
                                <span class="inline-flex items-center gap-2 rounded-xl bg-white border border-indigo-100 px-3 py-1.5 text-sm text-slate-700">
                                    <span id="crResumoVendasBadge" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[11px] font-bold text-white">0</span>
                                    vendas selecionadas
                                </span>
                                <span class="text-sm text-slate-500">|</span>
                                <span class="text-sm text-slate-700">Itens:</span>
                                <span id="crResumoItens" class="text-sm font-semibold text-slate-900">0</span>
                                <span class="text-sm text-slate-500">|</span>
                                <span class="text-sm text-slate-700">Total selecionado:</span>
                                <span id="crResumoTotal" class="text-lg font-semibold text-indigo-700">R$ 0,00</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    id="crAbrirModalRelatorioVendas"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 shadow-sm">
                                <i data-lucide="printer" class="h-4 w-4"></i>
                                Imprimir Vendas
                            </button>
                            <button type="submit"
                                    id="crBtnCriarFatura"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm {{ $canCreate ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white hover:from-indigo-700 hover:to-violet-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                    @if(!$canCreate) disabled title="Usuário sem permissão" @else title="Selecione ao menos um item para criar a fatura." @endif>
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-[12px]">+</span>
                                <span id="crBtnCriarFaturaTexto">Criar Fatura</span>
                            </button>
                        </div>
                    </footer>
                    </footer>
                    @endif
                </form>
            </section>
        </section>

        <section data-cr-tab="faturas" class="space-y-4 {{ $abaAtiva === 'faturas' ? '' : 'hidden' }}">
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Faturas</h2>
                        <p class="text-xs text-slate-500">Filtros fixos no topo, tabela com rolagem interna e ações por linha</p>
                    </div>
                </header>

		                <div class="m-4 flex flex-col">


                    <form method="GET" class="px-4 md:px-5 pt-4 pb-3 border-b border-indigo-100 bg-white/70">
                        <input type="hidden" name="aba" value="faturas">
                        @if($contaDetalheSelecionada)
                            <input type="hidden" name="fatura_id" value="{{ $contaDetalheSelecionada->id }}">
                        @endif
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12 items-end">
                            <div class="xl:col-span-2">
                                <label class="text-xs font-semibold text-slate-600">Nº da fatura</label>
                                <input type="text"
                                       name="faturas_numero"
                                       value="{{ $filtrosFaturas['numero'] ?? '' }}"
                                       placeholder="Ex.: 123"
                                       class="w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2 h-[42px]">
                            </div>

                            <div class="xl:col-span-3">
                                <label class="text-xs font-semibold text-slate-600">Cliente</label>
                                <input type="hidden" name="faturas_cliente_id" id="cr-faturas-cliente-id" value="{{ $filtrosFaturas['cliente_id'] ?? '' }}">
                                <div class="relative">
                                    <input type="text"
                                           name="faturas_cliente"
                                           id="cr-faturas-cliente-autocomplete-input"
                                           autocomplete="off"
                                           value="{{ $filtrosFaturas['cliente'] ?? '' }}"
                                           placeholder="Todos os clientes"
                                           class="w-full rounded-xl border border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 text-sm px-3 py-2 h-[42px]">
                                    <div id="cr-faturas-cliente-autocomplete-list"
                                         class="absolute z-20 mt-1 w-full max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg hidden"></div>
                                </div>
                            </div>

                            <div class="xl:col-span-2">
                                <label class="text-xs font-semibold text-slate-600">Status</label>
                                <select name="faturas_status"
                                        class="w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2 h-[42px]">
                                    <option value="">Todos</option>
                                    <option value="aberta" @selected(($filtrosFaturas['status'] ?? '') === 'aberta')>Aberta</option>
                                    <option value="com_baixa" @selected(($filtrosFaturas['status'] ?? '') === 'com_baixa')>Com baixa</option>
                                    <option value="parcial" @selected(($filtrosFaturas['status'] ?? '') === 'parcial')>Parcial</option>
                                    <option value="baixada" @selected(($filtrosFaturas['status'] ?? '') === 'baixada')>Baixada</option>
                                    <option value="cancelada" @selected(($filtrosFaturas['status'] ?? '') === 'cancelada')>Cancelada</option>
                                </select>
                            </div>

                            <div class="xl:col-span-2">
                                <label class="text-xs font-semibold text-slate-600">Filtrar período por</label>
                                <select name="faturas_tipo_periodo"
                                        class="w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2 h-[42px]">
                                    <option value="vencimento" @selected(($filtrosFaturas['tipo_periodo'] ?? 'vencimento') === 'vencimento')>Vencimento</option>
                                    <option value="emissao" @selected(($filtrosFaturas['tipo_periodo'] ?? '') === 'emissao')>Emissão</option>
                                    <option value="ambas" @selected(($filtrosFaturas['tipo_periodo'] ?? '') === 'ambas')>Emissão ou vencimento</option>
                                </select>
                            </div>

                            <div class="xl:col-span-1">
                                <label class="text-xs font-semibold text-slate-600">Data inicial</label>
                                <input type="date" name="faturas_data_inicio" value="{{ $filtrosFaturas['data_inicio'] ?? '' }}"
                                       class="w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2 h-[42px]">
                            </div>

                            <div class="xl:col-span-2">
                                <label class="text-xs font-semibold text-slate-600">Data final</label>
                                <input type="date" name="faturas_data_fim" value="{{ $filtrosFaturas['data_fim'] ?? '' }}"
                                       class="w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2 h-[42px]">
                            </div>

                            <div class="md:col-span-2 xl:col-span-12 flex items-center justify-between gap-3 pt-1">
                                <p class="text-xs text-slate-500">
                                    Filtre por cliente, status, número ou período de emissão/vencimento.
                                </p>
                                <div class="flex items-center gap-2">
                                    <button class="inline-flex h-[40px] items-center justify-center px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 text-white text-sm font-semibold shadow-sm hover:from-indigo-700 hover:to-indigo-600">
                                        Filtrar
                                    </button>
                                    <a href="{{ route('financeiro.contas-receber', ['aba' => 'faturas']) }}"
                                       class="inline-flex h-[40px] items-center justify-center px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                        Limpar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    @php
                        $totaisFaturas = $totaisFaturas ?? ['faturas_com_baixa_registrada' => 0, 'valor_em_aberto' => 0];
                    @endphp
                    <div class="px-4 md:px-5 pt-4">
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-wide font-semibold text-amber-700">Faturas em aberto</p>
                                <p class="mt-1 text-2xl font-semibold text-amber-900">
                                    R$ {{ number_format((float) ($totaisFaturas['valor_em_aberto'] ?? 0), 2, ',', '.') }}
                                </p>
                                <p class="mt-1 text-xs text-amber-700/80">Saldo em aberto dentro do filtro atual</p>
                            </div>

                            <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-wide font-semibold text-indigo-700">Faturas com baixas registradas</p>
                                <p class="mt-1 text-2xl font-semibold text-indigo-900">
                                    R$ {{ number_format((float) ($totaisFaturas['faturas_com_baixa_registrada'] ?? 0), 2, ',', '.') }}
                                </p>
                                <p class="mt-1 text-xs text-indigo-700/80">Somatório das faturas do filtro que possuem ao menos uma baixa</p>
                            </div>
                        </div>
                    </div>

		                    <div class="mt-4">
		                        <div class="max-h-[38rem] overflow-auto rounded-lg border border-slate-200 bg-white">
	                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead class="bg-slate-50 text-slate-600 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Nº Fatura</th>
                                            <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                                            <th class="px-4 py-3 text-left font-semibold">Emissão</th>
                                            <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                                            <th class="px-4 py-3 text-right font-semibold">Valor</th>
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
                                                    $hasBaixa = $pago > 0.0001;
                                                    $detalheUrlLinha = route('financeiro.contas-receber', array_merge(request()->query(), [
                                                        'aba' => 'detalhe',
                                                        'fatura_id' => $conta->id,
                                                    ]));
                                                    $telefoneWhatsappLinha = preg_replace('/\D+/', '', (string) ($conta->cliente->telefone ?? ''));
                                                    if (in_array(strlen($telefoneWhatsappLinha), [10, 11], true)) {
                                                        $telefoneWhatsappLinha = '55' . $telefoneWhatsappLinha;
                                                    }
                                                    $whatsUrlLinha = $telefoneWhatsappLinha !== '' ? route('financeiro.contas-receber.whatsapp', $conta) : null;
                                                $uiStatus = match (true) {
                                                    strtoupper((string) $conta->status) === 'CANCELADO' => 'Cancelada',
                                                    $saldo <= 0.0001 && $total > 0 => 'Baixada',
                                                    $hasBaixa => 'Parcial',
                                                    default => 'Aberta',
                                                };
                                                $podeEmailLinha = $canUpdate;
                                                $statusRawLinha = strtoupper((string) ($conta->status ?? ''));
                                                $isCanceladaLinha = $statusRawLinha === 'CANCELADO';
                                                $podeRegistrarBaixaLinha = !$isCanceladaLinha && $uiStatus !== 'Baixada' && $canUpdate;
                                                $podeExcluirBaixaLinha = $hasBaixa && $canUpdate;
                                                $podeExcluirFaturaLinha = !$hasBaixa && $canDelete;
                                                $badge = match ($uiStatus) {
                                                    'Baixada' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                    'Parcial' => 'bg-sky-50 text-sky-700 border-sky-100',
                                                    'Aberta' => 'bg-amber-50 text-amber-700 border-amber-100',
                                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                                };
                                            @endphp
                                            <tr class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50/30">
                                                <td class="px-4 py-3 text-slate-800 font-semibold">#{{ $conta->id }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $conta->cliente->razao_social ?? $conta->cliente->nome_fantasia ?? 'Cliente' }}</td>
                                                <td class="px-4 py-3 text-slate-600">{{ optional($conta->created_at)->format('d/m/Y') ?? '—' }}</td>
                                                <td class="px-4 py-3 text-slate-600">{{ optional($conta->vencimento)->format('d/m/Y') ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($total, 2, ',', '.') }}</td>
                                                <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($pago, 2, ',', '.') }}</td>
                                                <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($saldo, 2, ',', '.') }}</td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $badge }}">{{ $uiStatus }}</span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                                        <a href="{{ $detalheUrlLinha }}"
                                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700"
                                                           title="Ver detalhe"
                                                           aria-label="Ver detalhe">
                                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                                        </a>

                                                        <a href="{{ route('financeiro.contas-receber.impressao', $conta) }}"
                                                           target="_blank"
                                                           rel="noopener"
                                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50"
                                                           title="Imprimir fatura"
                                                           aria-label="Imprimir fatura">
                                                            <i data-lucide="printer" class="h-4 w-4"></i>
                                                        </a>

                                                        @php
                                                            $podeWhatsappLinha = (bool) $whatsUrlLinha;
                                                            $paramEmailClienteLinha = trim((string) ($parametroClienteEmailPorCliente[$conta->cliente_id] ?? ''));
                                                            $emailFinanceiroLinha = trim((string) ($conta->empresa?->email ?? ''));
                                                            $emailClienteLinha = trim((string) ($conta->cliente->email ?? ''));
                                                            $emailDestinoPadraoLinha = $paramEmailClienteLinha ?: $emailFinanceiroLinha ?: $emailClienteLinha;
                                                            $podeEmailLinha = $canUpdate && $emailDestinoPadraoLinha !== '';
                                                        @endphp
                                                                <button type="button"
                                                            @if($podeWhatsappLinha) data-whatsapp-route="{{ $whatsUrlLinha }}" @endif
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold {{ $podeWhatsappLinha ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                                            title="{{ $podeWhatsappLinha ? 'Enviar fatura via WhatsApp' : 'Telefone 1 do cliente não informado.' }}"
                                                            aria-label="WhatsApp"
                                                            @if(!$podeWhatsappLinha) disabled @endif>
                                                           <i class="fa-brands fa-whatsapp text-sm"></i>
                                                       </button>

                                                        <button type="button"
                                                            @if($podeEmailLinha)
                                                                data-financeiro-email-route="{{ route('financeiro.contas-receber.enviar-email', $conta) }}"
                                                                data-financeiro-email-destino="{{ $emailDestinoPadraoLinha }}"
                                                            @endif
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold {{ $podeEmailLinha ? 'bg-sky-600 text-white hover:bg-sky-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed pointer-events-none' }}"
                                                            title="{{ $podeEmailLinha ? 'Enviar fatura por e-mail' : 'Destino de e-mail não configurado ou permissão negada' }}"
                                                            aria-label="Enviar por e-mail"
                                                            @if(!$podeEmailLinha) disabled @endif>
                                                            <i data-lucide="mail" class="h-4 w-4"></i>
                                                        </button>

                                                        <a href="{{ $detalheUrlLinha }}#cr-open-modal-baixa"
                                                           data-cr-open-modal-baixa-link
                                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold {{ $podeRegistrarBaixaLinha ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed pointer-events-none' }}"
                                                           title="{{ $podeRegistrarBaixaLinha ? 'Registrar baixa' : (!$canUpdate ? 'Usuário sem permissão' : 'Registrar baixa disponível apenas para faturas abertas ou parciais.') }}"
                                                           aria-label="Registrar baixa">
                                                            <i data-lucide="banknote" class="h-4 w-4"></i>
                                                        </a>

                                                        <form method="POST" action="{{ route('financeiro.contas-receber.excluir-baixa', $conta) }}" class="m-0 js-form-excluir-baixa">
                                                            @csrf
                                                            <button class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold {{ $podeExcluirBaixaLinha ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                                                    @if(!$podeExcluirBaixaLinha) disabled title="{{ !$hasBaixa ? 'Fatura sem baixa vinculada.' : 'Usuário sem permissão' }}" @else title="Excluir baixa" @endif
                                                                    aria-label="Excluir baixa">
                                                                <i data-lucide="undo-2" class="h-4 w-4"></i>
                                                            </button>
                                                        </form>

                                                        <form method="POST" action="{{ route('financeiro.contas-receber.destroy', $conta) }}" class="m-0 js-form-remover-fatura">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold {{ $podeExcluirFaturaLinha ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                                                    @if(!$podeExcluirFaturaLinha) disabled title="{{ $hasBaixa ? 'Para excluir a fatura, primeiro exclua a baixa.' : 'Usuário sem permissão' }}" @else title="Excluir fatura" @endif
                                                                    aria-label="Excluir fatura">
                                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">Nenhuma fatura encontrada.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
	                        </div>
	                    </div>

                    <footer class="border-t border-slate-100 bg-white px-5 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <p class="text-sm text-slate-600">
                            Total de faturas nesta página: <strong>{{ $contas->count() }}</strong>
                            <span class="text-slate-400">•</span>
                            Página {{ $contas->currentPage() }} de {{ max(1, $contas->lastPage()) }}
                        </p>
                        <div>{{ $contas->links() }}</div>
                    </footer>
                </div>
            </section>
        </section>

        <section data-cr-tab="detalhe" class="space-y-4 {{ $abaAtiva === 'detalhe' ? '' : 'hidden' }}">
            @if($contaDetalheSelecionada)
                <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-slate-800">1) Cabeçalho da fatura</h2>

                    <div class="mt-4 grid gap-3 md:grid-cols-12">
                        <div class="md:col-span-7 rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-white p-5 shadow-sm">
                            <p class="text-xs uppercase tracking-wide font-semibold text-indigo-700">Cliente</p>
                            @php
                                $clienteNomeDetalhe = $contaDetalheSelecionada->cliente->razao_social
                                    ?? $contaDetalheSelecionada->cliente->nome_fantasia
                                    ?? 'Cliente';
                            @endphp
                            @if(!empty($contaDetalheSelecionada->cliente_id))
                                <a target="_blank" href="{{ route('clientes.edit', $contaDetalheSelecionada->cliente_id) }}"
                                   class="group mt-1 inline-flex items-center gap-2 text-2xl md:text-3xl font-semibold text-slate-900 leading-tight hover:text-indigo-700 transition-colors"
                                   title="Abrir cadastro do cliente">
                                    <span>{{ $clienteNomeDetalhe }}</span>
                                    <span class="inline-flex items-center text-sm md:text-base text-indigo-500 opacity-80 group-hover:opacity-100">↗</span>
                                </a>
                            @else
                                <p class="mt-1 text-2xl md:text-3xl font-semibold text-slate-900 leading-tight">
                                    {{ $clienteNomeDetalhe }}
                                </p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                @if(!empty($contaDetalheSelecionada->cliente->documento_principal))
                                    <span class="text-slate-600">{{ $contaDetalheSelecionada->cliente->documento_label }} <strong class="text-slate-800">{{ $contaDetalheSelecionada->cliente->documento_principal }}</strong></span>
                                    <span class="text-slate-300">•</span>
                                @endif
                                <span class="text-slate-600">Emissão <strong class="text-slate-800">{{ optional($contaDetalheSelecionada->created_at)->format('d/m/Y') ?? '—' }}</strong></span>
                                <span class="text-slate-300">•</span>
                                <span class="text-slate-600">Vencimento <strong class="text-slate-800">{{ optional($contaDetalheSelecionada->vencimento)->format('d/m/Y') ?? '—' }}</strong></span>
                            </div>
                        </div>
                        <div class="md:col-span-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs uppercase tracking-wide font-semibold text-slate-500">Fatura</p>
                            <div class="mt-1 flex items-center justify-between gap-3">
                                <p class="text-2xl font-semibold text-slate-900">#{{ $contaDetalheSelecionada->id }}</p>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $detalheStatusBadge }}">
                                    {{ $detalheStatus }}
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
                                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Total</p>
                                    <p class="font-semibold text-slate-900">R$ {{ number_format($detalheTotal, 2, ',', '.') }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Pago</p>
                                    <p class="font-semibold text-slate-900">R$ {{ number_format($detalhePago, 2, ',', '.') }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Saldo</p>
                                    <p class="font-semibold text-indigo-700">R$ {{ number_format($detalheSaldo, 2, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                        <form method="POST"
                              action="{{ route('financeiro.contas-receber.update-datas', $contaDetalheSelecionada) }}"
                              class="grid gap-3 md:grid-cols-12 md:items-end">
                            @csrf
                            @method('PUT')
                            <div class="md:col-span-4">
                                <div class="rounded-xl border border-slate-200 bg-white px-3 py-3 h-full">
                                    <p class="text-[11px] uppercase tracking-wide font-semibold text-slate-500">Datas da fatura</p>
                                    <p class="mt-1 text-xs text-slate-600">
                                        Altere emissão e vencimento. A atualização é enviada automaticamente ao mudar o campo.
                                    </p>
                                </div>
                            </div>
                            <div class="md:col-span-8 grid gap-3 sm:grid-cols-2">
                                <label class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                                    <span class="text-xs font-semibold text-slate-600">Data de emissão</span>
                                    <input type="date"
                                           name="emissao"
                                           value="{{ optional($contaDetalheSelecionada->created_at)->format('Y-m-d') }}"
                                           onchange="this.form.requestSubmit()"
                                           class="mt-2 w-full rounded-xl border border-slate-200 bg-white text-slate-800 text-sm px-3 py-2 h-[42px]">
                                </label>
                                <label class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                                    <span class="text-xs font-semibold text-slate-600">Data de vencimento</span>
                                    <input type="date"
                                           name="vencimento"
                                           id="crDetalheVencimentoInput"
                                           value="{{ optional($contaDetalheSelecionada->vencimento)->format('Y-m-d') }}"
                                           onchange="this.form.requestSubmit()"
                                           class="mt-2 w-full rounded-xl border border-slate-200 bg-white text-slate-800 text-sm px-3 py-2 h-[42px]">
                                    @if(!$detalheVencimentoSugerido)
                                        <span class="mt-2 block text-[11px] text-slate-500">
                                            Dica: configure o vencimento padrão nos parâmetros do cliente para sugerir esta data automaticamente na criação da fatura.
                                        </span>
                                    @endif
                                </label>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 flex flex-col">
                    <h2 class="text-sm font-semibold text-slate-800">2) Itens da fatura (com data)</h2>
                    <p class="text-xs text-slate-500 mt-1">Itens com data de realização em container fixo com rolagem interna</p>

                    <div class="mt-4 flex flex-col rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-inner">
                        <div class="px-4 py-3 border-b border-indigo-200 bg-indigo-100/60 rounded-t-2xl">
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Container fixo de itens da fatura</p>
                            <p class="text-xs text-indigo-600 mt-1">Rolagem interna para leitura detalhada</p>
                        </div>
                        <div class="p-4 md:p-5">
                            <div class="rounded-xl border border-indigo-200/80 bg-white/95 p-3 md:p-4 shadow-sm">
                                <div class="max-h-[36rem] overflow-auto rounded-lg border border-slate-200">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50 text-slate-600 sticky top-0 z-10">
                                            <tr>
                                                <th class="px-4 py-3 text-left font-semibold">Data</th>
                                                <th class="px-4 py-3 text-left font-semibold">Venda</th>
                                                <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                                <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                                                <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                                <th class="px-4 py-3 text-right font-semibold">Valor</th>
                                                <th class="px-4 py-3 text-right font-semibold">Baixado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse($detalheItensComData as $item)
                                                @php
                                                    $itemStatus = strtoupper((string) $item->status);
                                                    $itemBadge = match(true) {
                                                        $itemStatus === 'BAIXADO' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                        $item->vencido => 'bg-rose-50 text-rose-700 border-rose-100',
                                                        $itemStatus === 'CANCELADO' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                        default => 'bg-amber-50 text-amber-700 border-amber-100',
                                                    };
                                                    $itemLabel = $item->vencido ? 'Vencido' : ucfirst(strtolower($itemStatus));
                                                    $servicoNome = $item->servico?->nome ?? $item->descricao ?? $item->vendaItem?->descricao_snapshot ?? 'Serviço';
                                                    $baixadoItem = (float) $item->total_baixado;
                                                @endphp
                                                <tr class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50/40">
                                                    <td class="px-4 py-3 text-slate-700">{{ optional($item->data_realizacao)->format('d/m/Y') ?? '—' }}</td>
                                                    <td class="px-4 py-3 text-slate-800">{{ $item->venda_id ? '#'.$item->venda_id : 'Avulso' }}</td>
                                                    <td class="px-4 py-3 text-slate-800">{{ $servicoNome }}</td>
                                                    <td class="px-4 py-3 text-slate-700">{{ $item->descricao ?? $item->vendaItem?->descricao_snapshot ?? '—' }}</td>
                                                    <td class="px-4 py-3 text-slate-700">{{ optional($item->vencimento)->format('d/m/Y') ?? '—' }}</td>
                                                    <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold border {{ $itemBadge }}">{{ $itemLabel }}</span></td>
                                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format((float) $item->valor, 2, ',', '.') }}</td>
                                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($baixadoItem, 2, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum item com data de realização nesta fatura.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <footer id="cr-acoes-financeiras" class="shrink-0 border-t border-indigo-200 bg-white px-4 py-3 rounded-b-2xl">
                        @php
                            $telefoneWhatsapp = preg_replace('/\D+/', '', (string) ($contaDetalheSelecionada->cliente->telefone ?? ''));
                            if (in_array(strlen($telefoneWhatsapp), [10, 11], true)) {
                                $telefoneWhatsapp = '55' . $telefoneWhatsapp;
                            }
                            $whatsUrl = $telefoneWhatsapp !== '' ? route('financeiro.contas-receber.whatsapp', $contaDetalheSelecionada) : null;
                            $emailOpcoesFatura = collect($contaDetalheEmailOpcoes ?? []);
                        @endphp
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div class="rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-white px-4 py-3 shadow-sm">
                                <p class="text-[11px] uppercase tracking-wide text-indigo-700 font-semibold">Ações da fatura</p>
                                <div class="mt-1 flex flex-wrap items-center gap-3 text-sm">
                                    <span class="inline-flex items-center gap-2 rounded-xl bg-white border border-indigo-100 px-3 py-1.5 text-slate-700">
                                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[11px] font-bold text-white">#</span>
                                        fatura #{{ $contaDetalheSelecionada->id }}
                                    </span>
                                    <span class="text-slate-500">|</span>
                                    <span class="text-slate-700">Status:</span>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $detalheStatusBadge }}">
                                        {{ $detalheStatus }}
                                    </span>
                                    <span class="text-slate-500">|</span>
                                    <span class="text-slate-700">Saldo:</span>
                                    <span class="text-base font-semibold text-indigo-700">R$ {{ number_format($detalheSaldo, 2, ',', '.') }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('financeiro.contas-receber.impressao', $contaDetalheSelecionada) }}"
                                   target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 shadow-sm">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100">
                                        <i data-lucide="printer" class="h-3.5 w-3.5 text-slate-700"></i>
                                    </span>
                                    Imprimir
                                </a>

                                @php $podeWhatsappDetalhe = (bool) $whatsUrl; @endphp
                                <button type="button"
                                        @if($podeWhatsappDetalhe) data-whatsapp-route="{{ $whatsUrl }}" @endif
                                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm {{ $podeWhatsappDetalhe ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white hover:from-emerald-600 hover:to-emerald-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                        aria-label="Enviar fatura via WhatsApp"
                                        title="{{ $podeWhatsappDetalhe ? 'Enviar fatura via WhatsApp' : 'Telefone 1 do cliente não informado.' }}"
                                        @if(!$podeWhatsappDetalhe) disabled @endif>
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20">
                                        <i class="fa-brands fa-whatsapp text-xs"></i>
                                    </span>
                                    WhatsApp
                                </button>

                                <button type="button"
                                        id="crAbrirModalEmailFatura"
                                        data-email-options='@json($emailOpcoesFatura ?? [])'
                                        data-email-route="{{ route('financeiro.contas-receber.enviar-email', $contaDetalheSelecionada) }}"
                                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm {{ ($canUpdate && $emailOpcoesFatura->isNotEmpty()) ? 'bg-gradient-to-r from-sky-600 to-cyan-600 text-white hover:from-sky-700 hover:to-cyan-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                        @if(!$canUpdate || $emailOpcoesFatura->isEmpty()) disabled title="{{ !$canUpdate ? 'Usuário sem permissão' : 'Nenhum e-mail disponível (financeiro/cliente).' }}" @endif>
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20">
                                        <i data-lucide="mail" class="h-3.5 w-3.5"></i>
                                    </span>
                                    Enviar por e-mail
                                </button>



                                <form method="POST" action="{{ route('financeiro.contas-receber.destroy', $contaDetalheSelecionada) }}" class="m-0 js-form-remover-fatura">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm {{ (!$detalheHasBaixa && $canDelete) ? 'bg-gradient-to-r from-rose-600 to-rose-500 text-white hover:from-rose-700 hover:to-rose-600' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                            @if($detalheHasBaixa || !$canDelete) disabled title="{{ $detalheHasBaixa ? 'Remova/exclua a baixa antes de excluir a fatura.' : 'Usuário sem permissão' }}" @endif>
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        </span>
                                        Excluir fatura
                                    </button>
                                </form>

                                <button type="button"
                                        id="crAbrirModalBaixaDetalhe"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm {{ ($canUpdate && !$detalheIsBaixada) ? 'bg-gradient-to-r from-emerald-600 to-green-600 text-white hover:from-emerald-700 hover:to-green-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                        @if(!$canUpdate || $detalheIsBaixada) disabled title="{{ !$canUpdate ? 'Usuário sem permissão' : 'Fatura baixada não permite registrar baixa.' }}" @endif>
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20">
                                        <i data-lucide="banknote" class="h-3.5 w-3.5"></i>
                                    </span>
                                    Registrar baixa
                                </button>

                                @if($detalheHasBaixa)
                                    <form method="POST" action="{{ route('financeiro.contas-receber.excluir-baixa', $contaDetalheSelecionada) }}" class="m-0 js-form-excluir-baixa">
                                        @csrf
                                        <button class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm {{ $canUpdate ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                                @if(!$canUpdate) disabled title="Usuário sem permissão" @endif>
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20">
                                                <i data-lucide="undo-2" class="h-3.5 w-3.5"></i>
                                            </span>
                                            Excluir baixa
                                        </button>
                                    </form>
                                @endif

{{--                                <form method="POST" action="{{ route('financeiro.contas-receber.boleto', $contaDetalheSelecionada) }}" class="m-0">--}}
{{--                                    @csrf--}}
{{--                                    <button class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm {{ $canUpdate ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white hover:from-indigo-700 hover:to-violet-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"--}}
{{--                                            @if(!$canUpdate) disabled title="Usuário sem permissão" @endif>--}}
{{--                                        Emitir boleto--}}
{{--                                    </button>--}}
{{--                                </form>--}}
                            </div>
                        </div>
                    </footer>
                    </div>
                </section>

                @php
                    $baixasDetalhe = $contaDetalheSelecionada->baixas
                        ->sortByDesc(function ($baixa) {
                            return optional($baixa->pago_em)->timestamp ?? optional($baixa->created_at)->timestamp ?? 0;
                        })
                        ->values();
                    $itensFaturaPorId = $contaDetalheSelecionada->itens->keyBy('id');
                    $totalBaixasDetalhe = (float) $baixasDetalhe->sum(fn ($b) => (float) ($b->valor ?? 0));
                    $temMuitasBaixas = $baixasDetalhe->count() > 4;
                @endphp

                <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800">3) Histórico de Baixas</h2>
                            <p class="text-xs text-slate-500 mt-1">Registros de recebimento desta fatura, com comprovante e observações.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $detalheHasBaixa ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                {{ $baixasDetalhe->count() }} {{ $baixasDetalhe->count() === 1 ? 'baixa' : 'baixas' }}
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-700">
                                Total baixado <strong class="text-slate-900">R$ {{ number_format($totalBaixasDetalhe, 2, ',', '.') }}</strong>
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-xs text-indigo-700">
                                Saldo <strong class="text-indigo-800">R$ {{ number_format($detalheSaldo, 2, ',', '.') }}</strong>
                            </span>
                        </div>
                    </div>

                    @if($baixasDetalhe->isEmpty())
                        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center">
                            <p class="text-sm font-semibold text-slate-700">Nenhuma baixa registrada para esta fatura.</p>
                            <p class="mt-1 text-xs text-slate-500">Use o botão abaixo para registrar o primeiro recebimento.</p>
                            <div class="mt-4">
                                <button type="button"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm {{ $canUpdate ? 'bg-gradient-to-r from-emerald-600 to-green-600 text-white hover:from-emerald-700 hover:to-green-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                        @if(!$canUpdate || $detalheIsBaixada) disabled title="{{ !$canUpdate ? 'Usuário sem permissão' : 'Fatura baixada não permite registrar baixa.' }}" @else onclick="document.getElementById('crAbrirModalBaixaDetalhe')?.click()" @endif>
                                    Registrar baixa
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <p class="text-xs text-slate-600">
                                    Mais recente primeiro. Comprovante e observação visíveis na mesma linha.
                                </p>
                                <button type="button"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold shadow-sm {{ $canUpdate ? 'bg-gradient-to-r from-emerald-600 to-green-600 text-white hover:from-emerald-700 hover:to-green-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                        @if(!$canUpdate || $detalheIsBaixada) disabled title="{{ !$canUpdate ? 'Usuário sem permissão' : 'Fatura baixada não permite registrar baixa.' }}" @else onclick="document.getElementById('crAbrirModalBaixaDetalhe')?.click()" @endif>
                                    Registrar nova baixa
                                </button>
                            </div>

                            <div class="{{ $temMuitasBaixas ? 'max-h-[20rem] overflow-y-auto' : '' }}">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead class="bg-white sticky top-0 z-10">
                                        <tr class="text-slate-500">
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold">Data</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold">Valor</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold">Meio</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold">Referência</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold">Obs.</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-semibold">Comprovante</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($baixasDetalhe as $baixa)
                                            @php
                                                $itemBaixa = $itensFaturaPorId->get($baixa->conta_receber_item_id);
                                                $itemBaixaServico = $itemBaixa
                                                    ? ($itemBaixa->servico?->nome ?? $itemBaixa->descricao ?? $itemBaixa->vendaItem?->descricao_snapshot ?? 'Item')
                                                    : 'Baixa geral';
                                                $meioBaixa = trim((string) ($baixa->meio_pagamento ?? 'Não informado'));
                                                $baixaComprovanteUrl = !empty($baixa->comprovante_path) ? \App\Helpers\S3Helper::temporaryUrl($baixa->comprovante_path, 10) : null;
                                            @endphp
                                            <tr class="odd:bg-white even:bg-slate-50/40 hover:bg-emerald-50/30">
                                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                                    {{ optional($baixa->pago_em)->format('d/m/Y') ?? optional($baixa->created_at)->format('d/m/Y') ?? '—' }}
                                                </td>
                                                <td class="px-4 py-3 font-semibold text-emerald-700 whitespace-nowrap">
                                                    R$ {{ number_format((float) $baixa->valor, 2, ',', '.') }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold border border-slate-200 bg-white text-slate-700">
                                                        {{ $meioBaixa }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    <div class="text-xs font-semibold text-slate-800">Baixa #{{ $baixa->id }}</div>
                                                    <div class="text-xs text-slate-500 truncate" title="{{ $itemBaixaServico }}">{{ $itemBaixaServico }}</div>
                                                </td>
                                                <td class="px-4 py-3 text-xs text-slate-600 max-w-[18rem]">
                                                    <div class="truncate" title="{{ $baixa->observacao ?: 'Sem observação' }}">
                                                        {{ $baixa->observacao ?: 'Sem observação' }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    @if($baixaComprovanteUrl)
                                                        <a href="{{ $baixaComprovanteUrl }}"
                                                           target="_blank"
                                                           rel="noopener"
                                                           class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">
                                                            Ver
                                                        </a>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400">
                                                            —
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </section>
            @else
                <section class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-sm font-semibold text-amber-800">Detalhe da fatura indisponível</p>
                    <p class="text-xs text-amber-700 mt-1">Abra a aba <strong>Faturas</strong> e clique em <strong>Ver</strong>.</p>
                </section>
            @endif
        </section>
    </div>

    <div id="crModalRelatorioVendas" class="fixed inset-0 z-[92] hidden overflow-y-auto">
        <div class="absolute inset-0 bg-slate-900/50" data-cr-fechar-modal-relatorio-vendas></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-5 space-y-4 border border-slate-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Gerar impresso de vendas</h3>
                    <button type="button" data-cr-fechar-modal-relatorio-vendas class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <p class="text-xs text-slate-600">
                    O relatório será gerado com os mesmos filtros aplicados na aba <strong>Vendas</strong>.
                </p>

                <form method="GET"
                      action="{{ route('financeiro.contas-receber.relatorio-vendas-impressao') }}"
                      target="_blank"
                      class="space-y-4">
                    <input type="hidden" name="tipo_data" value="{{ $filtros['tipo_data'] ?? 'venda' }}">
                    <input type="hidden" name="status_finalizacao" value="{{ $filtros['status_finalizacao'] ?? 'todas' }}">
                    <input type="hidden" name="data_inicio" value="{{ $filtros['data_inicio'] ?? '' }}">
                    <input type="hidden" name="data_fim" value="{{ $filtros['data_fim'] ?? '' }}">
                    <input type="hidden" name="cliente_id" value="{{ $filtros['cliente_id'] ?? '' }}">
                    <input type="hidden" name="cliente" value="{{ $filtros['cliente'] ?? '' }}">

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold text-slate-700">Como deseja agrupar?</p>
                        <div class="mt-2 space-y-2">
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="radio" name="agrupamento" value="servico" checked class="mt-0.5">
                                <span>
                                    <strong>Por serviço</strong><br>
                                    Cabeçalho por tipo de serviço e itens logo abaixo.
                                </span>
                            </label>
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="radio" name="agrupamento" value="cliente" class="mt-0.5">
                                <span>
                                    <strong>Por cliente</strong><br>
                                    Cabeçalho por cliente e itens de serviço dentro do grupo.
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button"
                                data-cr-fechar-modal-relatorio-vendas
                                class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 text-white text-sm font-semibold hover:from-indigo-700 hover:to-indigo-600">
                            <i data-lucide="printer" class="h-4 w-4"></i>
                            Gerar impresso
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($contaDetalheSelecionada)
        <div id="crModalEmailFatura" class="fixed inset-0 z-[91] hidden overflow-y-auto">
            <div class="absolute inset-0 bg-slate-900/50" data-cr-fechar-modal-email-fatura></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                    <div id="crEmailFaturaLoading" class="absolute inset-0 z-10 hidden rounded-2xl bg-white/75 backdrop-blur-[1px]">
                        <div class="flex h-full flex-col items-center justify-center gap-3 text-slate-700">
                            <svg class="h-8 w-8 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <p class="text-sm font-semibold">Enviando fatura...</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Enviar fatura por e-mail</h3>
                        <button type="button" data-cr-fechar-modal-email-fatura class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form id="crFormEnviarEmailFatura" method="POST" action="{{ route('financeiro.contas-receber.enviar-email', $contaDetalheSelecionada) }}" class="space-y-3">
                        @csrf
                        @php
                            $emailDestinoPadrao = collect($contaDetalheEmailOpcoes ?? [])
                                ->firstWhere('tipo', 'cliente_fatura')['value']
                                ?? (collect($contaDetalheEmailOpcoes ?? [])->firstWhere('tipo', 'financeiro')['value'] ?? '');
                        @endphp
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Destino</label>
                            <select name="email_destino" class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required>
                                @foreach(collect($contaDetalheEmailOpcoes ?? []) as $op)
                                    <option value="{{ $op['value'] }}" @selected(($op['value'] ?? '') === $emailDestinoPadrao)>{{ $op['label'] }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">O envio será realizado com a fatura em PDF anexada.</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                            <div>Fatura <strong class="text-slate-800">#{{ $contaDetalheSelecionada->id }}</strong></div>
                            <div class="mt-1">Emissão {{ optional($contaDetalheSelecionada->created_at)->format('d/m/Y') ?? '—' }} · Vencimento {{ optional($contaDetalheSelecionada->vencimento)->format('d/m/Y') ?? '—' }}</div>
                        </div>

                        <button id="crBtnEnviarEmailFatura" class="w-full px-4 py-2.5 rounded-xl text-sm font-semibold {{ $canUpdate ? 'bg-sky-600 text-white hover:bg-sky-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                @if(!$canUpdate) disabled title="Usuário sem permissão" @endif>
                            <span class="inline-flex items-center justify-center gap-2">
                                <svg id="crBtnEnviarEmailFaturaSpinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span id="crBtnEnviarEmailFaturaLabel">Enviar fatura</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div id="crModalBaixaDetalhe" class="fixed inset-0 z-[90] hidden overflow-y-auto">
            <div class="absolute inset-0 bg-slate-900/50" data-cr-fechar-modal-baixa></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl border border-slate-200 overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-emerald-50 via-white to-emerald-50/50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide font-semibold text-emerald-700">Recebimento</p>
                                <h3 class="mt-1 text-base font-semibold text-slate-900">Registrar baixa da fatura</h3>
                                <p class="mt-1 text-xs text-slate-500">Informe o pagamento, anexe o comprovante e registre uma observação se necessário.</p>
                            </div>
                            <button type="button"
                                    data-cr-fechar-modal-baixa
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 hover:text-slate-600 hover:border-slate-300">
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="px-5 py-4 border-b border-slate-100 bg-white">
                        <div class="grid gap-3 sm:grid-cols-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Fatura</p>
                                <p class="mt-1 font-semibold text-slate-900">#{{ $contaDetalheSelecionada->id }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Emissão</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ optional($contaDetalheSelecionada->created_at)->format('d/m/Y') ?? '—' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Vencimento</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ optional($contaDetalheSelecionada->vencimento)->format('d/m/Y') ?? '—' }}</p>
                            </div>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                                <p class="text-[11px] uppercase tracking-wide text-emerald-700">Saldo atual</p>
                                <p class="mt-1 font-semibold text-emerald-700">R$ {{ number_format($detalheSaldo, 2, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST"
                          action="{{ route('financeiro.contas-receber.baixar', $contaDetalheSelecionada) }}"
                          class="flex-1 min-h-0 flex flex-col"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="flex-1 min-h-0 overflow-y-auto px-5 py-4">
                            <div class="grid gap-4 md:grid-cols-12">
                                <div class="md:col-span-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <label class="text-xs font-semibold text-slate-600">Valor recebido</label>
                                    <div class="mt-2 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">R$</span>
                                        <input type="text"
                                               inputmode="decimal"
                                               name="valor"
                                               data-money-input
                                               class="w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm pl-10 pr-3 py-2.5 h-[44px] focus:border-emerald-300 focus:ring-emerald-100"
                                               placeholder="0,00"
                                               required />
                                    </div>
                                    <p class="mt-2 text-[11px] text-slate-500">Informe o valor efetivamente recebido nesta baixa.</p>
                                </div>

                                <div class="md:col-span-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <label class="text-xs font-semibold text-slate-600">Data do pagamento</label>
                                    <input type="date"
                                           name="pago_em"
                                           class="mt-2 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5 h-[44px] focus:border-emerald-300 focus:ring-emerald-100" />
                                    <p class="mt-2 text-[11px] text-slate-500">Opcional. Se vazio, será usado o registro atual.</p>
                                </div>

                                <div class="md:col-span-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <label class="text-xs font-semibold text-slate-600">Meio de pagamento</label>
                                    <select name="meio_pagamento"
                                            class="mt-2 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5 h-[44px] focus:border-emerald-300 focus:ring-emerald-100"
                                            required>
                                        <option value="">Selecione...</option>
                                        @foreach($formasPagamento as $formaPagamento)
                                            <option value="{{ $formaPagamento }}">{{ $formaPagamento }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-2 text-[11px] text-slate-500">Usado no histórico e conferência financeira.</p>
                                </div>

                                <div class="md:col-span-12 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 shadow-sm">
                                    <label class="text-xs font-semibold text-slate-600">Comprovante de pagamento</label>
                                    <div class="mt-2 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-4">
                                        <input type="file"
                                               name="comprovante"
                                               accept=".pdf,.jpg,.jpeg,.png"
                                               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm"
                                               required />
                                        <p class="mt-2 text-[11px] text-slate-500">Formatos aceitos: PDF, JPG, JPEG, PNG (máx. 10MB).</p>
                                    </div>
                                </div>

                                <div class="md:col-span-12 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <label class="text-xs font-semibold text-slate-600">Observação</label>
                                    <textarea name="observacao"
                                              rows="4"
                                              class="mt-2 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5 focus:border-emerald-300 focus:ring-emerald-100"
                                              placeholder="Detalhes sobre a baixa (ex.: referência bancária, parcela, observação interna)"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="px-5 py-4 border-t border-slate-100 bg-white/95 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <p class="text-xs text-slate-500">
                                O comprovante é obrigatório e ficará disponível no histórico de baixas da fatura.
                            </p>
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        data-cr-fechar-modal-baixa
                                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                    Cancelar
                                </button>
                                <button class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm {{ $canUpdate ? 'bg-gradient-to-r from-emerald-600 to-green-600 text-white hover:from-emerald-700 hover:to-green-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                        @if(!$canUpdate) disabled title="Usuário sem permissão" @endif>
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-[12px]">✓</span>
                                    Confirmar baixa
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const successAlert = document.getElementById('cr-success-alert');
            if (successAlert) {
                setTimeout(function () {
                    successAlert.classList.add('opacity-0');
                    setTimeout(function () {
                        successAlert.classList.add('hidden');
                    }, 300);
                }, 3500);
            }

            const tabButtons = document.querySelectorAll('[data-cr-tab-btn]');
            const tabSections = document.querySelectorAll('[data-cr-tab]');
            const vendaRows = document.querySelectorAll('tr[data-expand-toggle]');
            const expandActionButtons = document.querySelectorAll('[data-expand-action]');
            const vendaMasters = document.querySelectorAll('.js-venda-master');
            const itemCheckboxes = document.querySelectorAll('.js-venda-item-checkbox');
            const badgeVendas = document.getElementById('crResumoVendasBadge');
            const resumoItens = document.getElementById('crResumoItens');
            const resumoTotal = document.getElementById('crResumoTotal');
            const btnCriar = document.getElementById('crBtnCriarFatura');
            const btnCriarTexto = document.getElementById('crBtnCriarFaturaTexto');
            const filtrosVendasSection = document.getElementById('cr-filtros-vendas');
            const chkSelecionarTodos = document.getElementById('crSelecionarTodosHeader');
            const canCreate = btnCriar && !btnCriar.disabled;
            const hiddenClienteFatura = document.getElementById('crClienteIdFatura');
            const initialTab = @json($abaAtiva);
            const clienteInput = document.getElementById('cr-cliente-autocomplete-input');
            const clienteList = document.getElementById('cr-cliente-autocomplete-list');
            const clienteIdHidden = document.getElementById('cr-cliente-id');
            const faturasClienteInput = document.getElementById('cr-faturas-cliente-autocomplete-input');
            const faturasClienteList = document.getElementById('cr-faturas-cliente-autocomplete-list');
            const faturasClienteIdHidden = document.getElementById('cr-faturas-cliente-id');
            const clienteMap = @json(($clientes ?? collect())->mapWithKeys(function ($c) {
                $nome = trim((string) ($c->razao_social ?? ''));
                return $nome !== '' ? [$nome => (int) $c->id] : [];
            }));

            function ativarTab(target) {
                const activeTabClasses = {
                    vendas: ['bg-emerald-600', 'text-white'],
                    faturas: ['bg-indigo-600', 'text-white'],
                    detalhe: ['bg-amber-500', 'text-white'],
                };

                tabSections.forEach(section => {
                    section.classList.toggle('hidden', section.dataset.crTab !== target);
                });
                if (filtrosVendasSection) {
                    filtrosVendasSection.classList.toggle('hidden', target !== 'vendas');
                }
                tabButtons.forEach(btn => {
                    const active = btn.dataset.crTabBtn === target;
                    btn.classList.remove('bg-indigo-600', 'bg-emerald-600', 'bg-amber-500', 'text-white', 'text-slate-700', 'hover:bg-slate-100');
                    if (active) {
                        btn.classList.add(...(activeTabClasses[btn.dataset.crTabBtn] || ['bg-indigo-600', 'text-white']));
                    } else {
                        btn.classList.add('text-slate-700', 'hover:bg-slate-100');
                    }
                });
            }

            tabButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    ativarTab(button.dataset.crTabBtn);
                });
            });

            function toggleVendaExpand(targetId) {
                const row = document.querySelector('tr[data-expand-toggle="' + targetId + '"]');
                const target = targetId ? document.getElementById(targetId) : null;
                const icon = targetId ? document.querySelector('[data-expand-icon="' + targetId + '"]') : null;
                if (!row || !target) return;

                const isHidden = target.classList.contains('hidden');
                target.classList.toggle('hidden', !isHidden);
                row.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                row.classList.toggle('bg-indigo-50/60', isHidden);
                if (icon) {
                    icon.textContent = isHidden ? 'Itens ▾' : 'Itens ▸';
                    icon.classList.toggle('bg-indigo-100', isHidden);
                    icon.classList.toggle('border-indigo-300', isHidden);
                    icon.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                    icon.setAttribute('title', isHidden ? 'Ocultar itens da venda' : 'Mostrar itens da venda');
                }
            }

            vendaRows.forEach(function (row) {
                row.addEventListener('click', function (event) {
                    const clickedInteractive = event.target.closest('input, button, a, label, form');
                    if (clickedInteractive) {
                        return;
                    }

                    toggleVendaExpand(row.dataset.expandToggle);
                });
            });

            expandActionButtons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.stopPropagation();
                    toggleVendaExpand(button.dataset.expandAction);
                });
            });

            function formatCurrency(value) {
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
            }

            function syncMasterByChildren(parentId) {
                const master = document.querySelector('.js-venda-master[data-venda-target="' + parentId + '"]');
                if (!master) return;
                const children = Array.from(document.querySelectorAll('.js-venda-item-checkbox[data-parent-venda="' + parentId + '"]'));
                if (!children.length) {
                    master.checked = false;
                    master.indeterminate = false;
                    return;
                }
                const checkedCount = children.filter(c => c.checked).length;
                master.checked = checkedCount === children.length;
                master.indeterminate = checkedCount > 0 && checkedCount < children.length;
            }

            function atualizarResumo() {
                const checkedItems = Array.from(itemCheckboxes).filter(cb => cb.checked);
                const vendasSelecionadas = Array.from(vendaMasters).filter(cb => cb.checked || cb.indeterminate).length;
                const total = checkedItems.reduce((sum, cb) => sum + (parseFloat(cb.dataset.itemValor || '0') || 0), 0);
                const clientesSelecionados = Array.from(new Set(
                    checkedItems
                        .map(cb => String(cb.dataset.clienteId || '').trim())
                        .filter(Boolean)
                ));
                const temClientesDiferentes = clientesSelecionados.length > 1;

                if (badgeVendas) badgeVendas.textContent = String(vendasSelecionadas);
                if (resumoItens) resumoItens.textContent = String(checkedItems.length);
                if (resumoTotal) resumoTotal.textContent = formatCurrency(total);
                if (hiddenClienteFatura) {
                    hiddenClienteFatura.value = clientesSelecionados.length === 1 ? clientesSelecionados[0] : '';
                }
                if (btnCriar && canCreate) {
                    const desabilitar = checkedItems.length === 0;
                    btnCriar.disabled = desabilitar;
                    btnCriar.classList.toggle('opacity-60', desabilitar);
                    btnCriar.classList.toggle('cursor-not-allowed', desabilitar);
                    if (btnCriarTexto) {
                        btnCriarTexto.textContent = temClientesDiferentes ? 'Gerar Fatura em Lote' : 'Criar Fatura';
                    }
                    if (checkedItems.length === 0) {
                        btnCriar.title = 'Selecione ao menos um item para criar a fatura.';
                    } else if (temClientesDiferentes) {
                        btnCriar.title = 'Serão geradas faturas em lote, uma por cliente selecionado.';
                    } else {
                        btnCriar.title = 'Criar fatura com os itens selecionados.';
                    }
                }

                if (chkSelecionarTodos) {
                    const elegiveis = itemCheckboxes.filter(cb => !cb.disabled);
                    const elegiveisMarcados = elegiveis.filter(cb => cb.checked).length;
                    chkSelecionarTodos.checked = elegiveis.length > 0 && elegiveisMarcados === elegiveis.length;
                    chkSelecionarTodos.indeterminate = elegiveisMarcados > 0 && elegiveisMarcados < elegiveis.length;
                }
            }

            vendaMasters.forEach(function (master) {
                master.addEventListener('click', function (event) {
                    event.stopPropagation();
                });

                master.addEventListener('change', function () {
                    const parentId = master.dataset.vendaTarget;
                    document.querySelectorAll('.js-venda-item-checkbox[data-parent-venda="' + parentId + '"]').forEach(function (child) {
                        child.checked = master.checked;
                    });
                    master.indeterminate = false;
                    atualizarResumo();
                });
            });

            itemCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
                checkbox.addEventListener('change', function () {
                    syncMasterByChildren(checkbox.dataset.parentVenda);
                    atualizarResumo();
                });
            });

            function selecionarTodosItens(marcar = true) {
                itemCheckboxes.forEach(function (cb) {
                    if (!cb.disabled) {
                        cb.checked = marcar;
                    }
                });
                vendaMasters.forEach(function (cb) {
                    if (!cb.disabled) {
                        cb.checked = marcar;
                        cb.indeterminate = false;
                    }
                });
                atualizarResumo();
            }

            if (chkSelecionarTodos) {
                chkSelecionarTodos.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
                chkSelecionarTodos.addEventListener('change', function () {
                    selecionarTodosItens(chkSelecionarTodos.checked);
                });
            }

            function bindClienteAutocomplete(inputEl, listEl, hiddenEl) {
                if (!inputEl || !listEl) return;

                window.initTailwindAutocomplete?.(
                    inputEl,
                    listEl,
                    @json($cliente_autocomplete ?? [])
                );

                const syncClienteId = function () {
                    const valor = (inputEl.value || '').trim();
                    if (!hiddenEl) return;
                    hiddenEl.value = clienteMap[valor] || '';
                };

                inputEl.addEventListener('input', syncClienteId);
                inputEl.addEventListener('blur', function () {
                    setTimeout(syncClienteId, 100);
                });
            }

            bindClienteAutocomplete(clienteInput, clienteList, clienteIdHidden);
            bindClienteAutocomplete(faturasClienteInput, faturasClienteList, faturasClienteIdHidden);

            async function confirmAction(options) {
                const {
                    title = 'Confirmar ação',
                    text = 'Deseja continuar?',
                    confirmText = 'Confirmar',
                    cancelText = 'Cancelar',
                    icon = 'warning',
                    fallbackText = text,
                } = options || {};

                if (typeof window.uiConfirm === 'function') {
                    return window.uiConfirm(text, {
                        title,
                        icon,
                        confirmText,
                        cancelText,
                    });
                }

                if (!window.Swal || typeof window.Swal.fire !== 'function') {
                    return window.confirm(fallbackText);
                }

                const result = await window.Swal.fire({
                    icon,
                    title,
                    text,
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: cancelText,
                    reverseButtons: true,
                    focusCancel: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b'
                });

                return !!result.isConfirmed;
            }

            function bindConfirmSubmit(selector, options) {
                document.querySelectorAll(selector).forEach(function (form) {
                    form.addEventListener('submit', async function (event) {
                        event.preventDefault();
                        const confirmed = await confirmAction(options);
                        if (confirmed) {
                            form.submit();
                        }
                    });
                });
            }

            bindConfirmSubmit('.js-form-remover-fatura', {
                title: 'Excluir fatura?',
                text: 'Deseja mesmo realizar a exclusão desta fatura? Esta ação não poderá ser desfeita e a venda vai voltar a ficar pendente.',
                confirmText: 'Sim, excluir fatura',
                cancelText: 'Cancelar',
                icon: 'warning',
                fallbackText: 'Deseja mesmo realizar a exclusão desta fatura?'
            });

            bindConfirmSubmit('.js-form-excluir-baixa', {
                title: 'Excluir baixa?',
                text: 'Deseja mesmo realizar a exclusão da baixa desta fatura? A fatura poderá voltar para edição.',
                confirmText: 'Sim, excluir baixa',
                cancelText: 'Cancelar',
                icon: 'warning',
                fallbackText: 'Deseja mesmo realizar a exclusão da baixa?'
            });

            const formCriarFatura = document.getElementById('formCriarFatura');
            if (formCriarFatura) {
                formCriarFatura.addEventListener('submit', function (event) {
                    if (!btnCriar || !canCreate) return;
                    if (btnCriar.disabled) {
                        event.preventDefault();
                    }
                });
            }

            const abrirModalBaixaDetalhe = document.getElementById('crAbrirModalBaixaDetalhe');
            const modalBaixaDetalhe = document.getElementById('crModalBaixaDetalhe');
            const fecharModalBaixaDetalheBtns = document.querySelectorAll('[data-cr-fechar-modal-baixa]');
            const abrirModalEmailFatura = document.getElementById('crAbrirModalEmailFatura');
            const modalEmailFatura = document.getElementById('crModalEmailFatura');
            const fecharModalEmailFaturaBtns = document.querySelectorAll('[data-cr-fechar-modal-email-fatura]');
            const formEnviarEmailFatura = document.getElementById('crFormEnviarEmailFatura');
            const btnEnviarEmailFatura = document.getElementById('crBtnEnviarEmailFatura');
            const btnEnviarEmailFaturaLabel = document.getElementById('crBtnEnviarEmailFaturaLabel');
            const btnEnviarEmailFaturaSpinner = document.getElementById('crBtnEnviarEmailFaturaSpinner');
            const loadingEmailFatura = document.getElementById('crEmailFaturaLoading');
            const abrirModalRelatorioVendas = document.getElementById('crAbrirModalRelatorioVendas');
            const modalRelatorioVendas = document.getElementById('crModalRelatorioVendas');
            const fecharModalRelatorioVendasBtns = document.querySelectorAll('[data-cr-fechar-modal-relatorio-vendas]');
            const openModalBaixaLinks = document.querySelectorAll('[data-cr-open-modal-baixa-link]');
            const openModalEmailLinks = document.querySelectorAll('[data-cr-open-modal-email-link]');
            const moneyInputs = document.querySelectorAll('[data-money-input]');

            function formatMoneyValue(rawValue) {
                const digits = String(rawValue || '').replace(/\D/g, '');
                if (!digits) return '';

                const cents = digits.padStart(3, '0');
                const integerPart = cents.slice(0, -2).replace(/^0+(?=\d)/, '');
                const normalizedInteger = integerPart === '' ? '0' : integerPart;
                const formattedInteger = normalizedInteger.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                const decimalPart = cents.slice(-2);

                return `${formattedInteger},${decimalPart}`;
            }

            moneyInputs.forEach(function (input) {
                input.addEventListener('input', function () {
                    input.value = formatMoneyValue(input.value);
                });

                input.addEventListener('blur', function () {
                    input.value = formatMoneyValue(input.value);
                });
            });

            function openModalBaixa() {
                if (!modalBaixaDetalhe) return false;
                if (abrirModalBaixaDetalhe && abrirModalBaixaDetalhe.disabled) return false;
                modalBaixaDetalhe.classList.remove('hidden');
                return true;
            }

            function openModalEmail() {
                if (!modalEmailFatura) return false;
                if (abrirModalEmailFatura && abrirModalEmailFatura.disabled) return false;
                modalEmailFatura.classList.remove('hidden');
                return true;
            }

            if (formEnviarEmailFatura && btnEnviarEmailFatura) {
                formEnviarEmailFatura.addEventListener('submit', function (event) {
                    if (btnEnviarEmailFatura.disabled) {
                        event.preventDefault();
                        return;
                    }

                    btnEnviarEmailFatura.disabled = true;
                    btnEnviarEmailFatura.classList.add('opacity-80', 'cursor-wait');

                    if (btnEnviarEmailFaturaLabel) {
                        btnEnviarEmailFaturaLabel.textContent = 'Enviando...';
                    }

                    if (btnEnviarEmailFaturaSpinner) {
                        btnEnviarEmailFaturaSpinner.classList.remove('hidden');
                    }

                    if (loadingEmailFatura) {
                        loadingEmailFatura.classList.remove('hidden');
                    }
                });
            }

            function openModalRelatorioVendas() {
                if (!modalRelatorioVendas) return false;
                modalRelatorioVendas.classList.remove('hidden');
                return true;
            }

            if (abrirModalBaixaDetalhe && modalBaixaDetalhe) {
                abrirModalBaixaDetalhe.addEventListener('click', function () {
                    openModalBaixa();
                });
            }

            fecharModalBaixaDetalheBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    modalBaixaDetalhe?.classList.add('hidden');
                });
            });

            if (abrirModalEmailFatura && modalEmailFatura) {
                abrirModalEmailFatura.addEventListener('click', function () {
                    openModalEmail();
                });
            }

            fecharModalEmailFaturaBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    modalEmailFatura?.classList.add('hidden');
                });
            });

            if (abrirModalRelatorioVendas && modalRelatorioVendas) {
                abrirModalRelatorioVendas.addEventListener('click', function () {
                    openModalRelatorioVendas();
                });
            }

            fecharModalRelatorioVendasBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    modalRelatorioVendas?.classList.add('hidden');
                });
            });

            function isSameUrlWithoutHash(anchorUrl) {
                try {
                    const target = new URL(anchorUrl, window.location.origin);
                    return target.pathname === window.location.pathname
                        && target.search === window.location.search;
                } catch (e) {
                    return false;
                }
            }

            openModalBaixaLinks.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    const href = link.getAttribute('href') || '';
                    if (!href || !isSameUrlWithoutHash(href)) return;
                    event.preventDefault();
                    window.history.replaceState(null, '', href);
                    openModalBaixa();
                });
            });

            openModalEmailLinks.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    const href = link.getAttribute('href') || '';
                    if (!href || !isSameUrlWithoutHash(href)) return;
                    event.preventDefault();
                    window.history.replaceState(null, '', href);
                    openModalEmail();
                });
            });

            window.addEventListener('hashchange', function () {
                if (window.location.hash === '#cr-open-modal-baixa') {
                    openModalBaixa();
                }
                if (window.location.hash === '#cr-open-modal-email') {
                    openModalEmail();
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const whatsappSendingClass = ['cursor-wait', 'opacity-70'];
            const emailSendingClass = ['cursor-wait', 'opacity-70'];

            const quickEmailBtn = document.getElementById('crAbrirModalEmailFatura');
            const emailOptionsData = quickEmailBtn?.dataset?.emailOptions ?? '[]';
            const parsedEmailOptions = (() => {
                try {
                    return JSON.parse(emailOptionsData);
                } catch (err) {
                    return [];
                }
            })();
            const quickEmailRoute = quickEmailBtn?.dataset?.emailRoute || '';
            const quickEmailSpinner = document.getElementById('crBtnEnviarEmailFaturaSpinner');
            const quickEmailLabel = document.getElementById('crBtnEnviarEmailFaturaLabel');

            const setEmailButtonState = (loading) => {
                if (!quickEmailBtn) return;
                quickEmailBtn.disabled = loading;
                quickEmailBtn.classList.toggle('cursor-wait', loading);
                quickEmailBtn.classList.toggle('opacity-70', loading);
                if (quickEmailSpinner) {
                    quickEmailSpinner.classList.toggle('hidden', !loading);
                }
                if (quickEmailLabel) {
                    quickEmailLabel.textContent = loading ? 'Enviando...' : 'Enviar fatura';
                }
            };

            const defaultEmail = parsedEmailOptions.find((opt) => (opt['tipo'] ?? '') === 'cliente_fatura')?.['value']
                || parsedEmailOptions[0]?.['value'];

            const toggleFinanceiroEmailButton = (button, sending) => {
                if (!button) return;
                button.disabled = sending;
                button.classList.toggle('cursor-wait', sending);
                button.classList.toggle('opacity-70', sending);
                if (sending) {
                    button.dataset.financeiroEmailSending = '1';
                } else {
                    delete button.dataset.financeiroEmailSending;
                }
            };

            const enviarFinanceiroEmail = async (button) => {
                const route = button.getAttribute('data-financeiro-email-route') || '';
                const destino = button.getAttribute('data-financeiro-email-destino') || '';
                if (!route || !destino) {
                    await window.uiAlert('Não foi possível enviar o e-mail: verifique se o destino está configurado.', {
                        title: 'Erro',
                        icon: 'error',
                    });
                    return;
                }

                toggleFinanceiroEmailButton(button, true);
                try {
                    const formData = new FormData();
                    formData.append('email_destino', destino);
                    formData.append('_token', csrfToken);

                    const response = await fetch(route, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || data.ok === false) {
                        throw new Error(data.message || 'Não foi possível enviar o e-mail.');
                    }

                    await window.uiAlert(data.message || 'E-mail enviado com sucesso.', {
                        title: 'Sucesso',
                        icon: 'success',
                    });
                } catch (error) {
                    await window.uiAlert(error.message || 'Não foi possível enviar o e-mail.', {
                        title: 'Erro',
                        icon: 'error',
                    });
                } finally {
                    toggleFinanceiroEmailButton(button, false);
                }
            };

            const toggleWhatsappButton = (button, sending) => {
                if (sending) {
                    button.dataset.whatsappSending = '1';
                    button.disabled = true;
                    button.classList.add(...whatsappSendingClass);
                } else {
                    delete button.dataset.whatsappSending;
                    button.disabled = false;
                    button.classList.remove(...whatsappSendingClass);
                }
            };

            const enviarWhatsappFinanceiro = async (button) => {
                const route = button.getAttribute('data-whatsapp-route');
                if (!route) return;

                toggleWhatsappButton(button, true);
                try {
                    const response = await fetch(route, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || data.ok === false) {
                        throw new Error(data.message || 'Não foi possível enviar via WhatsApp.');
                    }

                    await window.uiAlert(data.message || 'Mensagem enviada com sucesso pelo WhatsApp.', {
                        title: 'Sucesso',
                        icon: 'success',
                    });
                } catch (error) {
                    await window.uiAlert(error.message || 'Não foi possível enviar via WhatsApp.', {
                        title: 'Erro',
                        icon: 'error',
                    });
                } finally {
                    toggleWhatsappButton(button, false);
                }
            };

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('[data-whatsapp-route]');
                if (!trigger) return;
                event.preventDefault();
                if (trigger.dataset.whatsappSending === '1') return;
                enviarWhatsappFinanceiro(trigger);
            });

            document.addEventListener('click', function (event) {
                const emailTrigger = event.target.closest('[data-financeiro-email-route]');
                if (!emailTrigger) return;
                event.preventDefault();
                if (emailTrigger.dataset.financeiroEmailSending === '1') return;
                enviarFinanceiroEmail(emailTrigger);
            });

            quickEmailBtn?.addEventListener('click', async function (event) {
                event.preventDefault();
                if (!quickEmailRoute || quickEmailBtn.disabled) {
                    openModalEmail();
                    return;
                }

                if (!defaultEmail) {
                    openModalEmail();
                    return;
                }

                setEmailButtonState(true);
                try {
                    const formData = new FormData();
                    formData.append('email_destino', defaultEmail);
                    formData.append('_token', csrfToken);

                    const response = await fetch(quickEmailRoute, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || data.ok === false) {
                        throw new Error(data.message || 'O envio falhou.');
                    }

                    await window.uiAlert(data.message || 'E-mail enviado com sucesso.', {
                        title: 'Sucesso',
                        icon: 'success',
                    });
                } catch (error) {
                    await window.uiAlert('O envio não foi concluído: verifique usuário/senha do SMTP antes de tentar novamente.', {
                        title: 'Erro',
                        icon: 'error',
                    });
                } finally {
                    setEmailButtonState(false);
                }
            });

            ativarTab(initialTab || 'vendas');
            atualizarResumo();

            const currentHash = window.location.hash || '';
            if (currentHash === '#cr-open-modal-baixa') {
                openModalBaixa();
            }
            if (currentHash === '#cr-open-modal-email') {
                openModalEmail();
            }
        });
    </script>
@endsection
