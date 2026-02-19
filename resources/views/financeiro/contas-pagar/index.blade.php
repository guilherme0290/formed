@extends('layouts.financeiro')
@section('title', 'Contas a Pagar')
@section('page-container', 'w-full p-0')

@section('content')
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-[0.2em] text-indigo-400">Financeiro</div>
                <h1 class="text-3xl font-semibold text-slate-900">Contas a Pagar</h1>
                <p class="text-sm text-slate-500">Lançamentos de despesas e pagamentos</p>
            </div>
            <a href="{{ route('financeiro.contas-pagar.create') }}"
               class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                Nova Conta
            </a>
        </div>

        @include('financeiro.partials.tabs')

        @if(session('error'))
            <div class="rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
            <form method="GET" class="grid gap-4 md:grid-cols-4 items-end">
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Fornecedor</label>
                    <select name="fornecedor_id" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                        <option value="">Todos</option>
                        @foreach($fornecedores as $fornecedor)
                            <option value="{{ $fornecedor->id }}" @selected((string) $filtros['fornecedor_id'] === (string) $fornecedor->id)>
                                {{ $fornecedor->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Status</label>
                    <select name="status_conta" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                        <option value="">Todos</option>
                        <option value="FECHADA" @selected($filtros['status_conta'] === 'FECHADA')>Fechada</option>
                        <option value="PAGA" @selected($filtros['status_conta'] === 'PAGA')>Paga</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Filtrar</button>
                    <a href="{{ route('financeiro.contas-pagar.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Limpar</a>
                </div>
            </form>
        </section>

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <header class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-800">Painel de contas a pagar</h2>
            </header>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Conta</th>
                        <th class="px-4 py-3 text-left font-semibold">Fornecedor</th>
                        <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                        <th class="px-4 py-3 text-right font-semibold">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($contas as $conta)
                        @php
                            $status = strtoupper((string) $conta->status);
                            $badge = match($status) {
                                'PAGA' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'FECHADA' => 'bg-amber-50 text-amber-700 border-amber-100',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100">
                            <td class="px-4 py-3 text-slate-700">#{{ $conta->id }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $conta->fornecedor->razao_social ?? 'Fornecedor' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ optional($conta->vencimento)->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $badge }}">
                                    {{ ucfirst(strtolower($status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format((float) $conta->total, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('financeiro.contas-pagar.show', $conta) }}"
                                   class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">Nenhuma conta a pagar encontrada.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-slate-100">
                {{ $contas->links() }}
            </div>
        </section>
    </div>
@endsection
