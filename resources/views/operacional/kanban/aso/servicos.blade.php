@extends('layouts.operacional')

@section('title', 'Nova Tarefa - Selecionar Serviço')

@section('content')
    <div class="max-w-6xl mx-auto px-6 py-8">

        {{-- Voltar para lista de clientes --}}
        <div class="mb-4">
            <a href="{{ route('operacional.kanban.aso.clientes') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>←</span>
                <span>Voltar ao Painel</span>
            </a>
        </div>

        {{-- Card principal --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            {{-- Cabeçalho azul --}}
            <div class="bg-[color:var(--color-brand-azul)] px-6 py-4">
                <h1 class="text-lg md:text-xl font-semibold text-white mb-1">
                    Selecione o Serviço
                </h1>
                <p class="text-xs md:text-sm text-blue-100">
                    Empresa selecionada:
                    <span class="font-semibold">
                        {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                    </span>
                </p>
            </div>

            {{-- Conteúdo --}}
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    {{-- ASO (ativo) --}}
                    <a href="{{ route('operacional.kanban.aso.create', $cliente) }}"
                       class="group rounded-2xl border border-sky-200 bg-sky-50/80 p-4 flex flex-col justify-between hover:bg-sky-100 transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-sky-500 flex items-center justify-center text-white text-xl mb-1">
                                📅
                            </div>
                            <h2 class="text-sm font-semibold text-sky-900">ASO</h2>
                            <p class="text-xs text-sky-800/80">
                                Atestado de Saúde Ocupacional para colaboradores.
                            </p>
                        </div>
                        <div class="mt-3 text-xs text-sky-800 flex items-center gap-1 font-medium">
                            <span>Selecionar</span>
                            <span>›</span>
                        </div>
                    </a>

                    {{-- PGR --}}
                    <a href="{{ route('operacional.kanban.pgr.tipo', $cliente) }}"
                       class="group rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 flex flex-col justify-between hover:bg-emerald-100 transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center text-white text-xl mb-1">
                                📄
                            </div>
                            <h2 class="text-sm font-semibold text-emerald-900">PGR</h2>
                            <p class="text-xs text-emerald-800/80">
                                Programa de Gerenciamento de Riscos.
                            </p>
                        </div>
                        <div class="mt-3 text-xs text-emerald-800 flex items-center gap-1 font-medium">
                            <span>Selecionar</span>
                            <span>›</span>
                        </div>
                    </a>

                    {{-- PCMSO --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-70">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-purple-500 flex items-center justify-center text-white text-xl mb-1">
                                🩺
                            </div>
                            <h2 class="text-sm font-semibold text-slate-800">PCMSO</h2>
                            <p class="text-xs text-slate-500">
                                Programa de Controle Médico Ocupacional.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] text-slate-400">Em breve.</p>
                    </div>

                    {{-- LTCAT --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-70">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-orange-500 flex items-center justify-center text-white text-xl mb-1">
                                📑
                            </div>
                            <h2 class="text-sm font-semibold text-slate-800">LTCAT</h2>
                            <p class="text-xs text-slate-500">
                                Laudo Técnico das Condições Ambientais.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] text-slate-400">Em breve.</p>
                    </div>

                    {{-- LTIP --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-70">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-red-500 flex items-center justify-center text-white text-xl mb-1">
                                🧾
                            </div>
                            <h2 class="text-sm font-semibold text-slate-800">LTIP</h2>
                            <p class="text-xs text-slate-500">
                                Laudo de Insalubridade e Periculosidade.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] text-slate-400">Em breve.</p>
                    </div>

                    {{-- APR --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-70">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-yellow-500 flex items-center justify-center text-white text-xl mb-1">
                                ⚠️
                            </div>
                            <h2 class="text-sm font-semibold text-slate-800">APR</h2>
                            <p class="text-xs text-slate-500">
                                Análise Preliminar de Riscos.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] text-slate-400">Em breve.</p>
                    </div>

                    {{-- PAE --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-70">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-pink-500 flex items-center justify-center text-white text-xl mb-1">
                                🚨
                            </div>
                            <h2 class="text-sm font-semibold text-slate-800">PAE</h2>
                            <p class="text-xs text-slate-500">
                                Plano de Atendimento a Emergências.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] text-slate-400">Em breve.</p>
                    </div>

                    {{-- Treinamentos NRs --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-70">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-indigo-500 flex items-center justify-center text-white text-xl mb-1">
                                🎓
                            </div>
                            <h2 class="text-sm font-semibold text-slate-800">Treinamentos NRs</h2>
                            <p class="text-xs text-slate-500">
                                Normas regulamentadoras e capacitações.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] text-slate-400">Em breve.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
