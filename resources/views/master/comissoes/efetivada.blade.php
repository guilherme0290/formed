@extends('layouts.master')
@section('title', 'Comiss&atilde;o Efetivada')

@section('content')
    @php
        $mesNome = \Carbon\Carbon::createFromDate($ano, $mes, 1)->locale('pt_BR')->isoFormat('MMMM');
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-5">
        <header class="rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 px-4 py-3 text-white">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-indigo-100">Comiss&atilde;o Efetivada</p>
                    <h1 class="text-xl md:text-2xl font-semibold">{{ ucfirst($mesNome) }} / {{ $ano }}</h1>
                    <p class="text-xs text-indigo-100 mt-1">Pagamentos confirmados por cliente.</p>
                </div>
                <a href="{{ route('master.comissoes.vendedores', ['ano' => $ano, 'vendedor' => $vendedorId]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold hover:bg-white/20">
                    Voltar para Meses
                </a>
            </div>
        </header>

        <div class="bg-white rounded-xl border border-indigo-100 shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                <div class="text-sm font-semibold text-indigo-700">Clientes pagos</div>
                <div class="text-xs text-slate-500">Valores efetivamente recebidos</div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($clientes as $cliente)
                    <div class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $cliente->cliente->nome_fantasia ?? $cliente->cliente->razao_social ?? 'Cliente #' . $cliente->cliente_id }}</p>
                            <p class="text-xs text-slate-500">Comiss&atilde;o confirmada</p>
                        </div>
                        <div class="text-lg font-semibold text-blue-800">R$ {{ number_format($cliente->total, 2, ',', '.') }}</div>
                    </div>
                @empty
                    <div class="px-4 py-4 text-sm text-slate-500">Nenhuma comiss&atilde;o efetivada para este m&ecirc;s.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection



