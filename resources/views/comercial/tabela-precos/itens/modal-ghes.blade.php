@php($routePrefix = $routePrefix ?? 'comercial')
@php($gheScope = $gheScope ?? 'global')
@php($clienteSelector = $clienteSelector ?? '[name="cliente_id"]')
@php($isClienteScope = $gheScope === 'cliente')
@php($canCreate = $canCreate ?? false)
@php($canUpdate = $canUpdate ?? false)
@php($canDelete = $canDelete ?? false)

<div id="modalGhe" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
    <div class="min-h-full w-full flex items-center justify-center p-4 md:p-6">
        <div class="bg-white w-full max-w-6xl rounded-2xl shadow-xl overflow-hidden max-h-[88vh] flex flex-col text-base">
            <div class="px-6 py-4 bg-amber-700 text-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">{{ $isClienteScope ? 'GHE do Cliente' : 'GHE Global' }}</h2>
                    <p class="text-xs opacity-90">
                        {{ $isClienteScope ? 'Defina o nome, funções e grupo de exames para este cliente.' : 'Defina o nome, funções e grupo de exames do GHE.' }}
                    </p>
                </div>
                <button type="button"
                        onclick="closeGheModal()"
                        class="h-9 w-9 rounded-xl hover:bg-white/10 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                <div id="gheAlert" class="hidden"></div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button"
                            @if($canCreate) onclick="openGheForm(null)" @endif
                            class="inline-flex items-center justify-center gap-2 rounded-2xl
                                   {{ $canCreate ? 'bg-amber-700 hover:bg-amber-800 active:bg-amber-900 text-white' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} px-5 py-2.5 text-sm font-semibold shadow-sm
                                   ring-1 ring-amber-600/30 transition"
                            @if(!$canCreate) disabled title="Usuário sem permissão" @endif>
                        <span class="text-base leading-none">＋</span>
                        <span>Novo GHE</span>
                    </button>
                </div>

                <div id="gheList" class="space-y-2"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal interno: Form criar/editar --}}
