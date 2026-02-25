@extends('layouts.financeiro')
@section('title', 'Relatorios Financeiros')
@section('page-container', 'w-full px-4 sm:px-6 lg:px-8 py-6')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900">Relatorios</h1>
            <p class="text-sm text-slate-500 mt-1">A Receber por periodo, inadimplencia, meios de pagamento e DRE simplificado.</p>
        </div>

        @include('financeiro.partials.tabs')

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">A Receber (mes atual)</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">R$ {{ number_format($receber_periodo, 2, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">DRE: Receita bruta</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700">R$ {{ number_format($dre['receita_bruta'], 2, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">DRE: Custos</p>
                <p class="mt-2 text-2xl font-semibold text-rose-700">R$ {{ number_format($dre['custos'], 2, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">DRE: Resultado</p>
                <p class="mt-2 text-2xl font-semibold {{ $dre['resultado'] >= 0 ? 'text-slate-900' : 'text-rose-700' }}">R$ {{ number_format($dre['resultado'], 2, ',', '.') }}</p>
            </article>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Recebimentos por meio de pagamento</h2>
                <div class="mt-3 space-y-2 text-sm">
                    @forelse($meios as $meio)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <span class="text-slate-700">{{ $meio['nome'] ?? 'Nao informado' }}</span>
                            <strong class="text-slate-900">R$ {{ number_format((float) $meio['valor'], 2, ',', '.') }}</strong>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Nenhum recebimento no periodo.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Inadimplencia por cliente</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="py-2 text-left font-semibold">Cliente</th>
                            <th class="py-2 text-right font-semibold">Em atraso</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($inadimplencia as $item)
                            <tr>
                                <td class="py-2 text-slate-700">{{ $item['nome'] }}</td>
                                <td class="py-2 text-right font-semibold text-rose-700">R$ {{ number_format((float) $item['valor'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-4 text-center text-slate-500">Sem inadimplencia registrada.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Exportacoes</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('financeiro.faturamento-detalhado.exportar-pdf', ['filtrar' => 1, 'data_inicio' => now()->startOfMonth()->toDateString(), 'data_fim' => now()->endOfMonth()->toDateString()]) }}"
                   class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">Exportar PDF</a>
                <a href="{{ route('financeiro.faturamento-detalhado.exportar-excel', ['filtrar' => 1, 'data_inicio' => now()->startOfMonth()->toDateString(), 'data_fim' => now()->endOfMonth()->toDateString()]) }}"
                   class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700">Exportar Excel</a>
            </div>
        </section>
    </div>
@endsection
