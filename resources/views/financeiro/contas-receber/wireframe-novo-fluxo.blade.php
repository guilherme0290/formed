@extends('layouts.financeiro')
@section('title', 'Contas a Receber - Novo Fluxo')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $aba = request('aba', 'vendas');
        $faturaStatus = request('status_fatura', 'ABERTA'); // ABERTA | PARCIAL | BAIXADA | CANCELADA
        $faturaBloqueada = strtoupper((string) $faturaStatus) === 'BAIXADA';
        $faturaSelecionada = request('fatura_id');
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex flex-col gap-2">
            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-indigo-400">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-2xl bg-indigo-500/20 text-pink-100 text-lg">💳</span>
                Contas a Receber
            </div>
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Novo fluxo oficial de faturas</h1>
                <p class="text-sm text-slate-500 mt-1">Wireframe para validação de UX/UI e regras de negócio</p>
            </div>
        </div>

        @include('financeiro.partials.tabs')

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-2">
            <div class="flex items-center gap-2 overflow-x-auto">
                <a href="{{ request()->fullUrlWithQuery(['aba' => 'vendas']) }}"
                   class="px-3 py-2 rounded-xl text-sm font-semibold whitespace-nowrap {{ $aba === 'vendas' ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    1) Vendas
                </a>
                <a href="{{ request()->fullUrlWithQuery(['aba' => 'faturas']) }}"
                   class="px-3 py-2 rounded-xl text-sm font-semibold whitespace-nowrap {{ $aba === 'faturas' ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    2) Faturas
                </a>
                @if($faturaSelecionada)
                    <a href="{{ request()->fullUrlWithQuery(['aba' => 'detalhe']) }}"
                       class="px-3 py-2 rounded-xl text-sm font-semibold whitespace-nowrap {{ $aba === 'detalhe' ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        3) Detalhe da Fatura
                    </a>
                @else
                    <span class="px-3 py-2 rounded-xl text-sm font-semibold whitespace-nowrap bg-slate-100 text-slate-400 cursor-not-allowed"
                          title="Abra o detalhe clicando em Ver na aba Faturas.">
                        3) Detalhe da Fatura
                    </span>
                @endif
            </div>
        </section>

        @if($aba === 'vendas')
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                <form method="GET" class="grid gap-4 md:grid-cols-6 items-end">
                    <input type="hidden" name="aba" value="vendas">

                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Cliente</label>
                        <select class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                            <option>Selecione...</option>
                            <option>ACME LTDA</option>
                            <option>Beta Serviços</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Período</label>
                        <div class="flex items-center gap-2">
                            <input type="date" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                            <span class="text-slate-400">a</span>
                            <input type="date" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Status</label>
                        <select class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                            <option>Todos</option>
                            <option>Sem fatura</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Filtrar</button>
                    </div>
                </form>
            </section>

            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Vendas/Serviços elegíveis</h2>
                        <p class="text-xs text-slate-500">Seleção múltipla para criação de uma fatura única</p>
                    </div>
                    <span class="text-xs text-slate-500">Wireframe</span>
                </header>

                <form class="m-4 flex flex-col h-[68vh] rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-inner">
                    <div class="px-4 py-3 border-b border-indigo-200 bg-indigo-100/60 rounded-t-2xl">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Container fixo de seleção</p>
                        <p class="text-xs text-indigo-600 mt-1">Lista rolável interna com ações mantidas fora do scroll</p>
                    </div>

                    <div class="flex-1 min-h-0 p-4 md:p-5">
                        <div class="h-full min-h-0 rounded-xl border border-indigo-200/80 bg-white/95 p-3 md:p-4 shadow-sm">
                            <div class="h-full min-h-0 overflow-auto rounded-lg border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-slate-600 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold w-12"></th>
                                            <th class="px-4 py-3 text-left font-semibold">Venda</th>
                                            <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                                            <th class="px-4 py-3 text-left font-semibold">Data</th>
                                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                                            <th class="px-4 py-3 text-right font-semibold">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @for($i = 0; $i < 10; $i++)
                                            @php
                                                $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                                                $expandId = 'venda-expand-' . $i;
                                            @endphp
                                            <tr class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50/40 cursor-pointer transition"
                                                data-expand-toggle="{{ $expandId }}"
                                                aria-expanded="false"
                                                title="Clique para ver os itens da venda">
                                                <td class="px-4 py-3 align-middle">
                                                    <input type="checkbox" class="rounded border-slate-300">
                                                </td>
                                                <td class="px-4 py-3 align-middle">
                                                    <div class="font-semibold text-slate-900">#10{{ 230 + $i }}</div>
                                                    <div class="text-xs text-indigo-600" data-expand-icon="{{ $expandId }}">Clique para ver itens ▸</div>
                                                    <div class="text-xs text-slate-500">Venda/Serviço</div>
                                                </td>
                                                <td class="px-4 py-3 text-slate-800 align-middle">ACME LTDA</td>
                                                <td class="px-4 py-3 text-slate-700 align-middle">{{ now()->subDays($i)->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 align-middle">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold border {{ $statusClass }}">
                                                        Sem fatura
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold text-slate-900 align-middle">
                                                    R$ {{ number_format(900 + ($i * 200), 2, ',', '.') }}
                                                </td>
                                            </tr>
                                            <tr id="{{ $expandId }}" class="hidden bg-indigo-50/30">
                                                <td colspan="6" class="px-4 pb-4 pt-0">
                                                    <div class="mt-2 rounded-xl border border-indigo-100 bg-white p-3">
                                                        <div class="flex items-center justify-between gap-2 mb-3">
                                                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">
                                                                Itens da venda #10{{ 230 + $i }}
                                                            </p>
                                                            <span class="text-xs text-slate-500">Pré-visualização</span>
                                                        </div>

                                                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                                                            <table class="min-w-full divide-y divide-slate-200 text-xs">
                                                                <thead class="bg-slate-50 text-slate-600">
                                                                    <tr>
                                                                        <th class="px-3 py-2 text-left font-semibold">Serviço</th>
                                                                        <th class="px-3 py-2 text-left font-semibold">Descrição</th>
                                                                        <th class="px-3 py-2 text-right font-semibold">Qtd</th>
                                                                        <th class="px-3 py-2 text-right font-semibold">Valor</th>
                                                                        <th class="px-3 py-2 text-right font-semibold">Subtotal</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-slate-100 bg-white">
                                                                    <tr>
                                                                        <td class="px-3 py-2 text-slate-700">ASO</td>
                                                                        <td class="px-3 py-2 text-slate-700">Exame ocupacional</td>
                                                                        <td class="px-3 py-2 text-right text-slate-700">1</td>
                                                                        <td class="px-3 py-2 text-right text-slate-700">R$ 150,00</td>
                                                                        <td class="px-3 py-2 text-right font-semibold text-slate-900">R$ 150,00</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="px-3 py-2 text-slate-700">Treinamento</td>
                                                                        <td class="px-3 py-2 text-slate-700">NR-35 básico</td>
                                                                        <td class="px-3 py-2 text-right text-slate-700">2</td>
                                                                        <td class="px-3 py-2 text-right text-slate-700">R$ 250,00</td>
                                                                        <td class="px-3 py-2 text-right font-semibold text-slate-900">R$ 500,00</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <footer class="border-t border-slate-100 bg-white px-5 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-white px-4 py-3 shadow-sm">
                            <p class="text-[11px] uppercase tracking-wide text-indigo-700 font-semibold">Resumo da seleção</p>
                            <div class="mt-1 flex flex-wrap items-center gap-3">
                                <span class="inline-flex items-center gap-2 rounded-xl bg-white border border-indigo-100 px-3 py-1.5 text-sm text-slate-700">
                                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[11px] font-bold text-white">3</span>
                                    vendas selecionadas
                                </span>
                                <span class="text-sm text-slate-500">|</span>
                                <span class="text-sm text-slate-700">Total selecionado:</span>
                                <span class="text-lg font-semibold text-indigo-700">R$ 3.500,00</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold">Limpar seleção</button>
                            <button type="button" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                                Criar Fatura
                            </button>
                        </div>
                    </footer>
                </form>
            </section>
        @endif

        @if($aba === 'faturas')
            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Faturas (ERP)</h2>
                        <p class="text-xs text-slate-500">Lista em container com ações fixas fora do scroll</p>
                    </div>
                </header>

                <div class="flex flex-col h-[68vh]">
                    <div class="flex-1 min-h-0 overflow-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Nº Fatura</th>
                                    <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                                    <th class="px-4 py-3 text-left font-semibold">Emissão</th>
                                    <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                                    <th class="px-4 py-3 text-right font-semibold">Valor</th>
                                    <th class="px-4 py-3 text-right font-semibold">Pago</th>
                                    <th class="px-4 py-3 text-right font-semibold">Saldo</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @php
                                    $mockFaturas = [
                                        ['id' => '000123', 'status' => 'ABERTA', 'valor' => 5780, 'pago' => 0],
                                        ['id' => '000124', 'status' => 'PARCIAL', 'valor' => 3000, 'pago' => 1200],
                                        ['id' => '000125', 'status' => 'BAIXADA', 'valor' => 2100, 'pago' => 2100],
                                        ['id' => '000126', 'status' => 'CANCELADA', 'valor' => 800, 'pago' => 0],
                                        ['id' => '000127', 'status' => 'ABERTA', 'valor' => 4500, 'pago' => 0],
                                        ['id' => '000128', 'status' => 'PARCIAL', 'valor' => 2600, 'pago' => 600],
                                    ];
                                @endphp

                                @foreach($mockFaturas as $fatura)
                                    @php
                                        $saldo = max($fatura['valor'] - $fatura['pago'], 0);
                                        $isBaixada = $fatura['status'] === 'BAIXADA';
                                        $badge = match($fatura['status']) {
                                            'ABERTA' => 'bg-amber-50 text-amber-700 border-amber-100',
                                            'PARCIAL' => 'bg-sky-50 text-sky-700 border-sky-100',
                                            'BAIXADA' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };
                                    @endphp
                                    <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100">
                                        <td class="px-4 py-3 text-slate-800">#{{ $fatura['id'] }}</td>
                                        <td class="px-4 py-3 text-slate-700">ACME LTDA</td>
                                        <td class="px-4 py-3 text-slate-600">01/02/2026</td>
                                        <td class="px-4 py-3 text-slate-600">15/02/2026</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($fatura['valor'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($fatura['pago'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">R$ {{ number_format($saldo, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $badge }}">
                                                {{ ucfirst(strtolower($fatura['status'])) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ request()->fullUrlWithQuery([
                                                    'aba' => 'detalhe',
                                                    'fatura_id' => $fatura['id'],
                                                    'status_fatura' => $fatura['status'],
                                                ]) }}"
                                                   class="px-2.5 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold">
                                                    Ver
                                                </a>
                                                <button class="px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Baixar</button>
                                                <button class="px-2.5 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold">Excluir baixa</button>
                                                <button class="px-2.5 py-1.5 rounded-lg bg-amber-500 text-white text-xs font-semibold">Cancelar</button>
                                                <button class="px-2.5 py-1.5 rounded-lg text-xs font-semibold {{ $isBaixada ? 'bg-slate-200 text-slate-500 cursor-not-allowed' : 'bg-rose-600 text-white' }}"
                                                        @if($isBaixada) disabled title="Ação disponível somente para faturas sem baixa." @endif>
                                                    Remover
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <footer class="border-t border-slate-100 bg-white px-5 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <p class="text-sm text-slate-600">Total de faturas: <strong>6</strong></p>
                        <div class="flex items-center gap-2">
                            <button type="button" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold">Atualizar lista</button>
                            <button type="button" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold">Exportar</button>
                        </div>
                    </footer>
                </div>
            </section>
        @endif

        @if($aba === 'detalhe' && $faturaSelecionada)
            @if($faturaBloqueada)
                <section class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-rose-800">Fatura baixada. Edição bloqueada.</p>
                        <p class="text-xs text-rose-700">Para alterar conteúdo/remover, exclua a baixa primeiro.</p>
                    </div>
                    <button class="px-3 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Excluir baixa</button>
                </section>
            @endif

            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-slate-800">1) Cabeçalho da fatura</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-12">
                    <div class="md:col-span-7 rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide font-semibold text-indigo-700">Cliente</p>
                        <p class="mt-1 text-2xl md:text-3xl font-semibold text-slate-900 leading-tight">ACME LTDA</p>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                            <span class="text-slate-600">CNPJ <strong class="text-slate-800">00.000.000/0001-00</strong></span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-600">Emissão <strong class="text-slate-800">01/02/2026</strong></span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-600">Vencimento <strong class="text-slate-800">15/02/2026</strong></span>
                        </div>
                    </div>

                    <div class="md:col-span-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wide font-semibold text-slate-500">Fatura</p>
                        <div class="mt-1 flex items-center justify-between gap-3">
                            <p class="text-2xl font-semibold text-slate-900">#{{ $faturaSelecionada ?? '000124' }}</p>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border
                                {{ $faturaBloqueada ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-100' }}">
                                {{ ucfirst(strtolower($faturaStatus)) }}
                            </span>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Total</p>
                                <p class="font-semibold text-slate-900">R$ 3.000,00</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Pago</p>
                                <p class="font-semibold text-slate-900">R$ 1.200,00</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-wide text-slate-500">Saldo</p>
                                <p class="font-semibold text-indigo-700">R$ 1.800,00</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-slate-800">2) Itens da fatura (com data)</h2>
                <p class="text-xs text-slate-500 mt-1">Itens listados em container fixo com rolagem interna</p>

                <div class="mt-4 flex flex-col h-[54vh] rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-inner">
                    <div class="px-4 py-3 border-b border-indigo-200 bg-indigo-100/60 rounded-t-2xl">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Container fixo de itens da fatura</p>
                        <p class="text-xs text-indigo-600 mt-1">Visualização detalhada por item com data de realização</p>
                    </div>

                    <div class="flex-1 min-h-0 p-4 md:p-5">
                        <div class="h-full min-h-0 rounded-xl border border-indigo-200/80 bg-white/95 p-3 md:p-4 shadow-sm">
                            <div class="h-full min-h-0 overflow-auto rounded-lg border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-slate-600 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Data</th>
                                            <th class="px-4 py-3 text-left font-semibold">Venda</th>
                                            <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                            <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                                            <th class="px-4 py-3 text-right font-semibold">Qtd</th>
                                            <th class="px-4 py-3 text-right font-semibold">Valor</th>
                                            <th class="px-4 py-3 text-right font-semibold">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @for($item = 1; $item <= 12; $item++)
                                            <tr class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50/40">
                                                <td class="px-4 py-3 text-slate-700">{{ now()->subDays($item)->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 text-slate-800">#10{{ 200 + (($item % 3) + 1) }}</td>
                                                <td class="px-4 py-3 text-slate-800">{{ $item % 2 === 0 ? 'ASO' : 'Treinamento' }}</td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ $item % 2 === 0 ? 'Exame ocupacional' : 'NR-35 básico' }}
                                                </td>
                                                <td class="px-4 py-3 text-right text-slate-700">{{ $item % 2 === 0 ? 1 : 2 }}</td>
                                                <td class="px-4 py-3 text-right text-slate-700">R$ {{ number_format($item % 2 === 0 ? 150 : 250, 2, ',', '.') }}</td>
                                                <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                                    R$ {{ number_format($item % 2 === 0 ? 150 : 500, 2, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-slate-800">3) Ações financeiras da fatura</h2>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button class="px-3 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold">Dar baixa</button>
                    <button class="px-3 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Excluir baixa</button>
                    <button class="px-3 py-2 rounded-xl bg-amber-500 text-white text-sm font-semibold">Cancelar fatura</button>
                    <button type="button"
                            id="btnRemoverFatura"
                            class="px-3 py-2 rounded-xl text-sm font-semibold {{ $faturaBloqueada ? 'bg-slate-200 text-slate-500 cursor-not-allowed' : 'bg-rose-600 text-white' }}"
                            @if($faturaBloqueada) data-bloqueada="1" title="Ação disponível somente para faturas sem baixa." @endif>
                        Remover fatura
                    </button>
                </div>
            </section>

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
                            <button type="button" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Excluir baixa agora</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($aba === 'detalhe' && !$faturaSelecionada)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-sm font-semibold text-amber-800">Detalhe da fatura indisponível</p>
                <p class="text-xs text-amber-700 mt-1">Para abrir esta aba, acesse <strong>Faturas</strong> e clique em <strong>Ver</strong>.</p>
            </section>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnRemover = document.getElementById('btnRemoverFatura');
            const modal = document.getElementById('modalRemocaoBloqueada');
            const fechar = document.getElementById('fecharModalRemocaoBloqueada');
            const expandRows = document.querySelectorAll('tr[data-expand-toggle]');

            expandRows.forEach(function (row) {
                row.addEventListener('click', function (event) {
                    const clickedInteractive = event.target.closest('input, button, a, label');
                    if (clickedInteractive) {
                        return;
                    }

                    const targetId = row.dataset.expandToggle;
                    const target = targetId ? document.getElementById(targetId) : null;
                    const icon = targetId ? document.querySelector('[data-expand-icon="' + targetId + '"]') : null;
                    if (!target) {
                        return;
                    }

                    const isHidden = target.classList.contains('hidden');
                    target.classList.toggle('hidden', !isHidden);
                    row.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                    row.classList.toggle('bg-indigo-50/60', isHidden);
                    if (icon) {
                        icon.textContent = isHidden ? 'Clique para ocultar itens ▾' : 'Clique para ver itens ▸';
                        icon.classList.toggle('font-semibold', isHidden);
                    }
                });
            });

            if (btnRemover && modal && fechar) {
                btnRemover.addEventListener('click', function () {
                    if (btnRemover.dataset.bloqueada === '1') {
                        modal.classList.remove('hidden');
                    }
                });

                fechar.addEventListener('click', function () {
                    modal.classList.add('hidden');
                });
            }
        });
    </script>
@endsection
