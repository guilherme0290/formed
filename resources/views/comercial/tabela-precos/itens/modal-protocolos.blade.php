@php($routePrefix = $routePrefix ?? 'comercial')
@php($canCreate = $canCreate ?? false)
@php($canUpdate = $canUpdate ?? false)
@php($canDelete = $canDelete ?? false)
@php($clienteSelector = $clienteSelector ?? null)

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

                <div id="protocoloEscopoWrap" class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2">
                    <div class="text-xs font-semibold text-slate-600">Escopo do grupo</div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="protocolo_escopo" id="protocolo_escopo_generico" value="generico" checked>
                        <span>Genérico da empresa</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="protocolo_escopo" id="protocolo_escopo_cliente" value="cliente">
                        <span>Exclusivo deste cliente</span>
                    </label>
                    <div id="protocoloEscopoHint" class="text-xs text-slate-500"></div>
                </div>

                <div id="protocoloClienteWrap" class="hidden">
                    <label class="text-xs font-semibold text-slate-600">Vincular este grupo de exame ao cliente... *</label>
                    <input id="protocolo_cliente_search" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           placeholder="Buscar por nome ou documento">
                    <div id="protocoloClienteResults"
                         class="mt-2 max-h-48 overflow-y-auto rounded-xl border border-slate-200 bg-white hidden"></div>
                    <input type="hidden" id="protocolo_cliente_id" value="">
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
            const CLIENTE_SELECTOR = @json($clienteSelector);
            const deny = (msg) => window.uiAlert?.(msg || 'Usuário sem permissão.');
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const PROTOCOLOS = {
                urls: {
                    list:   @json(route($routePrefix.'.protocolos-exames.indexJson')),
                    clientes: @json(route($routePrefix.'.protocolos-exames.clientesJson')),
                    store:  @json(route($routePrefix.'.protocolos-exames.store')),
                    update: (id) => @json(route($routePrefix.'.protocolos-exames.update', ['protocolo' => '__ID__'])).replace('__ID__', id),
                    destroy:(id) => @json(route($routePrefix.'.protocolos-exames.destroy', ['protocolo' => '__ID__'])).replace('__ID__', id),
                    exames: @json(route($routePrefix.'.exames.indexJson')),
                },
                state: { protocolos: [], exames: [], clientes: [] },
                dom: {
                    modal: document.getElementById('modalProtocolos'),
                    list: document.getElementById('protocolosList'),
                    alert: document.getElementById('protocolosAlert'),
                    modalForm: document.getElementById('modalProtocoloForm'),
                    form: document.getElementById('formProtocolo'),
                    title: document.getElementById('protocoloFormTitle'),
                    formAlert: document.getElementById('protocoloFormAlert'),
                    id: document.getElementById('protocolo_id'),
                    titulo: document.getElementById('protocolo_titulo'),
                    descricao: document.getElementById('protocolo_descricao'),
                    ativo: document.getElementById('protocolo_ativo'),
                    escopoWrap: document.getElementById('protocoloEscopoWrap'),
                    escopoGenerico: document.getElementById('protocolo_escopo_generico'),
                    escopoCliente: document.getElementById('protocolo_escopo_cliente'),
                    escopoHint: document.getElementById('protocoloEscopoHint'),
                    clienteWrap: document.getElementById('protocoloClienteWrap'),
                    clienteSearch: document.getElementById('protocolo_cliente_search'),
                    clienteResults: document.getElementById('protocoloClienteResults'),
                    clienteId: document.getElementById('protocolo_cliente_id'),
                    examesList: document.getElementById('protocoloExamesList'),
                    examesCount: document.getElementById('protocoloExamesCount'),
                    btnReloadExames: document.getElementById('btnProtocoloReloadExames'),
                    btnNovoExame: document.getElementById('btnProtocoloNovoExame'),
                }
            };

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
            function getClienteInput(){
                return CLIENTE_SELECTOR ? document.querySelector(CLIENTE_SELECTOR) : null;
            }
            function setEscopoSelection(scope){
                if (PROTOCOLOS.dom.escopoGenerico) {
                    PROTOCOLOS.dom.escopoGenerico.checked = scope !== 'cliente';
                }
                if (PROTOCOLOS.dom.escopoCliente) {
                    PROTOCOLOS.dom.escopoCliente.checked = scope === 'cliente';
                }
                if (PROTOCOLOS.dom.clienteWrap) {
                    PROTOCOLOS.dom.clienteWrap.classList.toggle('hidden', scope !== 'cliente');
                }
                if (scope !== 'cliente' && PROTOCOLOS.dom.clienteId) {
                    PROTOCOLOS.dom.clienteId.value = '';
                }
                if (scope !== 'cliente' && PROTOCOLOS.dom.clienteSearch) {
                    PROTOCOLOS.dom.clienteSearch.value = '';
                }
                if (PROTOCOLOS.dom.clienteResults) {
                    PROTOCOLOS.dom.clienteResults.classList.add('hidden');
                }
            }
            function getCurrentClienteId(){
                const el = getClienteInput();
                const value = Number(el?.value || 0);
                return Number.isFinite(value) && value > 0 ? value : null;
            }
            function hasClienteContext(){
                return !!getCurrentClienteId();
            }
            function buildListUrl(){
                const clienteId = getCurrentClienteId();
                if (!clienteId) return PROTOCOLOS.urls.list;
                return `${PROTOCOLOS.urls.list}?cliente_id=${encodeURIComponent(clienteId)}`;
            }
            function normalizeSearchTerm(value){
                return String(value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .trim();
            }
            function renderClienteResults(selectedId = null){
                if (!PROTOCOLOS.dom.clienteResults || !PROTOCOLOS.dom.clienteId || !PROTOCOLOS.dom.clienteSearch) return;
                const query = normalizeSearchTerm(PROTOCOLOS.dom.clienteSearch?.value || '');
                const current = String(selectedId || PROTOCOLOS.dom.clienteId.value || '');
                const filtered = PROTOCOLOS.state.clientes
                    .filter((cliente) => {
                        if (!query) return true;
                        const haystack = normalizeSearchTerm(`${cliente.nome} ${cliente.documento || ''}`);
                        return haystack.includes(query);
                    })
                    .slice(0, 20);

                const currentCliente = PROTOCOLOS.state.clientes.find((cliente) => String(cliente.id) === current);
                if (selectedId && currentCliente) {
                    PROTOCOLOS.dom.clienteId.value = String(currentCliente.id);
                    PROTOCOLOS.dom.clienteSearch.value = currentCliente.nome;
                }

                if (!filtered.length || (current && !query)) {
                    PROTOCOLOS.dom.clienteResults.classList.add('hidden');
                    PROTOCOLOS.dom.clienteResults.innerHTML = '';
                    return;
                }

                PROTOCOLOS.dom.clienteResults.innerHTML = '';
                filtered.forEach((cliente) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'w-full px-3 py-2 text-left hover:bg-slate-50 border-b last:border-b-0 border-slate-100';
                    button.innerHTML = `
                        <div class="text-sm font-medium text-slate-800">${escapeHtml(cliente.nome)}</div>
                        <div class="text-xs text-slate-500">${escapeHtml(cliente.documento || 'Sem documento')}</div>
                    `;
                    button.addEventListener('click', () => {
                        PROTOCOLOS.dom.clienteId.value = String(cliente.id);
                        PROTOCOLOS.dom.clienteSearch.value = cliente.nome;
                        PROTOCOLOS.dom.clienteResults.classList.add('hidden');
                    });
                    PROTOCOLOS.dom.clienteResults.appendChild(button);
                });
                PROTOCOLOS.dom.clienteResults.classList.remove('hidden');
            }
            async function ensureClientesLoaded(selectedId = null){
                if (!PROTOCOLOS.state.clientes.length) {
                    const res = await fetch(PROTOCOLOS.urls.clientes, { headers:{'Accept':'application/json'} });
                    const json = await res.json();
                    PROTOCOLOS.state.clientes = json.data || [];
                }
                renderClienteResults(selectedId);
            }
            function updateEscopoVisibility(protocolo = null){
                const hasContext = hasClienteContext();
                const isClienteScoped = !!(protocolo?.cliente_id);
                const defaultClienteId = protocolo?.cliente_id || getCurrentClienteId() || null;

                if (PROTOCOLOS.dom.escopoGenerico) {
                    PROTOCOLOS.dom.escopoGenerico.disabled = false;
                }

                if (PROTOCOLOS.dom.escopoCliente) {
                    PROTOCOLOS.dom.escopoCliente.disabled = false;
                }

                setEscopoSelection(isClienteScoped ? 'cliente' : 'generico');
                if (PROTOCOLOS.dom.clienteId && defaultClienteId) {
                    ensureClientesLoaded(defaultClienteId).catch((e) => console.error(e));
                }

                if (PROTOCOLOS.dom.escopoHint) {
                    PROTOCOLOS.dom.escopoHint.textContent = hasContext
                        ? 'Você pode usar o cliente atual ou escolher outro cliente para um grupo exclusivo.'
                        : 'Ao marcar exclusivo, escolha o cliente diretamente neste modal.';
                }
            }
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
                    const res = await fetch(buildListUrl(), { headers:{'Accept':'application/json'} });
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
                    row.className = 'rounded-xl border border-slate-200 px-4 py-3';

                    const total = brl(p.total || 0);
                    const examesTxt = p.exames?.length ? `${p.exames.length} exame(s)` : 'Sem exames';
                    const scopeBadge = p.escopo === 'cliente'
                        ? '<span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">Exclusivo</span>'
                        : '<span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">Genérico</span>';

                    row.innerHTML = `
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-slate-800 break-words">${escapeHtml(p.titulo)}</div>
                                <div class="mt-1">${scopeBadge}</div>
                                <div class="mt-1 text-xs text-slate-500 break-words">${p.descricao ? escapeHtml(p.descricao) : '—'}</div>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 lg:flex-nowrap lg:justify-end lg:text-right">
                                <div class="min-w-[92px] text-xs font-medium text-slate-600">${examesTxt}</div>
                                <div class="min-w-[110px] text-sm font-semibold text-slate-800">${total}</div>
                                <div class="flex items-center gap-3 justify-end">
                                    <button type="button" class="text-sm ${PERMS.update ? 'text-blue-600 hover:underline' : 'text-slate-400 cursor-not-allowed'}" data-action="edit" ${PERMS.update ? '' : 'disabled title=\"Usuário sem permissão\"'}>Editar</button>
                                    <button type="button" class="text-sm ${PERMS.delete ? 'text-red-600 hover:underline' : 'text-slate-400 cursor-not-allowed'}" data-action="del" ${PERMS.delete ? '' : 'disabled title=\"Usuário sem permissão\"'}>Excluir</button>
                                </div>
                            </div>
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
                    titulo: PROTOCOLOS.dom.titulo.value.trim(),
                    descricao: PROTOCOLOS.dom.descricao.value.trim() || null,
                    ativo: PROTOCOLOS.dom.ativo.checked,
                    exames: Array.from(PROTOCOLOS.dom.examesList.querySelectorAll('input[type="checkbox"]:checked'))
                        .map(cb => Number(cb.value)),
                };
                const clienteId = getCurrentClienteId();

                if (PROTOCOLOS.dom.escopoCliente?.checked) {
                    const selectedClienteId = Number(PROTOCOLOS.dom.clienteId?.value || clienteId || 0);
                    if (!selectedClienteId) return alertBox('err','Selecione o cliente do grupo exclusivo.');
                    payload.cliente_id = selectedClienteId;
                } else {
                    payload.cliente_id = null;
                }

                if (!payload.titulo) return alertBox('err','Informe o título do grupo.');

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

                    const isCliente = Number(payload.cliente_id || 0) > 0;
                    const isEdicao = !!id;
                    const mensagem = isEdicao
                        ? (isCliente ? 'Grupo de exames exclusivo atualizado com sucesso.' : 'Grupo de exames genérico atualizado com sucesso.')
                        : (isCliente ? 'Grupo de exames exclusivo cadastrado com sucesso.' : 'Grupo de exames genérico cadastrado com sucesso.');

                    if (typeof window.uiAlert === 'function') {
                        await window.uiAlert(mensagem, {
                            title: 'Sucesso',
                            icon: 'success',
                            confirmText: 'Fechar',
                        });
                    } else {
                        alertBox('ok', mensagem);
                    }

                    await loadProtocolos();
                    closeProtocoloForm();
                    window.dispatchEvent(new CustomEvent('protocolos:updated'));
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

            window.openProtocolosModal = async function(){
                ensureModalOverSidebar(PROTOCOLOS.dom.modal, 240);
                alertHide();
                PROTOCOLOS.dom.modal?.classList.remove('hidden');
                await loadProtocolos();
            };
            window.closeProtocolosModal = () => {
                alertHide();
                PROTOCOLOS.dom.modal?.classList.add('hidden');
            };

            window.openProtocoloForm = async function(protocolo){
                if (protocolo && !PERMS.update) return deny('Usuário sem permissão para editar.');
                if (!protocolo && !PERMS.create) return deny('Usuário sem permissão para criar.');
                await loadExames();
                ensureModalOverSidebar(PROTOCOLOS.dom.modalForm, 250);
                alertHide();
                PROTOCOLOS.dom.modalForm?.classList.remove('hidden');
                PROTOCOLOS.dom.title.textContent = protocolo ? 'Editar Grupo' : 'Novo Grupo';
                PROTOCOLOS.dom.id.value = protocolo?.id || '';
                PROTOCOLOS.dom.titulo.value = protocolo?.titulo || '';
                PROTOCOLOS.dom.descricao.value = protocolo?.descricao || '';
                PROTOCOLOS.dom.ativo.checked = protocolo ? !!protocolo.ativo : true;
                updateEscopoVisibility(protocolo || null);
                if (!protocolo) {
                    setEscopoSelection('generico');
                    if (PROTOCOLOS.dom.clienteId) {
                        PROTOCOLOS.dom.clienteId.value = '';
                    }
                    if (PROTOCOLOS.dom.clienteSearch) {
                        PROTOCOLOS.dom.clienteSearch.value = '';
                    }
                }
                const selected = (protocolo?.exames || []).map(e => e.id);
                renderExamesChecklist(selected);
            };
            window.closeProtocoloForm = () => {
                alertHide();
                PROTOCOLOS.dom.modalForm?.classList.add('hidden');
            };

            PROTOCOLOS.dom.form?.addEventListener('submit', saveProtocolo);
            PROTOCOLOS.dom.btnReloadExames?.addEventListener('click', async () => {
                const selected = selectedExameIdsFromChecklist();
                await loadExames(true);
                renderExamesChecklist(selected);
                alertBox('ok', 'Lista de exames atualizada.');
            });
            PROTOCOLOS.dom.btnNovoExame?.addEventListener('click', () => {
                if (!PERMS.create) return deny('Usuário sem permissão para criar.');
                if (typeof window.openExameForm !== 'function') {
                    return alertBox('err', 'Modal de exames não está disponível.');
                }
                window.openExameForm(null);
            });
            PROTOCOLOS.dom.escopoGenerico?.addEventListener('change', () => {
                setEscopoSelection('generico');
            });
            PROTOCOLOS.dom.escopoCliente?.addEventListener('change', async () => {
                await ensureClientesLoaded(getCurrentClienteId());
                setEscopoSelection('cliente');
            });
            PROTOCOLOS.dom.clienteSearch?.addEventListener('input', () => {
                if (!PROTOCOLOS.dom.clienteSearch.value.trim()) {
                    PROTOCOLOS.dom.clienteId.value = '';
                }
                renderClienteResults();
            });
            PROTOCOLOS.dom.clienteSearch?.addEventListener('focus', () => {
                if (PROTOCOLOS.dom.escopoCliente?.checked) {
                    renderClienteResults();
                }
            });
            getClienteInput()?.addEventListener('change', () => {
                updateEscopoVisibility();
            });
            document.addEventListener('click', (e) => {
                if (!PROTOCOLOS.dom.clienteWrap?.contains(e.target)) {
                    PROTOCOLOS.dom.clienteResults?.classList.add('hidden');
                }
            });
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
        })();
    </script>
@endpush
