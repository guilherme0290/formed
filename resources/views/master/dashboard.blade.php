@extends('layouts.master')
@section('title', 'Painel Master')

@section('content')

    <div class="w-full px-4 md:px-8 py-8 space-y-8">

        {{-- Hero --}}
        <div class="rounded-3xl bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 text-white shadow-xl p-6 md:p-8 flex flex-col gap-4 items-center text-center">
            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.3em] text-indigo-100">
                🔒 Master Control
            </div>
            <h1 class="text-3xl md:text-4xl font-semibold leading-tight">Painel Master</h1>
            <p class="text-sm md:text-base text-indigo-100 max-w-2xl">
                Visão centralizada das principais áreas: acessos, clientes, preços e comissões. Tudo em um só lugar.
            </p>
        </div>
        {{-- Cards m&eacute;tricas --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="rounded-2xl shadow-lg border border-indigo-400/40 p-5 text-center text-white bg-gradient-to-br from-indigo-700 via-blue-600 to-sky-500">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#128101;</div>
                <div class="mt-2 text-xs uppercase tracking-wide text-indigo-100">Clientes Ativos</div>
                <div class="text-3xl font-bold text-white mt-1">
                    {{ $visaoEmpresa['clientes_ativos'] ?? 0 }}
                </div>
                <div class="text-emerald-200 text-xs mt-1">Ativos</div>
            </div>
            <div class="rounded-2xl shadow-lg border border-emerald-400/40 p-5 text-center text-white bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-500">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#128181;</div>
                <div class="mt-2 text-xs uppercase tracking-wide text-emerald-100">Faturamento Global</div>
                <div class="text-3xl font-bold text-white mt-1">
                    R$ {{ number_format($visaoEmpresa['faturamento_global'] ?? 0, 2, ',', '.') }}
                </div>
                <div class="text-emerald-100 text-xs mt-1">Atualizado</div>
            </div>
            <div class="rounded-2xl shadow-lg border border-amber-400/40 p-5 text-center text-white bg-gradient-to-br from-amber-500 via-orange-500 to-rose-500">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#9201;</div>
                <div class="mt-2 text-xs uppercase tracking-wide text-amber-100">Tempo M&eacute;dio</div>
                <div class="text-3xl font-bold text-white mt-1">
                    {{ $visaoEmpresa['tempo_medio'] ?? '-' }}
                </div>
            </div>
            <div class="rounded-2xl shadow-lg border border-sky-400/40 p-5 text-center text-white bg-gradient-to-br from-sky-600 via-cyan-600 to-blue-500">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#128200;</div>
                <div class="mt-2 text-xs uppercase tracking-wide text-sky-100">Servi&ccedil;os Consumidos</div>
                <div class="text-3xl font-bold text-white mt-1">
                    {{ $visaoEmpresa['servicos_consumidos'] ?? 0 }}
                </div>
                <div class="text-sky-100 text-xs mt-1">Total de itens</div>
            </div>
        </div>

        {{-- Acessos rápidos --}}
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Acessos Rápidos</h2>
                    <p class="text-sm text-slate-500">Principais áreas de gestão do Master</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('master.acessos') }}" class="rounded-2xl border border-slate-100 bg-slate-50/70 hover:bg-slate-100 transition shadow-sm p-4 flex items-center gap-3">
                    <span class="h-10 w-10 rounded-xl bg-slate-900/5 text-slate-900 grid place-items-center text-xl">&#128274;</span>
                    <div>
                        <div class="font-semibold text-slate-900">Acessos & Usuários</div>
                        <div class="text-sm text-slate-500">Perfis, permissões e senhas</div>
                    </div>
                </a>

                <a href="{{ route('master.tabela-precos.itens.index') }}" class="rounded-2xl border border-amber-100 bg-amber-50/80 hover:bg-amber-100 transition shadow-sm p-4 flex items-center gap-3">
                    <span class="h-10 w-10 rounded-xl bg-yellow-500/10 text-yellow-600 grid place-items-center text-xl">💰</span>
                    <div>
                        <div class="font-semibold text-slate-900">Tabela de Preços</div>
                        <div class="text-sm text-slate-500">Cadastro e políticas de preço</div>
                    </div>
                </a>

                <a href="{{ route('master.comissoes.index') }}" class="rounded-2xl border border-emerald-100 bg-emerald-50/80 hover:bg-emerald-100 transition shadow-sm p-4 flex items-center gap-3">
                    <span class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-600 grid place-items-center text-xl">💸</span>
                    <div>
                        <div class="font-semibold text-slate-900">Comissões</div>
                        <div class="text-sm text-slate-500">Regras de % e vigências</div>
                    </div>
                </a>

                <a href="{{ route('master.email-caixas.index') }}" class="rounded-2xl border border-sky-100 bg-sky-50/80 hover:bg-sky-100 transition shadow-sm p-4 flex items-center gap-3">
                    <span class="h-10 w-10 rounded-xl bg-sky-500/10 text-sky-600 grid place-items-center text-xl">@</span>
                    <div>
                        <div class="font-semibold text-slate-900">Configuração de Email</div>
                        <div class="text-sm text-slate-500">SMTP e caixas salvas</div>
                    </div>
                </a>

                <a href="{{ route('clientes.index') }}"
                   class="rounded-2xl border border-blue-100 bg-blue-50/80 hover:bg-blue-100 transition shadow-sm p-4 flex items-center gap-3">
                    <span class="h-10 w-10 rounded-xl bg-blue-500/10 text-blue-600 grid place-items-center text-xl">👤</span>
                    <div>
                        <div class="font-semibold text-slate-900">Clientes</div>
                        <div class="text-sm text-slate-500">Cadastro e gestão</div>
                    </div>
                </a>

                <a href="{{ route('master.dashboard') }}" class="rounded-2xl border border-indigo-100 bg-indigo-50/80 hover:bg-indigo-100 transition shadow-sm p-4 flex items-center gap-3">
                    <span class="h-10 w-10 rounded-xl bg-indigo-500/10 text-indigo-600 grid place-items-center text-xl">📊</span>
                    <div>
                        <div class="font-semibold text-slate-900">Painel Master</div>
                        <div class="text-sm text-slate-500">Visão geral e relatórios</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Relatórios avançados --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 space-y-5">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Relatórios Avançados</h3>
                    <p class="text-sm text-slate-500">Insights operacionais e comerciais</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <div class="text-xs font-semibold text-slate-500 mb-2">Métricas Operacionais</div>
                    <ul class="space-y-2">
                        <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                            <span>Taxa de Conclusão</span>
                            <span class="font-semibold text-emerald-600">
                                {{ ($operacionais['taxa_conclusao'] ?? 0) }}%
                            </span>
                        </li>
                        <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                            <span>Tarefas Atrasadas</span>
                            <span class="font-semibold text-rose-600">
                                {{ $operacionais['atrasadas'] ?? 0 }}
                            </span>
                        </li>
                        <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                            <span>SLA Médio</span>
                            <span class="font-semibold text-indigo-600">
                                {{ is_null($operacionais['sla_percentual'] ?? null) ? '—' : ($operacionais['sla_percentual'].'%') }}
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <div class="text-xs font-semibold text-slate-500 mb-2">Métricas Comerciais</div>
                    <ul class="space-y-2">
                        <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                            <span>Ticket Médio</span>
                            <span class="font-semibold">
                                R$ {{ number_format($comerciais['ticket_medio'] ?? 0, 2, ',', '.') }}
                            </span>
                        </li>
                        <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                            <span>Taxa de Conversão</span>
                            <span class="font-semibold text-emerald-600">
                                {{ ($comerciais['taxa_conversao'] ?? 0) }}%
                            </span>
                        </li>
                        <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                            <span>Propostas em Aberto</span>
                            <span class="font-semibold">
                                {{ $comerciais['propostas_em_aberto'] ?? 0 }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection


