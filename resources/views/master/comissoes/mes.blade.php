@extends('layouts.master')
@section('title', 'Comissões do Mês')

@section('content')
    @php
        $mesNome = \Carbon\Carbon::createFromDate($ano, $mes, 1)->locale('pt_BR')->isoFormat('MMMM');
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-5">
        <header class="rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 px-4 py-3 text-white">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-indigo-100">Comiss&otilde;es Master &middot; {{ $ano }}</p>
                    <h1 class="text-xl md:text-2xl font-semibold">{{ ucfirst($mesNome) }}</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('master.dashboard') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold hover:bg-white/20">
                        Painel Master
                    </a>
                    <a href="{{ route('master.comissoes.vendedores', ['ano' => $ano, 'vendedor' => $vendedorSelecionado]) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold hover:bg-white/20">
                        Voltar para meses
                    </a>
                </div>
            </div>
        </header>

        <div class="grid md:grid-cols-3 gap-3">
            <a href="{{ route('master.comissoes.vendedores.previsao', ['ano' => $ano, 'mes' => $mes, 'vendedor' => $vendedorSelecionado]) }}"
               class="rounded-xl border border-indigo-100 bg-indigo-50/50 hover:bg-indigo-50 transition p-4 shadow-sm">
                <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wide">Previs&atilde;o de Comiss&atilde;o</p>
                <p class="text-2xl font-bold text-indigo-900 mt-2">R$ {{ number_format($totais->previsao ?? 0, 2, ',', '.') }}</p>
                <p class="text-xs text-slate-600 mt-1">Somat&oacute;rio das comiss&otilde;es previstas.</p>
            </a>

            <a href="{{ route('master.comissoes.vendedores.efetivada', ['ano' => $ano, 'mes' => $mes, 'vendedor' => $vendedorSelecionado]) }}"
               class="rounded-xl border border-blue-100 bg-blue-50/50 hover:bg-blue-50 transition p-4 shadow-sm">
                <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Comiss&atilde;o Efetivada</p>
                <p class="text-2xl font-bold text-blue-800 mt-2">R$ {{ number_format($totais->efetivada ?? 0, 2, ',', '.') }}</p>
                <p class="text-xs text-slate-600 mt-1">Pagamentos confirmados.</p>
            </a>

            <a href="{{ route('master.comissoes.vendedores.inadimplentes', ['ano' => $ano, 'mes' => $mes, 'vendedor' => $vendedorSelecionado]) }}"
               class="rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition p-4 shadow-sm">
                <p class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Clientes Inadimplentes</p>
                <p class="text-2xl font-bold text-slate-900 mt-2">{{ $totais->inadimplentes ?? 0 }}</p>
                <p class="text-xs text-slate-600 mt-1">Comiss&otilde;es pendentes de pagamento.</p>
            </a>
        </div>
    </div>
@endsection
