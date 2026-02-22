<div data-tab-panel="arquivos" data-tab-panel-root="cliente" class="hidden">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden min-h-[38rem] flex flex-col">
            <div class="px-6 py-4 border-b bg-indigo-700 text-white">
                <h1 class="text-lg font-semibold">Arquivos do Cliente</h1>
            </div>

            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/60">
                <form method="GET" action="{{ route($routePrefix.'.edit', $cliente) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <input type="hidden" name="tab" value="arquivos">

                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Pesquisar por título</label>
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Ex: ASO - João da Silva"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400"
                        >
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Data início</label>
                        <input
                            type="date"
                            name="data_inicio"
                            value="{{ request('data_inicio') }}"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400"
                        >
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Data fim</label>
                        <input
                            type="date"
                            name="data_fim"
                            value="{{ request('data_fim') }}"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400"
                        >
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Tipo de serviço</label>
                        <select
                            name="servico"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400"
                        >
                            <option value="">Todos</option>
                            @foreach($servicosArquivos as $servico)
                                <option value="{{ $servico->id }}" @selected((string) $servico->id === request('servico'))>
                                    {{ $servico->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2 md:justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition"
                        >
                            Filtrar
                        </button>
                        <a
                            href="{{ route($routePrefix.'.edit', ['cliente' => $cliente, 'tab' => 'arquivos']) }}"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition"
                        >
                            Limpar
                        </a>
                    </div>
                </form>
            </div>

            <div class="p-4 md:p-5 bg-slate-50/40 flex-1 min-h-0">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col min-h-[28rem]">
                    <header class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <span>Documentos do cliente</span>
                            <span class="inline-flex items-center rounded-full bg-white/10 px-2 py-0.5 text-[11px] font-semibold">
                                {{ $arquivos->count() }} {{ $arquivos->count() === 1 ? 'item' : 'itens' }}
                            </span>
                        </div>
                        <span class="text-[12px] text-slate-200">
                            Selecione para baixar
                        </span>
                    </header>

                    <form method="POST"
                          action="{{ route($routePrefix.'.arquivos.download-selecionados', ['cliente' => $cliente]) }}"
                          class="flex flex-col min-h-0 flex-1">
                        @csrf
                        <div class="overflow-x-auto overflow-y-auto flex-1 min-h-0 max-h-[58vh] md:max-h-[62vh]">
                            <table class="w-full min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-slate-600 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-5 py-3 text-left font-semibold w-10">
                                            <input type="checkbox" class="rounded border-slate-300 js-check-all-arquivos-edicao">
                                        </th>
                                        <th class="px-5 py-3 text-left font-semibold">Serviço</th>
                                        <th class="px-5 py-3 text-left font-semibold">Documento</th>
                                        <th class="px-5 py-3 text-left font-semibold">Finalizado em</th>
                                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                                        <th class="px-5 py-3 text-center font-semibold">Documento</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @if($arquivos->isEmpty())
                                        <tr>
                                            <td colspan="6" class="px-5 py-8 text-center">
                                                <div class="text-sm font-medium text-slate-700">Nenhum documento disponível</div>
                                                <div class="mt-1 text-xs text-slate-500">Assim que houver arquivos concluídos, eles aparecerão aqui.</div>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach($arquivos as $tarefa)
                                            @php
                                                $coluna = $tarefa->coluna;
                                                $statusLabel = $coluna?->nome ?? 'Finalizado';
                                                $badge = ($coluna && $coluna->finaliza)
                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                                                    : 'bg-slate-100 text-slate-700 border-slate-200';
                                                $certificadosTreinamento = ($tarefa->anexos ?? collect())->filter(function ($anexo) {
                                                    return mb_strtolower((string) ($anexo->servico ?? '')) === 'certificado_treinamento';
                                                });
                                            @endphp
                                            <tr class="hover:bg-slate-50/70">
                                                <td class="px-5 py-3">
                                                    <input type="checkbox" name="tarefa_ids[]" value="{{ $tarefa->id }}" class="rounded border-slate-300 js-check-item-arquivo-edicao">
                                                </td>
                                                <td class="px-5 py-3 text-slate-800">
                                                    <div class="font-medium text-slate-800">{{ $tarefa->servico->nome ?? 'Serviço' }}</div>
                                                </td>
                                                <td class="px-5 py-3 text-slate-700">
                                                    <div class="max-w-md truncate" title="{{ $tarefa->titulo ?? 'Tarefa' }}">
                                                        {{ $tarefa->titulo ?? 'Tarefa' }}
                                                    </div>
                                                </td>
                                                <td class="px-5 py-3 text-slate-700">
                                                    {{ optional($tarefa->finalizado_em)->format('d/m/Y H:i') ?? '-' }}
                                                </td>
                                                <td class="px-5 py-3">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full border text-xs font-semibold {{ $badge }}">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-3 text-center">
                                                    @if(!$tarefa->documento_link && $certificadosTreinamento->isEmpty())
                                                        <span class="text-xs text-slate-400">Indisponível</span>
                                                    @else
                                                        <div class="flex flex-col items-center gap-1">
                                                            @if($tarefa->documento_link)
                                                                <a href="{{ $tarefa->documento_link }}"
                                                                   class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700"
                                                                   target="_blank" rel="noopener">
                                                                    ASO
                                                                </a>
                                                            @endif
                                                            @foreach($certificadosTreinamento as $certificado)
                                                                <a href="{{ $certificado->url }}"
                                                                   class="px-3 py-1.5 rounded-lg border border-indigo-200 text-indigo-700 text-xs font-semibold hover:bg-indigo-50"
                                                                   target="_blank" rel="noopener">
                                                                    Certificado {{ $loop->iteration }}
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="sticky bottom-0 z-10 shrink-0 px-5 py-4 border-t border-slate-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80 flex items-center justify-between gap-3 shadow-[0_-8px_16px_-12px_rgba(15,23,42,0.25)]">
                            <span class="text-xs text-slate-500">
                                Se selecionar 1 item com 1 documento, o download será direto. Com 2 ou mais itens selecionados, o sistema baixa em ZIP.
                            </span>
                            <button type="submit"
                                    id="btnBaixarArquivosSelecionadosEdicao"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                                Baixar selecionados
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.querySelector('.js-check-all-arquivos-edicao');
    const items = Array.from(document.querySelectorAll('.js-check-item-arquivo-edicao'));
    const btn = document.getElementById('btnBaixarArquivosSelecionadosEdicao');

    const updateUi = () => {
        const checkedCount = items.filter((item) => item.checked).length;
        if (btn) {
            btn.textContent = checkedCount >= 2 ? 'Baixar selecionados (ZIP)' : 'Baixar selecionado(s)';
        }
        if (checkAll) {
            checkAll.checked = items.length > 0 && checkedCount === items.length;
            checkAll.indeterminate = checkedCount > 0 && checkedCount < items.length;
        }
    };

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            items.forEach((item) => {
                item.checked = checkAll.checked;
            });
            updateUi();
        });
    }

    items.forEach((item) => {
        item.addEventListener('change', updateUi);
    });

    updateUi();
});
</script>
