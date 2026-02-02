@php($routePrefix = $routePrefix ?? 'comercial')

<div id="modalExamesCrud" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
    <div class="min-h-full w-full flex items-center justify-center p-4 md:p-6">
        <div class="bg-white w-full max-w-5xl rounded-2xl shadow-xl overflow-hidden max-h-[85vh] flex flex-col">

            <div class="px-6 py-4 bg-blue-700 text-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Exames (Tabela de Preço)</h2>
                    <p class="text-xs opacity-90">Gerenciamento de exames.</p>
                </div>
                <button type="button"
                        onclick="closeExamesModal()"
                        class="h-9 w-9 rounded-xl hover:bg-white/10 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                <div id="examesAlert" class="hidden"></div>

                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-slate-800">Lista de exames</div>

                    <button type="button"
                            onclick="openExameForm(null)"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl
                                   bg-blue-600 hover:bg-blue-700 active:bg-blue-800
                                   text-white px-5 py-2.5 text-sm font-semibold shadow-sm
                                   ring-1 ring-blue-600/20 hover:ring-blue-700/30 transition">
                        <span class="text-base leading-none">＋</span>
                        <span>Novo Exame</span>
                    </button>
                </div>

                <div id="examesList" class="space-y-2"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal interno: Form criar/editar --}}
