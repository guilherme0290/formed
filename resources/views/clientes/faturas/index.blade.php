{{-- resources/views/clientes/faturas/index.blade.php --}}
@extends('layouts.cliente')

@section('title', 'Faturas e Serviços')
@section('page-container', 'w-full p-0')

@section('content')
    <section class="w-full px-3 md:px-5 py-4 md:py-5">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl md:text-2xl font-semibold text-slate-900">
                    Faturas e serviços realizados
                </h2>
                <p class="text-xs md:text-sm text-slate-500">
                    Histórico de contas a receber e seus valores.
                </p>
            </div>

            <a href="{{ route('cliente.dashboard') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-900 text-white text-xs md:text-sm font-semibold shadow">
                Voltar ao painel
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6">
            <form method="GET" class="grid gap-4 md:grid-cols-5 items-end">
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Período</label>
                    <div class="flex items-center gap-2">
                        <div class="relative w-full">
                            <input type="text"
                                   inputmode="numeric"
                                   placeholder="dd/mm/aaaa"
                                   class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm pl-3 pr-10 py-2 js-date-text"
                                   data-date-target="faturas_inicio">
                            <button type="button"
                                    class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                    data-date-target="faturas_inicio"
                                    aria-label="Abrir calendário">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
                                </svg>
                            </button>
                            <input type="hidden" id="faturas_inicio" name="data_inicio" value="{{ $filtros['data_inicio'] ?? '' }}">
                        </div>
                        <span class="text-slate-400">a</span>
                        <div class="relative w-full">
                            <input type="text"
                                   inputmode="numeric"
                                   placeholder="dd/mm/aaaa"
                                   class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm pl-3 pr-10 py-2 js-date-text"
                                   data-date-target="faturas_fim">
                            <button type="button"
                                    class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                    data-date-target="faturas_fim"
                                    aria-label="Abrir calendário">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
                                </svg>
                            </button>
                            <input type="hidden" id="faturas_fim" name="data_fim" value="{{ $filtros['data_fim'] ?? '' }}">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Período</label>
                    <select name="status" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                        <option value="" class="text-slate-900">Todos</option>
                        <option value="ABERTO" class="text-slate-900" @selected(($filtros['status'] ?? '') === 'ABERTO')>Em aberto</option>
                        <option value="VENCIDO" class="text-slate-900" @selected(($filtros['status'] ?? '') === 'VENCIDO')>Vencidos</option>
                        <option value="BAIXADO" class="text-slate-900" @selected(($filtros['status'] ?? '') === 'BAIXADO')>Pago</option>
                    </select>
                </div>
                <div class="flex items-end gap-3 md:col-span-2">
                    <button class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                        Filtrar
                    </button>
                    <a href="{{ route('cliente.faturas') }}" class="text-sm text-slate-500 hover:text-slate-700">Limpar</a>
                </div>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2 mb-6">
            <div class="rounded-2xl bg-[#059669] text-white shadow-lg shadow-emerald-900/25 p-5 flex items-center justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.18em] text-emerald-50/90">Fatura em aberto
                    </p>
                    <p class="mt-1 text-2xl md:text-3xl font-semibold">
                        R$ {{ number_format($totalFaturaAberto ?? 0, 2, ',', '.') }}
                    </p>
                    <p class="text-[11px] text-emerald-50/80 mt-1">
                        Contas em aberto + tarefas em andamento
                    </p>
                </div>
                <div class="hidden md:block text-4xl">$</div>
            </div>
            <div class="rounded-2xl bg-rose-600 text-white shadow-lg shadow-rose-900/25 p-5 flex items-center justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.18em] text-rose-100/90">
                        Vencidos
                    </p>
                    <p class="mt-1 text-2xl md:text-3xl font-semibold">
                        R$ {{ number_format($totalVencido ?? 0, 2, ',', '.') }}
                    </p>
                    <p class="text-[11px] text-rose-100/80 mt-1">
                        Faturas em atraso
                    </p>
                </div>
                <div class="hidden md:block text-4xl">!</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <header class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <span>Detalhes da fatura</span>
                </div>
                <span class="text-[12px] text-slate-200">
                    Atualizado automaticamente
                </span>
            </header>

            @if($itens->isEmpty() && ($itensEmAberto ?? collect())->isEmpty())
                <div class="p-6 text-sm text-slate-500">
                    Nenhuma cobrança encontrada. Assim que houver contas geradas, elas aparecerão aqui.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Data</th>
                                <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                                <th class="px-4 py-3 text-right font-semibold">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach(($itensEmAberto ?? collect()) as $item)
                                @php
                                    $servicoNome = $item->servico ?? 'Serviço';
                                    $status = 'EM ANDAMENTO';
                                    $vencimento = null;
                                    $valorReal = isset($item->valor_real) ? (float) $item->valor_real : (float) $item->valor;
                                    $badge = 'bg-sky-50 text-sky-700 border-sky-100';
                                    $label = 'Em andamento';
                                @endphp
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $item->data_realizacao ? \Carbon\Carbon::parse($item->data_realizacao)->format('d/m/Y') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $servicoNome }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[12px] font-semibold {{ $badge }}">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        —
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                        R$ {{ number_format($valorReal, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            @foreach($itens as $item)
                                @php
                                    $servicoNome = $item->servico ?? 'Serviço';
                                    $status = strtoupper((string) $item->status);
                                    $vencimento = $item->vencimento ? \Carbon\Carbon::parse($item->vencimento) : null;
                                    $vencido = $vencimento?->lt(now()->startOfDay()) ?? false;
                                    $valorReal = isset($item->valor_real) ? (float) $item->valor_real : (float) $item->valor;
                                    $badge = match(true) {
                                        $status === 'BAIXADO' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                        $vencido => 'bg-rose-50 text-rose-700 border-rose-100',
                                        default => 'bg-amber-50 text-amber-700 border-amber-100',
                                    };
                                    $label = $vencido ? 'Vencido' : ($status === 'BAIXADO' ? 'Pago' : 'Em aberto');
                                @endphp
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $item->data_realizacao ? \Carbon\Carbon::parse($item->data_realizacao)->format('d/m/Y') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $servicoNome }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[12px] font-semibold
                                            {{ $badge }}">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $vencimento?->format('d/m/Y') ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                        R$ {{ number_format($valorReal, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $itens->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@push('scripts')
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
@endpush
