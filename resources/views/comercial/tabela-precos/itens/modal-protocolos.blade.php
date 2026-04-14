@php($routePrefix = $routePrefix ?? 'comercial')
@php($canCreate = $canCreate ?? false)
@php($canUpdate = $canUpdate ?? false)
@php($canDelete = $canDelete ?? false)
@php($tabelaPrecosRoutePrefix = request()->routeIs('master.*') ? 'master' : 'comercial')

<div id="modalProtocolos" data-overlay-root="true" class="fixed inset-0 z-[240] hidden bg-black/50 overflow-y-auto" style="z-index: 240;">
    <div class="min-h-full w-full flex items-center justify-center p-4 md:p-6">
        <div class="bg-white w-full max-w-5xl rounded-2xl shadow-xl overflow-hidden max-h-[85vh] flex flex-col">
            <div class="px-6 py-4 bg-slate-800 text-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Grupo de Exames</h2>
                    <p class="text-xs opacity-90">Monte grupos com exames e preço total.</p>
                </div>
                <button type="button"
                        onclick="closeProtocolosModal()"
                        class="h-9 w-9 rounded-xl hover:bg-white/10 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                <div id="protocolosAlert" class="hidden"></div>

                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-slate-800">Lista de Grupos</div>

                    <button type="button"
                            @if($canCreate) onclick="openProtocoloForm(null)" @endif
                            class="inline-flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold border shadow-sm transition
                                   {{ $canCreate ? 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100' : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed' }}"
                            @if(!$canCreate) disabled title="Usuário sem permissão" @endif>
                        <span class="text-base leading-none">＋</span>
                        <span>Novo Grupo</span>
                    </button>
                </div>

                <div id="protocolosList" class="space-y-2"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal interno: Form criar/editar --}}
