@extends('layouts.financeiro')
@section('title', 'Conta a Receber')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canCreate = $isMaster || isset($permissionMap['financeiro.contas-receber.create']);
        $canUpdate = $isMaster || isset($permissionMap['financeiro.contas-receber.update']);
        $canDelete = $isMaster || isset($permissionMap['financeiro.contas-receber.delete']);
    @endphp
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-xs uppercase tracking-[0.2em] text-indigo-400">Conta a receber</div>
                <h1 class="text-3xl font-semibold text-slate-900">Conta #{{ $conta->id }}</h1>
                <p class="text-sm text-slate-500">Cliente: {{ $conta->cliente->razao_social ?? 'Cliente' }}</p>
            </div>
            <div class="w-full md:w-auto flex flex-nowrap items-center justify-end gap-1.5 whitespace-nowrap overflow-x-auto md:overflow-visible pb-1 md:pb-0">
                <a href="{{ route('financeiro.contas-receber') }}"
                   class="inline-flex h-8 shrink-0 items-center justify-center whitespace-nowrap px-2.5 rounded-lg bg-slate-200 text-slate-800 text-xs font-semibold leading-none hover:bg-slate-300">
                    Voltar
                </a>
                <button type="button"
                        id="abrirModalBaixa"
                        class="inline-flex h-8 shrink-0 items-center justify-center whitespace-nowrap px-2.5 rounded-lg text-xs font-semibold leading-none {{ $canUpdate ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                        @if(!$canUpdate) disabled title="Usuario sem permissao" @endif>Baixar</button>
                <form method="POST" action="{{ route('financeiro.contas-receber.reabrir', $conta) }}" class="m-0 flex shrink-0">
                    @csrf
                    <button class="inline-flex h-8 items-center justify-center whitespace-nowrap px-2.5 rounded-lg text-xs font-semibold leading-none {{ $canUpdate ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                            @if(!$canUpdate) disabled title="Usuario sem permissao" @endif>Reabrir</button>
                </form>
                <form method="POST" action="{{ route('financeiro.contas-receber.destroy', $conta) }}"
                      class="m-0 flex shrink-0"
                      id="formExcluirRecebimento">
                    @csrf
                    @method('DELETE')
                    <button class="inline-flex h-8 items-center justify-center whitespace-nowrap px-2.5 rounded-lg text-xs font-semibold leading-none {{ $canDelete ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                            @if(!$canDelete) disabled title="Usuario sem permissao" @endif>Excluir recebimento</button>
                </form>
                <form method="POST" action="{{ route('financeiro.contas-receber.boleto', $conta) }}" class="m-0 flex shrink-0">
                    @csrf
                    <button class="inline-flex h-8 items-center justify-center whitespace-nowrap px-2.5 rounded-lg text-xs font-semibold leading-none {{ $canUpdate ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                            @if(!$canUpdate) disabled title="Usuario sem permissao" @endif>Emitir Boleto</button>
                </form>
                <button type="button"
                        id="abrirModalItem"
                        class="inline-flex h-8 shrink-0 items-center justify-center whitespace-nowrap px-2.5 rounded-lg text-xs font-semibold leading-none {{ $canCreate ? 'bg-slate-800 text-white hover:bg-slate-900' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                        @if(!$canCreate) disabled title="Usuario sem permissao" @endif>Novo Item</button>
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
                            <th class="px-4 py-3 text-left font-semibold">ServiÃ§o</th>
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
                                $servicoNome = $item->servico?->nome ?? $item->descricao ?? $item->vendaItem?->descricao_snapshot ?? 'ServiÃ§o';
                                $baixado = $item->total_baixado;
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3 text-slate-800">{{ $servicoNome }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($item->data_realizacao)->format('d/m/Y') ?? 'â€”' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($item->vencimento)->format('d/m/Y') ?? 'â€”' }}</td>
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
                    <button type="button" data-fechar-modal-baixa class="text-slate-400 hover:text-slate-600">âœ•</button>
                </div>

                <form method="POST" action="{{ route('financeiro.contas-receber.baixar', $conta) }}" class="space-y-3" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Valor recebido</label>
                        <input type="number" step="0.01" name="valor" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required />
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Data do pagamento</label>
                        <div class="relative">
    <input type="text"
           inputmode="numeric"
           placeholder="dd/mm/aaaa"
           class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm pl-3 pr-10 py-2 js-date-text"
           data-date-target="cr_show_pago_em" />
    <button type="button"
            class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
            data-date-target="cr_show_pago_em"
            aria-label="Abrir calendário">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
        </svg>
    </button>
    <input type="hidden" id="cr_show_pago_em" name="pago_em" />
