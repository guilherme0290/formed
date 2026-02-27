@php($routePrefix = $routePrefix ?? 'comercial')
@php($canCreate = $canCreate ?? false)
@php($canUpdate = $canUpdate ?? false)
@php($canDelete = $canDelete ?? false)

<div id="modalMedicoesCrud" data-overlay-root="true" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
    <div class="min-h-full w-full flex items-center justify-center p-4 md:p-6">
        <div class="bg-white w-full max-w-5xl rounded-2xl shadow-xl overflow-hidden max-h-[85vh] flex flex-col">

            <div class="px-6 py-4 bg-amber-700 text-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Medições LTCAT/LTIP (Tabela de Preço)</h2>
                    <p class="text-xs opacity-90">Gerenciamento de itens de medições.</p>
                </div>
                <button type="button"
                        onclick="closeMedicoesCrudModal()"
                        class="h-9 w-9 rounded-xl hover:bg-white/10 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                <div id="medicoesAlert" class="hidden"></div>

                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-slate-800">Lista de medições</div>

                    <button type="button"
                            @if($canCreate) onclick="openMedicaoForm(null)" @endif
                            class="inline-flex items-center justify-center gap-2 rounded-2xl
                                   {{ $canCreate ? 'bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} px-5 py-2.5 text-sm font-semibold shadow-sm
                                   ring-1 ring-amber-600/20 hover:ring-amber-700/30 transition"
                            @if(!$canCreate) disabled title="Usuário sem permissão" @endif>
                        <span class="text-base leading-none">＋</span>
                        <span>Nova Medição</span>
                    </button>
                </div>

                <div id="medicoesList" class="space-y-2"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal interno: Form criar/editar --}}
