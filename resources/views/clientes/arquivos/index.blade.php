@extends('layouts.cliente')
@section('title', 'Meus Arquivos')

@section('content')
    @php
        $totalArquivos = $arquivos->count();
        $totalFuncionarios = ($funcionariosComArquivos ?? collect())->count();
        $totalComDocumentoPrincipal = $arquivos->filter(fn ($tarefa) => !empty($tarefa->documento_link))->count();
        $totalComCertificados = $arquivos->filter(function ($tarefa) {
            return ($tarefa->anexos ?? collect())
                ->contains(fn ($anexo) => mb_strtolower((string) ($anexo->servico ?? '')) === 'certificado_treinamento');
        })->count();
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg md:text-xl font-semibold text-slate-900">Meus Arquivos</h1>
                <p class="text-xs md:text-sm text-slate-500">
                    Arquivos finalizados e certificados disponiveis para download
                </p>
            </div>

            <a href="{{ route('cliente.dashboard') }}"
               class="hidden sm:inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                &larr; Voltar aos servicos
            </a>
        </div>

        <div class="rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-inner overflow-hidden">
            <div class="px-4 py-3 border-b border-indigo-200 bg-indigo-100/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Resumo de Arquivos</p>
            </div>

            <div class="p-4 md:p-5">
                <div class="rounded-xl border border-indigo-200/80 bg-white/95 p-3 md:p-4 shadow-sm">
                    <div class="grid gap-3 md:grid-cols-4">
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-wide text-indigo-700">Total de Registros</p>
                            <p class="mt-1 text-2xl font-semibold text-indigo-700">{{ $totalArquivos }}</p>
                        </div>

                        <div class="rounded-xl border border-cyan-100 bg-cyan-50/80 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-wide text-cyan-700">Funcionarios com Arquivos</p>
                            <p class="mt-1 text-2xl font-semibold text-cyan-700">{{ $totalFuncionarios }}</p>
                        </div>

                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/80 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-wide text-emerald-700">Doc. Principal</p>
                            <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ $totalComDocumentoPrincipal }}</p>
                        </div>

                        <div class="rounded-xl border border-amber-100 bg-amber-50/80 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-wide text-amber-700">Com Certificados</p>
                            <p class="mt-1 text-2xl font-semibold text-amber-600">{{ $totalComCertificados }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-inner overflow-hidden">
            <div class="px-4 py-3 border-b border-indigo-200 bg-indigo-100/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Arquivos</p>
            </div>

            <div class="p-4 md:p-5">
                <div class="rounded-xl border border-indigo-200/80 bg-white/95 p-3 md:p-4 shadow-sm space-y-4 max-h-[72vh] flex flex-col overflow-hidden">
                    <form method="GET" action="{{ route('cliente.arquivos.index') }}" class="flex flex-col gap-3">
                        <div class="grid gap-3 md:grid-cols-4">
                            <div>
                                <label class="text-[11px] font-semibold text-slate-600">Tipo de serviço</label>
                                <select
                                    name="servico"
                                    class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                    <option value="">Todos os serviços</option>
                                    @foreach($servicos as $servico)
                                        <option value="{{ $servico->id }}" @selected((string) $servico->id === request('servico'))>
                                            {{ $servico->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-[11px] font-semibold text-slate-600">Data inicio</label>
                                <input
                                    type="date"
                                    name="data_inicio"
                                    value="{{ request('data_inicio') }}"
                                    class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            </div>

                            <div>
                                <label class="text-[11px] font-semibold text-slate-600">Data fim</label>
                                <input
                                    type="date"
                                    name="data_fim"
                                    value="{{ request('data_fim') }}"
                                    class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            </div>

                            <div class="flex items-end gap-2 md:justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                                    Filtrar
                                </button>
                                <a
                                    href="{{ route('cliente.arquivos.index') }}"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">
                                    Limpar
                                </a>
                            </div>
                        </div>
                    </form>

                    @if(($funcionariosComArquivos ?? collect())->isNotEmpty())
                        <form method="POST" action="{{ route('cliente.arquivos.download-por-funcionario') }}" class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 md:p-4 grid gap-3 md:grid-cols-4 items-end">
                            @csrf
                            <div class="md:col-span-3">
                                <label class="text-xs font-semibold text-slate-700">Baixar todos por funcionário</label>
                                <select
                                    name="funcionario_id"
                                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                    <option value="0">Todos os funcionários</option>
                                    @foreach($funcionariosComArquivos as $func)
                                        <option value="{{ $func->id }}">{{ $func->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:justify-self-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                                    Baixar ZIP
                                </button>
                            </div>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('cliente.arquivos.download-selecionados') }}" class="flex-1 min-h-0 flex flex-col">
                        @csrf

                        @if($arquivos->count())
                            <div class="pr-1 rounded-xl border border-slate-200 bg-white overflow-y-auto"
                                 style="height: 52vh; overflow-y: auto; scrollbar-gutter: stable;">
                                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-3 py-2">
                                    <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                                        <input type="checkbox" class="rounded border-slate-300 js-check-all-arquivos">
                                        Selecionar todos os cards
                                    </label>
                                    <span class="text-xs text-slate-500">Marque os arquivos para baixar em um unico ZIP</span>
                                </div>

                                <div class="p-3">
                                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    @foreach($arquivos as $tarefa)
                                        @php
                                            $coluna = $tarefa->coluna;
                                            $servicoLabel = $tarefa->servico->nome ?? 'Servico';
                                            $isTreinamentoNr = $servicoLabel === 'Treinamentos NRs';
                                            $statusLabel = $coluna?->nome ?? 'Finalizado';
                                            $badge = ($coluna && $coluna->finaliza)
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                                                : 'bg-slate-100 text-slate-700 border-slate-200';
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

                                        <article class="h-full bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm flex flex-col">
                                            <div class="flex items-start justify-between gap-2 mb-2">
                                                <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                                                    <input
                                                        type="checkbox"
                                                        name="tarefa_ids[]"
                                                        value="{{ $tarefa->id }}"
                                                        class="rounded border-slate-300 js-check-item-arquivo">
                                                    Selecionar
                                                </label>

                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-semibold {{ $badge }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </div>

                                            <p class="text-sm font-semibold text-slate-900 leading-snug">
                                                {{ $tituloCard }}
                                            </p>
                                            @unless($isTreinamentoNr)
                                                <p class="text-[11px] text-slate-500 mt-0.5">
                                                    {{ $servicoLabel }}
                                                </p>
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

                                            <div class="mt-auto pt-3 flex flex-wrap gap-2">
                                                @if($tarefa->documento_link && !($servicoLabel === 'ASO' && $totalCertificados > 0))
                                                    <a href="{{ $tarefa->documento_link }}"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition"
                                                       target="_blank" rel="noopener">
                                                        {{ $servicoLabel }}
                                                    </a>
                                                @endif

                                                @if($totalCertificados > 0 && $servicoLabel === 'ASO')
                                                    <button
                                                        type="submit"
                                                        name="tarefa_ids[]"
                                                        value="{{ $tarefa->id }}"
                                                        formaction="{{ route('cliente.arquivos.download-selecionados') }}?include_anexos=1"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-lg border border-indigo-200 text-indigo-700 text-xs font-semibold hover:bg-indigo-50">
                                                        ASO+CERTIFICADOS
                                                    </button>
                                                @else
                                                    @foreach($certificadosTreinamento as $certificado)
                                                        <a href="{{ $certificado->url }}"
                                                           class="inline-flex items-center px-3 py-1.5 rounded-lg border border-indigo-200 text-indigo-700 text-xs font-semibold hover:bg-indigo-50"
                                                           target="_blank" rel="noopener">
                                                            {{ $servicoLabel }} + Certificado
                                                        </a>
                                                    @endforeach
                                                @endif

                                                @if(!$tarefa->documento_link && $certificadosTreinamento->isEmpty())
                                                    <span class="text-xs text-slate-400">Sem documento disponivel</span>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-end gap-2">
                                <button
                                    type="submit"
                                    formaction="{{ route('cliente.arquivos.download-por-funcionario') }}"
                                    name="funcionario_id"
                                    value="0"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-indigo-300 bg-white text-indigo-700 text-sm font-semibold hover:bg-indigo-50 transition">
                                    Baixar tudo em ZIP
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                                    Baixar selecionados
                                </button>
                            </div>
                        @else
                            <div class="mt-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-6 text-center">
                                <p class="text-xs md:text-sm text-slate-500">
                                    Nenhum documento disponivel ate o momento.
                                </p>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.querySelector('.js-check-all-arquivos');
    if (!checkAll) return;

    const items = Array.from(document.querySelectorAll('.js-check-item-arquivo'));
    checkAll.addEventListener('change', function () {
        items.forEach((item) => {
            item.checked = checkAll.checked;
        });
    });
});
</script>
@endpush
