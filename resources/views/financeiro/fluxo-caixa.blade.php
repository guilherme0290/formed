@extends('layouts.financeiro')
@section('title', 'Fluxo de Caixa')
@section('page-container', 'w-full px-4 sm:px-6 lg:px-8 py-6')

@section('content')
    @php
        $maxSaldo = max(1, (float) collect($serie)->max('saldo'));
    @endphp

    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900">Caixa / Fluxo de Caixa</h1>
            <p class="text-sm text-slate-500 mt-1">Entradas, saidas e saldo consolidado da empresa.</p>
        </div>

        @include('financeiro.partials.tabs')

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" class="grid gap-3 md:grid-cols-5 items-end">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Inicio</label>
                    <input type="date" name="inicio" value="{{ $filtros['inicio'] }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Fim</label>
                    <input type="date" name="fim" value="{{ $filtros['fim'] }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Categoria (saidas)</label>
                    <select name="categoria" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        <option value="">Todas</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat }}" @selected($filtros['categoria'] === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2 flex items-center gap-2">
                    <button class="h-10 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Aplicar filtros</button>
                    <button type="button" class="h-10 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700">Exportar</button>
                </div>
            </form>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Entradas</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700">R$ {{ number_format($totais['entradas'], 2, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Saidas</p>
                <p class="mt-2 text-2xl font-semibold text-rose-700">R$ {{ number_format($totais['saidas'], 2, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Saldo</p>
                <p class="mt-2 text-2xl font-semibold {{ $totais['saldo'] >= 0 ? 'text-slate-900' : 'text-rose-700' }}">R$ {{ number_format($totais['saldo'], 2, ',', '.') }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Saldo ao longo do tempo</h2>
            <div class="mt-4 flex items-end gap-1 h-52 overflow-x-auto">
                @foreach($serie as $ponto)
                    @php
                        $height = max(6, (int) round(((float) $ponto['saldo'] / $maxSaldo) * 180));
                        $color = ((float) $ponto['saldo']) >= 0 ? 'bg-emerald-500' : 'bg-rose-500';
                    @endphp
                    <div class="flex flex-col items-center justify-end min-w-[18px] gap-2">
                        <div class="w-3 rounded-t {{ $color }}" style="height: {{ abs($height) }}px"></div>
                        <span class="text-[10px] text-slate-500">{{ $ponto['data'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