</div>
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
                        <label class="text-xs font-semibold text-slate-600">ObservaÃ§Ã£o</label>
                        <textarea name="observacao" rows="3" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" placeholder="Detalhes sobre a baixa"></textarea>
                    </div>

                    <button class="w-full px-4 py-2 rounded-xl text-sm font-semibold {{ $canUpdate ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                            @if(!$canUpdate) disabled title="Usuario sem permissao" @endif>
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
                    <button type="button" data-fechar-modal class="text-slate-400 hover:text-slate-600">âœ•</button>
                </div>

                <form method="POST" action="{{ route('financeiro.contas-receber.itens.store', $conta) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-slate-600">ServiÃ§o (opcional)</label>
                        <select name="servico_id" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                            <option value="" class="text-slate-900">Selecione</option>
                            @foreach($servicos as $servico)
                                <option value="{{ $servico->id }}" class="text-slate-900">{{ $servico->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">DescriÃ§Ã£o (opcional)</label>
                        <input type="text" name="descricao" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" placeholder="Detalhe do item" />
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Data</label>
                        <div class="relative">
    <input type="text"
           inputmode="numeric"
           placeholder="dd/mm/aaaa"
           class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm pl-3 pr-10 py-2 js-date-text"
           data-date-target="cr_show_data_realizacao" />
    <button type="button"
            class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
            data-date-target="cr_show_data_realizacao"
            aria-label="Abrir calendário">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
        </svg>
    </button>
    <input type="hidden" id="cr_show_data_realizacao" name="data_realizacao" />
</div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Vencimento</label>
                        <div class="relative">
    <input type="text"
           inputmode="numeric"
           placeholder="dd/mm/aaaa"
           class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm pl-3 pr-10 py-2 js-date-text"
           data-date-target="cr_show_vencimento" />
    <button type="button"
            class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
            data-date-target="cr_show_vencimento"
            aria-label="Abrir calendário">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
        </svg>
    </button>
    <input type="hidden" id="cr_show_vencimento" name="vencimento" />
</div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Valor</label>
                        <input type="number" step="0.01" name="valor" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" required />
                    </div>

                    <button class="w-full px-4 py-2 rounded-xl text-sm font-semibold {{ $canCreate ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                            @if(!$canCreate) disabled title="Usuario sem permissao" @endif>
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
            const formExcluirRecebimento = document.getElementById('formExcluirRecebimento');
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

            if (formExcluirRecebimento) {
                formExcluirRecebimento.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const mensagem = 'Deseja excluir este recebimento e devolver os itens para vendas pendentes?';
                    let confirmado = false;

                    if (typeof window.uiConfirm === 'function') {
                        confirmado = await window.uiConfirm(mensagem);
                    } else {
                        confirmado = window.confirm(mensagem);
                    }

                    if (confirmado) {
                        formExcluirRecebimento.submit();
                    }
                });
            }

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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.flatpickr) {
            return;
        }

        if (flatpickr.l10ns && flatpickr.l10ns.pt) {
            flatpickr.localize(flatpickr.l10ns.pt);
        }

        function maskBrDate(value) {
            const digits = (value || '').replace(/\D+/g, '').slice(0, 8);
            if (digits.length <= 2) return digits;
            if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
            return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
        }

        document.querySelectorAll('.js-date-text').forEach((textInput) => {
            const hiddenId = textInput.dataset.dateTarget;
            const hiddenInput = hiddenId ? document.getElementById(hiddenId) : null;
            const defaultDate = hiddenInput && hiddenInput.value ? hiddenInput.value : null;

            const fp = flatpickr(textInput, {
                allowInput: true,
                dateFormat: 'd/m/Y',
                defaultDate: defaultDate,
                onChange: function (selectedDates) {
                    if (!hiddenInput) return;
                    hiddenInput.value = selectedDates.length
                        ? flatpickr.formatDate(selectedDates[0], 'Y-m-d')
                        : '';
                },
                onClose: function (selectedDates) {
                    if (!hiddenInput) return;
                    hiddenInput.value = selectedDates.length
                        ? flatpickr.formatDate(selectedDates[0], 'Y-m-d')
                        : '';
                },
            });

            textInput.addEventListener('input', () => {
                textInput.value = maskBrDate(textInput.value);
                if (!hiddenInput) return;
                if (textInput.value.length === 10) {
                    const parsed = fp.parseDate(textInput.value, 'd/m/Y');
                    hiddenInput.value = parsed ? fp.formatDate(parsed, 'Y-m-d') : '';
                }
            });

            textInput.addEventListener('blur', () => {
                if (!hiddenInput) return;
                const parsed = fp.parseDate(textInput.value, 'd/m/Y');
                hiddenInput.value = parsed ? fp.formatDate(parsed, 'Y-m-d') : '';
            });
        });

        document.querySelectorAll('.date-picker-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.dateTarget;
                const textInput = targetId
                    ? document.querySelector(`.js-date-text[data-date-target="${targetId}"]`)
                    : null;
                if (textInput && textInput._flatpickr) {
                    textInput.focus();
                    textInput._flatpickr.open();
                }
            });
        });
    });
</script>
@endsection