<div id="modalProtocoloForm" data-overlay-root="true" class="fixed inset-0 z-[250] hidden bg-black/50 overflow-y-auto" style="z-index: 250;">
    <div class="min-h-full w-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between">
                <h3 id="protocoloFormTitle" class="text-lg font-semibold">Novo Grupo</h3>
                <button type="button" onclick="closeProtocoloForm()"
                        class="h-9 w-9 rounded-xl hover:bg-white/10 text-white flex items-center justify-center">
                    ✕
                </button>
            </div>

            <form id="formProtocolo" class="p-6 space-y-4">
                <div id="protocoloFormAlert" class="hidden"></div>
                <input type="hidden" id="protocolo_id" value="">
                <div id="protocoloScopeBox" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-3">
                    <div class="text-xs font-semibold text-amber-800">Disponibilidade do grupo</div>
                    <div class="mt-2 space-y-2 text-sm text-slate-700">
                        <label class="flex items-start gap-2">
                            <input type="radio" name="protocolo_scope" value="generico" checked>
                            <span>Genérico da Formed</span>
                        </label>
                        <label class="flex items-start gap-2">
                            <input type="radio" name="protocolo_scope" value="cliente">
                            <span>Exclusivo de um cliente</span>
                        </label>
                    </div>
                    <div id="protocoloClienteBox" class="mt-3 hidden">
                        <label class="text-xs font-semibold text-slate-600">Cliente do grupo exclusivo</label>
                        <select id="protocolo_cliente_id"
                                class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2 bg-white">
                            <option value="">Selecione um cliente...</option>
                        </select>
                        <div class="mt-1 text-[11px] text-slate-500">Digite no select para buscar pelo nome ou número.</div>
                    </div>
                    <div id="protocoloScopeHint" class="mt-2 text-xs text-slate-600"></div>
                </div>
                <x-toggle-ativo
                    id="protocolo_ativo"
                    name="ativo"
                    :checked="true"
                    on-label="Ativo"
                    off-label="Inativo"
                    text-class="text-sm font-medium text-slate-700"
                />

                <div>
                    <label class="text-xs font-semibold text-slate-600">Título *</label>
                    <input id="protocolo_titulo" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           placeholder="Ex: Trabalho em Altura">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Descrição</label>
                    <input id="protocolo_descricao" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           placeholder="Opcional">
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-600">Exames do grupo</label>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    id="btnProtocoloReloadExames"
                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold
                                           text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100">
                                Recarregar exames
                            </button>
                            <button type="button"
                                    id="btnProtocoloNovoExame"
                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold
                                           {{ $canCreate ? 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100' : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed' }}"
                                    @if(!$canCreate) disabled title="Usuário sem permissão" @endif>
                                + Novo exame
                            </button>
                            <span class="text-xs text-slate-500" id="protocoloExamesCount">0 selecionados</span>
                        </div>
                    </div>
                    <div id="protocoloExamesList" class="mt-2 max-h-48 overflow-y-auto border border-slate-200 rounded-xl p-3 space-y-2 text-sm">
                        <div class="text-slate-500">Carregando exames...</div>
                    </div>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="closeProtocoloForm()"
                            class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm ring-1 ring-indigo-600/30">
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

            const PROTOCOLOS = {
                urls: {
                    list:   @json(route($routePrefix.'.protocolos-exames.indexJson')),
                    store:  @json(route($routePrefix.'.protocolos-exames.store')),
                    update: (id) => @json(route($routePrefix.'.protocolos-exames.update', ['protocolo' => '__ID__'])).replace('__ID__', id),
                    destroy:(id) => @json(route($routePrefix.'.protocolos-exames.destroy', ['protocolo' => '__ID__'])).replace('__ID__', id),
                    clientes: @json(route($routePrefix.'.protocolos-exames.clientesJson')),
                    exames: @json(route($routePrefix.'.exames.indexJson')),
                    novoExameRedirect: @json(route($tabelaPrecosRoutePrefix.'.tabela-precos.itens.index', ['open' => 'novo-exame'])),
                },
                state: {
                    protocolos: [],
                    clientes: [],
                    exames: [],
                    context: {
                        clienteId: null,
                        returnTo: null,
                    },
                    clienteSearchTerm: '',
                    clienteSearchTimer: null,
                },
                dom: {
                    modal: document.getElementById('modalProtocolos'),
                    list: document.getElementById('protocolosList'),
                    alert: document.getElementById('protocolosAlert'),
                    modalForm: document.getElementById('modalProtocoloForm'),
                    form: document.getElementById('formProtocolo'),
                    title: document.getElementById('protocoloFormTitle'),
                    formAlert: document.getElementById('protocoloFormAlert'),
                    id: document.getElementById('protocolo_id'),
                    scopeBox: document.getElementById('protocoloScopeBox'),
                    clienteBox: document.getElementById('protocoloClienteBox'),
                    clienteId: document.getElementById('protocolo_cliente_id'),
                    scopeHint: document.getElementById('protocoloScopeHint'),
                    titulo: document.getElementById('protocolo_titulo'),
                    descricao: document.getElementById('protocolo_descricao'),
                    ativo: document.getElementById('protocolo_ativo'),
                    examesList: document.getElementById('protocoloExamesList'),
                    examesCount: document.getElementById('protocoloExamesCount'),
                    btnReloadExames: document.getElementById('btnProtocoloReloadExames'),
                    btnNovoExame: document.getElementById('btnProtocoloNovoExame'),
                }
            };

            function getScopeRadios() {
                return Array.from(document.querySelectorAll('input[name="protocolo_scope"]'));
            }

            function getCurrentClienteId() {
                const id = Number(PROTOCOLOS.state.context.clienteId || 0);
                return id > 0 ? id : null;
            }

            function buildListUrl() {
                const url = new URL(PROTOCOLOS.urls.list, window.location.origin);
                const clienteId = getCurrentClienteId();

                if (clienteId) {
                    url.searchParams.set('cliente_id', String(clienteId));
                }

                return url.toString();
            }

            function buildNovoExameRedirectUrl() {
                const url = new URL(PROTOCOLOS.urls.novoExameRedirect, window.location.origin);
                const returnUrl = new URL(window.location.href);
                const clienteId = getCurrentClienteId();

                returnUrl.searchParams.set('tab', 'parametros');
                returnUrl.searchParams.delete('open');
                returnUrl.searchParams.delete('cliente_id');

                url.searchParams.set('return_to', returnUrl.toString());
                url.searchParams.set('return_open', 'novo-grupo');

                if (clienteId) {
                    url.searchParams.set('cliente_id', String(clienteId));
                }

                return url.toString();
            }

            function selectedScope() {
                return getScopeRadios().find((radio) => radio.checked)?.value || 'generico';
            }

            function setSelectedScope(scope) {
                getScopeRadios().forEach((radio) => {
                    radio.checked = radio.value === scope;
                });
            }

            function refreshScopeUi() {
                const scopeBox = PROTOCOLOS.dom.scopeBox;
                const clienteBox = PROTOCOLOS.dom.clienteBox;
                const scopeHint = PROTOCOLOS.dom.scopeHint;
                const clienteRadio = getScopeRadios().find((radio) => radio.value === 'cliente');

                if (!scopeBox || !scopeHint || !clienteBox) return;

                if (!PROTOCOLOS.state.clientes.length) {
                    scopeBox.classList.add('hidden');
                    return;
                }

                scopeBox.classList.remove('hidden');
                if (clienteRadio) {
                    clienteRadio.disabled = false;
                }
                clienteBox.classList.toggle('hidden', selectedScope() !== 'cliente');
                scopeHint.textContent = selectedScope() === 'cliente'
                    ? 'Este grupo ficará disponível apenas para o cliente escolhido.'
                    : 'Grupos genéricos aparecem para todos os clientes.';
            }

            function renderClientesFormSelect(selectedId = null) {
                const select = PROTOCOLOS.dom.clienteId;
                if (!select) return;

                const current = String(selectedId || select.value || getCurrentClienteId() || '');
                const term = String(PROTOCOLOS.state.clienteSearchTerm || '').trim().toLowerCase();
                select.innerHTML = '<option value="">Selecione um cliente...</option>';

                PROTOCOLOS.state.clientes
                    .filter((cliente) => {
                        if (!term) return true;
                        return String(cliente.nome || '').toLowerCase().includes(term);
                    })
                    .forEach((cliente) => {
                    const opt = document.createElement('option');
                    opt.value = String(cliente.id);
                    opt.textContent = cliente.nome;
                    select.appendChild(opt);
                    });

                select.value = current;
            }

            function resetClienteSearchFilter(selectedId = null) {
                PROTOCOLOS.state.clienteSearchTerm = '';
                if (PROTOCOLOS.state.clienteSearchTimer) {
                    clearTimeout(PROTOCOLOS.state.clienteSearchTimer);
                    PROTOCOLOS.state.clienteSearchTimer = null;
                }
                renderClientesFormSelect(selectedId);
            }

            function handleClienteSelectTypeSearch(event) {
                const key = event.key;

                if (['Tab', 'Enter', 'ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(key)) {
                    return;
                }

                if (key === 'Escape') {
                    resetClienteSearchFilter(PROTOCOLOS.dom.clienteId?.value || '');
                    return;
                }

                if (key === 'Backspace') {
                    event.preventDefault();
                    PROTOCOLOS.state.clienteSearchTerm = String(PROTOCOLOS.state.clienteSearchTerm || '').slice(0, -1);
                } else if (key.length === 1 && /[\p{L}\p{N}\s]/u.test(key)) {
                    event.preventDefault();
                    PROTOCOLOS.state.clienteSearchTerm = `${PROTOCOLOS.state.clienteSearchTerm || ''}${key}`;
                } else {
                    return;
                }

                renderClientesFormSelect(PROTOCOLOS.dom.clienteId?.value || '');

                if (PROTOCOLOS.state.clienteSearchTimer) {
                    clearTimeout(PROTOCOLOS.state.clienteSearchTimer);
                }

                PROTOCOLOS.state.clienteSearchTimer = setTimeout(() => {
                    resetClienteSearchFilter(PROTOCOLOS.dom.clienteId?.value || '');
                }, 900);
            }

            async function loadClientes() {
                if (!PROTOCOLOS.state.clientes.length) {
                    const res = await fetch(PROTOCOLOS.urls.clientes, { headers:{'Accept':'application/json'} });
                    const json = await res.json();
                    PROTOCOLOS.state.clientes = json.data || [];
                }

                resetClienteSearchFilter();
                refreshScopeUi();
            }

            function ensureModalOverSidebar(modalEl, zIndexValue) {
                if (!modalEl) return;
                const overlayRoot = document.getElementById('app-overlay-root');
                const mountTarget = overlayRoot || document.body;
                if (modalEl.parentElement !== mountTarget) {
                    mountTarget.appendChild(modalEl);
                }
                modalEl.classList.add('pointer-events-auto');
                modalEl.style.position = 'fixed';
                modalEl.style.inset = '0';
                modalEl.style.top = '0';
                modalEl.style.left = '0';
                modalEl.style.right = '0';
                modalEl.style.bottom = '0';
                modalEl.style.width = '100vw';
                modalEl.style.height = '100vh';
                modalEl.style.zIndex = String(zIndexValue);
            }

            ensureModalOverSidebar(PROTOCOLOS.dom.modal, 240);
            ensureModalOverSidebar(PROTOCOLOS.dom.modalForm, 250);

            function brl(n){ return Number(n||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
            function escapeHtml(str){
                return String(str||'')
                    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
                    .replaceAll('"','&quot;').replaceAll("'",'&#039;');
            }

            function alertBox(type,msg){
                const formAberto = PROTOCOLOS.dom.modalForm && !PROTOCOLOS.dom.modalForm.classList.contains('hidden');
                const alvo = formAberto ? PROTOCOLOS.dom.formAlert : PROTOCOLOS.dom.alert;
                if(!alvo) return;
                alvo.classList.remove('hidden');
                alvo.className = 'rounded-xl border px-4 py-3 text-sm';
                if(type==='ok'){
                    alvo.classList.add('bg-emerald-50','border-emerald-200','text-emerald-800');
                } else {
                    alvo.classList.add('bg-red-50','border-red-200','text-red-800');
                }
                alvo.textContent = msg;
            }
            function alertHide(){
                if(PROTOCOLOS.dom.alert) PROTOCOLOS.dom.alert.classList.add('hidden');
                if(PROTOCOLOS.dom.formAlert) PROTOCOLOS.dom.formAlert.classList.add('hidden');
            }

            function selectedExameIdsFromChecklist(){
                return Array.from(PROTOCOLOS.dom.examesList?.querySelectorAll('input[type="checkbox"]:checked') || [])
                    .map((cb) => Number(cb.value))
                    .filter((id) => Number.isFinite(id));
            }

            async function loadExames(force = false){
                if (!force && PROTOCOLOS.state.exames.length) return;
                const res = await fetch(PROTOCOLOS.urls.exames, { headers:{'Accept':'application/json'} });
                const json = await res.json();
                PROTOCOLOS.state.exames = json.data || [];
            }

            async function loadProtocolos(){
                try{
                    alertHide();
                    const url = buildListUrl();
                    if (!url) {
                        PROTOCOLOS.state.protocolos = [];
                        renderProtocolos();
                        return;
                    }
                    const res = await fetch(url, { headers:{'Accept':'application/json'} });
                    const json = await res.json();
                    PROTOCOLOS.state.protocolos = json.data || [];
                    renderProtocolos();
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao carregar grupos.');
                }
            }

            function renderProtocolos(){
                const wrap = PROTOCOLOS.dom.list;
                if(!wrap) return;
                wrap.innerHTML = '';
                if(!PROTOCOLOS.state.protocolos.length){
                    wrap.innerHTML = `<div class="text-sm text-slate-500 py-3">Nenhum grupo cadastrado.</div>`;
                    return;
                }
                PROTOCOLOS.state.protocolos.forEach(p=>{
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 px-3 py-2';

                    const total = brl(p.total || 0);
                    const examesTxt = p.exames?.length ? `${p.exames.length} exame(s)` : 'Sem exames';

                    row.innerHTML = `
                        <div class="col-span-5">
                            <div class="font-semibold text-slate-800">${escapeHtml(p.titulo)}</div>
                            <div class="mt-1 flex items-center gap-3 text-xs text-slate-500">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ${p.cliente_id ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800'}">
                                    ${p.cliente_id ? 'Exclusivo' : 'Genérico'}
                                </span>
                                <span>${examesTxt}</span>
                            </div>
                            ${p.descricao ? `<div class="text-xs text-slate-500 mt-1">${escapeHtml(p.descricao)}</div>` : ''}
                        </div>
                        <div class="col-span-3 text-xs text-slate-600"></div>
                        <div class="col-span-2 text-right font-semibold text-slate-800">${total}</div>
                        <div class="col-span-2 flex gap-2 justify-end">
                            <button type="button" class="text-sm ${PERMS.update ? 'text-blue-600 hover:underline' : 'text-slate-400 cursor-not-allowed'}" data-action="edit" ${PERMS.update ? '' : 'disabled title=\"Usuário sem permissão\"'}>Editar</button>
                            <button type="button" class="text-sm ${PERMS.delete ? 'text-red-600 hover:underline' : 'text-slate-400 cursor-not-allowed'}" data-action="del" ${PERMS.delete ? '' : 'disabled title=\"Usuário sem permissão\"'}>Excluir</button>
                        </div>
                    `;

                    row.querySelector('[data-action="edit"]').addEventListener('click', () => openProtocoloForm(p));
                    row.querySelector('[data-action="del"]').addEventListener('click', () => destroyProtocolo(p.id));

                    wrap.appendChild(row);
                });
            }

            function renderExamesChecklist(selectedIds = []){
                const list = PROTOCOLOS.dom.examesList;
                if(!list) return;
                list.innerHTML = '';
                if(!PROTOCOLOS.state.exames.length){
                    list.innerHTML = `<div class="text-sm text-slate-500">Nenhum exame cadastrado.</div>`;
                    return;
                }
                PROTOCOLOS.state.exames.forEach(ex=>{
                    const row = document.createElement('label');
                    row.className = 'flex items-center gap-2';
                    row.innerHTML = `
                        <input type="checkbox" value="${ex.id}" ${selectedIds.includes(ex.id) ? 'checked' : ''}>
                        <span>${escapeHtml(ex.titulo)}</span>
                        <span class="ml-auto text-xs text-slate-500">${brl(ex.preco)}</span>
                    `;
                    list.appendChild(row);
                });
                updateExamesCount();
                list.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.addEventListener('change', updateExamesCount);
                });
            }

            function updateExamesCount(){
                const count = PROTOCOLOS.dom.examesList?.querySelectorAll('input[type="checkbox"]:checked').length || 0;
                if (PROTOCOLOS.dom.examesCount) {
                    PROTOCOLOS.dom.examesCount.textContent = `${count} selecionados`;
                }
            }

            async function saveProtocolo(e){
                e.preventDefault();
                const id = PROTOCOLOS.dom.id.value;
                if (id && !PERMS.update) return deny('Usuário sem permissão para editar.');
                if (!id && !PERMS.create) return deny('Usuário sem permissão para criar.');
                const payload = {
                    cliente_id: selectedScope() === 'cliente'
                        ? (Number(PROTOCOLOS.dom.clienteId?.value || 0) || null)
                        : null,
                    titulo: PROTOCOLOS.dom.titulo.value.trim(),
                    descricao: PROTOCOLOS.dom.descricao.value.trim() || null,
                    ativo: PROTOCOLOS.dom.ativo.checked,
                    exames: Array.from(PROTOCOLOS.dom.examesList.querySelectorAll('input[type="checkbox"]:checked'))
                        .map(cb => Number(cb.value)),
                };

                if (!payload.titulo) return alertBox('err','Informe o título do grupo.');
                if (selectedScope() === 'cliente' && !payload.cliente_id) {
                    return alertBox('err','Selecione o cliente do grupo exclusivo.');
                }

                const url = id ? PROTOCOLOS.urls.update(id) : PROTOCOLOS.urls.store;
                const method = id ? 'PUT' : 'POST';

                try{
                    alertHide();
                    const res = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const json = await res.json().catch(() => ({}));

                    if(!res.ok) {
                        if (json?.errors) {
                            const first = Object.values(json.errors)[0]?.[0] || 'Erro ao salvar grupo.';
                            return alertBox('err', first);
                        }
                        return alertBox('err', json?.message || 'Erro ao salvar grupo.');
                    }

                    await loadProtocolos();
                    const detail = {
                        returnTo: PROTOCOLOS.state.context.returnTo || null,
                        message: 'Grupo de exames salvo com sucesso.',
                        protocolo: json?.data ? {
                            id: Number(json.data.id || 0) || null,
                            titulo: json.data.titulo || '',
                            cliente_id: json.data.cliente_id ? Number(json.data.cliente_id) : null,
                        } : null,
                    };

                    closeProtocoloForm();

                    if (PROTOCOLOS.state.context.returnTo === 'ghe-form') {
                        closeProtocolosModal();
                    }

                    window.dispatchEvent(new CustomEvent('protocolos:updated', { detail }));
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao salvar grupo. Verifique sua conexão e tente novamente.');
                }
            }

            async function destroyProtocolo(id){
                if (!PERMS.delete) return deny('Usuário sem permissão para excluir.');
                const ok = await window.uiConfirm('Deseja remover este grupo?');
                if (!ok) return;
                try{
                    const res = await fetch(PROTOCOLOS.urls.destroy(id), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                    });
                    if(!res.ok) throw new Error('fail');
                    await loadProtocolos();
                    window.dispatchEvent(new CustomEvent('protocolos:updated'));
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao excluir grupo.');
                }
            }

            window.openProtocolosModal = async function(options = {}){
                PROTOCOLOS.state.context.clienteId = Number(options?.clienteId || 0) || null;
                PROTOCOLOS.state.context.returnTo = options?.returnTo || null;
                refreshScopeUi();
                ensureModalOverSidebar(PROTOCOLOS.dom.modal, 240);
                alertHide();
                PROTOCOLOS.dom.modal?.classList.remove('hidden');
                await loadClientes();
                await loadProtocolos();

                if (options?.openForm) {
                    window.openProtocoloForm(null);
                }
            };
            window.closeProtocolosModal = () => {
                PROTOCOLOS.state.context.returnTo = null;
                alertHide();
                PROTOCOLOS.dom.modal?.classList.add('hidden');
            };

            window.openProtocoloForm = async function(protocolo){
                if (protocolo && !PERMS.update) return deny('Usuário sem permissão para editar.');
                if (!protocolo && !PERMS.create) return deny('Usuário sem permissão para criar.');
                await loadExames();
                await loadClientes();
                ensureModalOverSidebar(PROTOCOLOS.dom.modalForm, 250);
                alertHide();
                PROTOCOLOS.dom.modalForm?.classList.remove('hidden');
                PROTOCOLOS.dom.title.textContent = protocolo ? 'Editar Grupo' : 'Novo Grupo';
                PROTOCOLOS.dom.id.value = protocolo?.id || '';
                const defaultClienteId = protocolo?.cliente_id || getCurrentClienteId() || null;
                const defaultScope = protocolo
                    ? (protocolo?.cliente_id ? 'cliente' : 'generico')
                    : (defaultClienteId ? 'cliente' : 'generico');
                setSelectedScope(defaultScope);
                resetClienteSearchFilter(defaultClienteId);
                refreshScopeUi();
                PROTOCOLOS.dom.titulo.value = protocolo?.titulo || '';
                PROTOCOLOS.dom.descricao.value = protocolo?.descricao || '';
                PROTOCOLOS.dom.ativo.checked = protocolo ? !!protocolo.ativo : true;
                const selected = (protocolo?.exames || []).map(e => e.id);
                renderExamesChecklist(selected);
            };
            window.closeProtocoloForm = () => {
                alertHide();
                PROTOCOLOS.dom.modalForm?.classList.add('hidden');
            };

            PROTOCOLOS.dom.form?.addEventListener('submit', saveProtocolo);
            getScopeRadios().forEach((radio) => {
                radio.addEventListener('change', refreshScopeUi);
            });
            PROTOCOLOS.dom.clienteId?.addEventListener('keydown', handleClienteSelectTypeSearch);
            PROTOCOLOS.dom.clienteId?.addEventListener('blur', () => {
                resetClienteSearchFilter(PROTOCOLOS.dom.clienteId?.value || '');
            });
            PROTOCOLOS.dom.btnReloadExames?.addEventListener('click', async () => {
                const selected = selectedExameIdsFromChecklist();
                await loadExames(true);
                renderExamesChecklist(selected);
                alertBox('ok', 'Lista de exames atualizada.');
            });
            PROTOCOLOS.dom.btnNovoExame?.addEventListener('click', () => {
                if (!PERMS.create) return deny('Usuário sem permissão para criar.');
                if (typeof window.openExameForm !== 'function') {
                    window.location.assign(buildNovoExameRedirectUrl());
                    return;
                }
                window.openExameForm(null);
            });

            async function handleAutoOpenFromQuery() {
                const url = new URL(window.location.href);
                if (url.searchParams.get('open') !== 'novo-grupo') return;

                const clienteId = Number(url.searchParams.get('cliente_id') || 0) || null;
                url.searchParams.delete('open');
                url.searchParams.delete('cliente_id');
                window.history.replaceState({}, document.title, url.toString());

                await window.openProtocolosModal({ clienteId, openForm: true });
            }
            window.addEventListener('exames:updated', async () => {
                if (!PROTOCOLOS.dom.modalForm || PROTOCOLOS.dom.modalForm.classList.contains('hidden')) return;
                const selected = selectedExameIdsFromChecklist();
                await loadExames(true);
                renderExamesChecklist(selected);
            });
            document.addEventListener('click', (e) => {
                if (PROTOCOLOS.dom.modalForm && !PROTOCOLOS.dom.modalForm.classList.contains('hidden') && e.target === PROTOCOLOS.dom.modalForm) {
                    window.closeProtocoloForm?.();
                    return;
                }
                if (PROTOCOLOS.dom.modal && !PROTOCOLOS.dom.modal.classList.contains('hidden') && e.target === PROTOCOLOS.dom.modal) {
                    window.closeProtocolosModal?.();
                }
            });
            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') return;
                if (PROTOCOLOS.dom.modalForm && !PROTOCOLOS.dom.modalForm.classList.contains('hidden')) {
                    window.closeProtocoloForm?.();
                    return;
                }
                if (PROTOCOLOS.dom.modal && !PROTOCOLOS.dom.modal.classList.contains('hidden')) {
                    window.closeProtocolosModal?.();
                }
            });

            handleAutoOpenFromQuery();
        })();
    </script>
@endpush
