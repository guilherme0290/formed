@extends('layouts.financeiro')
@section('title', 'Detalhamento de Faturamento')
@section('page-container', 'w-full px-4 sm:px-6 lg:px-8 py-6')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-2">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Detalhamento de Faturamento</h1>
                <p class="text-sm text-slate-500 mt-1">Itens recebidos e pendentes por cliente e serviço.</p>
            </div>
        </div>

        @include('financeiro.partials.tabs')

        <div class="bg-slate-50/70 rounded-3xl border border-slate-200 shadow-sm p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="text-sm font-semibold text-slate-900">Filtros</div>
            </div>
            <form method="GET" class="grid gap-4 md:grid-cols-5 items-end">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Data in&iacute;cio</label>
                    <input type="date" name="data_inicio"
                           value="{{ $data_inicio ?? '' }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 text-sm px-3 py-2 h-[44px]">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Data fim</label>
                    <input type="date" name="data_fim"
                           value="{{ $data_fim ?? '' }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 text-sm px-3 py-2 h-[44px]">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Cliente</label>
                    <input type="text" name="cliente" list="clientes-list"
                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 text-sm px-3 py-2 h-[44px]"
                           placeholder="Todos os clientes"
                           value="{{ ($cliente_selecionado ?? 'todos') === 'todos' ? '' : ($cliente_selecionado_label ?? '') }}">
                    <datalist id="clientes-list">
                        <option value="Todos os clientes"></option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->razao_social }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Status</label>
                    <select name="status"
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-700 text-sm px-3 py-2 h-[44px]">
                        <option value="" @selected(($status_selecionado ?? 'todos') === 'todos')>Todos</option>
                        <option value="recebido" @selected(($status_selecionado ?? 'todos') === 'recebido')>Recebido</option>
                        <option value="pendente" @selected(($status_selecionado ?? 'todos') === 'pendente')>Pendente</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" name="filtrar" value="1"
                            class="h-[44px] px-5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-500">
                        Filtrar
                    </button>
                    <a href="{{ route('financeiro.faturamento-detalhado.exportar-pdf', request()->query()) }}"
                       class="h-[44px] px-4 rounded-xl border border-indigo-200 bg-white text-sm font-semibold text-indigo-700 hover:bg-indigo-50 inline-flex items-center gap-2">
                        Exportar PDF
                    </a>
                    <a href="{{ route('financeiro.faturamento-detalhado') }}"
                       class="h-[44px] px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 inline-flex items-center">
                        Limpar
                    </a>
                </div>
            </form>
        </div>

        @php
            $servicosLabel = ($total_servicos ?? 0) === 1 ? 'serviço' : 'serviços';
            $clientesLabel = ($total_clientes ?? 0) === 1 ? 'cliente' : 'clientes';
            $chipsResumo = ($total_servicos ?? 0).' '.$servicosLabel.' • '.($total_clientes ?? 0).' '.$clientesLabel;
        @endphp

        <div class="grid gap-4 md:grid-cols-2">
            <div class="relative overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100/60 px-4 py-4 shadow-sm">
                <div class="text-xs font-bold text-emerald-800 uppercase">Recebido no per&iacute;odo</div>
                <div class="text-2xl font-semibold text-emerald-900 mt-1">
                    R$ {{ number_format($total_recebido ?? 0, 2, ',', '.') }}
                </div>
                <div class="text-xs text-emerald-700 mt-2">{{ $chipsResumo }}</div>
                <div class="absolute right-3 bottom-2 flex items-end gap-1 opacity-40">
                    <span class="block w-2 h-6 rounded bg-emerald-300"></span>
                    <span class="block w-2 h-9 rounded bg-emerald-400"></span>
                    <span class="block w-2 h-4 rounded bg-emerald-300"></span>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100/60 px-4 py-4 shadow-sm">
                <div class="text-xs font-bold text-amber-800 uppercase">Pendente no per&iacute;odo</div>
                <div class="text-2xl font-semibold text-amber-900 mt-1">
                    R$ {{ number_format($total_pendente ?? 0, 2, ',', '.') }}
                </div>
                <div class="text-xs text-amber-700 mt-2">{{ $chipsResumo }}</div>
                <div class="absolute right-3 bottom-2 flex items-end gap-1 opacity-40">
                    <span class="block w-2 h-6 rounded bg-amber-300"></span>
                    <span class="block w-2 h-9 rounded bg-amber-400"></span>
                    <span class="block w-2 h-4 rounded bg-amber-300"></span>
                </div>
            </div>
        </div>

        <div class="text-xs text-slate-500">
            Per&iacute;odo: {{ \Carbon\Carbon::parse($data_inicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($data_fim)->format('d/m/Y') }}
            &bull; Cliente: {{ $cliente_selecionado_label ?? 'Todos os clientes' }}
            &bull; Status: {{ $status_selecionado_label ?? 'Todos' }}
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Detalhamento</h2>
                    <p class="text-xs text-slate-500">Cada linha representa um servi&ccedil;o executado.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                            <th class="px-4 py-3 text-left font-semibold">Servi&ccedil;o</th>
                            <th class="px-4 py-3 text-left font-semibold">Descri&ccedil;&atilde;o</th>
                            <th class="px-4 py-3 text-left font-semibold">Data</th>
                            <th class="px-4 py-3 text-right font-semibold">Valor</th>
                            <th class="px-4 py-3 text-right font-semibold">Recebido</th>
                            <th class="px-4 py-3 text-right font-semibold">Pendente</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($itens as $item)
                            @php
                                $valor = (float) ($item->valor ?? 0);
                                $recebido = (float) ($item->total_baixado ?? 0);
                                $pendente = max($valor - $recebido, 0);
                                $status = $pendente <= 0 ? 'Recebido' : 'Pendente';
                                $statusClass = $pendente <= 0
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : 'bg-amber-50 text-amber-700 border-amber-200';
                                $dataRef = $item->data_realizacao ?? $item->vencimento ?? $item->created_at;
                            @endphp
                            <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100">
                                <td class="px-4 py-3 text-slate-700">{{ $item->cliente->razao_social ?? 'Cliente' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $item->servico->nome ?? 'Serviço' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $item->descricao ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $dataRef ? \Carbon\Carbon::parse($dataRef)->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-slate-700 text-right">R$ {{ number_format($valor, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold {{ $recebido >= $valor ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">
                                        R$ {{ number_format($recebido, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold {{ $pendente > 0 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                        R$ {{ number_format($pendente, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-slate-500">
                                    Nenhum item encontrado para os filtros selecionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($itens, 'links'))
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $itens->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
