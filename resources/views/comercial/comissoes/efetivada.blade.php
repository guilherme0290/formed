@extends('layouts.comercial')
@section('title', 'Comissão Efetivada')

@section('content')
    @php
        $mesNome = \Carbon\Carbon::createFromDate($ano, $mes, 1)->locale('pt_BR')->isoFormat('MMMM');
    @endphp

    <div class="max-w-6xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="space-y-1">
                <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.18em] text-emerald-600">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">$</span>
                    Comissão Efetivada
                </div>
                <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">{{ ucfirst($mesNome) }} / {{ $ano }}</h1>
                <p class="text-sm text-slate-500">Pagamentos confirmados por cliente.</p>
            </div>
            <a href="{{ route('comercial.comissoes.mes', ['ano' => $ano, 'mes' => $mes]) }}"
               class="text-sm text-slate-600 hover:text-slate-800 flex items-center gap-2">
                ← Voltar para resumo
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <div class="text-sm font-semibold text-emerald-600">Clientes pagos</div>
                <div class="text-xs text-slate-500">Valores efetivamente recebidos</div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($clientes as $cliente)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $cliente->cliente->nome_fantasia ?? $cliente->cliente->razao_social ?? 'Cliente #' . $cliente->cliente_id }}</p>
                            <p class="text-xs text-slate-500">Comissão confirmada</p>
                        </div>
                        <div class="text-lg font-semibold text-emerald-700">R$ {{ number_format($cliente->total, 2, ',', '.') }}</div>
                    </div>
                @empty
                    <div class="px-5 py-4 text-sm text-slate-500">Nenhuma comissão efetivada para este mês.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
