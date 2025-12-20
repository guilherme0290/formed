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
                    Histórico de serviços finalizados no kanban e seus valores.
                </p>
            </div>

            <a href="{{ route('cliente.dashboard') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-900 text-white text-xs md:text-sm font-semibold shadow">
                ⏪ Voltar ao painel
            </a>
        </div>

        <div class="grid gap-4 md:grid-cols-3 mb-6">
            <div class="md:col-span-3 rounded-2xl bg-[#059669] text-white shadow-lg shadow-emerald-900/25 p-5 flex items-center justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.18em] text-emerald-50/90">
                        Fatura total consolidada
                    </p>
                    <p class="mt-1 text-2xl md:text-3xl font-semibold">
                        R$ {{ number_format($faturaTotal ?? 0, 2, ',', '.') }}
                    </p>
                    <p class="text-[11px] text-emerald-50/80 mt-1">
                        Soma dos serviços finalizados (coluna finalizada do kanban)
                    </p>
                </div>
                <div class="hidden md:block text-4xl">💲</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <header class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <span>📋</span> <span>Detalhes dos serviços faturados</span>
                </div>
                <span class="text-[12px] text-slate-200">
                    Atualizado automaticamente
                </span>
            </header>

            @if($vendas->isEmpty())
                <div class="p-6 text-sm text-slate-500">
                    Nenhum serviço faturado encontrado. Assim que houver serviços finalizados, eles aparecerão aqui.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Data</th>
                                <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($vendas as $venda)
                                @php
                                    $item = $venda->itens->first();
                                    $servicoNome = $item?->servico->nome ?? $item?->descricao_snapshot ?? 'Serviço';
                                    $coluna = $venda->tarefa?->coluna;
                                    $finalizada = $coluna?->finaliza === true;
                                @endphp
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ optional($venda->created_at)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $servicoNome }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[12px] font-semibold
                                            {{ $finalizada ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-100' }}">
                                            {{ $finalizada ? 'Finalizado' : 'Em aberto' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                        R$ {{ number_format($venda->total ?? 0, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $vendas->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
