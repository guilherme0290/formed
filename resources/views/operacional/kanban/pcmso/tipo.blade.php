@extends('layouts.operacional')

@section('pageTitle', 'PCMSO - Selecione o Tipo')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <a href="{{ route('operacional.kanban.servicos', $cliente) }}"
           class="inline-flex items-center gap-2 text-xs text-slate-600 mb-4">
            ← Voltar
        </a>

        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-fuchsia-500 text-white">
                <h1 class="text-lg font-semibold">PCMSO - Selecione o Tipo</h1>
                <p class="text-xs text-purple-100 mt-1">
                    Empresa: <span class="font-semibold">{{ $cliente->razao_social ?? $cliente->nome }}</span>
                </p>
            </div>

            <div class="p-6 space-y-4">
                <a href="{{ route('operacional.pcmso.possui-pgr', [$cliente, 'matriz']) }}"
                   class="block rounded-xl border border-indigo-200 hover:border-indigo-400 hover:bg-indigo-50
                          px-4 py-3 transition">
                    <h2 class="text-sm font-semibold text-slate-800">PCMSO - Matriz</h2>
                    <p class="text-xs text-slate-500 mt-1">Para a sede/matriz da empresa</p>
                </a>

                <a href="{{ route('operacional.pcmso.possui-pgr', [$cliente, 'especifico']) }}"
                   class="block rounded-xl border border-purple-200 hover:border-purple-400 hover:bg-purple-50
                          px-4 py-3 transition">
                    <h2 class="text-sm font-semibold text-slate-800">PCMSO - Específico</h2>
                    <p class="text-xs text-slate-500 mt-1">Para obra ou local específico</p>
                </a>
            </div>
        </div>
    </div>
@endsection
