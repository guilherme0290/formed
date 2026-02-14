@extends('layouts.master')
@section('title', 'Agendamentos e Tarefas')

@section('content')
    @php
        $dataRelatorio = \Carbon\Carbon::parse($agendamentos['data_relatorio'] ?? $agendamentos['data_inicio'] ?? now()->toDateString());
        $selectedDateStr = $dataRelatorio->toDateString();
        $janelaInicio = ($agendamentos['janela_inicio'] ?? $dataRelatorio->copy()->startOfMonth())->copy();
        $janelaFim = ($agendamentos['janela_fim'] ?? $dataRelatorio->copy()->endOfMonth())->copy();
        $resumoPorDia = $agendamentos['resumo_por_dia'] ?? [];
        $filtrosBase = [
            'data_inicio' => $agendamentos['data_inicio'] ?? null,
            'data_fim' => $agendamentos['data_fim'] ?? null,
            'servico' => $agendamentos['servico_selecionado'] ?? 'todos',
            'responsavel' => $agendamentos['responsavel_selecionado'] ?? 'todos',
            'filtro_prestados' => $agendamentos['filtro_prestados'] ?? 'finalizadas',
        ];
        $mesSelecionado = (int) $dataRelatorio->month;
        $anoSelecionado = (int) $dataRelatorio->year;
        $anosDisponiveis = range(max(((int) now()->year - 5), 2020), 2050);
        $mesesDisponiveis = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Marco',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];
        $janelaDias = [];
        for ($diaRef = $janelaInicio->copy(); $diaRef->lte($janelaFim); $diaRef->addDay()) {
            $janelaDias[] = $diaRef->copy();
        }
    @endphp

    <div class="w-full max-w-6xl mx-auto px-3 md:px-4 py-5 space-y-6">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Agendamentos e Tarefas</h1>
                <p class="text-sm text-slate-500">Resumo por período e serviço</p>
            </div>
            <a href="{{ route('master.dashboard') }}"
               class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                Voltar ao painel
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-indigo-500 bg-indigo-600 text-white grid grid-cols-1 lg:grid-cols-3 items-center gap-3">
                <div class="flex items-center gap-2 justify-start flex-wrap">
                    <a href="{{ route('master.agendamentos', $filtrosBase + ['data_relatorio' => $dataRelatorio->copy()->subMonthNoOverflow()->startOfMonth()->toDateString()]) }}"
                       class="inline-flex w-32 justify-center items-center gap-2 rounded-lg border border-white/40 bg-white/15 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/25">
                        Mes anterior
                    </a>
                    <form method="GET" action="{{ route('master.agendamentos') }}" class="flex items-center gap-2">
                        <input type="hidden" name="data_inicio" value="{{ $agendamentos['data_inicio'] ?? '' }}">
                        <input type="hidden" name="data_fim" value="{{ $agendamentos['data_fim'] ?? '' }}">
                        <input type="hidden" name="servico" value="{{ $agendamentos['servico_selecionado'] ?? 'todos' }}">
                        <input type="hidden" name="responsavel" value="{{ $agendamentos['responsavel_selecionado'] ?? 'todos' }}">
                        <input type="hidden" name="filtro_prestados" value="{{ $agendamentos['filtro_prestados'] ?? 'finalizadas' }}">
                        <select name="mes_relatorio" onchange="this.form.submit()"
                                class="rounded-lg border border-white/40 bg-white/15 px-2 py-1.5 text-xs text-white">
                            @foreach($mesesDisponiveis as $mesNumero => $mesNome)
                                <option value="{{ $mesNumero }}" class="text-slate-900 bg-white" @selected($mesSelecionado === $mesNumero)>
                                    {{ $mesNome }}
                                </option>
                            @endforeach
                        </select>
                        <select name="ano_relatorio" onchange="this.form.submit()"
                                class="rounded-lg border border-white/40 bg-white/15 px-2 py-1.5 text-xs text-white">
                            @foreach($anosDisponiveis as $anoItem)
                                <option value="{{ $anoItem }}" class="text-slate-900 bg-white" @selected($anoSelecionado === (int) $anoItem)>
                                    {{ $anoItem }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="text-center justify-self-center">
                    <div class="text-[11px] uppercase tracking-[0.16em] text-indigo-100">Mes selecionado</div>
                    <div class="text-xl font-semibold text-white">{{ \Illuminate\Support\Str::ucfirst($dataRelatorio->locale('pt_BR')->translatedFormat('F \\d\\e Y')) }}</div>
                </div>
                <div class="flex justify-start lg:justify-end">
                    <a href="{{ route('master.agendamentos', $filtrosBase + ['data_relatorio' => $dataRelatorio->copy()->addMonthNoOverflow()->startOfMonth()->toDateString()]) }}"
                       class="inline-flex w-32 justify-center items-center gap-2 rounded-lg border border-white/40 bg-white/15 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/25">
                        Proximo mes
                    </a>
                </div>
            </div>

            <div class="p-4 border-b border-slate-100">
                <div class="grid grid-cols-5 md:grid-cols-7 gap-2">
                    @foreach ($janelaDias as $dia)
                        @php
                            $dataStr = $dia->toDateString();
                            $isSelected = $dataStr === $selectedDateStr;
                        @endphp
                        <button type="button"
                                class="agenda-dia w-full rounded-xl border px-2 py-2 text-center {{ $isSelected ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-slate-300' }}"
                                data-date="{{ $dataStr }}"
                                data-label="{{ $dia->format('d/m/Y') }}">
                            <div class="text-[11px] font-semibold uppercase">{{ strtoupper($dia->locale('pt_BR')->translatedFormat('D')) }}</div>
                            <div class="text-lg font-semibold leading-none mt-1">{{ $dia->day }}</div>
                            <div class="text-[11px] opacity-80 mt-1 uppercase">{{ strtoupper($dia->locale('pt_BR')->translatedFormat('M')) }}</div>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="bg-indigo-600 text-white px-4 py-3 flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="text-sm font-semibold">Relat&oacute;rio do Dia - <span id="reportDiaLabel">{{ $dataRelatorio->format('d/m/Y') }}</span></div>
                    <form method="GET" action="{{ route('master.agendamentos') }}">
                        <input type="hidden" name="data_relatorio" value="{{ $dataRelatorio->toDateString() }}">
                        <input type="hidden" name="data_inicio" value="{{ $agendamentos['data_inicio'] ?? '' }}">
                        <input type="hidden" name="data_fim" value="{{ $agendamentos['data_fim'] ?? '' }}">
                        <input type="hidden" name="servico" value="{{ $agendamentos['servico_selecionado'] ?? 'todos' }}">
                        <input type="hidden" name="responsavel" value="{{ $agendamentos['responsavel_selecionado'] ?? 'todos' }}">
                        <select name="filtro_prestados" onchange="this.form.submit()"
                                class="rounded-lg border border-white/40 bg-white/15 px-2 py-1 text-xs text-white">
                            <option value="inicio_previsto" class="text-slate-900 bg-white" @selected(($agendamentos['filtro_prestados'] ?? 'finalizadas') === 'inicio_previsto')>Serviços Criados</option>
                            <option value="finalizadas" class="text-slate-900 bg-white" @selected(($agendamentos['filtro_prestados'] ?? 'finalizadas') === 'finalizadas')>Serviços Finalizados</option>
                        </select>
                    </form>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="inline-flex items-center rounded-lg border border-white/40 bg-white/15 px-3 py-1.5 text-xs font-semibold">
                        Serviços Finalizados: <span id="reportVendasFinalizadas" class="ml-1">R$ 0,00</span>
                    </div>
                    <div class="inline-flex items-center rounded-lg border border-white/40 bg-white/15 px-3 py-1.5 text-xs font-semibold">
                        Serviços em Andamento: <span id="reportVendasPendentes" class="ml-1">R$ 0,00</span>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 px-4 py-4 md:px-5 md:py-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-slate-800">Atendimentos por Unidade</h3>
                        <div id="reportUnidadesList" class="space-y-2"></div>
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">
                            Total de atendimentos: <span id="reportTotalAtendimentos">0</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-slate-800">Servi&ccedil;os Prestados Hoje</h3>
                        <div id="reportServicosList" class="space-y-2"></div>
                        <div class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-800">
                            Total de servi&ccedil;os: <span id="reportTotalServicos">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden">
            @foreach (($agendamentos['tarefas_janela_por_data'] ?? collect()) as $dataStr => $tarefasDia)
                <div id="agenda-dia-{{ $dataStr }}">
                    @forelse ($tarefasDia as $tarefa)
                        @php
                            $isConcluida = (bool) optional($tarefa->coluna)->finaliza;
                            $cardClasses = $isConcluida
                                ? 'bg-emerald-50/40 border-emerald-100'
                                : 'bg-amber-50/40 border-amber-100';
                        @endphp
                        <div class="rounded-xl border shadow-sm p-3 space-y-2 {{ $cardClasses }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $tarefa->titulo ?? 'Tarefa' }}</p>
                                    @if($tarefa->descricao)
                                        <p class="text-xs text-slate-600 mt-1">{{ $tarefa->descricao }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-semibold {{ $isConcluida ? 'bg-emerald-600 text-white' : 'bg-amber-400 text-amber-900' }}">
                                    {{ $isConcluida ? 'Concluída' : 'Pendente' }}
                                </span>
                            </div>
                            <div class="mt-2 space-y-1 text-xs text-slate-500">
                                <div>Hora: {{ optional($tarefa->inicio_previsto)->format('H:i') ?? '--:--' }}</div>
                                <div>Serviço: {{ optional($tarefa->servico)->nome ?? 'Sem serviço' }}</div>
                                <div>Cliente: {{ optional($tarefa->cliente)->razao_social ?? 'Não informado' }}</div>
                                <div>Responsável: {{ optional($tarefa->responsavel)->name ?? 'Não informado' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">Sem compromissos.</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>

    <div id="agendaDiaModal" class="fixed inset-0 z-[90] hidden flex items-center justify-center bg-black/50 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-5 py-4 border-b border-slate-100 bg-emerald-600 text-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Compromissos</h2>
                    <p class="text-xs text-emerald-50" id="agendaDiaLabel"></p>
                </div>
                <button type="button" id="btnFecharAgendaDia" class="h-9 w-9 flex items-center justify-center rounded-xl hover:bg-emerald-500 text-white">X</button>
            </div>
            <div class="p-5 space-y-3" id="agendaDiaConteudo"></div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('agendaDiaModal');
            const btnClose = document.getElementById('btnFecharAgendaDia');
            const label = document.getElementById('agendaDiaLabel');
            const content = document.getElementById('agendaDiaConteudo');
            const reportDiaLabel = document.getElementById('reportDiaLabel');
            const reportVendasFinalizadas = document.getElementById('reportVendasFinalizadas');
            const reportVendasPendentes = document.getElementById('reportVendasPendentes');
            const reportUnidadesList = document.getElementById('reportUnidadesList');
            const reportServicosList = document.getElementById('reportServicosList');
            const reportTotalAtendimentos = document.getElementById('reportTotalAtendimentos');
            const reportTotalServicos = document.getElementById('reportTotalServicos');
            const resumoPorDia = @json($resumoPorDia);
            let currentDate = @json($selectedDateStr);

            function openModal(dateLabel, html) {
                if (!modal || !content || !label) return;
                label.textContent = dateLabel;
                content.innerHTML = html || '<div class="text-sm text-slate-500">Sem compromissos.</div>';
                modal.classList.remove('hidden');
            }

            function renderList(container, rows, emptyMessage, badgeClass) {
                if (!container) return;
                if (!rows || !rows.length) {
                    container.innerHTML = `<div class="text-sm text-slate-500">${emptyMessage}</div>`;
                    return;
                }
                container.innerHTML = rows.map((row) => {
                    const labelText = row.nome || row.tipo || 'Item';
                    const total = Number(row.total || 0);
                    return `<div class="flex items-center justify-between rounded-lg bg-white border border-slate-200 px-3 py-2 text-sm">
                                <span class="text-slate-700">${labelText}</span>
                                <span class="inline-flex min-w-[28px] justify-center rounded-md ${badgeClass} px-2 py-0.5 text-xs font-semibold text-white">${total}</span>
                            </div>`;
                }).join('');
            }

            function updateReport(date, dateLabel) {
                const resumo = resumoPorDia[date] || {
                    unidades: [],
                    servicos: [],
                    total_atendimentos: 0,
                    total_servicos: 0,
                    vendas_finalizadas_valor: 0,
                    vendas_pendentes_valor: 0,
                };
                currentDate = date;
                if (reportDiaLabel) reportDiaLabel.textContent = dateLabel || date;
                if (reportVendasFinalizadas) {
                    const totalVendas = Number(resumo.vendas_finalizadas_valor || 0);
                    reportVendasFinalizadas.textContent = totalVendas.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                }
                if (reportVendasPendentes) {
                    const totalPendentes = Number(resumo.vendas_pendentes_valor || 0);
                    reportVendasPendentes.textContent = totalPendentes.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                }
                if (reportTotalAtendimentos) reportTotalAtendimentos.textContent = String(resumo.total_atendimentos || 0);
                if (reportTotalServicos) reportTotalServicos.textContent = String(resumo.total_servicos || 0);
                renderList(reportUnidadesList, resumo.unidades || [], 'Nenhuma unidade cadastrada.', 'bg-indigo-600');
                renderList(reportServicosList, resumo.servicos || [], 'Sem serviços prestados neste dia.', 'bg-blue-600');
            }

            function abrirCompromissosDoDia(date) {
                const trigger = document.querySelector(`.agenda-dia[data-date="${date}"]`);
                const dateLabel = trigger?.dataset?.label || date;
                const container = document.getElementById('agenda-dia-' + date);
                openModal(dateLabel, container ? container.innerHTML : '');
            }

            function closeModal() {
                modal?.classList.add('hidden');
            }

            document.querySelectorAll('.agenda-dia').forEach(btn => {
                btn.addEventListener('click', () => {
                    const date = btn.dataset.date;
                    const dateLabel = btn.dataset.label || date;
                    updateReport(date, dateLabel);
                    document.querySelectorAll('.agenda-dia').forEach((item) => {
                        item.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
                        item.classList.add('bg-slate-50', 'text-slate-700', 'border-slate-200');
                    });
                    btn.classList.remove('bg-slate-50', 'text-slate-700', 'border-slate-200');
                    btn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
                });
            });

            const btnInicial = document.querySelector(`.agenda-dia[data-date="${currentDate}"]`);
            if (btnInicial) {
                updateReport(currentDate, btnInicial.dataset.label || currentDate);
            }

            btnClose?.addEventListener('click', closeModal);
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
        })();
    </script>
@endsection
