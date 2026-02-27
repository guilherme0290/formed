@extends('layouts.comercial')
@section('title', 'Minhas Comiss&otilde;es')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $totalAno = (float) collect($meses)->sum(fn ($mes) => (float) ($mes->total ?? 0));
        $mesesAbertos = (int) collect($meses)->where('status', 'ABERTO')->count();
        $mesesFechados = (int) collect($meses)->where('status', 'FECHADO')->count();
        $cardThemes = [
            ['bg' => 'bg-blue-100/80', 'border' => 'border-blue-200', 'box' => 'bg-slate-100/90'],
            ['bg' => 'bg-emerald-100/70', 'border' => 'border-emerald-200', 'box' => 'bg-slate-100/90'],
            ['bg' => 'bg-amber-100/70', 'border' => 'border-amber-200', 'box' => 'bg-slate-100/90'],
            ['bg' => 'bg-rose-100/60', 'border' => 'border-rose-200', 'box' => 'bg-slate-100/90'],
            ['bg' => 'bg-indigo-100/70', 'border' => 'border-indigo-200', 'box' => 'bg-slate-100/90'],
            ['bg' => 'bg-cyan-100/70', 'border' => 'border-cyan-200', 'box' => 'bg-slate-100/90'],
        ];
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 min-h-[calc(100vh-8.5rem)] flex flex-col gap-5">
        <header class="rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 px-4 py-3 text-white">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-indigo-100">Minhas Comiss&otilde;es</p>
                    <h1 class="text-xl md:text-2xl font-semibold">Vis&atilde;o Geral</h1>
                    <p class="text-xs text-indigo-100 mt-1">Acompanhe suas comiss&otilde;es por ano.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('comercial.dashboard') }}"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold">
                        Painel Comercial
                    </a>
                    <form method="GET" action="{{ route('comercial.comissoes.index') }}" class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-indigo-100">Ano</label>
                        <input type="number" name="ano" min="2000" max="{{ now()->year + 1 }}" value="{{ $anoSelecionado }}"
                               class="w-24 px-2.5 py-1.5 rounded-lg border border-white/30 text-xs text-white bg-white/10 placeholder:text-indigo-100">
                        <button class="px-3 py-1.5 rounded-lg bg-blue-500 hover:bg-blue-400 text-xs font-semibold">Aplicar</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="grid flex-1 gap-3 md:grid-cols-3 auto-rows-fr content-start">
            @foreach($meses as $mes)
                @php
                    $theme = $cardThemes[($mes->mes - 1) % count($cardThemes)];
                @endphp

                <a href="{{ route('comercial.comissoes.previsao', ['ano' => $anoSelecionado, 'mes' => $mes->mes]) }}"
                   class="rounded-xl border {{ $theme['border'] }} {{ $theme['bg'] }} shadow-sm p-4 flex h-full flex-col gap-3 text-left transition hover:shadow-md hover:-translate-y-[1px] cursor-pointer">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-orange-500">{{ $mes->nome }}</p>
                            <p class="text-lg font-semibold text-slate-900">R$ {{ number_format($mes->total, 2, ',', '.') }}</p>
                        </div>

                        @if($mes->status === 'FECHADO')
                            <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-emerald-50/80 text-emerald-700 border border-emerald-200">
                                <span class="h-3 w-3 rounded-full bg-green-500 border border-green-700"></span>
                                Fechado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-amber-50/90 text-amber-700 border border-amber-200">
                                <span class="h-3 w-3 rounded-full bg-orange-500 border border-orange-700"></span>
                                Em aberto
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-lg {{ $theme['box'] }} px-2.5 py-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-orange-500">Previsto</p>
                            <p class="text-sm font-semibold text-slate-900">R$ {{ number_format($mes->total_previsto ?? 0, 2, ',', '.') }}</p>
                        </div>

                        <div class="rounded-lg {{ $theme['box'] }} px-2.5 py-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600">Efetivado</p>
                            <p class="text-sm font-semibold text-slate-900">R$ {{ number_format($mes->total_efetivado ?? 0, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <section class="grid gap-3 lg:grid-cols-3">
            <article class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Total do ano</p>
                <p class="text-2xl font-bold text-indigo-900 mt-1">R$ {{ number_format($totalAno, 2, ',', '.') }}</p>
            </article>
            <article class="rounded-xl border border-amber-100 bg-amber-50/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Meses em aberto</p>
                <p class="text-2xl font-bold text-amber-800 mt-1">{{ $mesesAbertos }}</p>
            </article>
            <article class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Meses fechados</p>
                <p class="text-2xl font-bold text-emerald-800 mt-1">{{ $mesesFechados }}</p>
            </article>
        </section>
    </div>
@endsection
