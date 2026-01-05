{{-- resources/views/clientes/faturas/index.blade.php --}}
@extends('layouts.cliente')

@section('title', 'Faturas e Serviços')

@section('content')
    <section class="max-w-6xl mx-auto px-4 md:px-0">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl md:text-2xl font-semibold text-slate-900">
                    Faturas e serviços realizados
                </h2>
                <p class="text-xs md:text-sm text-slate-500">
                    Histórico de contas a receber e seus valores.
                </p>
            </div>

            <a href="{{ route('cliente.dashboard') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-900 text-white text-xs md:text-sm font-semibold shadow">
                ⏪ Voltar ao painel
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6">
            <form method="GET" class="grid gap-4 md:grid-cols-5 items-end">
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Período</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="data_inicio" value="{{ $filtros['data_inicio'] ?? '' }}"
                               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                        <span class="text-slate-400">a</span>
                        <input type="date" name="data_fim" value="{{ $filtros['data_fim'] ?? '' }}"
                               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Status</label>
                    <select name="status" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                        <option value="" class="text-slate-900">Todos</option>
                        <option value="ABERTO" class="text-slate-900" @selected(($filtros['status'] ?? '') === 'ABERTO')>Em aberto</option>
                        <option value="BAIXADO" class="text-slate-900" @selected(($filtros['status'] ?? '') === 'BAIXADO')>Pago</option>
                    </select>
                </div>
                <div class="flex items-end gap-3 md:col-span-2">
                    <button class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                        Filtrar
                    </button>
                    <a href="{{ route('cliente.faturas') }}" class="text-sm text-slate-500 hover:text-slate-700">Limpar</a>
                </div>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-3 mb-6">
            <div class="md:col-span-3 rounded-2xl bg-[#059669] text-white shadow-lg shadow-emerald-900/25 p-5 flex items-center justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.18em] text-emerald-50/90">
                        Fatura em aberto
                    </p>
                    <p class="mt-1 text-2xl md:text-3xl font-semibold">
                        R$ {{ number_format($faturaTotal ?? 0, 2, ',', '.') }}
                    </p>
                    <p class="text-[11px] text-emerald-50/80 mt-1">
                        Soma das contas a receber em aberto
                    </p>
                </div>
                <div class="hidden md:block text-4xl">💲</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <header class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <span>📋</span> <span>Detalhes da fatura</span>
                </div>
                <span class="text-[12px] text-slate-200">
                    Atualizado automaticamente
                </span>
            </header>

            @if($itens->isEmpty())
                <div class="p-6 text-sm text-slate-500">
                    Nenhuma cobrança encontrada. Assim que houver contas geradas, elas aparecerão aqui.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Data</th>
                                <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                                <th class="px-4 py-3 text-right font-semibold">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($itens as $item)
                                @php
                                    $servicoNome = $item->servico ?? 'Serviço';
                                    $status = strtoupper((string) $item->status);
                                    $vencimento = $item->vencimento ? \Carbon\Carbon::parse($item->vencimento) : null;
                                    $vencido = $status === 'ABERTO' && $vencimento?->lt(now()->startOfDay());
                                    $badge = match(true) {
                                        $status === 'BAIXADO' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                        $vencido => 'bg-rose-50 text-rose-700 border-rose-100',
                                        default => 'bg-amber-50 text-amber-700 border-amber-100',
                                    };
                                    $label = $vencido ? 'Vencido' : ($status === 'BAIXADO' ? 'Pago' : 'Em aberto');
                                @endphp
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $item->data_realizacao ? \Carbon\Carbon::parse($item->data_realizacao)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $servicoNome }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[12px] font-semibold
                                            {{ $badge }}">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $vencimento?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                        R$ {{ number_format((float) $item->valor, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $itens->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
