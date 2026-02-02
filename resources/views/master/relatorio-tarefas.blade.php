@extends('layouts.master')
@section('title', 'Relatório de Tarefas')

@section('content')
    <div class="w-full px-4 md:px-8 py-8 space-y-8">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Relatório de Tarefas</h1>
                <p class="text-sm text-slate-500">Filtros por status, data, serviço e responsável</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('master.relatorio-tarefas.pdf', request()->query()) }}"
                   class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                    Exportar PDF
                </a>
                <a href="{{ route('master.dashboard') }}"
                   class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                    Voltar ao painel
                </a>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 space-y-5">
            <form method="GET" class="grid gap-3 md:grid-cols-9 items-end">
                <div>
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Data início</label>
                    <input type="date" name="data_inicio"
                           value="{{ $data_inicio ?? '' }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Data fim</label>
                    <input type="date" name="data_fim"
                           value="{{ $data_fim ?? '' }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Serviços</label>
                    <select name="servico"
                            class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                        <option value="todos" @selected(($servico_selecionado ?? 'todos') === 'todos')>
                            Todos os serviços
                        </option>
                        @foreach(($servicos_disponiveis ?? []) as $servico)
                            <option value="{{ $servico->id }}"
                                @selected(($servico_selecionado ?? 'todos') == $servico->id)>
                                {{ $servico->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Responsável</label>
                    <select name="responsavel"
                            class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                        <option value="todos" @selected(($responsavel_selecionado ?? 'todos') === 'todos')>
                            Todos os responsáveis
                        </option>
                        @foreach(($responsaveis_disponiveis ?? []) as $responsavel)
                            <option value="{{ $responsavel->id }}"
                                @selected(($responsavel_selecionado ?? 'todos') == $responsavel->id)>
                                {{ $responsavel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Status</label>
                    <select name="status"
                            class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                        <option value="todos" @selected(($status_selecionado ?? 'todos') === 'todos')>
                            Todos os status
                        </option>
                        @foreach(($status_opcoes ?? []) as $status)
                            <option value="{{ $status['slug'] }}"
                                @selected(($status_selecionado ?? 'todos') === $status['slug'])>
                                {{ $status['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-1 flex justify-end self-end">
                    <button type="submit"
                            class="w-full md:w-auto h-[44px] px-5 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                        Filtrar
                    </button>
                </div>
                <div class="md:col-span-6 md:col-start-3 text-[11px] text-slate-400 -mt-1">
                    Use os filtros para montar o relatório e exportar em PDF.
                </div>
            </form>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="text-sm font-semibold text-slate-900 mb-3">Status das tarefas</div>
                    <div class="h-64">
                        <canvas id="chartStatusTarefas"></canvas>
                    </div>
                    <div class="text-xs text-slate-500 mt-3">
                        Total de tarefas: {{ $resumo['total'] ?? 0 }}
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="text-sm font-semibold text-slate-900 mb-3">Serviços no período</div>
                    <div class="h-64">
                        <canvas id="chartServicosTarefas"></canvas>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-900">
                    Tarefas filtradas
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">ID</th>
                                <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                                <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                <th class="px-4 py-3 text-left font-semibold">Responsável</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-left font-semibold">Início previsto</th>
                                <th class="px-4 py-3 text-left font-semibold">Fim previsto</th>
                                <th class="px-4 py-3 text-left font-semibold">Finalizado em</th>
                                <th class="px-4 py-3 text-left font-semibold">Criado em</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse(($tarefas ?? []) as $tarefa)
                                @php
                                    $inicioPrevisto = $tarefa->inicio_previsto
                                        ? \Carbon\Carbon::parse($tarefa->inicio_previsto)->format('d/m/Y')
                                        : '-';
                                    $fimPrevisto = $tarefa->fim_previsto
                                        ? \Carbon\Carbon::parse($tarefa->fim_previsto)->format('d/m/Y')
                                        : '-';
                                    $finalizadoEm = $tarefa->finalizado_em
                                        ? \Carbon\Carbon::parse($tarefa->finalizado_em)->format('d/m/Y')
                                        : '-';
                                    $criadoEm = $tarefa->created_at
                                        ? \Carbon\Carbon::parse($tarefa->created_at)->format('d/m/Y')
                                        : '-';
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-slate-700">{{ $tarefa->id }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ optional($tarefa->cliente)->razao_social ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ optional($tarefa->servico)->nome ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ optional($tarefa->responsavel)->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ optional($tarefa->coluna)->nome ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $inicioPrevisto }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $fimPrevisto }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $finalizadoEm }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $criadoEm }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-slate-500">
                                        Nenhuma tarefa encontrada para os filtros selecionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const statusLabels = ['Pendentes', 'Em execução', 'Aguardando fornecedor', 'Correção', 'Atrasados', 'Finalizadas'];
        const statusData = [
            {{ $resumo['pendentes'] ?? 0 }},
            {{ $resumo['em_execucao'] ?? 0 }},
            {{ $resumo['aguardando_fornecedor'] ?? 0 }},
            {{ $resumo['correcao'] ?? 0 }},
            {{ $resumo['atrasados'] ?? 0 }},
            {{ $resumo['finalizadas'] ?? 0 }},
        ];

        new Chart(document.getElementById('chartStatusTarefas'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: ['#fbbf24', '#38bdf8', '#a78bfa', '#fb923c', '#f87171', '#34d399'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        const servicosLabels = @json(($servicos_por_servico ?? collect())->pluck('servico_nome'));
        const servicosData = @json(($servicos_por_servico ?? collect())->pluck('total'));

        new Chart(document.getElementById('chartServicosTarefas'), {
            type: 'bar',
            data: {
                labels: servicosLabels,
                datasets: [{
                    label: 'Total',
                    data: servicosData,
                    backgroundColor: '#6366f1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
@endpush
