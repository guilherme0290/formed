@extends('layouts.comercial')
@section('title', 'Comissões do mês')

@section('content')
    @php
        $mesNome = \Carbon\Carbon::createFromDate($anoSelecionado, $mes, 1)->locale('pt_BR')->isoFormat('MMMM');
    @endphp

    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="space-y-1">
                <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.18em] text-orange-500">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-orange-600">$</span>
                    Minhas Comissões · {{ $anoSelecionado }}
                </div>
                <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">{{ ucfirst($mesNome) }}</h1>
            </div>
            <a href="{{ route('comercial.comissoes.ano', ['ano' => $anoSelecionado]) }}"
               class="text-sm text-slate-600 hover:text-slate-800 flex items-center gap-2">
                ← Voltar para meses
            </a>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <a href="{{ route('comercial.comissoes.previsao', ['ano' => $anoSelecionado, 'mes' => $mes]) }}"
               class="rounded-2xl border border-orange-100 bg-orange-50/60 hover:bg-orange-100 transition p-4 shadow-sm">
                <p class="text-xs font-semibold text-orange-600 uppercase tracking-wide">Previsão de Comissão</p>
                <p class="text-2xl font-bold text-slate-900 mt-2">R$ {{ number_format($totais->previsao ?? 0, 2, ',', '.') }}</p>
                <p class="text-xs text-slate-600 mt-1">Somatório das comissões previstas</p>
            </a>

            <a href="{{ route('comercial.comissoes.efetivada', ['ano' => $anoSelecionado, 'mes' => $mes]) }}"
               class="rounded-2xl border border-emerald-100 bg-emerald-50/70 hover:bg-emerald-100 transition p-4 shadow-sm">
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Comissão Efetivada</p>
                <p class="text-2xl font-bold text-emerald-700 mt-2">R$ {{ number_format($totais->efetivada ?? 0, 2, ',', '.') }}</p>
                <p class="text-xs text-slate-600 mt-1">Pagamentos confirmados</p>
            </a>

            <a href="{{ route('comercial.comissoes.inadimplentes', ['ano' => $anoSelecionado, 'mes' => $mes]) }}"
               class="rounded-2xl border border-rose-100 bg-rose-50/70 hover:bg-rose-100 transition p-4 shadow-sm">
                <p class="text-xs font-semibold text-rose-600 uppercase tracking-wide">Clientes Inadimplentes</p>
                <p class="text-2xl font-bold text-rose-700 mt-2">{{ $totais->inadimplentes ?? 0 }}</p>
                <p class="text-xs text-slate-600 mt-1">Comissão pendente de pagamento</p>
            </a>
        </div>
    </div>
@endsection
