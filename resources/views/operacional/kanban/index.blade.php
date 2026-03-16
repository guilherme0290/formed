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
                        $temFiltrosAtivos = !empty($filtroBusca) || !empty($filtroServico) || !empty($filtroResponsavel) || !empty($filtroColuna) || !empty($filtroDe) || !empty($filtroAte);
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
                    <input type="hidden" name="coluna_id" value="{{ $filtroColuna }}">
                    <input type="hidden" name="de" value="{{ $filtroDe }}">
                    <input type="hidden" name="ate" value="{{ $filtroAte }}">
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
                                        $clienteTel   = optional($tarefa->cliente)->telefone ?? '';

                                        $pgr  = $tarefa->pgrSolicitacao ?? null;
                                        $ltip = $tarefa->ltipSolicitacao;

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
                                                'url'         => $anexo->url,                 // S3
                                                'mime'        => $anexo->mime_type,
                                                'tamanho'     => $anexo->tamanho_humano,      // opcional
                                                'servico'     => $anexo->servico,
                                                'uploaded_by' => optional($anexo->uploader)->name,
                                                'data'        => optional($anexo->created_at)->format('d/m/Y H:i'),
                                            ];
                                        })->values();

                                        $asoTreinamentoEsperado = 0;
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
                                            $asoTreinamentoEsperado = count($codigosTreinamentos);
                                            if ($asoTreinamentoEsperado === 0) {
                                                $asoTreinamentoEsperado = 1;
                                            }
                                        }
                                        $asoTreinamentoEnviado = $anexos->filter(function ($anexo) {
                                            return mb_strtolower((string) ($anexo->servico ?? '')) === 'certificado_treinamento';
                                        })->count();
                                        $asoTreinamentoPendente = $asoTreinamentoEsperado > 0
                                            && $asoTreinamentoEnviado < $asoTreinamentoEsperado;
                                        $documentoComplementarPgrPcmso = $anexos->first(function ($anexo) {
                                            return mb_strtolower((string) ($anexo->servico ?? '')) === 'documento_complementar_pgr_pcmso';
                                        });
                                    @endphp
                                    data-tem-anexos="{{ $anexos->isNotEmpty() ? '1' : '0' }}"
                                    data-anexos='@json($anexosPayload)'
                                    data-id="{{ $tarefa->id }}"
                                    data-cliente="{{ $clienteNome }}"
                                    data-cnpj="{{ $clienteCnpj }}"
                                    data-telefone="{{ $clienteTel }}"
                                    data-servico="{{ $servicoNome }}"
                                    data-responsavel="{{ $respNome }}"
                                    data-datahora="{{ $dataHora }}"
                                    data-sla="{{ $slaData }}"
                                    data-fim-previsto="{{ optional($tarefa->fim_previsto)->toIso8601String() }}"
                                    data-move-url="{{ route('operacional.tarefas.mover', $tarefa) }}"
                                    data-finalizar-url="{{ route('operacional.tarefas.finalizar-com-arquivo', $tarefa) }}
                                    "
                                    data-finalizar-documento-existente-url="{{ route('operacional.tarefas.finalizar-documento-existente', $tarefa) }}"
                                    data-substituir-doc-url="{{ route('operacional.tarefas.documento-cliente', $tarefa) }}"
                                    data-substituir-doc-complementar-url="{{ route('operacional.tarefas.documento-complementar', $tarefa) }}"
                                    data-prioridade="{{ ucfirst($tarefa->prioridade) }}"
                                    data-status="{{ $coluna->nome }}"
                                    data-finalizado="{{ (!empty($tarefa->finalizado_em) || ($coluna->finaliza ?? false)) ? '1' : '0' }}"
                                    data-observacoes="{{ e($obs) }}"

                                    data-funcionario="{{ $funcionarioNome . ($funcionarioCpf ? ' | CPF '.$funcionarioCpf : '') }}
                                    "
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
                                    data-is-aso="{{ $isAsoTask ? '1' : '0' }}"
                                    data-certificados-pendentes="{{ $asoTreinamentoPendente ? '1' : '0' }}"
                                    data-certificados-enviados="{{ $asoTreinamentoEnviado }}"
                                    data-certificados-total="{{ $asoTreinamentoEsperado }}"
                                    data-certificados-upload-url="{{ route('operacional.tarefas.certificados', $tarefa) }}"

                                    data-observacao-interna="{{ e($tarefa->observacao_interna) }}"
                                    data-observacao-url="{{ route('operacional.tarefas.observacao', $tarefa) }}"
                                    data-edit-url="{{ $editUrl }}"
                                    data-excluido-por="{{ $tarefa->excluidoPor?->name ?? '' }}"
                                    data-motivo-exclusao="{{ e($tarefa->motivo_exclusao ?? '') }}"


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
                                        data-pcmso-pgr-url="{{ $documentoComplementarPgrPcmso->url }}"
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
                                        @if($pcmso->pgr_arquivo_path)
                                            data-pcmso-pgr-url="{{ $pcmso->pgr_arquivo_url }}"
                                        @endif
                                    @endif
                                    @if($tarefa->treinamentoNr && $tarefa->treinamentoNrDetalhes)
                                        @php
                                            $treiFuncs = $tarefa->treinamentoNr()->with('funcionario')->get();
                                            $treiDet   = $tarefa->treinamentoNrDetalhes;
                                            $listaNomesArr = $treiFuncs->pluck('funcionario.nome')
                                                ->filter()
                                                ->sort()
                                                ->values()
                                                ->all();
                                            $listaNomes = implode(', ', $listaNomesArr);
                                            $listaFuncoes = $treiFuncs->pluck('funcionario.funcao')->join(', ');
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

                                            @if($asoTreinamentoEsperado > 0)
                                                @php
                                                    $certBadgeClass = $asoTreinamentoPendente
                                                        ? 'bg-amber-50 border-amber-200 text-amber-700'
                                                        : 'bg-emerald-50 border-emerald-200 text-emerald-700';
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $certBadgeClass }}">
                                                    Trein. {{ $asoTreinamentoEnviado }}/{{ $asoTreinamentoEsperado }}
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
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-4">
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
                            <div class="mt-2">
                                <dt class="text-[11px] text-slate-500">Responsável</dt>
                                <dd class="font-medium" id="modal-responsavel"></dd>
                            </div>

                            {{-- BLOCO ESPECÍFICO: ASO --}}

                            <div id="modal-bloco-aso" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
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
                                        <dt class="text-[11px] text-slate-500">Setor</dt>
                                        <dd class="font-medium" id="modal-funcionario-setor">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Status</dt>
                                        <dd class="font-medium" id="modal-funcionario-ativo">—</dd>
                                    </div>
                                </div>

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

                        <div class="mt-2">
                            <p class="text-[11px] font-semibold text-indigo-700">Funções</p>
                            <p id="modal-treinamento-funcoes" class="text-sm">—</p>
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
                                📎 Abrir PGR anexado (PDF)
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
                        </div>
                    </section>
                    {{-- 5. Documento final da tarefa --}}
                    <section id="modal-arquivo-wrapper"
                             class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 mt-3 hidden">
                        <h3 class="text-xs font-semibold text-emerald-700 mb-2">
                            5. DOCUMENTO FINAL DA TAREFA
                        </h3>

                        <p id="modal-arquivo-descricao" class="text-[12px] text-emerald-800 mb-2">
                            Este é o documento principal concluído desta tarefa, disponibilizado ao cliente.
                        </p>

                        <p id="modal-arquivo-status" class="text-[12px] text-emerald-900 font-semibold mb-2">
                            Status: Documento final ainda não anexado.
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
                        <div class="mt-3 flex flex-col gap-2">
                            <input
                                type="file"
                                id="modal-arquivo-replace-input"
                                class="hidden"
                                accept=".pdf,.jpg,.jpeg,.png"
                            >
                            <button
                                type="button"
                                id="modal-arquivo-replace-btn"
                                class="inline-flex items-center justify-center px-3 py-2 rounded-lg
                                   border border-emerald-200 bg-white text-emerald-700 text-xs font-semibold
                                   hover:bg-emerald-50 transition">
                                Anexar documento final
                            </button>
                            <p id="modal-arquivo-impacto" class="text-[11px] text-emerald-700/90">
                                Quando anexado, este documento ficará disponível para o cliente.
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
                            <div class="mt-3 flex flex-col gap-2">
                                <input
                                    type="file"
                                    id="modal-arquivo-complementar-input"
                                    class="hidden"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                >
                                <button
                                    type="button"
                                    id="modal-arquivo-complementar-btn"
                                    class="inline-flex items-center justify-center px-3 py-2 rounded-lg
                                       border border-emerald-200 bg-white text-emerald-700 text-xs font-semibold
                                       hover:bg-emerald-50 transition">
                                    Anexar documento complementar
                                </button>
                            </div>
                        </div>
                        <button
                            type="button"
                            id="btn-notificar-cliente"
                            class="mt-3 w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                               bg-emerald-600 text-white text-sm font-semibold shadow-sm
                               hover:bg-emerald-700 transition hidden">
                            Notificar cliente (WhatsApp)
                        </button>
                    </section>
                    {{-- 5b. Documentos da tarefa (ASO, PGR, PCMSO etc) --}}
                    <section id="modal-docs-wrapper"
                             class="bg-slate-50 border border-slate-200 rounded-xl p-4 mt-3 hidden">
                        <h3 class="text-xs font-semibold text-slate-500 mb-2">
                            DOCUMENTOS DA TAREFA
                        </h3>

                        <p class="text-[12px] text-slate-600 mb-2">
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
                        <input
                            type="file"
                            id="modal-certificados-input"
                            class="hidden"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            multiple
                        >
                        <button
                            type="button"
                            id="modal-certificados-upload-btn"
                            class="inline-flex items-center justify-center px-3 py-2 rounded-lg
                                   border border-amber-200 bg-white text-amber-700 text-xs font-semibold
                                   hover:bg-amber-50 transition">
                            Anexar certificados
                        </button>
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
            const spanFuncionarioSetor = document.getElementById('modal-funcionario-setor');
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
            const blocoAso = document.getElementById('modal-bloco-aso');

            const spanTelefone = document.getElementById('modal-telefone');
            const spanResp = document.getElementById('modal-responsavel');
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
            const textareaObsInterna = document.getElementById('modal-observacao-interna');

            const docsWrapper = document.getElementById('modal-docs-wrapper');
            const docsList = document.getElementById('modal-docs-list');
            const certificadosWrapper = document.getElementById('modal-certificados-wrapper');
            const certificadosStatus = document.getElementById('modal-certificados-status');
            const certificadosInput = document.getElementById('modal-certificados-input');
            const certificadosUploadBtn = document.getElementById('modal-certificados-upload-btn');
            const finalizarBtn = document.getElementById('modal-finalizar-btn');
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
            const spanTreinFuncs = document.getElementById('modal-treinamento-funcoes');
            const spanTreinModo = document.getElementById('modal-treinamento-modo');
            const spanTreinCodigos = document.getElementById('modal-treinamento-codigos');
            const spanTreinPacote = document.getElementById('modal-treinamento-pacote');

            // Link do documento da tarefa (arquivo_cliente_path)
            const arquivoWrapper = document.getElementById('modal-arquivo-wrapper');
            const arquivoLink = document.getElementById('modal-arquivo-link');
            const btnNotificarCliente = document.getElementById('btn-notificar-cliente');
            const arquivoReplaceInput = document.getElementById('modal-arquivo-replace-input');
            const arquivoReplaceBtn = document.getElementById('modal-arquivo-replace-btn');
            const arquivoComplementarWrapper = document.getElementById('modal-arquivo-complementar-wrapper');
            const arquivoComplementarStatus = document.getElementById('modal-arquivo-complementar-status');
            const arquivoComplementarLink = document.getElementById('modal-arquivo-complementar-link');
            const arquivoComplementarInput = document.getElementById('modal-arquivo-complementar-input');
            const arquivoComplementarBtn = document.getElementById('modal-arquivo-complementar-btn');
            const arquivoDescricao = document.getElementById('modal-arquivo-descricao');
            const arquivoStatus = document.getElementById('modal-arquivo-status');
            const arquivoAjuda = document.getElementById('modal-arquivo-ajuda');
            const arquivoImpacto = document.getElementById('modal-arquivo-impacto');
            let detalhesCurrentCard = null;

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

            function buildWhatsappMensagem(card, arquivoUrl) {
                const telefone = (card?.dataset?.telefone || '').replace(/\D/g, '');
                if (!telefone) return null;

                const servico = card?.dataset?.servico || 'documento';
                const links = arquivoUrl ? [arquivoUrl] : [];
                const linksTexto = links.length ? `\n\nLinks:\n${links.join('\n')}` : '';
                const mensagem = `Olá! Segue abaixo o anexo do ${servico}.\n\nEnviado pela Formed.${linksTexto}`;

                return { telefone, mensagem };
            }

            function getDocumentoFinalAjuda(card) {
                const servico = String(card?.dataset?.servico || '').toLowerCase();
                const isAsoTask = card?.dataset?.isAso === '1';

                if (isAsoTask) {
                    return 'Anexe o ASO final assinado/emitido para o cliente.';
                }
                if (card?.dataset?.pgrPcmso === '1') {
                    return 'Anexe os documentos finais do PGR e do PCMSO entregues ao cliente.';
                }
                if (servico.includes('pgr')) {
                    return 'Anexe o documento final do PGR entregue ao cliente.';
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

                const isCancelada = card.dataset.cancelada === '1';
                modal.dataset.cancelada = isCancelada ? '1' : '0';
                // Campos básicos
                spanId.textContent = card.dataset.id ?? '';
                spanCliente.textContent = card.dataset.cliente ?? '';
                spanCnpj.textContent = card.dataset.cnpj || '—';
                spanTelefone.textContent = card.dataset.telefone || '—';
                spanResp.textContent = card.dataset.responsavel ?? '';
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
                updateModalTempo(card, Date.now());

                spanFuncionario.textContent = card.dataset.funcionario || '—';
                spanFuncionarioFuncao.textContent = card.dataset.funcionarioFuncao || '—';
                if (spanFuncionarioNascimento) {
                    spanFuncionarioNascimento.textContent = card.dataset.funcionarioNascimento || '—';
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
                    const totalCertificados = Number(card.dataset.certificadosTotal || '0');
                    const enviadosCertificados = Number(card.dataset.certificadosEnviados || '0');
                    const pendentesCertificados = card.dataset.certificadosPendentes === '1';
                    const temDocumentoFinal = !!urlArquivo;
                    const isPgrComPcmso = card.dataset.pgrPcmso === '1';
                    const urlComplementar = card.dataset.pcmsoPgrUrl || '';
                    const temDocumentoComplementar = !!urlComplementar;

                    arquivoWrapper.classList.remove('hidden');

                    if (arquivoDescricao) {
                        arquivoDescricao.textContent = isPgrComPcmso
                            ? 'Anexe os arquivos finais do PGR e do PCMSO entregues ao cliente.'
                            : 'Este é o documento principal concluído desta tarefa, disponibilizado ao cliente.';
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
                                : 'Status: Documento final anexado.';
                        }
                        if (arquivoReplaceBtn) {
                            arquivoReplaceBtn.textContent = isPgrComPcmso ? 'Atualizar PGR' : 'Enviar nova versão';
                            arquivoReplaceBtn.title = isPgrComPcmso
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
                                : 'Status: Documento final ainda não anexado.';
                        }
                        if (arquivoReplaceBtn) {
                            arquivoReplaceBtn.textContent = isPgrComPcmso ? 'Anexar PGR' : 'Anexar documento final';
                            arquivoReplaceBtn.title = isPgrComPcmso
                                ? 'Anexar o documento final do PGR'
                                : 'Anexar o documento final da tarefa';
                        }
                        if (arquivoImpacto) {
                            arquivoImpacto.textContent = isPgrComPcmso
                                ? ''
                                : 'Quando anexado, este documento ficará disponível para o cliente.';
                        }
                    }

                    if (arquivoComplementarWrapper && arquivoComplementarStatus && arquivoComplementarLink && arquivoComplementarBtn) {
                        if (isPgrComPcmso) {
                            arquivoComplementarWrapper.classList.remove('hidden');

                            if (temDocumentoComplementar) {
                                arquivoComplementarLink.href = urlComplementar;
                                arquivoComplementarLink.classList.remove('hidden');
                                arquivoComplementarStatus.textContent = 'PCMSO: Documento anexado.';
                                arquivoComplementarBtn.textContent = 'Atualizar PCMSO';
                                arquivoComplementarBtn.title = 'Substitui o documento final do PCMSO';
                            } else {
                                arquivoComplementarLink.href = '#';
                                arquivoComplementarLink.classList.add('hidden');
                                arquivoComplementarStatus.textContent = 'PCMSO: Documento ainda não anexado.';
                                arquivoComplementarBtn.textContent = 'Anexar PCMSO';
                                arquivoComplementarBtn.title = 'Anexar o documento final do PCMSO';
                            }
                        } else {
                            arquivoComplementarWrapper.classList.add('hidden');
                            arquivoComplementarLink.href = '#';
                            arquivoComplementarLink.classList.add('hidden');
                            arquivoComplementarStatus.textContent = 'Status: Documento complementar ainda não anexado.';
                        }
                    }

                    if (btnNotificarCliente) {
                        const podeNotificar = isPgrComPcmso
                            ? (temDocumentoFinal && temDocumentoComplementar)
                            : temDocumentoFinal;
                        btnNotificarCliente.classList.toggle('hidden', !podeNotificar);
                    }

                    if (arquivoAjuda && isAsoTask && totalCertificados > 0 && !temDocumentoFinal) {
                        arquivoAjuda.textContent += ' Após anexar o documento final do ASO, os certificados de treinamento serão liberados abaixo.';
                    }

                    if (arquivoStatus && isAsoTask && totalCertificados > 0 && temDocumentoFinal) {
                        arquivoStatus.textContent += pendentesCertificados
                            ? ` Certificados pendentes: ${enviadosCertificados}/${totalCertificados}.`
                            : ` Certificados concluídos: ${enviadosCertificados}/${totalCertificados}.`;
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
                if (docsWrapper && docsList) {
                    // limpa lista anterior
                    docsList.innerHTML = '';

                    // 1) Documento final da tarefa (path_documento_cliente)
                    if (card.dataset.arquivoClienteUrl) {
                        anexos.push({
                            label: isPgrComPcmso ? 'Documento final - PGR' : 'Documento final da tarefa',
                            url: card.dataset.arquivoClienteUrl
                        });
                    }

                    // 2) PGR anexado ao PCMSO (se existir)
                    if (card.dataset.pcmsoPgrUrl) {
                        anexos.push({
                            label: isPgrComPcmso ? 'Documento final - PCMSO' : 'PGR anexado (PCMSO)',
                            url: card.dataset.pcmsoPgrUrl
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

                    const anexosDocs = anexos.filter(a => !a || a.servico !== 'cancelamento_tarefa');

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
                                                data-doc-id="${a.id || ''}">
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
                    const temDocumentoAso = !!card.dataset.arquivoClienteUrl;

                    if (isAso && total > 0) {
                        certificadosWrapper.classList.remove('hidden');
                        if (certificadosUploadBtn) {
                            certificadosUploadBtn.disabled = !temDocumentoAso;
                            certificadosUploadBtn.classList.toggle('opacity-60', !temDocumentoAso);
                            certificadosUploadBtn.classList.toggle('cursor-not-allowed', !temDocumentoAso);
                        }

                        if (!temDocumentoAso) {
                            certificadosStatus.textContent = `Esta tarefa espera ${total} certificado(s). Anexe primeiro o documento final do ASO para liberar o envio.`;
                        } else {
                            certificadosStatus.textContent = pendentes
                                ? `Aguardando certificados: ${enviados}/${total}.`
                                : `Certificados concluídos: ${enviados}/${total}.`;
                        }
                    } else {
                        certificadosWrapper.classList.add('hidden');
                        certificadosStatus.textContent = '—';
                        if (certificadosUploadBtn) {
                            certificadosUploadBtn.disabled = false;
                            certificadosUploadBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                        }
                    }
                }

                if (finalizarBtn) {
                    const temDocumentoFinal = !!card.dataset.arquivoClienteUrl;
                    const precisaDocumentoComplementar = card.dataset.pgrPcmso === '1';
                    const temDocumentoComplementar = !!card.dataset.pcmsoPgrUrl;
                    const podeFinalizar = temDocumentoFinal && (!precisaDocumentoComplementar || temDocumentoComplementar);

                    finalizarBtn.disabled = !podeFinalizar;
                    finalizarBtn.classList.toggle('opacity-60', !podeFinalizar);
                    finalizarBtn.classList.toggle('cursor-not-allowed', !podeFinalizar);
                }

                if (exclusaoAnexoWrapper && exclusaoAnexoList) {
                    const anexosCancelamento = anexos.filter(a => a && a.servico === 'cancelamento_tarefa');
                    if (anexosCancelamento.length) {
                        exclusaoAnexoWrapper.classList.remove('hidden');
                        exclusaoAnexoList.innerHTML = anexosCancelamento.map(a => `
                            <li>
                                <a href="${a.url}" target="_blank" class="underline text-[13px] font-medium">
                                    ${a.nome || 'Print do cancelamento'}
                                </a>
                                <span class="text-[11px] text-slate-500">
                                    (${a.tamanho || '-'} · ${a.mime || '-'}${a.data ? ' · ' + a.data : ''}${a.uploaded_by ? ' · ' + a.uploaded_by : ''})
                                </span>
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

                // ASO
                // ASO
                if (blocoAso) {
                    if (isAso) {
                        blocoAso.classList.remove('hidden');

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
                        if (spanFuncionarioSetor) {
                            spanFuncionarioSetor.textContent = card.dataset.funcionarioSetor || '—';
                        }
                        if (spanFuncionarioAtivo) {
                            spanFuncionarioAtivo.textContent = card.dataset.funcionarioAtivo || '—';
                        }
                        if (spanFuncionarioCelular) {
                            spanFuncionarioCelular.textContent = formatTelefone(card.dataset.funcionarioCelular || '');
                        }
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
                        spanFuncionario.textContent = '—';
                        spanFuncionarioFuncao.textContent = '—';
                        if (spanFuncionarioCpf) spanFuncionarioCpf.textContent = '—';
                        if (spanFuncionarioRg) spanFuncionarioRg.textContent = '—';
                        if (spanFuncionarioNascimento) spanFuncionarioNascimento.textContent = '—';
                        if (spanFuncionarioAdmissao) spanFuncionarioAdmissao.textContent = '—';
                        if (spanFuncionarioSetor) spanFuncionarioSetor.textContent = '—';
                        if (spanFuncionarioAtivo) spanFuncionarioAtivo.textContent = '—';
                        if (spanFuncionarioCelular) spanFuncionarioCelular.textContent = '—';
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
                    const funcoes = card.dataset.treinamentoFuncoes || '—';
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
                    spanTreinFuncs.textContent = funcoes;
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
                    moverBtns.forEach(b => toggleBtn(b, true));
                } else {
                    toggleBtn(btnEditar, false);
                    toggleBtn(btnSalvarObs, false);
                    toggleBtn(btnExcluir, false);
                    moverBtns.forEach(b => toggleBtn(b, false));
                }



                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                if (!modal) return;
                if (modal.classList.contains('hidden')) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                window.location.reload();
            }

            function hideModalWithoutReload() {
                if (!modal) return;
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
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            if (btnNotificarCliente) {
                btnNotificarCliente.addEventListener('click', function () {
                    if (!detalhesCurrentCard) return;
                    const arquivoUrl = detalhesCurrentCard.dataset.arquivoClienteUrl || '';
                    if (!arquivoUrl) {
                        window.uiAlert('Nenhum documento anexado para enviar.');
                        return;
                    }

                    const payload = buildWhatsappMensagem(detalhesCurrentCard, arquivoUrl);
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

                    if (options.fromDetalhes) {
                        openDetalhesModal(card);
                        showPendenciaInline(`${data.message || 'A tarefa ainda possui pendencias.'} Deseja continuar alterando a tarefa agora para anexar o que falta?`);
                        return;
                    }

                    window.location.reload();
                    return;
                }

                hidePendenciaInline();

                if (options.closeModal) {
                    closeFinalizarModal();
                }

                if (data.message) {
                    window.uiAlert(data.message, {
                        icon: 'success',
                        title: 'Sucesso',
                    });
                }

                setTimeout(() => window.location.reload(), 250);
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
                            const raw = await r.text();
                            let data = null;

                            if (raw) {
                                try {
                                    data = JSON.parse(raw);
                                } catch (error) {
                                    const jsonStart = raw.indexOf('{');
                                    const jsonEnd = raw.lastIndexOf('}');
                                    if (jsonStart !== -1 && jsonEnd !== -1 && jsonEnd > jsonStart) {
                                        try {
                                            data = JSON.parse(raw.slice(jsonStart, jsonEnd + 1));
                                        } catch (innerError) {
                                            data = null;
                                        }
                                    }
                                }
                            }

                            if (!r.ok) {
                                const error =
                                    data?.error
                                    || data?.message
                                    || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                    || 'Erro ao finalizar tarefa.';
                                throw new Error(error);
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
                            window.uiAlert(error?.message || 'Erro ao finalizar tarefa.');
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

            async function parseJsonResponse(response) {
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    return null;
                }

                try {
                    return await response.json();
                } catch (error) {
                    return null;
                }
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
                        const contentType = r.headers.get('content-type') || '';
                        const isJson = contentType.includes('application/json');
                        const data = isJson ? await r.json() : null;

                        if (!r.ok) {
                            const error =
                                data?.error
                                || data?.message
                                || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                || 'Erro ao enviar documento.';
                            throw new Error(error);
                        }

                        return data;
                    })
                    .then((data) => {
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
                    })
                    .catch((error) => {
                        window.uiAlert(error?.message || 'Erro ao enviar documento.');
                    });
            }

            function uploadDocumentoComplementar(file) {
                if (!file || !detalhesCurrentCard) return;
                const url = detalhesCurrentCard.dataset.substituirDocComplementarUrl;
                if (!url) {
                    window.uiAlert('Não foi possível enviar o documento complementar desta tarefa.');
                    return;
                }

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
                        const contentType = r.headers.get('content-type') || '';
                        const isJson = contentType.includes('application/json');
                        const data = isJson ? await r.json() : null;

                        if (!r.ok) {
                            const error =
                                data?.error
                                || data?.message
                                || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                || 'Erro ao enviar documento complementar.';
                            throw new Error(error);
                        }

                        return data;
                    })
                    .then((data) => {
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
                            openDetalhesModal(detalhesCurrentCard);
                        }
                    })
                    .catch((error) => {
                        window.uiAlert(error?.message || 'Erro ao enviar documento complementar.');
                    });
            }

            function uploadCertificadosTreinamento(files) {
                if (!files?.length || !detalhesCurrentCard) return;
                const url = detalhesCurrentCard.dataset.certificadosUploadUrl;
                if (!url) {
                    window.uiAlert('Não foi possível enviar os certificados desta tarefa.');
                    return;
                }

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
                        const contentType = r.headers.get('content-type') || '';
                        const data = contentType.includes('application/json') ? await r.json() : null;
                        if (!r.ok) {
                            const error =
                                data?.error
                                || data?.message
                                || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                || 'Erro ao enviar certificados.';
                            throw new Error(error);
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
                        if (data.status_label) {
                            detalhesCurrentCard.dataset.status = String(data.status_label);
                        }

                        if (certificadosInput) {
                            certificadosInput.value = '';
                        }
                        openDetalhesModal(detalhesCurrentCard);
                    })
                    .catch((error) => {
                        window.uiAlert(error?.message || 'Erro ao enviar certificados.');
                    });
            }

            if (arquivoReplaceBtn && arquivoReplaceInput) {
                arquivoReplaceBtn.addEventListener('click', function () {
                    if (arquivoReplaceInput) {
                        arquivoReplaceInput.value = '';
                        arquivoReplaceInput.click();
                    }
                });

                arquivoReplaceInput.addEventListener('change', function () {
                    const file = arquivoReplaceInput.files?.[0];
                    if (!file) return;
                    uploadDocumentoClienteTemporario(file);
                });
            }

            if (arquivoComplementarBtn && arquivoComplementarInput) {
                arquivoComplementarBtn.addEventListener('click', function () {
                    arquivoComplementarInput.value = '';
                    arquivoComplementarInput.click();
                });

                arquivoComplementarInput.addEventListener('change', function () {
                    const file = arquivoComplementarInput.files?.[0];
                    if (!file) return;
                    uploadDocumentoComplementar(file);
                });
            }

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

            if (docsList) {
                docsList.addEventListener('click', async function (event) {
                    const btn = event.target.closest('[data-doc-delete-url]');
                    if (!btn || !detalhesCurrentCard) return;

                    const url = btn.dataset.docDeleteUrl;
                    const docId = String(btn.dataset.docId || '');
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

                        openDetalhesModal(detalhesCurrentCard);
                    } catch (error) {
                        window.uiAlert(error?.message || 'Erro ao excluir o documento.');
                    } finally {
                        btn.disabled = false;
                    }
                });
            }

            if (certificadosUploadBtn && certificadosInput) {
                certificadosUploadBtn.addEventListener('click', function () {
                    certificadosInput.value = '';
                    certificadosInput.click();
                });

                certificadosInput.addEventListener('change', function () {
                    if (!certificadosInput.files?.length) return;
                    uploadCertificadosTreinamento(certificadosInput.files);
                });
            }

            if (finalizarBtn) {
                finalizarBtn.addEventListener('click', async function () {
                    if (!detalhesCurrentCard) return;

                    const url = detalhesCurrentCard.dataset.finalizarDocumentoExistenteUrl;
                    if (!url) {
                        window.uiAlert('Não foi possível finalizar esta tarefa.');
                        return;
                    }

                    finalizarBtn.disabled = true;
                    try {
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

                        await handleFinalizacaoResponse(detalhesCurrentCard, data, { fromDetalhes: true });
                    } catch (error) {
                        window.uiAlert(error?.message || 'Erro ao finalizar tarefa.');
                    } finally {
                        finalizarBtn.disabled = false;
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
                pendenciaFecharBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    hidePendenciaInline();
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
                        onEnd: function (evt) {
                            const card = evt.item;
                            const colunaId = card.closest('.kanban-column').dataset.colunaId;
                            const colunaEl = card.closest('.kanban-column');
                            const colunaCor = colunaEl?.dataset.colunaCor || '#38bdf8';
                            const colunaSlug = colunaEl?.dataset.colunaSlug || '';
                            const moveUrl = card.dataset.moveUrl;
                            const colunaOrigemEl = evt.from;
                            const colunaOrigemId = colunaOrigemEl?.dataset?.colunaId || '';

                            // segurança extra: se por algum motivo chegou aqui, não processa
                            if (card.dataset.cancelada === '1') {
                                evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                                return;
                            }

                            // ajusta cor da borda com a cor da coluna
                            const cardColor = resolveCardBorderColor(card, colunaEl);
                            if (cardColor) {
                                card.style.borderLeftColor = cardColor;
                            }

                            // Se soltou na coluna "finalizada": NÃO chama mover(),
                            // abre o modal de finalizar com arquivo.

                            if (colunaSlug === 'finalizada') {
                                const finalizarUrl = card.dataset.finalizarUrl;
                                if (finalizarUrl) {
                                    openFinalizarModal(card, finalizarUrl);
                                } else {
                                    window.location.reload();
                                }
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
                                .then(response => parseJsonResponse(response))
                                .then(data => {
                                    if (!data || !data.ok) return;

                                    const colunaSection = card.closest('section');
                                    const headerTitleEl = colunaSection
                                        ? colunaSection.querySelector('header h2')
                                        : null;

                                    const statusName = data.status_label
                                        || (headerTitleEl ? headerTitleEl.textContent.trim() : '');

                                    const statusSpan = card.querySelector('[data-role="card-status-label"]');
                                    if (statusSpan && statusName) {
                                        statusSpan.textContent = statusName;
                                    }
                                    if (statusName) {
                                        card.dataset.status = statusName;
                                    }
                                    card.dataset.finalizado = (colunaSlug === 'finalizada') ? '1' : '0';

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
                                })
                                .catch(() => {
                                    // aqui dá pra colocar um toast se quiser
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
                        .then(response => parseJsonResponse(response))
                        .then(data => {
                            if (data?.ok) {
                                // Se tiver um badge de status, atualiza:
                                const statusBadge = document.querySelector('#tarefa-status-label');
                                if (statusBadge && data.status_label) {
                                    statusBadge.textContent = data.status_label;
                                }

                                // Opcional: recarregar página/fechar modal
                                location.reload();

                                console.log('Movido com sucesso:', data);
                            } else {
                                window.uiAlert(data?.error || data?.message || 'Não foi possível mover a tarefa.');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            window.uiAlert('Erro ao mover a tarefa.');
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
                { maxItems: 200 }
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
