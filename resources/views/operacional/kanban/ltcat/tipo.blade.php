@extends('layouts.operacional')

@section('pageTitle', 'LTCAT - Selecione o Tipo')

@section('content')
    @php
        // pega da view (vindo do controller) ou da querystring
        $origem = $origem ?? request()->query('origem');

        $rotaVoltar = $origem === 'cliente'
            ? route('cliente.dashboard')
            : route('operacional.kanban.servicos', $cliente);
    @endphp

    <div class="container mx-auto px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ $rotaVoltar }}"
               class="inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50">
                ← Voltar
            </a>
        </div>

        <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-orange-600 to-orange-700 text-white">
                <h1 class="text-lg font-semibold">LTCAT - Selecione o Tipo</h1>
                <p class="text-xs text-white/80 mt-1">
                    Empresa:
                    <span class="font-semibold">
                        {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                    </span>
                </p>
            </div>

            <div class="p-6 space-y-4">

                <div class="space-y-1 text-xs text-slate-500 mb-3">
                    <p>Escolha se o LTCAT será referente à matriz da empresa ou a um local/obra específico.</p>
                </div>

                {{-- Matriz --}}
                <a href="{{ route('operacional.ltcat.create', [
                        'cliente' => $cliente,
                        'tipo'    => 'matriz',
                        'origem'  => $origem,
                    ]) }}"
                   class="block rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 hover:border-sky-400 hover:bg-sky-50 transition">
                    <h2 class="text-sm font-semibold text-slate-800">LTCAT - Matriz</h2>
                    <p class="text-xs text-slate-500">
                        Para a sede/matriz da empresa
                    </p>
                </a>

                {{-- Específico --}}
                <a href="{{ route('operacional.ltcat.create', [
                        'cliente' => $cliente,
                        'tipo'    => 'especifico',
                        'origem'  => $origem,
                    ]) }}"
                   class="block rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 hover:border-orange-400 hover:bg-orange-50 transition">
                    <h2 class="text-sm font-semibold text-slate-800">LTCAT - Específico</h2>
                    <p class="text-xs text-slate-500">
                        Para obra ou local específico
                    </p>
                </a>
            </div>
        </div>
    </div>
@endsection
