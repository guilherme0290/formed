@extends('layouts.comercial')
@section('title', 'Painel Comercial')

@section('content')
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-8">

        {{-- Título --}}
        <header>
            <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Painel Comercial</h1>
            <p class="text-slate-500 text-sm md:text-base mt-1">
                Gerencie propostas, contratos e comissões.
            </p>
        </header>

        {{-- RANKING DE VENDEDORES --}}
        <section>
            <div class="rounded-2xl shadow-lg overflow-hidden bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700">
                {{-- barra título --}}
                <div class="px-5 md:px-8 py-3 border-b border-white/10 flex items-center justify-between">
                    <h2 class="text-sm md:text-base font-semibold text-white">
                        🏅 Ranking de Vendedores – Outubro 2025
                    </h2>
                    <span class="hidden md:inline text-xs text-blue-100">
                        Atualizado automaticamente conforme o faturamento do mês
                    </span>
                </div>

                {{-- cards do ranking --}}
                <div class="px-4 md:px-6 py-4 md:py-5">
                    <div class="grid gap-4 md:grid-cols-3">
                        {{-- 1º lugar --}}
                        <article class="bg-gradient-to-br from-yellow-400 via-amber-400 to-orange-300 rounded-xl shadow-md p-4 md:p-5 text-white flex flex-col justify-between">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide">1º Lugar</p>
                                    <p class="text-lg md:text-xl font-bold mt-1">Fernando</p>
                                </div>
                                <span class="text-3xl font-black opacity-60 leading-none">1º</span>
                            </div>
                            <div class="mt-4">
                                <p class="text-xs uppercase tracking-wide text-white/80">Faturamento</p>
                                <p class="text-2xl font-semibold leading-tight">R$ 50.000</p>
                            </div>
                        </article>

                        {{-- 2º lugar --}}
                        <article class="bg-gradient-to-br from-slate-200 via-slate-300 to-slate-400 rounded-xl shadow-md p-4 md:p-5 text-slate-800 flex flex-col justify-between">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-700">2º Lugar</p>
                                    <p class="text-lg md:text-xl font-bold mt-1">Wesley</p>
                                </div>
                                <span class="text-3xl font-black text-slate-500/70 leading-none">2º</span>
                            </div>
                            <div class="mt-4">
                                <p class="text-xs uppercase tracking-wide text-slate-600">Faturamento</p>
                                <p class="text-2xl font-semibold leading-tight">R$ 40.000</p>
                            </div>
                        </article>

                        {{-- 3º lugar --}}
                        <article class="bg-gradient-to-br from-orange-400 via-orange-500 to-amber-500 rounded-xl shadow-md p-4 md:p-5 text-white flex flex-col justify-between">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide">3º Lugar</p>
                                    <p class="text-lg md:text-xl font-bold mt-1">Geovany</p>
                                </div>
                                <span class="text-3xl font-black opacity-70 leading-none">3º</span>
                            </div>
                            <div class="mt-4">
                                <p class="text-xs uppercase tracking-wide text-white/80">Faturamento</p>
                                <p class="text-2xl font-semibold leading-tight">R$ 30.000</p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        {{-- MENU PRINCIPAL --}}
        <section class="space-y-4">
            {{-- faixa título --}}
            <div class="rounded-2xl bg-slate-900 text-white px-5 md:px-6 py-3 shadow">
                <h2 class="text-sm md:text-base font-semibold">Menu Principal</h2>
            </div>

            <div class="bg-white rounded-2xl shadow border border-slate-100 px-4 md:px-6 py-5 space-y-6">
                {{-- Linha 1 --}}
                <div class="grid gap-4 md:grid-cols-5">
                    {{-- Criar Proposta --}}
                    <a href="{{ route('comercial.propostas.index') }}"
                       class="group rounded-2xl border border-blue-100 bg-blue-50/80 hover:bg-blue-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500 text-white mb-3">
                                +
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Criar Proposta</h3>
                            <p class="text-xs text-slate-500 mt-1">Nova proposta comercial</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-blue-700 group-hover:text-blue-800">
                            Acessar →
                        </span>
                    </a>

                    {{-- Gerar Apresentação --}}
                    <a href="{{ route('comercial.apresentacao.cliente') }}"
                       class="group rounded-2xl border border-emerald-100 bg-emerald-50/80 hover:bg-emerald-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500 text-white mb-3">
                                📺
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Gerar Apresentação</h3>
                            <p class="text-xs text-slate-500 mt-1">Apresentação comercial</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-emerald-700 group-hover:text-emerald-800">
                            Acessar →
                        </span>
                    </a>

                    {{-- Tabela de Preços --}}
                    <a href="{{ route('comercial.tabela-precos.index') }}"
                       class="group rounded-2xl border border-green-100 bg-green-50/80 hover:bg-green-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-green-500 text-white mb-3">
                                📄
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Tabela de Preços</h3>
                            <p class="text-xs text-slate-500 mt-1">Consultar valores</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-green-700 group-hover:text-green-800">
                            Acessar →
                        </span>
                    </a>

                    {{-- Contratos --}}
                    <a href="#"
                       class="group rounded-2xl border border-purple-100 bg-purple-50/80 hover:bg-purple-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500 text-white mb-3">
                                📑
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Contratos</h3>
                            <p class="text-xs text-slate-500 mt-1">Gerenciar contratos</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-purple-700 group-hover:text-purple-800">
                            Acessar →
                        </span>
                    </a>

                    {{-- Minhas Comissões --}}
                    <a href="#"
                       class="group rounded-2xl border border-amber-100 bg-amber-50/80 hover:bg-amber-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500 text-white mb-3">
                                $
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Minhas Comissões</h3>
                            <p class="text-xs text-slate-500 mt-1">Ver comissões</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-amber-700 group-hover:text-amber-800">
                            Acessar →
                        </span>
                    </a>
                </div>

                {{-- Linha 2 --}}
                <div class="grid gap-4 md:grid-cols-5">
                    {{-- Acompanhamento --}}
                    <a href="#"
                       class="group rounded-2xl border border-rose-100 bg-rose-50/80 hover:bg-rose-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500 text-white mb-3">
                                📈
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Acompanhamento</h3>
                            <p class="text-xs text-slate-500 mt-1">Pipeline de vendas</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-rose-700 group-hover:text-rose-800">
                            Acessar →
                        </span>
                    </a>

                    {{-- Agenda --}}
                    <a href="#"
                       class="group rounded-2xl border border-indigo-100 bg-indigo-50/80 hover:bg-indigo-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500 text-white mb-3">
                                🗓️
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Agenda</h3>
                            <p class="text-xs text-slate-500 mt-1">Calendário e tarefas</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-indigo-700 group-hover:text-indigo-800">
                            Acessar →
                        </span>
                    </a>

                    {{-- Espaços vazios só pra manter o grid alinhado --}}
                    <div class="hidden md:block"></div>
                    <div class="hidden md:block"></div>
                    <div class="hidden md:block"></div>
                </div>
            </div>
        </section>

        {{-- GESTÃO & RELATÓRIOS --}}
        <section class="space-y-4">
            <div class="rounded-2xl bg-slate-900 text-white px-5 md:px-6 py-3 shadow">
                <h2 class="text-sm md:text-base font-semibold">Gestão & Relatórios</h2>
            </div>

            <div class="bg-white rounded-2xl shadow border border-slate-100 px-4 md:px-6 py-5">
                <div class="grid gap-4 md:grid-cols-4">
                    {{-- Clientes & Leads --}}
                    <a href="#"
                       class="group rounded-2xl border border-cyan-100 bg-cyan-50/80 hover:bg-cyan-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-500 text-white mb-3">
                                👥
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Clientes & Leads</h3>
                            <p class="text-xs text-slate-500 mt-1">CRM completo</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-cyan-700 group-hover:text-cyan-800">
                            Acessar →
                        </span>
                    </a>

                    {{-- Relatórios e KPIs --}}
                    <a href="#"
                       class="group rounded-2xl border border-emerald-100 bg-emerald-50/80 hover:bg-emerald-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500 text-white mb-3">
                                📊
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Relatórios e KPIs</h3>
                            <p class="text-xs text-slate-500 mt-1">Indicadores e métricas</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-emerald-700 group-hover:text-emerald-800">
                            Acessar →
                        </span>
                    </a>

                    {{-- Gestão Contratos --}}
                    <a href="#"
                       class="group rounded-2xl border border-violet-100 bg-violet-50/80 hover:bg-violet-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-violet-500 text-white mb-3">
                                📃
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Gestão Contratos</h3>
                            <p class="text-xs text-slate-500 mt-1">Contratos ativos</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-violet-700 group-hover:text-violet-800">
                            Acessar →
                        </span>
                    </a>

                    {{-- Parceiros --}}
                    <a href="#"
                       class="group rounded-2xl border border-pink-100 bg-pink-50/80 hover:bg-pink-100 transition shadow-sm p-4 flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-pink-500 text-white mb-3">
                                🤝
                            </div>
                            <h3 class="text-sm font-semibold text-slate-800">Parceiros</h3>
                            <p class="text-xs text-slate-500 mt-1">Gestão de parceiros</p>
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-pink-700 group-hover:text-pink-800">
                            Acessar →
                        </span>
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
