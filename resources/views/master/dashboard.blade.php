@extends('layouts.master')
@section('title', 'Painel Master')

@section('content')

    <style>
        .agenda-empty-float {
            animation: agendaFloat 3.2s ease-in-out infinite;
        }

        .agenda-empty-tilt {
            animation: agendaTilt 4.8s ease-in-out infinite;
            transform-origin: 50% 60%;
        }

        .agenda-empty-shadow {
            animation: agendaShadow 3.2s ease-in-out infinite;
        }

        .agenda-empty-dot-1 {
            animation: agendaDot 2.3s ease-in-out infinite;
        }

        .agenda-empty-dot-2 {
            animation: agendaDot 2.3s ease-in-out infinite 0.6s;
        }

        @keyframes agendaFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-9px); }
        }

        @keyframes agendaTilt {
            0%, 100% { transform: rotate(-2deg); }
            50% { transform: rotate(2deg); }
        }

        @keyframes agendaShadow {
            0%, 100% { transform: scale(1); opacity: .25; }
            50% { transform: scale(.86); opacity: .15; }
        }

        @keyframes agendaDot {
            0%, 100% { transform: translateY(0px); opacity: .45; }
            50% { transform: translateY(-8px); opacity: .95; }
        }
    </style>

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

            <article class="sm:col-span-2 lg:col-span-2 rounded-2xl shadow-lg border border-indigo-400/40 p-4 text-white bg-gradient-to-br from-indigo-700 via-blue-600 to-sky-500">
                <div class="flex items-center gap-3">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white">&#128197;</div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-indigo-100">Resumo da Agenda</p>
                        <p class="text-xs text-indigo-100">Visao geral das tarefas do master</p>
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-2.5">
                    <div class="rounded-xl bg-white/10 border border-white/20 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-indigo-100 font-semibold">Total de compromissos em aberto</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $agendaKpis['aberto_total'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 border border-white/20 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-indigo-100 font-semibold">Pendentes do dia</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $agendaKpis['pendentes_dia'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 border border-white/20 p-3">
                        <p class="text-[11px] uppercase tracking-wide text-indigo-100 font-semibold">Concluidas do dia</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $agendaKpis['concluidas_dia'] ?? 0 }}</p>
                    </div>
                </div>
            </article>
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

        @if(session('erro'))
            <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
                {{ session('erro') }}
            </div>
        @endif

        <section class="bg-white rounded-xl border border-indigo-100 shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 text-white px-3 py-2 flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-2">
                    <a href="{{ route('master.dashboard', ['agenda_data' => $agendaMesAnterior->toDateString(), 'agenda_dia' => $agendaMesAnterior->copy()->startOfMonth()->toDateString()]) }}"
                       class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold">
                        Mes anterior
                    </a>
                    <form method="GET" action="{{ route('master.dashboard') }}" class="flex items-center gap-2">
                        <input type="month"
                               name="agenda_data"
                               value="{{ $agendaDataSelecionada->format('Y-m') }}"
                               onchange="this.form.submit()"
                               class="px-2.5 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold text-white border border-white/20">
                    </form>
                </div>

                <div class="text-center">
                    <p class="text-[11px] uppercase tracking-[0.2em] text-indigo-100">Mes selecionado</p>
                    <p class="text-xl font-semibold leading-none mt-1">{{ $agendaDataSelecionada->locale('pt_BR')->translatedFormat('F \d\e Y') }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="btnAbrirModalAgenda"
                            class="px-3 py-2 rounded-lg bg-blue-500 hover:bg-blue-400 text-xs font-semibold">
                        Nova tarefa
                    </button>
                    <a href="{{ route('master.dashboard', ['agenda_data' => $agendaMesProximo->toDateString(), 'agenda_dia' => $agendaMesProximo->copy()->startOfMonth()->toDateString()]) }}"
                       class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold">
                        Proximo mes
                    </a>
                </div>
            </div>

            <div class="p-0">
                <div class="grid gap-0 lg:grid-cols-2 items-stretch">
                    <div class="rounded-none border-0 lg:border-r lg:border-slate-200 px-2 md:px-2.5 pt-2 md:pt-2.5 pb-0 h-[390px]">
                        @php
                            $agendaSemanas = max(1, (int) ceil(count($agendaDias) / 7));
                            $labelsSemana = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'];
                        @endphp
                        <div class="h-full grid grid-cols-7 gap-1.5" style="grid-template-rows: repeat({{ $agendaSemanas }}, minmax(0, 1fr));">
                            @foreach ($agendaDias as $dia)
                                @if (!$dia)
                                    <div class="h-full min-h-[68px] rounded-lg border border-transparent bg-slate-50/70"></div>
                                    @continue
                                @endif
                                @php
                                    $dataStr = $dia->toDateString();
                                    $contagens = $agendaContagensPorData[$dataStr] ?? ['pendentes' => 0, 'concluidas' => 0];
                                    $selecionado = $agendaDiaSelecionado === $dataStr;
                                    $mesCurto = mb_strtoupper(rtrim($dia->locale('pt_BR')->translatedFormat('M'), '.'), 'UTF-8');
                                    $labelDiaSemana = $labelsSemana[$dia->dayOfWeek] ?? '';
                                @endphp
                                <button type="button"
                                        class="agenda-dia h-full min-h-[68px] rounded-lg border px-1 py-1 text-center transition {{ $selecionado ? 'bg-indigo-600 border-indigo-600 text-white shadow' : 'bg-slate-50 border-slate-200 text-slate-700 hover:border-blue-300 hover:bg-blue-50/50' }}"
                                        data-date="{{ $dataStr }}"
                                        data-label="{{ $dia->format('d/m/Y') }}">
                                    <div class="text-[9px] font-semibold tracking-wide {{ $selecionado ? 'text-indigo-100' : 'text-slate-500' }}">{{ $labelDiaSemana }}</div>
                                    <div class="text-lg md:text-xl font-bold leading-none mt-0.5">{{ $dia->day }}</div>
                                    <div class="text-[8px] mt-0.5 font-semibold {{ $selecionado ? 'text-indigo-100' : 'text-slate-500' }}">{{ $mesCurto }}</div>
                                    <div class="mt-0.5 flex items-center justify-center gap-1">
                                        @if ($contagens['pendentes'] > 0)
                                            <span class="inline-flex h-1.5 w-1.5 rounded-full {{ $selecionado ? 'bg-amber-300' : 'bg-amber-500' }}"></span>
                                        @endif
                                        @if ($contagens['concluidas'] > 0)
                                            <span class="inline-flex h-1.5 w-1.5 rounded-full {{ $selecionado ? 'bg-emerald-200' : 'bg-emerald-500' }}"></span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <aside class="rounded-none border-0 bg-slate-50/60 overflow-hidden min-h-[390px]">
                        <div class="px-4 py-3 border-b border-slate-200 bg-white/70">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Detalhes do dia</p>
                            <p class="text-lg font-semibold text-slate-800" id="agendaSideLabel">{{ \Carbon\Carbon::parse($agendaDiaSelecionado)->format('d/m/Y') }}</p>
                        </div>
                        <div class="p-3 space-y-3 h-[390px] overflow-y-auto" id="agendaSideConteudo">
                            <div class="h-full min-h-[240px] flex flex-col items-center justify-start pt-6 text-center gap-3">
                                <div class="relative w-[220px] h-[220px] flex items-center justify-center">
                                    <span class="absolute h-20 w-24 rounded-[999px] bg-indigo-900/20 blur-sm agenda-empty-shadow"></span>
                                    <span class="absolute left-16 top-16 h-3 w-3 rounded-full bg-indigo-300 agenda-empty-dot-1"></span>
                                    <span class="absolute right-16 top-20 h-2.5 w-2.5 rounded-full bg-sky-300 agenda-empty-dot-2"></span>
                                    <span class="agenda-empty-float">
                                        <span class="agenda-empty-tilt relative inline-flex h-24 w-24 items-center justify-center rounded-2xl border border-indigo-200 bg-white shadow-lg">
                                            <i class="fa-regular fa-file-lines text-5xl text-indigo-500"></i>
                                        </span>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Nenhum compromisso para esta data.</p>
                                    <p class="text-xs text-slate-500 mt-1">Use o botão "Nova tarefa" para registrar seu próximo lembrete.</p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <div class="hidden">
            @foreach ($agendaTarefasPorData as $dataStr => $tarefasDia)
                <div id="agenda-dia-{{ $dataStr }}" data-has-itens="{{ $tarefasDia->count() > 0 ? '1' : '0' }}">
                    @forelse ($tarefasDia as $tarefa)
                        @php
                            $isConcluida = $tarefa->status === 'CONCLUIDA';
                            $cardClasses = $isConcluida
                                ? 'bg-white border-emerald-200'
                                : 'bg-white border-amber-200';
                            $accentClasses = $isConcluida
                                ? 'border-l-emerald-500'
                                : 'border-l-amber-500';
                        @endphp
                        <div class="rounded-2xl border border-l-4 shadow-sm hover:shadow-lg transition px-4 py-3 space-y-3 {{ $cardClasses }} {{ $accentClasses }}">
                            <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-100">
                                <div class="min-w-0">
                                    <p class="text-base leading-tight font-semibold text-slate-900 truncate">{{ $tarefa->titulo }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap {{ $isConcluida ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {!! $isConcluida ? 'Concluida' : 'Pendente' !!}
                                </span>
                            </div>
                            @if($tarefa->descricao)
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                    {{ $tarefa->descricao }}
                                </div>
                            @endif
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 text-slate-700 px-2.5 py-1">
                                    <i class="fa-regular fa-clock"></i> {{ $tarefa->hora?->format('H:i') ?? '--:--' }}
                                </span>
                                @if($tarefa->cliente)
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-blue-50 text-blue-700 px-2.5 py-1">
                                        <i class="fa-regular fa-user"></i> {{ $tarefa->cliente }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-2 text-xs pt-1">
                                @if (!$isConcluida)
                                    <form method="POST" action="{{ route('master.agenda.concluir', $tarefa) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="agenda_data" value="{{ $agendaDataSelecionada->toDateString() }}">
                                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                            Concluir
                                        </button>
                                    </form>
                                    <button type="button"
                                            class="js-editar-agenda inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 text-white hover:bg-slate-800"
                                            data-id="{{ $tarefa->id }}"
                                            data-titulo="{{ $tarefa->titulo }}"
                                            data-descricao="{{ $tarefa->descricao }}"
                                            data-tipo="{{ $tarefa->tipo }}"
                                            data-prioridade="{{ $tarefa->prioridade }}"
                                            data-data="{{ $tarefa->data?->toDateString() }}"
                                            data-hora="{{ $tarefa->hora?->format('H:i') }}"
                                            data-cliente="{{ $tarefa->cliente }}">
                                        <i class="fa-solid fa-pen text-[10px]"></i>
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('master.agenda.destroy', $tarefa) }}" data-confirm="Remover esta tarefa?">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="agenda_data" value="{{ $agendaDataSelecionada->toDateString() }}">
                                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-rose-200 text-rose-700 hover:bg-rose-50">
                                            <i class="fa-regular fa-trash-can text-[10px]"></i>
                                            Excluir
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">Sem compromissos.</div>
                    @endforelse
                </div>
            @endforeach
        </div>

        <div id="agendaModal" class="fixed inset-0 z-[90] hidden flex items-center justify-center bg-black/50 p-4 overflow-y-auto">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900" id="agendaModalTitle">Nova tarefa</h2>
                    <button type="button" id="btnFecharAgendaModal" class="h-9 w-9 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500">X</button>
                </div>
                <form method="POST" action="{{ route('master.agenda.store') }}" class="p-5 space-y-4" id="agendaForm">
                    @csrf
                    <input type="hidden" name="_method" id="agendaFormMethod" value="POST">
                    <input type="hidden" name="agenda_data" value="{{ $agendaDataSelecionada->toDateString() }}">

                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Titulo *</label>
                        <input name="titulo" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Ex: Retorno com cliente XPTO">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Descricao</label>
                        <textarea name="descricao" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Detalhes ou observacoes"></textarea>
                    </div>
                    <div class="grid md:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-600">Tipo *</label>
                            <select name="tipo" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option>Retorno Cliente</option>
                                <option>Reuniao</option>
                                <option>Follow-up</option>
                                <option selected>Tarefa</option>
                                <option>Outro</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-600">Prioridade *</label>
                            <select name="prioridade" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="Baixa">Baixa</option>
                                <option value="Media" selected>Media</option>
                                <option value="Alta">Alta</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-600">Data *</label>
                            <input type="date" name="data" value="{{ $agendaDiaSelecionado }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-600">Hora</label>
                            <input type="time" name="hora" value="{{ now()->format('H:i') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Cliente (opcional)</label>
                        <input name="cliente" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Nome do cliente">
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" id="btnCancelarAgenda" class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700" id="agendaSubmitBtn">Criar tarefa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('agendaModal');
            const form = document.getElementById('agendaForm');
            const btnAbrir = document.getElementById('btnAbrirModalAgenda');
            const btnFechar = document.getElementById('btnFecharAgendaModal');
            const btnCancelar = document.getElementById('btnCancelarAgenda');
            const title = document.getElementById('agendaModalTitle');
            const submitBtn = document.getElementById('agendaSubmitBtn');
            const methodInput = document.getElementById('agendaFormMethod');
            const storeAction = @json(route('master.agenda.store'));
            const updateActionTemplate = @json(route('master.agenda.update', ['tarefa' => '__ID__']));

            function openModal() {
                if (!modal) return;
                modal.classList.remove('hidden');
            }

            function closeModal() {
                if (!modal) return;
                modal.classList.add('hidden');
                if (form) {
                    form.reset();
                    const dataInput = form.querySelector('[name="data"]');
                    const horaInput = form.querySelector('[name="hora"]');
                    if (dataInput) dataInput.value = '{{ $agendaDiaSelecionado }}';
                    if (horaInput) horaInput.value = '{{ now()->format('H:i') }}';
                }
                if (form) form.action = storeAction;
                if (methodInput) methodInput.value = 'POST';
                if (title) title.textContent = 'Nova tarefa';
                if (submitBtn) submitBtn.textContent = 'Criar tarefa';
            }

            btnAbrir?.addEventListener('click', openModal);
            btnFechar?.addEventListener('click', closeModal);
            btnCancelar?.addEventListener('click', closeModal);
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            document.addEventListener('click', (e) => {
                const target = e.target;
                if (!(target instanceof HTMLElement)) return;
                const btn = target.closest('.js-editar-agenda');
                if (!btn) return;
                const data = btn.dataset;
                if (!form) return;

                form.action = updateActionTemplate.replace('__ID__', data.id || '');
                if (methodInput) methodInput.value = 'PUT';
                if (title) title.textContent = 'Editar tarefa';
                if (submitBtn) submitBtn.textContent = 'Salvar alteracoes';

                const titulo = form.querySelector('[name="titulo"]');
                if (titulo) titulo.value = data.titulo || '';
                const descricao = form.querySelector('[name="descricao"]');
                if (descricao) descricao.value = data.descricao || '';
                const tipo = form.querySelector('[name="tipo"]');
                if (tipo) tipo.value = data.tipo || 'Tarefa';
                const prioridade = form.querySelector('[name="prioridade"]');
                if (prioridade) prioridade.value = data.prioridade || 'Media';
                const dataInput = form.querySelector('[name="data"]');
                if (dataInput) dataInput.value = data.data || '{{ $agendaDiaSelecionado }}';
                const horaInput = form.querySelector('[name="hora"]');
                if (horaInput) horaInput.value = data.hora || '';
                const cliente = form.querySelector('[name="cliente"]');
                if (cliente) cliente.value = data.cliente || '';

                openModal();
            });
        })();
    </script>

    <script>
        (function () {
            const label = document.getElementById('agendaSideLabel');
            const content = document.getElementById('agendaSideConteudo');
            if (!label || !content) return;
            const emptyStateHtml = `
                <div class="h-full min-h-[240px] flex flex-col items-center justify-start pt-6 text-center gap-3">
                    <div class="relative w-[220px] h-[220px] flex items-center justify-center">
                        <span class="absolute h-20 w-24 rounded-[999px] bg-indigo-900/20 blur-sm agenda-empty-shadow"></span>
                        <span class="absolute left-16 top-16 h-3 w-3 rounded-full bg-indigo-300 agenda-empty-dot-1"></span>
                        <span class="absolute right-16 top-20 h-2.5 w-2.5 rounded-full bg-sky-300 agenda-empty-dot-2"></span>
                        <span class="agenda-empty-float">
                            <span class="agenda-empty-tilt relative inline-flex h-24 w-24 items-center justify-center rounded-2xl border border-indigo-200 bg-white shadow-lg">
                                <i class="fa-regular fa-file-lines text-5xl text-indigo-500"></i>
                            </span>
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Nenhum compromisso para esta data.</p>
                        <p class="text-xs text-slate-500 mt-1">Use o botão "Nova tarefa" para registrar seu próximo lembrete.</p>
                    </div>
                </div>
            `;

            function updateSidePanel(dateLabel, html, hasItems) {
                label.textContent = dateLabel;
                content.innerHTML = hasItems ? html : emptyStateHtml;
            }

            document.querySelectorAll('.agenda-dia').forEach(btn => {
                btn.addEventListener('click', () => {
                    const date = btn.dataset.date;
                    const dateLabel = btn.dataset.label || date;
                    const container = document.getElementById('agenda-dia-' + date);
                    const hasItems = !!container && container.dataset.hasItens === '1';
                    updateSidePanel(dateLabel, container ? container.innerHTML : '', hasItems);

                    document.querySelectorAll('.agenda-dia').forEach(item => {
                        item.classList.remove('bg-indigo-600', 'border-indigo-600', 'text-white', 'shadow');
                        item.classList.add('bg-slate-50', 'border-slate-200', 'text-slate-700');
                    });
                    btn.classList.remove('bg-slate-50', 'border-slate-200', 'text-slate-700');
                    btn.classList.add('bg-indigo-600', 'border-indigo-600', 'text-white', 'shadow');
                });
            });

            const initialDate = @json($agendaDiaSelecionado);
            const initialBtn = document.querySelector('.agenda-dia[data-date="' + initialDate + '"]');
            if (initialBtn instanceof HTMLElement) {
                initialBtn.click();
            }
        })();
    </script>

    @if(session('ok'))
        <div id="successPopup" class="fixed inset-0 z-[120] flex items-center justify-center bg-black/35 p-4">
            <div class="w-full max-w-sm rounded-2xl border border-emerald-200 bg-white shadow-xl overflow-hidden">
                <div class="px-4 py-3 bg-emerald-50 border-b border-emerald-100">
                    <p class="text-sm font-semibold text-emerald-700">Sucesso</p>
                </div>
                <div class="px-4 py-4 space-y-3">
                    <p class="text-sm text-slate-700">{{ session('ok') }}</p>
                    <div class="flex justify-end">
                        <button type="button" id="btnCloseSuccessPopup"
                                class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                            Ok
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const popup = document.getElementById('successPopup');
            const closeBtn = document.getElementById('btnCloseSuccessPopup');
            if (!popup) return;

            function closePopup() {
                popup.classList.add('hidden');
            }

            closeBtn?.addEventListener('click', closePopup);
            popup.addEventListener('click', (e) => {
                if (e.target === popup) closePopup();
            });
            setTimeout(closePopup, 2400);
        })();
    </script>
@endsection
