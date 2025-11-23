@extends('layouts.operacional')

@section('title', 'PGR - Selecione o Tipo')

@section('content')
    <div class="max-w-3xl mx-auto px-4 md:px-8 py-8">

        <div class="mb-4">
            <a href="{{ route('operacional.kanban.servicos', $cliente) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>←</span>
                <span>Voltar</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-emerald-700 px-6 py-4">
                <h1 class="text-lg md:text-xl font-semibold text-white mb-1">
                    PGR - Selecione o Tipo
                </h1>
                <p class="text-xs md:text-sm text-emerald-100">
                    Empresa:
                    <span class="font-semibold">
                        {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                    </span>
                </p>
            </div>

            <div class="px-6 py-6 space-y-4">
                <a href="{{ route('operacional.kanban.pgr.create', ['cliente' => $cliente, 'tipo' => 'matriz']) }}"
                   class="block rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4 hover:bg-sky-100 transition">
                    <h2 class="text-sm font-semibold text-slate-800 mb-1">PGR - Matriz</h2>
                    <p class="text-xs text-slate-500">
                        Para a sede/matriz da empresa
                    </p>
                </a>

                <a href="{{ route('operacional.kanban.pgr.create', ['cliente' => $cliente, 'tipo' => 'especifico']) }}"
                   class="block rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 hover:bg-emerald-100 transition">
                    <h2 class="text-sm font-semibold text-slate-800 mb-1">PGR - Específico</h2>
                    <p class="text-xs text-slate-500">
                        Para obra ou local específico
                    </p>
                </a>
            </div>
        </div>
    </div>
@endsection
