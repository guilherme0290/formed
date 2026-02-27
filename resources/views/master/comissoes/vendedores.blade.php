@extends('layouts.master')
@section('title', 'Comiss&otilde;es por Vendedor')

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
        <header class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 px-4 py-3 text-white shadow-lg shadow-indigo-900/10">
            <div class="pointer-events-none absolute -right-8 -top-10 h-36 w-36 rounded-full bg-white/10 blur-2xl"></div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-indigo-100">Comiss&otilde;es Master</p>
                    <h1 class="text-xl md:text-2xl font-semibold">Vis&atilde;o Geral por Vendedor</h1>
                    <p class="text-xs text-indigo-100 mt-1">An&aacute;lise anual com filtros e comparativos.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('master.dashboard') }}"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold border border-white/20">
                        Painel Master
                    </a>
                    <form method="GET" action="{{ route('master.comissoes.vendedores') }}" class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-indigo-100">Vendedor</label>
                        <select name="vendedor" class="px-2.5 py-1.5 rounded-lg border border-white/30 text-xs text-white bg-white/10">
                            <option value="" {{ empty($vendedorSelecionado) ? 'selected' : '' }}>Todos</option>
                            @foreach($vendedores as $id => $vend)
                                <option value="{{ $id }}" {{ (int)$vendedorSelecionado === (int)$id ? 'selected' : '' }}>
                                    {{ $vend->name ?? 'Vendedor '.$id }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="ano" value="{{ $anoSelecionado }}">
                        <button class="px-3 py-1.5 rounded-lg bg-blue-500 hover:bg-blue-400 text-xs font-semibold">Aplicar</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="flex flex-wrap gap-2 items-center">
            @forelse($anos as $ano)
                <a href="{{ route('master.comissoes.vendedores', ['ano' => $ano, 'vendedor' => $vendedorSelecionado]) }}"
                   class="px-3 py-1.5 rounded-xl border text-sm font-semibold transition {{ (int)$ano === (int)$anoSelecionado ? 'bg-orange-500 text-white border-orange-500 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
                    {{ $ano }}
                </a>
            @empty
                <span class="text-sm text-slate-500">Sem comiss&otilde;es registradas.</span>
            @endforelse
        </div>

        <div class="grid flex-1 gap-3 md:grid-cols-3 auto-rows-fr content-start">
            @foreach($meses as $mes)
                @php
                    $theme = $cardThemes[($mes->mes - 1) % count($cardThemes)];
                @endphp

                <a href="{{ route('master.comissoes.vendedores.previsao', ['ano' => $anoSelecionado, 'mes' => $mes->mes, 'vendedor' => $vendedorSelecionado]) }}"
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

        <section class="bg-white rounded-2xl border border-indigo-100 shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-indigo-700">Ranking de vendedores ({{ $anoSelecionado }})</h2>
                <span class="text-xs text-slate-500">Comiss&atilde;o total (prevista + paga)</span>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($ranking as $row)
                    @php
                        $pos = $loop->iteration;
                        $medalha = match ($pos) {
                            1 => 'bg-amber-100 text-amber-700 border-amber-200',
                            2 => 'bg-slate-100 text-slate-700 border-slate-200',
                            3 => 'bg-orange-100 text-orange-700 border-orange-200',
                            default => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                        };
                    @endphp
                    <div class="px-4 py-3 hover:bg-slate-50/60 transition">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex min-w-[2rem] justify-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $medalha }}">
                                    #{{ $pos }}
                                </span>
                                <p class="text-sm font-semibold text-slate-800">{{ $row->vendedor->name ?? 'ID '.$row->vendedor_id }}</p>
                            </div>
                            <div class="text-lg font-semibold text-indigo-800">R$ {{ number_format($row->total ?? 0, 2, ',', '.') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-4 text-sm text-slate-500">Nenhuma comiss&atilde;o registrada neste ano.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
