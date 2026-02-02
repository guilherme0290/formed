@extends('layouts.master')
@section('title', 'Relatório de Produtividade')

@section('content')
    <div class="w-full px-4 md:px-8 py-8 space-y-8">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Relatório de Produtividade</h1>
                <p class="text-sm text-slate-500">Serviços finalizados e propostas comerciais por setor</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('master.relatorio-produtividade.pdf', request()->query()) }}"
                   class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                    Exportar PDF
                </a>
                <a href="{{ route('master.dashboard') }}"
                   class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                    Voltar ao painel
                </a>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 space-y-6">
            <form method="GET" class="grid gap-3 md:grid-cols-8 items-end">
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
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Setor</label>
                    <select name="setor"
                            class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                        <option value="todos" @selected(($setor_selecionado ?? 'todos') === 'todos')>Todos</option>
                        <option value="operacional" @selected(($setor_selecionado ?? 'todos') === 'operacional')>Operacional</option>
                        <option value="comercial" @selected(($setor_selecionado ?? 'todos') === 'comercial')>Comercial</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Usuário</label>
                    <select name="usuario"
                            class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                        <option value="todos" @selected(($usuario_selecionado ?? 'todos') === 'todos')>
                            Todos os usuários
                        </option>
                        @foreach(($usuarios_disponiveis ?? []) as $usuario)
                            <option value="{{ $usuario->id }}"
                                @selected(($usuario_selecionado ?? 'todos') == $usuario->id)>
                                {{ $usuario->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Status proposta</label>
                    <select name="status_proposta"
                            class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                        @foreach(($status_proposta_opcoes ?? []) as $status)
                            <option value="{{ $status }}"
                                @selected(($status_proposta_selecionado ?? 'FECHADA') === $status)>
                                {{ $status }}
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
                    Relatório por setor com contagens e detalhes. Serviços consideram tarefas finalizadas (exceto Exame).
                </div>
            </form>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="text-sm font-semibold text-slate-900 mb-3">Produtividade por setor</div>
                    <div class="h-64">
                        <canvas id="chartProdutividadeSetor"></canvas>
                    </div>
                    <div class="text-xs text-slate-500 mt-3">
                        Serviços finalizados: {{ $servicos_total ?? 0 }} • Propostas: {{ $propostas_total ?? 0 }} •
                        Valor: R$ {{ number_format($propostas_valor_total ?? 0, 2, ',', '.') }}
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="text-sm font-semibold text-slate-900 mb-3">Serviços finalizados por tipo</div>
                    <div class="h-64">
                        <canvas id="chartServicosProdutividade"></canvas>
                    </div>
                </div>
            </div>

            @if(($setor_selecionado ?? 'todos') !== 'comercial')
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-900">
                            Serviços finalizados por tipo
                        </div>
                        <div class="grid gap-3 p-4 sm:grid-cols-2">
                            @forelse(($servicos_por_servico ?? []) as $row)
                                <div class="rounded-2xl border border-indigo-200 bg-indigo-50/60 px-4 py-4 flex items-center justify-between">
                                    <div class="text-sm font-semibold text-slate-900">{{ $row->servico_nome }}</div>
                                    <div class="text-2xl font-semibold text-slate-900">{{ $row->total }}</div>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">Nenhum serviço finalizado no período.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-900">
                            Serviços finalizados (detalhado)
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">ID</th>
                                        <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                                        <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                        <th class="px-4 py-3 text-left font-semibold">Responsável</th>
                                        <th class="px-4 py-3 text-left font-semibold">Finalizado em</th>
                                        <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse(($servicos_tarefas ?? []) as $tarefa)
                                        @php
                                            $finalizadoEm = $tarefa->finalizado_em
                                                ? \Carbon\Carbon::parse($tarefa->finalizado_em)->format('d/m/Y')
                                                : '-';
                                        @endphp
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-3 text-slate-700">{{ $tarefa->id }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ optional($tarefa->cliente)->razao_social ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ optional($tarefa->servico)->nome ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ optional($tarefa->responsavel)->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $finalizadoEm }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $tarefa->descricao ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                                                Nenhum serviço finalizado para os filtros selecionados.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if(($setor_selecionado ?? 'todos') !== 'operacional')
                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-900">
                        Propostas comerciais ({{ $status_proposta_selecionado ?? 'FECHADA' }})
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Código</th>
                                    <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                                    <th class="px-4 py-3 text-left font-semibold">Vendedor</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold">Valor</th>
                                    <th class="px-4 py-3 text-left font-semibold">Atualizado em</th>
                                    <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse(($propostas ?? []) as $proposta)
                                    @php
                                        $atualizadoEm = $proposta->updated_at
                                            ? \Carbon\Carbon::parse($proposta->updated_at)->format('d/m/Y')
                                            : '-';
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 text-slate-700">{{ $proposta->codigo ?? $proposta->id }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ optional($proposta->cliente)->razao_social ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ optional($proposta->vendedor)->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $proposta->status ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">
                                            R$ {{ number_format($proposta->valor_total ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $atualizadoEm }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $proposta->observacoes ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">
                                            Nenhuma proposta encontrada para os filtros selecionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const setorLabels = ['Operacional', 'Comercial'];
        const setorData = [
            {{ $produtividade_setor['operacional'] ?? 0 }},
            {{ $produtividade_setor['comercial'] ?? 0 }},
        ];

        new Chart(document.getElementById('chartProdutividadeSetor'), {
            type: 'bar',
            data: {
                labels: setorLabels,
                datasets: [{
                    label: 'Total',
                    data: setorData,
                    backgroundColor: ['#38bdf8', '#f97316']
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

        const servicosLabels = @json(($servicos_por_servico ?? collect())->pluck('servico_nome'));
        const servicosData = @json(($servicos_por_servico ?? collect())->pluck('total'));

        new Chart(document.getElementById('chartServicosProdutividade'), {
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
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
@endpush