<div id="modalGheForm" class="fixed inset-0 z-[100] hidden bg-black/50 overflow-y-auto">
    <div class="min-h-full w-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-5xl rounded-2xl shadow-xl overflow-hidden text-base max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-slate-800/30 bg-gradient-to-r from-slate-900 to-slate-700 text-white flex items-center justify-between">
                <h3 id="gheFormTitle" class="text-xl font-semibold">Novo GHE</h3>
                <button type="button" onclick="closeGheForm()"
                        class="h-9 w-9 rounded-xl hover:bg-white/10 text-white/80 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <form id="formGhe" class="flex flex-col">
                <input type="hidden" id="ghe_id" value="">

                <div class="p-6 space-y-5">
                    <section class="rounded-2xl border border-sky-200 bg-sky-50/70 p-4">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-sky-700">1. Dados principais</div>
                        <div class="mt-3 grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Nome do GHE *</label>
                                <input required id="ghe_nome" type="text"
                                       class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2 bg-white"
                                       placeholder="Ex: Trabalho em Altura">
                            </div>
                            <div class="text-xs text-slate-500 flex items-end">
                                Defina um nome objetivo para facilitar o vínculo com funções e exames.
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-700">2. Funções vinculadas</div>
                                <p class="mt-1 text-xs text-slate-500">Mova as funções para a lista da direita.</p>
                            </div>
                            <div class="flex items-center gap-2 text-[11px] font-semibold">
                                <span id="gheFuncoesSelectedCount" class="text-slate-500">(0 selecionadas)</span>
                                <button type="button"
                                        id="gheFuncoesReload"
                                        class="text-sky-700 hover:text-sky-800 underline decoration-dotted">
                                    Recarregar
                                </button>
                                <a href="{{ route('comercial.funcoes.index') }}"
                                   class="text-sky-700 hover:text-sky-800 underline decoration-dotted"
                                   target="_blank" rel="noopener">
                                    Gerenciar
                                </a>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] gap-3">
                            <div class="space-y-2">
                                <label for="gheFuncoesSearchAvailable" class="text-[11px] font-semibold text-slate-600">Não selecionadas</label>
                                <input id="gheFuncoesSearchAvailable" type="text"
                                       class="w-full rounded-xl border-slate-200 text-sm px-3 py-2"
                                       placeholder="Buscar função">
                                <select id="gheFuncoesAvailable" multiple
                                        class="w-full h-56 rounded-xl border border-slate-200 text-sm px-2 py-2 bg-white"></select>
                            </div>

                            <div class="flex md:flex-col items-center justify-center gap-2 pt-6">
                                <button type="button" id="gheFuncoesAdd" class="h-9 min-w-9 px-2 rounded-lg border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100" title="Adicionar selecionadas">&gt;</button>
                                <button type="button" id="gheFuncoesRemove" class="h-9 min-w-9 px-2 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" title="Remover selecionadas">&lt;</button>
                                <button type="button" id="gheFuncoesAddAll" class="h-9 min-w-9 px-2 rounded-lg border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100" title="Adicionar todas">&gt;&gt;</button>
                                <button type="button" id="gheFuncoesRemoveAll" class="h-9 min-w-9 px-2 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" title="Remover todas">&lt;&lt;</button>
                            </div>

                            <div class="space-y-2">
                                <label for="gheFuncoesSearchSelected" class="text-[11px] font-semibold text-slate-600">Selecionadas</label>
                                <input id="gheFuncoesSearchSelected" type="text"
                                       class="w-full rounded-xl border-slate-200 text-sm px-3 py-2"
                                       placeholder="Buscar função">
                                <select id="gheFuncoesSelected" multiple
                                        class="w-full h-56 rounded-xl border border-slate-200 text-sm px-2 py-2 bg-white"></select>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">3. Grupo de exames</div>
                                <p class="mt-1 text-xs text-slate-500">Opcional, para vincular exames ao GHE.</p>
                            </div>
                            <button type="button"
                                    id="gheProtocoloNovo"
                                    class="text-[11px] font-semibold {{ $canCreate ? 'text-emerald-700 hover:text-emerald-800' : 'text-slate-400 cursor-not-allowed' }} underline decoration-dotted"
                                    @if(!$canCreate) disabled title="Usuário sem permissão" @endif>
                                + Novo Grupo
                            </button>
                        </div>

                        <select id="ghe_protocolo" class="w-full mt-3 rounded-xl border-slate-200 text-sm px-3 py-2 bg-white">
                            <option value="">Selecione...</option>
                        </select>
                        <div id="gheProtocoloResumo" class="mt-2 text-xs text-slate-500">Nenhum exame selecionado.</div>
                    </section>

                    <div class="hidden">
                        <input id="ghe_base_adm" type="number" min="0" step="0.01" value="0.00">
                        <input id="ghe_base_per" type="number" min="0" step="0.01" value="0.00">
                        <input id="ghe_base_dem" type="number" min="0" step="0.01" value="0.00">
                        <input id="ghe_base_fun" type="number" min="0" step="0.01" value="0.00">
                        <input id="ghe_base_ret" type="number" min="0" step="0.01" value="0.00">
                        <input id="ghe_fechado_adm" type="number" min="0" step="0.01" value="">
                        <input id="ghe_fechado_per" type="number" min="0" step="0.01" value="">
                        <input id="ghe_fechado_dem" type="number" min="0" step="0.01" value="">
                        <input id="ghe_fechado_fun" type="number" min="0" step="0.01" value="">
                        <input id="ghe_fechado_ret" type="number" min="0" step="0.01" value="">
                    </div>
                </div>

                <div class="sticky bottom-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end gap-3">
                    <button type="button" onclick="closeGheForm()"
                            class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white px-5 py-2 text-sm font-semibold">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const PERMS = {
                create: @json((bool) $canCreate),
                update: @json((bool) $canUpdate),
                delete: @json((bool) $canDelete),
            };
            const deny = (msg) => window.uiAlert?.(msg || 'Usuário sem permissão.');
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const FUNCOES = @json($funcoes->map(fn ($f) => ['id' => $f->id, 'nome' => $f->nome, 'descricao' => $f->descricao]));
            const IS_CLIENTE_SCOPE = @json($isClienteScope);
            const CLIENTE_SELECTOR = @json($clienteSelector);

            const GHE = {
                urls: {
                    global: {
                        list:   @json(route($routePrefix.'.ghes.indexJson')),
                        store:  @json(route($routePrefix.'.ghes.store')),
                        update: (id) => @json(route($routePrefix.'.ghes.update', ['ghe' => '__ID__'])).replace('__ID__', id),
                        destroy:(id) => @json(route($routePrefix.'.ghes.destroy', ['ghe' => '__ID__'])).replace('__ID__', id),
                    },
                    cliente: {
                        list:   @json(route($routePrefix.'.clientes-ghes.indexJson')),
                        store:  @json(route($routePrefix.'.clientes-ghes.store')),
                        update: (id) => @json(route($routePrefix.'.clientes-ghes.update', ['ghe' => '__ID__'])).replace('__ID__', id),
                        destroy:(id) => @json(route($routePrefix.'.clientes-ghes.destroy', ['ghe' => '__ID__'])).replace('__ID__', id),
                    },
                    protocolos: @json(route($routePrefix.'.protocolos-exames.indexJson')),
                    funcoes: @json(route($routePrefix.'.funcoes.indexJson')),
                },
                state: { ghes: [], protocolos: [], funcoes: FUNCOES || [], selectedFuncoes: new Set() },
                dom: {
                    modal: document.getElementById('modalGhe'),
                    list: document.getElementById('gheList'),
                    alert: document.getElementById('gheAlert'),
                    modalForm: document.getElementById('modalGheForm'),
                    form: document.getElementById('formGhe'),
                    title: document.getElementById('gheFormTitle'),
                    id: document.getElementById('ghe_id'),
                    nome: document.getElementById('ghe_nome'),
                    protocolo: document.getElementById('ghe_protocolo'),
                    protocoloResumo: document.getElementById('gheProtocoloResumo'),
                    funcoesSearchAvailable: document.getElementById('gheFuncoesSearchAvailable'),
                    funcoesSearchSelected: document.getElementById('gheFuncoesSearchSelected'),
                    funcoesAvailable: document.getElementById('gheFuncoesAvailable'),
                    funcoesSelected: document.getElementById('gheFuncoesSelected'),
                    funcoesAdd: document.getElementById('gheFuncoesAdd'),
                    funcoesRemove: document.getElementById('gheFuncoesRemove'),
                    funcoesAddAll: document.getElementById('gheFuncoesAddAll'),
                    funcoesRemoveAll: document.getElementById('gheFuncoesRemoveAll'),
                    funcoesSelectedCount: document.getElementById('gheFuncoesSelectedCount'),
                    funcoesReload: document.getElementById('gheFuncoesReload'),
                    baseAdm: document.getElementById('ghe_base_adm'),
                    basePer: document.getElementById('ghe_base_per'),
                    baseDem: document.getElementById('ghe_base_dem'),
                    baseFun: document.getElementById('ghe_base_fun'),
                    baseRet: document.getElementById('ghe_base_ret'),
                    fechadoAdm: document.getElementById('ghe_fechado_adm'),
                    fechadoPer: document.getElementById('ghe_fechado_per'),
                    fechadoDem: document.getElementById('ghe_fechado_dem'),
                    fechadoFun: document.getElementById('ghe_fechado_fun'),
                    fechadoRet: document.getElementById('ghe_fechado_ret'),
                    totalAdm: document.getElementById('gheTotalAdm'),
                    totalPer: document.getElementById('gheTotalPer'),
                    totalDem: document.getElementById('gheTotalDem'),
                    totalFun: document.getElementById('gheTotalFun'),
                    totalRet: document.getElementById('gheTotalRet'),
                }
            };

            function brl(n){ return Number(n||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
            function escapeHtml(str){
                return String(str||'')
                    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
                    .replaceAll('"','&quot;').replaceAll("'",'&#039;');
            }

            function alertBox(type,msg){
                if(!GHE.dom.alert) return;
                GHE.dom.alert.classList.remove('hidden');
                GHE.dom.alert.className = 'rounded-xl border px-4 py-3 text-sm';
                if(type==='ok'){
                    GHE.dom.alert.classList.add('bg-emerald-50','border-emerald-200','text-emerald-800');
                } else {
                    GHE.dom.alert.classList.add('bg-red-50','border-red-200','text-red-800');
                }
                GHE.dom.alert.textContent = msg;
            }
            function alertHide(){
                if(!GHE.dom.alert) return;
                GHE.dom.alert.classList.add('hidden');
            }

            function getClienteId(){
                const el = document.querySelector(CLIENTE_SELECTOR);
                const id = Number(el?.value || 0);
                return id > 0 ? id : null;
            }

            function normalizeRow(row){
                if (!IS_CLIENTE_SCOPE) {
                    return row;
                }
                return {
                    ...row,
                    grupo_exames_id: row?.grupo_exames_id ?? row?.protocolo?.id ?? null,
                };
            }

            async function loadGhes(){
                try{
                    alertHide();
                    const clienteId = getClienteId();
                    if (IS_CLIENTE_SCOPE && !clienteId) {
                        GHE.state.ghes = [];
                        renderGhes();
                        alertBox('err','Selecione um cliente para gerenciar os GHEs.');
                        return;
                    }
                    const baseUrl = IS_CLIENTE_SCOPE ? GHE.urls.cliente.list : GHE.urls.global.list;
                    const url = (IS_CLIENTE_SCOPE && clienteId)
                        ? `${baseUrl}?cliente_id=${encodeURIComponent(clienteId)}`
                        : baseUrl;
                    const res = await fetch(url, { headers:{'Accept':'application/json'} });
                    const json = await res.json();
                    GHE.state.ghes = (json.data || []).map(normalizeRow);
                    renderGhes();
                } catch(e){
                    console.error(e);
                    alertBox('err', IS_CLIENTE_SCOPE ? 'Falha ao carregar GHEs do cliente.' : 'Falha ao carregar GHEs globais.');
                }
            }

            async function loadProtocolos(){
                try{
                    const res = await fetch(GHE.urls.protocolos, { headers:{'Accept':'application/json'} });
                    const json = await res.json();
                    GHE.state.protocolos = json.data || [];
                    renderProtocolosSelect();
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao carregar grupos de exames.');
                }
            }

            function renderProtocolosSelect(){
                if (!GHE.dom.protocolo) return;
                const current = String(GHE.dom.protocolo.value || '');
                GHE.dom.protocolo.innerHTML = '<option value="">Selecione...</option>';
                GHE.state.protocolos.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.titulo;
                    GHE.dom.protocolo.appendChild(opt);
                });
                if (current) {
                    GHE.dom.protocolo.value = current;
                }
                updateProtocoloResumo();
            }

            function updateProtocoloResumo(){
                if (!GHE.dom.protocoloResumo) return;
                const id = Number(GHE.dom.protocolo?.value || 0);
                if (!id) {
                    GHE.dom.protocoloResumo.textContent = 'Nenhum exame selecionado.';
                    return;
                }
                const grupo = GHE.state.protocolos.find(p => Number(p.id) === id);
                const total = Number(grupo?.total || 0);
                const count = grupo?.exames?.length ?? 0;
                GHE.dom.protocoloResumo.textContent = count
                    ? `${count} exame(s) • Total ${brl(total)}`
                    : 'Grupo sem exames.';
            }

            function renderGhes(){
                const wrap = GHE.dom.list;
                if(!wrap) return;
                wrap.innerHTML = '';
                if(!GHE.state.ghes.length){
                    wrap.innerHTML = `<div class="text-sm text-slate-500 py-3">Nenhum GHE cadastrado.</div>`;
                    return;
                }
                GHE.state.ghes.forEach(g=>{
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 px-3 py-2';

                    const funcoesTxt = g.funcoes?.length ? g.funcoes.map(f => f.nome).filter(Boolean).join(', ') : 'Sem funções';

                    row.innerHTML = `
                        <div class="col-span-5">
                            <div class="font-semibold text-slate-800">${escapeHtml(g.nome)}</div>
                        </div>
                        <div class="col-span-6 text-xs text-slate-600">${escapeHtml(funcoesTxt)}</div>
                        <div class="col-span-1 flex gap-2 justify-end">
                            <button type="button" class="text-sm ${PERMS.update ? 'text-blue-600 hover:underline' : 'text-slate-400 cursor-not-allowed'}" data-action="edit" ${PERMS.update ? '' : 'disabled title=\"Usuário sem permissão\"'}>Editar</button>
                            <button type="button" class="text-sm ${PERMS.delete ? 'text-red-600 hover:underline' : 'text-slate-400 cursor-not-allowed'}" data-action="del" ${PERMS.delete ? '' : 'disabled title=\"Usuário sem permissão\"'}>Excluir</button>
                        </div>
                    `;

                    row.querySelector('[data-action="edit"]').addEventListener('click', () => openGheForm(g));
                    row.querySelector('[data-action="del"]').addEventListener('click', () => destroyGhe(g.id));
                    wrap.appendChild(row);
                });
            }

            function getSelectedFuncoes(){
                return Array.from(GHE.state.selectedFuncoes.values()).map(Number).filter(id => id > 0);
            }

            function updateSelectedCount(){
                if (!GHE.dom.funcoesSelectedCount) return;
                GHE.dom.funcoesSelectedCount.textContent = `(${getSelectedFuncoes().length} selecionadas)`;
            }

            function matchesSearch(funcao, query) {
                const nome = String(funcao?.nome || '').toLowerCase();
                const descricao = String(funcao?.descricao || '').toLowerCase();
                if (!query) return true;
                return nome.includes(query) || descricao.includes(query);
            }

            function setSelectedFuncoes(ids){
                GHE.state.selectedFuncoes = new Set((ids || []).map(id => Number(id)).filter(id => id > 0));
                renderFuncoesList();
                updateSelectedCount();
            }

            function renderFuncoesSelect(selectEl, items) {
                if (!selectEl) return;
                selectEl.innerHTML = '';

                if (!items.length) {
                    const opt = document.createElement('option');
                    opt.disabled = true;
                    opt.value = '';
                    opt.textContent = 'Nenhuma função';
                    selectEl.appendChild(opt);
                    return;
                }

                items.forEach(funcao => {
                    const opt = document.createElement('option');
                    opt.value = String(funcao.id);
                    opt.textContent = String(funcao.nome || '').toLocaleUpperCase('pt-BR');
                    selectEl.appendChild(opt);
                });
            }

            function renderFuncoesList() {
                const queryAvailable = String(GHE.dom.funcoesSearchAvailable?.value || '').trim().toLowerCase();
                const querySelected = String(GHE.dom.funcoesSearchSelected?.value || '').trim().toLowerCase();

                const available = [];
                const selected = [];

                GHE.state.funcoes.forEach(funcao => {
                    const id = Number(funcao?.id || 0);
                    if (id <= 0) return;

                    if (GHE.state.selectedFuncoes.has(id)) {
                        if (matchesSearch(funcao, querySelected)) {
                            selected.push(funcao);
                        }
                    } else if (matchesSearch(funcao, queryAvailable)) {
                        available.push(funcao);
                    }
                });

                renderFuncoesSelect(GHE.dom.funcoesAvailable, available);
                renderFuncoesSelect(GHE.dom.funcoesSelected, selected);
                updateSelectedCount();
            }

            function moveOptions(sourceEl, toSelected) {
                if (!sourceEl) return;
                const ids = Array.from(sourceEl.selectedOptions)
                    .map(opt => Number(opt.value))
                    .filter(id => id > 0);

                ids.forEach(id => {
                    if (toSelected) {
                        GHE.state.selectedFuncoes.add(id);
                    } else {
                        GHE.state.selectedFuncoes.delete(id);
                    }
                });

                renderFuncoesList();
            }

            function moveAll(toSelected) {
                const allIds = GHE.state.funcoes
                    .map(f => Number(f?.id || 0))
                    .filter(id => id > 0);

                allIds.forEach(id => {
                    if (toSelected) {
                        GHE.state.selectedFuncoes.add(id);
                    } else {
                        GHE.state.selectedFuncoes.delete(id);
                    }
                });

                renderFuncoesList();
            }

            async function reloadFuncoes(){
                try{
                    const selected = getSelectedFuncoes();
                    const res = await fetch(GHE.urls.funcoes, { headers:{'Accept':'application/json'} });
                    const json = await res.json();
                    GHE.state.funcoes = (json.data || []).map(f => ({
                        id: Number(f.id),
                        nome: f.nome,
                        descricao: f.descricao || '',
                        ativo: !!f.ativo,
                    }));
                    renderFuncoesList();
                    setSelectedFuncoes(selected);
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao recarregar funções.');
                }
            }

            async function saveGhe(e){
                e.preventDefault();
                const id = GHE.dom.id.value;
                if (id && !PERMS.update) return deny('Usuário sem permissão para editar.');
                if (!id && !PERMS.create) return deny('Usuário sem permissão para criar.');
                const payload = {
                    nome: GHE.dom.nome.value.trim(),
                    grupo_exames_id: Number(GHE.dom.protocolo?.value || 0) || null,
                    funcoes: getSelectedFuncoes(),
                    base: {
                        admissional: Number(GHE.dom.baseAdm.value || 0),
                        periodico: Number(GHE.dom.basePer.value || 0),
                        demissional: Number(GHE.dom.baseDem.value || 0),
                        mudanca_funcao: Number(GHE.dom.baseFun.value || 0),
                        retorno_trabalho: Number(GHE.dom.baseRet.value || 0),
                    },
                    preco_fechado: {
                        admissional: GHE.dom.fechadoAdm.value ? Number(GHE.dom.fechadoAdm.value) : null,
                        periodico: GHE.dom.fechadoPer.value ? Number(GHE.dom.fechadoPer.value) : null,
                        demissional: GHE.dom.fechadoDem.value ? Number(GHE.dom.fechadoDem.value) : null,
                        mudanca_funcao: GHE.dom.fechadoFun.value ? Number(GHE.dom.fechadoFun.value) : null,
                        retorno_trabalho: GHE.dom.fechadoRet.value ? Number(GHE.dom.fechadoRet.value) : null,
                    },
                    ativo: true,
                };

                if (!payload.nome) return alertBox('err','Informe o nome do GHE.');

                const clienteId = getClienteId();
                if (IS_CLIENTE_SCOPE && !clienteId) {
                    return alertBox('err', 'Selecione um cliente para salvar o GHE.');
                }

                if (IS_CLIENTE_SCOPE) {
                    payload.cliente_id = clienteId;
                    payload.protocolo_id = payload.grupo_exames_id;
                    delete payload.grupo_exames_id;
                }

                const url = id
                    ? (IS_CLIENTE_SCOPE ? GHE.urls.cliente.update(id) : GHE.urls.global.update(id))
                    : (IS_CLIENTE_SCOPE ? GHE.urls.cliente.store : GHE.urls.global.store);
                const method = id ? 'PUT' : 'POST';

                try{
                    const res = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    if(!res.ok) throw new Error('fail');
                    await loadGhes();
                    closeGheForm();
                    window.dispatchEvent(new CustomEvent('ghes:updated'));
                    window.dispatchEvent(new CustomEvent('cliente-ghes:updated'));
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao salvar GHE.');
                }
            }

            async function destroyGhe(id){
                if (!PERMS.delete) return deny('Usuário sem permissão para excluir.');
                const ok = await window.uiConfirm('Deseja remover este GHE?');
                if (!ok) return;
                try{
                    const url = IS_CLIENTE_SCOPE ? GHE.urls.cliente.destroy(id) : GHE.urls.global.destroy(id);
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                    });
                    if(!res.ok) throw new Error('fail');
                    await loadGhes();
                    window.dispatchEvent(new CustomEvent('ghes:updated'));
                    window.dispatchEvent(new CustomEvent('cliente-ghes:updated'));
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao excluir GHE.');
                }
            }

            window.openGheModal = async function(){
                GHE.dom.modal?.classList.remove('hidden');
                await loadProtocolos();
                renderFuncoesList();
                await loadGhes();
            };
            window.closeGheModal = () => GHE.dom.modal?.classList.add('hidden');

            window.openGheForm = async function(ghe){
                if (ghe && !PERMS.update) return deny('Usuário sem permissão para editar.');
                if (!ghe && !PERMS.create) return deny('Usuário sem permissão para criar.');
                GHE.dom.modalForm?.classList.remove('hidden');
                GHE.dom.title.textContent = ghe ? 'Editar GHE' : 'Novo GHE';
                GHE.dom.id.value = ghe?.id || '';
                GHE.dom.nome.value = ghe?.nome || '';
                if (GHE.dom.protocolo) {
                    GHE.dom.protocolo.value = ghe?.grupo_exames_id || '';
                    updateProtocoloResumo();
                }
                if (GHE.dom.funcoesSearchAvailable) GHE.dom.funcoesSearchAvailable.value = '';
                if (GHE.dom.funcoesSearchSelected) GHE.dom.funcoesSearchSelected.value = '';
                renderFuncoesList();
                setSelectedFuncoes(ghe?.funcoes?.map(f => f.id) || []);
                GHE.dom.baseAdm.value = Number(ghe?.base?.admissional || 0).toFixed(2);
                GHE.dom.basePer.value = Number(ghe?.base?.periodico || 0).toFixed(2);
                GHE.dom.baseDem.value = Number(ghe?.base?.demissional || 0).toFixed(2);
                GHE.dom.baseFun.value = Number(ghe?.base?.mudanca_funcao || 0).toFixed(2);
                GHE.dom.baseRet.value = Number(ghe?.base?.retorno_trabalho || 0).toFixed(2);
                GHE.dom.fechadoAdm.value = ghe?.preco_fechado?.admissional ?? '';
                GHE.dom.fechadoPer.value = ghe?.preco_fechado?.periodico ?? '';
                GHE.dom.fechadoDem.value = ghe?.preco_fechado?.demissional ?? '';
                GHE.dom.fechadoFun.value = ghe?.preco_fechado?.mudanca_funcao ?? '';
                GHE.dom.fechadoRet.value = ghe?.preco_fechado?.retorno_trabalho ?? '';
            };
            window.closeGheForm = () => GHE.dom.modalForm?.classList.add('hidden');

            GHE.dom.form?.addEventListener('submit', saveGhe);
            GHE.dom.protocolo?.addEventListener('change', updateProtocoloResumo);
            GHE.dom.funcoesReload?.addEventListener('click', reloadFuncoes);
            GHE.dom.funcoesSearchAvailable?.addEventListener('input', renderFuncoesList);
            GHE.dom.funcoesSearchSelected?.addEventListener('input', renderFuncoesList);
            GHE.dom.funcoesAdd?.addEventListener('click', () => moveOptions(GHE.dom.funcoesAvailable, true));
            GHE.dom.funcoesRemove?.addEventListener('click', () => moveOptions(GHE.dom.funcoesSelected, false));
            GHE.dom.funcoesAddAll?.addEventListener('click', () => moveAll(true));
            GHE.dom.funcoesRemoveAll?.addEventListener('click', () => moveAll(false));
            GHE.dom.funcoesAvailable?.addEventListener('dblclick', () => moveOptions(GHE.dom.funcoesAvailable, true));
            GHE.dom.funcoesSelected?.addEventListener('dblclick', () => moveOptions(GHE.dom.funcoesSelected, false));
            document.getElementById('gheProtocoloNovo')?.addEventListener('click', () => {
                if (!PERMS.create) {
                    deny('Usuário sem permissão para criar.');
                    return;
                }
                if (typeof window.openProtocolosModal === 'function') {
                    window.openProtocolosModal();
                }
            });
            window.addEventListener('protocolos:updated', () => {
                loadProtocolos();
            });
        })();
    </script>
@endpush

@include('comercial.tabela-precos.itens.modal-protocolos', [
    'routePrefix' => $routePrefix,
    'canCreate' => $canCreate,
    'canUpdate' => $canUpdate,
    'canDelete' => $canDelete,
])
