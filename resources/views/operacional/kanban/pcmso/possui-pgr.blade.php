@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')


@php
    $titulo = $tipo === 'especifico' ? 'PCMSO - Específico' : 'PCMSO - Matriz';
@endphp

@section('pageTitle', $titulo)

@section('content')
    <div class="container mx-auto px-4 py-8">
        <a href="{{ route('operacional.pcmso.tipo', $cliente) }}"
           class="inline-flex items-center gap-2 text-xs text-slate-600 mb-4">
            ← Voltar ao Início
        </a>

        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-fuchsia-500 text-white">
                <h1 class="text-lg font-semibold">{{ $titulo }}</h1>
                <p class="text-xs text-purple-100 mt-1">
                    Empresa: <span class="font-semibold">{{ $cliente->razao_social ?? $cliente->nome }}</span>
                </p>
            </div>

            <div class="p-6 space-y-4">
                <div class="border border-amber-200 bg-amber-50 rounded-xl px-4 py-3 text-xs text-amber-800 flex gap-3">
                    <span>⚠️</span>
                    <div>
                        <p class="font-semibold">Para fazer o PCMSO é necessário ter o PGR</p>
                        <p class="mt-1 text-[11px]">
                            O PGR (Programa de Gerenciamento de Riscos) é pré-requisito para elaboração do PCMSO.
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-sm font-semibold text-slate-800 mb-3">
                        Você já possui o PGR?
                    </p>

                    <div class="space-y-3">
                        {{-- Sim, tenho PGR --}}
                        <a href="{{ route('operacional.pcmso.create-com-pgr', [$cliente, $tipo]) }}"
                           class="block w-full text-center rounded-lg bg-emerald-500 hover:bg-emerald-600
                                  text-white text-sm font-semibold py-2.5 transition">
                            ✅ Sim, tenho o PGR e vou inserir
                        </a>

                        {{-- Não, quero que a FORMED faça o PGR --}}
                        <a href="{{ route('operacional.kanban.pgr.tipo', $cliente) }}"
                           class="block w-full text-center rounded-lg border border-rose-300 text-rose-600
                                  text-sm font-semibold py-2.5 hover:bg-rose-50 transition">
                            Não, solicitar que a FORMED realize o PGR
                        </a>

                        <a href="{{ route('operacional.pcmso.tipo', $cliente) }}"
                           class="block w-full text-center rounded-lg bg-slate-50 border border-slate-200
                                  text-slate-600 text-sm font-semibold py-2.5 hover:bg-slate-100 transition">
                            Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
