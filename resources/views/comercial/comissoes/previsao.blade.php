@extends('layouts.comercial')
@section('title', 'Previs&atilde;o de Comiss&atilde;o')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $mesNome = \Carbon\Carbon::createFromDate($ano, $mes, 1)->locale('pt_BR')->isoFormat('MMMM');
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-5">
        <header class="rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 px-4 py-3 text-white">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-indigo-100">Previs&atilde;o de Comiss&atilde;o</p>
                    <h1 class="text-xl md:text-2xl font-semibold">{{ ucfirst($mesNome) }} / {{ $ano }}</h1>
                    <p class="text-xs text-indigo-100 mt-1">Soma das comiss&otilde;es previstas por cliente.</p>
                </div>
                <a href="{{ route('comercial.comissoes.mes', ['ano' => $ano, 'mes' => $mes]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold hover:bg-white/20">
                    Voltar para resumo
                </a>
            </div>
        </header>

        <div class="bg-white rounded-xl border border-indigo-100 shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                <div class="text-sm font-semibold text-indigo-700">Clientes</div>
                <div class="text-xs text-slate-500">Ordenado pelo maior valor</div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($clientes as $cliente)
                    @php
                        $detalhes = $detalhesPorCliente->get($cliente->cliente_id, collect());
                    @endphp
                    <div x-data="{ open: false }" class="px-4 py-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $cliente->cliente->nome_fantasia ?? $cliente->cliente->razao_social ?? 'Cliente #' . $cliente->cliente_id }}</p>
                                <p class="text-xs text-slate-500">Comiss&atilde;o prevista</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-lg font-semibold text-indigo-800">R$ {{ number_format($cliente->total, 2, ',', '.') }}</div>
                                @if($detalhes->isNotEmpty())
                                    <button type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50">
                                        Detalhes
                                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($detalhes->isNotEmpty())
                            <div x-show="open" x-transition class="rounded-lg border border-slate-200 bg-slate-50 p-2.5">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Detalhes por servi&ccedil;o</p>
                                <div class="space-y-1">
                                    @foreach($detalhes as $item)
                                        <div class="flex items-center justify-between gap-3 text-xs">
                                            <div class="text-slate-700">{{ $item->servico_nome }} <span class="text-slate-400">({{ $item->quantidade }}x)</span></div>
                                            <div class="font-semibold text-slate-800">R$ {{ number_format($item->total, 2, ',', '.') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-4 text-sm text-slate-500">Nenhuma comiss&atilde;o prevista para este m&ecirc;s.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
