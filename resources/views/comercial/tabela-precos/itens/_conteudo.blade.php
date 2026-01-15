@php
    $routePrefix = $routePrefix ?? 'comercial';
    $dashboardRoute = $dashboardRoute ?? route($routePrefix === 'master' ? 'master.dashboard' : 'comercial.dashboard');
@endphp

<div class="w-full mx-auto px-4 md:px-6 xl:px-8 py-6 space-y-6">

    <div class="mb-4">
        <a href="{{ $dashboardRoute }}"
           class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
            ← Voltar ao Painel
        </a>
    </div>
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
                <span>eSocial</span>
            </button>
            <button type="button"
                    onclick="openExamesModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl
               bg-white hover:bg-blue-50 active:bg-blue-100
               text-blue-700 px-4 py-2 text-sm font-semibold shadow-sm
               ring-1 ring-blue-200 hover:ring-blue-300 transition">
                <span>Exames</span>
            </button>
            <button type="button"
                    onclick="openTreinamentosCrudModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl
               bg-white hover:bg-emerald-50 active:bg-emerald-100
               text-emerald-700 px-4 py-2 text-sm font-semibold shadow-sm
               ring-1 ring-emerald-200 hover:ring-emerald-300 transition">
                <span>Treinamentos</span>
            </button>
            <button type="button"
                    onclick="openProtocolosModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl
               bg-white hover:bg-slate-50 active:bg-slate-100
               text-slate-700 px-4 py-2 text-sm font-semibold shadow-sm
               ring-1 ring-slate-200 hover:ring-slate-300 transition">
                <span>Grupo de Exames</span>
            </button>
{{--            <button type="button"--}}
{{--                    onclick="openGheModal()"--}}
{{--                    class="inline-flex items-center justify-center gap-2 rounded-2xl--}}
{{--               bg-white hover:bg-amber-50 active:bg-amber-100--}}
{{--               text-amber-700 px-4 py-2 text-sm font-semibold shadow-sm--}}
{{--               ring-1 ring-amber-200 hover:ring-amber-300 transition">--}}
{{--                <span>GHE do Cliente</span>--}}
{{--            </button>--}}
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
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 font-semibold w-32">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @php
                    $treinamentoId = (int) config('services.treinamento_id');
                    $itensTreinamentos = $itens->filter(fn ($i) => (int) ($i->servico_id ?? 0) === $treinamentoId);
                    $itensOutros = $itens->reject(fn ($i) => (int) ($i->servico_id ?? 0) === $treinamentoId);
                @endphp

                @if($itens->isEmpty())
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-slate-500">
                            Nenhum item cadastrado.
                        </td>
                    </tr>
                @else
                    @if($itensOutros->isNotEmpty())
                        <tr class="bg-slate-50">
                            <td colspan="5" class="px-5 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">
                                Serviços (ASO, documentos, laudos, etc.)
                            </td>
                        </tr>

                        @foreach($itensOutros as $item)
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
                                        <span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">
                                            Ativo
                                        </span>
                                    @else
                                        <span class="text-xs px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100">
                                            Inativo
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
                                            data-update-url="{{ route($routePrefix.'.tabela-precos.itens.update', $item) }}">
                                        Editar
                                    </button>

                                    <form method="POST"
                                          action="{{ route($routePrefix.'.tabela-precos.itens.destroy', $item) }}"
                                          onsubmit="return confirm('Deseja remover este item?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline text-sm">
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    @if($itensTreinamentos->isNotEmpty())
                        <tr class="bg-emerald-50 border-t-2 border-emerald-200">
                            <td colspan="5" class="px-5 py-3 text-xs font-semibold text-emerald-800 uppercase tracking-wide">
                                Treinamentos (NRs)
                            </td>
                        </tr>

                        @foreach($itensTreinamentos as $item)
                            <tr>
                                <td class="px-5 py-3">
                                    <span class="text-slate-800 font-medium">
                                        {{ $item->servico?->nome ?? 'Treinamentos' }}
                                    </span>
                                </td>

                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-800">{{$item->codigo  }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->descricao }}</div>
                                </td>

                                <td class="px-5 py-3 font-semibold text-slate-800">
                                    R$ {{ number_format($item->preco, 2, ',', '.') }}
                                </td>

                                <td class="px-5 py-3">
                                    @if($item->ativo)
                                        <span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">
                                            Ativo
                                        </span>
                                    @else
                                        <span class="text-xs px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100">
                                            Inativo
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
                                            data-update-url="{{ route($routePrefix.'.tabela-precos.itens.update', $item) }}">
                                        Editar
                                    </button>

                                    <form method="POST"
                                          action="{{ route($routePrefix.'.tabela-precos.itens.destroy', $item) }}"
                                          onsubmit="return confirm('Deseja remover este item?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline text-sm">
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endif
                </tbody>
            </table>
        </div>
    </section>

</div>

@include('comercial.tabela-precos.itens.modal-protocolos', ['routePrefix' => $routePrefix])
@include('comercial.tabela-precos.itens.modal-ghes', [
    'routePrefix' => $routePrefix,
    'clientes' => $clientes ?? collect(),
    'funcoes' => $funcoes ?? collect(),
])

{{-- MODAL NOVO ITEM --}}
@php($storeRoute = $routePrefix.'.tabela-precos.itens.store')
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
                  action="{{ route($storeRoute) }}"
                  class="flex-1 flex flex-col">
                @csrf

                <div id="formMethodSpoof"></div>


                {{-- Body com scroll interno --}}
                <div class="px-6 py-5 space-y-4 overflow-y-auto">
                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <div class="font-semibold mb-1">Verifique os campos</div>
                            <ul class="list-disc ml-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


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
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <label class="text-xs font-semibold text-slate-600 block">
                                Treinamento (NR)
                            </label>

                            <button type="button"
                                    onclick="openTreinamentosCrudModal()"
                                    class="text-xs font-semibold text-emerald-700 hover:underline">
                                Cadastrar/editar treinamentos
                            </button>
                        </div>

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


@include('comercial.tabela-precos.itens.modal-esocial', ['routePrefix' => $routePrefix])
@include('comercial.tabela-precos.itens.modal-exames', ['routePrefix' => $routePrefix])
@include('comercial.tabela-precos.itens.modal-treinamentos', ['routePrefix' => $routePrefix])



@push('scripts')
    <script>
        (function () {

            // ============================
            // CONFIG / ELEMENTOS (ITENS)
            // ============================
            const storeUrl = @json(route($routePrefix.'.tabela-precos.itens.store'));
            const treinamentosUrl = @json(route($routePrefix.'.treinamentos-nrs.json'));
            const SERVICO_TREINAMENTO_ID = {{ config('services.treinamento_id') ?? 'null' }};

            const el = {
                modalItem: document.getElementById('modalNovoItem'),

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
            };

            // Se algo essencial não existir, não quebra a página
            if (!el.form || !el.modalItem) return;

            const state = {
                treinamentos: [],
                treinamentosLoaded: false,
            };

            // ============================
            // TOGGLE ATIVO
            // ============================
            function syncAtivoLabel() {
                if (!el.ativo || !el.ativoLabel) return;
                el.ativoLabel.textContent = el.ativo.checked ? 'Ativo' : 'Inativo';
            }

            // ============================
            // TREINAMENTOS (NRs) - CHIPS
            // ============================
            async function loadTreinamentos(force = false) {
                if (!force && state.treinamentosLoaded) return state.treinamentos;

                try {
                    const res = await fetch(treinamentosUrl, { headers: { 'Accept':'application/json' } });
                    const json = await res.json();
                    const list = (json?.data || [])
                        .filter(x => x && (x.ativo === true || x.ativo === 1))
                        .map(x => ({
                            id: Number(x.id),
                            codigo: String(x.codigo || ''),
                            titulo: String(x.titulo || ''),
                        }))
                        .filter(x => x.id && x.codigo);

                    state.treinamentos = list;
                    state.treinamentosLoaded = true;
                    return list;
                } catch (e) {
                    state.treinamentos = [];
                    state.treinamentosLoaded = true;
                    return [];
                }
            }

            function renderTreinamentosChips() {
                if (!el.nrWrap) return;
                el.nrWrap.innerHTML = '';

                if (!state.treinamentos.length) {
                    el.nrWrap.innerHTML = '<div class="text-sm text-slate-500">Nenhum treinamento ativo cadastrado.</div>';
                    return;
                }

                state.treinamentos.forEach(nr => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'px-3 py-1.5 rounded-full border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-blue-50';
                    btn.textContent = nr.codigo;
                    btn.addEventListener('click', () => {
                        if (el.codigo) el.codigo.value = nr.codigo;
                        if (el.descricao && !el.descricao.value) el.descricao.value = nr.titulo;
                    });
                    el.nrWrap.appendChild(btn);
                });
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

                el.precoView.addEventListener('focus', () => {
                    const digits = onlyDigits(el.precoView.dataset.digits || '0');
                    el.precoView.dataset.digits = digits;
                    el.precoView.value = formatBRLNumber(centsDigitsToNumber(digits));
                    setTimeout(() => el.precoView.select(), 50);
                });

                el.precoView.addEventListener('input', (e) => {
                    const digits = onlyDigits(e.target.value || e.target.dataset.digits || '0').slice(0, 12);
                    e.target.dataset.digits = digits || '0';
                    const number = centsDigitsToNumber(digits);
                    setPrecoFromNumber(number);
                });
            }

            // ============================
            // EXIBIR / FECHAR MODAL
            // ============================
            function resetNovoItemForm() {
                if (!el.form) return;
                el.form.reset();
                if (el.servico) el.servico.value = '';
                if (el.codigo) el.codigo.value = '';
                if (el.descricao) el.descricao.value = '';
                if (el.precoHidden) el.precoHidden.value = '0.00';
                setPrecoFromNumber(0);
                if (el.ativo) el.ativo.checked = true;
                syncAtivoLabel();
                if (el.nrContainer) el.nrContainer.classList.add('hidden');
                if (el.nrWrap) el.nrWrap.innerHTML = '';
            }

            window.openNovoItemModal = function () {
                if (!el.modalItem) return;

                el.form.action = storeUrl;
                el.spoof.innerHTML = '';
                el.title.textContent = 'Novo Item';
                el.submit.textContent = 'Salvar';

                resetNovoItemForm();

                el.modalItem.classList.remove('hidden');
            }

            window.closeNovoItemModal = function () {
                resetNovoItemForm();
                el.modalItem?.classList.add('hidden');
            }

            // ============================
            // EDITAR ITEM
            // ============================
            window.openEditarItemModal = function (btn) {
                if (!el.modalItem || !btn) return;

                const data = btn.dataset;

                el.form.action = data.updateUrl;
                el.spoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                el.title.textContent = 'Editar Item';
                el.submit.textContent = 'Salvar alterações';

                if (el.servico) el.servico.value = data.servicoId || '';
                if (el.codigo) el.codigo.value = data.codigo || '';
                if (el.descricao) el.descricao.value = data.descricao || '';

                if (el.precoHidden) {
                    el.precoHidden.value = data.preco || 0;
                    setPrecoFromNumber(Number(data.preco || 0));
                }

                if (el.ativo) {
                    el.ativo.checked = data.ativo === '1';
                    syncAtivoLabel();
                }

                el.modalItem.classList.remove('hidden');
            }

            // ============================
            // NR: MOSTRAR CHIPS QUANDO SERVICO = TREINAMENTO
            // ============================
            function toggleNrChips() {
                if (!el.nrContainer || !el.servico) return;
                const isTreinamento = Number(el.servico.value || 0) === Number(SERVICO_TREINAMENTO_ID);
                el.nrContainer.classList.toggle('hidden', !isTreinamento);
                if (isTreinamento) {
                    if (el.nrWrap) {
                        el.nrWrap.innerHTML = '<div class="text-sm text-slate-500">Carregando...</div>';
                    }
                    loadTreinamentos().then(() => renderTreinamentosChips());
                }
            }

            // ============================
            // INIT
            // ============================
            syncAtivoLabel();
            attachPrecoMaskListeners();
            toggleNrChips();

            el.ativo?.addEventListener('change', syncAtivoLabel);
            el.servico?.addEventListener('change', toggleNrChips);
            window.addEventListener('treinamentos-nrs:changed', () => {
                state.treinamentosLoaded = false;
                if (!el.nrContainer?.classList.contains('hidden')) {
                    if (el.nrWrap) {
                        el.nrWrap.innerHTML = '<div class="text-sm text-slate-500">Carregando...</div>';
                    }
                    loadTreinamentos(true).then(() => renderTreinamentosChips());
                }
            });
        })();
    </script>
@endpush
