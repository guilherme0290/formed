@extends('layouts.comercial')
@section('title', 'Criar Proposta Comercial')

@section('content')
    @php
        $isEdit = isset($proposta) && $proposta;
        $temGheSnapshot = false;
        $gheTotalExames = 0.0;
        if ($isEdit && $proposta->cliente_id) {
            $gheSnapshot = app(\App\Services\AsoGheService::class)
                ->buildSnapshotForCliente($proposta->cliente_id, $proposta->empresa_id);
            $temGheSnapshot = !empty($gheSnapshot['ghes']);
            foreach ($gheSnapshot['ghes'] ?? [] as $ghe) {
                $gheTotalExames += (float) ($ghe['total_exames'] ?? 0);
            }
        }
        $initialData = [
            'isEdit' => (bool) $isEdit,
            'itens' => [],
            'esocial' => null,
            'temGhe' => $temGheSnapshot,
            'gheTotal' => $gheTotalExames,
            'asoGrupos' => [],
        ];

        if ($isEdit) {
            $initialData['itens'] = $proposta->itens
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
                'enabled' => (bool) $proposta->incluir_esocial,
                'qtd' => (int) ($proposta->esocial_qtd_funcionarios ?? 0),
                'valor' => (float) ($proposta->esocial_valor_mensal ?? 0),
            ];
        }

        if (!empty($propostaAsoGrupos)) {
            $initialData['asoGrupos'] = collect($propostaAsoGrupos)
                ->mapWithKeys(function ($row) {
                    return [(string) $row->tipo_aso => [
                        'grupo_id' => (int) ($row->grupo_exames_id ?? 0),
                        'grupo_titulo' => $row->grupo?->titulo,
                        'total_exames' => (float) ($row->total_exames ?? 0),
                    ]];
                })
                ->toArray();
        }
    @endphp
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <div class="mb-4">
            <a href="{{ route('comercial.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                ← Voltar ao Painel
            </a>
        </div>

        <form id="propostaForm" method="POST"
              action="{{ $isEdit ? route('comercial.propostas.update', $proposta) : route('comercial.propostas.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="fixed top-20 left-4 right-4 sm:left-auto sm:right-6 z-40">
                <div id="itemToast" class="hidden pointer-events-auto rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg flex items-start gap-3 transition-all duration-200 opacity-0 translate-y-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-white text-xs font-semibold">
                        ✓
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

            <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b bg-blue-600 text-white">
                    <h1 class="text-lg font-semibold">
                        {{ $isEdit ? 'Editar Proposta Comercial' : 'Criar Nova Proposta Comercial' }}
                    </h1>
                </div>

                <div class="p-6 space-y-8">

                    {{-- 1. Cliente Final --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700">1. Cliente Final</h2>

                        <div>
                            <label class="text-xs font-medium text-slate-600">Cliente Final</label>
                            <div class="mt-1 flex items-center gap-2">
                                <select name="cliente_id" required class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2">
                                    <option value="">Selecione...</option>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id }}"
                                            @selected((string) old('cliente_id', $isEdit ? $proposta->cliente_id : '') === (string) $cliente->id)>
                                            {{ $cliente->razao_social }}
                                        </option>
                                    @endforeach
                                </select>
                                <a href="{{ route('clientes.create') }}"
                                   class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 whitespace-nowrap">
                                    + Novo cliente
                                </a>
                            </div>
                        </div>
                    </section>

                    {{-- 2. Serviços --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700">2. Serviços</h2>

                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 mb-4" data-tabs="proposta">
                                <button type="button"
                                        class="px-4 py-2 rounded-full text-sm font-semibold bg-blue-600 text-white"
                                        data-tab="servicos">
                                    Serviços
                                </button>
                                <button type="button"
                                        class="relative px-4 py-2 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-100"
                                        data-tab="ghe">
                                    GHE
                                    <span id="badgeTabGhe" class="hidden absolute -top-1 -right-1 h-2.5 w-2.5 rounded-full bg-amber-400"></span>
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
                                <div class="text-sm font-semibold text-slate-800">Serviços</div>
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

                            <div data-tab-panel="ghe" class="hidden space-y-3">
                                <div class="text-sm font-semibold text-slate-800">GHE do Cliente</div>
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-slate-500">
                                        Defina GHEs e funções para este cliente.
                                    </p>
                                    <button type="button"
                                            class="px-3 py-2 rounded-xl border border-amber-200 text-sm bg-amber-50 hover:bg-amber-100 text-amber-800 font-semibold"
                                            id="btnGheCliente">
                                        Gerenciar GHE
                                    </button>
                                </div>
                            </div>

                            <div data-tab-panel="aso-tipos" class="hidden space-y-3">
                                <div class="text-sm font-semibold text-slate-800">ASO por tipo</div>
                                <p class="text-xs text-slate-500">
                                    Selecione grupos de exames por tipo de ASO. Cada tipo gera um item separado na proposta.
                                </p>

                                @php
                                    $asoTipos = [
                                        'admissional' => 'Admissional',
                                        'periodico' => 'Periódico',
                                        'demissional' => 'Demissional',
                                        'mudanca_funcao' => 'Mudança de Função',
                                        'retorno_trabalho' => 'Retorno ao Trabalho',
                                    ];
                                @endphp

                                <div class="rounded-xl border border-slate-200 overflow-hidden">
                                    <div class="hidden md:grid grid-cols-12 gap-2 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                                        <div class="col-span-2">Tipo</div>
                                        <div class="col-span-4">Grupo de exames</div>
                                        <div class="col-span-4"></div>
                                        <div class="col-span-2 text-right">Total</div>
                                    </div>
                                    <div class="divide-y divide-slate-200">
                                        @foreach($asoTipos as $asoKey => $asoLabel)
                                            <div class="grid grid-cols-12 gap-2 items-center px-3 py-2 bg-white">
                                                <div class="col-span-12 md:col-span-2">
                                                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Tipo</div>
                                                    <div class="text-sm font-semibold text-slate-800">{{ $asoLabel }}</div>
                                                    <button type="button"
                                                            data-aso-show="{{ $asoKey }}"
                                                            class="mt-1 text-xs font-semibold text-blue-600 hover:underline">
                                                        Ver exames
                                                    </button>
                                                    <div class="mt-1 hidden text-xs text-slate-600" data-aso-exames="{{ $asoKey }}"></div>
                                                </div>
                                                <div class="col-span-12 md:col-span-4">
                                                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Grupo de exames</div>
                                                    <select data-aso-grupo="{{ $asoKey }}"
                                                            class="w-full rounded-md border-slate-200 text-sm px-2 py-2">
                                                        <option value="">Selecione...</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-12 md:col-span-4"></div>
                                                <div class="col-span-12 md:col-span-2 text-right">
                                                    <div class="text-[11px] font-semibold text-slate-500 md:hidden">Total</div>
                                                    <div class="text-sm font-semibold text-emerald-700">
                                                        <span data-aso-total="{{ $asoKey }}">R$ 0,00</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
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
                            <h3 class="text-xs font-semibold text-slate-600 mb-2">Serviços Adicionados</h3>
                            <div class="rounded-xl border border-slate-200 overflow-hidden">
                                <div class="hidden md:grid grid-cols-12 gap-1 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-600">
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


                    {{-- 3. E-Social --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700">3. E-Social (Opcional)</h2>

                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" id="chkEsocial" name="incluir_esocial" value="1"
                                   class="rounded border-slate-300"
                                   @checked(old('incluir_esocial', $isEdit ? $proposta->incluir_esocial : false))>
                            Incluir E-Social (mensal)
                        </label>

                        <div id="esocialBox" class="hidden rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-semibold text-slate-700">Qtd colaboradores</label>
                                    <input id="esocialQtd" type="number" min="1"
                                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                           placeholder="Ex.: 12"
                                           value="{{ old('esocial_qtd_funcionarios', $isEdit ? $proposta->esocial_qtd_funcionarios : '') }}">
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-700">Valor mensal</label>
                                    <input id="esocialValorView" type="text" readonly
                                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 bg-white"
                                           value="R$ 0,00">
                                    <input type="hidden" name="esocial_qtd_funcionarios" id="esocialQtdHidden">
                                    <input type="hidden" name="esocial_valor_mensal" id="esocialValorHidden"
                                           value="{{ old('esocial_valor_mensal', $isEdit ? $proposta->esocial_valor_mensal : '0.00') }}">
                                </div>
                            </div>

                            <p id="esocialAviso" class="mt-3 text-sm text-amber-700 hidden"></p>
                        </div>
                    </section>

                    {{-- 4. Forma de Pagamento --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700">4. Forma de Pagamento *</h2>

                        <select name="forma_pagamento" required class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2">
                            <option value="">Selecione...</option>
                            @foreach($formasPagamento as $fp)
                                <option value="{{ $fp }}"
                                    @selected(old('forma_pagamento', $isEdit ? $proposta->forma_pagamento : '') === $fp)>
                                    {{ $fp }}
                                </option>
                            @endforeach
                        </select>
                    </section>

                    {{-- 5. Prazo da proposta --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700">5. Prazo da proposta (dias) *</h2>

                        <input type="number" name="prazo_dias" min="1" max="365" required
                               value="{{ old('prazo_dias', $isEdit ? ($proposta->prazo_dias ?? '') : '') }}"
                               class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2">
                        <p class="text-xs text-slate-500">Informe o prazo de vencimento da proposta.</p>
                    </section>

                    {{-- 6. Data de vencimento --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700">6. Data de vencimento *</h2>

                        <input type="number" min="1" max="31" name="vencimento_servicos" required
                               value="{{ old('vencimento_servicos', $isEdit ? ($proposta->vencimento_servicos ?? '') : '') }}"
                               class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2">

                    </section>

                    {{-- Rodapé --}}
                    <section class="pt-4 border-t flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="bg-blue-600 text-white rounded-2xl px-6 py-4">
                            <p class="text-xs uppercase tracking-wide">Valor Total</p>
                            <p class="text-2xl font-semibold" id="valor-total-display">R$ 0,00</p>
                            <p class="text-[11px] opacity-80">*E-Social mensal incluso se selecionado</p>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                                Salvar Proposta
                            </button>
                        </div>
                    </section>

                </div>
            </div>
        </form>
    </div>

    {{-- MODAL TREINAMENTOS --}}
    <div id="modalTreinamentos" class="fixed inset-0 z-50 hidden bg-black/40">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Selecionar Treinamento (NR)</h3>
                    <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100" onclick="closeTreinamentosModal()">✕</button>
                </div>
                <div class="p-5">
                    <div id="nrChips" class="flex flex-wrap gap-2"></div>
                    <p class="text-xs text-slate-500 mt-3">Clique em uma NR para adicionar na proposta.</p>
                </div>
            </div>
        </div>
    </div>

    @include('comercial.tabela-precos.itens.modal-ghes', [
        'routePrefix' => 'comercial',
        'clientes' => $clientes ?? collect(),
        'funcoes' => $funcoes ?? collect(),
    ])
    {{--
    MODAL PACOTE EXAMES (dinamico)
    <div id="modalExames" class="fixed inset-0 z-50 hidden bg-black/40">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b bg-blue-700 text-white flex items-center justify-between">
                    <h3 class="font-semibold">Criar Pacote de Exames</h3>
                    <button type="button" class="h-9 w-9 rounded-xl hover:bg-white/10" onclick="closeExamesModal()">✕</button>
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
    @include('comercial.propostas.modal-treinamentos')
    {{-- MODAL eSOCIAL (seu include existente) --}}
    @include('comercial.tabela-precos.itens.modal-esocial')

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
                    gruposExames: @json(route('comercial.protocolos-exames.indexJson')),
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

                // =========================
                // State
                // =========================
                const state = {
                    itens: [],         // {id, servico_id, tipo, nome, descricao, valor_unitario, quantidade, prazo, acrescimo, desconto, meta, valor_total}
                    exames: { loaded: false, list: [], manualPrice: false }, // [{id, nome, valor}]
                    esocial: { enabled:false, qtd:0, valor:0, aviso:null },
                    asoGrupos: {},
                    gruposExames: [],
                };

                const INITIAL = @json($initialData);
                const LAST_BY_CLIENTE = @json($ultimaPropostaPorCliente ?? []);
                const gheInfo = {
                    has: !!INITIAL?.temGhe,
                    total: Number(INITIAL?.gheTotal || 0),
                };

                // =========================
                // DOM
                // =========================
                const el = {
                    lista: document.getElementById('lista-itens'),
                    total: document.getElementById('valor-total-display'),
                    clienteSelect: document.querySelector('select[name="cliente_id"]'),
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
                    modalExames: document.getElementById('modalExames'),
                    examesList: document.getElementById('pkgExamesList'),
                    examesAvulsos: document.getElementById('examesAvulsos'),
                    pkgExamesCount: document.getElementById('pkgExamesCount'),
                    pkgExamesNome: document.getElementById('pkgExamesNome'),
                    pkgExamesValorView: document.getElementById('pkgExamesValorView'),
                    pkgExamesValorHidden: document.getElementById('pkgExamesValorHidden'),
                    form: document.getElementById('propostaForm'),
                    tabsWrap: document.querySelector('[data-tabs="proposta"]'),
                };

                const ASO_TYPES = [
                    { key: 'admissional', label: 'Admissional' },
                    { key: 'periodico', label: 'Periódico' },
                    { key: 'demissional', label: 'Demissional' },
                    { key: 'mudanca_funcao', label: 'Mudança de Função' },
                    { key: 'retorno_trabalho', label: 'Retorno ao Trabalho' },
                ];

                const asoDom = {};
                ASO_TYPES.forEach(({ key }) => {
                    asoDom[key] = {
                        select: document.querySelector(`[data-aso-grupo="${key}"]`),
                        resumo: document.querySelector(`[data-aso-resumo="${key}"]`),
                        total: document.querySelector(`[data-aso-total="${key}"]`),
                        exames: document.querySelector(`[data-aso-exames="${key}"]`),
                        showBtn: document.querySelector(`[data-aso-show="${key}"]`),
                    };
                });

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

                    if (INITIAL.asoGrupos) {
                        state.asoGrupos = INITIAL.asoGrupos;
                    }

                    ensureAsoItemForGhe();
                }

                initTabs();
                updateTabBadges();
                bindAsoGrupoEvents();
                loadGruposExames();
                bindClienteAutoLoad();

                // =========================
                // Utils
                // =========================
                function hasAsoItem(it) {
                    const nomeBase = String(it?.nome || it?.descricao || '').toUpperCase();
                    return nomeBase && nomeBase.includes('ASO');
                }

                function ensureAsoItemForGhe() {
                    if (hasAsoGrupoSelection()) return;
                    if (!gheInfo.has) return;
                    if (state.itens.some(it => hasAsoItem(it))) return;
                    const item = {
                        id: uid(),
                        servico_id: SERVICO_ASO_ID ? Number(SERVICO_ASO_ID) : null,
                        tipo: 'SERVICO',
                        nome: 'ASO',
                        descricao: 'ASO por GHE',
                        valor_unitario: 0,
                        quantidade: 1,
                        prazo: '',
                        acrescimo: 0,
                        desconto: 0,
                        meta: null,
                        valor_total: 0,
                    };
                    recalcItemTotal(item);
                    state.itens.push(item);
                    applyGheToAsoItems();
                    render();
                }

                function bindClienteAutoLoad() {
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

                function hasAsoGrupoSelection() {
                    return Object.values(state.asoGrupos || {}).some(g => Number(g?.grupo_id || 0) > 0);
                }

                function getGrupoById(id) {
                    return state.gruposExames.find(g => Number(g.id) === Number(id));
                }

                async function loadGruposExames() {
                    try {
                        const res = await fetch(URLS.gruposExames, { headers: { 'Accept': 'application/json' } });
                        const json = await res.json();
                        state.gruposExames = json.data || [];
                        renderAsoGrupoOptions();
                        applyInitialAsoGrupos();
                    } catch (e) {
                        console.error(e);
                    }
                }

                function renderAsoGrupoOptions() {
                    ASO_TYPES.forEach(({ key }) => {
                        const sel = asoDom[key]?.select;
                        if (!sel) return;
                        sel.innerHTML = '<option value="">Selecione...</option>';
                        state.gruposExames.forEach(g => {
                            const opt = document.createElement('option');
                            opt.value = g.id;
                            opt.textContent = g.titulo;
                            sel.appendChild(opt);
                        });
                    });
                }

                function applyInitialAsoGrupos() {
                    if (!state.asoGrupos) return;
                    ASO_TYPES.forEach(({ key }) => {
                        const sel = asoDom[key]?.select;
                        const grupoId = Number(state.asoGrupos?.[key]?.grupo_id || 0);
                        if (sel && grupoId) {
                            sel.value = String(grupoId);
                        }
                        updateAsoGrupoResumo(key);
                    });
                }

                function updateAsoGrupoResumo(tipo) {
                    const dom = asoDom[tipo];
                    if (!dom?.select) return;
                    const grupoId = Number(dom.select.value || 0);
                    if (!grupoId) {
                        if (dom.resumo) dom.resumo.textContent = 'Nenhum grupo selecionado.';
                        if (dom.total) dom.total.textContent = brl(0);
                        if (dom.exames && !dom.exames.classList.contains('hidden')) {
                            dom.exames.innerHTML = '<div class="text-slate-500">Selecione um grupo para ver os exames.</div>';
                        }
                        delete state.asoGrupos[tipo];
                        syncAsoTipoItems();
                        return;
                    }

                    const grupo = getGrupoById(grupoId);
                    const total = Number(state.asoGrupos?.[tipo]?.total_exames ?? grupo?.total ?? 0);
                    const titulo = grupo?.titulo || state.asoGrupos?.[tipo]?.grupo_titulo || 'Grupo selecionado';

                    state.asoGrupos[tipo] = {
                        grupo_id: grupoId,
                        grupo_titulo: titulo,
                        total_exames: total,
                    };

                    if (dom.resumo) {
                        dom.resumo.textContent = `${titulo} • ${grupo?.exames?.length ?? 0} exame(s)`;
                    }
                    if (dom.total) dom.total.textContent = brl(total);
                    if (dom.exames && !dom.exames.classList.contains('hidden')) {
                        renderAsoExames(tipo);
                    }
                    syncAsoTipoItems();
                }

                function renderAsoExames(tipo) {
                    const dom = asoDom[tipo];
                    if (!dom?.exames) return;
                    const grupoId = Number(dom.select?.value || 0);
                    if (!grupoId) {
                        dom.exames.innerHTML = '<div class="text-slate-500">Selecione um grupo para ver os exames.</div>';
                        return;
                    }
                    const grupo = getGrupoById(grupoId);
                    if (!grupo?.exames?.length) {
                        dom.exames.innerHTML = '<div class="text-slate-500">Nenhum exame neste grupo.</div>';
                        return;
                    }
                    dom.exames.innerHTML = grupo.exames.map(ex => {
                        return `<div class="flex items-center justify-between gap-2">
                            <span class="truncate">${escapeHtml(ex.titulo || 'Exame')}</span>
                            <span class="text-slate-700 font-semibold">${brl(ex.preco || 0)}</span>
                        </div>`;
                    }).join('');
                }

                function syncAsoTipoItems() {
                    const tiposSelecionados = new Set(Object.keys(state.asoGrupos || {}));

                    state.itens = state.itens.filter(it => {
                        const tipo = it?.meta?.aso_tipo;
                        return !tipo || tiposSelecionados.has(tipo);
                    });

                    ASO_TYPES.forEach(({ key, label }) => {
                        const grupo = state.asoGrupos?.[key];
                        if (!grupo?.grupo_id) return;

                        let item = state.itens.find(it => it?.meta?.aso_tipo === key);
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
                        item.descricao = `Grupo: ${grupo.grupo_titulo}`;
                        item.meta = {
                            ...(item.meta || {}),
                            aso_tipo: key,
                            grupo_id: grupo.grupo_id,
                        };
                        item.quantidade = 1;
                        item.valor_unitario = Number(grupo.total_exames || 0);
                        recalcItemTotal(item);
                    });

                    render();
                }

                function bindAsoGrupoEvents() {
                    ASO_TYPES.forEach(({ key }) => {
                        const dom = asoDom[key];
                        dom?.select?.addEventListener('change', () => updateAsoGrupoResumo(key));
                        dom?.showBtn?.addEventListener('click', () => {
                            if (!dom.exames) return;
                            dom.exames.classList.toggle('hidden');
                            if (!dom.exames.classList.contains('hidden')) {
                                renderAsoExames(key);
                            }
                        });
                    });
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
                    const badgeGhe = document.getElementById('badgeTabGhe');
                    const badgeTrein = document.getElementById('badgeTabTreinamentos');
                    const temGhe = !!gheInfo.has;
                    const temTrein = state.itens.some(it => (String(it.tipo || '')).toUpperCase() === 'TREINAMENTO_NR');

                    if (badgeGhe) {
                        badgeGhe.classList.toggle('hidden', !temGhe);
                    }
                    if (badgeTrein) {
                        badgeTrein.classList.toggle('hidden', !temTrein);
                    }
                }
                function initTabs() {
                    if (!el.tabsWrap) return;
                    const buttons = Array.from(el.tabsWrap.querySelectorAll('[data-tab]'));
                    const panels = Array.from(document.querySelectorAll('[data-tab-panel]'));
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

                function applyGheToAsoItems() {
                    const gheTotal = Number(gheInfo.total || 0);
                    if (!gheTotal) return;
                    const asoItems = state.itens.filter(it => hasAsoItem(it) && !it?.meta?.aso_tipo);
                    if (!asoItems.length) return;
                    const totalAtual = asoItems.reduce((sum, it) => sum + Number(it.valor_total || 0), 0);
                    if (totalAtual > 0) return;
                    const target = asoItems[0];
                    if (!target.servico_id && SERVICO_ASO_ID) {
                        target.servico_id = Number(SERVICO_ASO_ID);
                    }
                    const qtd = Number(target.quantidade || 1);
                    target.valor_unitario = qtd > 0 ? (gheTotal / qtd) : gheTotal;
                    recalcItemTotal(target);
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
                    applyGheToAsoItems();
                    removeEsocialItens();
                    state.itens.forEach(i => total += Number(i.valor_total || 0));
                    if (state.esocial.enabled) total += Number(state.esocial.valor || 0);
                    el.total.textContent = brl(total);
                    updateTabBadges();
                }

                window.addEventListener('ghe:updated', (event) => {
                    const detail = event?.detail || {};
                    gheInfo.has = !!detail.hasGhe;
                    gheInfo.total = Number(detail.total || 0);
                    ensureAsoItemForGhe();
                    recalcTotals();
                });

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

                    Object.entries(state.asoGrupos || {}).forEach(([tipo, grupo]) => {
                        if (!grupo?.grupo_id) return;
                        const base = `aso_grupos[${tipo}]`;
                        const pairs = [
                            ['grupo_id', grupo.grupo_id],
                            ['total_exames', Number(grupo.total_exames || 0).toFixed(2)],
                        ];

                        pairs.forEach(([k,v]) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `${base}[${k}]`;
                            input.value = v;
                            input.setAttribute('data-hidden-aso-grupos','1');
                            el.form.appendChild(input);
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

                    const asoOrder = new Map(ASO_TYPES.map((t, i) => [t.key, i]));
                    let insertedAsoHeader = false;
                    const sortedItems = state.itens
                        .map((it, idx) => ({ it, idx }))
                        .sort((a, b) => {
                            const aTipo = a.it?.meta?.aso_tipo;
                            const bTipo = b.it?.meta?.aso_tipo;
                            const aIsAso = !!aTipo;
                            const bIsAso = !!bTipo;
                            if (aIsAso && !bIsAso) return -1;
                            if (!aIsAso && bIsAso) return 1;
                            if (aIsAso && bIsAso) {
                                return (asoOrder.get(aTipo) ?? 999) - (asoOrder.get(bTipo) ?? 999);
                            }
                            return a.idx - b.idx;
                        })
                        .map((row) => row.it);

                    sortedItems.forEach(item => {
                        const hasZeroPrice = Number(item.valor_unitario || 0) <= 0;
                        const isAsoTipo = !!item?.meta?.aso_tipo;
                        if (isAsoTipo && !insertedAsoHeader) {
                            const header = document.createElement('div');
                            header.className = 'px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-600 uppercase tracking-wide';
                            header.textContent = 'ASO';
                            el.lista.appendChild(header);
                            insertedAsoHeader = true;
                        }
                        const row = document.createElement('div');
                        row.className = hasZeroPrice
                            ? 'grid grid-cols-12 gap-1 items-center px-2 py-1.5 bg-amber-50/60'
                            : 'grid grid-cols-12 gap-1 items-center px-2 py-1.5 bg-white';

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
                        <button type="button" class="h-8 w-8 rounded-md border border-slate-200 hover:bg-slate-50 text-sm" data-act="qtd_minus">−</button>
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
                        if (isAsoTipo) {
                            valorView.setAttribute('readonly', 'readonly');
                            valorView.classList.add('bg-slate-100', 'cursor-not-allowed');
                        }

                        valorView.addEventListener('keydown', (e) => {
                            if (isAsoTipo) {
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
	                                return;
	                            }

                            if (nav.includes(e.key)) return;
                            if (!/^\d$/.test(e.key)) e.preventDefault();
                        });

                        valorView.addEventListener('input', () => {
                            if (isAsoTipo) return;
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
                    applyGheToAsoItems();
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
                    applyGheToAsoItems();
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
                        const tipo = item.meta.aso_tipo;
                        if (asoDom[tipo]?.select) {
                            asoDom[tipo].select.value = '';
                            updateAsoGrupoResumo(tipo);
                        } else {
                            delete state.asoGrupos[tipo];
                            state.itens = state.itens.filter(x => x.id !== itemId);
                            render();
                        }
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
                    if (!nomePacote) return alert('Informe o nome do pacote.');

                    const ids = Array.from(el.examesList.querySelectorAll('input[type="checkbox"]:checked'))
                        .map(i => Number(i.value));

                    const escolhidos = (state.exames.list || []).filter(e => ids.includes(e.id));
                    if (!escolhidos.length) return alert('Selecione pelo menos um exame.');

                    const valor = Number(el.pkgExamesValorHidden?.value || 0);
                    if (valor <= 0) return alert('Informe o valor do pacote.');

                    closeExamesModal();
                    addPacoteExames(nomePacote, valor, escolhidos);
                }

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
                        addServico(btn.dataset.servicoId, btn.dataset.servicoNome);
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

                document.getElementById('btnGheCliente')?.addEventListener('click', () => {
                    const sel = el.clienteSelect?.value || '';
                    const nome = el.clienteSelect?.selectedOptions?.[0]?.textContent?.trim() || '';
                    if (typeof setGheCliente === 'function') {
                        setGheCliente(sel, nome);
                    }
                    if (typeof openGheModal === 'function') {
                        openGheModal();
                    }
                });

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
                    if (!nomePacote) return alert('Informe o nome do pacote.');

                    const checks = Array.from(pkgList.querySelectorAll('input[type="checkbox"]:checked'));
                    if (!checks.length) return alert('Selecione pelo menos um treinamento.');

                    const treinamentos = checks.map(c => ({
                        id: Number(c.value),
                        codigo: c.dataset.codigo,
                        titulo: c.dataset.titulo,
                    }));

                    const valor = Number(pkgValorHidden.value || 0);

                    const item = {
                        id: uid(),
                        servico_id: null,
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

                // Mensagem customizada para prazo da proposta
                const prazoInput = document.querySelector('input[name="prazo_dias"]');
                if (prazoInput) {
                    prazoInput.addEventListener('invalid', () => {
                        if (!prazoInput.value) {
                            prazoInput.setCustomValidity('Insira um prazo para proposta');
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
                        const names = zeroItems.map(it => it.nome).slice(0, 3).join(', ');
                        const extra = zeroItems.length > 3 ? ` e mais ${zeroItems.length - 3}` : '';
                        showItemAlert(`Existem itens com preço zerado: ${names}${extra}.`, 'error');
                    }
                });

                // =========================
                // Modais click fora / ESC
                // =========================
                document.addEventListener('click', (e) => {
                    if (el.modalTrein && !el.modalTrein.classList.contains('hidden') && e.target === el.modalTrein) closeTreinamentosModal();
                    if (el.modalExames && !el.modalExames.classList.contains('hidden') && e.target === el.modalExames) closeExamesModal();
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        if (el.modalTrein && !el.modalTrein.classList.contains('hidden')) closeTreinamentosModal();
                        if (el.modalExames && !el.modalExames.classList.contains('hidden')) closeExamesModal();
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
@endsection
