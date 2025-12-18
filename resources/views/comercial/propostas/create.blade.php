@extends('layouts.comercial')
@section('title', 'Criar Proposta Comercial')

@section('content')
    @php
        $isEdit = isset($proposta) && $proposta;
        $initialData = [
            'isEdit' => (bool) $isEdit,
            'itens' => [],
            'esocial' => null,
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
    @endphp
    <div class="max-w-5xl mx-auto px-4 md:px-6 py-6">

        <div class="mb-4">
            <a href="{{ route('comercial.dashboard') }}"
               class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800">
                ← Voltar ao Painel
            </a>
        </div>

        <form id="propostaForm" method="POST"
              action="{{ $isEdit ? route('comercial.propostas.update', $proposta) : route('comercial.propostas.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

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
                            <select name="cliente_id" required class="mt-1 w-full border border-slate-200 rounded-xl text-sm px-3 py-2">
                                <option value="">Selecione...</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}"
                                        @selected((string) old('cliente_id', $isEdit ? $proposta->cliente_id : '') === (string) $cliente->id)>
                                        {{ $cliente->razao_social }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </section>

                    {{-- 2. Vendedor --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700">2. Vendedor</h2>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-slate-600">Nome</label>
                                <input type="text"
                                       class="mt-1 w-full border border-slate-200 rounded-xl text-sm px-3 py-2 bg-slate-50"
                                       value="{{ $user->name }}" readonly>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-600">Telefone</label>
                                <input type="text"
                                       class="mt-1 w-full border border-slate-200 rounded-xl text-sm px-3 py-2 bg-slate-50"
                                       value="{{ $user->telefone ?? '' }}" readonly>
                            </div>
                        </div>
                    </section>

                    {{-- 3. Serviços --}}
                    {{-- 3. Serviços --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700">3. Serviços</h2>

                        {{-- BLOCO: Serviços --}}
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="text-sm font-semibold text-slate-800 mb-3">Serviços</div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                @foreach($servicos as $servico)
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

                        {{-- BLOCO: Exames --}}
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-sm font-semibold text-slate-800">Exames</div>

                                <button type="button"
                                        class="px-3 py-2 rounded-xl border border-blue-200 text-sm bg-blue-50 hover:bg-blue-100"
                                        id="btnPacoteExames">
                                    + Pacote de Exames
                                </button>
                            </div>

                            <p class="text-xs text-slate-500">Selecione exames avulsos ou crie um “Pacote de Exames”.</p>

                            <div id="examesAvulsos" class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                <div class="text-sm text-slate-500">Carregando exames...</div>
                            </div>
                        </div>

                        {{-- BLOCO: Treinamentos --}}
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between mb-3">
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



                        {{-- Lista cards --}}
                        <div class="mt-5">
                            <h3 class="text-xs font-semibold text-slate-600 mb-2">Serviços Adicionados</h3>
                            <div id="lista-itens" class="space-y-3"></div>
                        </div>
                    </section>


                    {{-- 4. E-Social --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700">4. E-Social (Opcional)</h2>

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

                    {{-- 5. Forma de Pagamento --}}
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700">5. Forma de Pagamento *</h2>

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
                                Salvar &amp; Proposta
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

    {{-- MODAL PACOTE EXAMES (dinâmico) --}}
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
                            {{-- carregado via fetch --}}
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
                    esocialPreco: (qtd) => @json(route('comercial.propostas.esocial-preco')) + '?qtd=' + encodeURIComponent(qtd),

                    esocialList: @json(route('comercial.esocial.faixas.json')),
                    esocialStore: @json(route('comercial.esocial.faixas.store')),
                    esocialUpdate: (id) => @json(route('comercial.esocial.faixas.update', ['faixa' => '__ID__'])).replace('__ID__', id),
                    esocialDestroy: (id) => @json(route('comercial.esocial.faixas.destroy', ['faixa' => '__ID__'])).replace('__ID__', id),
                };

                const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const SERVICO_ESOCIAL_ID = @json(config('services.esocial_id'));
                const SERVICO_TREINAMENTO_ID = @json(config('services.treinamento_id'));
                const SERVICO_EXAME_ID = @json(config('services.exame_id'));

                // =========================
                // State
                // =========================
                const state = {
                    itens: [],         // {id, servico_id, tipo, nome, descricao, valor_unitario, quantidade, prazo, acrescimo, desconto, meta, valor_total}
                    exames: { loaded: false, list: [], manualPrice: false }, // [{id, nome, valor}]
                    esocial: { enabled:false, qtd:0, valor:0, aviso:null },
                };

                const INITIAL = @json($initialData);

                // =========================
                // DOM
                // =========================
                const el = {
                    lista: document.getElementById('lista-itens'),
                    total: document.getElementById('valor-total-display'),
                    clienteSelect: document.querySelector('select[name="cliente_id"]'),

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
                };

                // =========================
                // Hydrate (edit)
                // =========================
                if (INITIAL?.isEdit) {
                    state.itens = Array.isArray(INITIAL.itens) ? INITIAL.itens : [];
                    state.itens.forEach(it => recalcItemTotal(it));

                    if (INITIAL.esocial) {
                        state.esocial.enabled = !!INITIAL.esocial.enabled;
                        state.esocial.qtd = Number(INITIAL.esocial.qtd || 0);
                        state.esocial.valor = Number(INITIAL.esocial.valor || 0);
                    }
                }

                // =========================
                // Utils
                // =========================

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

                function recalcTotals() {
                    let total = 0;
                    state.itens.forEach(i => total += Number(i.valor_total || 0));
                    if (state.esocial.enabled) total += Number(state.esocial.valor || 0);
                    el.total.textContent = brl(total);
                }

                function syncHiddenInputs() {
                    // remove inputs anteriores
                    document.querySelectorAll('[data-hidden-itens]').forEach(n => n.remove());

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
                }

                // =========================
                // Render card (igual protótipo)
                // =========================
                function render() {
                    el.lista.innerHTML = '';

                    state.itens.forEach(item => {
                        const card = document.createElement('div');
                        card.className = 'rounded-xl border border-slate-200 bg-white px-4 py-3';

                        card.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-800 text-sm">${escapeHtml(item.nome)}</div>
                        ${item.descricao ? `<div class="text-xs text-slate-500 mt-0.5">${escapeHtml(item.descricao)}</div>` : ``}
                    </div>

	                        <div class="flex items-center gap-3">
	                        <button type="button" class="text-red-600 hover:bg-red-50 rounded-lg px-2 py-1 text-sm" data-act="remove">×</button>
	                        <span data-el="valor_total" class="inline-flex items-center rounded-full bg-emerald-600 text-white text-xs font-semibold px-3 py-1">
	                            ${brl(item.valor_total)}
	                        </span>
	                    </div>
	                </div>

                <div class="mt-3 grid grid-cols-12 gap-3 items-end">
                    <div class="col-span-12 md:col-span-4">
                        <label class="text-[11px] font-semibold text-slate-600">Valor</label>
                        <input type="text" class="w-full mt-1 rounded-lg border border-slate-200 text-sm px-3 py-2"
                               data-act="valor_view" value="${brl(item.valor_unitario)}">
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="text-[11px] font-semibold text-slate-600">Prazo</label>
                        <input type="text" class="w-full mt-1 rounded-lg border border-slate-200 text-sm px-3 py-2"
                               data-act="prazo" value="${escapeHtml(item.prazo || '')}" placeholder="Ex: 15 dias">
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="text-[11px] font-semibold text-slate-600">Qtd</label>
                        <div class="mt-1 inline-flex items-center gap-2">
                            <button type="button" class="h-9 w-9 rounded-lg border border-slate-200 hover:bg-slate-50" data-act="qtd_minus">−</button>
                            <input type="text" class="h-9 w-12 text-center rounded-lg border border-slate-200 text-sm" data-act="qtd" value="${item.quantidade}">
                            <button type="button" class="h-9 w-9 rounded-lg border border-slate-200 hover:bg-slate-50" data-act="qtd_plus">+</button>
                        </div>
                    </div>
                </div>
            `;

                        // Actions
                        card.querySelector('[data-act="remove"]').addEventListener('click', () => removeItem(item.id));

                        // Prazo
                        card.querySelector('[data-act="prazo"]').addEventListener('input', (e) => {
                            item.prazo = e.target.value || '';
                            syncHiddenInputs();
                        });

	                        // Qtd
	                        card.querySelector('[data-act="qtd_minus"]').addEventListener('click', () => updateQtd(item.id, -1));
	                        card.querySelector('[data-act="qtd_plus"]').addEventListener('click', () => updateQtd(item.id, +1));

	                        // Input qtd (manual)
	                        card.querySelector('[data-act="qtd"]').addEventListener('input', (e) => {
	                            const n = parseInt(String(e.target.value || '1').replace(/\D+/g,''), 10) || 1;
	                            item.quantidade = Math.max(1, n);
	                            recalcItemTotal(item);
	                            e.target.value = String(item.quantidade);
	                            const totalEl = card.querySelector('[data-el="valor_total"]');
	                            if (totalEl) totalEl.textContent = brl(item.valor_total);
	                            recalcTotals();
	                            syncHiddenInputs();
	                        });

                        // Valor (máscara por centavos)
                        const valorView = card.querySelector('[data-act="valor_view"]');
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
	                                const totalEl = card.querySelector('[data-el="valor_total"]');
	                                if (totalEl) totalEl.textContent = brl(item.valor_total);
	                                recalcTotals();
	                                syncHiddenInputs();
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
	                            const totalEl = card.querySelector('[data-el="valor_total"]');
	                            if (totalEl) totalEl.textContent = brl(item.valor_total);
	                            recalcTotals();
	                            syncHiddenInputs();
	                        });

                        el.lista.appendChild(card);
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
                }

                function updateQtd(itemId, delta) {
                    const it = state.itens.find(x => x.id === itemId);
                    if (!it) return;
                    it.quantidade = Math.max(1, Number(it.quantidade || 1) + delta);
                    recalcItemTotal(it);
                    render();
                }

                function removeItem(itemId) {
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
                    state.esocial.qtd = Number(qtd || 0);

                    if (!state.esocial.enabled || state.esocial.qtd <= 0) {
                        state.esocial.valor = 0;
                        state.esocial.aviso = null;
                        applyEsocialUI();
                        return;
                    }

                    try {
                        const res = await fetch(URLS.esocialPreco(state.esocial.qtd), { headers: { 'Accept':'application/json' } });
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

                el.esocialQtd?.addEventListener('input', () => updateEsocial(el.esocialQtd.value));

                // Inicializa UI do eSocial em edição
                if (INITIAL?.isEdit && el.chkEsocial) {
                    el.esocialBox.classList.toggle('hidden', !state.esocial.enabled);
                    if (el.esocialQtd) el.esocialQtd.value = state.esocial.qtd > 0 ? String(state.esocial.qtd) : '';
                    applyEsocialUI();
                }

                // =========================
                // Submit: garantir meta JSON -> array (backend aceita array)
                // =========================
                el.form.addEventListener('submit', () => {
                    // meta está como object no state, mas hidden input manda string JSON.
                    // O backend valida meta como array. Então aqui trocamos pra array serializado por campo:
                    // -> vamos manter como JSON string e você pode ajustar validação se quiser.
                    // Se preferir array real: trocar estratégia e enviar via fetch JSON.
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