<div id="modalExameForm" class="fixed inset-0 z-[100] hidden bg-black/50 overflow-y-auto">
    <div class="min-h-full w-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 id="exameFormTitle" class="text-lg font-semibold text-slate-800">Novo Exame</h3>
                <button type="button" onclick="closeExameForm()"
                        class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <form id="formExame" class="p-6 space-y-4">
                <input type="hidden" id="exame_id" value="">
                <x-toggle-ativo
                    id="exame_ativo"
                    name="ativo"
                    :checked="true"
                    on-label="Ativo"
                    off-label="Inativo"
                    text-class="text-sm font-medium text-slate-700"
                />

                <div>
                    <label class="text-xs font-semibold text-slate-600">Título *</label>
                    <input id="exame_titulo" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           placeholder="Ex: Audiometria">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Descrição</label>
                    <input id="exame_descricao" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           placeholder="Opcional">
                </div>



                <div>
                    <label class="text-xs font-semibold text-slate-600">Preço *</label>
                    <input id="exame_preco_view" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           value="R$ 0,00">
                    <input id="exame_preco" type="hidden" value="0.00">
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="closeExameForm()"
                            class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold">
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

            const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const EXAMES = {
                urls: {
                list:   @json(route($routePrefix.'.exames.indexJson')),
                store:  @json(route($routePrefix.'.exames.store')),
                update: (id) => @json(route($routePrefix.'.exames.update', ['exame' => '__ID__'])).replace('__ID__', id),
                destroy:(id) => @json(route($routePrefix.'.exames.destroy', ['exame' => '__ID__'])).replace('__ID__', id),
                },
                state: { exames: [] },
                dom: {
                    modal: document.getElementById('modalExamesCrud'),
                    list: document.getElementById('examesList'),
                    alert: document.getElementById('examesAlert'),

                    modalForm: document.getElementById('modalExameForm'),
                    form: document.getElementById('formExame'),
                    title: document.getElementById('exameFormTitle'),

                    id: document.getElementById('exame_id'),
                    titulo: document.getElementById('exame_titulo'),
                    descricao: document.getElementById('exame_descricao'),
                    // ordem: document.getElementById('exame_ordem'),
                    ativo: document.getElementById('exame_ativo'),
                    precoView: document.getElementById('exame_preco_view'),
                    precoHidden: document.getElementById('exame_preco'),
                }
            };

            function brl(n){ return Number(n||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
            function escapeHtml(str){
                return String(str||'')
                    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
                    .replaceAll('"','&quot;').replaceAll("'",'&#039;');
            }

            function alertBox(type,msg){
                if(!EXAMES.dom.alert) return;
                EXAMES.dom.alert.classList.remove('hidden');
                EXAMES.dom.alert.className = 'rounded-xl border px-4 py-3 text-sm';
                if(type==='ok'){
                    EXAMES.dom.alert.classList.add('bg-emerald-50','border-emerald-200','text-emerald-800');
                } else {
                    EXAMES.dom.alert.classList.add('bg-red-50','border-red-200','text-red-800');
                }
                EXAMES.dom.alert.textContent = msg;
            }
            function alertHide(){
                if(!EXAMES.dom.alert) return;
                EXAMES.dom.alert.classList.add('hidden');
            }

            // máscara por centavos (reaproveita seu padrão)
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

            async function loadExames(){
                try{
                    alertHide();
                    const res = await fetch(EXAMES.urls.list, { headers:{'Accept':'application/json'} });
                    const json = await res.json();
                    EXAMES.state.exames = json.data || [];
                    renderExames();
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao carregar exames.');
                }
            }

            function renderExames(){
                const wrap = EXAMES.dom.list;
                if(!wrap) return;

                wrap.innerHTML = '';
                if(!EXAMES.state.exames.length){
                    wrap.innerHTML = `<div class="text-sm text-slate-500 py-3">Nenhum exame cadastrado.</div>`;
                    return;
                }

                EXAMES.state.exames.forEach(x=>{
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 px-3 py-2';

                    row.innerHTML = `
                                <div class="col-span-4">
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

                                <div class="col-span-2 text-right">
                                  <button type="button" class="text-blue-600 hover:underline text-sm" data-act="edit">Editar</button>
                                  <button type="button" class="text-red-600 hover:underline text-sm ml-2" data-act="del">Excluir</button>
                                </div>
                              `;

                    row.querySelector('[data-act="edit"]').addEventListener('click', ()=> openExameForm(x));
                    row.querySelector('[data-act="del"]').addEventListener('click', ()=> deleteExame(x.id));

                    wrap.appendChild(row);
                });
            }

            function openExameForm(exame){
                if(!EXAMES.dom.modalForm) return;

                EXAMES.dom.id.value = exame?.id ?? '';
                EXAMES.dom.titulo.value = exame?.titulo ?? '';
                EXAMES.dom.descricao.value = exame?.descricao ?? '';
                // EXAMES.dom.ordem.value = exame?.ordem ?? 0;
                EXAMES.dom.ativo.checked = exame ? !!exame.ativo : true;

                EXAMES.dom.precoHidden.value = exame ? Number(exame.preco || 0).toFixed(2) : '0.00';
                attachMoneyMask(EXAMES.dom.precoView, EXAMES.dom.precoHidden);
                EXAMES.dom.precoView.value = brl(Number(EXAMES.dom.precoHidden.value||0));

                EXAMES.dom.title.textContent = exame ? 'Editar Exame' : 'Novo Exame';
                EXAMES.dom.modalForm.classList.remove('hidden');
            }

            function closeExameForm(){
                EXAMES.dom.modalForm?.classList.add('hidden');
            }

            async function saveExame(e){
                e.preventDefault();

                const id = EXAMES.dom.id.value;
                const payload = {
                    titulo: (EXAMES.dom.titulo.value||'').trim(),
                    descricao: (EXAMES.dom.descricao.value||'').trim() || null,
                    preco: Number(EXAMES.dom.precoHidden.value || 0),
                    // ordem: Number(EXAMES.dom.ordem.value || 0),
                    ativo: EXAMES.dom.ativo.checked ? 1 : 0,
                };

                if(!payload.titulo) return alertBox('err','Informe o título.');
                if(payload.preco < 0) return alertBox('err','Preço inválido.');

                try{
                    alertHide();
                    const isEdit = !!id;
                    const url = isEdit ? EXAMES.urls.update(id) : EXAMES.urls.store;

                    const res = await fetch(url, {
                        method: isEdit ? 'PUT' : 'POST',
                        headers: {
                            'Accept':'application/json',
                            'Content-Type':'application/json',
                            'X-CSRF-TOKEN': CSRF,
                        },
                        body: JSON.stringify(payload)
                    });

                    const json = await res.json().catch(()=>({}));

                    if(!res.ok){
                        if(json?.errors){
                            const first = Object.values(json.errors)[0]?.[0] || 'Erro ao salvar.';
                            return alertBox('err', first);
                        }
                        return alertBox('err', json?.message || 'Erro ao salvar exame.');
                    }

                    closeExameForm();
                    await loadExames();
                    alertBox('ok', isEdit ? 'Exame atualizado.' : 'Exame criado.');
                } catch(err){
                    console.error(err);
                    alertBox('err','Falha ao salvar exame.');
                }
            }

            async function deleteExame(id){
                const ok = await window.uiConfirm('Deseja remover este exame?');
                if (!ok) return;

                try{
                    const res = await fetch(EXAMES.urls.destroy(id), {
                        method:'DELETE',
                        headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF }
                    });

                    if(!res.ok) return alertBox('err','Erro ao excluir exame.');
                    await loadExames();
                    alertBox('ok','Exame removido.');
                } catch(err){
                    console.error(err);
                    alertBox('err','Falha ao excluir exame.');
                }
            }

            // Expor funções p/ onclick
            window.openExamesModal = async function(){
                EXAMES.dom.modal?.classList.remove('hidden');
                await loadExames();
            }
            window.closeExamesModal = function(){
                EXAMES.dom.modal?.classList.add('hidden');
            }
            window.openExameForm = openExameForm;
            window.closeExameForm = closeExameForm;

            EXAMES.dom.form?.addEventListener('submit', saveExame);

            // fechar clicando fora e ESC
            document.addEventListener('click', (e)=>{
                if(EXAMES.dom.modal && !EXAMES.dom.modal.classList.contains('hidden') && e.target === EXAMES.dom.modal) closeExamesModal();
                if(EXAMES.dom.modalForm && !EXAMES.dom.modalForm.classList.contains('hidden') && e.target === EXAMES.dom.modalForm) closeExameForm();
            });
            document.addEventListener('keydown', (e)=>{
                if(e.key==='Escape'){
                    if(EXAMES.dom.modal && !EXAMES.dom.modal.classList.contains('hidden')) closeExamesModal();
                    if(EXAMES.dom.modalForm && !EXAMES.dom.modalForm.classList.contains('hidden')) closeExameForm();
                }
            });

        })();
    </script>

@endpush
