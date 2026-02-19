<section class="w-full px-3 md:px-5 py-4 md:py-5">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl md:text-2xl font-semibold text-slate-900">
                    Meus agendamentos
                </h2>
                <p class="text-xs md:text-sm text-slate-500">
                    Aqui você acompanha todos os serviços solicitados.
                </p>
            </div>

            <a href="{{ route('cliente.dashboard') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-900 text-white text-xs md:text-sm font-semibold shadow">
                Voltar ao painel
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <header class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
                <span class="text-sm font-semibold">Tarefas solicitadas</span>
                <span class="text-[12px] text-slate-200">Cliente: {{ $cliente->razao_social ?? $cliente->nome_fantasia }}</span>
            </header>

            @if($agendamentos->isEmpty())
                <div class="p-6 text-sm text-slate-500">
                    Nenhum agendamento encontrado.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Solicitado em</th>
                                <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                <th class="px-4 py-3 text-left font-semibold">Início previsto</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($agendamentos as $tarefa)
                                @php
                                    $slug = mb_strtolower((string) optional($tarefa->coluna)->slug);
                                    $isCancelado = !empty($tarefa->deleted_at);
                                    $isPendente = !$isCancelado && in_array($slug, ['pendente', 'pendentes'], true);
                                    $isFinalizada = !$isCancelado && (
                                        (bool) optional($tarefa->coluna)->finaliza
                                        || !empty($tarefa->finalizado_em)
                                        || in_array($slug, ['finalizada', 'finalizadas'], true)
                                    );

                                    $podeExcluir = $isPendente;
                                    $statusNome = $isCancelado
                                        ? 'Cancelado'
                                        : ($isPendente
                                            ? 'Pendente'
                                            : ($isFinalizada ? 'Finalizada' : 'Em execução'));

                                    $aso = $tarefa->asoSolicitacao;
                                    $pgr = $tarefa->pgrSolicitacao;
                                    $pcmso = $tarefa->pcmsoSolicitacao;
                                    $apr = $tarefa->aprSolicitacao;
                                    $servicoNome = $tarefa->servico->nome ?? 'Serviço';
                                    $servicoDisplay = $tarefa->servico_detalhe ?? $servicoNome;
                                    $temDetalheAso = !empty($tarefa->aso_colaborador) || !empty($tarefa->aso_tipo) || !empty($tarefa->aso_data) || !empty($tarefa->aso_unidade) || !empty($tarefa->aso_email);
                                    $temDetalhePgr = !empty($tarefa->pgr_tipo) || !empty($tarefa->pgr_obra) || !empty($tarefa->pgr_contratante) || !empty($tarefa->pgr_total);
                                    $temDetalheApr = $apr && (
                                        !empty($apr->obra_nome)
                                        || !empty($apr->obra_endereco)
                                        || !empty($apr->status)
                                        || !empty($apr->endereco_atividade)
                                        || !empty($apr->funcoes_envolvidas)
                                        || !empty($apr->etapas_atividade)
                                    );
                                    $temDetalhe = $temDetalheAso || $temDetalhePgr || $temDetalheApr;
                                    $editUrl = null;
                                    $servicoNomeLower = mb_strtolower((string) $servicoNome);
                                    if (str_contains($servicoNomeLower, 'aso')) {
                                        $editUrl = route('operacional.kanban.aso.editar', ['tarefa' => $tarefa->id, 'origem' => 'cliente']);
                                    } elseif (str_contains($servicoNomeLower, 'pgr')) {
                                        $editUrl = route('operacional.kanban.pgr.editar', ['tarefa' => $tarefa->id, 'origem' => 'cliente']);
                                    } elseif (str_contains($servicoNomeLower, 'pcmso')) {
                                        $editUrl = route('operacional.kanban.pcmso.edit', ['tarefa' => $tarefa->id, 'origem' => 'cliente']);
                                    } elseif (str_contains($servicoNomeLower, 'ltcat')) {
                                        $editUrl = route('operacional.ltcat.edit', ['tarefa' => $tarefa->id, 'origem' => 'cliente']);
                                    } elseif (str_contains($servicoNomeLower, 'ltip')) {
                                        $editUrl = route('operacional.ltip.edit', ['tarefa' => $tarefa->id, 'origem' => 'cliente']);
                                    } elseif (str_contains($servicoNomeLower, 'apr')) {
                                        $editUrl = route('operacional.apr.edit', ['tarefa' => $tarefa->id, 'origem' => 'cliente']);
                                    } elseif (str_contains($servicoNomeLower, 'pae')) {
                                        $editUrl = route('operacional.pae.edit', ['tarefa' => $tarefa->id, 'origem' => 'cliente']);
                                    } elseif (str_contains($servicoNomeLower, 'treinamento')) {
                                        $editUrl = route('operacional.treinamentos-nr.edit', ['tarefa' => $tarefa->id, 'origem' => 'cliente']);
                                    }
                                    $badge = $isCancelado
                                        ? 'bg-slate-100 text-slate-700 border-slate-200'
                                        : ($isPendente
                                            ? 'bg-amber-50 text-amber-700 border-amber-100'
                                            : ($isFinalizada
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                                                : 'bg-sky-50 text-sky-700 border-sky-100'));
                                    $certificadosTreinamento = ($tarefa->anexos ?? collect())->filter(function ($anexo) {
                                        return mb_strtolower((string) ($anexo->servico ?? '')) === 'certificado_treinamento';
                                    });
                                    $documentoAsoUrl = $tarefa->documento_link;
                                @endphp
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ optional($tarefa->created_at)->format('d/m/Y H:i') ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        <div class="font-medium">
                                            {{ $servicoDisplay }}
                                        </div>
                                        @if($temDetalhe)
                                            <details class="mt-1">
                                                <summary class="text-xs text-slate-500 cursor-pointer select-none">Detalhar</summary>
                                                <div class="mt-2 text-xs text-slate-600 space-y-1">
                                                    @if(!empty($tarefa->aso_colaborador))
                                                        <div><span class="font-semibold">Colaborador:</span> {{ $tarefa->aso_colaborador }}</div>
                                                    @endif
                                                    @if(!empty($tarefa->aso_tipo))
                                                        <div><span class="font-semibold">Tipo:</span> {{ $tarefa->aso_tipo }}</div>
                                                    @endif
                                                    @if(!empty($tarefa->aso_data))
                                                        <div><span class="font-semibold">Data:</span> {{ \Carbon\Carbon::parse($tarefa->aso_data)->format('d/m/Y') }}</div>
                                                    @endif
                                                    @if(!empty($tarefa->aso_unidade))
                                                        <div><span class="font-semibold">Unidade:</span> {{ $tarefa->aso_unidade }}</div>
                                                    @endif
                                                    @if(!empty($tarefa->aso_email))
                                                        <div><span class="font-semibold">E-mail:</span> {{ $tarefa->aso_email }}</div>
                                                    @endif
                                                    @if($temDetalhePgr)
                                                        @php
                                                            $rotuloPrincipalPgr = str_contains(mb_strtolower((string) ($servicoDisplay ?? $servicoNome)), 'pcmso') ? 'PCMSO' : 'PGR';
                                                            $rotuloComplementarPgr = $rotuloPrincipalPgr === 'PCMSO' ? 'PGR' : 'PCMSO';
                                                        @endphp
                                                        <div class="pt-1"></div>
                                                        @if(!empty($tarefa->pgr_tipo))
                                                            <div><span class="font-semibold">{{ $rotuloPrincipalPgr }}:</span> {{ $tarefa->pgr_tipo }}</div>
                                                        @endif
                                                        @if(!empty($tarefa->pgr_obra))
                                                            <div><span class="font-semibold">Obra:</span> {{ $tarefa->pgr_obra }}</div>
                                                        @endif
                                                        @if(!empty($tarefa->pgr_total))
                                                            <div><span class="font-semibold">Trabalhadores:</span> {{ $tarefa->pgr_total }}</div>
                                                        @endif
                                                        <div><span class="font-semibold">{{ $rotuloComplementarPgr }}:</span> {{ !empty($tarefa->pgr_com_pcms0) ? 'Com ' . $rotuloComplementarPgr : 'Sem ' . $rotuloComplementarPgr }}</div>
                                                        <div><span class="font-semibold">ART:</span> {{ !empty($tarefa->pgr_com_art) ? 'Com ART' : 'Sem ART' }}</div>
                                                    @endif
                                                    @if($temDetalheApr)
                                                        <div class="pt-1"></div>
                                                        <div class="font-semibold text-slate-700">APR</div>
                                                        @if(!empty($apr->status))
                                                            <div>Status: {{ strtoupper((string) $apr->status) }}</div>
                                                        @endif
                                                        @if(!empty($apr->obra_nome))
                                                            <div>Obra: {{ $apr->obra_nome }}</div>
                                                        @endif
                                                        @if(!empty($apr->obra_endereco))
                                                            <div>Endereço da obra: {{ $apr->obra_endereco }}</div>
                                                        @endif
                                                        @if(!empty($apr->endereco_atividade))
                                                            <div>Endereço da atividade: {{ $apr->endereco_atividade }}</div>
                                                        @endif
                                                        @if(!empty($apr->funcoes_envolvidas))
                                                            <div>Funções envolvidas: {{ $apr->funcoes_envolvidas }}</div>
                                                        @endif
                                                        @if(!empty($apr->etapas_atividade))
                                                            <div>Etapas da atividade: {{ $apr->etapas_atividade }}</div>
                                                        @endif
                                                    @endif
                                                </div>
                                            </details>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ optional($tarefa->inicio_previsto)->format('d/m/Y H:i') ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[12px] font-semibold {{ $badge }}">
                                            {{ $statusNome }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-2 flex-wrap justify-end">
                                            @if($documentoAsoUrl)
                                                <a href="{{ $documentoAsoUrl }}"
                                                   target="_blank" rel="noopener"
                                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-indigo-300 bg-indigo-50 text-indigo-700 text-xs font-semibold hover:bg-indigo-100">
                                                    ASO
                                                </a>
                                            @endif
                                            @foreach($certificadosTreinamento as $certificado)
                                                <a href="{{ $certificado->url }}"
                                                   target="_blank" rel="noopener"
                                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-indigo-200 bg-white text-indigo-700 text-xs font-semibold hover:bg-indigo-50">
                                                    Certificado {{ $loop->iteration }}
                                                </a>
                                            @endforeach
                                            @if($podeExcluir)
                                                @if($editUrl)
                                                    <a href="{{ $editUrl }}"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">
                                                        Editar
                                                    </a>
                                                @endif
                                                <form method="POST"
                                                      id="delete-agendamento-form-{{ $tarefa->id }}"
                                                      action="{{ route('cliente.agendamentos.destroy', $tarefa) }}"
                                                      class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            data-delete-form-id="delete-agendamento-form-{{ $tarefa->id }}"
                                                            data-delete-servico="{{ $servicoDisplay }}"
                                                            data-delete-status="{{ $statusNome }}"
                                                            class="js-open-delete-modal inline-flex items-center px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700">
                                                        Excluir
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $agendamentos->links() }}
                </div>
            @endif
        </div>
    </section>

    <div id="delete-modal" class="fixed inset-0 z-[95] hidden items-center justify-center bg-black/60 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 bg-red-600 text-white">
                <h3 class="text-sm font-semibold">Excluir Agendamento</h3>
                <button type="button" id="delete-modal-close" class="text-white/90 hover:text-white">✕</button>
            </div>
            <div class="p-5 space-y-4 text-sm text-slate-700">
                <div class="text-sm text-slate-600 font-bold">
                    Confirme a exclusão do agendamento selecionado.
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <div class="text-[11px] text-slate-500 font-bold">Serviço</div>
                    <div id="delete-modal-servico" class="text-sm font-medium text-slate-800">—</div>
                </div>
                <div class="text-sm text-red-600 font-medium">
                    Esta ação não pode ser desfeita.
                </div>
                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button"
                            id="delete-modal-cancel"
                            class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="button"
                            id="delete-modal-confirm"
                            class="inline-flex items-center px-3 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                        Confirmar exclusão
                    </button>
                </div>
            </div>
        </div>
    </div>



