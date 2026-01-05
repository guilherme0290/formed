@extends('layouts.financeiro')
@section('title', 'Itens da Conta a Receber')

@section('content')
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-6 space-y-6">
        <div class="flex flex-col gap-2">
            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-indigo-400">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-2xl bg-indigo-500/20 text-pink-100 text-lg">🧾</span>
                Itens do contas a receber
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-semibold text-slate-900">{{ $cliente->razao_social ?? 'Cliente' }}</h1>
                <span class="text-sm text-slate-500">Revise os itens e finalize a conta</span>
            </div>
        </div>

        @if(session('error'))
            <div class="rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form id="contaReceberForm" method="POST" action="{{ route('financeiro.contas-receber.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
            @foreach($itens as $item)
                <input type="hidden" name="itens[]" value="{{ $item->id }}">
            @endforeach

            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Itens selecionados</h2>
                        <p class="text-xs text-slate-500">Base para geração da conta a receber</p>
                    </div>
                    <button type="button" id="abrirModalItem" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                        Novo Item
                    </button>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm" id="itensTable">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                <th class="px-4 py-3 text-left font-semibold">Data realização</th>
                                <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($itens as $item)
                                @php
                                    $venda = $item->venda;
                                    $dataRealizacao = $venda?->tarefa?->finalizado_em ?? $venda?->created_at;
                                    $servicoNome = $item->servico?->nome ?? $item->descricao_snapshot ?? 'Serviço';
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-slate-800">{{ $servicoNome }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $dataRealizacao?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">—</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                            Em aberto
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                        R$ {{ number_format((float) $item->subtotal_snapshot, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr id="manualItemsAnchor"></tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-slate-100 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Vencimento padrão</label>
                            <input type="date" name="vencimento" required class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Pago em (opcional)</label>
                            <input type="date" name="pago_em" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                        </div>
                    </div>
                    <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        Gerar Contas a Receber
                    </button>
                </div>
            </section>

            <div id="manualItemsContainer"></div>
        </form>
    </div>

    <div id="modalItem" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/40" data-fechar-modal></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Adicionar item avulso</h3>
                    <button type="button" data-fechar-modal class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Serviço (opcional)</label>
                    <select id="modalServico" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                        <option value="" class="text-slate-900">Selecione</option>
                        @foreach($servicos as $servico)
                            <option value="{{ $servico->id }}" class="text-slate-900">{{ $servico->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Descrição (opcional)</label>
                    <input type="text" id="modalDescricao" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" placeholder="Detalhe do item" />
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Data</label>
                    <input type="date" id="modalData" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Vencimento</label>
                    <input type="date" id="modalVencimento" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Valor</label>
                    <input type="number" step="0.01" id="modalValor" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                </div>

                <button type="button" id="salvarItem" class="w-full px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                    Incluir item
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('modalItem');
            const abrirModal = document.getElementById('abrirModalItem');
            const fecharModalBtns = document.querySelectorAll('[data-fechar-modal]');
            const salvarBtn = document.getElementById('salvarItem');
            const manualContainer = document.getElementById('manualItemsContainer');
            const tableBody = document.querySelector('#itensTable tbody');
            let manualIndex = 0;

            const abrir = () => modal.classList.remove('hidden');
            const fechar = () => modal.classList.add('hidden');

            abrirModal.addEventListener('click', abrir);
            fecharModalBtns.forEach(btn => btn.addEventListener('click', fechar));

            salvarBtn.addEventListener('click', () => {
                const servicoSelect = document.getElementById('modalServico');
                const descricaoInput = document.getElementById('modalDescricao');
                const dataInput = document.getElementById('modalData');
                const vencInput = document.getElementById('modalVencimento');
                const valorInput = document.getElementById('modalValor');

                const servicoId = servicoSelect.value;
                const servicoNome = servicoSelect.options[servicoSelect.selectedIndex]?.text || '';
                const descricao = descricaoInput.value.trim();
                const data = dataInput.value;
                const vencimento = vencInput.value;
                const valor = valorInput.value;

                if (!servicoId && !descricao) {
                    alert('Informe um serviço ou descrição.');
                    return;
                }

                if (!valor || parseFloat(valor) <= 0) {
                    alert('Informe um valor válido.');
                    return;
                }

                const linha = document.createElement('tr');
                linha.innerHTML = `
                    <td class="px-4 py-3 text-slate-800">${servicoNome || descricao}</td>
                    <td class="px-4 py-3 text-slate-600">${data ? data.split('-').reverse().join('/') : '—'}</td>
                    <td class="px-4 py-3 text-slate-600">${vencimento ? vencimento.split('-').reverse().join('/') : '—'}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                            Em aberto
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                        R$ ${parseFloat(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </td>
                `;
                tableBody.appendChild(linha);

                const addHidden = (name, value) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value ?? '';
                    manualContainer.appendChild(input);
                };

                addHidden(`manual_items[${manualIndex}][servico_id]`, servicoId);
                addHidden(`manual_items[${manualIndex}][descricao]`, descricao);
                addHidden(`manual_items[${manualIndex}][data_realizacao]`, data);
                addHidden(`manual_items[${manualIndex}][vencimento]`, vencimento);
                addHidden(`manual_items[${manualIndex}][valor]`, valor);

                manualIndex += 1;

                servicoSelect.value = '';
                descricaoInput.value = '';
                dataInput.value = '';
                vencInput.value = '';
                valorInput.value = '';

                fechar();
            });
        });
    </script>
@endsection
