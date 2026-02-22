@extends('layouts.financeiro')
@section('title', 'Detalhe da Fatura')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canCreate = $isMaster || isset($permissionMap['financeiro.contas-receber.create']);
        $canUpdate = $isMaster || isset($permissionMap['financeiro.contas-receber.update']);
        $canDelete = $isMaster || isset($permissionMap['financeiro.contas-receber.delete']);

        $total = (float) $conta->total;
        $pago = (float) $conta->total_baixado;
        $saldo = (float) $conta->total_aberto;
        $hasBaixa = $conta->baixas->isNotEmpty();
        $isBaixada = $saldo <= 0.0001 && $total > 0;
        $uiStatus = match (true) {
            strtoupper((string) $conta->status) === 'CANCELADO' => 'Cancelada',
            $isBaixada => 'Baixada',
            $hasBaixa => 'Parcial',
            default => 'Aberta',
        };
        $statusBadge = match ($uiStatus) {
            'Baixada' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'Parcial' => 'bg-sky-50 text-sky-700 border-sky-100',
            'Aberta' => 'bg-amber-50 text-amber-700 border-amber-100',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };

        $itensComData = $conta->itens
            ->filter(fn ($item) => !is_null($item->data_realizacao))
            ->sortByDesc(fn ($item) => optional($item->data_realizacao)?->timestamp ?? 0)
            ->values();
        $qtdItensSemData = $conta->itens->count() - $itensComData->count();
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex flex-col gap-2">
            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-indigo-400">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-2xl bg-indigo-500/20 text-pink-100 text-lg">💳</span>
                Contas a Receber
            </div>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-slate-900">3) Detalhe da Fatura</h1>
                    <p class="text-sm text-slate-500 mt-1">Visualização detalhada da fatura e ações financeiras</p>
                </div>
                <a href="{{ route('financeiro.contas-receber') }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-200 text-slate-800 text-sm font-semibold hover:bg-slate-300">
                    Voltar para Faturas
                </a>
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

        @if($isBaixada)
            <section class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-rose-800">Fatura baixada. Edição bloqueada.</p>
                    <p class="text-xs text-rose-700">Para alterar/remover a fatura, primeiro exclua a baixa.</p>
                </div>
                @if($hasBaixa)
                    <form method="POST" action="{{ route('financeiro.contas-receber.excluir-baixa', $conta) }}" class="m-0">
                        @csrf
                        <button class="px-3 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold {{ $canUpdate ? 'hover:bg-slate-800' : 'opacity-60 cursor-not-allowed' }}"
                                @if(!$canUpdate) disabled title="Usuário sem permissão" @endif>
                            Excluir baixa
                        </button>
                    </form>
                @endif
            </section>
        @endif

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-slate-800">1) Cabeçalho da fatura</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-12">
                <div class="md:col-span-7 rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide font-semibold text-indigo-700">Cliente</p>
                    <p class="mt-1 text-2xl md:text-3xl font-semibold text-slate-900 leading-tight">
                        {{ $conta->cliente->razao_social ?? $conta->cliente->nome_fantasia ?? 'Cliente' }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        @if(!empty($conta->cliente->cnpj))
                            <span class="text-slate-600">CNPJ <strong class="text-slate-800">{{ $conta->cliente->cnpj }}</strong></span>
                            <span class="text-slate-300">•</span>
                        @endif
                        <span class="text-slate-600">Emissão <strong class="text-slate-800">{{ optional($conta->created_at)->format('d/m/Y') ?? '—' }}</strong></span>
                        <span class="text-slate-300">•</span>
                        <span class="text-slate-600">Vencimento <strong class="text-slate-800">{{ optional($conta->vencimento)->format('d/m/Y') ?? '—' }}</strong></span>
                    </div>
                </div>

                <div class="md:col-span-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide font-semibold text-slate-500">Fatura</p>
                    <div class="mt-1 flex items-center justify-between gap-3">
                        <p class="text-2xl font-semibold text-slate-900">#{{ $conta->id }}</p>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $statusBadge }}">
                            {{ $uiStatus }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500">Total</p>
                            <p class="font-semibold text-slate-900">R$ {{ number_format($total, 2, ',', '.') }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500">Pago</p>
                            <p class="font-semibold text-slate-900">R$ {{ number_format($pago, 2, ',', '.') }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500">Saldo</p>
                            <p class="font-semibold text-indigo-700">R$ {{ number_format($saldo, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-slate-800">2) Itens da fatura (com data)</h2>
            <p class="text-xs text-slate-500 mt-1">Exibindo somente itens com data de realização em container fixo com rolagem interna</p>
            @if($qtdItensSemData > 0)
                <p class="text-xs text-amber-700 mt-2">{{ $qtdItensSemData }} item(ns) sem data de realização não aparecem nesta lista.</p>
            @endif

            <div class="mt-4 flex flex-col h-[54vh] rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-inner">
                <div class="px-4 py-3 border-b border-indigo-200 bg-indigo-100/60 rounded-t-2xl">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Container fixo de itens da fatura</p>
                    <p class="text-xs text-indigo-600 mt-1">Rolagem interna para leitura detalhada dos itens</p>
                </div>

                <div class="flex-1 min-h-0 p-4 md:p-5">
                    <div class="h-full min-h-0 rounded-xl border border-indigo-200/80 bg-white/95 p-3 md:p-4 shadow-sm">
                        <div class="h-full min-h-0 overflow-auto rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-slate-600 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Data</th>
                                        <th class="px-4 py-3 text-left font-semibold">Venda</th>
                                        <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                        <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                                        <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                                        <th class="px-4 py-3 text-right font-semibold">Valor</th>
                                        <th class="px-4 py-3 text-right font-semibold">Baixado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($itensComData as $item)
                                        @php
                                            $itemStatus = strtoupper((string) $item->status);
                                            $badge = match(true) {
                                                $itemStatus === 'BAIXADO' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                $item->vencido => 'bg-rose-50 text-rose-700 border-rose-100',
                                                $itemStatus === 'CANCELADO' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                default => 'bg-amber-50 text-amber-700 border-amber-100',
                                            };
                                            $label = $item->vencido ? 'Vencido' : ucfirst(strtolower($itemStatus));
                                            $servicoNome = $item->servico?->nome ?? $item->descricao ?? $item->vendaItem?->descricao_snapshot ?? 'Serviço';
                                            $baixadoItem = (float) $item->total_baixado;
                                        @endphp
                                        <tr class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50/40">
                                            <td class="px-4 py-3 text-slate-700">{{ optional($item->data_realizacao)->format('d/m/Y') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-slate-800">{{ $item->venda_id ? '#'.$item->venda_id : 'Avulso' }}</td>
                                            <td class="px-4 py-3 text-slate-800">{{ $servicoNome }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $item->descricao ?? $item->vendaItem?->descricao_snapshot ?? '—' }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ optional($item->vencimento)->format('d/m/Y') ?? '—' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold border {{ $badge }}">{{ $label }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format((float) $item->valor, 2, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($baixadoItem, 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum item com data de realização nesta fatura.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="acoes-financeiras" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-slate-800">3) Ações financeiras da fatura</h2>
            <p class="text-xs text-slate-500 mt-1">Remover fatura só é permitido sem baixa. Para remover algo baixado, primeiro exclua a baixa.</p>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button type="button"
                        id="abrirModalBaixa"
                        class="px-3 py-2 rounded-xl text-sm font-semibold {{ $canUpdate ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                        @if(!$canUpdate) disabled title="Usuário sem permissão" @endif>
                    Dar baixa
                </button>

                @if($hasBaixa)
                    <form method="POST" action="{{ route('financeiro.contas-receber.excluir-baixa', $conta) }}" class="m-0">
                        @csrf
                        <button class="px-3 py-2 rounded-xl text-sm font-semibold {{ $canUpdate ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                @if(!$canUpdate) disabled title="Usuário sem permissão" @endif>
                            Excluir baixa
                        </button>
                    </form>
                @else
                    <button type="button" class="px-3 py-2 rounded-xl bg-slate-200 text-slate-500 text-sm font-semibold cursor-not-allowed" disabled title="Fatura sem baixa.">
                        Excluir baixa
                    </button>
                @endif

                <button type="button" class="px-3 py-2 rounded-xl bg-slate-200 text-slate-500 text-sm font-semibold cursor-not-allowed" disabled title="Ação de cancelamento não implementada nesta tela.">
                    Cancelar fatura
                </button>

                <form method="POST" action="{{ route('financeiro.contas-receber.boleto', $conta) }}" class="m-0">
                    @csrf
                    <button class="px-3 py-2 rounded-xl text-sm font-semibold {{ $canUpdate ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                            @if(!$canUpdate) disabled title="Usuário sem permissão" @endif>
                        Emitir boleto
                    </button>
                </form>

                @if(!$hasBaixa)
                    <form method="POST" action="{{ route('financeiro.contas-receber.destroy', $conta) }}" id="formRemoverFatura" class="m-0">
                        @csrf
                        @method('DELETE')
                        <button class="px-3 py-2 rounded-xl text-sm font-semibold {{ $canDelete ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                @if(!$canDelete) disabled title="Usuário sem permissão" @endif>
                            Remover fatura
                        </button>
                    </form>
                @else
                    <button type="button"
                            id="btnRemoverFaturaBloqueado"
                            class="px-3 py-2 rounded-xl bg-slate-200 text-slate-500 text-sm font-semibold cursor-not-allowed"
                            title="Ação disponível somente para faturas sem baixa.">
                        Remover fatura
                    </button>
                @endif
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

                    <button class="w-full px-4 py-2 rounded-xl text-sm font-semibold {{ $canUpdate ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                            @if(!$canUpdate) disabled title="Usuário sem permissão" @endif>
                        Confirmar baixa
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="modalRemocaoBloqueada" class="fixed inset-0 z-[90] hidden">
        <div class="absolute inset-0 bg-slate-900/50"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="text-sm font-semibold text-slate-900">Não é possível remover agora</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Para remover esta fatura:
                    1) Exclua a baixa
                    2) Remova a fatura.
                </p>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" id="fecharModalRemocaoBloqueada" class="px-3 py-2 rounded-lg bg-slate-200 text-slate-800 text-sm font-semibold">Entendi</button>
                    @if($hasBaixa)
                        <form method="POST" action="{{ route('financeiro.contas-receber.excluir-baixa', $conta) }}" class="m-0">
                            @csrf
                            <button class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold {{ $canUpdate ? 'hover:bg-slate-800' : 'opacity-60 cursor-not-allowed' }}"
                                    @if(!$canUpdate) disabled title="Usuário sem permissão" @endif>
                                Excluir baixa agora
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalBaixa = document.getElementById('modalBaixa');
            const abrirModalBaixa = document.getElementById('abrirModalBaixa');
            const fecharModalBaixaBtns = document.querySelectorAll('[data-fechar-modal-baixa]');
            const btnRemoverBloqueado = document.getElementById('btnRemoverFaturaBloqueado');
            const modalRemocaoBloqueada = document.getElementById('modalRemocaoBloqueada');
            const fecharModalRemocaoBloqueada = document.getElementById('fecharModalRemocaoBloqueada');
            const formRemoverFatura = document.getElementById('formRemoverFatura');

            if (abrirModalBaixa && modalBaixa) {
                abrirModalBaixa.addEventListener('click', function () {
                    modalBaixa.classList.remove('hidden');
                });
            }

            fecharModalBaixaBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    modalBaixa?.classList.add('hidden');
                });
            });

            if (btnRemoverBloqueado && modalRemocaoBloqueada) {
                btnRemoverBloqueado.addEventListener('click', function () {
                    modalRemocaoBloqueada.classList.remove('hidden');
                });
            }

            if (fecharModalRemocaoBloqueada && modalRemocaoBloqueada) {
                fecharModalRemocaoBloqueada.addEventListener('click', function () {
                    modalRemocaoBloqueada.classList.add('hidden');
                });
            }

            if (formRemoverFatura) {
                formRemoverFatura.addEventListener('submit', function (event) {
                    event.preventDefault();
                    if (window.confirm('Deseja remover esta fatura?')) {
                        formRemoverFatura.submit();
                    }
                });
            }
        });
    </script>
@endsection
