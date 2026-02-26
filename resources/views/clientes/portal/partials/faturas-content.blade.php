@php
    $itensPaginados = $itens instanceof \Illuminate\Pagination\AbstractPaginator
        ? $itens->getCollection()
        : collect($itens ?? []);
    $listaFaturas = collect($itensEmAberto ?? [])->concat($itensPaginados);
    $totalRegistros = $listaFaturas->count();
    $totalAndamento = collect($itensEmAberto ?? [])->count();
@endphp

<section class="w-full px-3 md:px-5 py-4 md:py-5">
    <div class="mb-5">
        <div>
            <h1 class="text-lg md:text-xl font-semibold text-slate-900">
                Faturas e Serviços
            </h1>
            <p class="text-xs md:text-sm text-slate-500">
                Histórico de contas a receber e serviços realizados.
            </p>
        </div>
    </div>

    <div class="mb-6 grid gap-3 md:grid-cols-2">
        <div class="rounded-xl border border-blue-200 bg-blue-50/80 px-4 py-3">
            <p class="text-[11px] uppercase tracking-wide text-blue-700">Faturas em Aberto</p>
            <p class="mt-1 text-2xl font-semibold text-blue-800">R$ {{ number_format($totalFaturaAberto ?? 0, 2, ',', '.') }}</p>
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50/80 px-4 py-3">
            <p class="text-[11px] uppercase tracking-wide text-blue-700">Vencidos</p>
            <p class="mt-1 text-2xl font-semibold text-blue-800">R$ {{ number_format($totalVencido ?? 0, 2, ',', '.') }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-blue-200 bg-blue-50/40 shadow-inner overflow-hidden p-1 md:p-2">
        <div class="px-4 py-3 border-b border-blue-200 bg-blue-100/60 rounded-xl">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Faturas</p>
        </div>

        <div class="p-3 md:p-4">
            <div class="rounded-xl border border-blue-200 bg-white p-3 md:p-4 shadow-sm space-y-4 max-h-[65vh] md:max-h-[72vh] flex flex-col overflow-hidden">
                <form method="GET" action="{{ route('cliente.faturas') }}" class="flex flex-col gap-3 shrink-0">
                    <div class="grid gap-3 md:grid-cols-4">
                        <div class="md:col-span-2">
                            <label class="text-[11px] font-bold text-slate-600">Período</label>
                            <div class="mt-1 flex flex-col sm:flex-row sm:items-center gap-2">
                                <div class="relative w-full">
                                    <input type="text"
                                           inputmode="numeric"
                                           placeholder="dd/mm/aaaa"
                                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs text-slate-700 js-date-text"
                                           data-date-target="faturas_inicio">
                                    <button type="button"
                                            class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                            data-date-target="faturas_inicio"
                                            aria-label="Abrir calendario">
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
                                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs text-slate-700 js-date-text"
                                           data-date-target="faturas_fim">
                                    <button type="button"
                                            class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                            data-date-target="faturas_fim"
                                            aria-label="Abrir calendario">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
                                        </svg>
                                    </button>
                                    <input type="hidden" id="faturas_fim" name="data_fim" value="{{ $filtros['data_fim'] ?? '' }}">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-[11px] font-bold text-slate-600">Status</label>
                            <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs text-slate-700">
                                <option value="">Todos</option>
                                <option value="ABERTO" @selected(($filtros['status'] ?? '') === 'ABERTO')>Em aberto</option>
                                <option value="VENCIDO" @selected(($filtros['status'] ?? '') === 'VENCIDO')>Vencidos</option>
                                <option value="BAIXADO" @selected(($filtros['status'] ?? '') === 'BAIXADO')>Pago</option>
                            </select>
                        </div>

                        <div class="flex flex-wrap items-end gap-2 md:justify-end">
                            <button type="submit" class="inline-flex w-full sm:w-auto items-center justify-center px-4 py-2 rounded-xl bg-blue-700 text-white text-sm font-semibold hover:bg-blue-800 transition">
                                Filtrar
                            </button>
                            <a href="{{ route('cliente.faturas') }}" class="inline-flex w-full sm:w-auto items-center justify-center px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">
                                Limpar
                            </a>
                        </div>
                    </div>
                </form>

                @if($listaFaturas->isNotEmpty())
                    <div class="flex-1 min-h-0 overflow-y-auto pr-1">
                        <div class="overflow-x-auto rounded-xl border border-blue-200">
                            <div class="min-w-[880px]">
                                <div class="sticky top-0 z-10 grid grid-cols-12 gap-3 bg-blue-50 border-b border-blue-200 px-4 py-2 text-[11px] font-semibold uppercase tracking-wide text-blue-700">
                                    <div class="col-span-2">Data</div>
                                    <div class="col-span-5">Serviços</div>
                                    <div class="col-span-2">Status</div>
                                    <div class="col-span-1">Venc.</div>
                                    <div class="col-span-2 text-right">Valor</div>
                                </div>

                                <div class="divide-y divide-slate-100 bg-white">
                                    @foreach($listaFaturas as $item)
                                            @php
                                                $servicoNome = $item->servico ?? 'Servico';
                                                $servicoDisplay = $item->servico_detalhe ?? $servicoNome;
                                                if (($item->treinamento_modo ?? null) === 'pacote' && !empty($item->treinamento_pacote)) {
                                                    $servicoDisplay = 'Treinamentos NRs - ' . $item->treinamento_pacote;
                                                } elseif (($item->treinamento_modo ?? null) === 'avulso' && !empty($item->treinamento_codigos)) {
                                                    $codigosTitulo = is_array($item->treinamento_codigos)
                                                        ? array_values(array_filter($item->treinamento_codigos))
                                                        : array_values(array_filter(array_map('trim', explode(',', (string) $item->treinamento_codigos))));
                                                    if (count($codigosTitulo) === 1) {
                                                        $servicoDisplay = 'Treinamentos NRs - ' . $codigosTitulo[0];
                                                    }
                                                }

                                                $status = strtoupper((string) ($item->status ?? ''));
                                                $vencimento = !empty($item->vencimento) ? \Carbon\Carbon::parse($item->vencimento) : null;
                                                $isAndamento = $status === '' || $status === 'EM ANDAMENTO';
                                                $vencido = !$isAndamento && ($vencimento?->lt(now()->startOfDay()) ?? false);
                                                $valorReal = isset($item->valor_real) ? (float) $item->valor_real : (float) ($item->valor ?? 0);

                                                $badge = match (true) {
                                                    $isAndamento => 'bg-sky-50 text-sky-700 border-sky-100',
                                                    $status === 'BAIXADO' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                    $vencido => 'bg-rose-50 text-rose-700 border-rose-100',
                                                    default => 'bg-amber-50 text-amber-700 border-amber-100',
                                                };
                                                $label = $isAndamento ? 'Em andamento' : ($vencido ? 'Vencido' : ($status === 'BAIXADO' ? 'Pago' : 'Em aberto'));
                                            @endphp

                                            <div class="grid grid-cols-12 gap-3 px-4 py-3 text-xs text-slate-700 {{ $loop->even ? 'bg-slate-50/60' : 'bg-white' }} hover:bg-slate-100/70">
                                                <div class="col-span-2">
                                                    {{ $item->data_realizacao ? \Carbon\Carbon::parse($item->data_realizacao)->format('d/m/Y') : 'N/A' }}
                                                </div>
                                                <div class="col-span-5">
                                                    <p class="font-semibold text-slate-900">{{ $servicoDisplay }}</p>
                                                    <p class="text-[11px] text-slate-500">{{ $servicoNome }}</p>
                                                </div>
                                                <div class="col-span-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-semibold {{ $badge }}">
                                                        {{ $label }}
                                                    </span>
                                                </div>
                                                <div class="col-span-1">
                                                    {{ $vencimento?->format('d/m/Y') ?? '-' }}
                                                </div>
                                                <div class="col-span-2 text-right font-semibold text-slate-900">
                                                    R$ {{ number_format($valorReal, 2, ',', '.') }}
                                                </div>
                                            </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                @else
                    <div class="mt-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-6 text-center">
                        <p class="text-xs md:text-sm text-slate-500">
                            Nenhuma cobranca encontrada.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

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
                if (defaultDate) {
                    fp.setDate(defaultDate, false, 'Y-m-d');
                }

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
                        const hiddenInput = targetId ? document.getElementById(targetId) : null;
                        if (hiddenInput && hiddenInput.value) {
                            textInput._flatpickr.setDate(hiddenInput.value, false, 'Y-m-d');
                            textInput._flatpickr.jumpToDate(hiddenInput.value);
                        } else {
                            textInput._flatpickr.jumpToDate(new Date());
                        }
                        textInput.focus();
                        textInput._flatpickr.open();
                    }
                });
            });
        });
    </script>
@endpush