@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('delete-modal');
            const btnClose = document.getElementById('delete-modal-close');
            const btnCancel = document.getElementById('delete-modal-cancel');
            const btnConfirm = document.getElementById('delete-modal-confirm');
            const servicoLabel = document.getElementById('delete-modal-servico');
            const openButtons = document.querySelectorAll('.js-open-delete-modal');
            let formIdSelecionado = null;

            function openModal(formId, servico) {
                formIdSelecionado = formId;
                if (servicoLabel) {
                    servicoLabel.textContent = servico || '-';
                }
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            }

            function closeModal() {
                formIdSelecionado = null;
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            }

            openButtons.forEach((btn) => {
                btn.addEventListener('click', function () {
                    const formId = this.getAttribute('data-delete-form-id');
                    const servico = this.getAttribute('data-delete-servico');
                    if (formId) {
                        openModal(formId, servico);
                    }
                });
            });

            btnCancel?.addEventListener('click', closeModal);
            btnClose?.addEventListener('click', closeModal);
            modal?.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            btnConfirm?.addEventListener('click', function () {
                if (!formIdSelecionado) {
                    closeModal();
                    return;
                }

                const form = document.getElementById(formIdSelecionado);
                if (form) {
                    form.submit();
                } else {
                    closeModal();
                }
            });
        });
    </script>
@endpush
