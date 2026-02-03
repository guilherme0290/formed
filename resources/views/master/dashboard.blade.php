@extends('layouts.master')
@section('title', 'Painel Master')

@section('content')

    <div class="w-full px-4 md:px-8 py-8 space-y-8">

{{--        --}}{{-- Hero --}}
{{--        <div class="rounded-3xl bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 text-white shadow-xl p-6 md:p-8 flex flex-col gap-4 items-center text-center"--}}
{{--             data-dashboard-card="hero">--}}
{{--            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.3em] text-indigo-100">--}}
{{--                🔒 Master Control--}}
{{--            </div>--}}
{{--            <h1 class="text-3xl md:text-4xl font-semibold leading-tight">Painel Master</h1>--}}
{{--            <p class="text-sm md:text-base text-indigo-100 max-w-2xl">--}}
{{--                Visão centralizada das principais áreas: acessos, clientes, preços e comissões. Tudo em um só lugar.--}}
{{--            </p>--}}
{{--        </div>--}}
        {{-- Cards m&eacute;tricas --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="rounded-2xl shadow-lg border border-indigo-400/40 p-5 text-center text-white bg-gradient-to-br from-indigo-700 via-blue-600 to-sky-500"
                 data-dashboard-card="clientes-ativos">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#128101;</div>
                <div class="mt-2 text-xs uppercase tracking-wide text-indigo-100">Clientes Ativos</div>
                <div class="text-3xl font-bold text-white mt-1">
                    {{ $visaoEmpresa['clientes_ativos'] ?? 0 }}
                </div>
                <div class="text-emerald-200 text-xs mt-1">Ativos</div>
            </div>
            <div class="rounded-2xl shadow-lg border border-emerald-400/40 p-5 text-center text-white bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-500"
                 data-dashboard-card="faturamento-global">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#128181;</div>
                <div class="mt-2 text-xs uppercase tracking-wide text-emerald-100">Faturamento Global</div>
                <div class="text-3xl font-bold text-white mt-1">
                    R$ {{ number_format($visaoEmpresa['faturamento_global'] ?? 0, 2, ',', '.') }}
                </div>
                <div class="text-emerald-100 text-xs mt-1">Atualizado</div>
            </div>
{{--            <div class="rounded-2xl shadow-lg border border-amber-400/40 p-5 text-center text-white bg-gradient-to-br from-amber-500 via-orange-500 to-rose-500"--}}
{{--                 data-dashboard-card="tempo-medio">--}}
{{--                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#9201;</div>--}}
{{--                <div class="mt-2 text-xs uppercase tracking-wide text-amber-100">Tempo M&eacute;dio</div>--}}
{{--                <div class="text-3xl font-bold text-white mt-1">--}}
{{--                    {{ $visaoEmpresa['tempo_medio'] ?? '-' }}--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="rounded-2xl shadow-lg border border-sky-400/40 p-5 text-center text-white bg-gradient-to-br from-sky-600 via-cyan-600 to-blue-500"--}}
{{--                 data-dashboard-card="servicos-consumidos">--}}
{{--                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#128200;</div>--}}
{{--                <div class="mt-2 text-xs uppercase tracking-wide text-sky-100">Servi&ccedil;os Consumidos</div>--}}
{{--                <div class="text-3xl font-bold text-white mt-1">--}}
{{--                    {{ $visaoEmpresa['servicos_consumidos'] ?? 0 }}--}}
{{--                </div>--}}
{{--                <div class="text-sky-100 text-xs mt-1">Total de itens</div>--}}
{{--            </div>--}}
            <a href="{{ route('financeiro.faturamento-detalhado', [
                'status' => 'pendente',
                'filtrar' => 1,
                'data_inicio' => now()->subMonth()->format('Y-m-d'),
                'data_fim' => now()->format('Y-m-d'),
            ]) }}"
               class="rounded-2xl shadow-lg border border-amber-400/40 p-5 text-center text-white bg-gradient-to-br from-amber-500 via-orange-500 to-rose-500 hover:opacity-95 transition"
               data-dashboard-card="financeiro-pendente">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#128184;</div>
                <div class="mt-2 text-xs uppercase tracking-wide text-amber-100">Faturamento Pendente</div>
                <div class="text-3xl font-bold text-white mt-1">
                    R$ {{ number_format($financeiro['total_aberto'] ?? 0, 2, ',', '.') }}
                </div>
                <div class="text-amber-100 text-xs mt-1">Clique para detalhar</div>
            </a>
            <a href="{{ route('financeiro.faturamento-detalhado', [
                'status' => 'recebido',
                'filtrar' => 1,
                'data_inicio' => now()->subMonth()->format('Y-m-d'),
                'data_fim' => now()->format('Y-m-d'),
            ]) }}"
               class="rounded-2xl shadow-lg border border-emerald-400/40 p-5 text-center text-white bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-500 hover:opacity-95 transition"
               data-dashboard-card="financeiro-recebido">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#128176;</div>
                <div class="mt-2 text-xs uppercase tracking-wide text-emerald-100">Faturamento Recebido</div>
                <div class="text-3xl font-bold text-white mt-1">
                    R$ {{ number_format($financeiro['total_recebido'] ?? 0, 2, ',', '.') }}
                </div>
                <div class="text-emerald-100 text-xs mt-1">Clique para detalhar</div>
            </a>
            <a href="{{ route('master.agendamentos') }}"
               class="rounded-2xl shadow-lg border border-emerald-400/40 p-5 text-center text-white bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-500 hover:opacity-95 transition"
               data-dashboard-card="agendamentos-dia">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#128197;</div>
                <div class="mt-2 text-xs uppercase tracking-wide text-emerald-100">Agendamentos do dia</div>
                <div class="text-3xl font-bold text-white mt-1">
                    {{ $agendamentosHoje['total'] ?? 0 }}
                </div>
                <div class="text-emerald-100 text-xs mt-1">
                    Abertas: {{ $agendamentosHoje['abertas'] ?? 0 }} • Fechadas: {{ $agendamentosHoje['fechadas'] ?? 0 }}
                </div>
            </a>
            <a href="{{ route('master.relatorios', ['setor' => 'operacional']) }}"
               class="rounded-2xl shadow-lg border border-indigo-400/40 p-5 text-center text-white bg-gradient-to-br from-indigo-700 via-violet-600 to-purple-500 hover:opacity-95 transition"
               data-dashboard-card="relatorios-master">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white mx-auto">&#128202;</div>
                <div class="mt-2 text-xs uppercase tracking-wide text-indigo-100">Relatório</div>
                <div class="text-lg font-semibold text-white mt-1">
                    Relatórios Master
                </div>
                <div class="text-indigo-100 text-xs mt-1">
                    Produtividade + tarefas + PDF
                </div>
            </a>
        </div>

        {{-- Relatórios avançados --}}
{{--        <div class="bg-sky-50 rounded-3xl shadow-sm border border-sky-100 p-6 space-y-5"--}}
{{--             data-dashboard-card="relatorios-avancados">--}}
{{--            <div class="flex items-center justify-center">--}}
{{--                <div class="text-center">--}}
{{--                    <h3 class="text-lg font-semibold text-slate-900 text-center">Relatórios Avançados</h3>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <div class="grid md:grid-cols-2 gap-6">--}}
{{--                <div class="space-y-2">--}}
{{--                    <div class="mb-2 flex justify-center">--}}
{{--                        <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white shadow-sm">--}}
{{--                            Métricas Operacionais--}}
{{--                        </span>--}}
{{--                    </div>--}}
{{--                    <ul class="space-y-2">--}}
{{--                        <li>--}}
{{--                            <a href="{{ route('master.relatorios', ['setor' => 'operacional', 'aba' => 'operacional']) }}"--}}
{{--                               class="flex items-center justify-between bg-white/70 rounded-xl px-4 py-2 border border-transparent shadow-sm transition duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md hover:border-sky-200">--}}
{{--                                <span>Taxa de Conclusão</span>--}}
{{--                                <span class="font-semibold text-emerald-600">--}}
{{--                                    {{ ($operacionais['taxa_conclusao'] ?? 0) }}%--}}
{{--                                </span>--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                        <li>--}}
{{--                            <a href="{{ route('master.relatorios', ['setor' => 'operacional', 'aba' => 'operacional']) }}"--}}
{{--                               class="flex items-center justify-between bg-white/70 rounded-xl px-4 py-2 border border-transparent shadow-sm transition duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md hover:border-sky-200">--}}
{{--                                <span>Tarefas Atrasadas</span>--}}
{{--                                <span class="font-semibold text-rose-600">--}}
{{--                                    {{ $operacionais['atrasadas'] ?? 0 }}--}}
{{--                                </span>--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                        <li>--}}
{{--                            <a href="{{ route('master.relatorios', ['setor' => 'operacional', 'aba' => 'operacional']) }}"--}}
{{--                               class="flex items-center justify-between bg-white/70 rounded-xl px-4 py-2 border border-transparent shadow-sm transition duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md hover:border-sky-200">--}}
{{--                                <span>SLA Médio</span>--}}
{{--                                <span class="font-semibold text-indigo-600">--}}
{{--                                    {{ is_null($operacionais['sla_percentual'] ?? null) ? '—' : ($operacionais['sla_percentual'].'%') }}--}}
{{--                                </span>--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                    </ul>--}}
{{--                </div>--}}

{{--                <div class="space-y-2">--}}
{{--                    <div class="mb-2 flex justify-center">--}}
{{--                        <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white shadow-sm">--}}
{{--                            Métricas Comerciais--}}
{{--                        </span>--}}
{{--                    </div>--}}
{{--                    <ul class="space-y-2">--}}
{{--                        <li>--}}
{{--                            <a href="{{ route('master.relatorios', ['setor' => 'operacional', 'aba' => 'comercial']) }}"--}}
{{--                               class="flex items-center justify-between bg-white/70 rounded-xl px-4 py-2 border border-transparent shadow-sm transition duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md hover:border-sky-200">--}}
{{--                                <span>Ticket Médio</span>--}}
{{--                                <span class="font-semibold">--}}
{{--                                    R$ {{ number_format($comerciais['ticket_medio'] ?? 0, 2, ',', '.') }}--}}
{{--                                </span>--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                        <li>--}}
{{--                            <a href="{{ route('master.relatorios', ['setor' => 'operacional', 'aba' => 'comercial']) }}"--}}
{{--                               class="flex items-center justify-between bg-white/70 rounded-xl px-4 py-2 border border-transparent shadow-sm transition duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md hover:border-sky-200">--}}
{{--                                <span>Taxa de Conversão</span>--}}
{{--                                <span class="font-semibold text-emerald-600">--}}
{{--                                    {{ ($comerciais['taxa_conversao'] ?? 0) }}%--}}
{{--                                </span>--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                        <li>--}}
{{--                            <a href="{{ route('master.relatorios', ['setor' => 'operacional', 'aba' => 'comercial']) }}"--}}
{{--                               class="flex items-center justify-between bg-white/70 rounded-xl px-4 py-2 border border-transparent shadow-sm transition duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md hover:border-sky-200">--}}
{{--                                <span>Propostas em Aberto</span>--}}
{{--                                <span class="font-semibold">--}}
{{--                                    {{ $comerciais['propostas_em_aberto'] ?? 0 }}--}}
{{--                                </span>--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                    </ul>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
    </div>
@endsection
