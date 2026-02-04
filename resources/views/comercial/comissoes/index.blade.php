@extends('layouts.comercial')
@section('title', 'Minhas Comiss&otilde;es')
@section('page-container', 'w-full p-0')

@section('content')
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <a href="{{ route('comercial.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                Voltar ao Painel
            </a>
        </div>
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-orange-500">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-orange-600">$</span>
                    Minhas Comiss&otilde;es
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Vis&atilde;o Geral</h1>
                </div>
                <p class="text-sm text-slate-500">Acompanhe suas comiss&otilde;es por ano</p>
            </div>
            <div class="flex flex-wrap gap-2 justify-end items-center">
                <form method="GET" action="{{ route('comercial.comissoes.index') }}" class="flex items-center gap-2">
                    <label class="text-sm font-semibold text-slate-900">Ano</label>
                    <input type="number" name="ano" min="2000" max="{{ now()->year + 1 }}" value="{{ $anoSelecionado }}"
                           class="w-24 px-3 py-1.5 rounded-xl border border-slate-200 text-sm text-slate-700 bg-white">
                    <button class="px-3 py-1.5 rounded-xl bg-slate-900 text-white text-sm">Ir</button>
                </form>
            </div>
        </header>

        {{-- Grid de meses --}}
        <div class="grid gap-4 md:grid-cols-3">
            @foreach($meses as $mes)
                <a href="{{ route('comercial.comissoes.mes', ['ano' => $anoSelecionado, 'mes' => $mes->mes]) }}"
                   class="rounded-2xl border shadow-sm hover:shadow-md transition p-4 flex flex-col gap-3 {{ $mes->status === 'FECHADO' ? 'bg-emerald-50/80 border-emerald-100 hover:bg-emerald-100/80' : 'bg-amber-50/80 border-amber-100 hover:bg-amber-100/80' }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-orange-500">{{ $mes->nome }}</p>
                            <p class="text-lg font-semibold text-slate-900">R$ {{ number_format($mes->total, 2, ',', '.') }}</p>
                        </div>
                        @if($mes->status === 'FECHADO')
                            <span class="inline-flex items-center gap-1 text-xs px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                                &#128994; Fechado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-100">
                                &#128992; Em Aberto
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500">Clique para ver previs&atilde;o, efetivadas e inadimplentes do m&ecirc;s.</p>
                </a>
            @endforeach
        </div>
    </div>
@endsection
