@extends('layouts.financeiro')
@section('title', 'Contratos Financeiro')

@section('content')
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold text-slate-900">Contratos</h1>
                <p class="text-sm text-slate-500">Visualização financeira (somente leitura)</p>
            </div>
        </div>

        @include('financeiro.partials.tabs')

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Cliente</th>
                            <th class="px-5 py-3 text-left font-semibold">Início</th>
                            <th class="px-5 py-3 text-left font-semibold">Valor</th>
                            <th class="px-5 py-3 text-center font-semibold">Status</th>
                            <th class="px-5 py-3 text-center font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($contratos as $contrato)
                            @php
                                $status = strtoupper((string) $contrato->status);
                                $badge = match($status) {
                                    'ATIVO' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'PENDENTE' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100">
                                <td class="px-5 py-3 text-slate-800">
                                    <div class="font-semibold">{{ $contrato->cliente->razao_social ?? 'Cliente' }}</div>
                                    @if($contrato->cliente->nome_fantasia)
                                        <div class="text-xs text-slate-500">{{ $contrato->cliente->nome_fantasia }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-700">
                                    {{ optional($contrato->vigencia_inicio)->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-5 py-3 font-semibold text-slate-900">
                                    R$ {{ number_format((float) $contrato->valor_mensal, 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full border text-xs font-semibold {{ $badge }}">
                                        {{ ucfirst(strtolower($status)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <a href="{{ route('financeiro.contratos.show', $contrato) }}"
                                       class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                                        Ver Contrato
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-6 text-center text-slate-500">Nenhum contrato encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
