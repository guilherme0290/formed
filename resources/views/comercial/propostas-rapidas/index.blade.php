@extends('layouts.comercial')
@section('title', 'Propostas Rápidas')

@section('content')
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Propostas Rápidas</h1>
                <p class="text-sm text-slate-500">Propostas diretas para abordagem inicial do comercial.</p>
            </div>

            <a href="{{ route('comercial.propostas.rapidas.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                <span class="text-base leading-none">+</span>
                <span>Nova proposta</span>
            </a>
        </div>

        @if(session('ok'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 p-4">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-10">
                        <label class="text-xs font-semibold text-slate-600">Buscar</label>
                        <input name="q"
                               value="{{ request('q') }}"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                               placeholder="Buscar por nome do cliente...">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit"
                                class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <div class="max-h-[70vh] overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Código</th>
                        <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                        <th class="px-4 py-3 text-left font-semibold">Vendedor</th>
                        <th class="px-4 py-3 text-left font-semibold">Valor final</th>
                        <th class="px-4 py-3 text-left font-semibold">Criada em</th>
                        <th class="px-4 py-3 text-right font-semibold">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($propostas as $proposta)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $proposta->codigo ?? ('#' . $proposta->id) }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $proposta->cliente?->razao_social ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $proposta->cliente?->documento_principal ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $proposta->vendedor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">R$ {{ number_format((float) $proposta->valor_total, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ optional($proposta->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('comercial.propostas.rapidas.show', $proposta) }}"
                                       class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Abrir
                                    </a>
                                    <a href="{{ route('comercial.propostas.rapidas.edit', $proposta) }}"
                                       class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                Nenhuma proposta rápida encontrada.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 p-4">
                {{ $propostas->links() }}
            </div>
        </section>
    </div>
@endsection
