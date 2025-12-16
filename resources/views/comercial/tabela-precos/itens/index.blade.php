@extends('layouts.comercial')
@section('title', 'Itens da Tabela de Preços')

@section('content')
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">

        <header class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Itens da Tabela de Preços</h1>
                <p class="text-slate-500 text-sm mt-1">
                    Itens utilizados nas propostas comerciais.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <button type="button"
                        onclick="openNovoItemModal()"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl
               bg-blue-600 hover:bg-blue-700 active:bg-blue-800
               text-white px-5 py-2.5 text-sm font-semibold shadow-sm
               ring-1 ring-blue-600/20 hover:ring-blue-700/30
               transition">
                    <span class="text-base leading-none">＋</span>
                    <span>ASO, Documentos e Laudos</span>
                </button>

                <button type="button"
                        onclick="openEsocialModal()"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl
               bg-white hover:bg-indigo-50 active:bg-indigo-100
               text-indigo-700 px-5 py-2.5 text-sm font-semibold shadow-sm
               ring-1 ring-indigo-200 hover:ring-indigo-300
               transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Gerenciar faixas eSocial</span>
                </button>
            </div>
        </header>

        @if (session('ok'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif
        @if ($errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    openNovoItemModal();
                });
            </script>
        @endif

        <section class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-5 py-3 font-semibold">Serviço</th>
                        <th class="px-5 py-3 font-semibold">Item</th>
                        <th class="px-5 py-3 font-semibold">Preço</th>
                        <th class="px-5 py-3 font-semibold">Ativo</th>
                        <th class="px-5 py-3 font-semibold w-32">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($itens as $item)
                        <tr>
                            <td class="px-5 py-3">
                            <span class="text-slate-800 font-medium">
                                {{ $item->servico?->nome ?? 'Item livre' }}
                            </span>
                            </td>

                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $item->descricao }}</div>
                                <div class="text-xs text-slate-500">{{ $item->codigo }}</div>
                            </td>

                            <td class="px-5 py-3 font-semibold text-slate-800">
                                R$ {{ number_format($item->preco, 2, ',', '.') }}
                            </td>

                            <td class="px-5 py-3">
                                @if($item->ativo)
                                    <span
                                        class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">
                                    Sim
                                </span>
                                @else
                                    <span
                                        class="text-xs px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100">
                                    Não
                                </span>
                                @endif
                            </td>

                            <td class="px-5 py-3 flex gap-2">
                                <button type="button"
                                        class="text-blue-600 hover:underline text-sm"
                                        onclick="openEditarItemModal(this)"
                                        data-id="{{ $item->id }}"
                                        data-servico-id="{{ $item->servico_id ?? '' }}"
                                        data-codigo="{{ e($item->codigo ?? '') }}"
                                        data-descricao="{{ e($item->descricao ?? '') }}"
                                        data-preco="{{ $item->preco }}"
                                        data-ativo="{{ $item->ativo ? 1 : 0 }}"
                                        data-update-url="{{ route('comercial.tabela-precos.itens.update', $item) }}">
                                    Editar
                                </button>

                                <form method="POST"
                                      action="{{ route('comercial.tabela-precos.itens.destroy', $item) }}"
                                      onsubmit="return confirm('Deseja remover este item?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline text-sm">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-slate-500">
                                Nenhum item cadastrado.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    {{-- MODAL NOVO ITEM --}}
    {{-- MODAL NOVO ITEM --}}
    <div id="modalNovoItem"
         class="fixed inset-0 z-50 hidden bg-black/40">

        {{-- Centralizador + respiro --}}
        <div class="min-h-full w-full flex items-center justify-center p-4 md:p-6">
            {{-- Card modal --}}
            <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden
                    max-h-[85vh] flex flex-col">

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 id="modalItemTitle" class="text-lg font-semibold text-slate-800">Novo Item</h2>

                    <button type="button"
                            onclick="closeNovoItemModal()"
                            class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500 flex items-center justify-center">
                        ✕
                    </button>
                </div>

                {{-- Form --}}
                <form id="formItem"
                      method="POST"
                      action="{{ route('comercial.tabela-precos.itens.store') }}"
                      class="flex-1 flex flex-col">
                    @csrf

                    <div id="formMethodSpoof"></div>


                    {{-- Body com scroll interno --}}
                    <div class="px-6 py-5 space-y-4 overflow-y-auto">


                        <div class="flex items-center gap-3">
                            <div class="relative inline-block w-11 h-5">
                                <input
                                    id="item_ativo"
                                    name="ativo"
                                    type="checkbox"
                                    value="1"
                                    @checked(old('ativo', true))
                                    class="peer appearance-none w-11 h-5 rounded-full cursor-pointer transition-colors duration-300
                                  bg-red-600 checked:bg-green-600"
                                />

                                <label
                                    for="item_ativo"
                                    class="absolute top-0 left-0 w-5 h-5 bg-white rounded-full border shadow-sm cursor-pointer
                                   transition-transform duration-300
                                   border-red-600 peer-checked:border-green-600
                                   peer-checked:translate-x-6"
                                ></label>
                            </div>

                            <span id="item_ativo_label" class="text-sm font-medium text-slate-700">
                            {{ old('ativo', true) ? 'Ativo' : 'Inativo' }}
                        </span>
                        </div>


                        <div>
                            <label class="text-xs font-semibold text-slate-600">Serviço (opcional)</label>
                            <select id="item_servico_id" name="servico_id"
                                    class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">
                                <option value="">— Item livre (sem serviço) —</option>
                                @foreach($servicos as $s)
                                    <option value="{{ $s->id }}" @selected(old('servico_id') == $s->id)>
                                        {{ $s->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Chips Treinamentos NRs --}}
                        <div id="nrChipsContainer" class="hidden">
                            <label class="text-xs font-semibold text-slate-600 mb-2 block">
                                Treinamento (NR)
                            </label>

                            <div id="nrChips"
                                 class="flex flex-wrap gap-2">
                                {{-- Chips via JS --}}
                            </div>

                            <p class="text-xs text-slate-500 mt-2">
                                Selecione apenas um treinamento. O código será preenchido automaticamente.
                            </p>
                        </div>

                        {{-- Código --}}

                        <div>
                            <label class="text-xs font-semibold text-slate-600">Codigo(opcional)</label>
                            <input id="item_codigo" type="text" name="codigo" value="{{ old('codigo') }}"
                                   class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Descricao</label>
                            <input id="item_descricao" type="text" name="descricao" value="{{ old('descricao') }}"
                                   required
                                   class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Preço</label>
                            {{-- Visual (R$) --}}
                            <input id="item_preco_view"
                                   type="text"
                                   inputmode="decimal"
                                   autocomplete="off"
                                   placeholder="R$ 0,00"
                                   class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">

                            {{-- Valor real que vai pro backend (ex: 1234.56) --}}
                            <input id="item_preco"
                                   type="hidden"
                                   name="preco"
                                   value="{{ old('preco') }}"
                                   required>

                        </div>

                        <div id="btnEsocialContainer" class="hidden">
                            {{--                            <button type="button"--}}
                            {{--                                    onclick="openEsocialModal()"--}}
                            {{--                                    class="w-full mt-2 rounded-xl border border-indigo-200 bg-indigo-50--}}
                            {{--                   text-indigo-700 px-4 py-2 text-sm font-semibold hover:bg-indigo-100">--}}
                            {{--                                Gerenciar faixas de eSocial--}}
                            {{--                            </button>--}}
                        </div>

                        {{-- Footer fixo --}}
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                            <button type="button"
                                    onclick="closeNovoItemModal()"
                                    class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                Cancelar
                            </button>

                            <button id="modalItemSubmit" type="submit"
                                    class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold">
                                Salvar
                            </button>

                        </div>

                    </div>
                </form>


            </div>
        </div>
    </div>

    {{--    Modal eSocial--}}
    {{--    <div id="modalEsocial"--}}
    {{--         class="fixed inset-0 z-50 hidden bg-black/40">--}}

    {{--        <div class="min-h-full flex items-center justify-center p-4">--}}
    {{--            <div class="bg-white w-full max-w-3xl rounded-2xl shadow-xl">--}}

    {{--                <div class="px-6 py-4 border-b flex justify-between">--}}
    {{--                    <h2 class="text-lg font-semibold">Faixas de eSocial</h2>--}}
    {{--                    <button onclick="closeEsocialModal()">✕</button>--}}
    {{--                </div>--}}

    {{--                <div class="p-6">--}}
    {{--                    --}}{{-- tabela de faixas --}}
    {{--                    <div id="esocialFaixas"></div>--}}

    {{--                    <button class="mt-4 rounded-xl bg-blue-600 text-white px-4 py-2">--}}
    {{--                        Nova Faixa--}}
    {{--                    </button>--}}
    {{--                </div>--}}
    {{--            </div>--}}
    {{--        </div>--}}
    {{--    </div>--}}
    @include('comercial.tabela-precos.itens.modal-esocial')


    @push('scripts')
        <script>
            (function () {

                // ============================
                // CONFIG / ELEMENTOS
                // ============================
                const storeUrl = @json(route('comercial.tabela-precos.itens.store'));

                const SERVICO_TREINAMENTO_ID = {{ config('services.treinamento_id') ?? 'null' }};
                const SERVICO_ESOCIAL_ID = {{ config('services.esocial_id') ?? 'null' }};


                const el = {
                    modalItem: document.getElementById('modalNovoItem'),
                    modalEsocial: document.getElementById('modalEsocial'),

                    form: document.getElementById('formItem'),
                    spoof: document.getElementById('formMethodSpoof'),

                    title: document.getElementById('modalItemTitle'),
                    submit: document.getElementById('modalItemSubmit'),

                    ativo: document.getElementById('item_ativo'),
                    ativoLabel: document.getElementById('item_ativo_label'),

                    servico: document.getElementById('item_servico_id'),
                    codigo: document.getElementById('item_codigo'),
                    descricao: document.getElementById('item_descricao'),

                    precoView: document.getElementById('item_preco_view'),
                    precoHidden: document.getElementById('item_preco'),

                    nrWrap: document.getElementById('nrChips'),
                    nrContainer: document.getElementById('nrChipsContainer'),

                    btnEsocial: document.getElementById('btnEsocialContainer'),
                    esocialFaixas: document.getElementById('esocialFaixas'),
                };

                // Se algo essencial não existir, não quebra a página
                if (!el.form || !el.modalItem) return;

                // ============================
                // TOGGLE ATIVO
                // ============================
                function syncAtivoLabel() {
                    if (!el.ativo || !el.ativoLabel) return;
                    el.ativoLabel.textContent = el.ativo.checked ? 'Ativo' : 'Inativo';
                }

                // ============================
                // PREÇO (MÁSCARA POR CENTAVOS)
                // ============================
                let precoMaskReady = false;

                function onlyDigits(str) {
                    return String(str || '').replace(/\D+/g, '');
                }

                function centsDigitsToNumber(digits) {
                    const n = parseInt(digits || '0', 10);
                    return n / 100;
                }

                function formatBRLNumber(n) {
                    return Number(n || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
                }

                function setPrecoFromNumber(n) {
                    if (!el.precoView || !el.precoHidden) return;

                    const num = Number(n || 0);
                    el.precoHidden.value = num.toFixed(2);
                    el.precoView.value = formatBRLNumber(num);
                    el.precoView.dataset.digits = onlyDigits(Math.round(num * 100));
                }

                function attachPrecoMaskListeners() {
                    if (!el.precoView || !el.precoHidden) return;
                    if (precoMaskReady) return;
                    precoMaskReady = true;

                    // init
                    setPrecoFromNumber(Number(el.precoHidden.value || 0));

                    // input (suporta colar e IME)
                    el.precoView.addEventListener('input', function () {
                        const digits = onlyDigits(el.precoView.value);
                        el.precoView.dataset.digits = digits;

                        const num = centsDigitsToNumber(digits);
                        el.precoHidden.value = num.toFixed(2);
                        el.precoView.value = formatBRLNumber(num);

                        requestAnimationFrame(() => {
                            el.precoView.setSelectionRange(el.precoView.value.length, el.precoView.value.length);
                        });
                    });

                    // keydown (bloqueio + backspace controlado)
                    el.precoView.addEventListener('keydown', function (e) {
                        const navKeys = ['Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Delete'];
                        if (e.ctrlKey || e.metaKey) return;

                        if (e.key === 'Backspace') {
                            e.preventDefault();
                            const d = el.precoView.dataset.digits || '';
                            const nd = d.slice(0, -1);
                            el.precoView.dataset.digits = nd;

                            const num = centsDigitsToNumber(nd);
                            el.precoHidden.value = num.toFixed(2);
                            el.precoView.value = formatBRLNumber(num);

                            requestAnimationFrame(() => {
                                el.precoView.setSelectionRange(el.precoView.value.length, el.precoView.value.length);
                            });
                            return;
                        }

                        // permite navegação
                        if (navKeys.includes(e.key)) return;

                        // só dígitos
                        if (!/^\d$/.test(e.key)) e.preventDefault();
                    });

                    // paste controlado
                    el.precoView.addEventListener('paste', function (e) {
                        e.preventDefault();
                        const text = (e.clipboardData || window.clipboardData).getData('text');
                        const digits = onlyDigits(text);

                        el.precoView.dataset.digits = digits;
                        const num = centsDigitsToNumber(digits);

                        el.precoHidden.value = num.toFixed(2);
                        el.precoView.value = formatBRLNumber(num);

                        requestAnimationFrame(() => {
                            el.precoView.setSelectionRange(el.precoView.value.length, el.precoView.value.length);
                        });
                    });

                    // foco: cursor no fim
                    el.precoView.addEventListener('focus', function () {
                        requestAnimationFrame(() => {
                            el.precoView.setSelectionRange(el.precoView.value.length, el.precoView.value.length);
                        });
                    });
                }

                // ============================
                // SERVIÇO => CHIPS NRs / eSOCIAL
                // ============================
                async function loadNrChips() {
                    if (!el.nrWrap) return;

                    const res = await fetch(@json(route('comercial.tabela-precos.treinamentos-nrs.json')));
                    const json = await res.json();

                    console.log(res)

                    el.nrWrap.innerHTML = '';

                    (json.data || []).forEach(nr => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        // btn.textContent = `${nr.codigo} – ${nr.titulo}`;
                        btn.textContent = `${nr.codigo}`;
                        btn.dataset.codigo = nr.codigo;
                        btn.dataset.descricao = nr.titulo;

                        btn.className = 'px-3 py-1 rounded-full border text-sm font-medium border-slate-300 text-slate-700 hover:bg-blue-50';

                        btn.addEventListener('click', () => selectNrChip(btn));
                        el.nrWrap.appendChild(btn);
                    });
                }

                function selectNrChip(selected) {
                    document.querySelectorAll('#nrChips button').forEach(btn => {
                        btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                        btn.classList.add('border-slate-300', 'text-slate-700');
                    });

                    selected.classList.add('bg-blue-600', 'text-white', 'border-blue-600');

                    if (el.codigo) el.codigo.value = selected.dataset.codigo || '';

                    if (el.descricao) el.descricao.value = selected.dataset.descricao || '';

                }

                function handleServicoChange() {
                    if (!el.servico) return;

                    const val = el.servico.value;

                    if (el.nrContainer) el.nrContainer.classList.add('hidden');
                    if (el.btnEsocial) el.btnEsocial.classList.add('hidden');

                    if (!val) return;

                    if (SERVICO_TREINAMENTO_ID !== null && Number(val) === Number(SERVICO_TREINAMENTO_ID)) {
                        if (el.nrContainer) el.nrContainer.classList.remove('hidden');
                        loadNrChips();
                    }

                    if (SERVICO_ESOCIAL_ID !== null && Number(val) === Number(SERVICO_ESOCIAL_ID)) {
                        if (el.btnEsocial) el.btnEsocial.classList.remove('hidden');
                    }
                }

                // ============================
                // MODAIS
                // ============================
                function openNovoItemModal() {
                    el.title.textContent = 'Novo Item';
                    el.submit.textContent = 'Salvar';
                    el.form.action = storeUrl;
                    el.spoof.innerHTML = '';

                    if (el.servico) el.servico.value = '';
                    if (el.codigo) el.codigo.value = '';
                    if (el.descricao) el.descricao.value = '';
                    if (el.ativo) el.ativo.checked = true;

                    // preço
                    if (el.precoHidden) el.precoHidden.value = '0.00';
                    attachPrecoMaskListeners();
                    setPrecoFromNumber(0);

                    syncAtivoLabel();
                    handleServicoChange();

                    el.modalItem.classList.remove('hidden');
                }

                function openEditarItemModal(btn) {
                    el.title.textContent = 'Editar Item';
                    el.submit.textContent = 'Salvar alterações';
                    el.form.action = btn.dataset.updateUrl;
                    el.spoof.innerHTML = '@method("PUT")';

                    if (el.servico) el.servico.value = btn.dataset.servicoId || '';
                    if (el.codigo) el.codigo.value = btn.dataset.codigo || '';
                    if (el.descricao) el.descricao.value = btn.dataset.descricao || '';
                    if (el.ativo) el.ativo.checked = (btn.dataset.ativo === '1');

                    // preço
                    const preco = Number(btn.dataset.preco || 0);
                    if (el.precoHidden) el.precoHidden.value = preco.toFixed(2);
                    attachPrecoMaskListeners();
                    setPrecoFromNumber(preco);

                    syncAtivoLabel();
                    handleServicoChange();

                    el.modalItem.classList.remove('hidden');
                }

                function closeNovoItemModal() {
                    el.modalItem.classList.add('hidden');
                }

                function openEsocialModal() {
                    if (!el.modalEsocial) return;
                    el.modalEsocial.classList.remove('hidden');
                    // TODO: loadEsocialFaixas()
                }

                function closeEsocialModal() {
                    if (!el.modalEsocial) return;
                    el.modalEsocial.classList.add('hidden');
                }

                // ============================
                // EVENTS GERAIS (clicar fora / ESC)
                // ============================
                document.addEventListener('click', function (e) {
                    if (el.modalItem && !el.modalItem.classList.contains('hidden') && e.target === el.modalItem) {
                        closeNovoItemModal();
                    }
                    if (el.modalEsocial && !el.modalEsocial.classList.contains('hidden') && e.target === el.modalEsocial) {
                        closeEsocialModal();
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        if (el.modalItem && !el.modalItem.classList.contains('hidden')) closeNovoItemModal();
                        if (el.modalEsocial && !el.modalEsocial.classList.contains('hidden')) closeEsocialModal();
                    }
                });

                // ============================
                // INIT
                // ============================
                document.addEventListener('DOMContentLoaded', function () {
                    attachPrecoMaskListeners();

                    if (el.ativo) {
                        el.ativo.addEventListener('change', syncAtivoLabel);
                        syncAtivoLabel();
                    }

                    if (el.servico) {
                        el.servico.addEventListener('change', handleServicoChange);
                        handleServicoChange();
                    }
                });

                // ============================
                // EXPOE NO WINDOW (onclick="")
                // ============================
                window.openNovoItemModal = openNovoItemModal;
                window.openEditarItemModal = openEditarItemModal;
                window.closeNovoItemModal = closeNovoItemModal;

                window.openEsocialModal = openEsocialModal;
                window.closeEsocialModal = closeEsocialModal;

            })();

            // ============================
            // eSOCIAL (CRUD via AJAX)
            // ============================

            const ESOCIAL = {
                urls: {
                    list:   @json(route('comercial.esocial.faixas.json')),
                    store:  @json(route('comercial.esocial.faixas.store')),
                    update: (id) =>
                    @json(route('comercial.esocial.faixas.update', ['faixa' => '__ID__'])).replace('__ID__', id),
                    destroy: (id) =>
                    @json(route('comercial.esocial.faixas.destroy', ['faixa' => '__ID__'])).replace('__ID__', id),
                },
                csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                state: {
                    faixas: []
                },
                dom: {
                    alert: document.getElementById('esocialAlert'),
                    list: document.getElementById('esocialFaixas'),
                    btnNova: document.getElementById('btnNovaFaixa'),

                    modalForm: document.getElementById('modalEsocialForm'),
                    form: document.getElementById('formEsocialFaixa'),
                    title: document.getElementById('esocialFormTitle'),

                    id: document.getElementById('esocial_faixa_id'),
                    inicio: document.getElementById('esocial_inicio'),
                    fim: document.getElementById('esocial_fim'),
                    descricao: document.getElementById('esocial_descricao'),
                    precoView: document.getElementById('esocial_preco_view'),
                    precoHidden: document.getElementById('esocial_preco'),
                    ativo: document.getElementById('esocial_ativo'),
                    ativoLabel: document.getElementById('esocial_ativo_label'),
                }
            };

            function esocialAlert(type, msg) {
                if (!ESOCIAL.dom.alert) return;
                ESOCIAL.dom.alert.classList.remove('hidden');
                ESOCIAL.dom.alert.className = 'mb-4 rounded-xl border px-4 py-3 text-sm';

                if (type === 'ok') {
                    ESOCIAL.dom.alert.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-800');
                } else {
                    ESOCIAL.dom.alert.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
                }
                ESOCIAL.dom.alert.textContent = msg;
            }

            function esocialAlertHide() {
                if (!ESOCIAL.dom.alert) return;
                ESOCIAL.dom.alert.classList.add('hidden');
            }

            function renderEsocialFaixas() {
                const wrap = ESOCIAL.dom.list;
                if (!wrap) return;

                wrap.innerHTML = '';

                if (!ESOCIAL.state.faixas.length) {
                    wrap.innerHTML = `<div class="text-sm text-slate-500 py-3">Nenhuma faixa cadastrada ainda.</div>`;
                    return;
                }

                ESOCIAL.state.faixas.forEach(f => {
                    const faixaTxt = `${String(f.inicio).padStart(2, '0')} até ${f.fim == 999999 ? 'acima' : String(f.fim).padStart(2, '0')}`;
                    const precoTxt = Number(f.preco || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});

                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 px-3 py-2';

                    row.innerHTML = `
            <div class="col-span-3">
                <div class="text-sm font-semibold text-slate-800">${faixaTxt}</div>
                <div class="text-[11px] text-slate-500">#${f.id}</div>
            </div>

            <div class="col-span-5 text-sm text-slate-700">
                ${f.descricao ? escapeHtml(f.descricao) : '<span class="text-slate-400">—</span>'}
            </div>

            <div class="col-span-2 text-right text-sm font-semibold text-slate-800">
                ${precoTxt}
            </div>

            <div class="col-span-1 text-center">
                ${f.ativo ? '<span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">Sim</span>'
                        : '<span class="text-xs px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100">Não</span>'}
            </div>

            <div class="col-span-1 flex justify-end gap-2">
                <button type="button"
                        class="text-blue-600 hover:underline text-sm"
                        data-action="edit" data-id="${f.id}">
                    Editar
                </button>
                <button type="button"
                        class="text-red-600 hover:underline text-sm"
                        data-action="del" data-id="${f.id}">
                    Excluir
                </button>
            </div>
        `;

                    row.querySelector('[data-action="edit"]').addEventListener('click', () => openEsocialForm(f));
                    row.querySelector('[data-action="del"]').addEventListener('click', () => deleteEsocialFaixa(f.id));

                    wrap.appendChild(row);
                });
            }

            async function loadEsocialFaixas() {
                try {
                    esocialAlertHide();

                    const res = await fetch(ESOCIAL.urls.list, {headers: {'Accept': 'application/json'}});
                    const json = await res.json();

                    ESOCIAL.state.faixas = json.data || [];
                    renderEsocialFaixas();
                } catch (e) {
                    esocialAlert('err', 'Falha ao carregar faixas do eSocial.');
                    console.error(e);
                }
            }

            // -------- Form modal (create/edit) --------

            let esocialPrecoMaskReady = false;

            function esocialOnlyDigits(str) {
                return String(str || '').replace(/\D+/g, '');
            }

            function esocialCentsToNumber(d) {
                return (parseInt(d || '0', 10) / 100);
            }

            function esocialFormatBRL(n) {
                return Number(n || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
            }

            function esocialSetPrecoFromNumber(n) {
                const v = ESOCIAL.dom.precoView, h = ESOCIAL.dom.precoHidden;
                if (!v || !h) return;

                const num = Number(n || 0);
                h.value = num.toFixed(2);
                v.value = esocialFormatBRL(num);
                v.dataset.digits = esocialOnlyDigits(Math.round(num * 100));
            }

            function esocialAttachMask() {
                const v = ESOCIAL.dom.precoView, h = ESOCIAL.dom.precoHidden;
                if (!v || !h) return;
                if (esocialPrecoMaskReady) return;
                esocialPrecoMaskReady = true;

                esocialSetPrecoFromNumber(Number(h.value || 0));

                v.addEventListener('input', () => {
                    const digits = esocialOnlyDigits(v.value);
                    v.dataset.digits = digits;

                    const num = esocialCentsToNumber(digits);
                    h.value = num.toFixed(2);
                    v.value = esocialFormatBRL(num);

                    requestAnimationFrame(() => v.setSelectionRange(v.value.length, v.value.length));
                });

                v.addEventListener('keydown', (e) => {
                    const nav = ['Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Delete'];
                    if (e.ctrlKey || e.metaKey) return;

                    if (e.key === 'Backspace') {
                        e.preventDefault();
                        const d = v.dataset.digits || '';
                        const nd = d.slice(0, -1);
                        v.dataset.digits = nd;

                        const num = esocialCentsToNumber(nd);
                        h.value = num.toFixed(2);
                        v.value = esocialFormatBRL(num);

                        requestAnimationFrame(() => v.setSelectionRange(v.value.length, v.value.length));
                        return;
                    }

                    if (nav.includes(e.key)) return;
                    if (!/^\d$/.test(e.key)) e.preventDefault();
                });

                v.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const text = (e.clipboardData || window.clipboardData).getData('text');
                    const digits = esocialOnlyDigits(text);
                    v.dataset.digits = digits;

                    const num = esocialCentsToNumber(digits);
                    h.value = num.toFixed(2);
                    v.value = esocialFormatBRL(num);

                    requestAnimationFrame(() => v.setSelectionRange(v.value.length, v.value.length));
                });

                v.addEventListener('focus', () => {
                    requestAnimationFrame(() => v.setSelectionRange(v.value.length, v.value.length));
                });
            }

            function syncEsocialAtivoLabel() {
                if (!ESOCIAL.dom.ativo || !ESOCIAL.dom.ativoLabel) return;
                ESOCIAL.dom.ativoLabel.textContent = ESOCIAL.dom.ativo.checked ? 'Ativo' : 'Inativo';
            }

            function openEsocialForm(faixa = null) {
                if (!ESOCIAL.dom.modalForm) return;

                ESOCIAL.dom.title.textContent = faixa ? 'Editar Faixa' : 'Nova Faixa';

                ESOCIAL.dom.id.value = faixa?.id ?? '';
                ESOCIAL.dom.inicio.value = faixa?.inicio ?? '';
                ESOCIAL.dom.fim.value = faixa?.fim ?? '';
                ESOCIAL.dom.descricao.value = faixa?.descricao ?? '';
                ESOCIAL.dom.ativo.checked = faixa ? !!faixa.ativo : true;
                syncEsocialAtivoLabel();

                ESOCIAL.dom.precoHidden.value = faixa ? Number(faixa.preco || 0).toFixed(2) : '0.00';
                esocialAttachMask();
                esocialSetPrecoFromNumber(Number(ESOCIAL.dom.precoHidden.value || 0));

                ESOCIAL.dom.modalForm.classList.remove('hidden');
            }

            function closeEsocialForm() {
                if (!ESOCIAL.dom.modalForm) return;
                ESOCIAL.dom.modalForm.classList.add('hidden');
            }

            async function saveEsocialFaixa(e) {
                e.preventDefault();
                esocialAlertHide();

                const id = ESOCIAL.dom.id.value;
                const inicio = Number(ESOCIAL.dom.inicio.value || 0);
                const fim = Number(ESOCIAL.dom.fim.value || 0);
                const descricao = ESOCIAL.dom.descricao.value || null;
                const preco = Number(ESOCIAL.dom.precoHidden.value || 0);
                const ativo = ESOCIAL.dom.ativo.checked ? 1 : 0;

                if (!inicio || !fim) return esocialAlert('err', 'Informe início e fim.');
                if (inicio > fim) return esocialAlert('err', 'Início não pode ser maior que o fim.');
                if (preco < 0) return esocialAlert('err', 'Preço inválido.');

                const payload = {inicio, fim, descricao, preco, ativo};

                try {
                    const isEdit = !!id;
                    const url = isEdit ? ESOCIAL.urls.update(id) : ESOCIAL.urls.store;

                    const res = await fetch(url, {
                        method: isEdit ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': ESOCIAL.csrf,
                        },
                        body: JSON.stringify(payload)
                    });

                    const json = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        // Laravel validation
                        if (json?.errors) {
                            const first = Object.values(json.errors)[0]?.[0] || 'Erro ao salvar.';
                            return esocialAlert('err', first);
                        }
                        return esocialAlert('err', json?.message || 'Erro ao salvar faixa.');
                    }

                    closeEsocialForm();
                    await loadEsocialFaixas();
                    esocialAlert('ok', isEdit ? 'Faixa atualizada.' : 'Faixa criada.');
                } catch (err) {
                    console.error(err);
                    esocialAlert('err', 'Falha ao salvar faixa.');
                }
            }

            async function deleteEsocialFaixa(id) {
                if (!confirm('Deseja remover esta faixa?')) return;

                try {
                    const res = await fetch(ESOCIAL.urls.destroy(id), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': ESOCIAL.csrf,
                        }
                    });

                    const json = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        return esocialAlert('err', json?.message || 'Erro ao excluir faixa.');
                    }

                    await loadEsocialFaixas();
                    esocialAlert('ok', 'Faixa removida.');
                } catch (err) {
                    console.error(err);
                    esocialAlert('err', 'Falha ao excluir faixa.');
                }
            }

            function escapeHtml(str) {
                return String(str)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", "&#039;");
            }

            // 🔥 liga o botão "Nova Faixa" e o submit
            if (ESOCIAL.dom.btnNova) ESOCIAL.dom.btnNova.addEventListener('click', () => openEsocialForm(null));
            if (ESOCIAL.dom.form) ESOCIAL.dom.form.addEventListener('submit', saveEsocialFaixa);
            if (ESOCIAL.dom.ativo) ESOCIAL.dom.ativo.addEventListener('change', syncEsocialAtivoLabel);

        </script>
    @endpush

@endsection
