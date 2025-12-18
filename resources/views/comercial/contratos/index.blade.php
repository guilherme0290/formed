@extends('layouts.comercial')
@section('title', 'Contratos')

@section('content')
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">

        <div>
            <a href="{{ route('comercial.dashboard') }}"
               class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800">
                ← Voltar ao Painel
            </a>
        </div>

        <header class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Contratos</h1>
                <p class="text-slate-500 text-sm mt-1">Gestão de contratos derivados das propostas fechadas.</p>
            </div>
        </header>

        {{-- Cards de resumo (não filtram) --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="bg-white border border-emerald-100 rounded-2xl shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-emerald-600">Contratos Ativos</p>
                    <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $totalAtivos }}</p>
                    <p class="text-[11px] text-emerald-500">Total geral do sistema</p>
                </div>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 font-semibold">
                    ✔
                </span>
            </div>

            <div class="bg-white border border-amber-100 rounded-2xl shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-amber-600">Contratos Pendentes</p>
                    <p class="text-2xl font-bold text-amber-700 mt-1">{{ $totalPendentes }}</p>
                    <p class="text-[11px] text-amber-500">Total geral do sistema</p>
                </div>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700 font-semibold">
                    ⏳
                </span>
            </div>

            <div class="bg-white border border-blue-100 rounded-2xl shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-blue-600">Faturamento Mensal (ativos)</p>
                    <p class="text-2xl font-bold text-blue-700 mt-1">R$ {{ number_format($faturamentoAtivo, 2, ',', '.') }}</p>
                    <p class="text-[11px] text-blue-500">Soma de contratos ATIVOS</p>
                </div>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700 font-semibold">
                    $
                </span>
            </div>
        </div>

        {{-- Filtros --}}
        <section class="bg-white rounded-2xl shadow border border-slate-100 p-4 md:p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-800">Filtros</h2>
            </div>

            <form method="GET" action="{{ route('comercial.contratos.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-4">
                    <label class="text-xs font-semibold text-slate-600">Cliente</label>
                    <input type="text" name="q" value="{{ $buscaCliente }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                           placeholder="Buscar por cliente">
                </div>

                <div class="md:col-span-3">
                    <label class="text-xs font-semibold text-slate-600">Status</label>
                    <select name="status[]" multiple size="6"
                            class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                        @php
                            $opts = ['TODOS','ATIVO','PENDENTE','EM_ABERTO','FECHADO','CANCELADO','SUBSTITUIDO'];
                        @endphp
                        @foreach($opts as $opt)
                            <option value="{{ $opt }}" @selected(in_array($opt, $statusFiltro))>{{ str_replace('_',' ', $opt) }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Sem seleção: mostra Ativo/Pendente. Selecionar "Todos" ignora filtro de status.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Vigência início (de)</label>
                    <input type="date" name="vigencia_de" value="{{ $vigenciaDe }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                </div>

                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Vigência fim (até)</label>
                    <input type="date" name="vigencia_ate" value="{{ $vigenciaAte }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                </div>

                <div class="md:col-span-2 lg:col-span-1">
                    <label class="text-xs font-semibold text-slate-600">Valor mín (R$)</label>
                    <input type="number" step="0.01" name="valor_min" value="{{ $valorMin }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                </div>

                <div class="md:col-span-2 lg:col-span-1">
                    <label class="text-xs font-semibold text-slate-600">Valor máx (R$)</label>
                    <input type="number" step="0.01" name="valor_max" value="{{ $valorMax }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                </div>

                <div class="md:col-span-12 flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('comercial.contratos.index') }}"
                       class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Limpar filtros
                    </a>
                    <button type="submit"
                            class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold shadow-sm">
                        Filtrar
                    </button>
                </div>
            </form>
        </section>

        {{-- Lista de contratos --}}
        <section class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Lista de Contratos</h2>
                    <p class="text-xs text-slate-500">Mostrando {{ $usandoFiltroCustom ? 'contratos conforme filtros' : 'somente Ativos e Pendentes' }}.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-5 py-3 font-semibold">Cliente</th>
                        <th class="px-5 py-3 font-semibold">Valor Mensal</th>
                        <th class="px-5 py-3 font-semibold">Vigência Início</th>
                        <th class="px-5 py-3 font-semibold">Vigência Fim</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 font-semibold w-40">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($contratos as $contrato)
                        @php
                            $status = strtoupper((string) $contrato->status);
                            $badge = match($status) {
                                'ATIVO' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'PENDENTE' => 'bg-amber-50 text-amber-800 border-amber-200',
                                'EM_ABERTO' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'FECHADO' => 'bg-blue-100 text-blue-900 border-blue-200',
                                'CANCELADO' => 'bg-slate-100 text-red-700 border-slate-200',
                                'SUBSTITUIDO' => 'bg-slate-50 text-slate-600 border-slate-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr>
                            <td class="px-5 py-3">
                                <div class="font-semibold text-slate-800">{{ $contrato->cliente->razao_social ?? '—' }}</div>
                                <div class="text-xs text-slate-500">Contrato #{{ $contrato->id }}</div>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-800">
                                R$ {{ number_format((float) $contrato->valor_mensal, 2, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-slate-700">
                                {{ optional($contrato->vigencia_inicio)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-slate-700">
                                {{ optional($contrato->vigencia_fim)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border {{ $badge }}">
                                    {{ str_replace('_',' ', $status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('comercial.contratos.show', $contrato) }}"
                                   class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold">
                                    Ver Contrato
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                Nenhum contrato encontrado.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 md:p-5 border-t border-slate-100">
                {{ $contratos->links() }}
            </div>
        </section>

    </div>
@endsection
