@extends('layouts.master')
@section('title', 'Previs&atilde;o de Comiss&atilde;o')

@section('content')
    @php
        $mesNome = \Carbon\Carbon::createFromDate($ano, $mes, 1)->locale('pt_BR')->isoFormat('MMMM');
        $totalPrevisao = (float) collect($clientes)->sum(fn ($cliente) => (float) ($cliente->total ?? 0));
        $totalClientes = (int) collect($clientes)->count();
        $totalLinhas = (int) collect($detalhesPorCliente)->flatten(1)->count();
        $maiorCliente = collect($clientes)->sortByDesc(fn ($cliente) => (float) ($cliente->total ?? 0))->first();
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-5">
        <header class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 px-4 py-4 text-white shadow-lg shadow-indigo-900/10">
            <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute bottom-0 left-1/3 h-24 w-24 rounded-full bg-cyan-300/10 blur-2xl"></div>

            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-indigo-100">Previs&atilde;o de Comiss&atilde;o</p>
                    <h1 class="mt-1 text-2xl md:text-3xl font-semibold">{{ ucfirst($mesNome) }} / {{ $ano }}</h1>
                    <p class="mt-1 text-sm text-indigo-100">Leitura consolidada por cliente com detalhamento do que est&aacute; compondo cada valor.</p>
                </div>

                <a href="{{ route('master.comissoes.vendedores', ['ano' => $ano, 'vendedor' => $vendedorId]) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold border border-white/20 hover:bg-white/20">
                    <i class="fa-solid fa-arrow-left text-[11px]"></i>
                    Voltar
                </a>
            </div>
        </header>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-indigo-500">Total previsto</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">R$ {{ number_format($totalPrevisao, 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">Soma das comiss&otilde;es previstas no m&ecirc;s.</p>
            </article>

            <article class="rounded-2xl border border-cyan-100 bg-cyan-50/70 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-700">Clientes</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalClientes }}</p>
                <p class="mt-1 text-xs text-slate-500">Clientes com comiss&atilde;o prevista neste recorte.</p>
            </article>

            <article class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-700">Itens detalhados</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalLinhas }}</p>
                <p class="mt-1 text-xs text-slate-500">Linhas detalhadas entre servi&ccedil;os e descri&ccedil;&otilde;es.</p>
            </article>

            <article class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">Maior cliente</p>
                <p class="mt-2 text-lg font-bold text-slate-900">
                    {{ $maiorCliente?->cliente->nome_fantasia ?? $maiorCliente?->cliente->razao_social ?? '—' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $maiorCliente ? 'R$ ' . number_format((float) ($maiorCliente->total ?? 0), 2, ',', '.') : 'Sem dados no m&ecirc;s.' }}
                </p>
            </article>
        </section>

        <section class="rounded-2xl border border-indigo-100 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-indigo-100 bg-slate-50/80 px-4 py-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Clientes</h2>
                    <p class="text-xs text-slate-500">Ordenado por maior valor previsto.</p>
                </div>
                <div class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-semibold text-indigo-700 border border-indigo-100">
                    {{ $totalClientes }} cliente{{ $totalClientes === 1 ? '' : 's' }}
                </div>
            </div>

            <div class="space-y-4 p-4">
                @forelse($clientes as $cliente)
                    @php
                        $detalhes = collect($detalhesPorCliente)
                            ->flatten(1)
                            ->filter(fn ($item) => (int) ($item->cliente_id ?? 0) === (int) $cliente->cliente_id)
                            ->values();
                        $clienteNome = $cliente->cliente->nome_fantasia ?? $cliente->cliente->razao_social ?? 'Cliente #' . $cliente->cliente_id;
                        $clienteTotal = (float) ($cliente->total ?? 0);
                    @endphp

                    <article x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }"
                             class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-900">{{ $clienteNome }}</h3>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                        Cliente
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Comiss&atilde;o prevista consolidada para este cliente.</p>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-indigo-500">Total</p>
                                    <p class="text-2xl font-bold text-indigo-800">R$ {{ number_format($clienteTotal, 2, ',', '.') }}</p>
                                </div>

                                @if($detalhes->isNotEmpty())
                                    <button type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        <span x-text="open ? 'Ocultar detalhes' : 'Ver detalhes'"></span>
                                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($detalhes->isNotEmpty())
                            <div x-show="open" x-transition class="border-t border-slate-200 bg-slate-50/70 px-4 py-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Composi&ccedil;&atilde;o da comiss&atilde;o</p>
                                    <p class="text-[11px] text-slate-400">Servi&ccedil;o, descri&ccedil;&atilde;o e valor comissionado</p>
                                </div>

                                <div class="space-y-2">
                                    @foreach($detalhes as $item)
                                        @php
                                            $descricaoDetalhada = trim((string) ($item->item_descricao ?? ''));
                                            $servicoNome = trim((string) ($item->servico_nome ?? ''));
                                            $mostrarDescricao = $descricaoDetalhada !== '' && $descricaoDetalhada !== $servicoNome;
                                            $quantidadeTarefas = (int) ($item->quantidade_tarefas ?? 0);
                                            $quantidadeItens = (int) ($item->quantidade_itens ?? 0);
                                            $quantidadeBase = $quantidadeTarefas > 0 ? $quantidadeTarefas : $quantidadeItens;
                                            $quantidadeLabel = $quantidadeTarefas > 0 ? 'tarefa' : 'item';
                                            $primeiraData = !empty($item->primeira_data) ? \Carbon\Carbon::parse($item->primeira_data) : null;
                                            $periodoLabel = $primeiraData?->format('d/m/Y');
                                        @endphp

                                        <div class="grid gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 md:grid-cols-[minmax(0,1fr),auto] md:items-start">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 border border-indigo-100">
                                                        {{ $servicoNome !== '' ? $servicoNome : 'Servi&ccedil;o' }}
                                                    </span>
                                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                                        {{ $quantidadeBase }} {{ $quantidadeLabel }}{{ $quantidadeBase === 1 ? '' : 's' }}
                                                    </span>
                                                    @if($periodoLabel)
                                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 border border-amber-100">
                                                            {{ $periodoLabel }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if($mostrarDescricao)
                                                    <p class="mt-2 text-sm font-medium text-slate-800 break-words">{{ $descricaoDetalhada }}</p>
                                                @else
                                                    <p class="mt-2 text-sm text-slate-500">Sem detalhamento adicional para este item.</p>
                                                @endif
                                            </div>

                                            <div class="text-left md:text-right">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Comiss&atilde;o</p>
                                                <p class="mt-1 text-lg font-bold text-slate-900">R$ {{ number_format((float) ($item->total ?? 0), 2, ',', '.') }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm font-semibold text-slate-700">Nenhuma comiss&atilde;o prevista para este m&ecirc;s.</p>
                        <p class="mt-1 text-xs text-slate-500">Quando existirem lan&ccedil;amentos para o per&iacute;odo, eles aparecer&atilde;o aqui.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
