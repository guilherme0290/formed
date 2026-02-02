@extends('layouts.financeiro')
@section('title', 'Conta a Receber')

@section('content')
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-[0.2em] text-indigo-400">Conta a receber</div>
                <h1 class="text-3xl font-semibold text-slate-900">Conta #{{ $conta->id }}</h1>
                <p class="text-sm text-slate-500">Cliente: {{ $conta->cliente->razao_social ?? 'Cliente' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('financeiro.contas-receber') }}"
                   class="px-3 py-2 rounded-lg bg-slate-200 text-slate-800 text-xs font-semibold hover:bg-slate-300">
                    Voltar
                </a>
                <button type="button" id="abrirModalBaixa" class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">Baixar</button>
                <form method="POST" action="{{ route('financeiro.contas-receber.reabrir', $conta) }}">
                    @csrf
                    <button class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">Reabrir</button>
                </form>
                <form method="POST" action="{{ route('financeiro.contas-receber.boleto', $conta) }}">
                    @csrf
                    <button class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">Emitir Boleto</button>
                </form>
                <button type="button" id="abrirModalItem" class="px-3 py-2 rounded-lg bg-slate-800 text-white text-xs font-semibold hover:bg-slate-900">Novo Item</button>
            </div>
        </div>

        @include('financeiro.partials.tabs')

        @if(session('error'))
            <div class="rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <button type="button" data-tab-target="resumo" class="px-3 py-2 rounded-xl text-sm font-semibold bg-indigo-600 text-white">
                Resumo
            </button>
            <button type="button" data-tab-target="itens" class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Itens
            </button>
        </div>

        <section data-tab="resumo" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Resumo da conta</h2>
                    <p class="text-xs text-slate-500">Visão geral e baixas registradas</p>
                </div>
                <div class="text-xs text-slate-500">
                    Total: <strong class="text-slate-800">R$ {{ number_format((float) $conta->total, 2, ',', '.') }}</strong>
                </div>
            </header>

            <div class="p-5 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Status</p>
                    <p class="text-lg font-semibold text-slate-900">{{ ucfirst(strtolower((string) $conta->status)) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Total baixado</p>
                    <p class="text-lg font-semibold text-slate-900">R$ {{ number_format((float) $conta->total_baixado, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Saldo em aberto</p>
                    <p class="text-lg font-semibold text-slate-900">R$ {{ number_format((float) $conta->total_aberto, 2, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <section data-tab="itens" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden hidden">
            <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Itens da conta</h2>
                    <p class="text-xs text-slate-500">Detalhes das vendas e itens avulsos</p>
                </div>
            </header>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                            <th class="px-4 py-3 text-left font-semibold">Data</th>
                            <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Valor</th>
                            <th class="px-4 py-3 text-right font-semibold">Baixado</th>
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
                                $servicoNome = $item->servico?->nome ?? $item->descricao ?? $item->vendaItem?->descricao_snapshot ?? 'Serviço';
                                $baixado = $item->total_baixado;
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3 text-slate-800">{{ $servicoNome }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($item->data_realizacao)->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($item->vencimento)->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $badge }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format((float) $item->valor, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format((float) $baixado, 2, ',', '.') }}</td>
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
                    <h3 class="text-sm font-semibold text-slate-900">Registrar baixa</h3>
                    <button type="button" data-fechar-modal-baixa class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form method="POST" action="{{ route('financeiro.contas-receber.baixar', $conta) }}" class="space-y-3" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Valor recebido</label>
                        <input type="number" step="0.01" name="valor" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required />
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Data do pagamento</label>
                        <input type="date" name="pago_em" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
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
                        <label class="text-xs font-semibold text-slate-600">Comprovante de pagamento</label>
                        <input type="file" name="comprovante" accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required />
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Observação</label>
                        <textarea name="observacao" rows="3" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" placeholder="Detalhes sobre a baixa"></textarea>
                    </div>

                    <button class="w-full px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                        Confirmar baixa
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="modalItem" class="fixed inset-0 z-[90] hidden overflow-y-auto">
        <div class="absolute inset-0 bg-slate-900/50" data-fechar-modal></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Adicionar item avulso</h3>
                    <button type="button" data-fechar-modal class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form method="POST" action="{{ route('financeiro.contas-receber.itens.store', $conta) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Serviço (opcional)</label>
                        <select name="servico_id" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                            <option value="" class="text-slate-900">Selecione</option>
                            @foreach($servicos as $servico)
                                <option value="{{ $servico->id }}" class="text-slate-900">{{ $servico->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Descrição (opcional)</label>
                        <input type="text" name="descricao" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" placeholder="Detalhe do item" />
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Data</label>
                        <input type="date" name="data_realizacao" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Vencimento</label>
                        <input type="date" name="vencimento" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Valor</label>
                        <input type="number" step="0.01" name="valor" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required />
                    </div>

                    <button class="w-full px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        Incluir item
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('modalItem');
            const abrirModal = document.getElementById('abrirModalItem');
            const fecharModalBtns = document.querySelectorAll('[data-fechar-modal]');
            const modalBaixa = document.getElementById('modalBaixa');
            const abrirModalBaixa = document.getElementById('abrirModalBaixa');
            const fecharModalBaixaBtns = document.querySelectorAll('[data-fechar-modal-baixa]');
            const tabButtons = document.querySelectorAll('[data-tab-target]');
            const tabSections = document.querySelectorAll('[data-tab]');

            const abrir = () => modal.classList.remove('hidden');
            const fechar = () => modal.classList.add('hidden');
            const abrirBaixa = () => modalBaixa.classList.remove('hidden');
            const fecharBaixa = () => modalBaixa.classList.add('hidden');

            abrirModal.addEventListener('click', abrir);
            fecharModalBtns.forEach(btn => btn.addEventListener('click', fechar));
            abrirModalBaixa.addEventListener('click', abrirBaixa);
            fecharModalBaixaBtns.forEach(btn => btn.addEventListener('click', fecharBaixa));

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const target = button.dataset.tabTarget;
                    tabSections.forEach(section => {
                        section.classList.toggle('hidden', section.dataset.tab !== target);
                    });
                    tabButtons.forEach(btn => {
                        const active = btn.dataset.tabTarget === target;
                        btn.classList.toggle('bg-indigo-600', active);
                        btn.classList.toggle('text-white', active);
                        btn.classList.toggle('text-slate-700', !active);
                        btn.classList.toggle('hover:bg-slate-100', !active);
                    });
                });
            });
        });
    </script>
@endsection
