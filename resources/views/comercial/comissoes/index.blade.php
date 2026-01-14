@extends('layouts.comercial')
@section('title', 'Minhas Comissões')

@section('content')
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <a href="{{ route('comercial.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                Voltar ao Painel
            </a>
        </div>
        <header class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-orange-500">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-orange-600">$</span>
                Minhas Comissões
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Visão Geral</h1>
                <span class="text-sm text-slate-500">Acompanhe suas comissões por ano</span>
            </div>
        </header>

        {{-- Seleção de ano --}}
        <div class="flex flex-wrap gap-2">
            @forelse($anos as $ano)
                <a href="{{ route('comercial.comissoes.ano', ['ano' => $ano]) }}"
                   class="px-3 py-1.5 rounded-xl border text-sm font-semibold {{ (int)$ano === (int)$anoSelecionado ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
                    {{ $ano }}
                </a>
            @empty
                <span class="text-sm text-slate-500">Sem comissões registradas ainda.</span>
            @endforelse
        </div>

        {{-- Grid de meses --}}
        <div class="grid gap-4 md:grid-cols-3">
            @foreach($meses as $mes)
                <a href="{{ route('comercial.comissoes.mes', ['ano' => $anoSelecionado, 'mes' => $mes->mes]) }}"
                   class="rounded-2xl border border-slate-100 bg-white shadow-sm hover:shadow-md transition p-4 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-orange-500">{{ $mes->nome }}</p>
                            <p class="text-lg font-semibold text-slate-900">R$ {{ number_format($mes->total, 2, ',', '.') }}</p>
                        </div>
                        @if($mes->status === 'FECHADO')
                            <span class="inline-flex items-center gap-1 text-xs px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                                🟢 Fechado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-100">
                                🟠 Em Aberto
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500">Clique para ver previsão, efetivadas e inadimplentes do mês.</p>
                </a>
            @endforeach
        </div>
    </div>
@endsection
