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
                        @if($errors->any())
                            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <div class="font-semibold mb-1">Não foi possível salvar os parâmetros:</div>
                                <ul class="list-disc pl-5">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if($errors->any())
                            @push('scripts')
                                <script>
                                    (function () {
                                        const keys = @json(array_keys($errors->toArray()));
                                        let targetTab = 'parametros';
                                        if (keys.some(k => k === 'forma_pagamento' || k === 'vencimento_servicos')) {
                                            targetTab = 'forma-pagamento';
                                        } else if (keys.some(k => k === 'incluir_esocial' || k.startsWith('esocial_'))) {
                                            targetTab = 'esocial';
                                        }

                                        const tabButton = document.querySelector(`[data-tabs="cliente"] [data-tab="${targetTab}"]`);
                                        if (tabButton) {
                                            tabButton.click();
                                        }

                                        const msg = @json($errors->first());
                                        if (typeof window.uiAlert === 'function') {
                                            window.uiAlert(msg, {
                                                icon: 'error',
                                                title: 'Verifique os campos',
                                                confirmText: 'Entendi',
                                            });
                                        } else if (msg) {
                                            alert(msg);
                                        }
                                    })();
                                </script>
                            @endpush
                        @endif

                    <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">

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
                            </div>

                            <div data-tab-panel="servicos" class="space-y-3">
                                <div class="text-sm font-semibold text-black">Serviços</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                    @php
                                        $servicoTreinamentoId = (int) config('services.treinamento_id');
                                        $servicoExameId = (int) config('services.exame_id');
                                        $servicoAsoId = (int) (config('services.aso_id') ?? 0);
                                    @endphp
                                    @foreach($servicos as $servico)
                                        @if(
                                            (int) $servico->id === $servicoTreinamentoId
                                            || (int) $servico->id === $servicoExameId
                                            || ($servicoAsoId > 0 && (int) $servico->id === $servicoAsoId)
                                        )
                                            @continue
                                        @endif
                                        <button type="button"
                                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white hover:bg-slate-50"
                                                data-action="add-servico"
                                                data-servico-id="{{ $servico->id }}"
                                                data-servico-nome="{{ e($servico->nome) }}">
                                            + {{ $servico->nome }}
                                        </button>
                                    @endforeach
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
                                                onclick="openProtocolosModal()">
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
                                        <div class="text-sm font-semibold text-slate-800">Protocolos de ASO Configurados</div>
                                        <div class="text-xs text-slate-500">Resumo dos GHEs com seus grupos por tipo.</div>
                                    </div>
                                    <div id="gheConfigsGrid" class="p-3 grid gap-3 md:grid-cols-2"></div>
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
                        </div>
                        {{-- Lista itens (compacto) --}}
                        <div class="mt-5">
                            <h3 class="text-sm font-semibold text-black mb-2">Serviços Adicionados</h3>
                            <div class="rounded-xl border border-slate-200 overflow-hidden">
                                <div class="hidden md:grid grid-cols-12 gap-1 bg-slate-50 px-2 py-1.5 text-sm font-semibold text-black">
                                    <div class="col-span-4">Item</div>
                                    <div class="col-span-2">Valor</div>
                                    <div class="col-span-2">Prazo</div>
                                    <div class="col-span-2">Qtd</div>
                                    <div class="col-span-1 text-right">Total</div>
                                    <div class="col-span-1 text-center">Ação</div>
                                </div>
                                <div id="lista-itens" class="divide-y divide-slate-200"></div>
                            </div>
                        </div>
                    </section>


                    {{-- Rodapé --}}
                    <section class="pt-4 border-t">
                        <button type="submit"
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
                                        class="w-full rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-base font-semibold py-3 shadow-md shadow-emerald-200">
                                    Salvar Unidades Permitidas
                                </button>
                            </section>
                        </div>
                    </div>
                </div>

            <div data-tab-panel="esocial" data-tab-panel-root="cliente" class="hidden">
                <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b bg-amber-600 text-white">
                        <h1 class="text-lg font-semibold">eSocial</h1>
                    </div>
                    <div class="p-6 space-y-8">
                        <section class="space-y-3">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-black">
                                <input type="checkbox" id="chkEsocial" name="incluir_esocial" value="1"
                                       class="rounded border-slate-300"
                                       @checked(old('incluir_esocial', $isEdit ? $parametro->incluir_esocial : false))>
                                Incluir eSocial (mensal)
                            </label>

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
                        </section>

                        <section class="pt-4 border-t">
                            <button type="submit"
                                    class="w-full rounded-2xl bg-amber-600 hover:bg-amber-700 text-white text-base font-semibold py-3 shadow-md shadow-amber-200">
                                Salvar Parâmetros
                            </button>
                        </section>
                    </div>
                </div>
            </div>

            <div data-tab-panel="forma-pagamento" data-tab-panel-root="cliente" class="hidden">
                <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b bg-indigo-600 text-white">
                        <h1 class="text-lg font-semibold">Forma de Pagamento</h1>
                    </div>
                    <div class="p-6 space-y-8">
                        <section class="space-y-3">
                            <h2 class="text-sm font-semibold text-black">Forma de Pagamento *</h2>

                            <select name="forma_pagamento" required class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2">
                                <option value="">Selecione...</option>
                                @foreach($formasPagamento as $fp)
                                    <option value="{{ $fp }}"
                                        @selected(old('forma_pagamento', $isEdit ? $parametro->forma_pagamento : '') === $fp)>
                                        {{ $fp }}
                                    </option>
                                @endforeach
                            </select>
                            @error('forma_pagamento')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </section>

                        <section class="space-y-3">
                            <h2 class="text-sm font-semibold text-black">Dia de Vencimento *</h2>

                            <input type="number" min="1" max="31" name="vencimento_servicos" required
                                   value="{{ old('vencimento_servicos', $isEdit ? ($parametro->vencimento_servicos ?? '') : '') }}"
                                   class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2">
                            @error('vencimento_servicos')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </section>

                        <section class="pt-4 border-t">
                            <button type="submit"
                                    class="w-full rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-base font-semibold py-3 shadow-md shadow-indigo-200">
                                Salvar Parâmetros
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
    ])
    @include('comercial.tabela-precos.itens.modal-protocolos', ['routePrefix' => 'comercial'])
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
                    ghes: @json(route('comercial.ghes.indexJson')),
                    clientesAsoGrupos: @json(route('comercial.clientes-aso-grupos.indexJson')),
                    esocialPreco: (qtd) => @json(route('comercial.propostas.esocial-preco', ['qtd' => '__QTD__']))
                        .replace('__QTD__', encodeURIComponent(qtd)),

                    esocialList: @json(route('comercial.esocial.faixas.json')),
                    esocialStore: @json(route('comercial.esocial.faixas.store')),
                    esocialUpdate: (id) => @json(route('comercial.esocial.faixas.update', ['faixa' => '__ID__'])).replace('__ID__', id),
                    esocialDestroy: (id) => @json(route('comercial.esocial.faixas.destroy', ['faixa' => '__ID__'])).replace('__ID__', id),
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
                    esocial: { enabled:false, qtd:0, valor:0, aviso:null },
                    gruposExames: [],
                    gheCatalog: [],
                    gheConfigs: [], // [{cliente_ghe_id, ghe_id, ghe_nome, tipos:{tipo:{grupo_id, grupo_titulo, total_exames}}}]
                    currentGhe: { cliente_ghe_id: null, ghe_id: null, ghe_nome: '', tipos: {} },
                };

                const INITIAL = @json($initialData);
                const LAST_BY_CLIENTE = {};
                // =========================
                // DOM
                // =========================
                const el = {
                    lista: document.getElementById('lista-itens'),
                    total: document.getElementById('valor-total-display'),
                    clienteSelect: document.querySelector('[name="cliente_id"]'),
                    itemToast: document.getElementById('itemToast'),
                    itemToastText: document.getElementById('itemToastText'),
                    itemAlert: document.getElementById('itemAlert'),
                    itemAlertText: document.getElementById('itemAlertText'),

                    chkEsocial: document.getElementById('chkEsocial'),
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
                    asoGheTitle: document.getElementById('asoGheTitle'),
                    btnAddGheConfig: document.getElementById('btnAddGheConfig'),
                    btnGheGlobal: document.getElementById('btnGheGlobal'),
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
                    state.itens = Array.isArray(INITIAL.itens) ? INITIAL.itens : [];
                    state.itens.forEach(it => recalcItemTotal(it));
                    removeEsocialItens();

                    if (INITIAL.esocial) {
                        state.esocial.enabled = !!INITIAL.esocial.enabled;
                        state.esocial.qtd = Number(INITIAL.esocial.qtd || 0);
                        state.esocial.valor = Number(INITIAL.esocial.valor || 0);
                    }

                    if (Array.isArray(INITIAL.gheConfigs)) {
                        state.gheConfigs = INITIAL.gheConfigs;
                    }
                }

                initTabs();
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

                async function loadGruposExames() {
                    try {
                        const res = await fetch(URLS.gruposExames, { headers: { 'Accept': 'application/json' } });
                        const json = await res.json();
                        state.gruposExames = json.data || [];
                        Object.entries(state.currentGhe.tipos || {}).forEach(([tipo, row]) => {
                            if (!row?.grupo_id) return;
                            const grupo = getGrupoById(row.grupo_id);
                            if (grupo) {
                                row.grupo_titulo = row.grupo_titulo || grupo.titulo || '';
                                if (!row.total_exames) row.total_exames = Number(grupo.total || 0);
                            }
                        });
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
                        const res = await fetch(URLS.ghes, { headers: { 'Accept': 'application/json' } });
                        const json = await res.json();
                        state.gheCatalog = json.data || [];
                        renderGheSelectOptions();
                    } catch (e) {
                        console.error(e);
                    }
                }

                function renderGheSelectOptions() {
                    if (!el.gheSelect) return;
                    const current = String(state.currentGhe.ghe_id || '');
                    el.gheSelect.innerHTML = '<option value="">Selecione o GHE...</option>';
                    state.gheCatalog.forEach(g => {
                        const opt = document.createElement('option');
                        opt.value = g.id;
                        opt.textContent = g.nome;
                        el.gheSelect.appendChild(opt);
                    });
                    if (current && !state.gheCatalog.some(g => String(g.id) === current) && state.currentGhe.ghe_nome) {
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
                    state.currentGhe = { cliente_ghe_id: null, ghe_id: null, ghe_nome: '', tipos: {} };
                    if (el.gheSelect) el.gheSelect.value = '';
                    if (el.asoGheTitle) el.asoGheTitle.textContent = '—';
                    if (el.btnAddGheConfig) el.btnAddGheConfig.textContent = '+ Adicionar este GHE à lista';
                    renderAsoTipoRows();
                }

                function setCurrentGheFromSelect() {
                    const id = Number(el.gheSelect?.value || 0);
                    const ghe = state.gheCatalog.find(g => Number(g.id) === id);
                    if (!ghe) {
                        resetCurrentGheConfig();
                        return;
                    }
                    state.currentGhe = { cliente_ghe_id: null, ghe_id: ghe.id, ghe_nome: ghe.nome, tipos: {} };
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
                                window.openProtocolosModal();
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

                function getGheConfigKey(cfg) {
                    if (cfg?.cliente_ghe_id) return `c:${cfg.cliente_ghe_id}`;
                    if (cfg?.ghe_id) return `g:${cfg.ghe_id}`;
                    return cfg?._key || uid();
                }

                function renderGheConfigsTable() {
                    if (!el.gheConfigsGrid) return;
                    el.gheConfigsGrid.innerHTML = '';

                    if (!state.gheConfigs.length) {
                        el.gheConfigsGrid.innerHTML = '<div class="px-3 py-3 text-sm text-slate-500">Nenhum GHE configurado.</div>';
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

                        card.querySelector('[data-action="edit"]').addEventListener('click', () => {
                            state.currentGhe = JSON.parse(JSON.stringify(cfg));
                            if (el.gheSelect) {
                                const opt = cfg.ghe_id ? String(cfg.ghe_id) : '';
                                el.gheSelect.value = opt;
                            }
                            if (el.asoGheTitle) el.asoGheTitle.textContent = cfg.ghe_nome || '—';
                            if (el.btnAddGheConfig) el.btnAddGheConfig.textContent = 'Atualizar este GHE';
                            renderAsoTipoRows();
                        });

                        card.querySelector('[data-action="del"]').addEventListener('click', () => {
                            const key = getGheConfigKey(cfg);
                            state.gheConfigs = state.gheConfigs.filter(c => getGheConfigKey(c) !== key);
                            syncAsoTipoItems();
                            renderGheConfigsTable();
                            updateTabBadges();
                        });

                        el.gheConfigsGrid.appendChild(card);
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
                        tipos,
                    };

                    const key = getGheConfigKey(cfg);
                    const idx = state.gheConfigs.findIndex(c => getGheConfigKey(c) === key);
                    if (idx >= 0) {
                        state.gheConfigs[idx] = cfg;
                    } else {
                        state.gheConfigs.push(cfg);
                    }

                    syncAsoTipoItems();
                    renderGheConfigsTable();
                    resetCurrentGheConfig();
                    updateTabBadges();
                }

                function syncAsoTipoItems() {
                    const selected = new Map();
                    state.gheConfigs.forEach(cfg => {
                        const baseKey = getGheConfigKey(cfg);
                        const tipos = cfg.tipos || {};
                        ASO_TYPES.forEach(({ key, label }) => {
                            const row = tipos[key];
                            if (!row?.grupo_id) return;
                            const asoKey = `${baseKey}:${key}`;
                            selected.set(asoKey, { cfg, row, tipo: key, label });
                        });
                    });

                    state.itens = state.itens.filter(it => {
                        const asoKey = it?.meta?.aso_key;
                        return !asoKey || selected.has(asoKey);
                    });

                    selected.forEach(({ cfg, row, tipo, label }, asoKey) => {
                        let item = state.itens.find(it => it?.meta?.aso_key === asoKey);
                        if (!item) {
                            item = {
                                id: uid(),
                                servico_id: SERVICO_ASO_ID ? Number(SERVICO_ASO_ID) : null,
                                tipo: 'ASO_TIPO',
                                nome: `ASO - ${label}`,
                                descricao: null,
                                valor_unitario: 0,
                                quantidade: 1,
                                prazo: '',
                                acrescimo: 0,
                                desconto: 0,
                                meta: {},
                                valor_total: 0,
                            };
                            state.itens.push(item);
                        }

                        item.nome = `ASO - ${label}`;
                        const descParts = [];
                        if (cfg.ghe_nome) descParts.push(`GHE: ${cfg.ghe_nome}`);
                        if (row.grupo_titulo) descParts.push(`Grupo: ${row.grupo_titulo}`);
                        item.descricao = descParts.length ? descParts.join(' | ') : null;
                        item.meta = {
                            ...(item.meta || {}),
                            aso_tipo: tipo,
                            grupo_id: row.grupo_id,
                            cliente_ghe_id: cfg.cliente_ghe_id || null,
                            ghe_id: cfg.ghe_id || null,
                            ghe_nome: cfg.ghe_nome || null,
                            aso_key: asoKey,
                        };
                        item.quantidade = 1;
                        item.valor_unitario = Number(row.total_exames || 0);
                        recalcItemTotal(item);
                    });

                    render();
                }

                function updateAsoConfigFromItem(item, value) {
                    const asoKey = item?.meta?.aso_key || '';
                    if (!asoKey) return;
                    const [baseKey, tipo] = asoKey.split(':').length >= 3
                        ? [asoKey.split(':').slice(0, 2).join(':'), asoKey.split(':').slice(2).join(':')]
                        : asoKey.split(':');
                    const cfg = state.gheConfigs.find(c => getGheConfigKey(c) === baseKey);
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

                    if (el.clienteSelect && !INITIAL?.isEdit) {
                        el.clienteSelect.addEventListener('change', () => {
                            const clienteId = el.clienteSelect.value;
                            if (!clienteId) return;
                            loadClienteAsoGrupos(clienteId);
                        });
                        if (el.clienteSelect.value) {
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
                        state.gheConfigs = Array.from(grouped.values());
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
                    if (!buttons.length || !panels.length) return;

                    const setActive = (name) => {
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

                    setActive(buttons[0].dataset.tab);
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

                // =========================
                // Render card (igual protótipo)
                // =========================
                function render() {
                    el.lista.innerHTML = '';
                    removeEsocialItens();

                    if (!state.itens.length) {
                        el.lista.innerHTML = '<div class="px-3 py-4 text-sm text-slate-500">Nenhum item adicionado.</div>';
                        recalcTotals();
                        syncHiddenInputs();
                        return;
                    }

                    let rowIndex = 0;
                    state.itens.forEach(item => {
                        const hasZeroPrice = Number(item.valor_unitario || 0) <= 0;
                        const isAsoTipo = !!item?.meta?.aso_tipo;
                        const stripeClass = rowIndex % 2 === 0 ? 'bg-white' : 'bg-slate-50/60';
                        const row = document.createElement('div');
                        row.setAttribute('data-item-id', String(item.id));
                        row.className = hasZeroPrice
                            ? 'grid grid-cols-12 gap-1 items-center px-2 py-1.5 bg-amber-50/60'
                            : `grid grid-cols-12 gap-1 items-center px-2 py-1.5 ${stripeClass}`;
                        rowIndex += 1;

                        row.innerHTML = `
                <div class="col-span-12 md:col-span-4">
                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Item</div>
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-800 text-sm leading-tight truncate">${escapeHtml(item.nome)}</div>
                        ${item.descricao ? `<div class="text-[11px] text-slate-500 truncate">${escapeHtml(item.descricao)}</div>` : ``}
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
                    <input type="text" class="w-full h-8 rounded-md border border-slate-200 text-sm px-2"
                           data-act="valor_view" value="${brl(item.valor_unitario)}">
                </div>

                <div class="col-span-6 md:col-span-2">
                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Prazo</div>
                    <input type="text" class="w-full h-8 rounded-md border border-slate-200 text-sm px-2"
                           data-act="prazo" placeholder="Ex: 15 dias">
                </div>

                <div class="col-span-6 md:col-span-2">
                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Qtd</div>
                    <div class="inline-flex items-center gap-1">
                        <button type="button" class="h-8 w-8 rounded-md border border-slate-200 hover:bg-slate-50 text-sm" data-act="qtd_minus">-</button>
                        <input type="text" class="h-8 w-10 text-center rounded-md border border-slate-200 text-sm" data-act="qtd" value="${item.quantidade}">
                        <button type="button" class="h-8 w-8 rounded-md border border-slate-200 hover:bg-slate-50 text-sm" data-act="qtd_plus">+</button>
                    </div>
                </div>

                <div class="col-span-4 md:col-span-1 text-right md:text-right">
                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Total</div>
                    <span data-el="valor_total" class="text-sm font-semibold text-emerald-700">
                        ${brl(item.valor_total)}
                    </span>
                </div>

                <div class="col-span-2 md:col-span-1 flex justify-end md:justify-center">
                    <button type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700 text-lg leading-none"
                            data-act="remove"
                            aria-label="Remover item">
                        ×
                    </button>
                </div>
            `;

                        // Actions
                        row.querySelector('[data-act="remove"]').addEventListener('click', () => removeItem(item.id));

                        // Prazo
                        const prazoInput = row.querySelector('[data-act="prazo"]');
                        if (isAsoTipo) {
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
                        if (isAsoTipo) {
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
                        if (isAsoTipo) {
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

                        valorView.addEventListener('keydown', (e) => {
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
                        const asoKey = item?.meta?.aso_key || '';
                        if (asoKey) {
                            const parts = asoKey.split(':');
                            const baseKey = parts.length >= 3 ? parts.slice(0, 2).join(':') : parts[0];
                            const tipo = parts.length >= 3 ? parts.slice(2).join(':') : parts[1];
                            const cfg = state.gheConfigs.find(c => getGheConfigKey(c) === baseKey);
                            if (cfg && cfg.tipos?.[tipo]) {
                                delete cfg.tipos[tipo];
                                if (!Object.keys(cfg.tipos || {}).length) {
                                    state.gheConfigs = state.gheConfigs.filter(c => getGheConfigKey(c) !== baseKey);
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
                    el.esocialValorView.value = brl(state.esocial.valor);
                    el.esocialQtdHidden.value = state.esocial.enabled ? state.esocial.qtd : '';
                    el.esocialValorHidden.value = state.esocial.enabled ? Number(state.esocial.valor || 0).toFixed(2) : '0.00';

                    if (state.esocial.aviso) {
                        el.esocialAviso.textContent = state.esocial.aviso;
                        el.esocialAviso.classList.remove('hidden');
                    } else {
                        el.esocialAviso.classList.add('hidden');
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

                    const item = {
                        id: uid(),
                        servico_id: SERVICO_TREINAMENTO_ID ? Number(SERVICO_TREINAMENTO_ID) : null,
                        tipo: 'PACOTE_TREINAMENTOS',
                        nome: nomePacote,
                        descricao: treinamentos.map(t => `${t.codigo} ${t.titulo}`).join(', '),
                        valor_unitario: valor,
                        quantidade: 1,
                        prazo: 'Ex: 15 dias',
                        acrescimo: 0,
                        desconto: 0,
                        meta: { treinamentos },
                        valor_total: 0,
                    };

                    recalcItemTotal(item);
                    state.itens.push(item);

                    closePacoteTreinamentosModal();
                    render();
                    showItemToast(`Pacote de treinamentos: ${nomePacote}`);
                    if (Number(item.valor_unitario || 0) <= 0) {
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
                    modalPkg.classList.remove('hidden');
                    pkgNome.value = '';
                    // limpar checks
                    pkgList.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
                    pkgCount.textContent = '0';
                    // reset valor
                    pkgValorHidden.value = '0.00';
                    pkgValorView.value = brl(0);
                    // preparar máscara por centavos (igual você já usa)
                    attachMoneyMask(pkgValorView, pkgValorHidden);
                });

                window.closePacoteTreinamentosModal = () => modalPkg.classList.add('hidden');

                pkgList?.addEventListener('change', () => {
                    const checked = pkgList.querySelectorAll('input[type="checkbox"]:checked').length;
                    pkgCount.textContent = String(checked);
                });


                // =========================
                // eSocial UI toggles
                // =========================
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

                // =========================
                // Submit: garantir meta JSON -> array (backend aceita array)
                // =========================
                el.form.addEventListener('submit', (e) => {
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
                document.addEventListener('click', (e) => {
                    if (el.modalTrein && !el.modalTrein.classList.contains('hidden') && e.target === el.modalTrein) closeTreinamentosModal();
                    if (el.modalExames && !el.modalExames.classList.contains('hidden') && e.target === el.modalExames) closeExamesModal();
                    if (el.modalMedicoes && !el.modalMedicoes.classList.contains('hidden') && e.target === el.modalMedicoes) closeMedicoesModal();
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        if (el.modalTrein && !el.modalTrein.classList.contains('hidden')) closeTreinamentosModal();
                        if (el.modalExames && !el.modalExames.classList.contains('hidden')) closeExamesModal();
                        if (el.modalMedicoes && !el.modalMedicoes.classList.contains('hidden')) closeMedicoesModal();
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
