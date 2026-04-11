@php
        $isEdit = isset($parametro) && $parametro;
        $initialData = [
            'isEdit' => (bool) $isEdit,
            'itens' => [],
            'esocial' => null,
            'gheConfigs' => [],
        ];

        if ($isEdit) {
            $initialData['itens'] = $parametro->itens
                ->map(function ($it) {
                    return [
                        'id' => 'db_' . $it->id,
                        'servico_id' => $it->servico_id,
                        'tipo' => $it->tipo,
                        'nome' => $it->nome,
                        'descricao' => $it->descricao,
                        'valor_unitario' => (float) $it->valor_unitario,
                        'quantidade' => (int) $it->quantidade,
                        'prazo' => $it->prazo,
                        'acrescimo' => (float) ($it->acrescimo ?? 0),
                        'desconto' => (float) ($it->desconto ?? 0),
                        'meta' => $it->meta,
                        'valor_total' => (float) $it->valor_total,
                    ];
                })
                ->values()
                ->toArray();

            $initialData['esocial'] = [
                'enabled' => (bool) $parametro->incluir_esocial,
                'qtd' => (int) ($parametro->esocial_qtd_funcionarios ?? 0),
                'valor' => (float) ($parametro->esocial_valor_mensal ?? 0),
            ];
        }

        if (!empty($parametroAsoGrupos)) {
            $initialData['gheConfigs'] = collect($parametroAsoGrupos)
                ->groupBy('cliente_ghe_id')
                ->map(function ($rows) {
                    $first = $rows->first();
                    return [
                        'cliente_ghe_id' => $first?->cliente_ghe_id,
                        'ghe_id' => $first?->clienteGhe?->ghe_id,
                        'ghe_nome' => $first?->clienteGhe?->nome,
                        'tipos' => $rows
                            ->mapWithKeys(function ($row) {
                                return [
                                    $row->tipo_aso => [
                                        'grupo_id' => (int) ($row->grupo_exames_id ?? 0),
                                        'grupo_titulo' => $row->grupo?->titulo,
                                        'total_exames' => (float) ($row->total_exames ?? 0),
                                    ],
                                ];
                            })
                            ->toArray(),
                    ];
                })
                ->values()
                ->toArray();
        }

        $unidadesDisponiveis = collect($unidadesDisponiveis ?? []);
        $unidadesPermitidasSalvas = collect($unidadesPermitidasIds ?? [])
            ->map(fn ($id) => (int) $id)
            ->values();
        $unidadesPermitidasOld = old('unidades_permitidas');
        if (is_array($unidadesPermitidasOld)) {
            $unidadesSelecionadas = collect($unidadesPermitidasOld)
                ->map(fn ($id) => (int) $id)
                ->values();
        } elseif ($unidadesPermitidasSalvas->isNotEmpty()) {
            $unidadesSelecionadas = $unidadesPermitidasSalvas;
        } else {
            $unidadesSelecionadas = $unidadesDisponiveis->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();
        }
        $funcoes = collect($funcoes ?? []);
        $funcoesSelecionadas = collect(old('funcoes_cliente', $clienteFuncoesIds ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();
        $funcionariosPorFuncao = collect($funcionariosPorFuncao ?? []);
        $ghesPorFuncao = collect($ghesPorFuncao ?? []);
        $routeFuncoesStore = route($routePrefix . '.parametros.funcoes.store', $cliente);
        $funcoesCrudPrefix = str_starts_with($routePrefix, 'comercial.') ? 'comercial.funcoes' : 'master.funcoes';
        $routeFuncoesUpdate = route($funcoesCrudPrefix . '.update', ['funcao' => '__ID__']);
        $routeFuncoesDestroy = route($funcoesCrudPrefix . '.destroy', ['funcao' => '__ID__']);
        $clienteTemGheComFuncoes = (bool) ($clienteTemGheComFuncoes ?? false);
        $parametroTabInicial = old('parametro_tab', $clienteTemGheComFuncoes ? 'aso-tipos' : 'funcoes');
    @endphp
<div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6" data-tabs-scope="parametro">
        <form id="parametroForm" method="POST" novalidate
              action="{{ route($routePrefix.'.parametros.save', $cliente) }}">
            @csrf

            <div class="fixed top-20 left-4 right-4 sm:left-auto sm:right-6 z-40">
                <div id="itemToast" class="hidden pointer-events-auto rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg flex items-start gap-3 transition-all duration-200 opacity-0 translate-y-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-white text-xs font-semibold">
                        ?
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold">Item adicionado</div>
                        <div id="itemToastText" class="text-xs text-emerald-700 truncate"></div>
                    </div>
                </div>
            </div>
            <div class="fixed top-20 left-4 right-4 sm:left-auto sm:right-6 z-40 mt-16">
                <div id="itemAlert" class="hidden pointer-events-auto rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-lg transition-all duration-200 opacity-0 translate-y-2">
                    <span id="itemAlertText"></span>
                </div>
            </div>

            <div data-tab-panel="parametros" data-tab-panel-root="cliente" class="hidden">
                <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b bg-emerald-600 text-white">
                        <h1 class="text-lg font-semibold">
                            {{ $isEdit ? 'Editar Parâmetros do Cliente' : 'Definir Parâmetros do Cliente' }}
                        </h1>
                    </div>

                    <div class="p-6 space-y-8">

                    <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                    <input type="hidden" name="parametro_tab" id="parametro_tab" value="{{ $parametroTabInicial }}">
                    <input type="hidden" name="forma_pagamento" value="{{ $parametro?->forma_pagamento ?? '' }}">
                    <input type="hidden" name="email_envio_fatura" value="{{ $parametro?->email_envio_fatura ?? '' }}">
                    <input type="hidden" name="vencimento_servicos" value="{{ $parametro?->vencimento_servicos ?? '' }}">

                    {{-- 1. Serviços --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-black">1. Serviços</h2>

                        <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4" data-tabs-scope="parametro-servicos">
                            <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 mb-4" data-tabs="parametro">
                                <button type="button"
                                        class="px-4 py-2 rounded-full text-sm font-semibold bg-blue-600 text-white"
                                        data-tab="servicos">
                                    Serviços
                                </button>
                                <button type="button"
                                        class="relative px-4 py-2 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-100"
                                        data-tab="aso-tipos">
                                    ASO
                                </button>
                                <button type="button"
                                        class="relative px-4 py-2 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-100"
                                        data-tab="treinamentos">
                                    Treinamentos
                                    <span id="badgeTabTreinamentos" class="hidden absolute -top-1 -right-1 h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                </button>
                                <button type="button"
                                        class="relative px-4 py-2 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-100"
                                        data-tab="funcoes">
                                    Funções do PGR
                                </button>
                            </div>

                            <div data-tab-panel="servicos" class="space-y-3">
                                <div class="text-sm font-semibold text-black">Serviços</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                    @php
                                        $servicoTreinamentoId = (int) config('services.treinamento_id');
                                        $servicoExameId = (int) config('services.exame_id');
                                        $servicoAsoId = (int) (config('services.aso_id') ?? 0);
                                        $servicoArtDisponivel = $servicoArtDisponivel ?? null;
                                        $artRenderizado = false;
                                        $serviceButtonStyles = [
                                            'aso' => 'border-slate-200 border-l-4 border-l-sky-500 bg-white text-sky-900 hover:bg-sky-50',
                                            'pgr' => 'border-slate-200 border-l-4 border-l-emerald-500 bg-white text-emerald-900 hover:bg-emerald-50',
                                            'pcmso' => 'border-slate-200 border-l-4 border-l-purple-500 bg-white text-purple-900 hover:bg-purple-50',
                                            'ltcat' => 'border-slate-200 border-l-4 border-l-orange-500 bg-white text-orange-900 hover:bg-orange-50',
                                            'ltip' => 'border-slate-200 border-l-4 border-l-red-600 bg-white text-red-900 hover:bg-red-50',
                                            'apr' => 'border-slate-200 border-l-4 border-l-amber-600 bg-white text-amber-900 hover:bg-amber-50',
                                            'art' => 'border-slate-200 border-l-4 border-l-cyan-600 bg-white text-cyan-900 hover:bg-cyan-50',
                                            'pae' => 'border-slate-200 border-l-4 border-l-rose-600 bg-white text-rose-900 hover:bg-rose-50',
                                            'default' => 'border-slate-200 border-l-4 border-l-slate-500 bg-white text-slate-700 hover:bg-slate-50',
                                        ];
                                    @endphp
                                    @foreach($servicos as $servico)
                                        @php
                                            $nomeServico = mb_strtolower((string) $servico->nome, 'UTF-8');
                                        @endphp
                                        @if(
                                            (int) $servico->id === $servicoTreinamentoId
                                            || (int) $servico->id === $servicoExameId
                                            || ($servicoAsoId > 0 && (int) $servico->id === $servicoAsoId)
                                            || $nomeServico === 'aso'
                                        )
                                            @continue
                                        @endif
                                        @php
                                            $colorKey = 'default';
                                            if (str_contains($nomeServico, 'pgr')) {
                                                $colorKey = 'pgr';
                                            } elseif (str_contains($nomeServico, 'pcmso')) {
                                                $colorKey = 'pcmso';
                                            } elseif (str_contains($nomeServico, 'ltcat')) {
                                                $colorKey = 'ltcat';
                                            } elseif (str_contains($nomeServico, 'ltip')) {
                                                $colorKey = 'ltip';
                                            } elseif (str_contains($nomeServico, 'apr')) {
                                                $colorKey = 'apr';
                                            } elseif (str_contains($nomeServico, 'pae')) {
                                                $colorKey = 'pae';
                                            }
                                            $btnServiceClass = $serviceButtonStyles[$colorKey] ?? $serviceButtonStyles['default'];
                                        @endphp
                                        @php
                                            if ($nomeServico === 'art') {
                                                $artRenderizado = true;
                                            }
                                        @endphp
                                        <button type="button"
                                                class="w-full px-3 py-2 rounded-xl border text-sm {{ $btnServiceClass }}"
                                                data-action="add-servico"
                                                data-servico-id="{{ $servico->id }}"
                                                data-servico-nome="{{ e($servico->nome) }}">
                                            + {{ $servico->nome }}
                                        </button>
                                    @endforeach
                                    @if(!$artRenderizado && $servicoArtDisponivel)
                                        <button type="button"
                                                class="w-full px-3 py-2 rounded-xl border text-sm {{ $serviceButtonStyles['art'] }}"
                                                data-action="add-servico"
                                                data-servico-id="{{ $servicoArtDisponivel->id }}"
                                                data-servico-nome="{{ e($servicoArtDisponivel->nome) }}">
                                            + {{ $servicoArtDisponivel->nome }}
                                        </button>
                                    @endif
                                    <button type="button"
                                            id="btnToggleEsocial"
                                            class="w-full px-3 py-2 rounded-xl border border-slate-200 border-l-4 border-l-violet-600 bg-white text-violet-800 text-sm hover:bg-violet-50">
                                        + eSocial
                                    </button>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <input type="checkbox" id="chkEsocial" name="incluir_esocial" value="1"
                                           class="hidden"
                                           @checked(old('incluir_esocial', $isEdit ? $parametro->incluir_esocial : false))>

                                    <div id="esocialBox" class="hidden rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                        <div class="grid md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="text-xs font-semibold text-slate-700">Qtd colaboradores</label>
                                                <input id="esocialQtd" type="number" min="1"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                                       placeholder="Ex.: 12"
                                                       value="{{ old('esocial_qtd_funcionarios', $isEdit ? $parametro->esocial_qtd_funcionarios : '') }}">
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-slate-700">Valor mensal</label>
                                                <input id="esocialValorView" type="text" readonly
                                                       class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 bg-white"
                                                       value="R$ 0,00">
                                                <input type="hidden" name="esocial_qtd_funcionarios" id="esocialQtdHidden">
                                                <input type="hidden" name="esocial_valor_mensal" id="esocialValorHidden"
                                                       value="{{ old('esocial_valor_mensal', $isEdit ? $parametro->esocial_valor_mensal : '0.00') }}">
                                            </div>
                                        </div>
                                        <p id="esocialAviso" class="mt-3 text-sm text-amber-700 hidden"></p>
                                    </div>
                                </div>
                            </div>

                        {{--
                        BLOCO: Exames
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-sm font-semibold text-slate-800">Exames</div>

                                <button type="button"
                                        class="px-3 py-2 rounded-xl border border-blue-200 text-sm bg-blue-50 hover:bg-blue-100"
                                        id="btnPacoteExames">
                                    + Pacote de Exames
                                </button>
                            </div>

                            <p class="text-xs text-slate-500">Selecione exames avulsos ou crie um "Pacote de Exames".</p>

                            <div id="examesAvulsos" class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                <div class="text-sm text-slate-500">Carregando exames...</div>
                            </div>
                        </div>
                        --}}

                            <div data-tab-panel="aso-tipos" class="hidden space-y-3">
                                <div class="text-sm font-semibold text-slate-800">ASO por GHE</div>

                                @php
                                    $asoTipos = [
                                        'admissional' => 'Admissional',
                                        'periodico' => 'Periódico',
                                        'demissional' => 'Demissional',
                                        'mudanca_funcao' => 'Mudança de Função',
                                        'retorno_trabalho' => 'Retorno ao Trabalho',
                                    ];
                                @endphp

                                <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                                    <div class="text-xs text-slate-500">
                                        Configure um GHE por vez. Após finalizar, adicione-o à lista abaixo.
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <select id="gheSelect"
                                                class="min-w-[220px] rounded-md border-slate-200 text-sm px-2 py-2">
                                            <option value="">Selecione o GHE...</option>
                                        </select>
                                        <button type="button"
                                                class="px-3 py-2 rounded-xl border border-blue-200 text-sm bg-blue-50 hover:bg-blue-100 text-blue-800 font-semibold"
                                                id="btnGheGlobal">
                                            Gerenciar GHE
                                        </button>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="flex items-center justify-between px-3 py-2 bg-slate-50">
                                        <div class="text-sm font-semibold text-slate-800">
                                            Grupos de Exames por Tipo de ASO — <span id="asoGheTitle" class="text-emerald-700">—</span>
                                        </div>
                                        <button type="button"
                                                class="px-2.5 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold hover:bg-emerald-100"
                                                onclick="openProtocolosModal({ clienteId: Number(document.querySelector('[name=&quot;cliente_id&quot;]')?.value || 0) || null })">
                                            + Novo Grupo
                                        </button>
                                    </div>
                                    <div class="hidden md:grid grid-cols-12 gap-2 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                                        <div class="col-span-2">Tipo de ASO</div>
                                        <div class="col-span-5">Grupo de exames</div>
                                        <div class="col-span-3">Exames</div>
                                        <div class="col-span-2 text-right">Valor</div>
                                    </div>
                                    <div class="divide-y divide-slate-200" id="asoTipoRows"></div>
                                    <div class="px-3 py-3 bg-white flex justify-end">
                                        <button type="button"
                                                class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold"
                                                id="btnAddGheConfig">
                                            + Adicionar este GHE à lista
                                        </button>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="px-3 py-2 bg-slate-50">
                                        <div class="text-sm font-semibold text-slate-800">Valores de ASO por GHE</div>
                                        <div class="text-xs text-slate-500">Visualização dos valores por tipo de ASO para todos os GHEs configurados.</div>
                                    </div>
                                    <div id="asoGheValoresGrid" class="p-3 grid gap-3 md:grid-cols-2"></div>
                                    <div class="px-3 py-2 text-xs text-slate-500 bg-emerald-50/60">
                                        O sistema aplicará automaticamente esses protocolos conforme o GHE do funcionário no momento da criação do ASO.
                                    </div>
                                </div>
                            </div>

                            <div data-tab-panel="treinamentos" class="hidden space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-slate-800">Treinamentos</div>

                                    <button type="button"
                                            class="px-3 py-2 rounded-xl border border-emerald-200 text-sm bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-semibold"
                                            id="btnPacoteTreinamentos">
                                        + Pacote de Treinamentos
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                    @foreach($treinamentos as $t)
                                        <button type="button"
                                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-left text-sm bg-white hover:bg-slate-50"
                                                data-action="add-treinamento"
                                                data-nr-id="{{ $t->id }}"
                                                data-nr-codigo="{{ e($t->codigo) }}"
                                                data-nr-titulo="{{ e($t->titulo) }}">
                                            <div class="font-semibold text-slate-800">{{ $t->codigo }}</div>
                                            <div class="text-xs text-slate-500">#{{ $t->id }} — {{ $t->titulo }}</div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div data-tab-panel="funcoes" class="hidden space-y-3">
                                <div class="rounded-2xl border {{ $clienteTemGheComFuncoes ? 'border-sky-200 bg-sky-50' : 'border-amber-200 bg-amber-50' }} p-4">
                                    <div class="text-sm font-semibold {{ $clienteTemGheComFuncoes ? 'text-sky-900' : 'text-amber-900' }}">
                                        {{ $clienteTemGheComFuncoes ? 'Cliente com GHE configurado' : 'Cliente sem GHE configurado' }}
                                    </div>
                                    <div class="mt-1 text-xs {{ $clienteTemGheComFuncoes ? 'text-sky-800' : 'text-amber-800' }}">
                                        {{ $clienteTemGheComFuncoes
                                            ? 'Para este cliente, o PGR usará primeiro as funções do GHE.'
                                            : 'Para este cliente, o PGR usará somente as funções marcadas aqui.' }}
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="grid gap-4 lg:grid-cols-[360px_minmax(0,1fr)]">
                                        <div class="rounded-2xl border border-blue-200 bg-white p-4 space-y-3">
                                            <div>
                                                <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">1. Cadastrar ou localizar</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-800">Função para uso no PGR</div>
                                                <div class="text-xs text-slate-500">Se a função já existir, o sistema avisará e selecionará a função para este cliente.</div>
                                            </div>
                                            <div class="space-y-2">
                                                <input type="text"
                                                       id="novaFuncaoNome"
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"
                                                       placeholder="Nova função">
                                                <div class="flex flex-wrap gap-2">
                                                    <button type="button"
                                                            id="btnCancelarEdicaoFuncao"
                                                            class="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                                        Cancelar
                                                    </button>
                                                    <button type="button"
                                                            id="btnNovaFuncaoParametro"
                                                            class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-100">
                                                        + Adicionar função
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-2xl border border-emerald-200 bg-white p-4 space-y-3">
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">2. Funções liberadas no PGR</div>
                                                    <div class="mt-1 text-sm font-semibold text-slate-800">Selecione as funções disponíveis para este cliente</div>
                                                    <div class="text-xs text-slate-500">Somente as funções marcadas aqui serão usadas como fallback do PGR quando o cliente não tiver GHE com funções.</div>
                                                </div>
                                                <span id="funcoesClienteResumo" class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">
                                                    0 selecionadas
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <input type="text"
                                                       id="funcoesClienteBusca"
                                                       class="min-w-[220px] flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                               placeholder="Buscar função">
                                                <button type="button"
                                                        id="btnSelecionarTodasFuncoes"
                                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                                    Selecionar todas
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-3">
                                    <div id="funcoesClienteGrid" class="max-h-[22rem] overflow-y-auto pr-1 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        @forelse($funcoes as $funcao)
                                            @php
                                                $funcaoId = (int) $funcao->id;
                                                $qtdFuncionarios = (int) ($funcionariosPorFuncao->get($funcaoId) ?? 0);
                                                $qtdGhes = (int) ($ghesPorFuncao->get($funcaoId) ?? 0);
                                                $funcaoAtiva = (bool) ($funcao->ativo ?? true);
                                            @endphp
                                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 transition-colors hover:border-emerald-200 {{ $funcaoAtiva ? '' : 'opacity-70' }}"
                                                   data-funcao-card
                                                   data-funcao-id="{{ $funcaoId }}"
                                                   data-funcao-nome="{{ e($funcao->nome) }}"
                                                   data-funcao-ativo="{{ $funcaoAtiva ? '1' : '0' }}"
                                                   data-funcao-funcionarios="{{ $qtdFuncionarios }}"
                                                   data-funcao-ghes="{{ $qtdGhes }}">
                                                <input type="checkbox"
                                                       name="funcoes_cliente[]"
                                                       value="{{ $funcaoId }}"
                                                       class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                                       @checked($funcoesSelecionadas->contains($funcaoId))>
                                                <span class="min-w-0 flex-1">
                                                    <span class="flex items-center justify-between gap-2">
                                                        <span class="block truncate text-sm font-semibold text-slate-800" data-role="funcao-title">{{ $funcao->nome }}</span>
                                                        <span class="flex items-center gap-2 shrink-0">
                                                            @unless($funcaoAtiva)
                                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700" data-role="funcao-status">Inativa</span>
                                                            @else
                                                                <span class="hidden rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700" data-role="funcao-status">Inativa</span>
                                                            @endunless
                                                            <button type="button"
                                                                    class="text-[11px] font-semibold text-blue-700 hover:text-blue-800"
                                                                    data-action="edit-funcao">
                                                                Editar
                                                            </button>
                                                        </span>
                                                    </span>
                                                    <span class="mt-1 block text-[11px] text-slate-500" data-role="funcao-meta">
                                                        Funcionários: {{ $qtdFuncionarios }} | GHEs: {{ $qtdGhes }}
                                                    </span>
                                                </span>
                                            </label>
                                        @empty
                                            <div class="col-span-full rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                                Nenhuma função cadastrada para a empresa.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm">
                                        Salvar Funções do PGR
                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- Lista itens (compacto) --}}
                        <div class="mt-5">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-semibold text-black">Serviços Adicionados</h3>
                                    <p class="text-xs text-slate-500">Revise os itens e ajuste valores antes de salvar.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span id="lista-itens-count" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        0 itens
                                    </span>
                                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">
                                        <span>Total</span>
                                        <span id="valor-total-display">R$ 0,00</span>
                                    </span>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                                <div class="hidden md:grid grid-cols-12 gap-2 border-b border-slate-200 bg-slate-50/90 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600">
                                    <div class="col-span-7">Item</div>
                                    <div class="col-span-2">Valor</div>
                                    <div class="col-span-2 text-right">Total</div>
                                    <div class="col-span-1 text-center">Ação</div>
                                </div>
                                <div class="min-h-[18rem] max-h-[58vh] overflow-y-auto bg-slate-50/40">
                                    <div id="lista-itens" class="space-y-2 p-2 md:p-3"></div>
                                </div>
                            </div>
                        </div>
                    </section>


                    {{-- Rodapé --}}
                    <section class="pt-4 border-t">
                        <button type="submit"
                                name="redirect_tab"
                                value="parametros"
                                class="w-full rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-base font-semibold py-3 shadow-md shadow-emerald-200">
                            Salvar Parâmetros
                        </button>
                    </section>
                    </div>
                </div>
            </div>

            <div data-tab-panel="unidades-permitidas" data-tab-panel-root="cliente" class="hidden">
                    <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b bg-emerald-600 text-white">
                            <h1 class="text-lg font-semibold">Unidades Permitidas</h1>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="rounded-xl border border-slate-200 p-3 space-y-3">
                                <p class="text-xs text-slate-500">
                                    Defina as unidades que este cliente pode usar em ASO e Treinamentos. Se nenhuma estiver marcada, o sistema libera todas por padrão.
                                </p>
                                @if($unidadesDisponiveis->isEmpty())
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                        Nenhuma unidade cadastrada para esta empresa.
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                        @foreach($unidadesDisponiveis as $unidade)
                                            @php
                                                $unidadeId = (int) ($unidade->id ?? 0);
                                                $checked = $unidadesSelecionadas->contains($unidadeId);
                                            @endphp
                                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 bg-white hover:bg-slate-50 text-sm text-slate-700">
                                                <input type="checkbox"
                                                       name="unidades_permitidas[]"
                                                       value="{{ $unidadeId }}"
                                                       class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                                    @checked($checked)>
                                                <span class="truncate">{{ $unidade->nome }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                                @error('unidades_permitidas')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                @error('unidades_permitidas.*')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <section class="pt-4 border-t">
                                <button type="submit"
                                        name="redirect_tab"
                                        value="unidades-permitidas"
                                        class="w-full rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-base font-semibold py-3 shadow-md shadow-emerald-200">
                                    Salvar Unidades Permitidas
                                </button>
                            </section>
                        </div>
                    </div>
                </div>
        </form>
    </div>

    {{-- MODAL TREINAMENTOS --}}
    <div id="modalTreinamentos" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="px-5 py-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Selecionar Treinamento (NR)</h3>
                    <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100" onclick="closeTreinamentosModal()">?</button>
                </div>
                <div class="p-5">
                    <div id="nrChips" class="flex flex-wrap gap-2"></div>
                    <p class="text-xs text-slate-500 mt-3">Clique em uma NR para adicionar no parâmetro.</p>
                </div>
            </div>
        </div>
    </div>

    @include('comercial.tabela-precos.itens.modal-ghes', [
        'routePrefix' => 'comercial',
        'clientes' => $clientes ?? collect(),
        'funcoes' => $funcoes ?? collect(),
        'gheScope' => 'cliente',
        'clienteSelector' => '[name="cliente_id"]',
        'canCreate' => true,
        'canUpdate' => true,
        'canDelete' => true,
    ])
    {{--
    MODAL PACOTE EXAMES (dinamico)
    <div id="modalExames" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="px-5 py-4 border-b bg-blue-700 text-white flex items-center justify-between">
                    <h3 class="font-semibold">Criar Pacote de Exames</h3>
                    <button type="button" class="h-9 w-9 rounded-xl hover:bg-white/10" onclick="closeExamesModal()">?</button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-700">Nome do Pacote *</label>
                        <input id="pkgExamesNome" type="text"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="Ex: Pacote Admissional">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 block mb-2">
                            Selecione os exames (<span id="pkgExamesCount">0</span> selecionados)
                        </label>

                        <div id="pkgExamesList" class="space-y-2 max-h-[45vh] overflow-auto pr-1">
                            -- carregado via fetch --
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700">Valor do Pacote (R$) *</label>
                        <input id="pkgExamesValorView" type="text"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               value="R$ 0,00">
                        <input id="pkgExamesValorHidden" type="hidden" value="0.00">
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" class="rounded-xl px-4 py-2 text-sm hover:bg-slate-100" onclick="closeExamesModal()">Cancelar</button>
                        <button type="button"
                                class="rounded-xl px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold"
                                onclick="confirmExames()">
                            Adicionar Pacote
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    --}}
    @include('comercial.propostas.modal-medicoes')
    @include('comercial.propostas.modal-treinamentos')
    {{-- MODAL eSOCIAL (seu include existente) --}}
    @include('comercial.tabela-precos.itens.modal-esocial', ['routePrefix' => 'comercial'])

    @push('scripts')
        <script>
            (function () {

                // =========================
                // URLs
                // =========================
                const URLS = {
                    precoServico: (id, clienteId) => {
                        const base = @json(route('comercial.propostas.preco-servico', ['servico' => '__ID__'])).replace('__ID__', id);
                        return clienteId ? (base + '?cliente_id=' + encodeURIComponent(clienteId)) : base;
                    },
                    precoTreinamento: (codigo, clienteId) => {
                        const base = @json(route('comercial.propostas.preco-treinamento', ['codigo' => '__COD__'])).replace('__COD__', encodeURIComponent(codigo));
                        return clienteId ? (base + '?cliente_id=' + encodeURIComponent(clienteId)) : base;
                    },
                    treinamentosJson: @json(route('comercial.propostas.treinamentos-nrs.json')),
                    examesJson: @json(route('comercial.exames.indexJson')),
                    medicoesJson: @json(route('comercial.medicoes.indexJson')),
                    gruposExames: @json(route('comercial.protocolos-exames.indexJson')),
                    clientesGhes: @json(route('comercial.clientes-ghes.indexJson')),
                    clientesAsoGrupos: @json(route('comercial.clientes-aso-grupos.indexJson')),
                    esocialPreco: (qtd) => @json(route('comercial.propostas.esocial-preco', ['qtd' => '__QTD__']))
                        .replace('__QTD__', encodeURIComponent(qtd)),

                    esocialList: @json(route('comercial.esocial.faixas.json')),
                    esocialStore: @json(route('comercial.esocial.faixas.store')),
                    esocialUpdate: (id) => @json(route('comercial.esocial.faixas.update', ['faixa' => '__ID__'])).replace('__ID__', id),
                    esocialDestroy: (id) => @json(route('comercial.esocial.faixas.destroy', ['faixa' => '__ID__'])).replace('__ID__', id),
                    funcoesStore: @json($routeFuncoesStore),
                    funcoesUpdate: (id) => @json($routeFuncoesUpdate).replace('__ID__', id),
                    funcoesDestroy: (id) => @json($routeFuncoesDestroy).replace('__ID__', id),
                };

                const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const SERVICO_ESOCIAL_ID = @json(config('services.esocial_id'));
                const SERVICO_TREINAMENTO_ID = @json(config('services.treinamento_id'));
                const SERVICO_EXAME_ID = @json(config('services.exame_id'));
                const SERVICO_ASO_ID = @json(config('services.aso_id'));
                const MEDICAO_SERVICOS = new Set(['LTCAT', 'LTIP']);

                // =========================
                // State
                // =========================
                const state = {
                    itens: [],         // {id, servico_id, tipo, nome, descricao, valor_unitario, quantidade, prazo, acrescimo, desconto, meta, valor_total}
                    exames: { loaded: false, list: [], manualPrice: false }, // [{id, nome, valor}]
                    medicoes: { loaded: false, list: [] }, // [{id, titulo, descricao, preco}]
                    medicoesTarget: null,
                    medicoesSelected: new Set(),
                    pacoteTreinamentosEditItemId: null,
                    funcaoClienteEditingId: null,
                    esocial: { enabled:false, qtd:0, valor:0, aviso:null },
                    gruposExames: [],
                    gheCatalog: [],
                    gheConfigs: [], // [{cliente_ghe_id, ghe_id, ghe_nome, tipos:{tipo:{grupo_id, grupo_titulo, total_exames}}}]
                    currentGhe: { cliente_ghe_id: null, ghe_id: null, ghe_nome: '', _key: null, tipos: {} },
                };

                const INITIAL = @json($initialData);
                const LAST_BY_CLIENTE = {};
                // =========================
                // DOM
                // =========================
                const el = {
                    lista: document.getElementById('lista-itens'),
                    listaCount: document.getElementById('lista-itens-count'),
                    total: document.getElementById('valor-total-display'),
                    clienteSelect: document.querySelector('[name="cliente_id"]'),
                    itemToast: document.getElementById('itemToast'),
                    itemToastText: document.getElementById('itemToastText'),
                    itemAlert: document.getElementById('itemAlert'),
                    itemAlertText: document.getElementById('itemAlertText'),

                    chkEsocial: document.getElementById('chkEsocial'),
                    btnToggleEsocial: document.getElementById('btnToggleEsocial'),
                    esocialBox: document.getElementById('esocialBox'),
                    esocialQtd: document.getElementById('esocialQtd'),
                    esocialValorView: document.getElementById('esocialValorView'),
                    esocialQtdHidden: document.getElementById('esocialQtdHidden'),
                    esocialValorHidden: document.getElementById('esocialValorHidden'),
                    esocialAviso: document.getElementById('esocialAviso'),

                    modalTrein: document.getElementById('modalTreinamentos'),
                    nrChips: document.getElementById('nrChips'),
                    modalMedicoes: document.getElementById('modalMedicoes'),
                    medicoesList: document.getElementById('medicoesList'),
                    medicoesCount: document.getElementById('medicoesCount'),
                    medicoesTotal: document.getElementById('medicoesTotal'),
                    medicoesTipoLabel: document.getElementById('medicoesTipoLabel'),
                    medicoesConfirmBtn: document.getElementById('medicoesConfirmBtn'),
                    medicoesAlert: document.getElementById('medicoesModalAlert'),
                    modalExames: document.getElementById('modalExames'),
                    examesList: document.getElementById('pkgExamesList'),
                    examesAvulsos: document.getElementById('examesAvulsos'),
                    pkgExamesCount: document.getElementById('pkgExamesCount'),
                    pkgExamesNome: document.getElementById('pkgExamesNome'),
                    pkgExamesValorView: document.getElementById('pkgExamesValorView'),
                    pkgExamesValorHidden: document.getElementById('pkgExamesValorHidden'),
                    form: document.getElementById('parametroForm'),
                    tabsWrap: document.querySelector('[data-tabs="parametro"]'),
                    gheSelect: document.getElementById('gheSelect'),
                    asoTipoRows: document.getElementById('asoTipoRows'),
                    gheConfigsGrid: document.getElementById('gheConfigsGrid'),
                    asoGheValoresGrid: document.getElementById('asoGheValoresGrid'),
                    asoGheTitle: document.getElementById('asoGheTitle'),
                    btnAddGheConfig: document.getElementById('btnAddGheConfig'),
                    btnGheGlobal: document.getElementById('btnGheGlobal'),
                    novaFuncaoNome: document.getElementById('novaFuncaoNome'),
                    funcoesClienteBusca: document.getElementById('funcoesClienteBusca'),
                    funcoesClienteResumo: document.getElementById('funcoesClienteResumo'),
                    btnSelecionarTodasFuncoes: document.getElementById('btnSelecionarTodasFuncoes'),
                    btnLimparFuncoes: document.getElementById('btnLimparFuncoes'),
                    btnNovaFuncaoParametro: document.getElementById('btnNovaFuncaoParametro'),
                    btnCancelarEdicaoFuncao: document.getElementById('btnCancelarEdicaoFuncao'),
                    funcoesClienteGrid: document.getElementById('funcoesClienteGrid'),
                };

                const ASO_TYPES = [
                    { key: 'admissional', label: 'Admissional' },
                    { key: 'periodico', label: 'Periódico' },
                    { key: 'demissional', label: 'Demissional' },
                    { key: 'mudanca_funcao', label: 'Mudança de Função' },
                    { key: 'retorno_trabalho', label: 'Retorno ao Trabalho' },
                ];

                const ALLOW_MULTIPLE_PROPOSTAS = true;

                // =========================
                // Hydrate (edit)
                // =========================
                if (INITIAL?.isEdit) {
                    state.itens = normalizeItens(Array.isArray(INITIAL.itens) ? INITIAL.itens : []);
                    state.itens.forEach(it => recalcItemTotal(it));
                    removeEsocialItens();

                    if (INITIAL.esocial) {
                        state.esocial.enabled = !!INITIAL.esocial.enabled;
                        state.esocial.qtd = Number(INITIAL.esocial.qtd || 0);
                        state.esocial.valor = Number(INITIAL.esocial.valor || 0);
                    }

                    if (Array.isArray(INITIAL.gheConfigs)) {
                        state.gheConfigs = normalizeGheConfigs(INITIAL.gheConfigs);
                    }
                }

                initTabs();
                initFuncoesClienteTab();
                updateTabBadges();
                bindClienteAutoLoad();
                bindAsoHandlers();
                loadGruposExames();
                loadGheCatalog();
                syncAsoTipoItems();

                // =========================
                // Utils
                // =========================
                function hasAsoItem(it) {
                    const nomeBase = String(it?.nome || it?.descricao || '').toUpperCase();
                    return nomeBase && nomeBase.includes('ASO');
                }

                function bindClienteAutoLoad() {
                    if (ALLOW_MULTIPLE_PROPOSTAS) return;
                    if (!el.clienteSelect || INITIAL?.isEdit) return;
                    el.clienteSelect.addEventListener('change', () => {
                        const clienteId = el.clienteSelect.value;
                        if (!clienteId) return;
                        const info = LAST_BY_CLIENTE[clienteId];
                        if (!info) return;
                        if (info.can_edit && info.edit_url) {
                            window.location.href = info.edit_url;
                            return;
                        }
                        if (info.show_url) {
                            window.location.href = info.show_url;
                        }
                    });
                }

                function getGrupoById(id) {
                    return state.gruposExames.find(g => Number(g.id) === Number(id));
                }

                function getCurrentClienteId() {
                    const clienteId = Number(el.clienteSelect?.value || 0);
                    return clienteId > 0 ? clienteId : null;
                }

                async function loadGruposExames() {
                    try {
                        const clienteId = getCurrentClienteId();
                        const url = clienteId
                            ? `${URLS.gruposExames}?cliente_id=${encodeURIComponent(clienteId)}`
                            : URLS.gruposExames;
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const json = await res.json();
                        state.gruposExames = json.data || [];
                        const gruposIds = new Set(state.gruposExames.map((grupo) => Number(grupo.id || 0)).filter((id) => id > 0));

                        Object.entries(state.currentGhe.tipos || {}).forEach(([tipo, row]) => {
                            if (!row?.grupo_id) return;
                            const grupo = getGrupoById(row.grupo_id);
                            if (!grupo || !gruposIds.has(Number(row.grupo_id || 0))) {
                                delete state.currentGhe.tipos[tipo];
                                return;
                            }

                            row.grupo_titulo = row.grupo_titulo || grupo.titulo || '';
                            if (!row.total_exames) row.total_exames = Number(grupo.total || 0);
                        });

                        state.gheConfigs = state.gheConfigs
                            .map((cfg) => {
                                const tipos = { ...(cfg?.tipos || {}) };

                                Object.entries(tipos).forEach(([tipo, row]) => {
                                    if (!row?.grupo_id) return;
                                    const grupo = getGrupoById(row.grupo_id);
                                    if (!grupo || !gruposIds.has(Number(row.grupo_id || 0))) {
                                        delete tipos[tipo];
                                        return;
                                    }

                                    row.grupo_titulo = row.grupo_titulo || grupo.titulo || '';
                                    if (!row.total_exames) row.total_exames = Number(grupo.total || 0);
                                });

                                return {
                                    ...cfg,
                                    tipos,
                                };
                            })
                            .filter((cfg) => Object.keys(cfg?.tipos || {}).length > 0);

                        state.gheConfigs.forEach(cfg => {
                            Object.entries(cfg.tipos || {}).forEach(([tipo, row]) => {
                                if (!row?.grupo_id) return;
                                const grupo = getGrupoById(row.grupo_id);
                                if (grupo) {
                                    row.grupo_titulo = row.grupo_titulo || grupo.titulo || '';
                                    if (!row.total_exames) row.total_exames = Number(grupo.total || 0);
                                }
                            });
                        });
                        renderAsoTipoRows();
                        renderGheConfigsTable();
                        syncAsoTipoItems();
                    } catch (e) {
                        console.error(e);
                    }
                }

                async function loadGheCatalog() {
                    try {
                        const clienteId = Number(el.clienteSelect?.value || 0);
                        if (!clienteId) {
                            state.gheCatalog = [];
                            renderGheSelectOptions();
                            return;
                        }

                        const res = await fetch(`${URLS.clientesGhes}?cliente_id=${clienteId}`, { headers: { 'Accept': 'application/json' } });
                        const json = await res.json();
                        state.gheCatalog = (json.data || []).map((ghe) => ({
                            id: Number(ghe.id || 0),
                            cliente_ghe_id: Number(ghe.id || 0),
                            ghe_id: ghe.ghe_id ? Number(ghe.ghe_id) : null,
                            nome: ghe.nome || '',
                            grupo_exames_id: ghe.grupo_exames_id ? Number(ghe.grupo_exames_id) : null,
                            protocolos: ghe.protocolos || {},
                            total_exames_por_tipo: ghe.total_exames_por_tipo || {},
                        }));
                        renderGheSelectOptions();
                    } catch (e) {
                        console.error(e);
                    }
                }

                function renderGheSelectOptions() {
                    if (!el.gheSelect) return;
                    const current = String(state.currentGhe.cliente_ghe_id || state.currentGhe.ghe_id || '');
                    el.gheSelect.innerHTML = '<option value="">Selecione o GHE...</option>';
                    state.gheCatalog.forEach(g => {
                        const opt = document.createElement('option');
                        opt.value = String(g.cliente_ghe_id || g.id || '');
                        opt.textContent = g.nome;
                        el.gheSelect.appendChild(opt);
                    });
                    if (current && !state.gheCatalog.some(g => String(g.cliente_ghe_id || g.id || '') === current) && state.currentGhe.ghe_nome) {
                        const opt = document.createElement('option');
                        opt.value = current;
                        opt.textContent = state.currentGhe.ghe_nome;
                        el.gheSelect.appendChild(opt);
                    }
                    if (current) {
                        el.gheSelect.value = current;
                    }
                }

                function resetCurrentGheConfig() {
                    state.currentGhe = { cliente_ghe_id: null, ghe_id: null, ghe_nome: '', _key: null, tipos: {} };
                    if (el.gheSelect) el.gheSelect.value = '';
                    if (el.asoGheTitle) el.asoGheTitle.textContent = '—';
                    if (el.btnAddGheConfig) el.btnAddGheConfig.textContent = '+ Adicionar este GHE à lista';
                    renderAsoTipoRows();
                }

                function setCurrentGheFromSelect() {
                    const id = Number(el.gheSelect?.value || 0);
                    const ghe = state.gheCatalog.find(g => Number(g.cliente_ghe_id || g.id || 0) === id);
                    if (!ghe) {
                        resetCurrentGheConfig();
                        return;
                    }
                    state.currentGhe = {
                        cliente_ghe_id: ghe.cliente_ghe_id || ghe.id || null,
                        ghe_id: ghe.ghe_id || null,
                        ghe_nome: ghe.nome,
                        _key: getGheConfigKey(ghe),
                        tipos: {},
                    };
                    if (el.asoGheTitle) el.asoGheTitle.textContent = ghe.nome || '—';
                    if (el.btnAddGheConfig) el.btnAddGheConfig.textContent = '+ Adicionar este GHE à lista';
                    if (ghe.grupo_exames_id) {
                        ASO_TYPES.forEach(({ key }) => {
                            const grupo = getGrupoById(ghe.grupo_exames_id);
                            state.currentGhe.tipos[key] = {
                                grupo_id: ghe.grupo_exames_id,
                                grupo_titulo: grupo?.titulo || '',
                                total_exames: Number(grupo?.total || 0),
                            };
                        });
                    } else {
                        ASO_TYPES.forEach(({ key }) => {
                            const protocolo = ghe.protocolos?.[key];
                            const grupoId = Number(protocolo?.id || 0);
                            if (!grupoId) return;
                            state.currentGhe.tipos[key] = {
                                grupo_id: grupoId,
                                grupo_titulo: protocolo?.titulo || '',
                                total_exames: Number(ghe.total_exames_por_tipo?.[key] || 0),
                            };
                        });
                    }
                    renderAsoTipoRows();
                }

                function renderAsoTipoRows() {
                    if (!el.asoTipoRows) return;
                    el.asoTipoRows.innerHTML = '';

                    ASO_TYPES.forEach(({ key, label }) => {
                        const row = state.currentGhe.tipos?.[key] || {};
                        const rowEl = document.createElement('div');
                        rowEl.className = 'px-3 py-2 bg-white';
                        rowEl.innerHTML = `
                            <div class="grid grid-cols-12 gap-2 items-start">
                                <div class="col-span-12 md:col-span-2">
                                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Tipo de ASO</div>
                                    <div class="text-sm font-semibold text-slate-800">${label}</div>
                                </div>
                                <div class="col-span-12 md:col-span-5">
                                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Grupo de exames</div>
                                    <select class="w-full rounded-md border-slate-200 text-sm px-2 py-2" data-aso-tipo-grupo></select>
                                </div>
                                <div class="col-span-8 md:col-span-3">
                                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Exames</div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="text-xs font-semibold text-blue-600 hover:underline" data-aso-tipo-show>Ver exames</button>
                                        <button type="button" class="text-xs font-semibold text-emerald-700 hover:underline" data-aso-tipo-new>+ Novo Grupo</button>
                                    </div>
                                    <div class="text-[11px] text-slate-500" data-aso-tipo-count>—</div>
                                </div>
                                <div class="col-span-4 md:col-span-2">
                                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Valor</div>
                                    <input type="text" class="w-full rounded-md border-slate-200 text-sm px-2 py-2 text-right" data-aso-tipo-total>
                                </div>
                            </div>
                            <div class="mt-2 hidden text-xs text-slate-600 space-y-1" data-aso-tipo-exames></div>
                        `;

                        const select = rowEl.querySelector('[data-aso-tipo-grupo]');
                        select.innerHTML = '<option value="">Selecione...</option>';
                        state.gruposExames.forEach(g => {
                            const opt = document.createElement('option');
                            opt.value = g.id;
                            opt.textContent = g.titulo;
                            select.appendChild(opt);
                        });
                        if (row.grupo_id) {
                            select.value = String(row.grupo_id);
                        }

                        const totalInput = rowEl.querySelector('[data-aso-tipo-total]');
                        totalInput.dataset.digits = onlyDigits(Math.round(Number(row.total_exames || 0) * 100));
                        totalInput.value = brl(Number(row.total_exames || 0));

                        const countEl = rowEl.querySelector('[data-aso-tipo-count]');
                        const examesWrap = rowEl.querySelector('[data-aso-tipo-exames]');
                        const showBtn = rowEl.querySelector('[data-aso-tipo-show]');
                        const newBtn = rowEl.querySelector('[data-aso-tipo-new]');

                        const updateRowExames = () => {
                            const grupo = getGrupoById(Number(select.value || 0));
                            const count = grupo?.exames?.length ?? 0;
                            if (countEl) {
                                countEl.textContent = count ? `${count} exame(s)` : '—';
                            }
                            if (!examesWrap || examesWrap.classList.contains('hidden')) return;
                            const totalOverride = Number((state.currentGhe.tipos?.[key]?.total_exames ?? grupo?.total) || 0);
                            renderAsoRowExames(grupo, examesWrap, totalOverride);
                        };

                        select.addEventListener('change', () => {
                            const grupoId = Number(select.value || 0);
                            const grupo = getGrupoById(grupoId);
                            state.currentGhe.tipos[key] = {
                                grupo_id: grupoId,
                                grupo_titulo: grupo?.titulo || '',
                                total_exames: Number(grupo?.total || 0),
                            };
                            totalInput.dataset.digits = onlyDigits(Math.round(Number(grupo?.total || 0) * 100));
                            totalInput.value = brl(Number(grupo?.total || 0));
                            updateRowExames();
                        });

                        totalInput.addEventListener('keydown', (e) => {
                            const nav = ['Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End','Delete'];
                            if (e.ctrlKey || e.metaKey) return;
                            if (e.key === 'Backspace') {
                                e.preventDefault();
                                const d = totalInput.dataset.digits || '';
                                const nd = d.slice(0, -1);
                                totalInput.dataset.digits = nd;
                                const num = centsDigitsToNumber(nd);
                                const current = state.currentGhe.tipos[key] || { grupo_id: Number(select.value || 0) };
                                current.total_exames = num;
                                state.currentGhe.tipos[key] = current;
                                totalInput.value = brl(num);
                                return;
                            }
                            if (nav.includes(e.key)) return;
                            if (!/^\d$/.test(e.key)) e.preventDefault();
                        });

                        totalInput.addEventListener('input', () => {
                            const digits = onlyDigits(totalInput.value);
                            totalInput.dataset.digits = digits;
                            const num = centsDigitsToNumber(digits);
                            const current = state.currentGhe.tipos[key] || { grupo_id: Number(select.value || 0) };
                            current.total_exames = num;
                            state.currentGhe.tipos[key] = current;
                            totalInput.value = brl(num);
                            if (examesWrap && !examesWrap.classList.contains('hidden')) {
                                const grupo = getGrupoById(Number(select.value || 0));
                                renderAsoRowExames(grupo, examesWrap, num);
                            }
                        });

                        showBtn.addEventListener('click', () => {
                            if (!examesWrap) return;
                            examesWrap.classList.toggle('hidden');
                            if (!examesWrap.classList.contains('hidden')) {
                                const grupo = getGrupoById(Number(select.value || 0));
                                const totalOverride = Number((state.currentGhe.tipos?.[key]?.total_exames ?? grupo?.total) || 0);
                                renderAsoRowExames(grupo, examesWrap, totalOverride);
                            }
                        });

                        newBtn?.addEventListener('click', () => {
                            if (typeof window.openProtocolosModal === 'function') {
                                window.openProtocolosModal({ clienteId: getCurrentClienteId() });
                            }
                        });

                        updateRowExames();
                        el.asoTipoRows.appendChild(rowEl);
                    });
                }

                function renderAsoRowExames(grupo, target, totalOverride) {
                    if (!grupo?.id) {
                        target.innerHTML = '<div class="text-slate-500">Selecione um grupo para ver os exames.</div>';
                        return;
                    }
                    if (!grupo?.exames?.length) {
                        target.innerHTML = '<div class="text-slate-500">Nenhum exame neste grupo.</div>';
                        return;
                    }
                    const exames = ratearExames(grupo.exames, Number(totalOverride || 0));
                    target.innerHTML = exames.map(ex => {
                        return `<div class="flex items-center justify-between gap-2">
                            <span class="truncate">${escapeHtml(ex.titulo || 'Exame')}</span>
                            <span class="text-slate-700 font-semibold">${brl(ex.preco || 0)}</span>
                        </div>`;
                    }).join('');
                }

                function ratearExames(exames, novoTotal) {
                    if (!Array.isArray(exames) || !exames.length) return [];
                    const somaOriginal = exames.reduce((sum, ex) => sum + Number(ex.preco || 0), 0);
                    if (!novoTotal) {
                        return exames;
                    }
                    let acumulado = 0;
                    const result = exames.map((ex, idx) => {
                        let novoPreco;
                        if (somaOriginal > 0) {
                            novoPreco = (Number(ex.preco || 0) / somaOriginal) * novoTotal;
                        } else {
                            novoPreco = novoTotal / exames.length;
                        }
                        novoPreco = Math.round(novoPreco * 100) / 100;
                        acumulado += novoPreco;
                        return { ...ex, preco: novoPreco };
                    });
                    const diff = Math.round((novoTotal - acumulado) * 100) / 100;
                    if (Math.abs(diff) >= 0.01) {
                        const last = result[result.length - 1];
                        last.preco = Math.round((Number(last.preco || 0) + diff) * 100) / 100;
                    }
                    return result;
                }

                function buildGheConfigKey(cfg) {
                    if (cfg?.cliente_ghe_id) return `c:${cfg.cliente_ghe_id}`;
                    if (cfg?.ghe_id) {
                        const nome = String(cfg?.ghe_nome || '').trim().toLowerCase();
                        return nome ? `g:${cfg.ghe_id}:n:${nome}` : `g:${cfg.ghe_id}`;
                    }
                    return cfg?._key || uid();
                }

                function getGheConfigKey(cfg) {
                    if (!cfg || typeof cfg !== 'object') return uid();
                    if (!cfg._key) {
                        cfg._key = buildGheConfigKey(cfg);
                    }
                    return cfg._key;
                }

                function getGheConfigRuntimeKey(cfg, index) {
                    return `${getGheConfigKey(cfg)}:idx:${Number(index || 0)}`;
                }

                function normalizeItens(items) {
                    if (!Array.isArray(items)) return [];
                    const normalized = [];
                    const asoByKey = new Map();
                    const usedIds = new Set();

                    items.forEach((raw) => {
                        if (!raw || typeof raw !== 'object') return;

                        const item = {
                            ...raw,
                            meta: (raw.meta && typeof raw.meta === 'object') ? { ...raw.meta } : {},
                        };

                        item.id = item.id || uid();
                        while (usedIds.has(String(item.id))) {
                            item.id = uid();
                        }
                        usedIds.add(String(item.id));

                        const isAsoTipo = String(item.tipo || '').toUpperCase() === 'ASO_TIPO';
                        const asoKey = String(item?.meta?.aso_key || '').trim();
                        if (!isAsoTipo) {
                            normalized.push(item);
                            return;
                        }

                        if (!asoKey) {
                            return;
                        }

                        asoByKey.set(asoKey, item);
                    });

                    asoByKey.forEach((item) => normalized.push(item));
                    return normalized;
                }

                function normalizeGheConfigs(configs) {
                    if (!Array.isArray(configs)) return [];
                    const grouped = new Map();

                    configs.forEach((raw) => {
                        if (!raw || typeof raw !== 'object') return;
                        const key = getGheConfigKey(raw);
                        if (!grouped.has(key)) {
                            grouped.set(key, {
                                cliente_ghe_id: raw.cliente_ghe_id || null,
                                ghe_id: raw.ghe_id || null,
                                ghe_nome: raw.ghe_nome || '',
                                _key: key,
                                tipos: {},
                            });
                        }

                        const cfg = grouped.get(key);
                        const tipos = (raw.tipos && typeof raw.tipos === 'object') ? raw.tipos : {};
                        ASO_TYPES.forEach(({ key: tipoKey }) => {
                            const row = tipos[tipoKey];
                            if (!row?.grupo_id) return;
                            cfg.tipos[tipoKey] = {
                                grupo_id: Number(row.grupo_id || 0),
                                grupo_titulo: row.grupo_titulo || '',
                                total_exames: Number(row.total_exames || 0),
                            };
                        });
                    });

                    return Array.from(grouped.values());
                }

                function renderGheConfigsTable() {
                    const hasLegacyGrid = !!el.gheConfigsGrid;
                    if (hasLegacyGrid) {
                        el.gheConfigsGrid.innerHTML = '';
                    }

                    if (!state.gheConfigs.length) {
                        if (hasLegacyGrid) {
                            el.gheConfigsGrid.innerHTML = '<div class="px-3 py-3 text-sm text-slate-500">Nenhum GHE configurado.</div>';
                        }
                        renderAsoValoresPorGhe();
                        return;
                    }

                    const chip = (label, grupo) => {
                        const hasGroup = !!(grupo && grupo !== '—');
                        const baseClass = hasGroup
                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                            : 'bg-slate-50 text-slate-500 border-slate-200';
                        return `
                            <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs ${baseClass}">
                                <span class="font-semibold">${label}</span>
                                <span class="text-[11px]">${escapeHtml(grupo || '—')}</span>
                            </div>
                        `;
                    };

                    state.gheConfigs.forEach((cfg) => {
                        const card = document.createElement('div');
                        card.className = 'rounded-xl border border-slate-200 bg-white p-4 space-y-3';
                        const tipos = cfg.tipos || {};
                        const getGrupo = (tipo) => tipos?.[tipo]?.grupo_titulo || '—';
                        const totalTipos = ASO_TYPES.length;
                        const configured = ASO_TYPES.filter(t => tipos?.[t.key]?.grupo_id).length;
                        const rateado = ASO_TYPES.some(({ key }) => {
                            const row = tipos?.[key];
                            if (!row?.grupo_id) return false;
                            const grupo = getGrupoById(Number(row.grupo_id || 0));
                            if (!grupo) return false;
                            return Number(row.total_exames || 0) !== Number(grupo.total || 0);
                        });

                        card.innerHTML = `
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-800">${escapeHtml(cfg.ghe_nome || 'GHE')}</div>
                                    <div class="text-xs text-slate-500">${configured}/${totalTipos} tipos configurados</div>
                                    ${rateado ? '<div class="mt-1 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] text-amber-800">Rateado</div>' : ''}
                                </div>
                                <div class="flex items-center gap-3">
                                    <button type="button" class="text-blue-600 text-sm font-semibold" data-action="edit">Editar</button>
                                    <button type="button" class="text-red-600 text-sm font-semibold" data-action="del">Excluir</button>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                ${chip('Admissional', getGrupo('admissional'))}
                                ${chip('Periódico', getGrupo('periodico'))}
                                ${chip('Demissional', getGrupo('demissional'))}
                                ${chip('Mudança', getGrupo('mudanca_funcao'))}
                                ${chip('Retorno', getGrupo('retorno_trabalho'))}
                            </div>
                        `;

                        card.querySelector('[data-action="edit"]').addEventListener('click', () => editGheConfig(cfg));
                        card.querySelector('[data-action="del"]').addEventListener('click', () => deleteGheConfig(cfg));

                        if (hasLegacyGrid) {
                            el.gheConfigsGrid.appendChild(card);
                        }
                    });

                    renderAsoValoresPorGhe();
                }

                function editGheConfig(cfg) {
                    state.currentGhe = JSON.parse(JSON.stringify(cfg));
                    if (el.gheSelect) {
                        const opt = cfg.cliente_ghe_id ? String(cfg.cliente_ghe_id) : (cfg.ghe_id ? String(cfg.ghe_id) : '');
                        el.gheSelect.value = opt;
                    }
                    if (el.asoGheTitle) el.asoGheTitle.textContent = cfg.ghe_nome || '—';
                    if (el.btnAddGheConfig) el.btnAddGheConfig.textContent = 'Atualizar este GHE';
                    renderAsoTipoRows();
                }

                function deleteGheConfig(cfg) {
                    const key = getGheConfigKey(cfg);
                    state.gheConfigs = state.gheConfigs.filter(c => getGheConfigKey(c) !== key);
                    syncAsoTipoItems();
                    renderGheConfigsTable();
                    updateTabBadges();
                }

                function renderAsoValoresPorGhe() {
                    if (!el.asoGheValoresGrid) return;
                    el.asoGheValoresGrid.innerHTML = '';

                    if (!state.gheConfigs.length) {
                        el.asoGheValoresGrid.innerHTML = '<div class="px-3 py-3 text-sm text-slate-500">Nenhum GHE configurado.</div>';
                        return;
                    }

                    state.gheConfigs.forEach((cfg) => {
                        const card = document.createElement('div');
                        card.className = 'rounded-xl border border-slate-200 bg-white overflow-hidden';
                        const tipos = cfg.tipos || {};

                        const rowsHtml = ASO_TYPES.map(({ key, label }) => {
                            const row = tipos[key];
                            const valor = Number(row?.total_exames || 0);
                            const grupoTitulo = row?.grupo_titulo ? escapeHtml(row.grupo_titulo) : '—';
                            return `
                                <div class="grid grid-cols-12 gap-2 px-3 py-2 border-t border-slate-100">
                                    <div class="col-span-7">
                                        <div class="text-sm text-slate-700">${escapeHtml(label)}</div>
                                        <div class="text-[11px] text-slate-500">${grupoTitulo}</div>
                                    </div>
                                    <div class="col-span-5 text-right text-sm font-semibold text-slate-800">${row?.grupo_id ? brl(valor) : '—'}</div>
                                </div>
                            `;
                        }).join('');

                        const totalGhe = ASO_TYPES.reduce((sum, { key }) => {
                            const row = tipos[key];
                            if (!row?.grupo_id) return sum;
                            return sum + Number(row.total_exames || 0);
                        }, 0);

                        card.innerHTML = `
                            <div class="px-3 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-800">${escapeHtml(cfg.ghe_nome || 'GHE')}</div>
                                <div class="flex items-center gap-3">
                                    <button type="button" class="text-blue-600 text-xs font-semibold" data-action="edit">Editar</button>
                                    <button type="button" class="text-red-600 text-xs font-semibold" data-action="del">Excluir</button>
                                </div>
                            </div>
                            ${rowsHtml}
                            <div class="grid grid-cols-12 gap-2 px-3 py-2 bg-emerald-50/60 border-t border-emerald-100">
                                <div class="col-span-7 text-sm font-semibold text-emerald-900">Total ASO (GHE)</div>
                                <div class="col-span-5 text-right text-sm font-semibold text-emerald-900">${brl(totalGhe)}</div>
                            </div>
                        `;

                        card.querySelector('[data-action="edit"]')?.addEventListener('click', () => editGheConfig(cfg));
                        card.querySelector('[data-action="del"]')?.addEventListener('click', () => deleteGheConfig(cfg));

                        el.asoGheValoresGrid.appendChild(card);
                    });
                }

                function addOrUpdateCurrentGheConfig() {
                    if (!el.clienteSelect?.value) {
                        showItemAlert('Selecione um cliente antes de configurar o GHE.');
                        return;
                    }
                    if (!state.currentGhe.ghe_id && !state.currentGhe.cliente_ghe_id) {
                        showItemAlert('Selecione um GHE para adicionar.');
                        return;
                    }

                    const gheNome = state.currentGhe.ghe_nome || (state.gheCatalog.find(g => Number(g.id) === Number(state.currentGhe.ghe_id))?.nome || '');
                    const tipos = {};
                    ASO_TYPES.forEach(({ key }) => {
                        const row = state.currentGhe.tipos?.[key] || {};
                        if (!row?.grupo_id) return;
                        const grupo = getGrupoById(row.grupo_id);
                        tipos[key] = {
                            grupo_id: row.grupo_id,
                            grupo_titulo: grupo?.titulo || row.grupo_titulo || '',
                            total_exames: Number(row.total_exames || grupo?.total || 0),
                        };
                    });
                    if (!Object.keys(tipos).length) {
                        showItemAlert('Selecione ao menos um grupo de exames.');
                        return;
                    }

                    const cfg = {
                        cliente_ghe_id: state.currentGhe.cliente_ghe_id || null,
                        ghe_id: state.currentGhe.ghe_id || null,
                        ghe_nome: gheNome,
                        _key: getGheConfigKey(state.currentGhe),
                        tipos,
                    };

                    const key = getGheConfigKey(cfg);
                    const idx = state.gheConfigs.findIndex(c => getGheConfigKey(c) === key);
                    if (idx >= 0) {
                        state.gheConfigs[idx] = cfg;
                    } else {
                        state.gheConfigs.push(cfg);
                    }
                    state.gheConfigs = normalizeGheConfigs(state.gheConfigs);

                    syncAsoTipoItems();
                    renderGheConfigsTable();
                    resetCurrentGheConfig();
                    updateTabBadges();
                }

                function syncAsoTipoItems() {
                    const fixedItems = state.itens.filter((it) => String(it?.tipo || '').toUpperCase() !== 'ASO_TIPO');
                    const asoItems = [];

                    state.gheConfigs.forEach((cfg, cfgIndex) => {
                        const cfgKey = getGheConfigRuntimeKey(cfg, cfgIndex);
                        const tipos = cfg.tipos || {};

                        ASO_TYPES.forEach(({ key, label }) => {
                            const row = tipos[key];
                            if (!row?.grupo_id) return;

                            const asoKey = `${cfgKey}:${key}:${Number(row.grupo_id || 0)}`;
                            const descParts = [];
                            if (cfg.ghe_nome) descParts.push(`GHE: ${cfg.ghe_nome}`);
                            if (row.grupo_titulo) descParts.push(`Grupo: ${row.grupo_titulo}`);

                            const item = {
                                id: uid(),
                                servico_id: SERVICO_ASO_ID ? Number(SERVICO_ASO_ID) : null,
                                tipo: 'ASO_TIPO',
                                nome: `ASO - ${label}`,
                                descricao: descParts.length ? descParts.join(' | ') : null,
                                valor_unitario: Number(row.total_exames || 0),
                                quantidade: 1,
                                prazo: '',
                                acrescimo: 0,
                                desconto: 0,
                                meta: {
                                    aso_tipo: key,
                                    aso_cfg_key: cfgKey,
                                    grupo_id: row.grupo_id,
                                    cliente_ghe_id: cfg.cliente_ghe_id || null,
                                    ghe_id: cfg.ghe_id || null,
                                    ghe_nome: cfg.ghe_nome || null,
                                    aso_key: asoKey,
                                },
                                valor_total: 0,
                            };

                            recalcItemTotal(item);
                            asoItems.push(item);
                        });
                    });

                    state.itens = [...fixedItems, ...asoItems];

                    render();
                }

                function updateAsoConfigFromItem(item, value) {
                    const baseKey = String(item?.meta?.aso_cfg_key || '').trim();
                    const tipo = String(item?.meta?.aso_tipo || '').trim();
                    if (!baseKey || !tipo) return;
                    const cfg = state.gheConfigs.find((c, idx) => getGheConfigRuntimeKey(c, idx) === baseKey);
                    if (!cfg || !cfg.tipos?.[tipo]) return;
                    cfg.tipos[tipo].total_exames = Number(value || 0);
                    renderGheConfigsTable();
                    syncHiddenInputs();
                }

                function bindAsoHandlers() {
                    el.gheSelect?.addEventListener('change', setCurrentGheFromSelect);
                    el.btnAddGheConfig?.addEventListener('click', addOrUpdateCurrentGheConfig);
                    el.btnGheGlobal?.addEventListener('click', () => {
                        if (typeof window.openGheModal === 'function') {
                            window.openGheModal();
                        }
                    });

                    window.addEventListener('protocolos:updated', () => {
                        loadGruposExames();
                    });
                    window.addEventListener('ghes:updated', () => {
                        loadGheCatalog();
                    });

                    if (el.clienteSelect) {
                        el.clienteSelect.addEventListener('change', () => {
                            const clienteId = el.clienteSelect.value;
                            state.gruposExames = [];
                            state.gheCatalog = [];
                            state.gheConfigs = [];
                            resetCurrentGheConfig();
                            renderGheConfigsTable();
                            syncAsoTipoItems();
                            loadGruposExames();
                            loadGheCatalog();
                            if (!clienteId) return;
                            if (!INITIAL?.isEdit) {
                                loadClienteAsoGrupos(clienteId);
                            }
                        });
                        if (el.clienteSelect.value && !INITIAL?.isEdit) {
                            loadClienteAsoGrupos(el.clienteSelect.value);
                        }
                    }

                    if (INITIAL?.isEdit) {
                        renderGheConfigsTable();
                        resetCurrentGheConfig();
                    }
                }

                async function loadClienteAsoGrupos(clienteId) {
                    try {
                        const res = await fetch(`${URLS.clientesAsoGrupos}?cliente_id=${clienteId}`, { headers: { 'Accept': 'application/json' } });
                        const json = await res.json();
                        const rows = json.data || [];
                        const grouped = new Map();
                        rows.forEach(r => {
                            const key = r.cliente_ghe_id ? `c:${r.cliente_ghe_id}` : (r.ghe_id ? `g:${r.ghe_id}` : uid());
                            if (!grouped.has(key)) {
                                grouped.set(key, {
                                    cliente_ghe_id: r.cliente_ghe_id || null,
                                    ghe_id: r.ghe_id || null,
                                    ghe_nome: r.ghe_nome || '',
                                    tipos: {},
                                });
                            }
                            const cfg = grouped.get(key);
                            cfg.tipos[r.tipo_aso] = {
                                grupo_id: r.grupo_id,
                                grupo_titulo: r.grupo_titulo || '',
                                total_exames: Number(r.total_exames || 0),
                            };
                        });
                        state.gheConfigs = normalizeGheConfigs(Array.from(grouped.values()));
                        syncAsoTipoItems();
                        renderGheConfigsTable();
                        resetCurrentGheConfig();
                    } catch (e) {
                        console.error(e);
                    }
                }

                function getAsoTotals() {
                    let totalAso = 0;
                    let temAso = false;
                    state.itens.forEach(it => {
                        if (hasAsoItem(it)) {
                            temAso = true;
                            totalAso += Number(it.valor_total || 0);
                        }
                    });
                    return { temAso, totalAso };
                }

                function updateTabBadges() {
                    const badgeTrein = document.getElementById('badgeTabTreinamentos');
                    const temTrein = state.itens.some(it => (String(it.tipo || '')).toUpperCase() === 'TREINAMENTO_NR');
                    if (badgeTrein) {
                        badgeTrein.classList.toggle('hidden', !temTrein);
                    }
                }
                function initTabs() {
                    if (!el.tabsWrap) return;
                    const buttons = Array.from(el.tabsWrap.querySelectorAll('[data-tab]'));
                    const scope = el.tabsWrap.closest('[data-tabs-scope="parametro-servicos"]') || el.tabsWrap.parentElement || document;
                    const panels = Array.from(scope.querySelectorAll('[data-tab-panel]'));
                    const parametroTabInput = document.getElementById('parametro_tab');
                    if (!buttons.length || !panels.length) return;

                    const setActive = (name) => {
                        if (parametroTabInput) {
                            parametroTabInput.value = name;
                        }
                        buttons.forEach(btn => {
                            const active = btn.dataset.tab === name;
                            btn.classList.toggle('bg-blue-600', active);
                            btn.classList.toggle('text-white', active);
                            btn.classList.toggle('text-slate-600', !active);
                            btn.classList.toggle('hover:bg-slate-100', !active);
                        });
                        panels.forEach(panel => {
                            panel.classList.toggle('hidden', panel.dataset.tabPanel !== name);
                        });
                    };

                    buttons.forEach(btn => {
                        btn.addEventListener('click', () => setActive(btn.dataset.tab));
                    });

                    const initialTab = buttons.some(btn => btn.dataset.tab === @json($parametroTabInicial))
                        ? @json($parametroTabInicial)
                        : buttons[0].dataset.tab;

                    setActive(initialTab);
                }

                function initFuncoesClienteTab() {
                    if (!el.btnNovaFuncaoParametro || !el.novaFuncaoNome || !el.funcoesClienteGrid) return;

                    const inputName = 'funcoes_cliente[]';
                    const getFuncaoCards = () => Array.from(el.funcoesClienteGrid.querySelectorAll('[data-funcao-card]'));

                    const updateFuncoesClienteResumo = () => {
                        const cards = getFuncaoCards();
                        const selected = cards.filter((card) => card.querySelector(`input[name="${inputName}"]`)?.checked).length;
                        const total = cards.length;
                        if (el.funcoesClienteResumo) {
                            el.funcoesClienteResumo.textContent = `${selected} de ${total} selecionadas`;
                        }
                    };

                    const refreshFuncoesClienteGrid = () => {
                        const term = String(el.funcoesClienteBusca?.value || '').trim().toLowerCase();
                        const cards = getFuncaoCards();

                        cards
                            .sort((a, b) => {
                                const aChecked = a.querySelector(`input[name="${inputName}"]`)?.checked ? 1 : 0;
                                const bChecked = b.querySelector(`input[name="${inputName}"]`)?.checked ? 1 : 0;
                                if (aChecked !== bChecked) return bChecked - aChecked;
                                return String(a.dataset.funcaoNome || '').localeCompare(String(b.dataset.funcaoNome || ''), 'pt-BR');
                            })
                            .forEach((card) => el.funcoesClienteGrid.appendChild(card));

                        cards.forEach((card) => {
                            const title = String(card.dataset.funcaoNome || '').toLowerCase();
                            const matches = !term || title.includes(term);
                            const checkbox = card.querySelector(`input[name="${inputName}"]`);
                            const checked = !!checkbox?.checked;
                            card.classList.toggle('hidden', !matches);
                            card.classList.toggle('border-emerald-300', checked);
                            card.classList.toggle('bg-emerald-50/40', checked);
                        });

                        updateFuncoesClienteResumo();
                    };

                    const resetFuncoesEditor = () => {
                        state.funcaoClienteEditingId = null;
                        el.novaFuncaoNome.value = '';
                        el.novaFuncaoNome.placeholder = 'Nova função';
                        el.btnNovaFuncaoParametro.textContent = '+ Cadastrar';
                        el.btnNovaFuncaoParametro.classList.remove('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
                        el.btnNovaFuncaoParametro.classList.add('border-blue-200', 'bg-blue-50', 'text-blue-800');
                        el.btnCancelarEdicaoFuncao?.classList.add('hidden');
                    };

                    const buildFuncaoCard = ({ id, nome, checked = true, funcionarios = 0, ghes = 0, ativo = true }) => {
                        const label = document.createElement('label');
                        label.className = `flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 ${ativo ? '' : 'opacity-70'}`;
                        label.dataset.funcaoCard = 'true';
                        label.dataset.funcaoId = String(id);
                        label.dataset.funcaoNome = String(nome || '');
                        label.dataset.funcaoAtivo = ativo ? '1' : '0';
                        label.dataset.funcaoFuncionarios = String(funcionarios || 0);
                        label.dataset.funcaoGhes = String(ghes || 0);
                        label.innerHTML = `
                            <input type="checkbox"
                                   name="${inputName}"
                                   value="${id}"
                                   class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                   ${checked ? 'checked' : ''}>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="block truncate text-sm font-semibold text-slate-800" data-role="funcao-title"></span>
                                    <span class="flex items-center gap-2 shrink-0">
                                        <span class="${ativo ? 'hidden ' : ''}rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700" data-role="funcao-status">Inativa</span>
                                        <button type="button" class="text-[11px] font-semibold text-blue-700 hover:text-blue-800" data-action="edit-funcao">Editar</button>
                                    </span>
                                </span>
                                <span class="mt-1 block text-[11px] text-slate-500" data-role="funcao-meta">Funcionários: ${funcionarios} | GHEs: ${ghes}</span>
                            </span>
                        `;
                        const title = label.querySelector('[data-role="funcao-title"]');
                        if (title) title.textContent = String(nome || '');
                        label.classList.toggle('border-emerald-300', checked);
                        label.classList.toggle('bg-emerald-50/40', checked);
                        return label;
                    };

                    const upsertFuncaoCard = ({ id, nome, checked = true, funcionarios = 0, ghes = 0, ativo = true }) => {
                        const existing = el.funcoesClienteGrid.querySelector(`input[name="${inputName}"][value="${id}"]`);
                        if (!existing) {
                            const emptyState = el.funcoesClienteGrid.querySelector('.col-span-full');
                            if (emptyState) emptyState.remove();
                            el.funcoesClienteGrid.appendChild(buildFuncaoCard({ id, nome, checked, funcionarios, ghes, ativo }));
                            refreshFuncoesClienteGrid();
                            return;
                        }

                        existing.checked = checked;
                        const card = existing.closest('[data-funcao-card]');
                        if (!card) return;
                        card.dataset.funcaoNome = String(nome || '');
                        card.dataset.funcaoAtivo = ativo ? '1' : '0';
                        card.dataset.funcaoFuncionarios = String(funcionarios || 0);
                        card.dataset.funcaoGhes = String(ghes || 0);
                        card.classList.toggle('opacity-70', !ativo);
                        const title = card.querySelector('[data-role="funcao-title"]');
                        const status = card.querySelector('[data-role="funcao-status"]');
                        const meta = card.querySelector('[data-role="funcao-meta"]');
                        if (title) title.textContent = String(nome || '');
                        if (status) status.classList.toggle('hidden', !!ativo);
                        if (meta) meta.textContent = `Funcionários: ${funcionarios} | GHEs: ${ghes}`;
                        card.classList.toggle('border-emerald-300', checked);
                        card.classList.toggle('bg-emerald-50/40', checked);
                        refreshFuncoesClienteGrid();
                    };

                    const removeFuncaoCard = (id) => {
                        const input = el.funcoesClienteGrid.querySelector(`input[name="${inputName}"][value="${id}"]`);
                        const card = input?.closest('[data-funcao-card]');
                        if (card) card.remove();
                        if (!el.funcoesClienteGrid.querySelector('[data-funcao-card]')) {
                            const empty = document.createElement('div');
                            empty.className = 'col-span-full rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800';
                            empty.textContent = 'Nenhuma função cadastrada para a empresa.';
                            el.funcoesClienteGrid.appendChild(empty);
                        }
                        refreshFuncoesClienteGrid();
                    };

                    const startEditing = (card) => {
                        const id = Number(card?.dataset.funcaoId || 0);
                        if (!id) return;
                        state.funcaoClienteEditingId = id;
                        el.novaFuncaoNome.value = String(card.dataset.funcaoNome || '');
                        el.novaFuncaoNome.placeholder = 'Editar função';
                        el.btnNovaFuncaoParametro.textContent = 'Salvar edição';
                        el.btnNovaFuncaoParametro.classList.remove('border-blue-200', 'bg-blue-50', 'text-blue-800');
                        el.btnNovaFuncaoParametro.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
                        el.btnCancelarEdicaoFuncao?.classList.remove('hidden');
                        el.novaFuncaoNome.focus();
                        el.novaFuncaoNome.select();
                    };

                    const submit = async () => {
                        const nome = String(el.novaFuncaoNome.value || '').trim();
                        if (!nome) {
                            showItemAlert('Informe o nome da função.');
                            return;
                        }

                        const editingId = Number(state.funcaoClienteEditingId || 0) || null;
                        const url = editingId ? URLS.funcoesUpdate(editingId) : URLS.funcoesStore;
                        const method = editingId ? 'PUT' : 'POST';

                        el.btnNovaFuncaoParametro.disabled = true;
                        el.btnCancelarEdicaoFuncao?.setAttribute('disabled', 'disabled');

                        try {
                            const existingCard = editingId
                                ? el.funcoesClienteGrid.querySelector(`[data-funcao-id="${editingId}"]`)
                                : null;
                            const res = await fetch(url, {
                                method,
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': CSRF,
                                },
                                body: JSON.stringify({
                                    nome,
                                    ...(editingId ? { ativo: true } : {}),
                                }),
                            });

                            const json = await res.json().catch(() => ({}));
                            const payloadFuncao = json?.funcao || null;
                            if (!res.ok || !payloadFuncao?.id) {
                                const msg = json?.message || `Não foi possível ${editingId ? 'atualizar' : 'cadastrar'} a função.`;
                                showItemAlert(msg, 'error');
                                return;
                            }

                            const existingUpdatedCard = el.funcoesClienteGrid.querySelector(`[data-funcao-id="${payloadFuncao.id}"]`);
                            upsertFuncaoCard({
                                id: Number(payloadFuncao.id),
                                nome: payloadFuncao.nome || nome,
                                checked: true,
                                funcionarios: Number((existingUpdatedCard || existingCard)?.dataset.funcaoFuncionarios || 0),
                                ghes: Number((existingUpdatedCard || existingCard)?.dataset.funcaoGhes || 0),
                                ativo: payloadFuncao.ativo !== false,
                            });

                            resetFuncoesEditor();
                            showItemToast(editingId
                                ? `Função atualizada: ${payloadFuncao.nome}`
                                : (json?.message || (json?.existing ? 'Esta função já existe. Selecione ela para o cliente.' : `Função cadastrada: ${payloadFuncao.nome}`)));
                        } catch (e) {
                            console.error(e);
                            showItemAlert(`Falha ao ${editingId ? 'atualizar' : 'cadastrar'} função.`, 'error');
                        } finally {
                            el.btnNovaFuncaoParametro.disabled = false;
                            el.btnCancelarEdicaoFuncao?.removeAttribute('disabled');
                        }
                    };

                    const destroy = async (card) => {
                        const id = Number(card?.dataset.funcaoId || 0);
                        if (!id) return;

                        const ok = await window.uiConfirm('Deseja excluir esta função?');
                        if (!ok) return;

                        try {
                            const res = await fetch(URLS.funcoesDestroy(id), {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': CSRF,
                                },
                            });

                            const json = await res.json().catch(() => ({}));
                            if (!res.ok) {
                                showItemAlert(json?.message || 'Não foi possível excluir a função.', 'error');
                                return;
                            }

                            const funcao = json?.funcao || {};
                            if (funcao.id && funcao.ativo === false) {
                                upsertFuncaoCard({
                                    id: Number(funcao.id),
                                    nome: funcao.nome || card.dataset.funcaoNome || '',
                                    checked: !!card.querySelector(`input[name="${inputName}"]`)?.checked,
                                    funcionarios: Number(card.dataset.funcaoFuncionarios || 0),
                                    ghes: Number(card.dataset.funcaoGhes || 0),
                                    ativo: false,
                                });
                            } else {
                                removeFuncaoCard(id);
                            }

                            if (state.funcaoClienteEditingId === id) {
                                resetFuncoesEditor();
                            }

                            showItemToast(json?.message || 'Função removida.');
                        } catch (e) {
                            console.error(e);
                            showItemAlert('Falha ao excluir função.', 'error');
                        }
                    };

                    el.btnNovaFuncaoParametro.addEventListener('click', submit);
                    el.btnCancelarEdicaoFuncao?.addEventListener('click', resetFuncoesEditor);
                    el.funcoesClienteBusca?.addEventListener('input', refreshFuncoesClienteGrid);
                    el.btnSelecionarTodasFuncoes?.addEventListener('click', () => {
                        getFuncaoCards().forEach((card) => {
                            const checkbox = card.querySelector(`input[name="${inputName}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                        refreshFuncoesClienteGrid();
                    });
                    el.novaFuncaoNome.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            submit();
                        }
                    });
                    el.funcoesClienteGrid.addEventListener('change', (e) => {
                        if (e.target.matches(`input[name="${inputName}"]`)) {
                            refreshFuncoesClienteGrid();
                        }
                    });

                    el.funcoesClienteGrid.addEventListener('click', (e) => {
                        const actionEl = e.target.closest('[data-action]');
                        if (!actionEl) return;
                        e.preventDefault();
                        e.stopPropagation();

                        const card = actionEl.closest('[data-funcao-card]');
                        if (!card) return;

                        if (actionEl.dataset.action === 'edit-funcao') {
                            startEditing(card);
                            return;
                        }

                        if (actionEl.dataset.action === 'delete-funcao') {
                            destroy(card);
                        }
                    });

                    refreshFuncoesClienteGrid();
                }

                function attachMoneyMask(viewEl, hiddenEl) {
                    if (!viewEl || !hiddenEl) return;
                    if (viewEl.dataset.maskReady === '1') return;
                    viewEl.dataset.maskReady = '1';

                    viewEl.dataset.digits = onlyDigits(Math.round(Number(hiddenEl.value || 0) * 100));
                    viewEl.value = brl(Number(hiddenEl.value || 0));

                    viewEl.addEventListener('keydown', (e) => {
                        const nav = ['Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End','Delete'];
                        if (e.ctrlKey || e.metaKey) return;

                        if (e.key === 'Backspace') {
                            e.preventDefault();
                            const d = viewEl.dataset.digits || '';
                            const nd = d.slice(0, -1);
                            viewEl.dataset.digits = nd;

                            const num = centsDigitsToNumber(nd);
                            hiddenEl.value = num.toFixed(2);
                            viewEl.value = brl(num);
                            return;
                        }
                        if (nav.includes(e.key)) return;
                        if (!/^\d$/.test(e.key)) e.preventDefault();
                    });

                    viewEl.addEventListener('input', () => {
                        const digits = onlyDigits(viewEl.value);
                        viewEl.dataset.digits = digits;

                        const num = centsDigitsToNumber(digits);
                        hiddenEl.value = num.toFixed(2);
                        viewEl.value = brl(num);
                    });
                }

                function uid() { return 'i_' + Math.random().toString(16).slice(2) + Date.now(); }

                function brl(n) {
                    return Number(n || 0).toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
                }

                function onlyDigits(str) { return String(str || '').replace(/\D+/g, ''); }
                function centsDigitsToNumber(d) { return (parseInt(d || '0', 10) / 100); }
                function setMoneyValue(viewEl, hiddenEl, value) {
                    if (!viewEl || !hiddenEl) return;
                    const num = Number(value || 0);
                    hiddenEl.value = num.toFixed(2);
                    viewEl.value = brl(num);
                    viewEl.dataset.digits = onlyDigits(Math.round(num * 100));
                }

                function recalcItemTotal(item) {
                    const unit = Number(item.valor_unitario || 0);
                    const qtd  = Number(item.quantidade || 1);
                    const acres = Number(item.acrescimo || 0);
                    const desc  = Number(item.desconto || 0);
                    item.valor_total = Math.max(0, (unit * qtd) + acres - desc);
                    return item.valor_total;
                }

                function removeEsocialItens() {
                    const servicoId = Number(SERVICO_ESOCIAL_ID || 0);
                    const before = state.itens.length;
                    state.itens = state.itens.filter(it => {
                        const tipo = String(it.tipo || '').toUpperCase();
                        const itemServicoId = Number(it.servico_id || 0);
                        if (tipo === 'ESOCIAL') return false;
                        if (servicoId > 0 && itemServicoId === servicoId) return false;
                        return true;
                    });
                    return before !== state.itens.length;
                }

                function getEsocialListItem() {
                    if (!state.esocial.enabled || Number(state.esocial.valor || 0) <= 0) {
                        return null;
                    }

                    return {
                        id: '__esocial__',
                        servico_id: Number(SERVICO_ESOCIAL_ID || 0) || null,
                        tipo: 'ESOCIAL',
                        nome: 'eSocial',
                        descricao: `eSocial (${state.esocial.qtd || 0} colaboradores)`,
                        valor_unitario: Number(state.esocial.valor || 0),
                        quantidade: 1,
                        prazo: '',
                        acrescimo: 0,
                        desconto: 0,
                        meta: { qtd_funcionarios: state.esocial.qtd || 0, virtual: true },
                        valor_total: Number(state.esocial.valor || 0),
                    };
                }

                function recalcTotals() {
                    let total = 0;
                    removeEsocialItens();
                    state.itens.forEach(i => total += Number(i.valor_total || 0));
                    if (state.esocial.enabled) total += Number(state.esocial.valor || 0);
                    if (el.total) {
                        el.total.textContent = brl(total);
                    }
                    updateTabBadges();
                }

                function syncHiddenInputs() {
                    removeEsocialItens();
                    // remove inputs anteriores
                    document.querySelectorAll('[data-hidden-itens]').forEach(n => n.remove());
                    document.querySelectorAll('[data-hidden-aso-grupos]').forEach(n => n.remove());

                    state.itens.forEach((it, idx) => {
                        const base = `itens[${idx}]`;

                        const pairs = [
                            ['servico_id', it.servico_id ?? ''],
                            ['tipo', it.tipo],
                            ['nome', it.nome],
                            ['descricao', it.descricao ?? ''],
                            ['valor_unitario', Number(it.valor_unitario || 0).toFixed(2)],
                            ['quantidade', it.quantidade],
                            ['prazo', it.prazo ?? ''],
                            ['acrescimo', Number(it.acrescimo || 0).toFixed(2)],
                            ['desconto', Number(it.desconto || 0).toFixed(2)],
                            ['valor_total', Number(it.valor_total || 0).toFixed(2)],
                            ['meta', it.meta ? JSON.stringify(it.meta) : ''],
                        ];

                        pairs.forEach(([k,v]) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `${base}[${k}]`;
                            input.value = v;
                            input.setAttribute('data-hidden-itens','1');
                            el.form.appendChild(input);
                        });
                    });

                    // eSocial como item (quando existir serviço vinculado)
                    if (state.esocial.enabled && Number(SERVICO_ESOCIAL_ID) > 0 && Number(state.esocial.valor || 0) > 0) {
                        const idx = state.itens.length;
                        const base = `itens[${idx}]`;
                        const meta = { qtd_funcionarios: state.esocial.qtd || 0 };
                        const pairs = [
                            ['servico_id', Number(SERVICO_ESOCIAL_ID)],
                            ['tipo', 'ESOCIAL'],
                            ['nome', 'eSocial'],
                            ['descricao', `eSocial (${state.esocial.qtd || 0} colaboradores)`],
                            ['valor_unitario', Number(state.esocial.valor || 0).toFixed(2)],
                            ['quantidade', 1],
                            ['prazo', ''],
                            ['acrescimo', Number(0).toFixed(2)],
                            ['desconto', Number(0).toFixed(2)],
                            ['valor_total', Number(state.esocial.valor || 0).toFixed(2)],
                            ['meta', JSON.stringify(meta)],
                        ];

                        pairs.forEach(([k,v]) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `${base}[${k}]`;
                            input.value = v;
                            input.setAttribute('data-hidden-itens','1');
                            el.form.appendChild(input);
                        });
                    }

                    // eSocial
                    el.esocialQtdHidden.value = state.esocial.enabled ? (state.esocial.qtd || 0) : '';
                    el.esocialValorHidden.value = state.esocial.enabled ? Number(state.esocial.valor || 0).toFixed(2) : '0.00';

                    state.gheConfigs.forEach((cfg, idx) => {
                        const base = `cliente_aso_grupos[${idx}]`;
                        const pairs = [
                            ['ghe_id', cfg.ghe_id ?? ''],
                            ['cliente_ghe_id', cfg.cliente_ghe_id ?? ''],
                            ['ghe_nome', cfg.ghe_nome ?? ''],
                        ];
                        pairs.forEach(([k,v]) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `${base}[${k}]`;
                            input.value = v;
                            input.setAttribute('data-hidden-aso-grupos','1');
                            el.form.appendChild(input);
                        });

                        ASO_TYPES.forEach(({ key }) => {
                            const row = cfg.tipos?.[key];
                            if (!row?.grupo_id) return;
                            const rowBase = `${base}[tipos][${key}]`;
                            const rowPairs = [
                                ['grupo_id', row.grupo_id],
                                ['total_exames', Number(row.total_exames || 0).toFixed(2)],
                            ];
                            rowPairs.forEach(([k,v]) => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = `${rowBase}[${k}]`;
                                input.value = v;
                                input.setAttribute('data-hidden-aso-grupos','1');
                                el.form.appendChild(input);
                            });
                        });
                    });
                }

                function getItemTipoUi(item) {
                    const tipo = String(item?.tipo || '').toUpperCase();
                    const map = {
                        SERVICO: { label: 'Serviço', cls: 'border-slate-200 bg-slate-100 text-slate-700' },
                        TREINAMENTO_NR: { label: 'Treinamento', cls: 'border-blue-200 bg-blue-50 text-blue-700' },
                        PACOTE_TREINAMENTOS: { label: 'Pacote Trein.', cls: 'border-emerald-200 bg-emerald-50 text-emerald-700' },
                        EXAME: { label: 'Exame', cls: 'border-cyan-200 bg-cyan-50 text-cyan-700' },
                        PACOTE_EXAMES: { label: 'Pacote Exames', cls: 'border-teal-200 bg-teal-50 text-teal-700' },
                        MEDICAO: { label: 'Medição', cls: 'border-indigo-200 bg-indigo-50 text-indigo-700' },
                        ASO_TIPO: { label: 'ASO', cls: 'border-amber-200 bg-amber-50 text-amber-700' },
                        ESOCIAL: { label: 'eSocial', cls: 'border-violet-200 bg-violet-50 text-violet-700' },
                    };
                    return map[tipo] || { label: tipo || 'Item', cls: 'border-slate-200 bg-slate-100 text-slate-700' };
                }

                function getItemMetaResumo(item) {
                    if (item?.meta?.treinamentos?.length) {
                        return `${item.meta.treinamentos.length} treinamento(s)`;
                    }
                    if (item?.meta?.exames?.length) {
                        return `${item.meta.exames.length} exame(s)`;
                    }
                    if (item?.meta?.codigo) {
                        return `NR ${item.meta.codigo}`;
                    }
                    if (item?.meta?.medicao_titulo) {
                        return item.meta.medicao_titulo;
                    }
                    if (item?.meta?.aso_tipo) {
                        return '';
                    }
                    return '';
                }

                // =========================
                // Render card (igual protótipo)
                // =========================
                function render() {
                    el.lista.innerHTML = '';
                    removeEsocialItens();
                    const esocialItem = getEsocialListItem();
                    const itensRender = esocialItem ? [esocialItem, ...state.itens] : [...state.itens];
                    if (el.listaCount) {
                        el.listaCount.textContent = `${itensRender.length} item${itensRender.length === 1 ? '' : 's'}`;
                    }

                    if (!itensRender.length) {
                        el.lista.innerHTML = `
                            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center">
                                <div class="text-sm font-semibold text-slate-700">Nenhum item adicionado</div>
                                <div class="mt-1 text-xs text-slate-500">Use os botões acima para incluir serviços, treinamentos, ASO ou pacotes.</div>
                            </div>
                        `;
                        recalcTotals();
                        syncHiddenInputs();
                        return;
                    }

                    let rowIndex = 0;
                    itensRender.forEach(item => {
                        const hasZeroPrice = Number(item.valor_unitario || 0) <= 0;
                        const isAsoTipo = !!item?.meta?.aso_tipo;
                        const isEsocialVirtual = item.id === '__esocial__';
                        const isPacoteTreinamentos = String(item?.tipo || '').toUpperCase() === 'PACOTE_TREINAMENTOS';
                        const tipoUi = getItemTipoUi(item);
                        const metaResumo = getItemMetaResumo(item);
                        const stripeClass = rowIndex % 2 === 0 ? 'bg-white' : 'bg-slate-50/60';
                        const row = document.createElement('div');
                        row.setAttribute('data-item-id', String(item.id));
                        row.className = hasZeroPrice
                            ? 'grid grid-cols-12 gap-2 items-center rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2 shadow-sm'
                            : `grid grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 px-3 py-2 shadow-sm hover:shadow-md transition-shadow ${stripeClass}`;
                        rowIndex += 1;

                        row.innerHTML = `
                <div class="col-span-12 md:col-span-7">
                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Item</div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${tipoUi.cls}">
                                ${escapeHtml(tipoUi.label)}
                            </span>
                            ${metaResumo ? `<span class="text-[11px] text-slate-500 truncate">${escapeHtml(metaResumo)}</span>` : ``}
                        </div>
                        <div class="mt-1 font-semibold text-slate-800 text-sm leading-tight truncate">${escapeHtml(item.nome)}</div>
                        ${isPacoteTreinamentos ? `
                            <button type="button"
                                    class="mt-1 text-[11px] font-semibold text-blue-600 hover:underline"
                                    data-act="edit-pacote-treinamentos">
                                Alterar treinamentos do pacote
                            </button>
                        ` : ``}
                        ${item.descricao ? `<div class="mt-0.5 text-[11px] text-slate-500 truncate">${escapeHtml(item.descricao)}</div>` : ``}
                        ${hasZeroPrice ? `<div class="mt-1 flex flex-wrap items-center gap-2 text-[11px]">
                            <span class="inline-flex items-center font-semibold text-amber-700 bg-amber-100/70 px-2 py-0.5 rounded-full">Sem preço definido</span>
                            <a href="{{ route('comercial.tabela-precos.itens.index') }}" target="_blank" rel="noopener"
                               class="text-amber-800 underline decoration-dotted hover:text-amber-900">
                                Abrir tabela de preços
                            </a>
                        </div>` : ``}
                    </div>
                </div>

                <div class="col-span-6 md:col-span-2">
                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Valor</div>
                    <input type="text" class="w-full h-9 rounded-lg border border-slate-200 bg-white text-sm px-2.5 font-medium shadow-sm focus:border-emerald-400 focus:ring-emerald-200"
                           data-act="valor_view" value="${brl(item.valor_unitario)}">
                </div>

                <div class="hidden col-span-6 md:col-span-2">
                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Prazo</div>
                    <input type="text" class="w-full h-8 rounded-md border border-slate-200 text-sm px-2"
                           data-act="prazo" placeholder="Ex: 15 dias">
                </div>

                <div class="hidden col-span-6 md:col-span-2">
                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Qtd</div>
                    <div class="inline-flex items-center gap-1">
                        <button type="button" class="h-8 w-8 rounded-md border border-slate-200 hover:bg-slate-50 text-sm" data-act="qtd_minus">-</button>
                        <input type="text" class="h-8 w-10 text-center rounded-md border border-slate-200 text-sm" data-act="qtd" value="${item.quantidade}">
                        <button type="button" class="h-8 w-8 rounded-md border border-slate-200 hover:bg-slate-50 text-sm" data-act="qtd_plus">+</button>
                    </div>
                </div>

                <div class="col-span-4 md:col-span-2 text-right md:text-right">
                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Total</div>
                    <span data-el="valor_total" class="inline-flex items-center justify-end rounded-full bg-emerald-50 px-2.5 py-1 text-sm font-semibold text-emerald-700">
                        ${brl(item.valor_total)}
                    </span>
                </div>

                <div class="col-span-2 md:col-span-1 flex justify-end md:justify-center">
                    <button type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 hover:bg-red-50 hover:text-red-700 text-lg leading-none shadow-sm"
                            data-act="remove"
                            aria-label="Remover item">
                        ×
                    </button>
                </div>
            `;

                        // Actions
                        row.querySelector('[data-act="remove"]').addEventListener('click', () => {
                            if (isEsocialVirtual) {
                                if (el.chkEsocial) {
                                    el.chkEsocial.checked = false;
                                    el.chkEsocial.dispatchEvent(new Event('change'));
                                }
                                return;
                            }
                            removeItem(item.id);
                        });
                        row.querySelector('[data-act="edit-pacote-treinamentos"]')?.addEventListener('click', () => {
                            openPacoteTreinamentosModal(item);
                        });

                        // Prazo
                        const prazoInput = row.querySelector('[data-act="prazo"]');
                        if (isAsoTipo || isEsocialVirtual) {
                            prazoInput.value = '—';
                            prazoInput.setAttribute('readonly', 'readonly');
                            prazoInput.classList.add('bg-slate-100', 'cursor-not-allowed');
                        } else {
                            prazoInput.addEventListener('input', (e) => {
                                item.prazo = e.target.value || '';
                                syncHiddenInputs();
                            });
                        }

	                        // Qtd
                        const qtdMinus = row.querySelector('[data-act="qtd_minus"]');
                        const qtdPlus = row.querySelector('[data-act="qtd_plus"]');
                        if (isAsoTipo || isEsocialVirtual) {
                            qtdMinus.setAttribute('disabled', 'disabled');
                            qtdPlus.setAttribute('disabled', 'disabled');
                            qtdMinus.classList.add('opacity-50', 'cursor-not-allowed');
                            qtdPlus.classList.add('opacity-50', 'cursor-not-allowed');
                        } else {
                            qtdMinus.addEventListener('click', () => updateQtd(item.id, -1));
                            qtdPlus.addEventListener('click', () => updateQtd(item.id, +1));
                        }

	                        // Input qtd (manual)
                        const qtdInput = row.querySelector('[data-act="qtd"]');
                        if (isAsoTipo || isEsocialVirtual) {
                            qtdInput.setAttribute('readonly', 'readonly');
                            qtdInput.classList.add('bg-slate-100', 'cursor-not-allowed');
                            qtdInput.value = '1';
                        } else {
                            qtdInput.addEventListener('input', (e) => {
                                const n = parseInt(String(e.target.value || '1').replace(/\D+/g,''), 10) || 1;
                                item.quantidade = Math.max(1, n);
                                recalcItemTotal(item);
                                e.target.value = String(item.quantidade);
                                const totalEl = row.querySelector('[data-el="valor_total"]');
                                if (totalEl) totalEl.textContent = brl(item.valor_total);
                                recalcTotals();
                                syncHiddenInputs();
                            });
                        }

                        // Valor (máscara por centavos)
                        const valorView = row.querySelector('[data-act="valor_view"]');
                        valorView.dataset.digits = onlyDigits(Math.round(Number(item.valor_unitario || 0) * 100));
                        if (isEsocialVirtual) {
                            valorView.setAttribute('readonly', 'readonly');
                            valorView.classList.add('bg-slate-100', 'cursor-not-allowed');
                        }

                        valorView.addEventListener('keydown', (e) => {
                            if (isEsocialVirtual) {
                                e.preventDefault();
                                return;
                            }
                            const nav = ['Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End','Delete'];
                            if (e.ctrlKey || e.metaKey) return;

                            if (e.key === 'Backspace') {
                                e.preventDefault();
                                const d = valorView.dataset.digits || '';
                                const nd = d.slice(0, -1);
                                valorView.dataset.digits = nd;

	                                const num = centsDigitsToNumber(nd);
	                                item.valor_unitario = num;
	                                recalcItemTotal(item);
	                                valorView.value = brl(num);
	                                const totalEl = row.querySelector('[data-el="valor_total"]');
	                                if (totalEl) totalEl.textContent = brl(item.valor_total);
	                                recalcTotals();
	                                syncHiddenInputs();
                                    if (isAsoTipo) updateAsoConfigFromItem(item, num);
	                                return;
	                            }

                            if (nav.includes(e.key)) return;
                            if (!/^\d$/.test(e.key)) e.preventDefault();
                        });

                        valorView.addEventListener('input', () => {
                            if (isEsocialVirtual) {
                                valorView.value = brl(item.valor_unitario);
                                return;
                            }
                            const digits = onlyDigits(valorView.value);
                            valorView.dataset.digits = digits;

                            const num = centsDigitsToNumber(digits);
	                            item.valor_unitario = num;
	                            recalcItemTotal(item);

	                            valorView.value = brl(num);
	                            const totalEl = row.querySelector('[data-el="valor_total"]');
	                            if (totalEl) totalEl.textContent = brl(item.valor_total);
	                            recalcTotals();
	                            syncHiddenInputs();
                                if (isAsoTipo) updateAsoConfigFromItem(item, num);
	                        });

                        el.lista.appendChild(row);
                    });

                    recalcTotals();
                    syncHiddenInputs();
                }

                function escapeHtml(str) {
                    return String(str || '')
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", "&#039;");
                }

                let toastTimer = null;
                function showItemToast(message) {
                    if (!el.itemToast || !el.itemToastText) return;
                    el.itemToastText.textContent = message || '';
                    el.itemToast.classList.remove('hidden', 'opacity-0', 'translate-y-2');
                    el.itemToast.classList.add('opacity-100', 'translate-y-0');
                    if (toastTimer) clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => {
                        el.itemToast.classList.add('opacity-0', 'translate-y-2');
                        setTimeout(() => el.itemToast.classList.add('hidden'), 200);
                    }, 2000);
                }

                let alertTimer = null;
                function showItemAlert(message, tone = 'warn') {
                    if (!el.itemAlert || !el.itemAlertText) return;
                    el.itemAlertText.textContent = message || '';
                    el.itemAlert.classList.remove('hidden', 'opacity-0', 'translate-y-2');
                    el.itemAlert.classList.add('opacity-100', 'translate-y-0');
                    el.itemAlert.classList.toggle('border-rose-200', tone === 'error');
                    el.itemAlert.classList.toggle('bg-rose-50', tone === 'error');
                    el.itemAlert.classList.toggle('text-rose-700', tone === 'error');
                    el.itemAlert.classList.toggle('border-amber-200', tone !== 'error');
                    el.itemAlert.classList.toggle('bg-amber-50', tone !== 'error');
                    el.itemAlert.classList.toggle('text-amber-800', tone !== 'error');
                    if (alertTimer) clearTimeout(alertTimer);
                    alertTimer = setTimeout(() => {
                        el.itemAlert.classList.add('opacity-0', 'translate-y-2');
                        setTimeout(() => el.itemAlert.classList.add('hidden'), 200);
                    }, 2600);
                }

                // =========================
                // Actions
                // =========================
                async function addServico(servicoId, servicoNome) {
                    const id = uid();
                    const clienteId = el.clienteSelect?.value || '';

                    // cria item base
                    const item = {
                        id,
                        servico_id: Number(servicoId),
                        tipo: 'SERVICO',
                        nome: servicoNome,
                        descricao: null,
                        valor_unitario: 0,
                        quantidade: 1,
                        prazo: '',
                        acrescimo: 0,
                        desconto: 0,
                        meta: null,
                        valor_total: 0,
                    };

                    // busca preço
                    try {
                        const res = await fetch(URLS.precoServico(servicoId, clienteId), { headers: { 'Accept':'application/json' } });
                        const json = await res.json();
                        const p = Number(json?.data?.preco || 0);
                        item.valor_unitario = p;
                    } catch (e) {}

                    recalcItemTotal(item);
                    state.itens.push(item);
                    render();
                    showItemToast(`Serviço: ${servicoNome}`);
                    if (Number(item.valor_unitario || 0) <= 0) {
                        showItemAlert(`Item ${servicoNome} sem preço definido na tabela de preço.`);
                    }
                }

                async function addTreinamentoNR(nr) {
                    const id = uid();
                    const clienteId = el.clienteSelect?.value || '';

                    const nome = `${nr.codigo} ${nr.titulo ? ('- ' + nr.titulo) : ''}`.trim();

                    const item = {
                        id,
                        servico_id: SERVICO_TREINAMENTO_ID ? Number(SERVICO_TREINAMENTO_ID) : null,
                        tipo: 'TREINAMENTO_NR',
                        nome,
                        descricao: nr.titulo || null,
                        valor_unitario: 0,
                        quantidade: 1,
                        prazo: 'Ex: 15 dias',
                        acrescimo: 0,
                        desconto: 0,
                        meta: { nr_id: Number(nr.id), codigo: nr.codigo, titulo: nr.titulo || null },
                        valor_total: 0,
                    };

                    try {
                        const res = await fetch(URLS.precoTreinamento(nr.codigo, clienteId), { headers: { 'Accept':'application/json' } });
                        const json = await res.json();
                        item.valor_unitario = Number(json?.data?.preco || 0);
                    } catch (e) {}

                    recalcItemTotal(item);
                    state.itens.push(item);
                    render();
                    showItemToast(`Treinamento: ${nome}`);
                    if (Number(item.valor_unitario || 0) <= 0) {
                        showItemAlert(`Item ${nr.codigo} sem preço definido na tabela de preço.`);
                    }
                }

                function buildPacoteDescricao(prefix, nomes, maxLen = 255) {
                    const lista = (nomes || []).map(n => String(n || '').trim()).filter(Boolean);
                    if (!lista.length) return prefix;

                    const full = lista.join(', ');
                    const base = `${prefix}: ${full}`;
                    if (base.length <= maxLen) return base;

                    let out = `${prefix}: `;
                    for (const nome of lista) {
                        const next = out.length > `${prefix}: `.length ? `${out}, ${nome}` : `${out}${nome}`;
                        if ((next + '…').length > maxLen) break;
                        out = next;
                    }
                    return (out + '…').slice(0, maxLen);
                }

                function addPacoteExames(nomePacote, valorPacote, exames) {
                    const id = uid();
                    const descricao = buildPacoteDescricao(
                        `Pacote com ${exames.length} exame(s)`,
                        exames.map(e => e.nome),
                    );

                    const item = {
                        id,
                        servico_id: SERVICO_EXAME_ID ? Number(SERVICO_EXAME_ID) : null,
                        tipo: 'PACOTE_EXAMES',
                        nome: nomePacote,
                        descricao,
                        valor_unitario: Number(valorPacote || 0),
                        quantidade: 1,
                        prazo: 'Ex: 15 dias',
                        acrescimo: 0,
                        desconto: 0,
                        meta: { exames },
                        valor_total: 0,
                    };

                    recalcItemTotal(item);
                    state.itens.push(item);
                    render();
                    showItemToast(`Pacote de exames: ${nomePacote}`);
                    if (Number(item.valor_unitario || 0) <= 0) {
                        showItemAlert(`Pacote ${nomePacote} sem preço definido.`);
                    }
                }

                function addExameAvulso(exame) {
                    const item = {
                        id: uid(),
                        servico_id: SERVICO_EXAME_ID ? Number(SERVICO_EXAME_ID) : null,
                        tipo: 'EXAME',
                        nome: exame.nome,
                        descricao: exame.descricao || null,
                        valor_unitario: Number(exame.valor || 0),
                        quantidade: 1,
                        prazo: 'Ex: 15 dias',
                        acrescimo: 0,
                        desconto: 0,
                        meta: { exame_id: exame.id },
                        valor_total: 0,
                    };

                    recalcItemTotal(item);
                    state.itens.push(item);
                    render();
                    showItemToast(`Exame: ${exame.nome}`);
                    if (Number(item.valor_unitario || 0) <= 0) {
                        showItemAlert(`Item ${exame.nome} sem preço definido na tabela de preço.`);
                    }
                }

                function updateQtd(itemId, delta) {
                    const it = state.itens.find(x => x.id === itemId);
                    if (!it) return;
                    it.quantidade = Math.max(1, Number(it.quantidade || 1) + delta);
                    recalcItemTotal(it);
                    render();
                }

                function removeItem(itemId) {
                    const item = state.itens.find(x => x.id === itemId);
                    if (item?.meta?.aso_tipo) {
                        const baseKey = String(item?.meta?.aso_cfg_key || '').trim();
                        const tipo = String(item?.meta?.aso_tipo || '').trim();
                        if (baseKey && tipo) {
                            const cfg = state.gheConfigs.find((c, idx) => getGheConfigRuntimeKey(c, idx) === baseKey);
                            if (cfg && cfg.tipos?.[tipo]) {
                                delete cfg.tipos[tipo];
                                if (!Object.keys(cfg.tipos || {}).length) {
                                    state.gheConfigs = state.gheConfigs.filter((c, idx) => getGheConfigRuntimeKey(c, idx) !== baseKey);
                                }
                            }
                        }
                        syncAsoTipoItems();
                        renderGheConfigsTable();
                        return;
                    }
                    state.itens = state.itens.filter(x => x.id !== itemId);
                    render();
                }

                // =========================
                // Treinamentos modal
                // =========================
                async function openTreinamentosModal() {
                    el.modalTrein.classList.remove('hidden');
                    el.nrChips.innerHTML = '<div class="text-sm text-slate-500">Carregando...</div>';

                    try {
                        const res = await fetch(URLS.treinamentosJson, { headers: { 'Accept':'application/json' } });
                        const json = await res.json();
                        const list = json.data || [];

                        el.nrChips.innerHTML = '';
                        list.forEach(nr => {
                            const b = document.createElement('button');
                            b.type = 'button';
                            b.className = 'px-3 py-1.5 rounded-full border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-blue-50';
                            b.textContent = nr.codigo;
                            b.addEventListener('click', () => {
                                closeTreinamentosModal();
                                addTreinamentoNR(nr);
                            });
                            el.nrChips.appendChild(b);
                        });
                    } catch (e) {
                        el.nrChips.innerHTML = '<div class="text-sm text-red-600">Falha ao carregar NRs.</div>';
                    }
                }

                window.closeTreinamentosModal = function() {
                    el.modalTrein.classList.add('hidden');
                }

                // =========================
                // Exames modal (dinâmico)
                // =========================
                async function loadExames(force = false) {
                    if (!force && state.exames.loaded) return state.exames.list;

                    const res = await fetch(URLS.examesJson, { headers: { 'Accept':'application/json' }});
                    const json = await res.json();

                    const list = (json?.data || [])
                        .filter(x => x && (x.ativo === true || x.ativo === 1))
                        .map(x => ({
                            id: Number(x.id),
                            nome: String(x.titulo ?? ''),
                            descricao: x.descricao ? String(x.descricao) : null,
                            valor: Number(x.preco || 0),
                        }))
                        .filter(x => x.id && x.nome);

                    state.exames.list = list;
                    state.exames.loaded = true;
                    return list;
                }

                function renderExamesAvulsos() {
                    if (!el.examesAvulsos) return;
                    el.examesAvulsos.innerHTML = '';

                    if (!state.exames.list.length) {
                        el.examesAvulsos.innerHTML = '<div class="text-sm text-slate-500">Nenhum exame ativo cadastrado.</div>';
                        return;
                    }

                    state.exames.list.forEach(ex => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'w-full px-3 py-2 rounded-xl border border-slate-200 text-left text-sm bg-white hover:bg-slate-50';
                        btn.innerHTML = `
                            <div class="font-semibold text-slate-800">${escapeHtml(ex.nome)}</div>
                            <div class="text-xs text-slate-500 flex items-center justify-between gap-2">
                                <span>${ex.descricao ? escapeHtml(ex.descricao) : '—'}</span>
                                <span class="font-semibold text-slate-700">${brl(ex.valor)}</span>
                            </div>
                        `;
                        btn.addEventListener('click', () => addExameAvulso(ex));
                        el.examesAvulsos.appendChild(btn);
                    });
                }

                async function openExamesModal() {
                    if (!el.modalExames) return;
                    el.modalExames.classList.remove('hidden');
                    el.examesList.innerHTML = '<div class="text-sm text-slate-500">Carregando exames...</div>';
                    if (el.pkgExamesNome) el.pkgExamesNome.value = '';
                    if (el.pkgExamesCount) el.pkgExamesCount.textContent = '0';
                    state.exames.manualPrice = false;
                    if (el.pkgExamesValorHidden) el.pkgExamesValorHidden.value = '0.00';
                    if (el.pkgExamesValorView) {
                        setMoneyValue(el.pkgExamesValorView, el.pkgExamesValorHidden, 0);
                        attachMoneyMask(el.pkgExamesValorView, el.pkgExamesValorHidden);
                    }

                    try {
                        const exames = await loadExames(true);
                        renderExamesAvulsos();

                        if (!exames.length) {
                            el.examesList.innerHTML = '<div class="text-sm text-slate-500">Nenhum exame ativo cadastrado na tabela de exames.</div>';
                            return;
                        }

                        el.examesList.innerHTML = '';
                        exames.forEach(ex => {
                            const row = document.createElement('label');
                            row.className = 'flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm';
                            row.innerHTML = `
                                <input type="checkbox" class="rounded border-slate-300" value="${ex.id}">
                                <span class="flex-1">
                                    <span class="font-semibold text-slate-800">${escapeHtml(ex.nome)}</span>
                                    ${ex.descricao ? `<span class="block text-xs text-slate-500">${escapeHtml(ex.descricao)}</span>` : ``}
                                </span>
                                <span class="font-semibold">${brl(ex.valor)}</span>
                                <span class="text-xs text-slate-400">#${ex.id}</span>
                            `;
                            el.examesList.appendChild(row);
                        });
                    } catch (e) {
                        console.error(e);
                        el.examesList.innerHTML = '<div class="text-sm text-red-600">Falha ao carregar exames.</div>';
                    }
                }

                window.closeExamesModal = function() {
                    if (!el.modalExames) return;
                    el.modalExames.classList.add('hidden');
                }

                el.pkgExamesValorView?.addEventListener('input', () => {
                    state.exames.manualPrice = true;
                });

                el.examesList?.addEventListener('change', () => {
                    const checked = el.examesList.querySelectorAll('input[type="checkbox"]:checked').length;
                    if (el.pkgExamesCount) el.pkgExamesCount.textContent = String(checked);

                    if (!state.exames.manualPrice && el.pkgExamesValorView && el.pkgExamesValorHidden) {
                        const ids = Array.from(el.examesList.querySelectorAll('input[type="checkbox"]:checked'))
                            .map(i => Number(i.value));
                        const escolhidos = (state.exames.list || []).filter(e => ids.includes(e.id));
                        const sugestao = escolhidos.reduce((acc, e) => acc + Number(e.valor || 0), 0);
                        setMoneyValue(el.pkgExamesValorView, el.pkgExamesValorHidden, sugestao);
                    }
                });

                window.confirmExames = function() {
                    const nomePacote = (el.pkgExamesNome?.value || '').trim();
                    if (!nomePacote) return window.uiAlert('Informe o nome do pacote.');

                    const ids = Array.from(el.examesList.querySelectorAll('input[type="checkbox"]:checked'))
                        .map(i => Number(i.value));

                    const escolhidos = (state.exames.list || []).filter(e => ids.includes(e.id));
                    if (!escolhidos.length) return window.uiAlert('Selecione pelo menos um exame.');

                    const valor = Number(el.pkgExamesValorHidden?.value || 0);
                    if (valor <= 0) return window.uiAlert('Informe o valor do pacote.');

                    closeExamesModal();
                    addPacoteExames(nomePacote, valor, escolhidos);
                }

                // =========================
                // Medições (LTCAT/LTIP)
                // =========================
                async function loadMedicoes(force = false) {
                    if (!force && state.medicoes.loaded) return state.medicoes.list;

                    const res = await fetch(URLS.medicoesJson, { headers: { 'Accept':'application/json' }});
                    const json = await res.json();

                    const list = (json?.data || [])
                        .filter(x => x && (x.ativo === true || x.ativo === 1))
                        .map(x => ({
                            id: Number(x.id),
                            titulo: String(x.titulo ?? ''),
                            descricao: x.descricao ? String(x.descricao) : null,
                            preco: Number(x.preco || 0),
                        }))
                        .filter(x => x.id && x.titulo);

                    state.medicoes.list = list;
                    state.medicoes.loaded = true;
                    return list;
                }

                function updateMedicoesTotals() {
                    const selected = (state.medicoes.list || []).filter(x => state.medicoesSelected.has(x.id));
                    const total = selected.reduce((acc, x) => acc + Number(x.preco || 0), 0);
                    if (el.medicoesCount) el.medicoesCount.textContent = String(selected.length);
                    if (el.medicoesTotal) el.medicoesTotal.textContent = brl(total);
                    if (el.medicoesConfirmBtn) {
                        el.medicoesConfirmBtn.disabled = selected.length === 0;
                        el.medicoesConfirmBtn.classList.toggle('opacity-50', selected.length === 0);
                        el.medicoesConfirmBtn.classList.toggle('cursor-not-allowed', selected.length === 0);
                    }
                }

                function renderMedicoesList() {
                    if (!el.medicoesList) return;
                    el.medicoesList.innerHTML = '';

                    if (!state.medicoes.list.length) {
                        el.medicoesList.innerHTML = '<div class="text-sm text-slate-500">Nenhuma medição ativa cadastrada.</div>';
                        updateMedicoesTotals();
                        return;
                    }

                    state.medicoes.list.forEach(m => {
                        const row = document.createElement('label');
                        row.className = 'flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm';
                        row.innerHTML = `
                            <input type="checkbox" class="rounded border-slate-300" value="${m.id}">
                            <span class="flex-1">
                                <span class="font-semibold text-slate-800">${escapeHtml(m.titulo)}</span>
                                ${m.descricao ? `<span class="block text-xs text-slate-500">${escapeHtml(m.descricao)}</span>` : ``}
                            </span>
                            <span class="font-semibold">${brl(m.preco)}</span>
                            <span class="text-xs text-slate-400">#${m.id}</span>
                        `;
                        const checkbox = row.querySelector('input[type="checkbox"]');
                        checkbox.checked = state.medicoesSelected.has(m.id);
                        checkbox.addEventListener('change', () => {
                            if (checkbox.checked) state.medicoesSelected.add(m.id);
                            else state.medicoesSelected.delete(m.id);
                            updateMedicoesTotals();
                            hideMedicoesAlert();
                        });
                        el.medicoesList.appendChild(row);
                    });

                    updateMedicoesTotals();
                }

                function showMedicoesAlert(msg) {
                    if (!el.medicoesAlert) return;
                    el.medicoesAlert.textContent = msg || '';
                    el.medicoesAlert.classList.remove('hidden');
                }

                function hideMedicoesAlert() {
                    el.medicoesAlert?.classList.add('hidden');
                }

                async function openMedicoesModal(target) {
                    if (!el.modalMedicoes) return;
                    state.medicoesTarget = target || null;
                    state.medicoesSelected = new Set();
                    hideMedicoesAlert();
                    if (el.medicoesTipoLabel) {
                        el.medicoesTipoLabel.textContent = target?.nome || 'LTCAT/LTIP';
                    }
                    if (el.medicoesList) {
                        el.medicoesList.innerHTML = '<div class="text-sm text-slate-500">Carregando medições...</div>';
                    }
                    updateMedicoesTotals();
                    el.modalMedicoes.classList.remove('hidden');

                    try {
                        await loadMedicoes(true);
                        renderMedicoesList();
                    } catch (e) {
                        console.error(e);
                        if (el.medicoesList) {
                            el.medicoesList.innerHTML = '<div class="text-sm text-red-600">Falha ao carregar medições.</div>';
                        }
                    }
                }

                window.closeMedicoesModal = function () {
                    el.modalMedicoes?.classList.add('hidden');
                };

                window.confirmMedicoes = function () {
                    const selected = (state.medicoes.list || []).filter(x => state.medicoesSelected.has(x.id));
                    if (!selected.length) {
                        showMedicoesAlert('Selecione ao menos 1 item de medição.');
                        return;
                    }

                    const target = state.medicoesTarget;
                    if (!target?.id) {
                        showMedicoesAlert('Serviço inválido.');
                        return;
                    }

                    selected.forEach((medicao) => {
                        const item = {
                            id: uid(),
                            servico_id: Number(target.id),
                            tipo: 'MEDICAO',
                            nome: `${target.nome} - ${medicao.titulo}`,
                            descricao: medicao.descricao || null,
                            valor_unitario: Number(medicao.preco || 0),
                            quantidade: 1,
                            prazo: '',
                            acrescimo: 0,
                            desconto: 0,
                            meta: {
                                medicao_tipo: target.nome,
                                medicao_id: medicao.id,
                                medicao_titulo: medicao.titulo,
                                medicao_preco: medicao.preco,
                            },
                            valor_total: 0,
                        };

                        recalcItemTotal(item);
                        state.itens.push(item);
                        if (Number(item.valor_unitario || 0) <= 0) {
                            showItemAlert(`Medição ${medicao.titulo} sem preço definido.`);
                        }
                    });
                    closeMedicoesModal();
                    render();
                    showItemToast(`Medições ${target.nome}: ${selected.length} item(ns)`);
                };

                // =========================
                // eSocial cálculo
                // =========================
                async function updateEsocial(qtd) {
                    const parsedQtd = Number(qtd || 0);
                    state.esocial.qtd = Number.isFinite(parsedQtd) ? parsedQtd : 0;

                    if (!state.esocial.enabled || state.esocial.qtd <= 0) {
                        state.esocial.valor = 0;
                        state.esocial.aviso = null;
                        applyEsocialUI();
                        return;
                    }

                    try {
                        const res = await fetch(URLS.esocialPreco(state.esocial.qtd), { headers: { 'Accept':'application/json' } });

                        if (!res.ok) throw new Error('Falha ao consultar eSocial.');
                        const json = await res.json();

                        state.esocial.valor = Number(json?.data?.preco || 0);
                        state.esocial.aviso = json?.data?.aviso || null;
                    } catch (e) {
                        state.esocial.valor = 0;
                        state.esocial.aviso = 'Falha ao calcular faixa do eSocial.';
                    }

                    applyEsocialUI();
                }

                function applyEsocialUI() {
                    if (el.btnToggleEsocial) {
                        el.btnToggleEsocial.textContent = '+ eSocial';
                        el.btnToggleEsocial.classList.toggle('font-semibold', state.esocial.enabled);
                        el.btnToggleEsocial.classList.toggle('ring-2', state.esocial.enabled);
                        el.btnToggleEsocial.classList.toggle('ring-violet-300', state.esocial.enabled);
                    }

                    el.esocialBox?.classList.toggle('hidden', !state.esocial.enabled);

                    el.esocialValorView.value = brl(state.esocial.valor);
                    el.esocialQtdHidden.value = state.esocial.enabled ? state.esocial.qtd : '';
                    el.esocialValorHidden.value = state.esocial.enabled ? Number(state.esocial.valor || 0).toFixed(2) : '0.00';

                    if (state.esocial.aviso) {
                        el.esocialAviso.textContent = state.esocial.aviso;
                        el.esocialAviso.classList.remove('hidden');
                    } else {
                        el.esocialAviso.classList.add('hidden');
                    }

                    if (el.lista) {
                        render();
                        return;
                    }

                    recalcTotals();
                    syncHiddenInputs();
                }

                // =========================
                // Bind botões serviços
                // =========================
                document.querySelectorAll('[data-action="add-servico"]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const servicoId = btn.dataset.servicoId;
                        const servicoNome = String(btn.dataset.servicoNome || '');
                        if (MEDICAO_SERVICOS.has(servicoNome.toUpperCase())) {
                            openMedicoesModal({ id: Number(servicoId), nome: servicoNome });
                            return;
                        }
                        addServico(servicoId, servicoNome);
                    });
                });

                // document.getElementById('btnTreinamentos')?.addEventListener('click', openTreinamentosModal);
                document.getElementById('btnPacoteExames')?.addEventListener('click', openExamesModal);
                // Carrega lista de exames avulsos na seção
                (async function initExamesAvulsos() {
                    try {
                        if (el.examesAvulsos) {
                            el.examesAvulsos.innerHTML = '<div class="text-sm text-slate-500">Carregando exames...</div>';
                        }
                        await loadExames(true);
                        renderExamesAvulsos();
                    } catch (e) {
                        console.error(e);
                        if (el.examesAvulsos) {
                            el.examesAvulsos.innerHTML = '<div class="text-sm text-red-600">Falha ao carregar exames.</div>';
                        }
                    }
                })();

                // B) Botão “Pacote de Treinamentos”
                document.querySelectorAll('[data-action="add-treinamento"]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        addTreinamentoNR({
                            id: Number(btn.dataset.nrId),
                            codigo: btn.dataset.nrCodigo,
                            titulo: btn.dataset.nrTitulo
                        });
                    });
                });
                function openPacoteTreinamentosModal(item = null) {
                    if (!modalPkg || !pkgList || !pkgNome || !pkgCount || !pkgValorView || !pkgValorHidden) return;

                    const isEdit = !!item && String(item?.tipo || '').toUpperCase() === 'PACOTE_TREINAMENTOS';
                    state.pacoteTreinamentosEditItemId = isEdit ? item.id : null;

                    modalPkg.classList.remove('hidden');

                    const selectedIds = new Set(
                        isEdit
                            ? ((item?.meta?.treinamentos || []).map(t => Number(t?.id || 0)).filter(Boolean))
                            : []
                    );

                    pkgNome.value = isEdit ? String(item?.nome || '') : '';
                    pkgList.querySelectorAll('input[type="checkbox"]').forEach(c => {
                        c.checked = selectedIds.has(Number(c.value || 0));
                    });
                    pkgCount.textContent = String(selectedIds.size);

                    const valorAtual = isEdit ? Number(item?.valor_unitario || 0) : 0;
                    pkgValorHidden.value = Number(valorAtual || 0).toFixed(2);
                    pkgValorView.value = brl(valorAtual);
                    attachMoneyMask(pkgValorView, pkgValorHidden);
                }

                window.confirmPacoteTreinamentos = function () {
                    const nomePacote = (pkgNome.value || '').trim();
                    if (!nomePacote) return window.uiAlert('Informe o nome do pacote.');

                    const checks = Array.from(pkgList.querySelectorAll('input[type="checkbox"]:checked'));
                    if (!checks.length) return window.uiAlert('Selecione pelo menos um treinamento.');

                    const treinamentos = checks.map(c => ({
                        id: Number(c.value),
                        codigo: c.dataset.codigo,
                        titulo: c.dataset.titulo,
                    }));

                    const valor = Number(pkgValorHidden.value || 0);
                    const editingId = state.pacoteTreinamentosEditItemId;
                    const item = editingId ? state.itens.find(x => x.id === editingId) : null;
                    const descricaoPacote = buildPacoteDescricao(
                        `Pacote com ${treinamentos.length} treinamento(s)`,
                        treinamentos.map(t => `${t.codigo} ${t.titulo}`.trim())
                    );

                    if (item) {
                        item.servico_id = SERVICO_TREINAMENTO_ID ? Number(SERVICO_TREINAMENTO_ID) : (item.servico_id ?? null);
                        item.tipo = 'PACOTE_TREINAMENTOS';
                        item.nome = nomePacote;
                        item.descricao = descricaoPacote;
                        item.valor_unitario = valor;
                        item.meta = { treinamentos };
                        recalcItemTotal(item);
                    } else {
                        const novoItem = {
                            id: uid(),
                            servico_id: SERVICO_TREINAMENTO_ID ? Number(SERVICO_TREINAMENTO_ID) : null,
                            tipo: 'PACOTE_TREINAMENTOS',
                            nome: nomePacote,
                            descricao: descricaoPacote,
                            valor_unitario: valor,
                            quantidade: 1,
                            prazo: 'Ex: 15 dias',
                            acrescimo: 0,
                            desconto: 0,
                            meta: { treinamentos },
                            valor_total: 0,
                        };

                        recalcItemTotal(novoItem);
                        state.itens.push(novoItem);
                    }

                    closePacoteTreinamentosModal();
                    render();
                    showItemToast(`${editingId ? 'Pacote atualizado' : 'Pacote de treinamentos'}: ${nomePacote}`);
                    const valorChecado = item ? Number(item.valor_unitario || 0) : valor;
                    if (valorChecado <= 0) {
                        showItemAlert(`Pacote ${nomePacote} sem preço definido.`);
                    }
                };

                const modalPkg = document.getElementById('modalPacoteTreinamentos');
                const pkgCount = document.getElementById('pkgTreinCount');
                const pkgList  = document.getElementById('pkgTreinList');
                const pkgNome  = document.getElementById('pkgTreinNome');
                const pkgValorView = document.getElementById('pkgTreinValorView');
                const pkgValorHidden = document.getElementById('pkgTreinValorHidden');

                document.getElementById('btnPacoteTreinamentos')?.addEventListener('click', () => {
                    openPacoteTreinamentosModal();
                });

                window.closePacoteTreinamentosModal = () => {
                    state.pacoteTreinamentosEditItemId = null;
                    modalPkg.classList.add('hidden');
                };

                pkgList?.addEventListener('change', () => {
                    const checked = pkgList.querySelectorAll('input[type="checkbox"]:checked').length;
                    pkgCount.textContent = String(checked);
                });


                // =========================
                // eSocial UI toggles
                // =========================
                el.btnToggleEsocial?.addEventListener('click', () => {
                    if (!el.chkEsocial) return;

                    if (!el.chkEsocial.checked) {
                        state.esocial.enabled = true;
                        el.chkEsocial.checked = true;
                        el.esocialBox?.classList.remove('hidden');
                        el.chkEsocial.dispatchEvent(new Event('change'));
                        setTimeout(() => el.esocialQtd?.focus(), 0);
                        return;
                    }

                    state.esocial.enabled = true;
                    el.esocialBox?.classList.remove('hidden');
                    applyEsocialUI();
                    setTimeout(() => el.esocialQtd?.focus(), 0);
                });

                el.chkEsocial?.addEventListener('change', () => {
                    state.esocial.enabled = el.chkEsocial.checked;
                    el.esocialBox.classList.toggle('hidden', !state.esocial.enabled);
                    updateEsocial(el.esocialQtd.value || 0);
                });

                const handleEsocialInput = () => updateEsocial(el.esocialQtd.value);
                el.esocialQtd?.addEventListener('input', handleEsocialInput);
                el.esocialQtd?.addEventListener('change', handleEsocialInput);
                el.esocialQtd?.addEventListener('blur', handleEsocialInput);

                // Inicializa UI do eSocial (novo + edição + validação)
                if (el.chkEsocial) {
                    state.esocial.enabled = el.chkEsocial.checked;
                    el.esocialBox.classList.toggle('hidden', !state.esocial.enabled);
                }

                if (el.esocialQtd) {
                    const initialQtd = Number(el.esocialQtd.value || 0);
                    state.esocial.qtd = Number.isFinite(initialQtd) ? initialQtd : 0;
                }

                if (state.esocial.enabled && state.esocial.qtd > 0) {
                    updateEsocial(state.esocial.qtd);
                } else {
                    applyEsocialUI();
                }

                // Mensagem customizada para prazo do parâmetro
                const prazoInput = document.querySelector('input[name="prazo_dias"]');
                if (prazoInput) {
                    prazoInput.addEventListener('invalid', () => {
                        if (!prazoInput.value) {
                            prazoInput.setCustomValidity('Insira um prazo para o parâmetro');
                        } else {
                            prazoInput.setCustomValidity('');
                        }
                    });
                    prazoInput.addEventListener('input', () => {
                        prazoInput.setCustomValidity('');
                    });
                }

                const vencimentoInput = document.querySelector('input[name="vencimento_servicos"]');
                if (vencimentoInput) {
                    vencimentoInput.addEventListener('invalid', () => {
                        if (!vencimentoInput.value) {
                            vencimentoInput.setCustomValidity('Preencha o campo de vencimento');
                        } else {
                            vencimentoInput.setCustomValidity('');
                        }
                    });
                    vencimentoInput.addEventListener('input', () => {
                        vencimentoInput.setCustomValidity('');
                    });
                }

                function openClienteTabByFieldName(fieldName) {
                    let targetTab = 'parametros';
                    if (fieldName === 'forma_pagamento' || fieldName === 'vencimento_servicos') {
                        targetTab = 'dados';
                    } else if (fieldName === 'incluir_esocial' || String(fieldName || '').startsWith('esocial_')) {
                        targetTab = 'parametros';
                    }
                    const tabButton = document.querySelector(`[data-tabs="cliente"] [data-tab="${targetTab}"]`);
                    tabButton?.click();
                }

                function resolveFieldLabel(field) {
                    if (!field) return 'campo';
                    const id = field.id ? String(field.id) : '';
                    if (id) {
                        const byFor = document.querySelector(`label[for="${id}"]`);
                        if (byFor?.textContent?.trim()) return byFor.textContent.trim();
                    }
                    const parentLabel = field.closest('label');
                    if (parentLabel?.textContent?.trim()) return parentLabel.textContent.trim();
                    const blockLabel = field.closest('div')?.querySelector('label');
                    if (blockLabel?.textContent?.trim()) return blockLabel.textContent.trim();
                    return field.getAttribute('name') || 'campo';
                }

                function findMissingRequiredField() {
                    const requiredFields = Array.from(el.form.querySelectorAll('[required]'));
                    for (const field of requiredFields) {
                        if (!field || field.disabled) continue;
                        const type = String(field.type || '').toLowerCase();
                        const name = String(field.name || '');

                        if (type === 'checkbox' || type === 'radio') {
                            if (!name) continue;
                            const group = Array.from(el.form.querySelectorAll(`[name="${CSS.escape(name)}"]`));
                            const hasChecked = group.some(input => !input.disabled && input.checked);
                            if (!hasChecked) return field;
                            continue;
                        }

                        const value = String(field.value ?? '').trim();
                        if (value === '') return field;
                    }
                    return null;
                }

                // =========================
                // Submit: garantir meta JSON -> array (backend aceita array)
                // =========================
                el.form.addEventListener('submit', (e) => {
                    const missingRequired = findMissingRequiredField();
                    if (missingRequired) {
                        e.preventDefault();
                        const fieldName = String(missingRequired.getAttribute('name') || '');
                        openClienteTabByFieldName(fieldName);

                        let message = 'Preencha os campos obrigatórios para continuar.';
                        if (fieldName === 'forma_pagamento') {
                            message = 'Selecione a forma de pagamento para continuar.';
                        } else if (fieldName === 'vencimento_servicos') {
                            message = 'Informe o vencimento dos serviços para continuar.';
                        } else {
                            const label = resolveFieldLabel(missingRequired);
                            message = `Preencha o campo obrigatório: ${label}.`;
                        }

                        if (typeof window.uiAlert === 'function') {
                            window.uiAlert(message, {
                                icon: 'error',
                                title: 'Campo obrigatório',
                                confirmText: 'Entendi',
                            });
                        } else {
                            alert(message);
                        }

                        setTimeout(() => {
                            if (typeof missingRequired.focus === 'function') {
                                missingRequired.focus();
                            }
                        }, 50);
                        return;
                    }

                    const zeroItems = state.itens.filter(it => {
                        if (Number(it.valor_unitario || 0) > 0) return false;
                        return true;
                    });
                    if (zeroItems.length) {
                        e.preventDefault();
                        const nomes = Array.from(new Set(zeroItems.map(it => String(it.nome || 'Item sem nome'))));
                        const names = nomes.slice(0, 5).join(', ');
                        const extra = nomes.length > 5 ? ` e mais ${nomes.length - 5}` : '';
                        const msg = `Existem itens com preço zerado: ${names}${extra}. Ajuste o valor para continuar.`;
                        if (typeof window.uiAlert === 'function') {
                            window.uiAlert(msg, {
                                icon: 'error',
                                title: 'Preço obrigatório',
                                confirmText: 'Entendi',
                            });
                        }

                        const firstZeroId = String(zeroItems[0]?.id ?? '');
                        if (firstZeroId) {
                            const row = el.lista?.querySelector(`[data-item-id="${firstZeroId}"]`);
                            row?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            const valorInput = row?.querySelector('[data-act="valor_view"]');
                            if (valorInput && typeof valorInput.focus === 'function') {
                                valorInput.focus();
                                if (typeof valorInput.select === 'function') valorInput.select();
                            }
                        }
                    }
                });

                // =========================
                // Modais click fora / ESC
                // =========================
                function closeModalById(id, fnName) {
                    return () => {
                        const fn = window[fnName];
                        if (typeof fn === 'function') {
                            fn();
                            return;
                        }
                        document.getElementById(id)?.classList.add('hidden');
                    };
                }

                function parseModalZIndex(modalEl) {
                    if (!modalEl) return 0;
                    const computed = window.getComputedStyle(modalEl).zIndex;
                    const parsed = Number.parseInt(computed, 10);
                    return Number.isFinite(parsed) ? parsed : 0;
                }

                function getOpenModalStack() {
                    const closers = [
                        { id: 'modalTreinamentos', close: closeModalById('modalTreinamentos', 'closeTreinamentosModal') },
                        { id: 'modalExames', close: closeModalById('modalExames', 'closeExamesModal') },
                        { id: 'modalMedicoes', close: closeModalById('modalMedicoes', 'closeMedicoesModal') },
                        { id: 'modalPacoteTreinamentos', close: closeModalById('modalPacoteTreinamentos', 'closePacoteTreinamentosModal') },
                        { id: 'modalEsocial', close: closeModalById('modalEsocial', 'closeEsocialModal') },
                        { id: 'modalEsocialForm', close: closeModalById('modalEsocialForm', 'closeEsocialForm') },
                        { id: 'modalGhe', close: closeModalById('modalGhe', 'closeGheModal') },
                        { id: 'modalGheForm', close: closeModalById('modalGheForm', 'closeGheForm') },
                        { id: 'modalProtocolos', close: closeModalById('modalProtocolos', 'closeProtocolosModal') },
                        { id: 'modalProtocoloForm', close: closeModalById('modalProtocoloForm', 'closeProtocoloForm') },
                    ];

                    return closers
                        .map((entry, index) => ({ ...entry, index, el: document.getElementById(entry.id) }))
                        .filter((entry) => entry.el && !entry.el.classList.contains('hidden'))
                        .sort((a, b) => {
                            const zDiff = parseModalZIndex(a.el) - parseModalZIndex(b.el);
                            if (zDiff !== 0) return zDiff;
                            return a.index - b.index;
                        });
                }

                function closeTopOpenModal() {
                    const stack = getOpenModalStack();
                    const top = stack[stack.length - 1];
                    if (!top) return false;
                    top.close();
                    return true;
                }

                document.addEventListener('click', (e) => {
                    const stack = getOpenModalStack();
                    const top = stack[stack.length - 1];
                    if (!top) return;
                    if (e.target === top.el) {
                        top.close();
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && closeTopOpenModal()) {
                        e.preventDefault();
                    }
                });

                // =========================
                // eSOCIAL modal: abrir já populando (ajuste pedido)
                // =========================
                window.openEsocialModal = async function () {
                    const modal = document.getElementById('modalEsocial');
                    if (!modal) return;
                    modal.classList.remove('hidden');

                    // chama seu loader existente, se existir:
                    if (typeof loadEsocialFaixas === 'function') {
                        await loadEsocialFaixas();
                    }
                };

                window.closeEsocialModal = function () {
                    const modal = document.getElementById('modalEsocial');
                    if (!modal) return;
                    modal.classList.add('hidden');
                };

                // init
                render();

            })();
        </script>
    @endpush
