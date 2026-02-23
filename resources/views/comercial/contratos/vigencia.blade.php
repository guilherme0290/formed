@extends('layouts.comercial')
@section('title', 'Nova vigência - Contrato #' . $contrato->id)

@section('content')
    <div class="max-w-[1800px] mx-auto px-3 sm:px-4 lg:px-8 py-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('comercial.contratos.show', $contrato) }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                    ← Voltar
                </a>
                <h1 class="text-2xl font-semibold text-slate-900 mt-1">Nova vigência</h1>
                <p class="text-sm text-slate-500">Contrato #{{ $contrato->id }} • {{ $contrato->cliente->razao_social ?? 'Cliente' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('comercial.contratos.vigencia.store', $contrato) }}" class="space-y-6">
            @csrf

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 space-y-4">
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Vigência início</label>
                        <div class="relative mt-1">
                            <input type="text"
                                   inputmode="numeric"
                                   placeholder="dd/mm/aaaa"
                                   value="{{ old('vigencia_inicio_br', '') }}"
                                   class="w-full rounded-lg border-slate-300 pl-3 pr-10 py-2 focus:ring-2 focus:ring-indigo-500 js-date-text"
                                   data-date-target="campo_vigencia_inicio">
                            <button type="button"
                                    class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                    data-date-target="campo_vigencia_inicio"
                                    aria-label="Abrir calendário">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
                                </svg>
                            </button>
                            <input type="date"
                                   id="campo_vigencia_inicio"
                                   name="vigencia_inicio"
                                   value="{{ old('vigencia_inicio') }}"
                                   class="absolute right-0 top-0 h-full w-10 opacity-0 pointer-events-none js-date-hidden">
                        </div>
                        @error('vigencia_inicio')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Vigência fim (opcional)</label>
                        <div class="relative mt-1">
                            <input type="text"
                                   inputmode="numeric"
                                   placeholder="dd/mm/aaaa"
                                   value="{{ old('vigencia_fim_br', '') }}"
                                   class="w-full rounded-lg border-slate-300 pl-3 pr-10 py-2 focus:ring-2 focus:ring-indigo-500 js-date-text"
                                   data-date-target="campo_vigencia_fim">
                            <button type="button"
                                    class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                    data-date-target="campo_vigencia_fim"
                                    aria-label="Abrir calendário">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
                                </svg>
                            </button>
                            <input type="date"
                                   id="campo_vigencia_fim"
                                   name="vigencia_fim"
                                   value="{{ old('vigencia_fim') }}"
                                   class="absolute right-0 top-0 h-full w-10 opacity-0 pointer-events-none js-date-hidden">
                        </div>
                        @error('vigencia_fim')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Observação</label>
                        <input type="text" name="observacao" value="{{ old('observacao') }}"
                               placeholder="Ex: reajuste anual, novo escopo..."
                               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                        @error('observacao')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="text-xs text-slate-500">Ao salvar, a vigência atual será registrada no histórico e os novos preços passam a valer a partir da data escolhida.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-800">Ajuste de preços</h2>
                    <span class="text-xs text-slate-500">Edite apenas o valor unitário</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                        <tr class="text-left text-slate-600">
                            <th class="px-5 py-3 font-semibold">Serviço</th>
                            <th class="px-5 py-3 font-semibold">Descrição</th>
                            <th class="px-5 py-3 font-semibold w-40">Valor atual</th>
                            <th class="px-5 py-3 font-semibold w-40">Novo valor</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @foreach($contrato->itens as $item)
                            <tr>
                                <td class="px-5 py-3 font-semibold text-slate-800">{{ $item->servico->nome ?? 'Serviço' }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $item->descricao_snapshot ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-700">
                                    R$ {{ number_format((float) $item->preco_unitario_snapshot, 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3">
                                    <input type="hidden" name="itens[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                    <input type="number" step="0.01" min="0"
                                           name="itens[{{ $loop->index }}][preco_unitario_snapshot]"
                                           value="{{ old('itens.'.$loop->index.'.preco_unitario_snapshot', $item->preco_unitario_snapshot) }}"
                                           class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                                    @error('itens.'.$loop->index.'.preco_unitario_snapshot')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('comercial.contratos.show', $contrato) }}"
                   class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700">Cancelar</a>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 shadow">
                    Salvar nova vigência
                </button>
            </div>
        </form>
    </div>
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
