@extends('layouts.master')
@section('title', 'Comissões por Vendedor')

@section('content')
    <div class="w-full mx-auto px-4 md:px-6 xl:px-8 py-6 space-y-6">
        <div>
            <a href="{{ route('master.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                Voltar ao Painel
            </a>
        </div>
        <div class="flex flex-col gap-2">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Visão geral por vendedor</h1>
                <p class="text-sm text-slate-500">Real time por ano, com filtro de vendedor.</p>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="flex flex-wrap gap-2 items-center">
            @forelse($anos as $ano)
                <a href="{{ route('master.comissoes.vendedores', ['ano' => $ano, 'vendedor' => $vendedorSelecionado]) }}"
                   class="px-3 py-1.5 rounded-xl border text-sm font-semibold {{ (int)$ano === (int)$anoSelecionado ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
                    {{ $ano }}
                </a>
            @empty
                <span class="text-sm text-slate-500">Sem comissões registradas.</span>
            @endforelse

            <form method="GET" class="ml-auto flex items-center gap-2">
                <input type="hidden" name="ano" value="{{ $anoSelecionado }}">
                <span class="inline-flex items-center rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-700">
                    Vendedor
                </span>
                <select name="vendedor"
                        class="rounded-lg border-slate-300 text-sm px-3 py-2 focus:ring-2 focus:ring-orange-500">
                    <option value="">Todos</option>
                    @foreach($vendedores as $id => $vend)
                        <option value="{{ $id }}" {{ $vendedorSelecionado == $id ? 'selected' : '' }}>
                            {{ $vend->name ?? 'Vendedor '.$id }}
                        </option>
                    @endforeach
                </select>
                <button class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm">Filtrar</button>
            </form>
        </div>

        {{-- Grid de meses --}}
        <div class="grid gap-4 md:grid-cols-3">
            @php
                $cardsCores = [
                    'bg-blue-50 border-blue-100',
                    'bg-emerald-50 border-emerald-100',
                    'bg-amber-50 border-amber-100',
                    'bg-rose-50 border-rose-100',
                    'bg-indigo-50 border-indigo-100',
                    'bg-teal-50 border-teal-100',
                    'bg-cyan-50 border-cyan-100',
                    'bg-lime-50 border-lime-100',
                    'bg-sky-50 border-sky-100',
                    'bg-orange-50 border-orange-100',
                    'bg-violet-50 border-violet-100',
                    'bg-fuchsia-50 border-fuchsia-100',
                ];
            @endphp
            @foreach($meses as $mes)
                @php $cardCor = $cardsCores[$loop->index % count($cardsCores)]; @endphp
                <div class="rounded-2xl border shadow-sm p-4 flex flex-col gap-3 {{ $cardCor }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-orange-500">{{ $mes->nome }}</p>
                            <p class="text-lg font-semibold text-slate-900">R$ {{ number_format($mes->total, 2, ',', '.') }}</p>
                        </div>
                        @if($mes->status === 'FECHADO')
                            <span class="inline-flex items-center gap-1 text-xs px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                                🟢 Fechado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-100">
                                🟠 Em Aberto
                            </span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs text-slate-600">
                        <div class="rounded-lg bg-slate-50 border border-slate-100 p-2">
                            <div class="text-[11px] uppercase tracking-wide text-amber-600">Previsto</div>
                            <div class="font-semibold text-slate-900">R$ {{ number_format($mes->total_previsto, 2, ',', '.') }}</div>
                        </div>
                        <div class="rounded-lg bg-slate-50 border border-slate-100 p-2">
                            <div class="text-[11px] uppercase tracking-wide text-emerald-600">Efetivado</div>
                            <div class="font-semibold text-slate-900">R$ {{ number_format($mes->total_efetivado, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Ranking por vendedor (ano selecionado) --}}
        <section class="bg-blue-50/60 rounded-2xl shadow-sm border border-blue-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-blue-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Ranking de vendedores ({{ $anoSelecionado }})</h2>
                <span class="text-xs font-semibold text-slate-900">Comissão total (prevista + paga)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-slate-900">
                    <thead class="bg-blue-50 text-slate-900">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Vendedor</th>
                            <th class="px-5 py-3 text-right font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-100">
                        @forelse($ranking as $row)
                            <tr class="hover:bg-blue-50/60">
                                <td class="px-5 py-3 text-slate-900">
                                    {{ $row->vendedor->name ?? 'ID '.$row->vendedor_id }}
                                </td>
                                <td class="px-5 py-3 text-right font-semibold text-slate-900">
                                    R$ {{ number_format($row->total ?? 0, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-5 py-4 text-center text-slate-900">
                                    Nenhuma comissão registrada neste ano.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
