@extends('layouts.operacional')
@section('title', 'Proposta Comercial')

@section('content')
    <div class="bg-white rounded-2xl shadow border border-slate-200 max-w-4xl mx-auto">
        <div class="px-6 py-4 bg-blue-600 text-white rounded-t-2xl flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Proposta Comercial</h1>
                <p class="text-xs opacity-80">{{ $proposta->created_at->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="p-6 space-y-6 text-sm text-slate-800">
            {{-- Contratada x Cliente --}}
            <div class="grid md:grid-cols-2 gap-4">
                <div class="border rounded-xl p-3 bg-slate-50">
                    <p class="text-xs font-semibold text-slate-500 mb-1">CONTRATADA</p>
                    <p class="font-semibold">{{ $proposta->empresa->nome_fantasia ?? 'FORMED' }}</p>
                    {{-- aqui você preenche CNPJ, email etc da empresa --}}
                </div>
                <div class="border rounded-xl p-3 bg-emerald-50/70">
                    <p class="text-xs font-semibold text-slate-500 mb-1">CLIENTE FINAL</p>
                    <p class="font-semibold">{{ $proposta->cliente->nome ?? '' }}</p>
                </div>
            </div>

            {{-- Serviços --}}
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-2">SERVIÇOS</p>

                <table class="w-full text-sm border-collapse">
                    <tbody>
                    @foreach($proposta->itens as $idx => $item)
                        <tr class="border-b last:border-0">
                            <td class="py-2 pr-2 align-top w-8">
                                {{ $idx + 1 }}.
                            </td>
                            <td class="py-2 pr-2">
                                <div class="font-medium">{{ $item->nome }}</div>
                                @if($item->descricao)
                                    <div class="text-xs text-slate-500">{{ $item->descricao }}</div>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right align-top">
                                Qtd: {{ $item->quantidade }}
                            </td>
                            <td class="py-2 pl-2 text-right align-top">
                                R$ {{ number_format($item->valor_total, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- E-Social --}}
            @if($proposta->incluir_esocial)
                <div class="border rounded-xl p-3 bg-yellow-50">
                    <p class="text-xs font-semibold text-slate-600 mb-1">E-SOCIAL (MENSAL)</p>
                    <p class="text-sm">
                        {{ $proposta->esocial_qtd_funcionarios }} funcionários —
                        R$ {{ number_format($proposta->esocial_valor_mensal, 2, ',', '.') }}/mês
                    </p>
                </div>
            @endif

            {{-- Valor Total --}}
            <div class="mt-2">
                <div class="rounded-2xl bg-emerald-500 text-white px-6 py-4 flex items-center justify-between">
                    <span class="text-sm font-medium">Valor Total</span>
                    <span class="text-2xl font-semibold">
                        R$ {{ number_format($proposta->valor_total, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- Forma de Pagamento --}}
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-1">PAGAMENTO</p>
                <p>{{ $proposta->forma_pagamento }}</p>
            </div>

            {{-- Unidades (se tiver) --}}
            @if($proposta->unidades->count())
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-1">UNIDADES</p>
                    <div class="grid md:grid-cols-3 gap-2">
                        @foreach($proposta->unidades as $unidade)
                            <div class="border rounded-lg px-3 py-2 text-xs">
                                <span class="block font-medium">{{ $unidade->nome }}</span>
                                <span class="block text-slate-500">{{ $unidade->endereco }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="pt-4 border-t flex justify-end gap-3">
                <a href="{{ route('comercial.propostas.create') }}"
                   class="px-4 py-2 rounded-lg border text-sm text-slate-700">
                    Nova
                </a>

                <button type="button"
                        onclick="window.print()"
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium">
                    Imprimir
                </button>
            </div>
        </div>
    </div>
@endsection
