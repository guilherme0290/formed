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
            <div class="rounded-3xl mb-5 px-5 md:px-6 py-4 md:py-5
            flex flex-col md:flex-row md:items-center justify-between gap-4
            bg-white border border-slate-200 shadow-sm">

                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[color:var(--color-brand-azul)]/10
                    flex items-center justify-center text-xl">
                        🧩
                    </div>

                    <div>
                        <p class="text-[11px] md:text-xs uppercase tracking-wide text-[color:var(--color-brand-azul)]/80">
                            Operacional • Formed
                        </p>
                        <h1 class="text-base md:text-xl font-semibold text-slate-900 leading-snug">
                            Selecione o serviço para este cliente
                        </h1>
                    </div>
                </div>

                <div class="md:text-right space-y-1">
                    <p class="text-[11px] md:text-xs text-slate-500">
                        Empresa selecionada
                    </p>

                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full
                    bg-[color:var(--color-brand-azul)]/5 border border-[color:var(--color-brand-azul)]/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-[color:var(--color-brand-azul)]"></span>
                        <p class="text-xs md:text-sm font-semibold text-slate-800 max-w-xs truncate">
                            {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Conteúdo --}}
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    {{-- ASO (ativo) --}}
                    {{-- ASO --}}
                    <a href="{{ route('operacional.kanban.aso.create', $cliente) }}"
                       class="group rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-sky-100 p-4
          flex flex-col justify-between hover:from-sky-100 hover:to-sky-200 hover:border-sky-300 hover:shadow-md
          transition">
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
                       class="group rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100 p-4
          flex flex-col justify-between hover:from-emerald-100 hover:to-emerald-200 hover:border-emerald-300 hover:shadow-md
          transition">
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
                    <a href="{{ route('operacional.pcmso.tipo', $cliente) }}"
                       class="group rounded-2xl border border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100 p-4
          flex flex-col justify-between hover:from-purple-100 hover:to-purple-200 hover:border-purple-300 hover:shadow-md
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-purple-500 flex items-center justify-center text-white text-xl mb-1">
                                🩺
                            </div>
                            <h2 class="text-sm font-semibold text-purple-900">PCMSO</h2>
                            <p class="text-xs text-purple-800/80">
                                Programa de Controle Médico Ocupacional.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] text-purple-700 font-medium">
                            Clique para solicitar o PCMSO.
                        </p>
                    </a>

                    {{-- LTCAT --}}
                    <a href="{{ route('operacional.ltcat.tipo', $cliente) }}"
                       class="group rounded-2xl border border-orange-200 bg-gradient-to-br from-orange-50 to-orange-100 p-4
          flex flex-col justify-between hover:from-orange-100 hover:to-orange-200 hover:border-orange-300 hover:shadow-md
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-orange-500 flex items-center justify-center text-white text-xl mb-1">
                                📑
                            </div>
                            <h2 class="text-sm font-semibold text-orange-900">LTCAT</h2>
                            <p class="text-xs text-orange-800/80">
                                Laudo Técnico das Condições Ambientais.
                            </p>
                        </div>

                        <p class="mt-3 text-[11px] text-orange-700 font-semibold">
                            Clique para solicitar o LTCAT.
                        </p>
                    </a>

                    {{-- LTIP --}}
                    <a href="{{ route('operacional.ltip.create', $cliente) }}"
                       class="group rounded-2xl border border-red-200 bg-gradient-to-br from-red-50 to-red-100 p-4
          flex flex-col justify-between hover:from-red-100 hover:to-red-200 hover:border-red-300 hover:shadow-md
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-red-600 flex items-center justify-center text-white text-xl mb-1">
                                ⚠️
                            </div>
                            <h2 class="text-sm font-semibold text-red-900">LTIP</h2>
                            <p class="text-xs text-red-800/80">
                                Laudo de Insalubridade e Periculosidade.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] text-red-700 font-semibold">
                            Clique para solicitar o LTIP.
                        </p>
                    </a>

                    {{-- APR --}}
                    <a href="{{ route('operacional.apr.create', $cliente) }}"
                       class="group rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100 p-4
          flex flex-col justify-between hover:from-amber-100 hover:to-amber-200 hover:border-amber-300 hover:shadow-md
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-amber-600 flex items-center justify-center text-white text-xl mb-1">
                                ⚠️
                            </div>
                            <h2 class="text-sm font-semibold text-amber-900">APR</h2>
                            <p class="text-xs text-amber-800/80">
                                Análise Preliminar de Riscos da atividade.
                            </p>
                        </div>
                    </a>

                    {{-- PAE --}}
                    <a href="{{ route('operacional.pae.create', $cliente) }}"
                       class="group rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 to-rose-100 p-4
          flex flex-col justify-between hover:from-rose-100 hover:to-rose-200 hover:border-rose-300 hover:shadow-md
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-rose-600 flex items-center justify-center text-white text-xl mb-1">
                                🚨
                            </div>
                            <h2 class="text-sm font-semibold text-rose-900">PAE</h2>
                            <p class="text-xs text-rose-800/80">
                                Plano de Atendimento a Emergências.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] text-rose-700">
                            Clique para criar uma nova solicitação de PAE para este cliente.
                        </p>
                    </a>

                    {{-- Treinamentos NRs --}}
                    <a href="{{ route('operacional.treinamentos-nr.create', $cliente) }}"
                       class="group rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-indigo-100 p-4
          flex flex-col justify-between hover:from-indigo-100 hover:to-indigo-200 hover:border-indigo-300 hover:shadow-md
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-indigo-500 flex items-center justify-center text-white text-xl mb-1">
                                🎓
                            </div>
                            <h2 class="text-sm font-semibold text-indigo-900">Treinamentos NRs</h2>
                            <p class="text-xs text-indigo-800/80">
                                Normas regulamentadoras e capacitações.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] text-indigo-700">
                            Clique para criar uma nova solicitação de Treinamento de NRs para este cliente.
                        </p>
                    </a>


                </div>
            </div>
        </div>
    </div>
@endsection