<div id="modalMedicaoForm" data-overlay-root="true" class="fixed inset-0 z-[100] hidden bg-black/50 overflow-y-auto">
    <div class="min-h-full w-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 id="medicaoFormTitle" class="text-lg font-semibold text-slate-800">Nova Medição</h3>
                <button type="button" onclick="closeMedicaoForm()"
                        class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <form id="formMedicao" class="p-6 space-y-4">
                <input type="hidden" id="medicao_id" value="">
                <x-toggle-ativo
                    id="medicao_ativo"
                    name="ativo"
                    :checked="true"
                    on-label="Ativo"
                    off-label="Inativo"
                    text-class="text-sm font-medium text-slate-700"
                />

                <div>
                    <label class="text-xs font-semibold text-slate-600">Título *</label>
                    <input id="medicao_titulo" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           placeholder="Ex: Avaliação de poeira total">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Descrição</label>
                    <input id="medicao_descricao" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           placeholder="Opcional">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Preço *</label>
                    <input id="medicao_preco_view" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           value="R$ 0,00">
                    <input id="medicao_preco" type="hidden" value="0.00">
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="closeMedicaoForm()"
                            class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="rounded-xl bg-amber-600 hover:bg-amber-700 text-white px-5 py-2 text-sm font-semibold">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function(){
            const PERMS = {
                create: @json((bool) $canCreate),
                update: @json((bool) $canUpdate),
                delete: @json((bool) $canDelete),
            };
            const deny = (msg) => window.uiAlert?.(msg || 'Usuário sem permissão.');
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const MEDICOES = {
                urls: {
                    list:   @json(route($routePrefix.'.medicoes.indexJson')),
                    store:  @json(route($routePrefix.'.medicoes.store')),
                    update: (id) => @json(route($routePrefix.'.medicoes.update', ['medicao' => '__ID__'])).replace('__ID__', id),
                    destroy:(id) => @json(route($routePrefix.'.medicoes.destroy', ['medicao' => '__ID__'])).replace('__ID__', id),
                },
                state: { medicoes: [] },
                dom: {
                    modal: document.getElementById('modalMedicoesCrud'),
                    list: document.getElementById('medicoesList'),
                    alert: document.getElementById('medicoesAlert'),

                    modalForm: document.getElementById('modalMedicaoForm'),
                    form: document.getElementById('formMedicao'),
                    title: document.getElementById('medicaoFormTitle'),

                    id: document.getElementById('medicao_id'),
                    titulo: document.getElementById('medicao_titulo'),
                    descricao: document.getElementById('medicao_descricao'),
                    ativo: document.getElementById('medicao_ativo'),
                    precoView: document.getElementById('medicao_preco_view'),
                    precoHidden: document.getElementById('medicao_preco'),
                }
            };

            function brl(n){ return Number(n||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
            function escapeHtml(str){
                return String(str||'')
                    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
                    .replaceAll('"','&quot;').replaceAll("'",'&#039;');
            }

            function alertBox(type,msg){
                if(!MEDICOES.dom.alert) return;
                MEDICOES.dom.alert.classList.remove('hidden');
                MEDICOES.dom.alert.className = 'rounded-xl border px-4 py-3 text-sm';
                if(type==='ok'){
                    MEDICOES.dom.alert.classList.add('bg-emerald-50','border-emerald-200','text-emerald-800');
                } else {
                    MEDICOES.dom.alert.classList.add('bg-red-50','border-red-200','text-red-800');
                }
                MEDICOES.dom.alert.textContent = msg;
            }
            function alertHide(){
                if(!MEDICOES.dom.alert) return;
                MEDICOES.dom.alert.classList.add('hidden');
            }

            function onlyDigits(str){ return String(str||'').replace(/\D+/g,''); }
            function centsDigitsToNumber(d){ return (parseInt(d||'0',10)/100); }

            let maskReady = false;
            function attachMoneyMask(viewEl, hiddenEl){
                if(!viewEl || !hiddenEl) return;
                if(maskReady) return;
                maskReady = true;

                viewEl.dataset.digits = onlyDigits(Math.round(Number(hiddenEl.value||0)*100));
                viewEl.value = brl(Number(hiddenEl.value||0));

                viewEl.addEventListener('keydown', (e)=>{
                    const nav = ['Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End','Delete'];
                    if(e.ctrlKey||e.metaKey) return;

                    if(e.key==='Backspace'){
                        e.preventDefault();
                        const d=viewEl.dataset.digits||'';
                        const nd=d.slice(0,-1);
                        viewEl.dataset.digits=nd;

                        const num=centsDigitsToNumber(nd);
                        hiddenEl.value=num.toFixed(2);
                        viewEl.value=brl(num);
                        return;
                    }
                    if(nav.includes(e.key)) return;
                    if(!/^\d$/.test(e.key)) e.preventDefault();
                });

                viewEl.addEventListener('input', ()=>{
                    const digits=onlyDigits(viewEl.value);
                    viewEl.dataset.digits=digits;

                    const num=centsDigitsToNumber(digits);
                    hiddenEl.value=num.toFixed(2);
                    viewEl.value=brl(num);
                });
            }

            async function loadMedicoes(){
                try{
                    alertHide();
                    const res = await fetch(MEDICOES.urls.list, { headers:{'Accept':'application/json'} });
                    const json = await res.json();
                    MEDICOES.state.medicoes = json.data || [];
                    renderMedicoes();
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao carregar medições.');
                }
            }

            function renderMedicoes(){
                const wrap = MEDICOES.dom.list;
                if(!wrap) return;

                wrap.innerHTML = '';
                if(!MEDICOES.state.medicoes.length){
                    wrap.innerHTML = `<div class="text-sm text-slate-500 py-3">Nenhuma medição cadastrada.</div>`;
                    return;
                }

                MEDICOES.state.medicoes.forEach(x=>{
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 px-3 py-2';

                    row.innerHTML = `
                        <div class="col-span-5">
                          <div class="font-semibold text-slate-800">${escapeHtml(x.titulo)}</div>
                          <div class="text-xs text-slate-500">${x.descricao ? escapeHtml(x.descricao) : '—'}</div>
                        </div>

                        <div class="col-span-2 text-right font-semibold text-slate-800">${brl(x.preco)}</div>

                        <div class="col-span-2 text-center">
                          ${x.ativo
                                ? `<span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">Ativo</span>`
                                : `<span class="text-xs px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100">Inativo</span>`
                            }
                        </div>

                        <div class="col-span-3 text-right">
                          <button type="button" class="text-sm ${PERMS.update ? 'text-blue-600 hover:underline' : 'text-slate-400 cursor-not-allowed'}" data-act="edit" ${PERMS.update ? '' : 'disabled title=\"Usuário sem permissão\"'}>Editar</button>
                          <button type="button" class="text-sm ml-2 ${PERMS.delete ? 'text-red-600 hover:underline' : 'text-slate-400 cursor-not-allowed'}" data-act="del" ${PERMS.delete ? '' : 'disabled title=\"Usuário sem permissão\"'}>Excluir</button>
                        </div>
                    `;

                    row.querySelector('[data-act="edit"]').addEventListener('click', ()=> openMedicaoForm(x));
                    row.querySelector('[data-act="del"]').addEventListener('click', ()=> deleteMedicao(x.id));

                    wrap.appendChild(row);
                });
            }

            async function deleteMedicao(id){
                if (!PERMS.delete) return deny('Usuário sem permissão para excluir.');
                const ok = await window.uiConfirm('Remover esta medição?');
                if (!ok) return;
                try{
                    await fetch(MEDICOES.urls.destroy(id), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
                    });
                    alertBox('ok','Medição removida.');
                    loadMedicoes();
                } catch(e){
                    console.error(e);
                    alertBox('err','Erro ao remover.');
                }
            }

            window.openMedicoesCrudModal = function(){
                if(!MEDICOES.dom.modal) return;
                MEDICOES.dom.modal.classList.remove('hidden');
                loadMedicoes();
            }

            window.closeMedicoesCrudModal = function(){
                MEDICOES.dom.modal?.classList.add('hidden');
            }

            function resetMedicaoForm(){
                MEDICOES.dom.form?.reset();
                if(MEDICOES.dom.id) MEDICOES.dom.id.value = '';
                if(MEDICOES.dom.precoHidden) MEDICOES.dom.precoHidden.value = '0.00';
                if(MEDICOES.dom.precoView) MEDICOES.dom.precoView.value = brl(0);
                if(MEDICOES.dom.ativo) MEDICOES.dom.ativo.checked = true;
            }

            window.openMedicaoForm = function(medicao){
                if(!MEDICOES.dom.modalForm) return;
                if (medicao && !PERMS.update) return deny('Usuário sem permissão para editar.');
                if (!medicao && !PERMS.create) return deny('Usuário sem permissão para criar.');

                resetMedicaoForm();

                if(medicao){
                    MEDICOES.dom.title.textContent = 'Editar Medição';
                    MEDICOES.dom.id.value = medicao.id;
                    MEDICOES.dom.titulo.value = medicao.titulo || '';
                    MEDICOES.dom.descricao.value = medicao.descricao || '';
                    MEDICOES.dom.precoHidden.value = Number(medicao.preco || 0).toFixed(2);
                    MEDICOES.dom.precoView.value = brl(medicao.preco || 0);
                    MEDICOES.dom.precoView.dataset.digits = onlyDigits(Math.round(Number(medicao.preco || 0) * 100));
                    MEDICOES.dom.ativo.checked = !!medicao.ativo;
                } else {
                    MEDICOES.dom.title.textContent = 'Nova Medição';
                }

                attachMoneyMask(MEDICOES.dom.precoView, MEDICOES.dom.precoHidden);
                MEDICOES.dom.modalForm.classList.remove('hidden');
            }

            window.closeMedicaoForm = function(){
                MEDICOES.dom.modalForm?.classList.add('hidden');
            }

            MEDICOES.dom.form?.addEventListener('submit', async (e)=>{
                e.preventDefault();
                const id = MEDICOES.dom.id?.value;
                if (id && !PERMS.update) return deny('Usuário sem permissão para editar.');
                if (!id && !PERMS.create) return deny('Usuário sem permissão para criar.');

                const payload = {
                    titulo: MEDICOES.dom.titulo?.value || '',
                    descricao: MEDICOES.dom.descricao?.value || '',
                    preco: MEDICOES.dom.precoHidden?.value || 0,
                    ativo: MEDICOES.dom.ativo?.checked ? 1 : 0,
                };

                const url = id ? MEDICOES.urls.update(id) : MEDICOES.urls.store;
                const method = id ? 'PUT' : 'POST';

                try{
                    const res = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                        },
                        body: JSON.stringify(payload),
                    });

                    if(!res.ok){
                        throw new Error('request_failed');
                    }

                    alertBox('ok', id ? 'Medição atualizada.' : 'Medição criada.');
                    closeMedicaoForm();
                    loadMedicoes();
                } catch(e){
                    console.error(e);
                    alertBox('err','Erro ao salvar.');
                }
            });
        })();
    </script>
@endpush
