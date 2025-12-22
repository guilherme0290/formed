@extends('layouts.comercial')
@section('title', 'Proposta Comercial')

@section('content')
    @php
        $status = strtoupper((string) ($proposta->status ?? 'RASCUNHO'));
        $statusBadge = match ($status) {
            'ENVIADA' => 'bg-amber-50 text-amber-800 border-amber-200',
            'FECHADA' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'CANCELADA' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    @endphp

    <div class="max-w-5xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div>
            <a href="{{ route('comercial.propostas.index') }}"
               class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800">
                ← Voltar
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 bg-gradient-to-r from-emerald-700 via-emerald-600 to-emerald-500 text-white">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-2xl bg-white/15 flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('storage/logo.svg') }}" alt="Formed" class="h-9 w-auto">
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-[0.2em] text-emerald-100">Proposta Comercial</div>
                            <h1 class="text-xl font-semibold tracking-tight">{{ $proposta->codigo ?? ('Proposta #'.$proposta->id) }}</h1>
                            <p class="text-xs text-emerald-100 mt-1">
                                Criada em {{ optional($proposta->created_at)->format('d/m/Y') ?? '—' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $statusBadge }}">
                            {{ str_replace('_', ' ', $status) }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-900/40 text-emerald-50">
                            Vendedor: {{ $proposta->vendedor->name ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                @if (session('ok'))
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                        {{ session('ok') }}
                    </div>
                @endif
                @if (session('erro'))
                    <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
                        {{ session('erro') }}
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-xs font-semibold text-slate-500 uppercase">Cliente</div>
                        <div class="text-sm font-semibold text-slate-900 mt-1">
                            {{ $proposta->cliente->razao_social ?? '—' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $proposta->cliente->email ?? 'E-mail não informado' }}
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="text-xs font-semibold text-slate-500 uppercase">Forma de pagamento</div>
                        <div class="text-sm font-semibold text-slate-900 mt-1">
                            {{ $proposta->forma_pagamento ?? '—' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            Itens: {{ $proposta->itens->count() }}
                        </div>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                        <div class="text-xs font-semibold text-emerald-700 uppercase">Valor total</div>
                        <div class="text-2xl font-semibold text-emerald-800 mt-2">
                            R$ {{ number_format($proposta->valor_total, 2, ',', '.') }}
                        </div>
                        <div class="text-xs text-emerald-700 mt-1">
                            {{ $proposta->incluir_esocial ? 'Inclui eSocial' : 'Sem eSocial' }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="text-xs font-semibold text-slate-500 uppercase mb-2">Contratada</div>
                        <div class="text-sm font-semibold text-slate-900">
                            {{ $proposta->empresa->nome_fantasia ?? 'FORMED' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $proposta->empresa->cnpj ?? '' }}
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4 bg-slate-50">
                        <div class="text-xs font-semibold text-slate-500 uppercase mb-2">Cliente final</div>
                        <div class="text-sm font-semibold text-slate-900">
                            {{ $proposta->cliente->razao_social ?? '—' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $proposta->cliente->cnpj ?? '' }}
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                        <h2 class="text-sm font-semibold text-slate-700">Itens da proposta</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white">
                            <tr class="text-left text-slate-500">
                                <th class="px-4 py-2 font-semibold">Serviço</th>
                                <th class="px-4 py-2 font-semibold">Prazo</th>
                                <th class="px-4 py-2 font-semibold text-right">Qtd</th>
                                <th class="px-4 py-2 font-semibold text-right">Valor unit.</th>
                                <th class="px-4 py-2 font-semibold text-right">Total</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                            @foreach($proposta->itens as $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800">{{ $item->nome }}</div>
                                        @if($item->descricao)
                                            <div class="text-xs text-slate-500">{{ $item->descricao }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $item->prazo ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ $item->quantidade }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">
                                        R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                        R$ {{ number_format($item->valor_total, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($proposta->incluir_esocial)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <div class="text-xs font-semibold text-amber-700 uppercase">eSocial (mensal)</div>
                        <p class="text-sm text-amber-800 mt-1">
                            {{ $proposta->esocial_qtd_funcionarios }} colaboradores — R$
                            {{ number_format($proposta->esocial_valor_mensal, 2, ',', '.') }}/mês
                        </p>
                    </div>
                @endif

                @if($proposta->unidades->count())
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="text-xs font-semibold text-slate-500 uppercase mb-2">Unidades</div>
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach($proposta->unidades as $unidade)
                                <div class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                                    <div class="font-semibold text-slate-800">{{ $unidade->nome }}</div>
                                    <div class="text-slate-500">{{ $unidade->endereco }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="pt-4 border-t flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('comercial.propostas.create') }}"
                       class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">
                        Nova proposta
                    </a>
                    <a href="{{ route('comercial.propostas.pdf', $proposta) }}"
                       class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                        Baixar PDF
                    </a>
                    <a href="{{ route('comercial.propostas.print', $proposta) }}"
                       target="_blank"
                       rel="noopener"
                       class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                        Imprimir
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
