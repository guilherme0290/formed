@extends('layouts.cliente')

@section('title', 'Detalhes do Funcionário')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canUpdate = $isMaster || isset($permissionMap['cliente.funcionarios.update']);
        $canToggle = $isMaster || isset($permissionMap['cliente.funcionarios.toggle']);
        $canDelete = $canUpdate;
        $totalArquivosAso = ($arquivosAso ?? collect())->count();
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-4">
        <section class="overflow-hidden rounded-2xl border border-blue-300 shadow-sm">
            <div class="bg-gradient-to-r from-[#123fbe] to-[#1a5de8] px-4 py-4 md:px-6 md:py-5 text-white">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-lg md:text-xl font-semibold">{{ $funcionario->nome }}</h1>
                        <p class="text-xs md:text-sm text-blue-100 mt-1">
                            {{ $funcionario->funcao->nome ?? 'Função não informada' }}
                        </p>
                    </div>

                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $funcionario->ativo ? 'bg-emerald-200 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                        {{ $funcionario->ativo ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>
            </div>

            <div class="bg-blue-50/70 border-t border-blue-200 px-4 py-3 md:px-6 md:py-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('cliente.dashboard') }}"
                           class="inline-flex w-full sm:w-auto items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold border border-blue-200 bg-white text-blue-700 hover:bg-blue-50">
                            &larr; Início
                        </a>
                        <a href="{{ route('cliente.funcionarios.index') }}"
                           class="inline-flex w-full sm:w-auto items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold border border-blue-200 bg-white text-blue-700 hover:bg-blue-50">
                            &larr; Lista
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ $canUpdate ? route('cliente.funcionarios.edit', $funcionario) : 'javascript:void(0)' }}"
                           @if(!$canUpdate) title="Usuário sem permissão." aria-disabled="true" @endif
                           class="inline-flex w-full sm:w-auto items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold {{ $canUpdate ? 'bg-blue-700 text-white hover:bg-blue-800' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                            Editar informações
                        </a>
                        <form method="POST" action="{{ route('cliente.funcionarios.destroy', $funcionario) }}"
                              onsubmit="return confirm('Deseja remover este funcionário?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    @if(!$canDelete) disabled title="Usuário sem permissão." @endif
                                    class="inline-flex w-full sm:w-auto items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold {{ $canDelete ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                                Deletar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-blue-200 bg-white overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b border-blue-100 bg-blue-50/60">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button"
                            data-tab-trigger="info"
                            class="tab-trigger inline-flex w-full sm:w-auto items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-blue-200 bg-blue-700 text-white">
                        Informações pessoais
                    </button>
                    <button type="button"
                            data-tab-trigger="aso"
                            class="tab-trigger inline-flex w-full sm:w-auto items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-blue-200 bg-white text-blue-700 hover:bg-blue-50">
                        Arquivos ASO e NRs
                    </button>
                </div>
            </div>

            <div class="p-4 md:p-5">
                <div data-tab-pane="info" class="tab-pane">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <section class="lg:col-span-8 rounded-xl border border-blue-200 bg-blue-50/40 p-4">
                            <h2 class="text-sm font-semibold text-blue-900 mb-3">Informações pessoais</h2>

                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-slate-700">
                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">CPF</dt>
                                    <dd class="mt-0.5">{{ $funcionario->cpf ?: '-' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Celular</dt>
                                    <dd class="mt-0.5">{{ $funcionario->celular ?: '-' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Setor</dt>
                                    <dd class="mt-0.5">{{ $funcionario->setor ?: '-' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Vaga atual</dt>
                                    <dd class="mt-0.5">{{ $funcionario->vaga_atual ?: '-' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Nascimento</dt>
                                    <dd class="mt-0.5">
                                        {{ $funcionario->data_nascimento ? \Carbon\Carbon::parse($funcionario->data_nascimento)->format('d/m/Y') : '-' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Admissão</dt>
                                    <dd class="mt-0.5">
                                        {{ $funcionario->data_admissao ? \Carbon\Carbon::parse($funcionario->data_admissao)->format('d/m/Y') : '-' }}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section class="lg:col-span-4 self-start rounded-xl border border-blue-200 bg-white p-4">
                            <h2 class="text-sm font-semibold text-blue-900 mb-2">Status do funcionário</h2>
                            <p class="text-xs text-slate-500 mb-4">Gerencie se o funcionário está ativo para novas solicitações.</p>

                            <form method="POST" action="{{ route('cliente.funcionarios.toggle-status', $funcionario) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        @if(!$canToggle) disabled title="Usuário sem permissão." @endif
                                        class="w-full inline-flex justify-center items-center px-4 py-2 rounded-lg text-xs font-semibold {{ $canToggle ? ($funcionario->ativo ? 'border border-red-300 text-red-700 bg-red-50 hover:bg-red-100' : 'border border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100') : 'bg-slate-200 text-slate-500 cursor-not-allowed border border-slate-300' }}">
                                    {{ $funcionario->ativo ? 'Inativar funcionário' : 'Reativar funcionário' }}
                                </button>
                            </form>
                        </section>
                    </div>
                </div>

                <div data-tab-pane="aso" class="tab-pane hidden">
                    <div class="rounded-2xl border border-blue-200 bg-blue-50/40 overflow-hidden">
                        <div class="px-4 py-3 border-b border-blue-200 bg-blue-100/60 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-semibold text-blue-900">Arquivos ASO e NRs</h2>
                                <p class="text-[11px] text-blue-800 mt-0.5">{{ $totalArquivosAso }} arquivo(s) vinculado(s) a este funcionário</p>
                            </div>
                            <a href="{{ route('cliente.arquivos.funcionario.download', $funcionario) }}"
                               class="inline-flex w-full sm:w-auto items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-blue-200 text-blue-700 bg-white hover:bg-blue-50">
                                Baixar todos
                            </a>
                        </div>

                        @if(($arquivosAso ?? collect())->isEmpty())
                            <div class="px-4 py-8 text-sm text-slate-500 bg-white">
                                Nenhum arquivo de ASO/NR disponível para este funcionário.
                            </div>
                        @else
                            <form method="POST" action="{{ route('cliente.arquivos.funcionario.download-selecionados', $funcionario) }}">
                                @csrf

                                <div class="border-b border-slate-200 bg-white px-3 py-2">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                                            <input type="checkbox" class="rounded border-slate-300 js-check-all-arquivos-funcionario">
                                            Selecionar todos os arquivos
                                        </label>
                                        <p class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm text-blue-800">
                                            <span id="js-aso-selected-count" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-700 px-1.5 text-[11px] font-semibold text-white">0</span>
                                            selecionados
                                        </p>
                                    </div>
                                </div>

                                <div class="max-h-[52vh] overflow-auto bg-white">
                                    <table class="min-w-[760px] text-sm">
                                        <thead class="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold w-10"></th>
                                            <th class="px-3 py-2 text-left font-semibold">Serviço</th>
                                            <th class="px-3 py-2 text-left font-semibold">Tipo</th>
                                            <th class="px-3 py-2 text-left font-semibold">Finalizado em</th>
                                            <th class="px-3 py-2 text-right font-semibold">Arquivo</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                        @foreach($arquivosAso as $arquivo)
                                            @php
                                                $servicoNome = (string) ($arquivo->servico->nome ?? 'ASO');
                                                $servicoNomeLower = mb_strtolower(trim($servicoNome));
                                                $isTreinamentoNr = str_contains($servicoNomeLower, 'treinamento');
                                                $tipoAso = (string) ($arquivo->asoSolicitacao->tipo_aso ?? '');
                                                $tipoLabel = $isTreinamentoNr
                                                    ? 'Treinamento NR'
                                                    : match ($tipoAso) {
                                                        'admissional' => 'Admissional',
                                                        'periodico' => 'Periódico',
                                                        'demissional' => 'Demissional',
                                                        'mudanca_funcao' => 'Mudança de Função',
                                                        'retorno_trabalho' => 'Retorno ao Trabalho',
                                                        default => 'ASO',
                                                    };
                                                $certificadosTreinamento = ($arquivo->anexos ?? collect())->filter(function ($anexo) {
                                                    return mb_strtolower((string) ($anexo->servico ?? '')) === 'certificado_treinamento';
                                                });
                                            @endphp
                                            <tr class="hover:bg-slate-50/80">
                                                <td class="px-3 py-2">
                                                    <input type="checkbox" name="tarefa_ids[]" value="{{ $arquivo->id }}" class="rounded border-slate-300 js-check-item-arquivo-funcionario">
                                                </td>
                                                <td class="px-3 py-2 text-slate-800">{{ $servicoNome }}</td>
                                                <td class="px-3 py-2 text-slate-700">{{ $tipoLabel }}</td>
                                                <td class="px-3 py-2 text-slate-700">{{ optional($arquivo->finalizado_em)->format('d/m/Y H:i') ?? '-' }}</td>
                                                <td class="px-3 py-2 text-right">
                                                    @if($arquivo->documento_link)
                                                        <a href="{{ $arquivo->documento_link }}"
                                                           target="_blank" rel="noopener"
                                                           class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-700 text-white hover:bg-blue-800">
                                                            Visualizar / Imprimir
                                                        </a>
                                                    @elseif($certificadosTreinamento->isNotEmpty())
                                                        @foreach($certificadosTreinamento as $certificado)
                                                            <a href="{{ $certificado->url }}"
                                                               target="_blank" rel="noopener"
                                                               class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-blue-200 text-blue-700 bg-white hover:bg-blue-50 ml-1">
                                                                Certificado
                                                            </a>
                                                        @endforeach
                                                    @else
                                                        <span class="text-xs text-slate-400">Indisponível</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/80 flex flex-wrap justify-end">
                                    <button type="submit"
                                            id="js-aso-download-selected"
                                            class="inline-flex w-full sm:w-auto items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-700 text-white hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Baixar selecionados
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const triggers = Array.from(document.querySelectorAll('[data-tab-trigger]'));
    const panes = Array.from(document.querySelectorAll('[data-tab-pane]'));

    function setActiveTab(tab) {
        triggers.forEach((btn) => {
            const active = btn.getAttribute('data-tab-trigger') === tab;
            btn.classList.toggle('bg-blue-700', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('bg-white', !active);
            btn.classList.toggle('text-blue-700', !active);
        });

        panes.forEach((pane) => {
            pane.classList.toggle('hidden', pane.getAttribute('data-tab-pane') !== tab);
        });
    }

    triggers.forEach((btn) => {
        btn.addEventListener('click', function () {
            setActiveTab(btn.getAttribute('data-tab-trigger'));
        });
    });

    setActiveTab('info');

    const checkAll = document.querySelector('.js-check-all-arquivos-funcionario');
    const items = Array.from(document.querySelectorAll('.js-check-item-arquivo-funcionario'));
    const countEl = document.getElementById('js-aso-selected-count');
    const btnDownload = document.getElementById('js-aso-download-selected');

    function updateAsoSelection() {
        const selected = items.filter((item) => item.checked).length;

        if (countEl) {
            countEl.textContent = String(selected);
        }

        if (btnDownload) {
            btnDownload.disabled = selected === 0;
        }

        if (checkAll) {
            checkAll.checked = items.length > 0 && selected === items.length;
            checkAll.indeterminate = selected > 0 && selected < items.length;
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            items.forEach((item) => {
                item.checked = checkAll.checked;
            });
            updateAsoSelection();
        });
    }

    items.forEach((item) => {
        item.addEventListener('change', updateAsoSelection);
    });

    updateAsoSelection();
});
</script>
@endpush

