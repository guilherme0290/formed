@extends('layouts.financeiro')
@section('title', 'Conta a Pagar')
@section('page-container', 'w-full p-0')

@section('content')
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-[0.2em] text-indigo-400">Conta a pagar</div>
                <h1 class="text-3xl font-semibold text-slate-900">Conta #{{ $conta->id }}</h1>
                <p class="text-sm text-slate-500">Fornecedor: {{ $conta->fornecedor->razao_social ?? 'Fornecedor' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('financeiro.contas-pagar.index') }}"
                   class="px-3 py-2 rounded-lg bg-slate-200 text-slate-800 text-xs font-semibold hover:bg-slate-300">Voltar</a>
                <button type="button" id="abrirModalBaixa" class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">Baixar</button>
                <form method="POST" action="{{ route('financeiro.contas-pagar.reabrir', $conta) }}">
                    @csrf
                    <button class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">Reabrir</button>
                </form>
                <button type="button" id="abrirModalItem" class="px-3 py-2 rounded-lg bg-slate-800 text-white text-xs font-semibold hover:bg-slate-900">Novo Item</button>
            </div>
        </div>

        @include('financeiro.partials.tabs')

        @if(session('error'))
            <div class="rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <button type="button" data-tab-target="resumo" class="px-3 py-2 rounded-xl text-sm font-semibold bg-indigo-600 text-white">Resumo</button>
            <button type="button" data-tab-target="itens" class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100">Itens</button>
        </div>

        <section data-tab="resumo" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Resumo da conta</h2>
                    <p class="text-xs text-slate-500">Visão geral e pagamentos registrados</p>
                </div>
                <div class="text-xs text-slate-500">Total: <strong class="text-slate-800">R$ {{ number_format((float) $conta->total, 2, ',', '.') }}</strong></div>
            </header>

            <div class="p-5 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Status</p>
                    <p class="text-lg font-semibold text-slate-900">{{ ucfirst(strtolower((string) $conta->status)) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Total pago</p>
                    <p class="text-lg font-semibold text-slate-900">R$ {{ number_format((float) $conta->total_baixado, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Saldo em aberto</p>
                    <p class="text-lg font-semibold text-slate-900">R$ {{ number_format((float) $conta->total_aberto, 2, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <section data-tab="itens" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden hidden">
            <header class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-800">Itens da conta</h2>
            </header>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Categoria</th>
                        <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                        <th class="px-4 py-3 text-left font-semibold">Competência</th>
                        <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Valor</th>
                        <th class="px-4 py-3 text-right font-semibold">Pago</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($conta->itens as $item)
                        @php
                            $status = strtoupper((string) $item->status);
                            $badge = match(true) {
                                $status === 'BAIXADO' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                $item->vencido => 'bg-rose-50 text-rose-700 border-rose-100',
                                $status === 'CANCELADO' => 'bg-slate-100 text-slate-700 border-slate-200',
                                default => 'bg-amber-50 text-amber-700 border-amber-100',
                            };
                            $label = $item->vencido ? 'Vencido' : ucfirst(strtolower($status));
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-slate-700">{{ $item->categoria ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $item->descricao }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ optional($item->data_competencia)->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ optional($item->vencimento)->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $badge }}">{{ $label }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format((float) $item->valor, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format((float) $item->total_baixado, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="modalBaixa" class="fixed inset-0 z-[90] hidden overflow-y-auto">
        <div class="absolute inset-0 bg-slate-900/50" data-fechar-modal-baixa></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Registrar pagamento</h3>
                    <button type="button" data-fechar-modal-baixa class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form method="POST" action="{{ route('financeiro.contas-pagar.baixar', $conta) }}" class="space-y-3" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Valor pago</label>
                        <input type="number" step="0.01" name="valor" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Data do pagamento</label>
                        <input type="date" name="pago_em" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Meio de pagamento</label>
                        <select name="meio_pagamento" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required>
                            <option value="">Selecione...</option>
                            @foreach($formasPagamento as $formaPagamento)
                                <option value="{{ $formaPagamento }}">{{ $formaPagamento }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Comprovante</label>
                        <input type="file" name="comprovante" accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Observação</label>
                        <textarea name="observacao" rows="3" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm"></textarea>
                    </div>
                    <button class="w-full px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">Confirmar pagamento</button>
                </form>
            </div>
        </div>
    </div>

    <div id="modalItem" class="fixed inset-0 z-[90] hidden overflow-y-auto">
        <div class="absolute inset-0 bg-slate-900/50" data-fechar-modal-item></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Adicionar item</h3>
                    <button type="button" data-fechar-modal-item class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form method="POST" action="{{ route('financeiro.contas-pagar.itens.store', $conta) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Categoria</label>
                        <input type="text" name="categoria" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" placeholder="Água, Luz, Aluguel">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Descrição</label>
                        <input type="text" name="descricao" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Competência</label>
                        <input type="date" name="data_competencia" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Vencimento</label>
                        <input type="date" name="vencimento" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Valor</label>
                        <input type="number" step="0.01" name="valor" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required>
                    </div>
                    <button class="w-full px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Incluir item</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabButtons = document.querySelectorAll('[data-tab-target]');
            const tabSections = document.querySelectorAll('[data-tab]');

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const target = button.dataset.tabTarget;
                    tabSections.forEach((section) => {
                        section.classList.toggle('hidden', section.dataset.tab !== target);
                    });
                    tabButtons.forEach((btn) => {
                        const active = btn.dataset.tabTarget === target;
                        btn.classList.toggle('bg-indigo-600', active);
                        btn.classList.toggle('text-white', active);
                        btn.classList.toggle('text-slate-700', !active);
                        btn.classList.toggle('hover:bg-slate-100', !active);
                    });
                });
            });

            const modalBaixa = document.getElementById('modalBaixa');
            const abrirModalBaixa = document.getElementById('abrirModalBaixa');
            const fecharModalBaixaBtns = document.querySelectorAll('[data-fechar-modal-baixa]');

            const modalItem = document.getElementById('modalItem');
            const abrirModalItem = document.getElementById('abrirModalItem');
            const fecharModalItemBtns = document.querySelectorAll('[data-fechar-modal-item]');

            abrirModalBaixa.addEventListener('click', () => modalBaixa.classList.remove('hidden'));
            abrirModalItem.addEventListener('click', () => modalItem.classList.remove('hidden'));
            fecharModalBaixaBtns.forEach((btn) => btn.addEventListener('click', () => modalBaixa.classList.add('hidden')));
            fecharModalItemBtns.forEach((btn) => btn.addEventListener('click', () => modalItem.classList.add('hidden')));
        });
    </script>
@endsection
