@extends('layouts.operacional')
@section('title', 'Painel Operacional')
@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    @php
        $permissionMap = $usuario?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $usuario?->hasPapel('Master');
        $canCreateTask = $isMaster
            || isset($permissionMap['operacional.tarefas.manage'])
            || isset($permissionMap['operacional.aso.create'])
            || isset($permissionMap['operacional.pgr.create'])
            || isset($permissionMap['operacional.pcmso.create'])
            || isset($permissionMap['operacional.ltcat.create'])
            || isset($permissionMap['operacional.ltip.create'])
            || isset($permissionMap['operacional.apr.create'])
            || isset($permissionMap['operacional.pae.create'])
            || isset($permissionMap['operacional.treinamentos.create']);
        $canUpdateTask = $isMaster
            || isset($permissionMap['operacional.tarefas.manage'])
            || isset($permissionMap['operacional.aso.update'])
            || isset($permissionMap['operacional.pgr.update'])
            || isset($permissionMap['operacional.pcmso.update'])
            || isset($permissionMap['operacional.ltcat.update'])
            || isset($permissionMap['operacional.ltip.update'])
            || isset($permissionMap['operacional.apr.update'])
            || isset($permissionMap['operacional.pae.update'])
            || isset($permissionMap['operacional.treinamentos.update']);
        $canDeleteTask = $isMaster
            || isset($permissionMap['operacional.tarefas.manage'])
            || isset($permissionMap['operacional.aso.delete'])
            || isset($permissionMap['operacional.pgr.delete'])
            || isset($permissionMap['operacional.pcmso.delete'])
            || isset($permissionMap['operacional.ltcat.delete'])
            || isset($permissionMap['operacional.ltip.delete'])
            || isset($permissionMap['operacional.apr.delete'])
            || isset($permissionMap['operacional.pae.delete'])
            || isset($permissionMap['operacional.treinamentos.delete']);
    @endphp

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <p class="font-medium mb-1">Ocorreram alguns erros ao salvar:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="w-full px-3 md:px-5 py-4 md:py-5">

        {{-- Barra de busca + Nova Tarefa --}}
        <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-4 mb-6">
            <div class="flex-1">
                <form method="GET" class="relative">
                    @php
                        $temFiltrosAtivos = !empty($filtroBusca) || !empty($filtroServico) || !empty($filtroResponsavel) || !empty($filtroCliente) || !empty($filtroColuna) || !empty($filtroDe) || !empty($filtroAte) || !empty($filtroTarefaId);
                    @endphp
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">🔍</span>
                    <input type="text"
                           id="kanban-index-autocomplete-input"
                           name="q"
                           value="{{ $filtroBusca ?? '' }}"
                           placeholder="Buscar..."
                           autocomplete="off"
                           class="w-full pl-9 pr-24 py-2.5 rounded-2xl border border-slate-200 bg-white/95
                              text-sm text-slate-700 shadow-sm
                              focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                    <div id="kanban-index-autocomplete-list"
                         class="absolute z-20 mt-1 w-full max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg hidden"></div>
                    <input type="hidden" name="servico_id" value="{{ $filtroServico }}">
                    <input type="hidden" name="responsavel_id" value="{{ $filtroResponsavel }}">
                    <input type="hidden" name="cliente_id" value="{{ $filtroCliente }}">
                    <input type="hidden" name="coluna_id" value="{{ $filtroColuna }}">
                    <input type="hidden" name="de" value="{{ $filtroDe }}">
                    <input type="hidden" name="ate" value="{{ $filtroAte }}">
                    <input type="hidden" name="tarefa_id" value="{{ $filtroTarefaId ?? '' }}">
                    <button type="submit"
                            class="absolute right-1.5 top-1.5 inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                        Buscar
                    </button>
                    @if($temFiltrosAtivos)
                        <a href="{{ route('operacional.kanban') }}"
                           class="absolute right-[4.9rem] top-1.5 inline-flex items-center px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">
                            Limpar
                        </a>
                    @endif
                </form>
            </div>

            <a href="{{ $canCreateTask ? route('operacional.kanban.aso.clientes') : 'javascript:void(0)' }}"
               @if(!$canCreateTask) title="Usuário sem permissão" aria-disabled="true" @endif
               class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl
                  {{ $canCreateTask ? 'bg-gradient-to-r from-sky-500 to-cyan-400 text-white shadow-md shadow-sky-500/30 hover:from-sky-600 hover:to-cyan-500' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}
                  text-sm font-semibold transition">
                <span>Nova Tarefa</span>
            </a>
        </div>

        {{-- Título --}}
        <div class="mb-3 space-y-2">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-slate-900">
                Painel Operacional
            </h1>
            <p class="mt-0.5 text-xs md:text-sm text-slate-500">
                Suas tarefas atribuídas ·
                <span class="font-medium text-sky-700">{{ $usuario->name }}</span>
            </p>
        </div>

        {{-- Filtros em card --}}
        <section class="mb-4 rounded-2xl bg-white/95 border border-slate-100 shadow-sm">
            <form method="GET" class="grid md:grid-cols-3 gap-3 md:gap-4 p-3 md:p-4 text-sm">
                <input type="hidden" name="q" value="{{ $filtroBusca ?? '' }}">

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        Tipo de Serviço
                    </label>
                    <select name="servico_id"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-sm
                               text-slate-700
                               focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                        <option value="">Todos os serviços</option>
                        @foreach($servicos as $servico)
                            <option value="{{ $servico->id }}" @selected($filtroServico == $servico->id)>
                                {{ $servico->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        Serviço adicionado por
                    </label>
                    <select name="responsavel_id"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-sm
                               text-slate-700
                               focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                        <option value="">Todos os usuários</option>
                        @foreach($responsaveis as $resp)
                            <option value="{{ $resp->id }}" @selected($filtroResponsavel == $resp->id)>
                                {{ $resp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        Status (Coluna)
                    </label>
                    <select name="coluna_id"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-sm
                                   text-slate-700
                                   focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                        <option value="">Todos os status</option>

                        {{-- 🔹 Novo item: Canceladas (soft delete) --}}
                        <option value="canceladas" @selected($filtroColuna === 'canceladas')>
                            Canceladas
                        </option>

                        @foreach($colunas as $col)
                            <option value="{{ $col->id }}" @selected($filtroColuna == $col->id)>
                                {{ $col->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        Data inicial
                    </label>
                    <div class="relative">
                        <input type="text"
                               inputmode="numeric"
                               placeholder="dd/mm/aaaa"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-3 pr-10 text-sm
                                  text-slate-700 js-date-text
                                  focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
                               data-date-target="kanban_de">
                        <button type="button"
                                class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                data-date-target="kanban_de"
                                aria-label="Abrir calendário">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
                            </svg>
                        </button>
                        <input type="hidden" id="kanban_de" name="de" value="{{ $filtroDe }}">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        Data final
                    </label>
                    <div class="relative">
                        <input type="text"
                               inputmode="numeric"
                               placeholder="dd/mm/aaaa"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-3 pr-10 text-sm
                                  text-slate-700 js-date-text
                                  focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
                               data-date-target="kanban_ate">
                        <button type="button"
                                class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                data-date-target="kanban_ate"
                                aria-label="Abrir calendário">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
                            </svg>
                        </button>
                        <input type="hidden" id="kanban_ate" name="ate" value="{{ $filtroAte }}">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        ID da Tarefa
                    </label>
                    <input type="number"
                           min="1"
                           step="1"
                           name="tarefa_id"
                           value="{{ $filtroTarefaId ?? '' }}"
                           placeholder="Ex.: 1234"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-sm
                              text-slate-700
                              focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                </div>

                <div class="flex items-end">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl
                               bg-slate-900 text-white text-sm font-semibold shadow-sm
                               hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        Filtrar
                    </button>
                </div>
            </form>
        </section>

        @php
            $statusCardsConfig = [
                'pendente'              => ['icon' => '⏳', 'bg' => 'from-amber-400 to-amber-500'],
                'pendentes'             => ['icon' => '⏳', 'bg' => 'from-amber-400 to-amber-500'],

                'em-execucao'           => ['icon' => '▶️', 'bg' => 'from-sky-500 to-blue-500'],

                'correcao'              => ['icon' => '🛠️', 'bg' => 'from-orange-500 to-orange-600'],

                'aguardando'            => ['icon' => '⏱️', 'bg' => 'from-purple-500 to-fuchsia-500'],
                'aguardando-fornecedor' => ['icon' => '⏱️', 'bg' => 'from-purple-500 to-fuchsia-500'],

                'finalizada'            => ['icon' => '✅', 'bg' => 'from-emerald-500 to-green-500'],
                'finalizadas'           => ['icon' => '✅', 'bg' => 'from-emerald-500 to-green-500'],

                'atrasado'              => ['icon' => '⛔', 'bg' => 'from-rose-500 to-red-500'],
                'atrasados'             => ['icon' => '⛔', 'bg' => 'from-rose-500 to-red-500'],
            ];
        @endphp



        {{-- Kanban --}}
        {{-- Kanban --}}
        <div class="mt-4 pb-6 overflow-x-auto xl:overflow-x-visible -mx-3 md:mx-0 px-3 md:px-0">
            <div class="flex gap-2 md:gap-3 min-w-max snap-x snap-mandatory">
                @foreach($colunas as $coluna)
                    @php
                        $slug = Str::slug($coluna->nome);
                        $config = $statusCardsConfig[$slug] ?? [
                            'icon' => '📌',
                            'bg'   => 'from-slate-400 to-slate-500',
                        ];

                        /** @var \Illuminate\Support\Collection $tarefasColuna */
                        $tarefasColuna = $tarefasPorColuna[$coluna->id] ?? collect();
                        $totalColuna = $tarefasColuna->count();
                    @endphp

                    {{-- UMA COLUNA COMPLETA (card resumo + raia) --}}

                    <section class="flex flex-col w-[clamp(210px,16vw,260px)] flex-shrink-0 gap-2 md:gap-3 snap-start">

                        {{-- Card resumo da coluna --}}
                        <article
                            class="rounded-2xl px-4 py-3 bg-gradient-to-r {{ $config['bg'] }}
                           text-white shadow-md flex items-center justify-between">

                            <div>
                                <h3 class="text-[11px] md:text-xs font-semibold uppercase tracking-wide opacity-90">
                                    {{ $coluna->nome }}
                                </h3>
                                <p class="mt-1 text-xl md:text-2xl font-bold leading-none">
                                    {{ $totalColuna }}
                                </p>
                            </div>

                            <div class="text-2xl md:text-3xl opacity-70">
                                {{ $config['icon'] }}
                            </div>
                        </article>

                        {{-- Raia do Kanban dessa coluna --}}
                        <article
                            class="bg-white border border-slate-200 rounded-2xl flex flex-col
                           h-[calc(100vh-320px)] min-h-[420px] max-h-[72vh] shadow-md">

                            {{-- cards --}}
                            <div class="flex-1 overflow-y-auto px-3 py-3 space-y-3 kanban-column"
                                 data-coluna-id="{{ $coluna->id }}"
                                 data-coluna-cor="{{ $coluna->cor }}"
                                 data-coluna-slug="{{ Str::slug($coluna->nome) }}">
                                {{-- aqui permanece o foreach dos cards de tarefa que você já tinha --}}

                                @forelse($tarefasColuna as $tarefa)
                                    @php
                                        $clienteNome  = optional($tarefa->cliente)->razao_social ?? 'Sem cliente';
                                        $servicoNome  = optional($tarefa->servico)->nome ?? 'Sem serviço';
                                        $isCancelada = $tarefa->trashed();

                                        $respNome     = optional($tarefa->responsavel)->name ?? 'Sem responsável';
                                        $dataHora     = $tarefa->inicio_previsto
                                                        ? \Carbon\Carbon::parse($tarefa->inicio_previsto)->format('d/m/Y H:i')
                                                        : 'Sem data';

                                        $funcionario   = optional($tarefa->funcionario);
                                        $funcionarioNome   = $funcionario->nome ?? null;
                                        $funcionarioCpf    = $funcionario->cpf ?? null;
                                        $funcionarioRg     = $funcionario->rg ?? null;
                                        $funcionarioFuncao = $funcionario->funcao_nome ?? null;
                                        $funcionarioSetor  = $funcionario->setor ?? null;
                                        $funcionarioCelular = $funcionario->celular ?? null;
                                        $funcionarioNascimentoRaw = $funcionario->data_nascimento
                                            ?? optional(optional($tarefa->asoSolicitacao)->funcionario)->data_nascimento;
                                        $funcionarioNascimento = $funcionarioNascimentoRaw
                                            ? \Carbon\Carbon::parse($funcionarioNascimentoRaw)->format('d/m/Y')
                                            : null;
                                        $funcionarioAdmissao = $funcionario->data_admissao
                                            ? \Carbon\Carbon::parse($funcionario->data_admissao)->format('d/m/Y')
                                            : null;
                                        $funcionarioAtivo = is_null($funcionario->ativo ?? null)
                                            ? null
                                            : ((bool) $funcionario->ativo ? 'Ativo' : 'Inativo');

                                        $slaData      = $tarefa->fim_previsto
                                                        ? \Carbon\Carbon::parse($tarefa->fim_previsto)->format('d/m/Y')
                                                        : '-';
                                        $obs          = $tarefa->descricao ?? '';

                                        $clienteCnpj  = optional($tarefa->cliente)->documento_principal ?? '';
                                        $clienteTelefonePrincipal = trim((string) (optional($tarefa->cliente)->telefone ?? ''));
                                        $clienteTelefoneSecundario = trim((string) (optional($tarefa->cliente)->telefone_2 ?? ''));
                                        $clienteTel   = $clienteTelefonePrincipal !== ''
                                            ? $clienteTelefonePrincipal
                                            : $clienteTelefoneSecundario;
                                        $clienteTipoPessoa = (string) (optional($tarefa->cliente)->tipo_pessoa ?? '');
                                        $clienteVendedorNome = $tarefa->vendedor_snapshot_nome
                                            ?? optional(optional($tarefa->cliente)->vendedor)->name
                                            ?? '—';

                                        $pgr  = $tarefa->pgrSolicitacao ?? null;
                                        $ltip = $tarefa->ltipSolicitacao;
                                        $toxicologico = $tarefa->exameToxicologicoSolicitacao;
                                        $toxicologicoSolicitante = '';
                                        if ($toxicologico) {
                                            $tituloTarefa = mb_strtolower((string) ($tarefa->titulo ?? ''));
                                            $descricaoTarefa = mb_strtolower((string) ($tarefa->descricao ?? ''));
                                            $ehColaborador = !empty($toxicologico->funcionario_id)
                                                || str_contains($tituloTarefa, 'colaborador da empresa')
                                                || str_contains($descricaoTarefa, 'colaborador da empresa');

                                            $toxicologicoSolicitante = $ehColaborador
                                                ? 'Colaborador da empresa'
                                                : 'Independente';
                                        }
                                        $toxicologicoTipo = $toxicologico
                                            ? ([
                                                'clt' => 'CLT',
                                                'cnh' => 'CNH',
                                                'concurso_publico' => 'Concurso Público',
                                            ][$toxicologico->tipo_exame] ?? $toxicologico->tipo_exame)
                                            : '';
                                        $toxicologicoNome = $toxicologico->nome_completo ?? '';
                                        $toxicologicoCpf = $toxicologico->cpf ?? '';
                                        $toxicologicoRg = $toxicologico->rg ?? '';
                                        $toxicologicoNascimento = $toxicologico?->data_nascimento
                                            ? \Carbon\Carbon::parse($toxicologico->data_nascimento)->format('d/m/Y')
                                            : '';
                                        $toxicologicoTelefone = $toxicologico->telefone ?? '';
                                        $toxicologicoEmail = $toxicologico->email_envio ?? '';
                                        $toxicologicoData = $toxicologico?->data_realizacao
                                            ? \Carbon\Carbon::parse($toxicologico->data_realizacao)->format('d/m/Y')
                                            : '';
                                        $toxicologicoUnidade = optional($toxicologico?->unidade)->nome ?? '';

                                        // ====== NOVO: dados específicos do ASO via aso_solicitacoes ======
                                        $aso                  = $tarefa->asoSolicitacao;
                                        $asoTipoLabel         = '';
                                        $asoDataFormatada     = '';
                                        $asoDataAdmissaoFormatada = '';
                                        $asoDataDemissaoFormatada = '';
                                        $asoUnidadeNome       = '';
                                        $asoTreinamentoFlag   = '';
                                        $asoTreinamentosLista = '';
                                        $asoEmail             = '';
                                        $asoPacoteNome        = '';
                                        $asoPcmsoExternoUrl   = '';
                                        $asoPcmsoExternoNome  = '';

                                        if ($aso) {
                                            $mapTiposAso = [
                                                'admissional'      => 'Admissional',
                                                'periodico'        => 'Periódico',
                                                'demissional'      => 'Demissional',
                                                'mudanca_funcao'   => 'Mudança de Função',
                                                'retorno_trabalho' => 'Retorno ao Trabalho',
                                            ];


                                            $asoTipoLabel     = $mapTiposAso[$aso->tipo_aso] ?? ucfirst($aso->tipo_aso);
                                            $asoDataFormatada = $aso->data_aso
                                                ? \Carbon\Carbon::parse($aso->data_aso)->format('d/m/Y')
                                                : '';
                                            $asoDataAdmissaoFormatada = ($funcionario && $funcionario->data_admissao)
                                                ? \Carbon\Carbon::parse($funcionario->data_admissao)->format('d/m/Y')
                                                : '';
                                            $asoDataDemissaoFormatada = $aso->data_demissao
                                                ? \Carbon\Carbon::parse($aso->data_demissao)->format('d/m/Y')
                                                : '';

                                            $asoUnidadeNome   = optional($aso->unidade)->nome ?? '';
                                            $asoEmail         = $aso->email_aso ?? '';
                                            $asoTreinamentoFlag = $aso->vai_fazer_treinamento ? 'Sim' : 'Não';

                                            // labels dos treinamentos
                                            $mapTrein = [
                                                'nr_35' => 'NR-35 - Trabalho em Altura',
                                                'nr_18' => 'NR-18 - Integração',
                                                'nr_12' => 'NR-12 - Máquinas e Equipamentos',
                                                'nr_06' => 'NR-06 - EPI',
                                                'nr_05' => 'NR-05 - CIPA Designada',
                                                'nr_01' => 'NR-01 - Ordem de Serviço',
                                                'nr_33' => 'NR-33 - Espaço Confinado',
                                                'nr_11' => 'NR-11 - Movimentação de Carga',
                                                'nr_10' => 'NR-10 - Elétrica',
                                            ];

                                            $codigosTreinamentosInformados = array_values(array_unique(array_filter(array_map(
                                                static fn ($v) => trim((string) $v),
                                                (array) ($aso->treinamentos ?? [])
                                            ))));
                                            if (empty($codigosTreinamentosInformados) && is_array($aso->treinamento_pacote ?? null)) {
                                                $codigosTreinamentosInformados = array_values(array_unique(array_filter(array_map(
                                                    static fn ($v) => trim((string) $v),
                                                    (array) ($aso->treinamento_pacote['codigos'] ?? [])
                                                ))));
                                            }

                                            $labelsTrein = [];
                                            foreach ($codigosTreinamentosInformados as $code) {
                                                $labelsTrein[] = $mapTrein[$code] ?? $code;
                                            }
                                            $asoTreinamentosLista = implode(', ', $labelsTrein);

                                            if (!empty($aso->treinamento_pacote)) {
                                                $pacote = (array) $aso->treinamento_pacote;
                                                $asoPacoteNome = $pacote['nome'] ?? '';
                                            }

                                            $asoPcmsoExternoUrl = optional($aso->pcmsoExternoAnexo)->url ?? '';
                                            $asoPcmsoExternoNome = optional($aso->pcmsoExternoAnexo)->nome_original ?? '';
                                        }

                                        $isAsoTask = (bool) $aso;

                                        $editUrl = null;
                                        if ($isAsoTask) {
                                            $editUrl = route('operacional.kanban.aso.editar', $tarefa);
                                        }
                                        if ($servicoNome === 'PGR') {
                                            $editUrl = route('operacional.kanban.pgr.editar', $tarefa);
                                        }
                                        if ($servicoNome === 'PCMSO') {
                                            $editUrl = route('operacional.kanban.pcmso.edit', $tarefa);
                                        }
                                        if ($servicoNome === 'LTCAT') {
                                            $editUrl = route('operacional.ltcat.edit', $tarefa);
                                        }
                                        if ($servicoNome === 'LTIP') {
                                            $editUrl = route('operacional.ltip.edit', $tarefa);
                                        }
                                        if ($servicoNome === 'APR') {
                                            $editUrl = route('operacional.apr.edit', $tarefa);
                                        }
                                        if ($servicoNome === 'PAE') {
                                            $editUrl = route('operacional.pae.edit', $tarefa);
                                        }
                                        if ($servicoNome === 'Treinamentos NRs') {
                                            $editUrl = route('operacional.treinamentos-nr.edit', $tarefa);
                                        }
                                        if (mb_strtolower((string) $servicoNome) === 'exame toxicológico' || mb_strtolower((string) $servicoNome) === 'exame toxicologico') {
                                            $editUrl = route('operacional.toxicologico.edit', $tarefa);
                                        }

                                        $slugColunaAtual = Str::slug((string) ($coluna->slug ?? $coluna->nome ?? ''));
                                        $isColunaPendente = in_array($slugColunaAtual, ['pendente', 'pendentes'], true);
                                        $cardBorderColor = $coluna->cor;
                                        if ($isColunaPendente) {
                                            if ($isAsoTask) {
                                                $cardBorderColor = '#2563eb'; // azul
                                            } elseif ($servicoNome === 'Treinamentos NRs') {
                                                $cardBorderColor = '#16a34a'; // verde
                                            }
                                        }

                                    @endphp




                                    <article
                                        class="kanban-card bg-white rounded-2xl shadow-md border border-slate-200 border-l-4
                                        px-3 py-3 text-xs cursor-pointer hover:shadow-lg transition hover:-translate-y-0.5"
                                        @if($isCancelada) opacity-60 ring-1 ring-red-200 @endif"
                                    style="border-left-color: {{ $cardBorderColor }};"

                                    data-move-url="{{ route('operacional.tarefas.mover', $tarefa) }}"

                                    data-cancelada="{{ $isCancelada ? '1' : '0' }}"

                                    @php
                                        $anexos = $tarefa->anexos ?? collect();

                                        $anexosPayload = $anexos->map(function (\App\Models\Anexos $anexo) {
                                            return [
                                                'id'          => $anexo->id,
                                                'nome'        => $anexo->nome_original,
                                                'url'         => $anexo->public_link,
                                                'delete_url'  => route('operacional.anexos.destroy', $anexo),
                                                'mime'        => $anexo->mime_type,
                                                'tamanho'     => $anexo->tamanho_humano,      // opcional
                                                'servico'     => $anexo->servico,
                                                'uploaded_by' => optional($anexo->uploader)->name,
                                                'uploaded_by_is_cliente' => $anexo->foiEnviadoPorCliente(),
                                                'data'        => optional($anexo->created_at)->format('d/m/Y H:i'),
                                            ];
                                        })->values();

                                        $treinamentosRelacionados = $tarefa->treinamentoNr ?? collect();
                                        $treinamentoDetalhes = $tarefa->treinamentoNrDetalhes;
                                        $isTreinamentoTask = $treinamentosRelacionados->isNotEmpty() && (bool) $treinamentoDetalhes;
                                        $certificadosEsperados = 0;
                                        if ($aso && $aso->vai_fazer_treinamento) {
                                            $codigosTreinamentos = [];
                                            $codigosTreinamentos = (array) ($aso->treinamentos ?? []);
                                            if (empty($codigosTreinamentos) && is_array($aso->treinamento_pacote ?? null)) {
                                                $codigosTreinamentos = (array) ($aso->treinamento_pacote['codigos'] ?? []);
                                            }
                                            $codigosTreinamentos = array_values(array_unique(array_filter(array_map(
                                                static fn ($v) => trim((string) $v),
                                                $codigosTreinamentos
                                            ))));
                                            $certificadosEsperados = count($codigosTreinamentos);
                                            if ($certificadosEsperados === 0) {
                                                $certificadosEsperados = 1;
                                            }
                                        } elseif ($isTreinamentoTask) {
                                            $treinamentoPayloadCertificados = (array) ($tarefa->treinamentoNrDetalhes->treinamentos ?? []);
                                            $treinamentoModoCertificados = (string) ($treinamentoPayloadCertificados['modo'] ?? '');
                                            if ($treinamentoModoCertificados === 'pacote') {
                                                $codigosTreinamentos = (array) data_get($treinamentoPayloadCertificados, 'pacote.codigos', []);
                                            } elseif (array_key_exists('codigos', $treinamentoPayloadCertificados)) {
                                                $codigosTreinamentos = (array) ($treinamentoPayloadCertificados['codigos'] ?? []);
                                            } else {
                                                $codigosTreinamentos = (array) $treinamentoPayloadCertificados;
                                            }
                                            $codigosTreinamentos = array_values(array_unique(array_filter(array_map(
                                                static fn ($v) => trim((string) $v),
                                                $codigosTreinamentos
                                            ))));
                                            $certificadosEsperados = count($codigosTreinamentos);
                                            if ($certificadosEsperados === 0) {
                                                $certificadosEsperados = 1;
                                            }
                                        }
                                        $certificadosEnviados = $anexos->filter(function ($anexo) {
                                            return mb_strtolower((string) ($anexo->servico ?? '')) === 'certificado_treinamento';
                                        })->count();
                                        $certificadosPendentes = $certificadosEsperados > 0
                                            && $certificadosEnviados < $certificadosEsperados;
                                        $documentoComplementarPgrPcmso = $anexos->first(function ($anexo) {
                                            return mb_strtolower((string) ($anexo->servico ?? '')) === 'documento_complementar_pgr_pcmso';
                                        });
                                        $documentoArtPgrPcmso = $anexos->first(function ($anexo) {
                                            return mb_strtolower((string) ($anexo->servico ?? '')) === 'documento_art_pgr_pcmso';
                                        });
                                    @endphp
                                    data-tem-anexos="{{ $anexos->isNotEmpty() ? '1' : '0' }}"
                                    data-anexos='@json($anexosPayload)'
                                    data-id="{{ $tarefa->id }}"
                                    data-cliente="{{ $clienteNome }}"
                                    data-cnpj="{{ $clienteCnpj }}"
                                    data-telefone="{{ $clienteTel }}"
                                    data-cliente-tipo-pessoa="{{ $clienteTipoPessoa }}"
                                    data-cliente-vendedor="{{ $clienteVendedorNome }}"
                                    data-servico="{{ $servicoNome }}"
                                    data-responsavel="{{ $respNome }}"
                                    data-datahora="{{ $dataHora }}"
                                    data-sla="{{ $slaData }}"
                                    data-fim-previsto="{{ optional($tarefa->fim_previsto)->toIso8601String() }}"
                                    data-move-url="{{ route('operacional.tarefas.mover', $tarefa) }}"
                                    data-finalizar-url="{{ route('operacional.tarefas.finalizar-com-arquivo', $tarefa) }}
                                    "
                                    data-finalizar-documento-existente-url="{{ route('operacional.tarefas.finalizar-documento-existente', $tarefa) }}"
                                    data-reprecificar-url="{{ route('operacional.tarefas.reprecificar', $tarefa) }}"
                                    data-substituir-doc-url="{{ route('operacional.tarefas.documento-cliente', $tarefa) }}"
                                    data-remover-documento-cliente-url="{{ route('operacional.tarefas.documento-cliente.destroy', $tarefa) }}"
                                    data-substituir-doc-complementar-url="{{ route('operacional.tarefas.documento-complementar', $tarefa) }}"
                                    data-substituir-doc-art-url="{{ route('operacional.tarefas.documento-art', $tarefa) }}"
                                    data-whatsapp-bundle-url="{{ $tarefa->pacote_publico_link }}"
                                    data-prioridade="{{ ucfirst($tarefa->prioridade) }}"
                                    data-status="{{ $coluna->nome }}"
                                    data-finalizado="{{ ($coluna->finaliza ?? false) ? '1' : '0' }}"
                                    data-observacoes="{{ e($obs) }}"

                                    data-funcionario="{{ $funcionarioNome ?? '' }}"
                                    data-funcionario-funcao="{{ $funcionarioFuncao }}"
                                    data-funcionario-cpf="{{ $funcionarioCpf }}"
                                    data-funcionario-rg="{{ $funcionarioRg }}"
                                    data-funcionario-celular="{{ $funcionarioCelular }}"
                                    data-funcionario-nascimento="{{ $funcionarioNascimento }}"
                                    data-funcionario-admissao="{{ $funcionarioAdmissao }}"
                                    data-funcionario-setor="{{ $funcionarioSetor }}"
                                    data-funcionario-ativo="{{ $funcionarioAtivo }}"

                                    data-aso-tipo="{{ $asoTipoLabel }}"
                                    data-aso-data="{{ $asoDataFormatada }}"
                                    data-aso-data-admissao="{{ $asoDataAdmissaoFormatada }}"
                                    data-aso-data-demissao="{{ $asoDataDemissaoFormatada }}"
                                    data-aso-unidade="{{ $asoUnidadeNome }}"
                                    data-aso-treinamento="{{ $asoTreinamentoFlag }}"
                                    data-aso-treinamentos="{{ $asoTreinamentosLista }}"
                                    data-aso-pacote="{{ $asoPacoteNome }}"
                                    data-aso-email="{{ $asoEmail }}"
                                    data-aso-pcmso-externo-url="{{ $asoPcmsoExternoUrl }}"
                                    data-aso-pcmso-externo-nome="{{ $asoPcmsoExternoNome }}"
                                    data-is-aso="{{ $isAsoTask ? '1' : '0' }}"
                                    data-is-treinamento-task="{{ $isTreinamentoTask ? '1' : '0' }}"
                                    data-certificados-pendentes="{{ $certificadosPendentes ? '1' : '0' }}"
                                    data-certificados-enviados="{{ $certificadosEnviados }}"
                                    data-certificados-total="{{ $certificadosEsperados }}"
                                    data-certificados-upload-url="{{ route('operacional.tarefas.certificados', $tarefa) }}"

                                    data-observacao-interna="{{ e($tarefa->observacao_interna) }}"
                                    data-observacao-url="{{ route('operacional.tarefas.observacao', $tarefa) }}"
                                    data-edit-url="{{ $editUrl }}"
                                    data-excluido-por="{{ $tarefa->excluidoPor?->name ?? '' }}"
                                    data-motivo-exclusao="{{ e($tarefa->motivo_exclusao ?? '') }}"
                                    data-toxicologico-solicitante="{{ $toxicologicoSolicitante }}"
                                    data-toxicologico-tipo="{{ $toxicologicoTipo }}"
                                    data-toxicologico-nome="{{ $toxicologicoNome }}"
                                    data-toxicologico-cpf="{{ $toxicologicoCpf }}"
                                    data-toxicologico-rg="{{ $toxicologicoRg }}"
                                    data-toxicologico-nascimento="{{ $toxicologicoNascimento }}"
                                    data-toxicologico-telefone="{{ $toxicologicoTelefone }}"
                                    data-toxicologico-email="{{ $toxicologicoEmail }}"
                                    data-toxicologico-data="{{ $toxicologicoData }}"
                                    data-toxicologico-unidade="{{ $toxicologicoUnidade }}"


                                    {{-- PGR --}}
                                    @if($pgr)
                                        @php
                                            $pgrFuncoesDetalhes = collect($pgr->funcoes ?? [])->map(function ($item) {
                                                $funcao = \App\Models\Funcao::find($item['funcao_id'] ?? null);
                                                return [
                                                    'nome' => $funcao?->nome ?? 'Funcao',
                                                    'quantidade' => (int) ($item['quantidade'] ?? 0),
                                                    'nr_altura' => (bool) ($item['nr_altura'] ?? false),
                                                    'nr_eletricidade' => (bool) ($item['nr_eletricidade'] ?? false),
                                                    'nr_espaco_confinado' => (bool) ($item['nr_espaco_confinado'] ?? false),
                                                ];
                                            })->values();
                                        @endphp
                                        data-pgr-tipo="{{ $pgr->tipo }}"
                                        data-pgr-com-art="{{ $pgr->com_art ? '1' : '0' }}"
                                        data-pgr-pcmso="{{ $pgr->com_pcms0 ? '1' : '0' }}"
                                        data-pgr-qtd-homens="{{ $pgr->qtd_homens }}"
                                        data-pgr-qtd-mulheres="{{ $pgr->qtd_mulheres }}"
                                        data-pgr-total-trabalhadores="{{ $pgr->total_trabalhadores }}"
                                        data-pgr-com-pcmso="{{ $pgr->com_pcms0 ? '1' : '0' }}"
                                        data-pgr-contratante="{{ $pgr->contratante_nome }}"
                                        data-pgr-contratante-cnpj="{{ $pgr->contratante_cnpj }}"
                                        data-pgr-obra-nome="{{ $pgr->obra_nome }}"
                                        data-pgr-obra-endereco="{{ $pgr->obra_endereco }}"
                                        data-pgr-obra-cej-cno="{{ $pgr->obra_cej_cno }}"
                                        data-pgr-obra-turno="{{ $pgr->obra_turno_trabalho }}"
                                        data-pgr-funcoes="{{ $pgr->funcoes_resumo }}"
                                        data-pgr-funcoes-json='@json($pgrFuncoesDetalhes)'
                                    @endif
                                    @if($tarefa->documento_link)
                                        data-arquivo-cliente-url="{{ $tarefa->documento_link }}"
                                    @endif
                                    @if($documentoComplementarPgrPcmso)
                                        data-pcmso-pgr-url="{{ $documentoComplementarPgrPcmso->public_link }}"
                                        data-pcmso-pgr-delete-url="{{ route('operacional.anexos.destroy', $documentoComplementarPgrPcmso) }}"
                                    @endif
                                    @if($documentoArtPgrPcmso)
                                        data-art-pgr-url="{{ $documentoArtPgrPcmso->public_link }}"
                                        data-art-pgr-delete-url="{{ route('operacional.anexos.destroy', $documentoArtPgrPcmso) }}"
                                    @endif

                                    {{-- APR --}}
                                    @if($tarefa->aprSolicitacao)
                                        @php
                                            $aprEpisDetalhes = collect($tarefa->aprSolicitacao->epis_json ?? [])
                                                ->map(function ($item) {
                                                    $tipo = (($item['tipo'] ?? 'epi') === 'maquina') ? 'maquina' : 'epi';
                                                    return [
                                                        'tipo' => $tipo,
                                                        'descricao' => trim((string) ($item['descricao'] ?? '')),
                                                    ];
                                                })
                                                ->filter(fn ($item) => $item['descricao'] !== '')
                                                ->values()
                                                ->all();
                                        @endphp
                                        data-apr-status="{{ $tarefa->aprSolicitacao->status }}"
                                        data-apr-obra-nome="{{ $tarefa->aprSolicitacao->obra_nome }}"
                                        data-apr-obra-endereco="{{ $tarefa->aprSolicitacao->obra_endereco }}"
                                        data-apr-data-inicio="{{ optional($tarefa->aprSolicitacao->atividade_data_inicio)->format('d/m/Y') }}"
                                        data-apr-data-fim="{{ optional($tarefa->aprSolicitacao->atividade_data_termino_prevista)->format('d/m/Y') }}"
                                        data-apr-endereco="{{ $tarefa->aprSolicitacao->endereco_atividade }}"
                                        data-apr-funcoes="{{ e($tarefa->aprSolicitacao->funcoes_envolvidas) }}"
                                        data-apr-etapas="{{ e($tarefa->aprSolicitacao->etapas_atividade) }}"
                                        data-apr-epis-json='@json($aprEpisDetalhes)'
                                    @endif

                                    {{-- LTCAT --}}
                                    @if($tarefa->ltcatSolicitacao)
                                        data-ltcat-tipo="{{ $tarefa->ltcatSolicitacao->tipo }}"
                                        data-ltcat-endereco="{{ $tarefa->ltcatSolicitacao->endereco_avaliacoes }}"
                                        data-ltcat-total-funcoes="{{ $tarefa->ltcatSolicitacao->total_funcoes }}"
                                        data-ltcat-total-funcionarios="{{ $tarefa->ltcatSolicitacao->total_funcionarios }}
                                        "
                                        data-ltcat-funcoes="{{ $tarefa->ltcatSolicitacao->funcoes_resumo }}"
                                    @endif

                                    {{-- LTIP --}}
                                    @if($servicoNome === 'LTIP' && $ltip)
                                        {{-- data-* usados no modal --}}

                                        data-ltip-endereco="{{ $ltip->endereco_avaliacoes }}"
                                        data-ltip-total-funcionarios="{{ $ltip->total_funcionarios }}"
                                        data-ltip-funcoes="{{ collect($ltip->funcoes ?? [])->map(function($f){
                                            $fn = \App\Models\Funcao::find($f['funcao_id'] ?? null);
                                            return ($fn?->nome ?? 'Função') . ' (' . ($f['quantidade'] ?? 0) . ')';
                                        })->implode(' | ') }}"
                                    @endif

                                    {{-- PAE --}}
                                    @if($tarefa->paeSolicitacao)
                                        @php $pae = $tarefa->paeSolicitacao; @endphp
                                        data-pae-endereco="{{ $pae->endereco_local }}"
                                        data-pae-total-funcionarios="{{ $pae->total_funcionarios }}"
                                        data-pae-descricao="{{ $pae->descricao_instalacoes }}"
                                    @endif

                                    {{-- PCMSO --}}
                                        @if($tarefa->pcmsoSolicitacao)
                                            @php $pcmso = $tarefa->pcmsoSolicitacao; @endphp
                                        data-pcmso-tipo="{{ $pcmso->tipo }}"
                                        data-pcmso-prazo="{{ $pcmso->prazo_dias }}"
                                        data-pcmso-obra-nome="{{ $pcmso->obra_nome }}"
                                        data-pcmso-obra-cnpj="{{ $pcmso->obra_cnpj_contratante }}"
                                        data-pcmso-obra-cei="{{ $pcmso->obra_cei_cno }}"
                                        data-pcmso-obra-endereco="{{ $pcmso->obra_endereco }}"
                                        data-pcmso-pgr-origem="{{ $pcmso->pgr_origem }}"
                                        @if($pcmso->pgr_arquivo_path)
                                            data-pcmso-pgr-url="{{ $pcmso->pgr_public_link }}"
                                        @endif
                                    @endif
                                    @if($isTreinamentoTask)
                                        @php
                                            $treiFuncs = $treinamentosRelacionados;
                                            $treiDet   = $treinamentoDetalhes;
                                            $listaNomesArr = $treiFuncs->pluck('funcionario.nome')
                                                ->filter()
                                                ->sort()
                                                ->values()
                                                ->all();
                                            $listaNomes = implode(', ', $listaNomesArr);
                                            $listaFuncoes = $treiFuncs->map(function ($treinamento) {
                                                return $treinamento->funcionario?->funcao?->nome;
                                            })
                                                ->filter()
                                                ->unique()
                                                ->values()
                                                ->implode(', ');
                                            $treiPayload = $treiDet->treinamentos ?? [];
                                            $treiModo = is_array($treiPayload) ? ($treiPayload['modo'] ?? null) : null;
                                            $treiPacote = '';
                                            $treiCodigos = [];
                                            if ($treiModo === 'pacote') {
                                                $treiPacote = (string) data_get($treiPayload, 'pacote.nome', '');
                                                $treiCodigos = (array) data_get($treiPayload, 'pacote.codigos', []);
                                            } else {
                                                if (is_array($treiPayload) && array_key_exists('codigos', $treiPayload)) {
                                                    $treiCodigos = (array) ($treiPayload['codigos'] ?? []);
                                                } else {
                                                    $treiCodigos = (array) $treiPayload;
                                                }
                                            }
                                            $treiCodigos = array_values(array_filter(array_map('strval', $treiCodigos)));
                                            $treiCodigosLabel = implode(', ', $treiCodigos);
                                        @endphp

                                        data-treinamento-participantes='@json($listaNomesArr)'
                                        data-treinamento-funcoes="{{ $listaFuncoes }}"
                                        data-treinamento-local="{{ $treiDet->local_tipo }}"
                                        data-treinamento-unidade="{{ optional($treiDet->unidade)->nome ?? '' }}"
                                        data-treinamento-modo="{{ $treiModo }}"
                                        data-treinamento-pacote="{{ $treiPacote }}"
                                        data-treinamento-codigos="{{ $treiCodigosLabel }}"
                                    @endif
                                    >

                                    @php
                                        // Label da tagzinha à direita
                                        $badgeLabel    = $servicoNome;
                                        $tituloCard    = $servicoNome;      // default
                                        $subtituloCard = $clienteNome;      // default

                                        // -------- ASO --------
                                        if ($isAsoTask) {
                                            $nomeFunc = $funcionarioNome
                                                ?? optional($tarefa->funcionario)->nome
                                                ?? 'Sem funcionário';

                                            $tituloCard    = 'ASO - ' . $nomeFunc;
                                            $subtituloCard = $clienteNome;

                                        // -------- PGR --------
                                        } elseif ($servicoNome === 'PGR') {
                                            $temPgrPcmso = $pgr && !empty($pgr->com_pcms0);
                                            if ($temPgrPcmso) {
                                                $tituloCard = 'PGR + PCMSO - ' . $clienteNome;
                                                $subtituloCard = $pgr->obra_nome ?: 'Obra não informada';
                                                $badgeLabel = 'PGR + PCMSO';
                                            } elseif ($pgr && $pgr->obra_nome) {
                                                $tituloCard    = 'PGR - ' . $pgr->obra_nome;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'PGR - ' . $clienteNome;
                                            }

                                        // -------- PCMSO --------
                                        } elseif ($servicoNome === 'PCMSO') {
                                            $pcmso = $tarefa->pcmsoSolicitacao ?? null;
                                            $pcmsoTipo = $pcmso?->tipo ?? null;

                                            if ($pcmsoTipo === 'especifico') {
                                                if ($pcmso && $pcmso->obra_nome) {
                                                    $tituloCard = 'PCMSO - Específico - ' . $pcmso->obra_nome;
                                                } else {
                                                    $tituloCard = 'PCMSO - Específico - ' . $clienteNome;
                                                }
                                                $subtituloCard = $clienteNome;
                                            } elseif ($pcmsoTipo === 'matriz') {
                                                $tituloCard = 'PCMSO - Matriz - ' . $clienteNome;
                                            } else {
                                                $tituloCard    = 'PCMSO - ' . $clienteNome;
                                            }

                                        // -------- LTCAT --------
                                        } elseif ($servicoNome === 'LTCAT') {
                                            $lt = $tarefa->ltcatSolicitacao ?? null;

                                            if ($lt && $lt->endereco_avaliacoes) {
                                                $tituloCard    = 'LTCAT - ' . $lt->endereco_avaliacoes;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'LTCAT - ' . $clienteNome;
                                            }

                                        // -------- LTIP --------
                                        } elseif ($servicoNome === 'LTIP') {
                                            $ltip = $tarefa->ltipSolicitacao ?? null;

                                            if ($ltip && $ltip->endereco_avaliacoes) {
                                                $tituloCard    = 'LTIP - ' . $ltip->endereco_avaliacoes;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'LTIP - ' . $clienteNome;
                                            }

                                        // -------- APR --------
                                        } elseif ($servicoNome === 'APR') {
                                            $apr = $tarefa->aprSolicitacao ?? null;

                                            if ($apr && $apr->endereco_atividade) {
                                                $tituloCard    = 'APR - ' . $apr->endereco_atividade;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'APR - ' . $clienteNome;
                                            }

                                        // -------- PAE --------
                                        } elseif ($servicoNome === 'PAE') {
                                            $pae = $tarefa->paeSolicitacao ?? null;

                                            if ($pae && $pae->endereco_local) {
                                                $tituloCard    = 'PAE - ' . $pae->endereco_local;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'PAE - ' . $clienteNome;
                                            }

                                        // -------- TREINAMENTOS NRs --------
                                        } elseif ($servicoNome === 'Treinamentos NRs') {
                                            $tituloCard    = 'Treinamento NR - ' . $clienteNome;
                                            $subtituloCard = 'Colaboradores: ' . ($tarefa->treinamentoNr()->count() ?? 0);

                                        // -------- DEFAULT (qualquer outro serviço) --------
                                        } else {
                                            $tituloCard    = $servicoNome . ' - ' . $clienteNome;
                                            $subtituloCard = $clienteNome;
                                        }
                                    @endphp

                                    {{-- ================== CABEÇALHO ================== --}}
                                    <div class="flex items-start justify-between gap-2 mb-1.5">
                                        <h4 class="text-[15px] font-semibold text-slate-800 leading-snug">
                                            {{ $tituloCard }}
                                        </h4>

                                        <div class="flex flex-col items-end gap-1">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full
                                                       text-[11px] font-semibold border"
                                                data-role="card-responsavel-badge"
                                                style="border-color: {{ $coluna->cor }}; color: #0f172a; background-color: {{ $coluna->cor }}20;">
                                                {{ $badgeLabel }}
                                            </span>

                                            @if($certificadosEsperados > 0)
                                                @php
                                                    $certBadgeClass = $certificadosPendentes
                                                        ? 'bg-amber-50 border-amber-200 text-amber-700'
                                                        : 'bg-emerald-50 border-emerald-200 text-emerald-700';
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $certBadgeClass }}">
                                                    Trein. {{ $certificadosEnviados }}/{{ $certificadosEsperados }}
                                                </span>
                                            @endif

                                            @if($isCancelada)
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full
                                                   text-[10px] font-semibold bg-red-50 border border-red-200
                                                   text-red-700 uppercase tracking-wide">
                                                        Cancelada
                                                </span>
                                                <span class="text-[10px] text-red-600">
                                                    Excluída por: {{ $tarefa->excluidoPor?->name ?? '—' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- ================== SUBTÍTULO (CLIENTE / ENDEREÇO / OBRA) ================== --}}
                                    <p class="text-[13px] text-slate-500 font-medium mb-2">
                                        {{ $subtituloCard }}
                                    </p>

                                    {{-- ================== DATA / HORÁRIO ================== --}}
                                    <div class="flex items-center gap-1 text-[11px] text-slate-500 mb-2">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="none">
                                            <circle cx="10" cy="10" r="8" stroke="#9CA3AF" stroke-width="1.5"/>
                                            <path d="M10 5.5V10.2L13 12" stroke="#9CA3AF" stroke-width="1.5"
                                                  stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span>{{ $dataHora }}</span>
                                    </div>

                                    {{-- ================== SLA / PRAZO ================== --}}
                                    @if($slaData && $slaData !== '-')
                                        <div class="mb-2">
                                            <div
                                                class="inline-flex items-center gap-1 px-3 py-1 rounded-md border
                                                    border-amber-200 bg-amber-50 text-[11px] text-amber-700">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="none">
                                                    <circle cx="10" cy="10" r="8" stroke="#FBBF24" stroke-width="1.5"/>
                                                    <path d="M10 5.5V10.2L13 12" stroke="#F59E0B" stroke-width="1.5"
                                                          stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span>{{ $slaData }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    @if($tarefa->fim_previsto)
                                        <div class="mb-2">
                                            <div
                                                class="inline-flex items-center gap-1 px-3 py-1 rounded-md border
                                                    border-slate-200 bg-slate-50 text-[11px] text-slate-600"
                                                data-role="card-tempo-label">
                                                <span>⏱</span>
                                                <span data-role="card-tempo-text">—</span>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- ================== RODAPÉ ================== --}}
                                    <div class="mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-500
                                        flex items-center justify-between">
                                        <span>#{{ $tarefa->id }}</span>

                                        <div class="inline-flex items-center gap-1 font-medium text-slate-600">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="none">
                                                <path d="M5 5h10v10H5z" stroke="#9CA3AF" stroke-width="1.4"/>
                                            </svg>
                                            <span>Detalhes</span>
                                        </div>
                                    </div>
                        </article>

                        @empty
                            <p class="text-[11px] text-slate-400 italic">Nenhuma tarefa nesta coluna.</p>

                    @endforelse
            </div>

            </section>

            @endforeach
        </div>
    </div>
    </div>
    @include('operacional.kanban.modals.anexo-documento-cliente')

    {{-- Modal de Detalhes da Tarefa --}}
    <div id="tarefa-modal"
         data-overlay-root="true"
         class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/50 p-4 overflow-y-auto">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden">
            <div id="modal-upload-loading"
                 class="absolute inset-0 z-20 hidden items-center justify-center bg-slate-950/45 backdrop-blur-[1px]">
                <div class="mx-6 flex max-w-sm flex-col items-center gap-3 rounded-2xl border border-white/20 bg-white px-6 py-5 text-center shadow-2xl">
                    <div class="h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-sky-600"></div>
                    <div>
                        <p id="modal-upload-loading-title" class="text-sm font-semibold text-slate-900">
                            Enviando anexo
                        </p>
                        <p id="modal-upload-loading-text" class="mt-1 text-xs text-slate-500">
                            Aguarde enquanto o arquivo e processado.
                        </p>
                    </div>
                </div>
            </div>
            {{-- Cabeçalho --}}
            {{-- Cabeçalho (VERSÃO DEBUG) --}}
            <div
                class="flex items-center justify-between px-6 py-4
           rounded-t-2xl shadow-sm
           bg-gradient-to-r from-sky-800 via-sky-700 to-sky-500
           text-white border-b border-sky-900/40"
            >
                <div>
                    <h2 class="text-lg font-semibold tracking-tight">
                        Detalhes da Tarefa
                    </h2>
                    <p class="text-xs text-white/80 mt-0.5">
                        ID:
                        <span id="modal-tarefa-id"
                              class="font-mono bg-white/10 px-2 py-0.5 rounded">
                -
            </span>
                    </p>
                </div>

                <button type="button"
                        id="tarefa-modal-close"
                        class="w-8 h-8 flex items-center justify-center rounded-full
                   bg-white/15 hover:bg-white/25
                   text-white text-sm font-bold
                   transition-colors duration-150"
                        aria-label="Fechar">
                    ✕
                </button>
            </div>


            {{-- Conteúdo --}}
            <div
                class="flex-1 overflow-y-auto p-6 grid grid-cols-1 lg:grid-cols-[2fr,1.5fr] gap-5 text-sm text-slate-700">
                {{-- Coluna esquerda --}}
                <div class="space-y-4">
                    {{-- 1. Detalhes da solicitação --}}
                    <section id="modal-bloco-solicitacao" class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-slate-500 mb-3">
                            1. DETALHES DA SOLICITAÇÃO
                        </h3>
                        <dl class="space-y-1 text-sm">
                            <div>
                                <dt class="text-[11px] text-slate-500">Razão Social</dt>
                                <dd class="font-semibold" id="modal-cliente"></dd>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                                <div>
                                    <dt class="text-[11px] text-slate-500">Documento</dt>
                                    <dd class="font-medium" id="modal-cnpj">—</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] text-slate-500">Telefone</dt>
                                    <dd class="font-medium" id="modal-telefone">—</dd>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                                <div>
                                    <dt class="text-[11px] text-slate-500">Responsável da tarefa</dt>
                                    <dd class="font-medium" id="modal-responsavel"></dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] text-slate-500">Vendedor do cliente</dt>
                                    <dd class="font-medium" id="modal-cliente-vendedor">—</dd>
                                </div>
                            </div>

                            <div id="modal-bloco-funcionario" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 hidden">
                                <div class="md:col-span-2">
                                    <div class="flex items-center gap-3 py-2">
                                        <div class="h-px flex-1 bg-slate-200"></div>
                                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
                                            Dados do Funcionário
                                        </span>
                                        <div class="h-px flex-1 bg-slate-200"></div>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Funcionário</dt>
                                        <dd class="font-medium" id="modal-funcionario">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">CPF</dt>
                                        <dd class="font-medium" id="modal-funcionario-cpf">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">RG</dt>
                                        <dd class="font-medium" id="modal-funcionario-rg">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Celular</dt>
                                        <dd class="font-medium" id="modal-funcionario-celular">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Função</dt>
                                        <dd class="font-medium" id="modal-funcionario-funcao">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Data de Nascimento</dt>
                                        <dd class="font-medium" id="modal-funcionario-nascimento">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Data de Admissão</dt>
                                        <dd class="font-medium" id="modal-funcionario-admissao">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Status</dt>
                                        <dd class="font-medium" id="modal-funcionario-ativo">—</dd>
                                    </div>
                                </div>
                            </div>

                            {{-- BLOCO ESPECÍFICO: ASO --}}
                            <div id="modal-bloco-aso" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 hidden">
                                <div class="space-y-1">
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Tipo de ASO</dt>
                                        <dd class="font-medium" id="modal-aso-tipo">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Data de Realização</dt>
                                        <dd class="font-medium" id="modal-aso-data">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Data de Admissão</dt>
                                        <dd class="font-medium" id="modal-aso-data-admissao">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Data de Demissão</dt>
                                        <dd class="font-medium" id="modal-aso-data-demissao">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Unidade</dt>
                                        <dd class="font-medium" id="modal-aso-unidade">—</dd>
                                    </div>
                                </div>

                                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 mt-1">
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Vai fazer treinamento?</dt>
                                        <dd class="font-medium" id="modal-aso-treinamento">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">E-mail para envio do ASO</dt>
                                        <dd class="font-medium" id="modal-aso-email">—</dd>
                                    </div>
                                </div>

                                    <div class="md:col-span-2 mt-1">
                                        <dt class="text-[11px] text-slate-500">Treinamentos selecionados</dt>
                                        <dd class="font-medium text-sm" id="modal-aso-treinamentos">—</dd>
                                    </div>
                                    <div class="md:col-span-2 mt-1">
                                        <dt class="text-[11px] text-slate-500">Pacote de Treinamento</dt>
                                        <dd class="font-medium text-sm" id="modal-aso-pacote">—</dd>
                                    </div>
                                </div>

                        </dl>
                    </section>

                    <section id="modal-bloco-toxicologico" class="bg-white border border-slate-200 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-slate-500 mb-3">1.1 DADOS DO EXAME TOXICOLÓGICO</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-[11px] text-slate-500">Solicitante</dt>
                                <dd class="font-medium" id="modal-toxicologico-solicitante">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-500">Tipo</dt>
                                <dd class="font-medium" id="modal-toxicologico-tipo">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-500">Nome</dt>
                                <dd class="font-medium" id="modal-toxicologico-nome">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-500">CPF</dt>
                                <dd class="font-medium" id="modal-toxicologico-cpf">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-500">RG</dt>
                                <dd class="font-medium" id="modal-toxicologico-rg">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-500">Data de nascimento</dt>
                                <dd class="font-medium" id="modal-toxicologico-nascimento">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-500">Telefone</dt>
                                <dd class="font-medium" id="modal-toxicologico-telefone">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-500">E-mail</dt>
                                <dd class="font-medium" id="modal-toxicologico-email">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-500">Vendedor responsável</dt>
                                <dd class="font-medium" id="modal-toxicologico-vendedor">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-500">Data de realização</dt>
                                <dd class="font-medium" id="modal-toxicologico-data">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-500">Unidade</dt>
                                <dd class="font-medium" id="modal-toxicologico-unidade">—</dd>
                            </div>
                        </div>
                    </section>

                    {{-- 2. Status atual --}}
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-slate-500 mb-3">
                            2. STATUS ATUAL
                        </h3>
                        <span id="modal-status-badge"
                              class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200">
                            <span id="modal-status-text"></span>
                        </span>
                        <div id="modal-exclusao-info" class="mt-3 hidden text-xs text-slate-600">
                            <div>
                                <span class="font-semibold">Excluída por:</span>
                                <span id="modal-excluido-por">—</span>
                            </div>
                            <div class="mt-1">
                                <span class="font-semibold">Motivo da exclusão:</span>
                                <span id="modal-motivo-exclusao">—</span>
                            </div>
                            <div id="modal-exclusao-anexo-wrapper" class="mt-2 hidden">
                                <span class="font-semibold">Print do cancelamento:</span>
                                <ul id="modal-exclusao-anexo-list" class="mt-1 space-y-1"></ul>
                            </div>
                        </div>
                    </section>

                    {{-- 3. Descrição da tarefa --}}
                    <section class="bg-violet-50 border border-violet-100 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-violet-700 mb-2">
                            3. DESCRIÇÃO DA TAREFA
                        </h3>
                        <p class="font-semibold text-slate-800 mb-1" id="modal-servico"></p>
                        <p class="text-sm text-slate-700" id="modal-observacoes"></p>
                        <div id="modal-documento-cliente-wrapper" class="mt-3 hidden">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700">
                                Documento enviado pelo cliente
                            </p>
                            <a id="modal-documento-cliente-link"
                               href="#"
                               target="_blank"
                               class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-cyan-700 underline hover:text-cyan-900">
                                Ver documento
                            </a>
                        </div>
                    </section>
                    {{-- BLOCO PGR (aparece só em serviços PGR) --}}
                    <section id="modal-bloco-pgr"
                             class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-emerald-700 mb-2">
                            4. DETALHES DO PGR
                        </h3>

                        <dl class="space-y-1 text-sm">
                            <div>
                                <dt class="text-[11px] text-slate-600">Tipo PGR</dt>
                                <dd class="font-medium" id="modal-pgr-tipo">—</dd>
                            </div>

                            <div class="mt-1">
                                <dt class="text-[11px] text-slate-600">ART</dt>
                                <dd class="font-medium" id="modal-pgr-art">—</dd>
                            </div>

                            <div class="grid grid-cols-3 gap-3 mt-2">
                                <div>
                                    <dt class="text-[11px] text-slate-600">Homens</dt>
                                    <dd class="font-medium" id="modal-pgr-qtd-homens">—</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] text-slate-600">Mulheres</dt>
                                    <dd class="font-medium" id="modal-pgr-qtd-mulheres">—</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] text-slate-600">Total</dt>
                                    <dd class="font-medium" id="modal-pgr-total-trabalhadores">—</dd>
                                </div>
                            </div>

                            <div class="mt-2">
                                <dt class="text-[11px] text-slate-600">Com PCMSO?</dt>
                                <dd class="font-medium" id="modal-pgr-com-pcmso">—</dd>
                            </div>

                            <div class="mt-3">
                                <dt class="text-[11px] text-slate-600">Contratante</dt>
                                <dd class="font-medium" id="modal-pgr-contratante">—</dd>
                                <dd class="text-xs text-slate-500" id="modal-pgr-contratante-cnpj">—</dd>
                            </div>

                            <div class="mt-3">
                                <dt class="text-[11px] text-slate-600">Obra</dt>
                                <dd class="font-medium" id="modal-pgr-obra-nome">—</dd>
                                <dd class="text-xs text-slate-500" id="modal-pgr-obra-endereco">—</dd>
                                <dd class="text-xs text-slate-500" id="modal-pgr-obra-cej-cno">—</dd>
                                <dd class="text-xs text-slate-500" id="modal-pgr-obra-turno">—</dd>
                            </div>

                            <div class="mt-3">
                                <dt class="text-[11px] text-slate-600">Funções / Cargos</dt>
                                <dd id="modal-pgr-funcoes" class="text-sm">—</dd>
                            </div>
                        </dl>
                    </section>
                    {{-- ====================== APR ====================== --}}
                    <section id="modal-bloco-apr"
                             class="bg-purple-50 border border-purple-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-purple-700 mb-2">DETALHES DO APR</h3>

                        <p><b>Obra:</b> <span id="modal-apr-obra-nome"></span></p>
                        <p class="mt-1"><b>Endereço da obra:</b> <span id="modal-apr-obra-endereco"></span></p>
                        <p class="mt-1"><b>Período:</b> <span id="modal-apr-periodo"></span></p>
                        <hr class="my-2 border-purple-100">
                        <p><b>Endereço da atividade:</b> <span id="modal-apr-endereco"></span></p>
                        <div class="mt-2">
                            <p><b>Funções envolvidas:</b></p>
                            <ul id="modal-apr-funcoes" class="mt-1 list-disc pl-5 text-sm text-slate-700"></ul>
                        </div>
                        <div class="mt-2">
                            <p><b>Etapas da atividade:</b></p>
                            <ul id="modal-apr-etapas" class="mt-1 list-disc pl-5 text-sm text-slate-700"></ul>
                        </div>
                        <div class="mt-2">
                            <p><b>EPIs e Máquinas:</b></p>
                            <ul id="modal-apr-epis" class="mt-1 list-disc pl-5 text-sm text-slate-700"></ul>
                        </div>
                    </section>

                    {{-- ====================== LTCAT ====================== --}}
                    <section id="modal-bloco-ltcat"
                             class="bg-orange-50 border border-orange-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-orange-700 mb-2">DETALHES DO LTCAT</h3>

                        <p><b>Tipo:</b> <span id="modal-ltcat-tipo"></span></p>
                        <p><b>Endereço das Avaliações:</b> <span id="modal-ltcat-endereco"></span></p>

                        <p class="mt-1">
                            <b>Total Funções:</b> <span id="modal-ltcat-total-funcoes"></span>
                            &nbsp;|&nbsp;
                            <b>Total Funcionários:</b> <span id="modal-ltcat-total-func"></span>
                        </p>

                        <p class="mt-1">
                            <b>Funções:</b>
                            <span id="modal-ltcat-funcoes"></span>
                        </p>
                    </section>

                    {{-- ====================== LTIP ====================== --}}
                    <section id="modal-bloco-ltip"
                             class="bg-blue-50 border border-blue-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-blue-700 mb-2">DETALHES DO LTIP</h3>
                        <p><b>Endereço das avaliações:</b> <span id="modal-ltip-endereco"></span></p>
                        <p><b>Funções:</b> <span id="modal-ltip-funcoes"></span></p>
                        <p><b>Total Funcionários:</b> <span id="modal-ltip-total-func"></span></p>
                    </section>
                    {{-- ====================== PAE ====================== --}}
                    <section id="modal-bloco-pae"
                             class="bg-red-50 border border-red-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-red-700 mb-2">DETALHES DO PAE</h3>

                        <p><b>Endereço Local:</b> <span id="modal-pae-endereco"></span></p>
                        <p><b>Total de funcionários:</b> <span id="modal-pae-total-func"></span></p>

                        <div class="mt-1">
                            <p class="text-[11px] text-red-700 font-semibold mb-0.5">Descrição das instalações</p>
                            <p class="text-sm" id="modal-pae-descricao">—</p>
                        </div>
                    </section>
                    {{-- ==================== TREINAMENTO NR ==================== --}}
                    <section id="modal-bloco-treinamento"
                             class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-indigo-700 mb-2">DETALHES DO TREINAMENTO NR</h3>

                        <p><b>Local:</b> <span id="modal-treinamento-local"></span></p>

                        <p><b>Unidade / Clínica:</b> <span id="modal-treinamento-unidade"></span></p>

                        <div class="mt-2">
                            <p class="text-[11px] font-semibold text-indigo-700">Modo</p>
                            <p id="modal-treinamento-modo" class="text-sm">—</p>
                        </div>

                        <div class="mt-2">
                            <p class="text-[11px] font-semibold text-indigo-700">Treinamentos selecionados</p>
                            <p id="modal-treinamento-codigos" class="text-sm">—</p>
                        </div>

                        <div class="mt-2">
                            <p class="text-[11px] font-semibold text-indigo-700">Pacote selecionado</p>
                            <p id="modal-treinamento-pacote" class="text-sm">—</p>
                        </div>

                        <div class="mt-2">
                            <p class="text-[11px] font-semibold text-indigo-700">Participantes</p>
                            <p id="modal-treinamento-participantes" class="text-sm">—</p>
                        </div>
                    </section>

                    {{-- ====================== PCMSO ====================== --}}
                    <section id="modal-bloco-pcmso"
                             class="bg-cyan-50 border border-cyan-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-cyan-700 mb-2">DETALHES DO PCMSO</h3>

                        <p><b>Tipo:</b> <span id="modal-pcmso-tipo"></span></p>
                        <p><b>Prazo:</b> <span id="modal-pcmso-prazo"></span> dias</p>

                        <div class="mt-2 space-y-0.5">
                            <p><b>Obra / Unidade:</b> <span id="modal-pcmso-obra-nome"></span></p>
                            <p><b>CNPJ Contratante:</b> <span id="modal-pcmso-obra-cnpj"></span></p>
                            <p><b>CEI / CNO:</b> <span id="modal-pcmso-obra-cei"></span></p>
                            <p><b>Endereço da Obra:</b> <span id="modal-pcmso-obra-endereco"></span></p>
                        </div>

                        <div id="modal-pcmso-pgr-wrapper" class="mt-3">
                            <a id="modal-pcmso-pgr-link"
                               href="#"
                               target="_blank"
                               class="inline-flex items-center gap-1 text-xs font-semibold text-cyan-700 hover:text-cyan-900 underline">
                                📎 Abrir PGR anexado pelo cliente
                            </a>
                        </div>
                    </section>


                    {{-- 6. Tipo de serviço --}}
                    <section class="bg-emerald-50 border border-emerald-100 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-emerald-700 mb-2">
                            6. TIPO DE SERVIÇO
                        </h3>
                        <p class="font-semibold" id="modal-tipo-servico"></p>
                    </section>

                    {{-- 7. Informações de data --}}
                    <section class="bg-amber-50 border border-amber-100 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-amber-700 mb-3">
                            7. INFORMAÇÕES DE DATA
                        </h3>
                        <dl class="space-y-1 text-sm">
                            <div>
                                <dt class="text-[11px] text-slate-600">Início previsto</dt>
                                <dd class="font-medium" id="modal-datahora"></dd>
                            </div>
                            <div class="mt-2">
                                <dt class="text-[11px] text-slate-600">Prazo / SLA</dt>
                                <dd class="font-medium" id="modal-sla"></dd>
                            </div>
                            <div class="mt-2">
                                <dt class="text-[11px] text-slate-600">Tempo restante</dt>
                                <dd class="font-medium" id="modal-tempo-restante">—</dd>
                            </div>
                            <div class="mt-2">
                                <dt class="text-[11px] text-slate-600">Prioridade</dt>
                                <dd class="font-medium" id="modal-prioridade"></dd>
                            </div>
                        </dl>
                    </section>
                </div>
                {{-- BLOCO ESPECÍFICO: PGR --}}
                <section id="modal-bloco-pgr"
                         class="bg-sky-50 border border-sky-100 rounded-xl p-4 hidden">
                    <h3 class="text-xs font-semibold text-sky-700 mb-3">
                        8. DADOS DO PGR
                    </h3>

                    <dl class="space-y-1 text-sm">
                        <div>
                            <dt class="text-[11px] text-slate-600">Tipo PGR</dt>
                            <dd class="font-medium" id="modal-pgr-tipo">—</dd>
                        </div>

                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">ART</dt>
                            <dd class="font-medium" id="modal-pgr-art">—</dd>
                        </div>

                        <div class="mt-2 grid grid-cols-3 gap-3">
                            <div>
                                <dt class="text-[11px] text-slate-600">Homens</dt>
                                <dd class="font-medium" id="modal-pgr-qtd-homens">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-600">Mulheres</dt>
                                <dd class="font-medium" id="modal-pgr-qtd-mulheres">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-600">Total</dt>
                                <dd class="font-medium" id="modal-pgr-total-trabalhadores">—</dd>
                            </div>
                        </div>

                        <div class="mt-2">
                            <dt class="text-[11px] text-slate-600">Com PCMSO?</dt>
                            <dd class="font-medium" id="modal-pgr-com-pcmso">—</dd>
                        </div>

                        <hr class="my-3 border-sky-100">

                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">Contratante</dt>
                            <dd class="font-medium" id="modal-pgr-contratante">—</dd>
                        </div>
                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">CNPJ Contratante</dt>
                            <dd class="font-medium" id="modal-pgr-contratante-cnpj">—</dd>
                        </div>

                        <div class="mt-3">
                            <dt class="text-[11px] text-slate-600">Nome da Obra</dt>
                            <dd class="font-medium" id="modal-pgr-obra-nome">—</dd>
                        </div>
                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">Endereço da Obra</dt>
                            <dd class="font-medium" id="modal-pgr-obra-endereco">—</dd>
                        </div>
                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">CEJ/CNO</dt>
                            <dd class="font-medium" id="modal-pgr-obra-cej-cno">—</dd>
                        </div>
                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">Turno(s) de Trabalho</dt>
                            <dd class="font-medium" id="modal-pgr-obra-turno">—</dd>
                        </div>

                        <div class="mt-3">
                            <dt class="text-[11px] text-slate-600">Funções e Cargos</dt>
                            <dd class="font-medium">
                                <ul id="modal-pgr-funcoes" class="list-disc list-inside text-xs space-y-0.5">
                                    {{-- preenchido via JS --}}
                                </ul>
                            </dd>
                        </div>
                    </dl>
                </section>


                {{-- Coluna direita --}}
                <div class="space-y-4">
                    {{-- 4. Ações rápidas --}}
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-slate-500 mb-3">
                            4. AÇÕES RÁPIDAS
                        </h3>

                        <div class="space-y-3">

                            {{-- NOVO: botão Editar Tarefa --}}
                            <button type="button"
                                    id="btn-editar-tarefa"
                                    data-permission-locked="{{ $canUpdateTask ? '0' : '1' }}"
                                    @if(!$canUpdateTask) title="Usuário sem permissão" @endif
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                       {{ $canUpdateTask ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} text-sm font-semibold shadow-sm
                       transition"
                                    @if(!$canUpdateTask) disabled @endif>
                                Editar Tarefa
                            </button>

                            <hr class="border-slate-200">


                            <button type="button"
                                    data-coluna-id="2"
                                    data-permission-locked="{{ $canUpdateTask ? '0' : '1' }}"
                                    @if(!$canUpdateTask) title="Usuário sem permissão" @endif
                                    class="js-mover-coluna w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                       {{ $canUpdateTask ? 'bg-[color:var(--color-brand-azul,#2563eb)] text-white hover:bg-blue-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} text-sm font-semibold shadow-sm
                       transition"
                                    @if(!$canUpdateTask) disabled @endif>
                                Mover para: Em Execução
                            </button>

                            <button type="button"
                                    data-coluna-id="6"
                                    data-permission-locked="{{ $canUpdateTask ? '0' : '1' }}"
                                    @if(!$canUpdateTask) title="Usuário sem permissão" @endif
                                    class="js-mover-coluna w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                       {{ $canUpdateTask ? 'bg-rose-500 text-white hover:bg-rose-600' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} text-sm font-semibold shadow-sm
                       transition"
                                    @if(!$canUpdateTask) disabled @endif>
                                Mover para: Atrasado
                            </button>

                            <button
                                type="button"
                                id="modal-finalizar-btn"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                                       bg-sky-600 text-white text-sm font-semibold shadow-sm
                                       hover:bg-sky-700 transition">
                                Finalizar tarefa
                            </button>

                            <button
                                type="button"
                                id="modal-reprecificar-btn"
                                data-permission-locked="{{ $canUpdateTask ? '0' : '1' }}"
                                @if(!$canUpdateTask) title="Usuário sem permissão" @endif
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                                       {{ $canUpdateTask ? 'bg-amber-500 text-white hover:bg-amber-600' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} text-sm font-semibold shadow-sm
                                       transition"
                                @if(!$canUpdateTask) disabled @endif>
                                Reprecificar venda
                            </button>

                            <button
                                type="button"
                                id="btn-notificar-cliente"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                                   bg-emerald-600 text-white text-sm font-semibold shadow-sm
                                   hover:bg-emerald-700 transition hidden">
                                Notificar cliente (WhatsApp)
                            </button>
                        </div>
                    </section>
                    {{-- 5. Documento final da tarefa --}}
                    <section id="modal-arquivo-wrapper"
                             class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 mt-3 hidden">
                        <h3 class="text-xs font-semibold text-emerald-700 mb-2">
                            5. DOCUMENTO FINAL DA TAREFA
                        </h3>

                        <p id="modal-arquivo-status" class="text-[12px] text-emerald-900 font-semibold mb-2">
                            Documento final ainda não anexado.
                        </p>

                        <p id="modal-arquivo-ajuda" class="text-[11px] text-emerald-800/90 mb-2">
                            Anexe o documento final concluído desta tarefa.
                        </p>

                        <a id="modal-arquivo-link"
                           href="#"
                           target="_blank"
                           class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-700
                                 hover:text-emerald-900 underline hidden">
                            📎 Visualizar documento final
                        </a>
                        <div
                            id="modal-arquivo-dropzone"
                            class="mt-3 rounded-2xl border-2 border-dashed border-emerald-300 bg-white/80 px-5 py-6 text-center transition cursor-pointer hover:bg-emerald-50"
                        >
                            <input
                                type="file"
                                id="modal-arquivo-replace-input"
                                class="hidden"
                                accept=".pdf,.jpg,.jpeg,.png"
                            >
                            <div class="flex flex-col items-center gap-2 text-emerald-700">
                                <span class="text-sm font-semibold" id="modal-arquivo-dropzone-title">Arraste o arquivo aqui</span>
                                <span class="text-[11px] text-emerald-800/90">ou clique para selecionar e anexar imediatamente</span>
                            </div>
                            <p id="modal-arquivo-impacto" class="text-[11px] text-emerald-700/90">
                                Disponivel para o cliente apos o envio.
                            </p>
                        </div>
                        <div id="modal-arquivo-complementar-wrapper" class="mt-4 hidden border-t border-emerald-100 pt-4">
                            <p id="modal-arquivo-complementar-status" class="text-[12px] text-emerald-900 font-semibold mb-2">
                                Status: Documento complementar ainda não anexado.
                            </p>
                            <a id="modal-arquivo-complementar-link"
                               href="#"
                               target="_blank"
                               class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-700 hover:text-emerald-900 underline hidden">
                                📎 Visualizar documento complementar
                            </a>
                            <div
                                id="modal-arquivo-complementar-dropzone"
                                class="mt-3 rounded-2xl border-2 border-dashed border-emerald-300 bg-white/80 px-5 py-6 text-center transition cursor-pointer hover:bg-emerald-50"
                            >
                                <input
                                    type="file"
                                    id="modal-arquivo-complementar-input"
                                    class="hidden"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                >
                                <div class="flex flex-col items-center gap-2 text-emerald-700">
                                    <span class="text-sm font-semibold" id="modal-arquivo-complementar-dropzone-title">Arraste o PCMSO aqui</span>
                                    <span class="text-[11px] text-emerald-800/90">ou clique para selecionar e anexar imediatamente</span>
                                </div>
                            </div>
                        </div>
                        <div id="modal-arquivo-art-wrapper" class="mt-4 hidden border-t border-emerald-100 pt-4">
                            <p id="modal-arquivo-art-status" class="text-[12px] text-emerald-900 font-semibold mb-2">
                                Status: ART ainda não anexada.
                            </p>
                            <a id="modal-arquivo-art-link"
                               href="#"
                               target="_blank"
                               class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-700 hover:text-emerald-900 underline hidden">
                                📎 Visualizar ART
                            </a>
                            <div
                                id="modal-arquivo-art-dropzone"
                                class="mt-3 rounded-2xl border-2 border-dashed border-emerald-300 bg-white/80 px-5 py-6 text-center transition cursor-pointer hover:bg-emerald-50"
                            >
                                <input
                                    type="file"
                                    id="modal-arquivo-art-input"
                                    class="hidden"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                >
                                <div class="flex flex-col items-center gap-2 text-emerald-700">
                                    <span class="text-sm font-semibold" id="modal-arquivo-art-dropzone-title">Arraste a ART aqui</span>
                                    <span class="text-[11px] text-emerald-800/90">ou clique para selecionar e anexar imediatamente</span>
                                </div>
                            </div>
                        </div>
                    </section>
                    {{-- 5b. Documentos da tarefa (ASO, PGR, PCMSO etc) --}}
                    <section id="modal-docs-wrapper"
                             class="bg-sky-50 border border-sky-200 rounded-xl p-4 mt-3 hidden">
                        <h3 class="text-xs font-semibold text-sky-700 mb-2">
                            DOCUMENTOS DA TAREFA
                        </h3>

                        <p class="text-[12px] text-sky-800/90 mb-2">
                            Lista de documentos anexados à tarefa (ASO, PGR, PCMSO, etc).
                        </p>

                        <ul id="modal-docs-list" class="space-y-1 text-sm">
                            {{-- preenchido via JavaScript --}}
                        </ul>
                    </section>

                    <section id="modal-certificados-wrapper"
                             class="bg-amber-50 border border-amber-100 rounded-xl p-4 mt-3 hidden">
                        <h3 class="text-xs font-semibold text-amber-700 mb-2">
                            CERTIFICADOS DE TREINAMENTO
                        </h3>
                        <p id="modal-certificados-status" class="text-[12px] text-amber-800 mb-2">—</p>
                        <div id="modal-certificados-dropzone"
                             class="mt-3 rounded-2xl border-2 border-dashed border-amber-300 bg-white/70 px-6 py-8 text-center transition cursor-pointer hover:bg-amber-50">
                            <input
                                type="file"
                                id="modal-certificados-input"
                                class="hidden"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                multiple
                            >
                            <div class="flex flex-col items-center justify-center gap-2 text-amber-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 16.5v1.25A2.25 2.25 0 0 0 6.25 20h11.5A2.25 2.25 0 0 0 20 17.75V16.5" />
                                </svg>
                                <div class="text-sm font-semibold" id="modal-certificados-dropzone-title">Arraste os certificados aqui</div>
                                <div class="text-xs text-amber-600/80">ou clique para selecionar varios arquivos e anexar imediatamente</div>
                            </div>
                        </div>
                        <ul id="modal-certificados-file-list" class="mt-3 space-y-1 text-xs text-amber-900 hidden"></ul>
                    </section>

                    {{-- 6. Adicionar observação interna --}}
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-4 h-full">
                        <h3 class="text-xs font-semibold text-slate-500 mb-3">
                            6. ADICIONAR OBSERVAÇÃO INTERNA
                        </h3>
                        <textarea
                            id="modal-observacao-interna"
                            rows="6"
                            class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2
                               focus:outline-none focus:ring-2 focus:ring-[color:var(--color-brand-azul)]/60
                               focus:border-[color:var(--color-brand-azul)] resize-none"
                            placeholder="Digite suas observações..."
                        ></textarea>

                        <button
                            type="button"
                            id="btn-salvar-observacao"
                            class="mt-3 w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                               bg-indigo-400 text-white text-sm font-semibold shadow-sm
                               hover:bg-indigo-500 transition">
                            Salvar Observação
                        </button>
                        @isset($usuario)
                            @if($usuario->hasPapel(['Master', 'Operacional']))
                                <button
                                    type="button"
                                    id="btn-excluir-tarefa"
                                    data-permission-locked="{{ $canDeleteTask ? '0' : '1' }}"
                                    @if(!$canDeleteTask) title="Usuário sem permissão" @endif
                                    class="mt-2 w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                                   border {{ $canDeleteTask ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' : 'border-slate-300 bg-slate-200 text-slate-500 cursor-not-allowed' }} text-sm font-semibold shadow-sm
                                   transition"
                                    @if(!$canDeleteTask) disabled @endif>
                                    Excluir Tarefa
                                </button>
                            @endif
                        @endisset
                    </section>


                </div>
            </div>

            <div id="modal-pendencia-wrapper"
                 class="absolute inset-0 z-30 hidden items-center justify-center bg-slate-950/45 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white px-8 py-7 shadow-2xl">
                    <div class="text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border-4 border-sky-400 text-4xl font-bold text-sky-400">
                            !
                        </div>
                        <h3 class="mt-6 text-2xl font-bold tracking-tight text-slate-600">
                            Alerta de pendencia
                        </h3>
                        <p id="modal-pendencia-texto" class="mt-5 text-[15px] font-medium leading-7 text-slate-600">
                            —
                        </p>
                    </div>
                    <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <button
                            type="button"
                            id="modal-pendencia-continuar-btn"
                            class="inline-flex min-w-[170px] items-center justify-center rounded-lg bg-indigo-500 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-600">
                            Continuar alterando
                        </button>
                        <button
                            type="button"
                            id="modal-pendencia-fechar-btn"
                            class="inline-flex min-w-[140px] items-center justify-center rounded-lg bg-slate-500 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-600">
                            Fechar tarefa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Exclusão da Tarefa --}}
    <div id="tarefa-excluir-modal"
         data-overlay-root="true"
         style="z-index: 1100;"
         class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/60 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 bg-red-600 text-white">
                <h3 class="text-sm font-semibold">Excluir Tarefa</h3>
                <button type="button" id="tarefa-excluir-close" class="text-white/90 hover:text-white">✕</button>
            </div>
            <div class="p-5 space-y-4 text-sm text-slate-700">
                <div class="text-xs text-slate-600 font-bold">
                    Informe o motivo da exclusão e, se necessário, anexe o print da conversa.
                </div>
                <div>
                    <label class="block text-[11px] text-slate-500 mb-1 font-bold">
                        Motivo da exclusão
                    </label>
                    <textarea id="tarefa-excluir-motivo"
                              rows="4"
                              class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2
                                     focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-300 resize-none"
                              placeholder="Descreva o motivo..."></textarea>
                </div>
                <div>
                    <label class="block text-[11px] text-slate-500 mb-1 font-bold">
                        Arquivo (print do WhatsApp)
                    </label>
                    <input id="tarefa-excluir-arquivo" type="file" accept=".pdf,.jpg,.jpeg,.png"
                           class="block w-full text-xs text-slate-600"/>
                </div>
            </div>
            <div class="px-5 py-4 bg-slate-50 flex items-center justify-end gap-2">
                <button type="button" id="tarefa-excluir-cancelar"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm">
                    Cancelar
                </button>
                <button type="button" id="tarefa-excluir-confirmar"
                        class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold">
                    Confirmar exclusão
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.flatpickr) {
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
                        const parsedDefaultDate = fp.parseDate(defaultDate, 'Y-m-d');
                        if (parsedDefaultDate) {
                            fp.setDate(parsedDefaultDate, false, 'Y-m-d');
                            textInput.value = fp.formatDate(parsedDefaultDate, 'd/m/Y');
                            if (hiddenInput) {
                                hiddenInput.value = fp.formatDate(parsedDefaultDate, 'Y-m-d');
                            }
                        }
                    } else {
                        textInput.value = '';
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
                            textInput.focus();
                            textInput._flatpickr.open();
                        }
                    });
                });
            }

            // =========================================================
            //  MODAL DE DETALHES DA TAREFA
            // =========================================================
            const modal = document.getElementById('tarefa-modal');
            const closeBtn = document.getElementById('tarefa-modal-close');
            const modalUploadLoading = document.getElementById('modal-upload-loading');
            const modalUploadLoadingTitle = document.getElementById('modal-upload-loading-title');
            const modalUploadLoadingText = document.getElementById('modal-upload-loading-text');

            const spanId = document.getElementById('modal-tarefa-id');
            const spanCliente = document.getElementById('modal-cliente');
            const spanCnpj = document.getElementById('modal-cnpj');

            const spanFuncionario = document.getElementById('modal-funcionario');
            const spanFuncionarioFuncao = document.getElementById('modal-funcionario-funcao');
            const spanFuncionarioCpf = document.getElementById('modal-funcionario-cpf');
            const spanFuncionarioRg = document.getElementById('modal-funcionario-rg');
            const spanFuncionarioCelular = document.getElementById('modal-funcionario-celular');
            const spanFuncionarioNascimento = document.getElementById('modal-funcionario-nascimento');
            const spanFuncionarioAdmissao = document.getElementById('modal-funcionario-admissao');
            const spanFuncionarioAtivo = document.getElementById('modal-funcionario-ativo');
            const spanAsoTipo = document.getElementById('modal-aso-tipo');
            const spanAsoData = document.getElementById('modal-aso-data');
            const spanAsoDataAdmissao = document.getElementById('modal-aso-data-admissao');
            const spanAsoDataDemissao = document.getElementById('modal-aso-data-demissao');
            const spanAsoUnidade = document.getElementById('modal-aso-unidade');
            const spanAsoTreinamento = document.getElementById('modal-aso-treinamento');
            const spanAsoTreinamentos = document.getElementById('modal-aso-treinamentos');
            const spanAsoPacote = document.getElementById('modal-aso-pacote');
            const spanAsoEmail = document.getElementById('modal-aso-email');
            const blocoFuncionario = document.getElementById('modal-bloco-funcionario');
            const blocoAso = document.getElementById('modal-bloco-aso');
            const blocoSolicitacao = document.getElementById('modal-bloco-solicitacao');
            const blocoToxicologico = document.getElementById('modal-bloco-toxicologico');
            const spanToxicologicoSolicitante = document.getElementById('modal-toxicologico-solicitante');
            const spanToxicologicoTipo = document.getElementById('modal-toxicologico-tipo');
            const spanToxicologicoNome = document.getElementById('modal-toxicologico-nome');
            const spanToxicologicoCpf = document.getElementById('modal-toxicologico-cpf');
            const spanToxicologicoRg = document.getElementById('modal-toxicologico-rg');
            const spanToxicologicoNascimento = document.getElementById('modal-toxicologico-nascimento');
            const spanToxicologicoTelefone = document.getElementById('modal-toxicologico-telefone');
            const spanToxicologicoEmail = document.getElementById('modal-toxicologico-email');
            const spanToxicologicoVendedor = document.getElementById('modal-toxicologico-vendedor');
            const spanToxicologicoData = document.getElementById('modal-toxicologico-data');
            const spanToxicologicoUnidade = document.getElementById('modal-toxicologico-unidade');

            const spanTelefone = document.getElementById('modal-telefone');
            const spanResp = document.getElementById('modal-responsavel');
            const spanClienteVendedor = document.getElementById('modal-cliente-vendedor');
            const spanServico = document.getElementById('modal-servico');
            const spanTipoServ = document.getElementById('modal-tipo-servico');
            const spanDataHora = document.getElementById('modal-datahora');
            const spanSla = document.getElementById('modal-sla');
            const spanTempoRestante = document.getElementById('modal-tempo-restante');
            const spanPrioridade = document.getElementById('modal-prioridade');
            const spanStatusText = document.getElementById('modal-status-text');
            const badgeStatus = document.getElementById('modal-status-badge');
            const exclusaoInfo = document.getElementById('modal-exclusao-info');
            const spanExcluidoPor = document.getElementById('modal-excluido-por');
            const spanMotivoExclusao = document.getElementById('modal-motivo-exclusao');
            const exclusaoAnexoWrapper = document.getElementById('modal-exclusao-anexo-wrapper');
            const exclusaoAnexoList = document.getElementById('modal-exclusao-anexo-list');
            const spanObs = document.getElementById('modal-observacoes');
            const documentoClienteWrapper = document.getElementById('modal-documento-cliente-wrapper');
            const documentoClienteLink = document.getElementById('modal-documento-cliente-link');
            const textareaObsInterna = document.getElementById('modal-observacao-interna');

            const docsWrapper = document.getElementById('modal-docs-wrapper');
            const docsList = document.getElementById('modal-docs-list');
            const certificadosWrapper = document.getElementById('modal-certificados-wrapper');
            const certificadosStatus = document.getElementById('modal-certificados-status');
            const certificadosInput = document.getElementById('modal-certificados-input');
            const certificadosDropzone = document.getElementById('modal-certificados-dropzone');
            const certificadosDropzoneTitle = document.getElementById('modal-certificados-dropzone-title');
            const certificadosFileList = document.getElementById('modal-certificados-file-list');
            const finalizarBtn = document.getElementById('modal-finalizar-btn');
            const reprecificarBtn = document.getElementById('modal-reprecificar-btn');
            const pendenciaWrapper = document.getElementById('modal-pendencia-wrapper');
            const pendenciaTexto = document.getElementById('modal-pendencia-texto');
            const pendenciaContinuarBtn = document.getElementById('modal-pendencia-continuar-btn');
            const pendenciaFecharBtn = document.getElementById('modal-pendencia-fechar-btn');


            // PGR
            const blocoPgr = document.getElementById('modal-bloco-pgr');
            const spanPgrTipo = document.getElementById('modal-pgr-tipo');
            const spanPgrArt = document.getElementById('modal-pgr-art');
            const spanPgrHomens = document.getElementById('modal-pgr-qtd-homens');
            const spanPgrMulheres = document.getElementById('modal-pgr-qtd-mulheres');
            const spanPgrTotal = document.getElementById('modal-pgr-total-trabalhadores');
            const spanPgrComPcmso = document.getElementById('modal-pgr-com-pcmso');
            const spanPgrContr = document.getElementById('modal-pgr-contratante');
            const spanPgrContrCnpj = document.getElementById('modal-pgr-contratante-cnpj');
            const spanPgrObraNome = document.getElementById('modal-pgr-obra-nome');
            const spanPgrObraEnd = document.getElementById('modal-pgr-obra-endereco');
            const spanPgrObraCej = document.getElementById('modal-pgr-obra-cej-cno');
            const spanPgrObraTurno = document.getElementById('modal-pgr-obra-turno');
            const ulPgrFuncoes = document.getElementById('modal-pgr-funcoes');

            // Treinamento NR
            const blocoTreinamento = document.getElementById('modal-bloco-treinamento');
            const spanTreinLocal = document.getElementById('modal-treinamento-local');
            const spanTreinUnidade = document.getElementById('modal-treinamento-unidade');
            const spanTreinPart = document.getElementById('modal-treinamento-participantes');
            const spanTreinModo = document.getElementById('modal-treinamento-modo');
            const spanTreinCodigos = document.getElementById('modal-treinamento-codigos');
            const spanTreinPacote = document.getElementById('modal-treinamento-pacote');
            const spanTreinFuncionario = document.getElementById('modal-treinamento-funcionario');
            const spanTreinCpf = document.getElementById('modal-treinamento-cpf');
            const spanTreinNascimento = document.getElementById('modal-treinamento-nascimento');
            const spanTreinAdmissao = document.getElementById('modal-treinamento-admissao');
            const spanTreinCelular = document.getElementById('modal-treinamento-celular');

            // Link do documento da tarefa (arquivo_cliente_path)
            const arquivoWrapper = document.getElementById('modal-arquivo-wrapper');
            const arquivoLink = document.getElementById('modal-arquivo-link');
            const btnNotificarCliente = document.getElementById('btn-notificar-cliente');
            const arquivoReplaceInput = document.getElementById('modal-arquivo-replace-input');
            const arquivoDropzone = document.getElementById('modal-arquivo-dropzone');
            const arquivoDropzoneTitle = document.getElementById('modal-arquivo-dropzone-title');
            const arquivoComplementarWrapper = document.getElementById('modal-arquivo-complementar-wrapper');
            const arquivoComplementarStatus = document.getElementById('modal-arquivo-complementar-status');
            const arquivoComplementarLink = document.getElementById('modal-arquivo-complementar-link');
            const arquivoComplementarInput = document.getElementById('modal-arquivo-complementar-input');
            const arquivoComplementarDropzone = document.getElementById('modal-arquivo-complementar-dropzone');
            const arquivoComplementarDropzoneTitle = document.getElementById('modal-arquivo-complementar-dropzone-title');
            const arquivoArtWrapper = document.getElementById('modal-arquivo-art-wrapper');
            const arquivoArtStatus = document.getElementById('modal-arquivo-art-status');
            const arquivoArtLink = document.getElementById('modal-arquivo-art-link');
            const arquivoArtInput = document.getElementById('modal-arquivo-art-input');
            const arquivoArtDropzone = document.getElementById('modal-arquivo-art-dropzone');
            const arquivoArtDropzoneTitle = document.getElementById('modal-arquivo-art-dropzone-title');
            const arquivoDescricao = document.getElementById('modal-arquivo-descricao');
            const arquivoStatus = document.getElementById('modal-arquivo-status');
            const arquivoAjuda = document.getElementById('modal-arquivo-ajuda');
            const arquivoImpacto = document.getElementById('modal-arquivo-impacto');
            let detalhesCurrentCard = null;
            let modalUploadLoadingCount = 0;

            function setModalUploadLoading(active, options = {}) {
                if (!modalUploadLoading) return;

                if (active) {
                    modalUploadLoadingCount += 1;

                    if (modalUploadLoadingTitle) {
                        modalUploadLoadingTitle.textContent = options.title || 'Enviando anexo';
                    }

                    if (modalUploadLoadingText) {
                        modalUploadLoadingText.textContent = options.text || 'Aguarde enquanto o arquivo e processado.';
                    }

                    modalUploadLoading.classList.remove('hidden');
                    modalUploadLoading.classList.add('flex');
                    return;
                }

                modalUploadLoadingCount = Math.max(0, modalUploadLoadingCount - 1);

                if (modalUploadLoadingCount === 0) {
                    modalUploadLoading.classList.add('hidden');
                    modalUploadLoading.classList.remove('flex');
                }
            }

            function formatTelefone(raw) {
                const digits = String(raw || '').replace(/\D/g, '');
                if (!digits) return '—';
                if (digits.length === 11) {
                    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
                }
                if (digits.length === 10) {
                    return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
                }
                return raw || '—';
            }

            function setDropzoneHighlight(element, active) {
                if (!element) return;
                element.classList.toggle('border-emerald-500', active);
                element.classList.toggle('bg-emerald-100/70', active);
            }

            function bindImmediateUploadDropzone(dropzone, input, uploadHandler) {
                if (!dropzone || !input) return;

                dropzone.addEventListener('click', function () {
                    if (dropzone.classList.contains('cursor-not-allowed')) return;
                    input.value = '';
                    input.click();
                });

                dropzone.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    if (dropzone.classList.contains('cursor-not-allowed')) return;
                    setDropzoneHighlight(dropzone, true);
                });

                dropzone.addEventListener('dragleave', function (event) {
                    event.preventDefault();
                    setDropzoneHighlight(dropzone, false);
                });

                dropzone.addEventListener('drop', function (event) {
                    event.preventDefault();
                    setDropzoneHighlight(dropzone, false);
                    if (dropzone.classList.contains('cursor-not-allowed')) return;

                    const file = event.dataTransfer?.files?.[0];
                    if (!file) return;
                    uploadHandler(file);
                });

                input.addEventListener('change', function () {
                    const file = input.files?.[0];
                    if (!file) return;
                    uploadHandler(file);
                });
            }

            function updateCardAnexosDataset(mutator) {
                if (!detalhesCurrentCard || typeof mutator !== 'function') return;

                let anexos = [];
                try {
                    anexos = detalhesCurrentCard.dataset.anexos
                        ? JSON.parse(detalhesCurrentCard.dataset.anexos)
                        : [];
                } catch (error) {
                    anexos = [];
                }

                const next = mutator(Array.isArray(anexos) ? anexos : []);
                detalhesCurrentCard.dataset.anexos = JSON.stringify(Array.isArray(next) ? next : anexos);
            }

            function upsertAnexoNaTarefa(anexo) {
                if (!anexo?.servico) return;

                const servico = String(anexo.servico).toLowerCase();
                updateCardAnexosDataset((anexos) => {
                    const filtrados = anexos.filter((item) => String(item?.servico || '').toLowerCase() !== servico);
                    filtrados.push(anexo);
                    return filtrados;
                });
            }

            function appendAnexosNaTarefa(anexosNovos) {
                const itens = Array.isArray(anexosNovos) ? anexosNovos.filter(Boolean) : [];
                if (!itens.length) return;

                updateCardAnexosDataset((anexos) => {
                    const existentes = Array.isArray(anexos) ? [...anexos] : [];
                    const ids = new Set(existentes.map((item) => String(item?.id || '')));

                    itens.forEach((item) => {
                        const id = String(item?.id || '');
                        if (id && ids.has(id)) return;
                        existentes.push(item);
                        if (id) ids.add(id);
                    });

                    return existentes;
                });
            }

            function collectWhatsappLinks(card, fallbackDocumentoUrl = '') {
                if (!card) return [];

                const links = [];
                const appendLink = (label, url) => {
                    const normalizedUrl = String(url || '').trim();
                    if (!normalizedUrl) return;
                    if (links.some((item) => item.url === normalizedUrl)) return;
                    links.push({ label, url: normalizedUrl });
                };

                const isPgrComPcmso = card.dataset.pgrPcmso === '1';
                const isAsoTask = card.dataset.isAso === '1';
                const isTreinamentoTask = card.dataset.isTreinamentoTask === '1';
                const documentoPrincipalUrl = fallbackDocumentoUrl || card.dataset.arquivoClienteUrl || '';

                if (documentoPrincipalUrl) {
                    appendLink(
                        isPgrComPcmso ? 'PGR' : (isAsoTask ? 'ASO' : 'Documento final'),
                        documentoPrincipalUrl
                    );
                }

                const pcmsoPgrEhArquivoDoCliente = String(card?.dataset?.pcmsoPgrOrigem || '').toLowerCase() === 'arquivo_cliente';

                if (card.dataset.pcmsoPgrUrl && !pcmsoPgrEhArquivoDoCliente) {
                    appendLink('PCMSO', card.dataset.pcmsoPgrUrl);
                }

                if (card.dataset.artPgrUrl) {
                    appendLink('ART', card.dataset.artPgrUrl);
                }

                let anexos = [];
                try {
                    anexos = card.dataset.anexos ? JSON.parse(card.dataset.anexos) : [];
                } catch (error) {
                    anexos = [];
                }

                anexos
                    .filter((anexo) => String(anexo?.servico || '').toLowerCase() === 'certificado_treinamento')
                    .filter((anexo) => !anexo?.uploaded_by_is_cliente)
                    .forEach((anexo, index) => appendLink(`Certificado ${index + 1}`, anexo?.url || ''));

                if (isTreinamentoTask && !links.length) {
                    anexos
                        .filter((anexo) => !anexo?.uploaded_by_is_cliente)
                        .filter((anexo) => String(anexo?.servico || '').toLowerCase() !== 'cancelamento_tarefa')
                        .forEach((anexo, index) => appendLink(`Documento ${index + 1}`, anexo?.url || ''));
                }

                return links;
            }

            function buildWhatsappMensagem(card, arquivoUrl) {
                const telefone = (card?.dataset?.telefone || '').replace(/\D/g, '');
                if (!telefone) return null;

                let servico = card?.dataset?.servico || 'documento';
                const funcionario = String(card?.dataset?.funcionario || '').split('|')[0].trim();
                const toxicologicoNome = String(card?.dataset?.toxicologicoNome || '').trim();

                if (card?.dataset?.isTreinamentoTask === '1') {
                    let participante = '';

                    try {
                        const participantes = card?.dataset?.treinamentoParticipantes
                            ? JSON.parse(card.dataset.treinamentoParticipantes)
                            : [];
                        participante = String(participantes?.[0] || '').trim();
                    } catch (error) {
                        participante = '';
                    }

                    const colaborador = funcionario || participante;
                    if (colaborador) {
                        servico = `Treinamentos NRs do colaborador ${colaborador}`;
                    }
                } else if (String(servico).toLowerCase().includes('toxicol') && toxicologicoNome) {
                    servico = `${servico} de ${toxicologicoNome}`;
                } else if (funcionario) {
                    servico = `${servico} do colaborador ${funcionario}`;
                }

                const links = collectWhatsappLinks(card, arquivoUrl);
                const bundleUrl = String(card?.dataset?.whatsappBundleUrl || '').trim();
                const possuiMultiplosDocumentos = links.length > 1;
                const labelsLinks = links.map((item) => String(item?.label || '').trim().toUpperCase()).filter(Boolean);
                const possuiPgr = labelsLinks.includes('PGR');
                const possuiPcmso = labelsLinks.includes('PCMSO');
                const possuiArt = labelsLinks.includes('ART');

                if (possuiPgr && possuiPcmso) {
                    servico = 'PGR e PCMSO';
                    if (possuiArt) {
                        servico = 'PGR, PCMSO e ART';
                    }
                }

                const linksTexto = links.length > 1 && bundleUrl
                    ? `\n\nBaixar todos os documentos:\n${bundleUrl}`
                    : (links.length
                        ? `\n\nLinks:\n${links.map((item) => `${item.label}: ${item.url}`).join('\n')}`
                        : '');
                const artigoServico = /^(PGR e PCMSO|PGR, PCMSO e ART)$/i.test(servico) ? 'de' : 'do';
                const introducao = possuiMultiplosDocumentos
                    ? `Olá! Seguem abaixo os documentos ${artigoServico} ${servico}.`
                    : `Olá! Segue abaixo o anexo ${artigoServico} ${servico}.`;
                const mensagem = `${introducao}\n\nEnviado pela Formed.${linksTexto ? `${linksTexto}` : ''}`;

                return { telefone, mensagem };
            }

            function getDocumentoFinalAjuda(card) {
                const servico = String(card?.dataset?.servico || '').toLowerCase();
                const isAsoTask = card?.dataset?.isAso === '1';

                if (isAsoTask) {
                    return 'Anexe o ASO final assinado/emitido para o cliente.';
                }
                if (card?.dataset?.pgrPcmso === '1') {
                    return card?.dataset?.pgrComArt === '1'
                        ? 'Anexe os documentos finais do PGR, do PCMSO e da ART entregues ao cliente.'
                        : 'Anexe os documentos finais do PGR e do PCMSO entregues ao cliente.';
                }
                if (servico.includes('pgr')) {
                    return 'Anexe o documento final do PGR entregue ao cliente.';
                }
                if (servico.includes('toxicol')) {
                    return 'Anexe o laudo final devolvido pelo laboratório para disponibilizar nos portais.';
                }
                if (servico.includes('treinamento')) {
                    return 'Anexe o documento final desta solicitação de treinamento entregue ao cliente.';
                }

                return 'Anexe o documento final concluído desta tarefa.';
            }

            function parseIsoToMs(iso) {
                if (!iso) return null;
                const ts = Date.parse(iso);
                return Number.isNaN(ts) ? null : ts;
            }

            function formatDuration(ms) {
                const total = Math.max(0, Math.floor(ms / 1000));
                const horas = Math.floor(total / 3600);
                const minutos = Math.floor((total % 3600) / 60);
                const segundos = total % 60;
                return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
            }

            function isCardFinalizada(card) {
                if (!card) return false;
                const finalizadoFlag = String(card.dataset.finalizado || '') === '1';
                const status = String(card.dataset.status || '').toLowerCase();
                const colunaSlug = String(card.closest('.kanban-column')?.dataset?.colunaSlug || '').toLowerCase();
                return finalizadoFlag || status.includes('finalizada') || colunaSlug === 'finalizada';
            }

            function updateTempoCard(card, nowMs) {
                if (!card) return;
                const fimIso = card.dataset.fimPrevisto || '';
                const fimMs = parseIsoToMs(fimIso);
                const label = card.querySelector('[data-role="card-tempo-label"]');
                const textEl = card.querySelector('[data-role="card-tempo-text"]');
                if (!label || !textEl) return;

                if (isCardFinalizada(card)) {
                    textEl.textContent = 'Finalizada';
                    label.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-600', 'border-rose-200', 'bg-rose-50', 'text-rose-700');
                    label.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
                    return;
                }

                label.classList.remove('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
                if (!fimMs) {
                    textEl.textContent = '—';
                    return;
                }

                const diff = fimMs - nowMs;
                if (diff >= 0) {
                    textEl.textContent = formatDuration(diff);
                    label.classList.remove('border-rose-200', 'bg-rose-50', 'text-rose-700');
                    label.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
                } else {
                    textEl.textContent = `Atrasado ${formatDuration(Math.abs(diff))}`;
                    label.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-600');
                    label.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-700');
                }
            }

            function updateModalTempo(card, nowMs) {
                if (!spanTempoRestante || !card) return;
                if (isCardFinalizada(card)) {
                    spanTempoRestante.textContent = 'Finalizada';
                    return;
                }
                const fimIso = card.dataset.fimPrevisto || '';
                const fimMs = parseIsoToMs(fimIso);
                if (!fimMs) {
                    spanTempoRestante.textContent = '—';
                    return;
                }
                const diff = fimMs - nowMs;
                if (diff >= 0) {
                    spanTempoRestante.textContent = formatDuration(diff);
                } else {
                    spanTempoRestante.textContent = `Atrasado ${formatDuration(Math.abs(diff))}`;
                }
            }


            function openDetalhesModal(card) {
                if (!card) return;
                detalhesCurrentCard = card;
                hidePendenciaInline();
                renderCertificadosSelecionados([]);
                if (certificadosInput) {
                    certificadosInput.value = '';
                }

                const isCancelada = card.dataset.cancelada === '1';
                modal.dataset.cancelada = isCancelada ? '1' : '0';
                // Campos básicos
                spanId.textContent = card.dataset.id ?? '';
                spanCliente.textContent = card.dataset.cliente ?? '';
                spanCnpj.textContent = card.dataset.cnpj || '—';
                spanTelefone.textContent = card.dataset.telefone || '—';
                spanResp.textContent = card.dataset.responsavel ?? '';
                if (spanClienteVendedor) spanClienteVendedor.textContent = card.dataset.clienteVendedor || '—';
                if (blocoSolicitacao) {
                    const clienteTipoPessoa = String(card.dataset.clienteTipoPessoa || '').toUpperCase();
                    blocoSolicitacao.classList.toggle('hidden', clienteTipoPessoa === 'PF');
                }
                const servicoOriginal = card.dataset.servico ?? '';
                const isPgrComPcmso = servicoOriginal === 'PGR' && card.dataset.pgrComPcmso === '1';
                const servicoModal = isPgrComPcmso ? 'PGR + PCMSO' : servicoOriginal;
                const tipoServicoModal = isPgrComPcmso ? 'PCMSO' : servicoOriginal;
                spanServico.textContent = servicoModal;
                spanTipoServ.textContent = tipoServicoModal;
                spanDataHora.textContent = card.dataset.datahora ?? '';
                spanSla.textContent = card.dataset.sla ?? '-';
                spanPrioridade.textContent = card.dataset.prioridade ?? '';
                spanStatusText.textContent = card.dataset.status ?? '';
                let observacoesModal = card.dataset.observacoes ?? '';
                spanObs.textContent = observacoesModal;
                if (documentoClienteWrapper && documentoClienteLink) {
                    documentoClienteWrapper.classList.add('hidden');
                    documentoClienteLink.href = '#';
                    documentoClienteLink.textContent = 'Ver documento';

                    if (card.dataset.servico === 'ASO') {
                        const documentoClienteUrl = String(card.dataset.asoPcmsoExternoUrl || '').trim();
                        const documentoClienteNome = String(card.dataset.asoPcmsoExternoNome || '').trim();

                        if (documentoClienteUrl) {
                            documentoClienteLink.href = documentoClienteUrl;
                            documentoClienteLink.textContent = documentoClienteNome
                                ? documentoClienteNome
                                : 'Documento enviado pelo cliente';
                            documentoClienteWrapper.classList.remove('hidden');
                        }
                    }
                }
                updateModalTempo(card, Date.now());

                spanFuncionario.textContent = card.dataset.funcionario || '—';
                spanFuncionarioFuncao.textContent = card.dataset.funcionarioFuncao || '—';
                if (spanFuncionarioCpf) {
                    spanFuncionarioCpf.textContent = card.dataset.funcionarioCpf || '—';
                }
                if (spanFuncionarioRg) {
                    spanFuncionarioRg.textContent = card.dataset.funcionarioRg || '—';
                }
                if (spanFuncionarioNascimento) {
                    spanFuncionarioNascimento.textContent = card.dataset.funcionarioNascimento || '—';
                }
                if (spanFuncionarioAdmissao) {
                    spanFuncionarioAdmissao.textContent = card.dataset.funcionarioAdmissao || '—';
                }
                if (spanFuncionarioAtivo) {
                    spanFuncionarioAtivo.textContent = card.dataset.funcionarioAtivo || '—';
                }
                if (spanFuncionarioCelular) {
                    spanFuncionarioCelular.textContent = formatTelefone(card.dataset.funcionarioCelular || '');
                }

                if (textareaObsInterna) {
                    textareaObsInterna.value = card.dataset.observacaoInterna || '';
                }
                modal.dataset.observacaoUrl = card.dataset.observacaoUrl || '';

                // Documento final da tarefa
                if (arquivoWrapper && arquivoLink) {
                    const urlArquivo = card.dataset.arquivoClienteUrl || '';
                    const isAsoTask = card.dataset.isAso === '1';
                    const isTreinamentoTask = card.dataset.isTreinamentoTask === '1';
                    const totalCertificados = Number(card.dataset.certificadosTotal || '0');
                    const enviadosCertificados = Number(card.dataset.certificadosEnviados || '0');
                    const pendentesCertificados = card.dataset.certificadosPendentes === '1';
                    const temDocumentoFinal = !!urlArquivo;
                    const isPgrComPcmso = card.dataset.pgrPcmso === '1';
                    const requerArt = card.dataset.pgrComArt === '1';
                    const urlComplementar = card.dataset.pcmsoPgrUrl || '';
                    const temDocumentoComplementar = !!urlComplementar;
                    const urlArt = card.dataset.artPgrUrl || '';
                    const temDocumentoArt = !!urlArt;

                    if (isTreinamentoTask && !isAsoTask) {
                        arquivoWrapper.classList.add('hidden');
                    } else {
                        arquivoWrapper.classList.remove('hidden');

                        if (arquivoDescricao) {
                            arquivoDescricao.textContent = isPgrComPcmso
                                ? (requerArt
                                    ? 'Anexe os arquivos finais do PGR, do PCMSO e da ART entregues ao cliente.'
                                    : 'Anexe os arquivos finais do PGR e do PCMSO entregues ao cliente.')
                                : 'Documento principal da tarefa.';
                        }
                        if (arquivoAjuda) {
                            arquivoAjuda.textContent = getDocumentoFinalAjuda(card);
                        }

                        if (temDocumentoFinal) {
                            arquivoLink.href = urlArquivo;
                            arquivoLink.classList.remove('hidden');

                            if (arquivoStatus) {
                                arquivoStatus.textContent = isPgrComPcmso
                                    ? 'PGR: Documento anexado.'
                                    : 'Documento final anexado.';
                            }
                            if (arquivoDropzoneTitle) {
                                arquivoDropzoneTitle.textContent = isPgrComPcmso ? 'Arraste um novo PGR aqui' : 'Arraste uma nova versão aqui';
                            }
                            if (arquivoDropzone) {
                                arquivoDropzone.title = isPgrComPcmso
                                    ? 'Substitui o documento final do PGR'
                                    : 'Substitui o documento final atual da tarefa';
                            }
                            if (arquivoImpacto) {
                                arquivoImpacto.textContent = isPgrComPcmso
                                    ? 'O arquivo do PGR ficará disponível para o cliente.'
                                    : 'A nova versão substituirá o documento atual da tarefa.';
                            }
                        } else {
                            arquivoLink.href = '#';
                            arquivoLink.classList.add('hidden');

                            if (arquivoStatus) {
                                arquivoStatus.textContent = isPgrComPcmso
                                    ? 'PGR: Documento ainda não anexado.'
                                    : 'Documento final ainda não anexado.';
                            }
                            if (arquivoDropzoneTitle) {
                                arquivoDropzoneTitle.textContent = isPgrComPcmso ? 'Arraste o PGR aqui' : 'Arraste o documento aqui';
                            }
                            if (arquivoDropzone) {
                                arquivoDropzone.title = isPgrComPcmso
                                    ? 'Anexar o documento final do PGR'
                                    : 'Anexar o documento final da tarefa';
                            }
                            if (arquivoImpacto) {
                                arquivoImpacto.textContent = isPgrComPcmso
                                    ? ''
                                    : 'Disponivel para o cliente apos o envio.';
                            }
                        }

                        if (arquivoComplementarWrapper && arquivoComplementarStatus && arquivoComplementarLink) {
                            if (isPgrComPcmso) {
                                arquivoComplementarWrapper.classList.remove('hidden');

                                if (temDocumentoComplementar) {
                                    arquivoComplementarLink.href = urlComplementar;
                                    arquivoComplementarLink.classList.remove('hidden');
                                    arquivoComplementarStatus.textContent = 'PCMSO: Documento anexado.';
                                    if (arquivoComplementarDropzoneTitle) {
                                        arquivoComplementarDropzoneTitle.textContent = 'Arraste um novo PCMSO aqui';
                                    }
                                    if (arquivoComplementarDropzone) {
                                        arquivoComplementarDropzone.title = 'Substitui o documento final do PCMSO';
                                    }
                                } else {
                                    arquivoComplementarLink.href = '#';
                                    arquivoComplementarLink.classList.add('hidden');
                                    arquivoComplementarStatus.textContent = 'PCMSO: Documento ainda não anexado.';
                                    if (arquivoComplementarDropzoneTitle) {
                                        arquivoComplementarDropzoneTitle.textContent = 'Arraste o PCMSO aqui';
                                    }
                                    if (arquivoComplementarDropzone) {
                                        arquivoComplementarDropzone.title = 'Anexar o documento final do PCMSO';
                                    }
                                }
                            } else {
                                arquivoComplementarWrapper.classList.add('hidden');
                                arquivoComplementarLink.href = '#';
                                arquivoComplementarLink.classList.add('hidden');
                                arquivoComplementarStatus.textContent = 'Status: Documento complementar ainda não anexado.';
                            }
                        }

                        if (arquivoArtWrapper && arquivoArtStatus && arquivoArtLink) {
                            if (isPgrComPcmso && requerArt) {
                                arquivoArtWrapper.classList.remove('hidden');

                                if (temDocumentoArt) {
                                    arquivoArtLink.href = urlArt;
                                    arquivoArtLink.classList.remove('hidden');
                                    arquivoArtStatus.textContent = 'ART: Documento anexado.';
                                    if (arquivoArtDropzoneTitle) {
                                        arquivoArtDropzoneTitle.textContent = 'Arraste uma nova ART aqui';
                                    }
                                    if (arquivoArtDropzone) {
                                        arquivoArtDropzone.title = 'Substitui o documento final da ART';
                                    }
                                } else {
                                    arquivoArtLink.href = '#';
                                    arquivoArtLink.classList.add('hidden');
                                    arquivoArtStatus.textContent = 'ART: Documento ainda não anexado.';
                                    if (arquivoArtDropzoneTitle) {
                                        arquivoArtDropzoneTitle.textContent = 'Arraste a ART aqui';
                                    }
                                    if (arquivoArtDropzone) {
                                        arquivoArtDropzone.title = 'Anexar o documento final da ART';
                                    }
                                }
                            } else {
                                arquivoArtWrapper.classList.add('hidden');
                                arquivoArtLink.href = '#';
                                arquivoArtLink.classList.add('hidden');
                                arquivoArtStatus.textContent = 'Status: ART ainda não anexada.';
                            }
                        }

                        if (arquivoStatus && isAsoTask && totalCertificados > 0 && temDocumentoFinal) {
                            arquivoStatus.textContent += pendentesCertificados
                                ? ` Certificados pendentes: ${enviadosCertificados}/${totalCertificados}.`
                                : ` Certificados concluídos: ${enviadosCertificados}/${totalCertificados}.`;
                        }
                    }

                    if (btnNotificarCliente) {
                        const podeNotificar = collectWhatsappLinks(card).length > 0;
                        btnNotificarCliente.classList.toggle('hidden', !podeNotificar);
                    }
                }
                // ===============================
                // LISTA DE ANEXOS DA TAREFA
                // ===============================
                let anexos = [];
                try {
                    anexos = card.dataset.anexos ? JSON.parse(card.dataset.anexos) : [];
                } catch (e) {
                    anexos = [];
                }
                const anexosOriginais = Array.isArray(anexos) ? [...anexos] : [];
                if (docsWrapper && docsList) {
                    // limpa lista anterior
                    docsList.innerHTML = '';
                    const anexosExibicao = [...anexosOriginais];
                    const pcmsoPgrEhArquivoDoCliente = String(card?.dataset?.pcmsoPgrOrigem || '').toLowerCase() === 'arquivo_cliente';

                    // 1) Documento final da tarefa (path_documento_cliente)
                    if (card.dataset.arquivoClienteUrl) {
                        anexosExibicao.push({
                            id: `principal-${card.dataset.id || 'tarefa'}`,
                            kind: 'documento_principal',
                            label: isPgrComPcmso ? 'Documento final - PGR' : 'Documento final da tarefa',
                            url: card.dataset.arquivoClienteUrl,
                            delete_url: card.dataset.removerDocumentoClienteUrl || ''
                        });
                    }

                    // 2) PGR anexado ao PCMSO (se existir)
                    const anexoComplementarPgrPcmso = anexosOriginais.find((a) =>
                        String(a?.servico || '').toLowerCase() === 'documento_complementar_pgr_pcmso'
                    );

                    if (card.dataset.pcmsoPgrUrl && !pcmsoPgrEhArquivoDoCliente) {
                        anexosExibicao.push({
                            id: anexoComplementarPgrPcmso?.id || `complementar-${card.dataset.id || 'tarefa'}`,
                            kind: pcmsoPgrEhArquivoDoCliente ? 'documento_cliente_referencia' : 'documento_complementar',
                            label: isPgrComPcmso
                                ? 'Documento final - PCMSO'
                                : 'Documento PGR anexado pelo cliente',
                            url: card.dataset.pcmsoPgrUrl,
                            delete_url: pcmsoPgrEhArquivoDoCliente
                                ? ''
                                : (card.dataset.pcmsoPgrDeleteUrl || anexoComplementarPgrPcmso?.delete_url || '')
                        });
                    }

                    const anexoArtPgrPcmso = anexosOriginais.find((a) =>
                        String(a?.servico || '').toLowerCase() === 'documento_art_pgr_pcmso'
                    );

                    if (card.dataset.artPgrUrl) {
                        anexosExibicao.push({
                            id: anexoArtPgrPcmso?.id || `art-${card.dataset.id || 'tarefa'}`,
                            kind: 'documento_art',
                            label: 'Documento final - ART',
                            url: card.dataset.artPgrUrl,
                            delete_url: card.dataset.artPgrDeleteUrl || anexoArtPgrPcmso?.delete_url || ''
                        });
                    }

                    // 👉 Aqui no futuro você pode ir plugando mais anexos:
                    // if (card.dataset.algumaOutraCoisaUrl) { ... }

                    const mapAnexoLabel = (anexo) => {
                        if (anexo && anexo.label) return anexo.label;
                        if (anexo && String(anexo.servico || '').toLowerCase() === 'certificado_treinamento') {
                            return 'Certificado de treinamento';
                        }
                        if (anexo && anexo.servico === 'cancelamento_tarefa') {
                            return 'Print do cancelamento';
                        }
                        return 'Documento';
                    };

                    const anexosDocs = anexosExibicao.filter((a) => {
                        const servico = String(a?.servico || '').toLowerCase();
                        if (a?.uploaded_by_is_cliente) {
                            return false;
                        }

                        return servico !== 'cancelamento_tarefa'
                            && servico !== 'documento_complementar_pgr_pcmso'
                            && servico !== 'documento_art_pgr_pcmso';
                    });

                    if (anexosDocs.length) {
                        docsWrapper.classList.remove('hidden');
                        docsList.innerHTML = anexosDocs.map(a => `
                                <li>
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <a href="${a.url}" target="_blank" class="underline text-[13px] font-medium">
                                                ${mapAnexoLabel(a)}
                                            </a>
                                            ${(a.tamanho || a.mime || a.data || a.uploaded_by) ? `
                                                <span class="text-[11px] text-slate-500">
                                                    (${[
                                                        a.tamanho || null,
                                                        a.mime || null,
                                                        a.data || null,
                                                        a.uploaded_by || null,
                                                    ].filter(Boolean).join(' · ')})
                                                </span>
                                            ` : ''}
                                        </div>
                                        ${a.delete_url ? `
                                            <button
                                                type="button"
                                                class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-100"
                                                data-doc-delete-url="${a.delete_url}"
                                                data-doc-id="${a.id || ''}"
                                                data-doc-kind="${a.kind || 'anexo'}"
                                                data-doc-service="${a.servico || ''}">
                                                Excluir
                                            </button>
                                        ` : ''}
                                    </div>
                                </li>
                            `).join('');
                    } else {
                        docsWrapper.classList.add('hidden');
                        docsList.innerHTML = '';
                    }
                }

                if (certificadosWrapper && certificadosStatus) {
                    const total = Number(card.dataset.certificadosTotal || '0');
                    const enviados = Number(card.dataset.certificadosEnviados || '0');
                    const pendentes = card.dataset.certificadosPendentes === '1';
                    const isAso = card.dataset.isAso === '1';
                    const isTreinamentoTask = card.dataset.isTreinamentoTask === '1';

                    if ((isAso || isTreinamentoTask) && total > 0) {
                        certificadosWrapper.classList.remove('hidden');
                        if (certificadosDropzone) {
                            certificadosDropzone.classList.remove('opacity-60', 'cursor-not-allowed');
                        }

                        certificadosStatus.textContent = pendentes
                            ? `Aguardando certificados: ${enviados}/${total}.`
                            : `Certificados concluídos: ${enviados}/${total}.`;
                        if (certificadosDropzoneTitle) {
                            certificadosDropzoneTitle.textContent = pendentes
                                ? 'Arraste os certificados que faltam aqui'
                                : 'Arraste mais certificados aqui';
                        }
                    } else {
                        certificadosWrapper.classList.add('hidden');
                        certificadosStatus.textContent = '—';
                        if (certificadosDropzoneTitle) {
                            certificadosDropzoneTitle.textContent = 'Arraste os certificados aqui';
                        }
                        if (certificadosDropzone) {
                            certificadosDropzone.classList.remove('opacity-60', 'cursor-not-allowed');
                        }
                    }
                }

                if (finalizarBtn) {
                    const temDocumentoFinal = !!card.dataset.arquivoClienteUrl;
                    const isTreinamentoTask = card.dataset.isTreinamentoTask === '1';
                    const precisaDocumentoComplementar = card.dataset.pgrPcmso === '1';
                    const precisaArt = card.dataset.pgrComArt === '1';
                    const temDocumentoComplementar = !!card.dataset.pcmsoPgrUrl;
                    const temDocumentoArt = !!card.dataset.artPgrUrl;
                    const podeFinalizar = isTreinamentoTask
                        ? true
                        : temDocumentoFinal
                            && (!precisaDocumentoComplementar || temDocumentoComplementar)
                            && (!precisaArt || temDocumentoArt);

                    finalizarBtn.disabled = !podeFinalizar;
                    finalizarBtn.classList.toggle('opacity-60', !podeFinalizar);
                    finalizarBtn.classList.toggle('cursor-not-allowed', !podeFinalizar);
                }

                if (exclusaoAnexoWrapper && exclusaoAnexoList) {
                    const anexosCancelamento = anexosOriginais.filter(a => a && a.servico === 'cancelamento_tarefa');
                    if (anexosCancelamento.length) {
                        exclusaoAnexoWrapper.classList.remove('hidden');
                        exclusaoAnexoList.innerHTML = anexosCancelamento.map(a => `
                            <li>
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <a href="${a.url}" target="_blank" class="underline text-[13px] font-medium">
                                            ${a.nome || 'Print do cancelamento'}
                                        </a>
                                        <span class="text-[11px] text-slate-500">
                                            (${a.tamanho || '-'} · ${a.mime || '-'}${a.data ? ' · ' + a.data : ''}${a.uploaded_by ? ' · ' + a.uploaded_by : ''})
                                        </span>
                                    </div>
                                    ${a.delete_url ? `
                                        <button
                                            type="button"
                                            class="rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-100"
                                            data-doc-delete-url="${a.delete_url}"
                                            data-doc-id="${a.id || ''}"
                                            data-doc-kind="anexo">
                                            Excluir
                                        </button>
                                    ` : ''}
                                </div>
                            </li>
                        `).join('');
                    } else {
                        exclusaoAnexoWrapper.classList.add('hidden');
                        exclusaoAnexoList.innerHTML = '';
                    }
                }

                // === REGRAS POR TIPO DE SERVIÇO ===
                const tipoServico = (card.dataset.servico || '').toLowerCase();
                const isAso = card.dataset.isAso === '1';
                const isPgr = tipoServico.includes('pgr');
                const isToxicologico = tipoServico.includes('toxicol');
                const temFuncionario = Boolean(
                    (card.dataset.funcionario || '').trim()
                    || (card.dataset.funcionarioCpf || '').trim()
                    || (card.dataset.funcionarioFuncao || '').trim()
                    || (card.dataset.funcionarioNascimento || '').trim()
                    || (card.dataset.funcionarioAdmissao || '').trim()
                );

                if (blocoFuncionario) {
                    blocoFuncionario.classList.toggle('hidden', !temFuncionario);
                }

                // ASO
                if (blocoAso) {
                    if (isAso) {
                        blocoAso.classList.remove('hidden');
                        if (spanAsoTipo) {
                            spanAsoTipo.textContent = card.dataset.asoTipo || '—';
                        }
                        if (spanAsoData) {
                            spanAsoData.textContent = card.dataset.asoData || '—';
                        }
                        if (spanAsoDataAdmissao) {
                            spanAsoDataAdmissao.textContent = card.dataset.asoDataAdmissao || '—';
                        }
                        if (spanAsoDataDemissao) {
                            spanAsoDataDemissao.textContent = card.dataset.asoDataDemissao || '—';
                        }
                        if (spanAsoUnidade) {
                            spanAsoUnidade.textContent = card.dataset.asoUnidade || '—';
                        }
                        if (spanAsoTreinamento) {
                            spanAsoTreinamento.textContent = card.dataset.asoTreinamento || '—';
                        }
                        if (spanAsoTreinamentos) {
                            spanAsoTreinamentos.textContent = card.dataset.asoTreinamentos || '—';
                        }
                        if (spanAsoPacote) {
                            spanAsoPacote.textContent = card.dataset.asoPacote || '—';
                        }
                        if (spanAsoEmail) {
                            spanAsoEmail.textContent = card.dataset.asoEmail || '—';
                        }
                    } else {
                        blocoAso.classList.add('hidden');
                        if (spanAsoTipo) spanAsoTipo.textContent = '—';
                        if (spanAsoData) spanAsoData.textContent = '—';
                        if (spanAsoDataAdmissao) spanAsoDataAdmissao.textContent = '—';
                        if (spanAsoDataDemissao) spanAsoDataDemissao.textContent = '—';
                        if (spanAsoUnidade) spanAsoUnidade.textContent = '—';
                        if (spanAsoTreinamento) spanAsoTreinamento.textContent = '—';
                        if (spanAsoTreinamentos) spanAsoTreinamentos.textContent = '—';
                        if (spanAsoEmail) spanAsoEmail.textContent = '—';
                    }
                }

                if (blocoToxicologico) {
                    if (isToxicologico) {
                        blocoToxicologico.classList.remove('hidden');
                        if (spanToxicologicoSolicitante) spanToxicologicoSolicitante.textContent = card.dataset.toxicologicoSolicitante || '—';
                        if (spanToxicologicoTipo) spanToxicologicoTipo.textContent = card.dataset.toxicologicoTipo || '—';
                        if (spanToxicologicoNome) spanToxicologicoNome.textContent = card.dataset.toxicologicoNome || '—';
                        if (spanToxicologicoCpf) spanToxicologicoCpf.textContent = card.dataset.toxicologicoCpf || '—';
                        if (spanToxicologicoRg) spanToxicologicoRg.textContent = card.dataset.toxicologicoRg || '—';
                        if (spanToxicologicoNascimento) spanToxicologicoNascimento.textContent = card.dataset.toxicologicoNascimento || '—';
                        if (spanToxicologicoTelefone) spanToxicologicoTelefone.textContent = formatTelefone(card.dataset.toxicologicoTelefone || '');
                        if (spanToxicologicoEmail) spanToxicologicoEmail.textContent = card.dataset.toxicologicoEmail || '—';
                        if (spanToxicologicoVendedor) spanToxicologicoVendedor.textContent = card.dataset.clienteVendedor || '—';
                        if (spanToxicologicoData) spanToxicologicoData.textContent = card.dataset.toxicologicoData || '—';
                        if (spanToxicologicoUnidade) spanToxicologicoUnidade.textContent = card.dataset.toxicologicoUnidade || '—';
                    } else {
                        blocoToxicologico.classList.add('hidden');
                        if (spanToxicologicoSolicitante) spanToxicologicoSolicitante.textContent = '—';
                        if (spanToxicologicoTipo) spanToxicologicoTipo.textContent = '—';
                        if (spanToxicologicoNome) spanToxicologicoNome.textContent = '—';
                        if (spanToxicologicoCpf) spanToxicologicoCpf.textContent = '—';
                        if (spanToxicologicoRg) spanToxicologicoRg.textContent = '—';
                        if (spanToxicologicoNascimento) spanToxicologicoNascimento.textContent = '—';
                        if (spanToxicologicoTelefone) spanToxicologicoTelefone.textContent = '—';
                        if (spanToxicologicoEmail) spanToxicologicoEmail.textContent = '—';
                        if (spanToxicologicoVendedor) spanToxicologicoVendedor.textContent = '—';
                        if (spanToxicologicoData) spanToxicologicoData.textContent = '—';
                        if (spanToxicologicoUnidade) spanToxicologicoUnidade.textContent = '—';
                    }
                }


                // PGR
                if (blocoPgr) {
                    if (isPgr) {
                        blocoPgr.classList.remove('hidden');

                        spanPgrTipo.textContent = card.dataset.pgrTipo || '—';

                        spanPgrArt.textContent = card.dataset.pgrComArt === '1'
                            ? 'Com ART'
                            : (card.dataset.pgrComArt === '0' ? 'Sem ART' : '—');

                        spanPgrHomens.textContent = card.dataset.pgrQtdHomens || '0';
                        spanPgrMulheres.textContent = card.dataset.pgrQtdMulheres || '0';
                        spanPgrTotal.textContent = card.dataset.pgrTotalTrabalhadores || '0';

                        spanPgrComPcmso.textContent = card.dataset.pgrComPcmso === '1'
                            ? 'Sim, PCMSO + PGR'
                            : (card.dataset.pgrComPcmso === '0' ? 'Não, apenas PGR' : '—');

                        spanPgrContr.textContent = card.dataset.pgrContratante || '—';
                        spanPgrContrCnpj.textContent = card.dataset.pgrContratanteCnpj || '—';

                        spanPgrObraNome.textContent = card.dataset.pgrObraNome || '—';
                        spanPgrObraEnd.textContent = card.dataset.pgrObraEndereco || '—';
                        spanPgrObraCej.textContent = card.dataset.pgrObraCejCno || '—';
                        spanPgrObraTurno.textContent = card.dataset.pgrObraTurno || '—';

                        if (ulPgrFuncoes) {
                            let funcoesJson = [];
                            try {
                                funcoesJson = card.dataset.pgrFuncoesJson
                                    ? JSON.parse(card.dataset.pgrFuncoesJson)
                                    : [];
                            } catch (e) {
                                funcoesJson = [];
                            }

                            if (Array.isArray(funcoesJson) && funcoesJson.length) {
                                const html = funcoesJson.map(item => {
                                    const nome = item.nome || 'Funcao';
                                    const qtd = typeof item.quantidade === 'number' ? item.quantidade : 0;
                                    const nrs = [];
                                    if (item.nr_altura) nrs.push('NR-35');
                                    if (item.nr_eletricidade) nrs.push('NR-10');
                                    if (item.nr_espaco_confinado) nrs.push('NR-33');
                                    const nrsHtml = nrs.length
                                        ? `<span class="ml-2 text-[11px] text-slate-500">${nrs.join(' · ')}</span>`
                                        : `<span class="ml-2 text-[11px] text-slate-400">Sem NRs</span>`;
                                    const content = `<span class="font-medium">${nome} (${qtd})</span>${nrsHtml}`;
                                    return ulPgrFuncoes.tagName === 'UL' ? `<li>${content}</li>` : `<div>${content}</div>`;
                                }).join('');
                                ulPgrFuncoes.innerHTML = html;
                            } else {
                                ulPgrFuncoes.textContent = card.dataset.pgrFuncoes || '—';
                            }
                        }
                    } else {
                        blocoPgr.classList.add('hidden');
                    }
                }

                // limpa blocos especiais
                ['apr', 'ltcat', 'ltip', 'pae', 'pcmso'].forEach(slug => {
                    const el = document.getElementById(`modal-bloco-${slug}`);
                    if (el) el.classList.add('hidden');
                });

                // APR
                if (card.dataset.servico === 'APR') {
                    document.getElementById('modal-apr-obra-nome').textContent =
                        card.dataset.aprObraNome || '—';

                    document.getElementById('modal-apr-obra-endereco').textContent =
                        card.dataset.aprObraEndereco || '—';

                    const dataIni = card.dataset.aprDataInicio || '—';
                    const dataFim = card.dataset.aprDataFim || '—';
                    document.getElementById('modal-apr-periodo').textContent = `${dataIni} a ${dataFim}`;

                    document.getElementById('modal-apr-endereco').textContent =
                        card.dataset.aprEndereco || '—';

                    const preencherListaApr = (elementId, valor, separadores) => {
                        const ul = document.getElementById(elementId);
                        if (!ul) return;

                        const regex = new RegExp(separadores, 'g');
                        const itens = String(valor || '')
                            .split(regex)
                            .map((txt) => txt.trim())
                            .filter(Boolean);

                        ul.innerHTML = '';
                        if (!itens.length) {
                            const li = document.createElement('li');
                            li.textContent = '—';
                            ul.appendChild(li);
                            return;
                        }

                        itens.forEach((item) => {
                            const li = document.createElement('li');
                            li.textContent = item;
                            ul.appendChild(li);
                        });
                    };

                    const preencherListaAprEpis = (elementId, jsonRaw) => {
                        const ul = document.getElementById(elementId);
                        if (!ul) return;

                        let itens = [];
                        try {
                            const parsed = JSON.parse(String(jsonRaw || '[]'));
                            if (Array.isArray(parsed)) {
                                itens = parsed
                                    .map((item) => {
                                        const tipo = (item?.tipo === 'maquina') ? 'Máquina' : 'EPI';
                                        const descricao = String(item?.descricao || '').trim();
                                        return descricao ? `${tipo}: ${descricao}` : '';
                                    })
                                    .filter(Boolean);
                            }
                        } catch (e) {
                            itens = [];
                        }

                        ul.innerHTML = '';
                        if (!itens.length) {
                            const li = document.createElement('li');
                            li.textContent = '—';
                            ul.appendChild(li);
                            return;
                        }

                        itens.forEach((item) => {
                            const li = document.createElement('li');
                            li.textContent = item;
                            ul.appendChild(li);
                        });
                    };

                    preencherListaApr('modal-apr-funcoes', card.dataset.aprFuncoes, ';|\\n');
                    preencherListaApr('modal-apr-etapas', card.dataset.aprEtapas, '\\n|;');
                    preencherListaAprEpis('modal-apr-epis', card.dataset.aprEpisJson);

                    document.getElementById('modal-bloco-apr').classList.remove('hidden');
                }

                // TREINAMENTO NR
                if (card.dataset.servico === 'Treinamentos NRs') {

                    blocoTreinamento.classList.remove('hidden');

                    const localTipo = card.dataset.treinamentoLocal || '—';
                    const unidade = card.dataset.treinamentoUnidade || '—';
                    let participantes = card.dataset.treinamentoParticipantes || '';
                    const modo = card.dataset.treinamentoModo || '';
                    const pacote = card.dataset.treinamentoPacote || '';
                    const codigos = card.dataset.treinamentoCodigos || '';

                    spanTreinLocal.textContent = localTipo === 'clinica' ? 'Clínica' : 'In Company';
                    spanTreinUnidade.textContent = unidade;
                    try {
                        const lista = participantes ? JSON.parse(participantes) : [];
                        if (Array.isArray(lista) && lista.length) {
                            spanTreinPart.innerHTML = lista.map(nome => String(nome)).join('<br>');
                        } else {
                            spanTreinPart.textContent = '—';
                        }
                    } catch (e) {
                        spanTreinPart.textContent = participantes || '—';
                    }
                    if (spanTreinModo) {
                        spanTreinModo.textContent = modo === 'pacote' ? 'Pacote' : (modo === 'avulso' ? 'Avulso' : '—');
                    }
                    if (spanTreinCodigos) {
                        spanTreinCodigos.textContent = codigos || '—';
                    }
                    if (spanTreinPacote) {
                        spanTreinPacote.textContent = pacote || '—';
                    }

                } else {
                    blocoTreinamento.classList.add('hidden');
                }

                // LTCAT
                if (card.dataset.servico === 'LTCAT') {
                    const tipoBruto = card.dataset.ltcatTipo || '';
                    let tipoLabel = '—';

                    if (tipoBruto === 'matriz') {
                        tipoLabel = 'Matriz';
                    } else if (tipoBruto === 'especifico') {
                        tipoLabel = 'Específico';
                    } else if (tipoBruto) {
                        tipoLabel = tipoBruto.charAt(0).toUpperCase() + tipoBruto.slice(1);
                    }

                    document.getElementById('modal-ltcat-tipo').textContent = tipoLabel;
                    document.getElementById('modal-ltcat-endereco').textContent = card.dataset.ltcatEndereco || '—';
                    document.getElementById('modal-ltcat-total-funcoes').textContent =
                        card.dataset.ltcatTotalFuncoes || '—';
                    document.getElementById('modal-ltcat-total-func').textContent =
                        card.dataset.ltcatTotalFuncionarios || '—';
                    document.getElementById('modal-ltcat-funcoes').textContent =
                        card.dataset.ltcatFuncoes || '—';

                    document.getElementById('modal-bloco-ltcat').classList.remove('hidden');
                }

                // LTIP
                if (card.dataset.servico === 'LTIP') {
                    document.getElementById('modal-ltip-endereco').textContent =
                        card.dataset.ltipEndereco || '—';

                    document.getElementById('modal-ltip-funcoes').textContent =
                        card.dataset.ltipFuncoes || '—';

                    document.getElementById('modal-ltip-total-func').textContent =
                        card.dataset.ltipTotalFuncionarios || '—';

                    document.getElementById('modal-bloco-ltip').classList.remove('hidden');
                }

                // PAE
                if (card.dataset.servico === 'PAE') {
                    document.getElementById('modal-pae-endereco').textContent =
                        card.dataset.paeEndereco || '—';

                    document.getElementById('modal-pae-total-func').textContent =
                        card.dataset.paeTotalFuncionarios || '—';

                    document.getElementById('modal-pae-descricao').textContent =
                        card.dataset.paeDescricao || '—';

                    document.getElementById('modal-bloco-pae').classList.remove('hidden');
                }

                // PCMSO
                if (card.dataset.servico === 'PCMSO') {
                    const tipoBruto = card.dataset.pcmsoTipo || '';
                    let tipoLabel = '—';

                    if (tipoBruto === 'matriz') {
                        tipoLabel = 'Matriz';
                    } else if (tipoBruto === 'especifico') {
                        tipoLabel = 'Específico';
                    } else if (tipoBruto) {
                        tipoLabel = tipoBruto.charAt(0).toUpperCase() + tipoBruto.slice(1);
                    }

                    document.getElementById('modal-pcmso-tipo').textContent = tipoLabel;
                    document.getElementById('modal-pcmso-prazo').textContent = card.dataset.pcmsoPrazo || '—';
                    document.getElementById('modal-pcmso-obra-nome').textContent = card.dataset.pcmsoObraNome || '—';
                    document.getElementById('modal-pcmso-obra-cnpj').textContent = card.dataset.pcmsoObraCnpj || '—';
                    document.getElementById('modal-pcmso-obra-cei').textContent = card.dataset.pcmsoObraCei || '—';
                    document.getElementById('modal-pcmso-obra-endereco').textContent =
                        card.dataset.pcmsoObraEndereco || '—';

                    const linkWrapper = document.getElementById('modal-pcmso-pgr-wrapper');
                    const linkEl = document.getElementById('modal-pcmso-pgr-link');
                    const url = card.dataset.pcmsoPgrUrl || '';

                    if (url) {
                        linkEl.href = url;
                        linkWrapper.classList.remove('hidden');
                    } else {
                        linkEl.href = '#';
                        linkWrapper.classList.add('hidden');
                    }

                    document.getElementById('modal-bloco-pcmso').classList.remove('hidden');
                }

                // badge de status
                if (isCancelada) {
                    spanStatusText.textContent = 'Cancelada';
                    badgeStatus.className =
                        'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ' +
                        'bg-red-100 text-red-700 border border-red-200';
                } else {
                    spanStatusText.textContent = card.dataset.status || '';
                    badgeStatus.className =
                        'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border ';

                    if (status.includes('pendente')) {
                        badgeStatus.className += 'bg-amber-100 text-amber-700 border-amber-200';
                    } else if (status.includes('execução') || status.includes('execucao')) {
                        badgeStatus.className += 'bg-blue-100 text-blue-700 border-blue-200';
                    } else if (status.includes('atrasado')) {
                        badgeStatus.className += 'bg-rose-100 text-rose-700 border-rose-200';
                    } else {
                        badgeStatus.className += 'bg-slate-100 text-slate-700 border-slate-200';
                    }
                }

                if (exclusaoInfo) {
                    if (isCancelada) {
                        exclusaoInfo.classList.remove('hidden');
                        if (spanExcluidoPor) {
                            spanExcluidoPor.textContent = card.dataset.excluidoPor || '—';
                        }
                        if (spanMotivoExclusao) {
                            spanMotivoExclusao.textContent = card.dataset.motivoExclusao || '—';
                        }
                    } else {
                        exclusaoInfo.classList.add('hidden');
                        if (spanExcluidoPor) {
                            spanExcluidoPor.textContent = '—';
                        }
                        if (spanMotivoExclusao) {
                            spanMotivoExclusao.textContent = '—';
                        }
                    }
                }

                modal.dataset.moveUrl = card.dataset.moveUrl || '';
                modal.dataset.tarefaId = card.dataset.id || '';
                modal.dataset.editUrl = card.dataset.editUrl || '';

                // === BLOQUEIO DE AÇÕES SE CANCELADA ===
                const btnEditar      = document.getElementById('btn-editar-tarefa');
                const btnSalvarObs   = document.getElementById('btn-salvar-observacao');
                const btnExcluir     = document.getElementById('btn-excluir-tarefa');
                const btnReprecificar = document.getElementById('modal-reprecificar-btn');
                const moverBtns      = document.querySelectorAll('.js-mover-coluna');

                // const isCancelada = card.dataset.cancelada === '1';

                function toggleBtn(btn, disabled) {
                    if (!btn) return;
                    const permissionLocked = btn.dataset.permissionLocked === '1';
                    const shouldDisable = disabled || permissionLocked;
                    if (shouldDisable) {
                        btn.setAttribute('disabled', 'disabled');
                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        btn.removeAttribute('disabled');
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }

                if (isCancelada) {
                    toggleBtn(btnEditar, true);
                    toggleBtn(btnSalvarObs, true);
                    toggleBtn(btnExcluir, true);
                    toggleBtn(btnReprecificar, true);
                    moverBtns.forEach(b => toggleBtn(b, true));
                } else {
                    toggleBtn(btnEditar, false);
                    toggleBtn(btnSalvarObs, false);
                    toggleBtn(btnExcluir, false);
                    toggleBtn(btnReprecificar, false);
                    moverBtns.forEach(b => toggleBtn(b, false));
                }



                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modalUploadLoadingCount = 0;
                setModalUploadLoading(false);
            }

            function closeModal() {
                if (!modal) return;
                if (modal.classList.contains('hidden')) return;
                modalUploadLoadingCount = 0;
                setModalUploadLoading(false);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                closeOverlayAlerts();
            }

            function hideModalWithoutReload() {
                if (!modal) return;
                modalUploadLoadingCount = 0;
                setModalUploadLoading(false);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function closeOverlayAlerts() {
                if (window.Swal && typeof window.Swal.close === 'function') {
                    window.Swal.close();
                }

                const overlayRoot = document.getElementById('app-overlay-root');
                if (overlayRoot) {
                    overlayRoot.querySelectorAll('.swal2-container').forEach((el) => el.remove());
                    overlayRoot.classList.add('pointer-events-none');
                }

                document.querySelectorAll('.swal2-container').forEach((el) => el.remove());
                document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                document.documentElement.classList.remove('swal2-shown', 'swal2-height-auto');
            }

            function hidePendenciaInline() {
                if (pendenciaWrapper) {
                    pendenciaWrapper.classList.add('hidden');
                    pendenciaWrapper.classList.remove('flex');
                    pendenciaWrapper.style.display = 'none';
                    pendenciaWrapper.setAttribute('aria-hidden', 'true');
                }
                if (pendenciaTexto) {
                    pendenciaTexto.textContent = '—';
                }
            }

            function showPendenciaInline(message) {
                if (pendenciaTexto) {
                    pendenciaTexto.textContent = message || 'A tarefa ainda possui pendencias.';
                }
                if (pendenciaWrapper) {
                    pendenciaWrapper.classList.remove('hidden');
                    pendenciaWrapper.classList.add('flex');
                    pendenciaWrapper.style.display = 'flex';
                    pendenciaWrapper.setAttribute('aria-hidden', 'false');
                }
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            if (modal) {
                const modalContent = modal.firstElementChild;
                if (modalContent) {
                    modalContent.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });
                }
            }

            if (btnNotificarCliente) {
                btnNotificarCliente.addEventListener('click', function () {
                    if (!detalhesCurrentCard) return;
                    const links = collectWhatsappLinks(detalhesCurrentCard);
                    if (!links.length) {
                        window.uiAlert('Nenhum documento anexado para enviar.');
                        return;
                    }

                    const payload = buildWhatsappMensagem(detalhesCurrentCard);
                    if (!payload) {
                        window.uiAlert('Telefone do cliente não informado.');
                        return;
                    }

                    const whatsappUrl = `https://wa.me/${payload.telefone}?text=${encodeURIComponent(payload.mensagem)}`;
                    window.open(whatsappUrl, '_blank');
                });
            }

            // Clique no card -> abre APENAS o modal de detalhes
            document.addEventListener('click', function (e) {
                const card = e.target.closest('.kanban-card');
                if (!card) return;
                openDetalhesModal(card);
            });

            // =========================================================
            //  MODAL DE FINALIZAR COM ARQUIVO
            // =========================================================
            const finalizarModal = document.getElementById('tarefa-finalizar-modal');
            const finalizarCloseBtn = document.getElementById('tarefa-finalizar-close');
            const finalizarCloseXBtn = document.getElementById('tarefa-finalizar-x');
            const finalizarForm = document.getElementById('tarefa-finalizar-form');
            const finalizarArquivo = document.getElementById('tarefa-finalizar-arquivo');
            const finalizarNotificar = document.getElementById('tarefa-finalizar-notificar');
            const finalizarTituloSpan = document.getElementById('tarefa-finalizar-titulo');
            const finalizarClienteSpan = document.getElementById('tarefa-finalizar-cliente');

            let finalizarCurrentCard = null;
            let finalizarUrl = null;
            let finalizarSkipReloadOnClose = false;

            function tarefaExigeDocumentoCombinado(card) {
                return card?.dataset?.pgrPcmso === '1' || card?.dataset?.pgrComArt === '1';
            }

            function tarefaTemDocumentosSuficientes(card) {
                if (!card) return false;

                const isTreinamentoTask = card.dataset.isTreinamentoTask === '1';
                if (isTreinamentoTask) {
                    return true;
                }

                const temDocumentoFinal = !!(card.dataset.arquivoClienteUrl || '');
                const precisaDocumentoComplementar = card.dataset.pgrPcmso === '1';
                const precisaArt = card.dataset.pgrComArt === '1';
                const temDocumentoComplementar = !!(card.dataset.pcmsoPgrUrl || '');
                const temDocumentoArt = !!(card.dataset.artPgrUrl || '');

                return temDocumentoFinal
                    && (!precisaDocumentoComplementar || temDocumentoComplementar)
                    && (!precisaArt || temDocumentoArt);
            }

            async function finalizarComDocumentoExistente(card, options = {}) {
                const url = card?.dataset?.finalizarDocumentoExistenteUrl || '';
                if (!url) {
                    throw new Error('Não foi possível finalizar esta tarefa.');
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await parseJsonResponse(response);

                if (!response.ok) {
                    const error =
                        data?.error
                        || data?.message
                        || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                        || 'Erro ao finalizar tarefa.';
                    throw new Error(error);
                }

                await handleFinalizacaoResponse(card, data, options);
            }

            function openFinalizarModal(card, url) {
                console.log(finalizarModal)
                if (!finalizarModal) return;

                finalizarCurrentCard = card;
                finalizarUrl = url;

                if (finalizarTituloSpan) {
                    finalizarTituloSpan.textContent =
                        (card.dataset.servico || '') + ' - #' + (card.dataset.id || '');
                }
                if (finalizarClienteSpan) {
                    finalizarClienteSpan.textContent = card.dataset.cliente || '';
                }

                if (finalizarArquivo) {
                    finalizarArquivo.value = '';
                }
                if (finalizarNotificar) {
                    finalizarNotificar.checked = true;
                }

                finalizarModal.classList.remove('hidden');
                finalizarModal.classList.add('flex');
            }

            function closeFinalizarModal() {
                console.log('chamando metodo para fechar modal')
                if (!finalizarModal) return;
                finalizarModal.classList.add('hidden');
                finalizarModal.classList.remove('flex');
                finalizarCurrentCard = null;
                finalizarUrl = null;
            }

            async function handleFinalizacaoResponse(card, data, options = {}) {
                if (!card || !data || !data.ok) return;

                if (data.documento_url) {
                    card.dataset.arquivoClienteUrl = data.documento_url;
                }

                const statusName = data.status_label || 'Finalizada';
                card.dataset.status = statusName;
                card.dataset.finalizado = data.finalizada_total ? '1' : '0';
                if (data?.certificados) {
                    card.dataset.certificadosPendentes = data.certificados.pendente ? '1' : '0';
                    card.dataset.certificadosEnviados = String(data.certificados.enviados ?? 0);
                    card.dataset.certificadosTotal = String(data.certificados.total_esperado ?? 0);
                }

                const statusSpan = card.querySelector('[data-role="card-status-label"]');
                if (statusSpan) {
                    statusSpan.textContent = statusName;
                }

                if (!data.finalizada_total && data?.certificados?.pendente) {
                    const colunaAguardando = document.querySelector('.kanban-column[data-coluna-slug="aguardando-fornecedor"], .kanban-column[data-coluna-slug="aguardando"]');
                    const colunaAguardandoId = colunaAguardando?.dataset?.colunaId;
                    if (colunaAguardandoId) {
                        moveCardToColumn(card, colunaAguardandoId, statusName);
                    }

                    if (options.closeModal) {
                        closeFinalizarModal();
                    }

                    openDetalhesModal(card);
                    showPendenciaInline(`${data.message || 'A tarefa ainda possui pendencias.'} Deseja continuar alterando a tarefa agora para anexar o que falta?`);
                    return;
                }

                hidePendenciaInline();

                if (options.closeModal) {
                    closeFinalizarModal();
                }

                hideModalWithoutReload();
                closeOverlayAlerts();

                const mensagemSucesso = data.message || 'Tarefa finalizada com sucesso.';
                await window.uiAlert(mensagemSucesso, {
                        icon: 'success',
                        title: 'Sucesso',
                    });

                window.location.reload();
            }

            [finalizarCloseBtn, finalizarCloseXBtn].forEach((btn) => {
                if (!btn) return;

                btn.addEventListener('click', function () {
                    console.log('fechar');
                    closeFinalizarModal();
                    // se quiser voltar o card pra coluna original:
                    if (!finalizarSkipReloadOnClose) {
                        window.location.reload();
                    }
                    finalizarSkipReloadOnClose = false;
                });
            });

            if (finalizarModal) {
                finalizarModal.addEventListener('click', function (e) {
                    if (e.target === finalizarModal) {
                        closeFinalizarModal();
                        if (!finalizarSkipReloadOnClose) {
                            window.location.reload();
                        }
                        finalizarSkipReloadOnClose = false;
                    }
                });
            }

            if (finalizarForm) {
                finalizarForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (!finalizarUrl || !finalizarCurrentCard || !finalizarArquivo.files.length) {
                        return;
                    }

                    const abrirWhatsapp = finalizarNotificar && finalizarNotificar.checked;
                    const whatsappPopup = abrirWhatsapp ? window.open('about:blank', '_blank') : null;
                    if (whatsappPopup) {
                        whatsappPopup.document.write('Aguarde, preparando o envio...');
                    }

                    const formData = new FormData();
                    formData.append('arquivo_cliente', finalizarArquivo.files[0]);
                    if (finalizarNotificar && finalizarNotificar.checked) {
                        formData.append('notificar', '1');
                    }

                    fetch(finalizarUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })
                        .then(async (r) => {
                            const data = await parseJsonResponse(r, 'Erro ao finalizar tarefa.');

                            if (!r.ok) {
                                const error =
                                    data?.error
                                    || data?.message
                                    || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                    || 'Erro ao finalizar tarefa.';
                                const uploadError = new Error(error);
                                uploadError.status = r.status;
                                throw uploadError;
                            }

                            return data;
                        })
                        .then(async (data) => {
                            if (!data || !data.ok) {
                                const error =
                                    data?.error
                                    || data?.message
                                    || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                    || 'Erro ao finalizar tarefa.';
                                window.uiAlert(error);
                                return;
                            }

                            if (data.finalizada_total && finalizarNotificar && finalizarNotificar.checked) {
                                const arquivoUrl = data.documento_url || '';
                                const payload = buildWhatsappMensagem(finalizarCurrentCard, arquivoUrl);

                                if (payload) {
                                    const whatsappUrl = `https://wa.me/${payload.telefone}?text=${encodeURIComponent(payload.mensagem)}`;
                                    if (whatsappPopup && !whatsappPopup.closed) {
                                        whatsappPopup.location.href = whatsappUrl;
                                    } else {
                                        window.location.href = whatsappUrl;
                                    }
                                } else if (whatsappPopup && !whatsappPopup.closed) {
                                    whatsappPopup.close();
                                }
                            } else if (whatsappPopup && !whatsappPopup.closed) {
                                whatsappPopup.close();
                            }

                            finalizarSkipReloadOnClose = !data.finalizada_total && !!data?.certificados?.pendente;
                            await handleFinalizacaoResponse(finalizarCurrentCard, data, { closeModal: true });
                            finalizarSkipReloadOnClose = false;
                        })
                        .catch((error) => {
                            if (whatsappPopup && !whatsappPopup.closed) {
                                whatsappPopup.close();
                            }
                            showUploadErrorAlert(error?.message, 'Erro ao finalizar tarefa.', error?.status, finalizarCurrentCard);
                        });
                });
            }

            // Função global usada pelo Sortable
            window.abreModalFinalizarTarefa = openFinalizarModal;

            // =========================================================
            //  SLA / TEMPO REAL (polling)
            // =========================================================
            const PRAZOS_URL = @json(route('operacional.tarefas.prazos'));
            const CSRF_TOKEN = @json(csrf_token());
            const POLL_INTERVAL = 30000;
            const TICK_INTERVAL = 1000;
            const DEFAULT_UPLOAD_MAX_MB = @json((int) config('services.upload_limits.default_mb', 10));
            const PGR_UPLOAD_MAX_MB = @json((int) config('services.upload_limits.pgr_mb', 100));
            const PCMSO_UPLOAD_MAX_MB = @json((int) config('services.upload_limits.pcmso_mb', 100));

            function sanitizeResponseErrorMessage(raw, fallbackMessage) {
                const text = String(raw || '')
                    .replace(/<br\s*\/?>/gi, '\n')
                    .replace(/<[^>]*>/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();

                if (!text || /unexpected token/i.test(text) || /not valid json/i.test(text)) {
                    return fallbackMessage;
                }

                return text;
            }

            async function parseJsonResponse(response, fallbackMessage = 'Erro ao processar a resposta do servidor.') {
                const raw = await response.text();

                if (!raw) {
                    return null;
                }

                try {
                    return JSON.parse(raw);
                } catch (error) {
                    const jsonStart = raw.indexOf('{');
                    const jsonEnd = raw.lastIndexOf('}');

                    if (jsonStart !== -1 && jsonEnd !== -1 && jsonEnd > jsonStart) {
                        try {
                            return JSON.parse(raw.slice(jsonStart, jsonEnd + 1));
                        } catch (innerError) {
                        }
                    }
                }

                return {
                    ok: false,
                    error: sanitizeResponseErrorMessage(raw, fallbackMessage),
                };
            }

            function isUploadTooLargeError(message, status = null) {
                const normalized = String(message || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase();

                return Number(status) === 413
                    || normalized.includes('arquivo enviado e muito grande')
                    || normalized.includes('payload too large')
                    || normalized.includes('post content length exceeded')
                    || normalized.includes('ultrapassou o tamanho permitido')
                    || /no maximo \d+\s*mb/.test(normalized)
                    || /no maximo \d+\s*megabytes?/.test(normalized);
            }

            function getUploadLimitMbForCard(card) {
                const servico = String(card?.dataset?.servico || '').trim().toUpperCase();

                if (servico === 'PGR') {
                    return PGR_UPLOAD_MAX_MB;
                }

                if (servico === 'PCMSO') {
                    return PCMSO_UPLOAD_MAX_MB;
                }

                return DEFAULT_UPLOAD_MAX_MB;
            }

            function showUploadTooLargeAlert(card = null) {
                const maxUploadMb = getUploadLimitMbForCard(card || finalizarCurrentCard || detalhesCurrentCard);

                return window.uiAlert('', {
                    title: 'Atenção',
                    html: `
                        <div class="text-left">
                            <p>O arquivo ultrapassou o tamanho permitido de ${maxUploadMb} MB.</p>
                        </div>
                    `,
                });
            }

            function showUploadErrorAlert(message, fallbackMessage, status = null, card = null) {
                const safeMessage = message || fallbackMessage;
                const normalizedMessage = String(safeMessage || '').trim().toLowerCase();

                if (isUploadTooLargeError(safeMessage, status)) {
                    return showUploadTooLargeAlert(card);
                }

                if (normalizedMessage === 'failed to fetch') {
                    return window.uiAlert('Não foi possível concluir a solicitação. Verifique sua conexão e tente novamente.');
                }

                return window.uiAlert(safeMessage || 'Erro ao enviar arquivo.');
            }

            function showUploadSuccessAlert(message = 'Documento anexado com sucesso.') {
                return window.uiAlert(message, {
                    icon: 'success',
                    title: 'Sucesso',
                });
            }

            function getKanbanCards() {
                return Array.from(document.querySelectorAll('.kanban-card'));
            }

            function updateAllTempoLabels() {
                const nowMs = Date.now();
                getKanbanCards().forEach(card => updateTempoCard(card, nowMs));
                if (detalhesCurrentCard) {
                    updateModalTempo(detalhesCurrentCard, nowMs);
                }
            }

            function uploadDocumentoClienteTemporario(file) {
                if (!file || !detalhesCurrentCard) return;
                const url = detalhesCurrentCard.dataset.substituirDocUrl;
                if (!url) {
                    window.uiAlert('Não foi possível enviar o documento desta tarefa.');
                    return;
                }

                setModalUploadLoading(true, {
                    title: 'Enviando documento',
                    text: 'O documento principal da tarefa esta sendo enviado.',
                });

                const formData = new FormData();
                formData.append('arquivo_cliente', file);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                    .then(async (r) => {
                        const data = await parseJsonResponse(r, 'Erro ao enviar documento.');

                        if (!r.ok) {
                            const error =
                                data?.error
                                || data?.message
                                || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                || 'Erro ao enviar documento.';
                            const uploadError = new Error(error);
                            uploadError.status = r.status;
                            throw uploadError;
                        }

                        return data;
                    })
                    .then(async (data) => {
                        if (!data || !data.ok) {
                            const error =
                                data?.error
                                || data?.message
                                || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                || 'Erro ao enviar documento.';
                            window.uiAlert(error);
                            return;
                        }

                        if (data.documento_url) {
                            detalhesCurrentCard.dataset.arquivoClienteUrl = data.documento_url;
                            if (arquivoLink) {
                                arquivoLink.href = data.documento_url;
                            }
                            if (arquivoWrapper) {
                                arquivoWrapper.classList.remove('hidden');
                            }
                            openDetalhesModal(detalhesCurrentCard);
                        }

                        await showUploadSuccessAlert(data?.message || 'Documento anexado com sucesso.');
                    })
                    .catch((error) => {
                        showUploadErrorAlert(error?.message, 'Erro ao enviar documento.', error?.status, detalhesCurrentCard);
                    })
                    .finally(() => {
                        setModalUploadLoading(false);
                    });
            }

            function uploadDocumentoComplementar(file) {
                if (!file || !detalhesCurrentCard) return;
                const url = detalhesCurrentCard.dataset.substituirDocComplementarUrl;
                if (!url) {
                    window.uiAlert('Não foi possível enviar o documento complementar desta tarefa.');
                    return;
                }

                setModalUploadLoading(true, {
                    title: 'Enviando documento complementar',
                    text: 'O anexo complementar do PGR + PCMSO esta sendo enviado.',
                });

                const formData = new FormData();
                formData.append('arquivo_cliente', file);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                    .then(async (r) => {
                        const data = await parseJsonResponse(r, 'Erro ao enviar documento complementar.');

                        if (!r.ok) {
                            const error =
                                data?.error
                                || data?.message
                                || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                || 'Erro ao enviar documento complementar.';
                            const uploadError = new Error(error);
                            uploadError.status = r.status;
                            throw uploadError;
                        }

                        return data;
                    })
                    .then(async (data) => {
                        if (!data || !data.ok) {
                            const error =
                                data?.error
                                || data?.message
                                || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                || 'Erro ao enviar documento complementar.';
                            window.uiAlert(error);
                            return;
                        }

                        if (data.documento_url) {
                            detalhesCurrentCard.dataset.pcmsoPgrUrl = data.documento_url;
                            if (data.delete_url) {
                                detalhesCurrentCard.dataset.pcmsoPgrDeleteUrl = data.delete_url;
                            }
                            upsertAnexoNaTarefa(data.anexo);
                            openDetalhesModal(detalhesCurrentCard);
                        }

                        await showUploadSuccessAlert(data?.message || 'Documento complementar anexado com sucesso.');
                    })
                    .catch((error) => {
                        showUploadErrorAlert(error?.message, 'Erro ao enviar documento complementar.', error?.status, detalhesCurrentCard);
                    })
                    .finally(() => {
                        setModalUploadLoading(false);
                    });
            }

            function uploadDocumentoArt(file) {
                if (!file || !detalhesCurrentCard) return;
                const url = detalhesCurrentCard.dataset.substituirDocArtUrl;
                if (!url) {
                    window.uiAlert('Não foi possível enviar o documento ART desta tarefa.');
                    return;
                }

                setModalUploadLoading(true, {
                    title: 'Enviando documento ART',
                    text: 'O anexo ART do PGR + PCMSO esta sendo enviado.',
                });

                const formData = new FormData();
                formData.append('arquivo_cliente', file);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                    .then(async (r) => {
                        const data = await parseJsonResponse(r, 'Erro ao enviar documento ART.');

                        if (!r.ok) {
                            const error =
                                data?.error
                                || data?.message
                                || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                || 'Erro ao enviar documento ART.';
                            const uploadError = new Error(error);
                            uploadError.status = r.status;
                            throw uploadError;
                        }

                        return data;
                    })
                    .then(async (data) => {
                        if (!data || !data.ok) {
                            const error =
                                data?.error
                                || data?.message
                                || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                || 'Erro ao enviar documento ART.';
                            window.uiAlert(error);
                            return;
                        }

                        if (data.documento_url) {
                            detalhesCurrentCard.dataset.artPgrUrl = data.documento_url;
                            if (data.delete_url) {
                                detalhesCurrentCard.dataset.artPgrDeleteUrl = data.delete_url;
                            }
                            upsertAnexoNaTarefa(data.anexo);
                            openDetalhesModal(detalhesCurrentCard);
                        }

                        await showUploadSuccessAlert(data?.message || 'Documento ART anexado com sucesso.');
                    })
                    .catch((error) => {
                        showUploadErrorAlert(error?.message, 'Erro ao enviar documento ART.', error?.status, detalhesCurrentCard);
                    })
                    .finally(() => {
                        setModalUploadLoading(false);
                    });
            }

            function uploadCertificadosTreinamento(files) {
                if (!files?.length || !detalhesCurrentCard) return;
                const url = detalhesCurrentCard.dataset.certificadosUploadUrl;
                if (!url) {
                    window.uiAlert('Não foi possível enviar os certificados desta tarefa.');
                    return;
                }

                setModalUploadLoading(true, {
                    title: 'Enviando certificados',
                    text: 'Os arquivos selecionados estao sendo enviados para a tarefa.',
                });

                const formData = new FormData();
                Array.from(files).forEach((file) => formData.append('arquivos[]', file));

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                    .then(async (r) => {
                        const data = await parseJsonResponse(r, 'Erro ao enviar certificados.');
                        if (!r.ok) {
                            const error =
                                data?.error
                                || data?.message
                                || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                || 'Erro ao enviar certificados.';
                            const uploadError = new Error(error);
                            uploadError.status = r.status;
                            throw uploadError;
                        }
                        return data;
                    })
                    .then((data) => {
                        if (!data?.ok) {
                            window.uiAlert(data?.error || data?.message || 'Erro ao enviar certificados.');
                            return;
                        }

                        const cert = data.certificados || {};
                        const enviados = Number(cert.enviados || 0);
                        const total = Number(cert.total_esperado || 0);
                        detalhesCurrentCard.dataset.certificadosEnviados = String(enviados);
                        detalhesCurrentCard.dataset.certificadosTotal = String(total);
                        detalhesCurrentCard.dataset.certificadosPendentes = cert.pendente ? '1' : '0';
                        appendAnexosNaTarefa(data.anexos || []);
                        if (data.status_label) {
                            detalhesCurrentCard.dataset.status = String(data.status_label);
                        }

                        if (certificadosInput) {
                            certificadosInput.value = '';
                        }
                        renderCertificadosSelecionados([]);
                        openDetalhesModal(detalhesCurrentCard);
                    })
                    .catch((error) => {
                        showUploadErrorAlert(error?.message, 'Erro ao enviar certificados.', error?.status, detalhesCurrentCard);
                    })
                    .finally(() => {
                        setModalUploadLoading(false);
                    });
            }

            bindImmediateUploadDropzone(arquivoDropzone, arquivoReplaceInput, uploadDocumentoClienteTemporario);
            bindImmediateUploadDropzone(arquivoComplementarDropzone, arquivoComplementarInput, uploadDocumentoComplementar);
            bindImmediateUploadDropzone(arquivoArtDropzone, arquivoArtInput, uploadDocumentoArt);

            function resolveCardBorderColor(card, colunaEl) {
                const colunaCor = colunaEl?.dataset?.colunaCor || '#38bdf8';
                const colunaSlug = String(colunaEl?.dataset?.colunaSlug || '').toLowerCase();
                const isAso = String(card?.dataset?.isAso || '') === '1';
                const servico = String(card?.dataset?.servico || '');

                if (colunaSlug === 'pendente' || colunaSlug === 'pendentes') {
                    if (isAso) return '#2563eb';
                    if (servico === 'Treinamentos NRs') return '#16a34a';
                }

                return colunaCor;
            }

            function bindDocumentoDeleteList(listElement) {
                if (!listElement) return;

                listElement.addEventListener('click', async function (event) {
                    const btn = event.target.closest('[data-doc-delete-url]');
                    if (!btn || !detalhesCurrentCard) return;

                    const url = btn.dataset.docDeleteUrl;
                    const docId = String(btn.dataset.docId || '');
                    const docKind = String(btn.dataset.docKind || 'anexo');
                    const docService = String(btn.dataset.docService || '').toLowerCase();
                    if (!url) return;

                    const confirmado = await window.uiConfirm(
                        'Deseja remover este documento da tarefa?',
                        {
                            title: 'Excluir documento',
                            confirmText: 'Excluir',
                            cancelText: 'Cancelar',
                        }
                    );
                    if (!confirmado) return;

                    btn.disabled = true;
                    try {
                        const response = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Não foi possível excluir o documento.');
                        }

                        let anexos = [];
                        try {
                            anexos = detalhesCurrentCard.dataset.anexos
                                ? JSON.parse(detalhesCurrentCard.dataset.anexos)
                                : [];
                        } catch (e) {
                            anexos = [];
                        }

                        anexos = anexos.filter((a) => String(a?.id || '') !== docId);
                        detalhesCurrentCard.dataset.anexos = JSON.stringify(anexos);

                        if (docKind === 'documento_principal') {
                            detalhesCurrentCard.dataset.arquivoClienteUrl = '';
                        } else if (docKind === 'documento_complementar') {
                            detalhesCurrentCard.dataset.pcmsoPgrUrl = '';
                        } else if (docKind === 'documento_art') {
                            detalhesCurrentCard.dataset.artPgrUrl = '';
                        } else if (docService === 'certificado_treinamento') {
                            const totalEsperado = Number(detalhesCurrentCard.dataset.certificadosTotal || '0');
                            const enviadosAtualizados = Math.max(0, Number(detalhesCurrentCard.dataset.certificadosEnviados || '0') - 1);
                            detalhesCurrentCard.dataset.certificadosEnviados = String(enviadosAtualizados);
                            detalhesCurrentCard.dataset.certificadosPendentes = (totalEsperado > enviadosAtualizados) ? '1' : '0';
                        }

                        openDetalhesModal(detalhesCurrentCard);
                    } catch (error) {
                        window.uiAlert(error?.message || 'Erro ao excluir o documento.');
                    } finally {
                        btn.disabled = false;
                    }
                });
            }

            bindDocumentoDeleteList(docsList);
            bindDocumentoDeleteList(exclusaoAnexoList);

            if (certificadosDropzone && certificadosInput) {
                certificadosDropzone.addEventListener('click', function () {
                    if (certificadosDropzone.classList.contains('cursor-not-allowed')) return;
                    certificadosInput.value = '';
                    certificadosInput.click();
                });

                certificadosDropzone.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    if (certificadosDropzone.classList.contains('cursor-not-allowed')) return;
                    certificadosDropzone.classList.add('border-amber-400', 'bg-amber-100/70');
                });

                certificadosDropzone.addEventListener('dragleave', function (e) {
                    e.preventDefault();
                    certificadosDropzone.classList.remove('border-amber-400', 'bg-amber-100/70');
                });

                certificadosDropzone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    certificadosDropzone.classList.remove('border-amber-400', 'bg-amber-100/70');
                    if (certificadosDropzone.classList.contains('cursor-not-allowed')) return;

                    const files = e.dataTransfer?.files;
                    if (!files?.length) return;

                    uploadCertificadosTreinamento(files);
                });

                certificadosInput.addEventListener('change', function () {
                    if (!certificadosInput.files?.length) return;
                    uploadCertificadosTreinamento(certificadosInput.files);
                });
            }

            if (finalizarBtn) {
                finalizarBtn.addEventListener('click', async function () {
                    if (!detalhesCurrentCard) return;

                    if (!tarefaTemDocumentosSuficientes(detalhesCurrentCard)) {
                        window.uiAlert('Anexe primeiro o documento final da tarefa antes de finalizar.');
                        return;
                    }

                    const url = detalhesCurrentCard.dataset.finalizarDocumentoExistenteUrl;
                    if (!url) {
                        window.uiAlert('Não foi possível finalizar esta tarefa.');
                        return;
                    }

                    finalizarBtn.disabled = true;
                    try {
                        await finalizarComDocumentoExistente(detalhesCurrentCard, { fromDetalhes: true });
                    } catch (error) {
                        window.uiAlert(error?.message || 'Erro ao finalizar tarefa.');
                    } finally {
                        finalizarBtn.disabled = false;
                    }
                });
            }

            if (reprecificarBtn) {
                reprecificarBtn.addEventListener('click', async function () {
                    if (!detalhesCurrentCard) return;
                    const url = detalhesCurrentCard.dataset.reprecificarUrl || '';
                    if (!url) {
                        window.uiAlert('Não foi possível localizar a rota de reprecificação.');
                        return;
                    }

                    const confirmar = typeof window.uiConfirm === 'function'
                        ? await window.uiConfirm(
                            'Deseja reprecificar esta venda com os parâmetros atuais?',
                            {
                                title: 'Reprecificar venda',
                                icon: 'warning',
                                confirmText: 'Reprecificar',
                                cancelText: 'Cancelar',
                            }
                        )
                        : window.confirm('Deseja reprecificar esta venda com os parâmetros atuais?');

                    if (!confirmar) return;

                    reprecificarBtn.disabled = true;
                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                        });

                        const json = await response.json().catch(() => ({}));
                        if (!response.ok || !json?.ok) {
                            window.uiAlert(json?.message || 'Não foi possível reprecificar a venda.');
                            return;
                        }

                        const totalLabel = (typeof json.total === 'number')
                            ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(json.total)
                            : null;
                        const msg = totalLabel
                            ? `Venda reprecificada com sucesso. Novo total: ${totalLabel}.`
                            : 'Venda reprecificada com sucesso.';

                        await window.uiAlert(msg, { icon: 'success', title: 'Sucesso' });
                        window.location.reload();
                    } catch (error) {
                        window.uiAlert('Erro ao reprecificar a venda.');
                    } finally {
                        reprecificarBtn.disabled = false;
                    }
                });
            }

            if (pendenciaContinuarBtn) {
                pendenciaContinuarBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    hidePendenciaInline();
                });
            }

            if (pendenciaFecharBtn) {
                pendenciaFecharBtn.addEventListener('click', async function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    hidePendenciaInline();

                    const destino = detalhesCurrentCard?.dataset?.status || 'Aguardando fornecedor';
                    await window.uiAlert(`A tarefa foi movida para ${destino}.`, {
                        icon: 'success',
                        title: 'Movimentação',
                    });

                    window.location.reload();
                });
            }

            function moveCardToColumn(card, colunaId, colunaNome) {
                const destino = document.querySelector(`.kanban-column[data-coluna-id="${colunaId}"]`);
                if (!destino) return;
                destino.appendChild(card);

                const cardColor = resolveCardBorderColor(card, destino);
                if (cardColor) {
                    card.style.borderLeftColor = cardColor;
                }

                if (colunaNome) {
                    card.dataset.status = colunaNome;
                }
                card.dataset.finalizado = (String(destino.dataset.colunaSlug || '') === 'finalizada') ? '1' : '0';
            }

            function getColumnDisplayName(colunaEl) {
                if (!colunaEl) return '';
                const section = colunaEl.closest('section');
                const headerTitleEl = section ? section.querySelector('article h3') : null;
                return (headerTitleEl?.textContent || colunaEl.dataset.colunaNome || colunaEl.dataset.colunaSlug || '').trim();
            }

            function notifyCardMovement(message, type = 'success') {
                if (!message) return;
                const icon = type === 'error' ? 'error' : 'success';
                const title = type === 'error' ? 'Erro' : 'Movimentação';

                window.setTimeout(() => {
                    window.uiAlert(message, {
                        icon,
                        title,
                    });
                }, 80);
            }

            function renderCertificadosSelecionados(files) {
                if (!certificadosFileList) return;

                const items = Array.from(files || []);
                if (!items.length) {
                    certificadosFileList.innerHTML = '';
                    certificadosFileList.classList.add('hidden');
                    return;
                }

                certificadosFileList.innerHTML = items.map((file) => `
                    <li class="rounded-lg border border-amber-200 bg-white px-3 py-2">
                        ${file.name}
                    </li>
                `).join('');
                certificadosFileList.classList.remove('hidden');
            }

            async function pollPrazos() {
                const ids = getKanbanCards()
                    .map(card => card.dataset.id)
                    .filter(Boolean);

                if (!ids.length) return;

                try {
                    const res = await fetch(PRAZOS_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids }),
                    });
                    const data = await parseJsonResponse(res);
                    if (!data || !data.ok || !Array.isArray(data.tarefas)) return;

                    data.tarefas.forEach((tarefa) => {
                        const card = document.querySelector(`.kanban-card[data-id="${tarefa.id}"]`);
                        if (!card) return;

                        if (tarefa.fim_previsto) {
                            card.dataset.fimPrevisto = tarefa.fim_previsto;
                        }

                        const colunaAtual = card.closest('.kanban-column')?.dataset?.colunaId;
                        if (tarefa.coluna_id && String(tarefa.coluna_id) !== String(colunaAtual)) {
                            moveCardToColumn(card, tarefa.coluna_id, tarefa.coluna_nome || '');
                        }
                    });
                } catch (e) {
                    // silencioso
                }
            }

            updateAllTempoLabels();
            setInterval(updateAllTempoLabels, TICK_INTERVAL);
            pollPrazos();
            setInterval(pollPrazos, POLL_INTERVAL);

            // =========================================================
            //  DRAG & DROP (Sortable)
            // =========================================================
            if (window.Sortable) {
                document.querySelectorAll('.kanban-column').forEach(function (colunaEl) {

                    new Sortable(colunaEl, {
                        group: 'kanban',
                        animation: 150,
                        handle: '.kanban-card',
                        draggable: '.kanban-card',

                        onMove: function (evt) {
                            const card = evt.dragged;
                            if (card.dataset.cancelada === '1') {
                                return false; // se a tarefa estiver cancelada, cancela o movimento
                            }
                        },
                        onEnd: async function (evt) {
                            const card = evt.item;
                            const colunaId = card.closest('.kanban-column').dataset.colunaId;
                            const colunaEl = card.closest('.kanban-column');
                            const colunaCor = colunaEl?.dataset.colunaCor || '#38bdf8';
                            const colunaSlug = colunaEl?.dataset.colunaSlug || '';
                            const moveUrl = card.dataset.moveUrl;
                            const colunaOrigemEl = evt.from;
                            const colunaOrigemId = colunaOrigemEl?.dataset?.colunaId || '';
                            const colunaOrigemNome = getColumnDisplayName(colunaOrigemEl);
                            const colunaDestinoNome = getColumnDisplayName(colunaEl);

                            // segurança extra: se por algum motivo chegou aqui, não processa
                            if (card.dataset.cancelada === '1') {
                                evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                                notifyCardMovement(`A tarefa voltou para ${colunaOrigemNome || 'a coluna de origem'}.`);
                                return;
                            }

                            // ajusta cor da borda com a cor da coluna
                            const cardColor = resolveCardBorderColor(card, colunaEl);
                            if (cardColor) {
                                card.style.borderLeftColor = cardColor;
                            }

                            // Se soltou na coluna "finalizada": NÃO chama mover(),
                            // tenta finalizar automaticamente se os anexos já estiverem completos.

                            if (colunaSlug === 'finalizada') {
                                const devolverParaOrigem = () => {
                                    if (!colunaOrigemEl) {
                                        return;
                                    }

                                    colunaOrigemEl.insertBefore(card, colunaOrigemEl.children[evt.oldIndex] || null);
                                    const colunaOrigemCor = colunaOrigemEl?.dataset?.colunaCor || '';
                                    const corOrigem = resolveCardBorderColor(card, colunaOrigemEl) || colunaOrigemCor;
                                    if (corOrigem) {
                                        card.style.borderLeftColor = corOrigem;
                                    }
                                };

                                if (tarefaTemDocumentosSuficientes(card)) {
                                    try {
                                        await finalizarComDocumentoExistente(card, { closeModal: false });
                                    } catch (error) {
                                        devolverParaOrigem();
                                        openDetalhesModal(card);
                                        window.uiAlert(error?.message || 'Não foi possível finalizar a tarefa.');
                                    }
                                    return;
                                }

                                devolverParaOrigem();
                                openDetalhesModal(card);
                                window.uiAlert('Para concluir, anexe os documentos necessários e clique em finalizar no modal.');
                                return;
                            }

                            // demais colunas -> fluxo normal de mover
                            if (!moveUrl || !colunaId) return;

                            const idsDestino = Array.from(colunaEl.querySelectorAll('.kanban-card'))
                                .map(el => el.dataset.id)
                                .filter(Boolean);
                            const idsOrigem = colunaOrigemEl
                                ? Array.from(colunaOrigemEl.querySelectorAll('.kanban-card'))
                                    .map(el => el.dataset.id)
                                    .filter(Boolean)
                                : [];

                            fetch(moveUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    coluna_id: colunaId,
                                    ordem: idsDestino,
                                    coluna_origem_id: colunaOrigemId,
                                    ordem_origem: idsOrigem,
                                }),
                            })
                                .then(async (response) => {
                                    const data = await parseJsonResponse(response);
                                    if (!response.ok || !data?.ok) {
                                        throw new Error(data?.error || data?.message || 'Erro ao mover a tarefa.');
                                    }

                                    try {
                                        const statusName = data.status_label || colunaDestinoNome || '';
                                        const statusSpan = card.querySelector('[data-role="card-status-label"]');
                                        if (statusSpan && statusName) {
                                            statusSpan.textContent = statusName;
                                        }
                                        if (statusName) {
                                            card.dataset.status = statusName;
                                        }
                                        card.dataset.finalizado = (colunaSlug === 'finalizada') ? '1' : '0';
                                        notifyCardMovement(`A tarefa foi movida para ${statusName || 'a nova coluna'}.`);

                                        const respBadge = card.querySelector('[data-role="card-responsavel-badge"]');
                                        if (respBadge && cardColor) {
                                            respBadge.style.borderColor = cardColor;
                                            respBadge.style.color = '#0f172a';
                                            respBadge.style.backgroundColor = cardColor + '20';
                                        }

                                        if (data.log) {
                                            const logContainer = card.querySelector('[data-role="card-last-log"]');
                                            if (logContainer) {
                                                logContainer.innerHTML = `
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span class="inline-flex items-center gap-1">
                                                            <span>🔁</span>
                                                            <span>
                                                                ${(data.log.de || 'Início')}
                                                                &rarr;
                                                                ${(data.log.para || '-')}
                                                            </span>
                                                        </span>
                                                        <span class="text-[10px] text-slate-400">
                                                            ${(data.log.user || 'Sistema')}
                                                            · ${(data.log.data || '')}
                                                        </span>
                                                    </div>
                                                `;
                                            }
                                        }
                                    } catch (uiError) {
                                        console.warn('Falha ao atualizar o card após mover a tarefa.', uiError);
                                    }
                                })
                                .catch((error) => {
                                    evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                                    notifyCardMovement(error?.message || 'Erro ao mover a tarefa.', 'error');
                                });
                        }
                    });

                });
            }

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.auto-dismiss');

            alerts.forEach(function (alert) {
                // tempo em ms (ex: 5000 = 5s)
                const timeout = 5000;

                setTimeout(function () {
                    // animação simples se estiver usando Tailwind
                    alert.classList.add('transition', 'opacity-0', 'translate-y-2');

                    // remove do DOM depois da animação
                    setTimeout(function () {
                        alert.remove();
                    }, 300); // 300ms pra bater com a transition
                }, timeout);
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.js-mover-coluna');

            const modal = document.getElementById('tarefa-modal');
            const statusText = document.getElementById('modal-status-text');

            async function parseQuickMoveResponse(response) {
                return await parseJsonResponse(response, 'Erro ao movimentar a tarefa.');
            }


            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    const colunaId = this.dataset.colunaId;

                    const isCancelada = modal.dataset.cancelada === '1';
                    console.log(isCancelada)
                    if (isCancelada) {
                        window.uiAlert('Esta tarefa está cancelada e não pode ser movimentada.');
                        return;
                    }

                    // Rota do método mover
                    const url = modal.dataset.moveUrl;

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            coluna_id: colunaId
                        })
                    })
                        .then(async (response) => {
                            const data = await parseQuickMoveResponse(response);
                            if (!response.ok || !data?.ok) {
                                throw new Error(data?.error || data?.message || 'Não foi possível mover a tarefa.');
                            }

                                // Se tiver um badge de status, atualiza:
                                const statusBadge = document.querySelector('#tarefa-status-label');
                                if (statusBadge && data.status_label) {
                                    statusBadge.textContent = data.status_label;
                                }

                                console.log('Movido com sucesso:', data);

                                return window.uiAlert(
                                    `Tarefa movida para ${data.status_label || 'a nova coluna'}.`,
                                    {
                                        icon: 'success',
                                        title: 'Movimentação',
                                    }
                                ).then(() => {
                                    location.reload();
                                });
                        })
                        .catch(err => {
                            console.error(err);
                            window.uiAlert(err?.message || 'Erro ao mover a tarefa.');
                        });
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('tarefa-modal');
            const textareaObsInterna = document.getElementById('modal-observacao-interna');
            const btnSalvarObs = document.getElementById('btn-salvar-observacao');

            if (btnSalvarObs && textareaObsInterna) {
                btnSalvarObs.addEventListener('click', function () {
                    const url = modal.dataset.observacaoUrl;

                    if (!url) {
                        window.uiAlert('Nenhuma tarefa selecionada para salvar observação.');
                        return;
                    }

                    const valor = textareaObsInterna.value;

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            observacao_interna: valor
                        })
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.ok) {
                                // feedback simples
                                btnSalvarObs.textContent = 'Observação salva!';
                                setTimeout(() => {
                                    btnSalvarObs.textContent = 'Salvar Observação';
                                }, 1500);
                            } else {
                                window.uiAlert('Não foi possível salvar a observação.');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            window.uiAlert('Erro ao salvar a observação.');
                        });
                });
            }
        });

    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initTailwindAutocomplete?.(
                'kanban-index-autocomplete-input',
                'kanban-index-autocomplete-list',
                @json($clienteAutocomplete ?? []),
                {
                    maxItems: 200,
                    onSelect: (_value, { input }) => {
                        input.form?.requestSubmit();
                    }
                }
            );
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnExcluir = document.getElementById('btn-excluir-tarefa');
            const modalExcluir = document.getElementById('tarefa-excluir-modal');
            const btnFecharExcluir = document.getElementById('tarefa-excluir-close');
            const btnCancelarExcluir = document.getElementById('tarefa-excluir-cancelar');
            const btnConfirmarExcluir = document.getElementById('tarefa-excluir-confirmar');
            const inputMotivo = document.getElementById('tarefa-excluir-motivo');
            const inputArquivo = document.getElementById('tarefa-excluir-arquivo');
            const baseUrl = @json(url('operacional/tarefas'));

            function openExcluirModal() {
                if (!modalExcluir) return;
                if (inputMotivo) inputMotivo.value = '';
                if (inputArquivo) inputArquivo.value = '';
                modalExcluir.classList.remove('hidden');
                modalExcluir.classList.add('flex');
            }

            function closeExcluirModal() {
                if (!modalExcluir) return;
                modalExcluir.classList.add('hidden');
                modalExcluir.classList.remove('flex');
            }

            if (btnExcluir) {
                btnExcluir.addEventListener('click', () => {
                    openExcluirModal();
                });
            }

            [btnFecharExcluir, btnCancelarExcluir].forEach((btn) => {
                if (!btn) return;
                btn.addEventListener('click', () => {
                    closeExcluirModal();
                    window.uiAlert('Exclusão cancelada.', { icon: 'success', title: 'Ok' });
                });
            });

            if (modalExcluir) {
                modalExcluir.addEventListener('click', (e) => {
                    if (e.target === modalExcluir) {
                        closeExcluirModal();
                    }
                });
            }

            if (btnConfirmarExcluir) {
                btnConfirmarExcluir.addEventListener('click', () => {
                    const idSpan = document.getElementById('modal-tarefa-id');
                    const tarefaId = idSpan ? idSpan.textContent.trim() : null;
                    const motivo = inputMotivo ? inputMotivo.value.trim() : '';

                    if (!tarefaId) {
                        window.uiAlert('ID da tarefa não encontrado.');
                        return;
                    }
                    if (!motivo) {
                        window.uiAlert('Informe o motivo da exclusão.');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('_method', 'DELETE');
                    formData.append('motivo_exclusao', motivo);
                    if (inputArquivo && inputArquivo.files && inputArquivo.files[0]) {
                        formData.append('arquivo_exclusao', inputArquivo.files[0]);
                    }

                    fetch(`${baseUrl}/${tarefaId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })
                        .then(r => r.json())
                        .then(json => {
                            if (!json.ok) {
                                window.uiAlert(json.message || 'Não foi possível excluir a tarefa.');
                                return;
                            }

                            closeExcluirModal();
                            const modal = document.getElementById('tarefa-modal');
                            if (modal) modal.classList.add('hidden');
                            window.uiAlert('Tarefa excluída com sucesso.', { icon: 'success', title: 'Sucesso' });
                            window.location.reload();
                        })
                        .catch(() => {
                            window.uiAlert('Erro na comunicação com o servidor.');
                        });
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('tarefa-modal');
            const btnEditar = document.getElementById('btn-editar-tarefa');

            if (modal && btnEditar) {
                btnEditar.addEventListener('click', function () {
                    const url = modal.dataset.editUrl;

                    if (!url) {
                        window.uiAlert('Edição ainda não está disponível para este tipo de tarefa.');
                        return;
                    }

                    // Redireciona para a tela de edição
                    window.location.href = url;
                });
            }
        });
    </script>

@endpush
