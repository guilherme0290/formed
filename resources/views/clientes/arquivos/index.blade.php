@extends('layouts.cliente')
@section('title', 'Meus Arquivos')

@section('content')

    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl border border-blue-300 shadow-sm">
            <div class="bg-gradient-to-r from-[#123fbe] to-[#1a5de8] px-4 py-4 md:px-6 md:py-5 text-white">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-lg md:text-xl font-semibold">Meus Arquivos</h1>
                    </div>

                    <a href="{{ route('cliente.dashboard') }}"
                       class="inline-flex w-full sm:w-auto items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold border border-blue-200/50 bg-white/10 text-white hover:bg-white/20 transition">
                        &larr; Voltar aos serviços
                    </a>
                </div>
            </div>

            <header class="px-4 py-3 md:px-5 md:py-4 border-t border-blue-200 bg-blue-100/60">
                <p class="text-sm font-semibold text-blue-900">1. Filtros e ações de download</p>
            </header>

            <div class="p-4 md:p-5 space-y-4">
                <form method="GET" action="{{ route('cliente.arquivos.index') }}" class="rounded-xl border border-blue-200 bg-white p-3 md:p-4">
                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <label class="text-[11px] font-semibold text-slate-700">Tipo de serviço</label>
                            <select
                                name="servico"
                                class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos os serviços</option>
                                @foreach($servicos as $servico)
                                    <option value="{{ $servico->id }}" @selected((string) $servico->id === request('servico'))>
                                        {{ $servico->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-[11px] font-semibold text-slate-700">Data início</label>
                            <input
                                type="date"
                                name="data_inicio"
                                value="{{ request('data_inicio') }}"
                                class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="text-[11px] font-semibold text-slate-700">Data fim</label>
                            <input
                                type="date"
                                name="data_fim"
                                value="{{ request('data_fim') }}"
                                class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2 md:justify-end">
                        <button
                            type="submit"
                            class="inline-flex w-full sm:w-auto items-center justify-center px-4 py-2 rounded-xl bg-blue-700 text-white text-sm font-semibold hover:bg-blue-800 transition">
                            Aplicar filtros
                        </button>
                        <a
                            href="{{ route('cliente.arquivos.index') }}"
                            class="inline-flex w-full sm:w-auto items-center justify-center px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">
                            Limpar
                        </a>
                    </div>
                </form>

                @if(($funcionariosComArquivos ?? collect())->isNotEmpty())
                    <form method="GET" action="{{ route('cliente.arquivos.index') }}" class="rounded-xl border border-blue-200 bg-white p-3 md:p-4">
                        <p class="text-xs font-semibold text-blue-900">Card de impressão e download por funcionário</p>
                        <p class="mt-1 text-[11px] text-slate-500">Selecione um funcionário específico para buscar os arquivos dele.</p>

                        <input type="hidden" name="servico" value="{{ request('servico') }}">
                        <input type="hidden" name="data_inicio" value="{{ request('data_inicio') }}">
                        <input type="hidden" name="data_fim" value="{{ request('data_fim') }}">

                        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div class="flex-1">
                                <label class="text-[11px] font-semibold text-slate-700">Funcionário</label>
                                <select
                                    name="funcionario_id"
                                    class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Selecione um funcionário</option>
                                    @foreach($funcionariosComArquivos as $func)
                                        <option value="{{ $func->id }}" @selected((string) request('funcionario_id') === (string) $func->id)>{{ $func->nome }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button
                                type="submit"
                                class="inline-flex w-full sm:w-auto items-center justify-center px-4 py-2 rounded-xl bg-blue-700 text-white text-sm font-semibold hover:bg-blue-800 transition">
                                Buscar
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-blue-200 bg-white overflow-hidden shadow-sm">
            <header class="px-4 py-3 md:px-5 md:py-4 border-b border-blue-100 bg-blue-50/60">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-blue-900">2. Arquivos disponíveis</p>
                    <span class="text-xs text-slate-500">Selecione os cards para montar seu pacote de download</span>
                </div>
            </header>

            <form method="POST" action="{{ route('cliente.arquivos.download-selecionados') }}" class="flex flex-col">
                @csrf

                @if($arquivos->count())
                    <div class="border-b border-slate-200 bg-white px-3 py-2 sticky top-0 z-10">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 js-check-all-arquivos">
                                Selecionar todos os cards
                            </label>
                        </div>
                    </div>

                    <div class="p-3 md:p-4 max-h-[52vh] md:max-h-[58vh] overflow-y-auto" style="scrollbar-gutter: stable;">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($arquivos as $tarefa)
                                @php
                                    $servicoLabel = $tarefa->servico->nome ?? 'Serviço';
                                    $isTreinamentoNr = $servicoLabel === 'Treinamentos NRs';
                                    $certificadosTreinamento = ($tarefa->anexos ?? collect())->filter(function ($anexo) {
                                        return mb_strtolower((string) ($anexo->servico ?? '')) === 'certificado_treinamento';
                                    });
                                    $totalCertificados = $certificadosTreinamento->count();
                                    $treinamentoPayload = (array) ($tarefa->treinamentoNrDetalhes->treinamentos ?? []);
                                    $treinamentoModo = (string) ($treinamentoPayload['modo'] ?? '');
                                    $treinamentoCodigos = [];
                                    if ($treinamentoModo === 'pacote') {
                                        $treinamentoCodigos = array_values((array) data_get($treinamentoPayload, 'pacote.codigos', []));
                                    } else {
                                        $treinamentoCodigos = array_values((array) ($treinamentoPayload['codigos'] ?? $treinamentoPayload));
                                    }
                                    $treinamentoCodigos = array_values(array_filter(array_map('strval', $treinamentoCodigos)));
                                    $treinamentoPacoteNome = (string) data_get($treinamentoPayload, 'pacote.nome', '');
                                    $treinamentoParticipantes = $tarefa->treinamentoNr
                                        ->pluck('funcionario.nome')
                                        ->filter()
                                        ->values()
                                        ->all();
                                    $tituloCard = $tarefa->titulo ?? 'Tarefa';
                                    if ($isTreinamentoNr && !empty($treinamentoParticipantes)) {
                                        $primeiroParticipante = (string) ($treinamentoParticipantes[0] ?? '');
                                        $participantesExtras = max(count($treinamentoParticipantes) - 1, 0);
                                        if ($primeiroParticipante !== '') {
                                            $tituloCard = 'Treinamento NR - ' . $primeiroParticipante;
                                            if ($participantesExtras > 0) {
                                                $tituloCard .= ' +' . $participantesExtras;
                                            }
                                        }
                                    }
                                    $mostrarParticipantesTreinamento = !empty($treinamentoParticipantes);
                                    if ($mostrarParticipantesTreinamento && count($treinamentoParticipantes) === 1) {
                                        $participanteUnico = (string) ($treinamentoParticipantes[0] ?? '');
                                        $tituloAtual = mb_strtolower((string) ($tarefa->titulo ?? ''));
                                        $mostrarParticipantesTreinamento = $participanteUnico === ''
                                            || !str_contains($tituloAtual, mb_strtolower($participanteUnico));
                                    }
                                @endphp

                                <article class="h-full rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm flex flex-col">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                                            <input
                                                type="checkbox"
                                                name="tarefa_ids[]"
                                                value="{{ $tarefa->id }}"
                                                class="rounded border-slate-300 js-check-item-arquivo">
                                            Selecionar
                                        </label>
                                    </div>

                                    <p class="text-sm font-semibold text-slate-900 leading-snug">{{ $tituloCard }}</p>
                                    @unless($isTreinamentoNr)
                                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $servicoLabel }}</p>
                                    @endunless

                                    <div class="mt-2 space-y-1 text-[11px] text-slate-600">
                                        <p>
                                            <span class="font-medium text-slate-700">Finalizado em:</span>
                                            {{ optional($tarefa->finalizado_em)->format('d/m/Y H:i') ?? '-' }}
                                        </p>
                                        @if($isTreinamentoNr)
                                            <p>
                                                <span class="font-medium text-slate-700">Treinamento:</span>
                                                {{ $treinamentoModo === 'pacote' ? 'Pacote' : 'Avulso' }}
                                            </p>
                                            @if(!empty($treinamentoPacoteNome))
                                                <p>
                                                    <span class="font-medium text-slate-700">Pacote:</span>
                                                    {{ $treinamentoPacoteNome }}
                                                </p>
                                            @endif
                                            @if(!empty($treinamentoCodigos))
                                                <p>
                                                    <span class="font-medium text-slate-700">NRs:</span>
                                                    {{ implode(', ', $treinamentoCodigos) }}
                                                </p>
                                            @endif
                                            @if($mostrarParticipantesTreinamento)
                                                <p>
                                                    <span class="font-medium text-slate-700">Participantes:</span>
                                                    {{ implode(', ', $treinamentoParticipantes) }}
                                                </p>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="mt-auto pt-3 rounded-xl border border-blue-100 bg-blue-50/50 p-2.5 space-y-2">
                                        <p class="text-[11px] font-semibold text-blue-800">Documento e impressão</p>

                                        <div class="flex flex-wrap gap-2">
                                            @if($tarefa->documento_link && !($servicoLabel === 'ASO' && $totalCertificados > 0))
                                                <a href="{{ $tarefa->documento_link }}"
                                                   class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-700 text-white text-xs font-semibold hover:bg-blue-800 transition"
                                                   target="_blank" rel="noopener">
                                                    Visualizar / Imprimir
                                                </a>
                                            @endif

                                            @if($totalCertificados > 0 && $servicoLabel === 'ASO')
                                                <button
                                                    type="submit"
                                                    name="tarefa_ids[]"
                                                    value="{{ $tarefa->id }}"
                                                    formaction="{{ route('cliente.arquivos.download-selecionados') }}?include_anexos=1"
                                                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-blue-200 text-blue-700 text-xs font-semibold hover:bg-blue-100">
                                                    Baixar ASO + Certificados
                                                </button>
                                            @else
                                                @foreach($certificadosTreinamento as $certificado)
                                                    <a href="{{ $certificado->url }}"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-blue-200 text-blue-700 text-xs font-semibold hover:bg-blue-100"
                                                       target="_blank" rel="noopener">
                                                        Certificado
                                                    </a>
                                                @endforeach
                                            @endif
                                        </div>

                                        @if(!$tarefa->documento_link && $certificadosTreinamento->isEmpty())
                                            <span class="text-xs text-slate-500">Sem documento disponível</span>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>

                    <footer class="border-t border-blue-100 bg-blue-50/60 px-3 py-3 md:px-4 md:py-4">
                        <div class="rounded-xl border border-blue-200 bg-white px-3 py-3 md:px-4">
                            <p class="text-[11px] uppercase tracking-wide font-semibold text-blue-800">Resumo da seleção</p>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                                <p class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm text-blue-800">
                                    <span id="js-selected-count" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-700 px-1.5 text-[11px] font-semibold text-white">0</span>
                                    itens selecionados
                                </p>

                                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                                    <button
                                        type="submit"
                                        formaction="{{ route('cliente.arquivos.download-por-funcionario') }}"
                                        name="funcionario_id"
                                        value="0"
                                        class="inline-flex w-full sm:w-auto items-center justify-center px-4 py-2 rounded-xl border border-blue-300 bg-white text-blue-700 text-sm font-semibold hover:bg-blue-50 transition">
                                        Baixar
                                    </button>
                                    <button
                                        type="submit"
                                        id="js-btn-download-selecionados"
                                        class="inline-flex w-full sm:w-auto items-center justify-center px-4 py-2 rounded-xl bg-blue-700 text-white text-sm font-semibold hover:bg-blue-800 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        Baixar selecionados
                                    </button>
                                </div>
                            </div>
                        </div>
                    </footer>
                @else
                    <div class="p-6">
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 px-4 py-8 text-center">
                            <p class="text-sm text-slate-500">Nenhum documento disponível até o momento.</p>
                        </div>
                    </div>
                @endif
            </form>
        </section>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.querySelector('.js-check-all-arquivos');
    const items = Array.from(document.querySelectorAll('.js-check-item-arquivo'));
    const countEl = document.getElementById('js-selected-count');
    const btnDownloadSelecionados = document.getElementById('js-btn-download-selecionados');

    function updateState() {
        const selected = items.filter((item) => item.checked).length;

        if (countEl) {
            countEl.textContent = String(selected);
        }

        if (btnDownloadSelecionados) {
            btnDownloadSelecionados.disabled = selected === 0;
        }

        if (checkAll) {
            const allChecked = items.length > 0 && selected === items.length;
            checkAll.checked = allChecked;
            checkAll.indeterminate = selected > 0 && selected < items.length;
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            items.forEach((item) => {
                item.checked = checkAll.checked;
            });
            updateState();
        });
    }

    items.forEach((item) => {
        item.addEventListener('change', updateState);
    });

    updateState();
});
</script>
@endpush
