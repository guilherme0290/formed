@php($routePrefix = $routePrefix ?? 'comercial')
@php($canCreate = $canCreate ?? false)
@php($canUpdate = $canUpdate ?? false)
@php($canDelete = $canDelete ?? false)

<div id="modalProtocolos" data-overlay-root="true" class="fixed inset-0 z-[200] hidden bg-black/50 overflow-y-auto">
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
                            class="inline-flex items-center justify-center gap-2 rounded-2xl
                                   {{ $canCreate ? 'bg-slate-800 hover:bg-slate-900 active:bg-black text-white' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} px-5 py-2.5 text-sm font-semibold shadow-sm
                                   ring-1 ring-slate-700/30 transition"
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
<div id="modalProtocoloForm" data-overlay-root="true" class="fixed inset-0 z-[210] hidden bg-black/50 overflow-y-auto">
    <div class="min-h-full w-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 id="protocoloFormTitle" class="text-lg font-semibold text-slate-800">Novo Grupo</h3>
                <button type="button" onclick="closeProtocoloForm()"
                        class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <form id="formProtocolo" class="p-6 space-y-4">
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

                <div>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-600">Exames do grupo</label>
                        <span class="text-xs text-slate-500" id="protocoloExamesCount">0 selecionados</span>
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
                            class="rounded-xl bg-slate-800 hover:bg-slate-900 text-white px-5 py-2 text-sm font-semibold">
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
                    exames: @json(route($routePrefix.'.exames.indexJson')),
                },
                state: { protocolos: [], exames: [] },
                dom: {
                    modal: document.getElementById('modalProtocolos'),
                    list: document.getElementById('protocolosList'),
                    alert: document.getElementById('protocolosAlert'),
                    modalForm: document.getElementById('modalProtocoloForm'),
                    form: document.getElementById('formProtocolo'),
                    title: document.getElementById('protocoloFormTitle'),
                    id: document.getElementById('protocolo_id'),
                    titulo: document.getElementById('protocolo_titulo'),
                    descricao: document.getElementById('protocolo_descricao'),
                    ativo: document.getElementById('protocolo_ativo'),
                    examesList: document.getElementById('protocoloExamesList'),
                    examesCount: document.getElementById('protocoloExamesCount'),
                }
            };

            function brl(n){ return Number(n||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
            function escapeHtml(str){
                return String(str||'')
                    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
                    .replaceAll('"','&quot;').replaceAll("'",'&#039;');
            }

            function alertBox(type,msg){
                if(!PROTOCOLOS.dom.alert) return;
                PROTOCOLOS.dom.alert.classList.remove('hidden');
                PROTOCOLOS.dom.alert.className = 'rounded-xl border px-4 py-3 text-sm';
                if(type==='ok'){
                    PROTOCOLOS.dom.alert.classList.add('bg-emerald-50','border-emerald-200','text-emerald-800');
                } else {
                    PROTOCOLOS.dom.alert.classList.add('bg-red-50','border-red-200','text-red-800');
                }
                PROTOCOLOS.dom.alert.textContent = msg;
            }
            function alertHide(){
                if(!PROTOCOLOS.dom.alert) return;
                PROTOCOLOS.dom.alert.classList.add('hidden');
            }

            async function loadExames(){
                if (PROTOCOLOS.state.exames.length) return;
                const res = await fetch(PROTOCOLOS.urls.exames, { headers:{'Accept':'application/json'} });
                const json = await res.json();
                PROTOCOLOS.state.exames = json.data || [];
            }

            async function loadProtocolos(){
                try{
                    alertHide();
                    const res = await fetch(PROTOCOLOS.urls.list, { headers:{'Accept':'application/json'} });
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
                            <div class="text-xs text-slate-500">${p.descricao ? escapeHtml(p.descricao) : '—'}</div>
                        </div>
                        <div class="col-span-3 text-xs text-slate-600">${examesTxt}</div>
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
                    titulo: PROTOCOLOS.dom.titulo.value.trim(),
                    descricao: PROTOCOLOS.dom.descricao.value.trim() || null,
                    ativo: PROTOCOLOS.dom.ativo.checked,
                    exames: Array.from(PROTOCOLOS.dom.examesList.querySelectorAll('input[type="checkbox"]:checked'))
                        .map(cb => Number(cb.value)),
                };

                if (!payload.titulo) return alertBox('err','Informe o título do grupo.');

                const url = id ? PROTOCOLOS.urls.update(id) : PROTOCOLOS.urls.store;
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
                    await loadProtocolos();
                    closeProtocoloForm();
                    window.dispatchEvent(new CustomEvent('protocolos:updated'));
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao salvar grupo.');
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
                PROTOCOLOS.dom.modal?.classList.remove('hidden');
                await loadProtocolos();
            };
            window.closeProtocolosModal = () => PROTOCOLOS.dom.modal?.classList.add('hidden');

            window.openProtocoloForm = async function(protocolo){
                if (protocolo && !PERMS.update) return deny('Usuário sem permissão para editar.');
                if (!protocolo && !PERMS.create) return deny('Usuário sem permissão para criar.');
                await loadExames();
                PROTOCOLOS.dom.modalForm?.classList.remove('hidden');
                PROTOCOLOS.dom.title.textContent = protocolo ? 'Editar Grupo' : 'Novo Grupo';
                PROTOCOLOS.dom.id.value = protocolo?.id || '';
                PROTOCOLOS.dom.titulo.value = protocolo?.titulo || '';
                PROTOCOLOS.dom.descricao.value = protocolo?.descricao || '';
                PROTOCOLOS.dom.ativo.checked = protocolo ? !!protocolo.ativo : true;
                const selected = (protocolo?.exames || []).map(e => e.id);
                renderExamesChecklist(selected);
            };
            window.closeProtocoloForm = () => PROTOCOLOS.dom.modalForm?.classList.add('hidden');

            PROTOCOLOS.dom.form?.addEventListener('submit', saveProtocolo);
        })();
    </script>
@endpush
