@php($routePrefix = $routePrefix ?? 'comercial')
@php($canCreate = $canCreate ?? false)
@php($canUpdate = $canUpdate ?? false)
@php($canDelete = $canDelete ?? false)

{{-- Modal eSocial --}}
<div id="modalEsocial" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-4xl rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[85vh]">

            {{-- Header --}}
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Faixas de eSocial</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Defina faixas por quantidade de colaboradores.</p>
                </div>
                <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                        onclick="closeEsocialModal()">✕</button>
            </div>

            {{-- Body --}}
            <div class="p-6 overflow-y-auto">
                {{-- alert --}}
                <div id="esocialAlert" class="hidden mb-4 rounded-xl border px-4 py-3 text-sm"></div>

                {{-- grid header --}}
                <div class="grid grid-cols-12 gap-2 text-xs font-semibold text-slate-500 mb-2">
                    <div class="col-span-2">Faixa</div>
                    <div class="col-span-5">Descrição</div>
                    <div class="col-span-1 text-right">Preço</div>
                    <div class="col-span-2 text-center">Status</div>
                    <div class="col-span-2 text-center">Ações</div>
                </div>

                {{-- rows --}}
                <div id="esocialFaixas" class="space-y-2"></div>

                <button id="btnNovaFaixa" type="button"
                        class="mt-5 w-full rounded-xl {{ $canCreate ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} px-4 py-2 text-sm font-semibold"
                        @if(!$canCreate) disabled title="Usuário sem permissão" @endif>
                    + Nova Faixa
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal interno: criar/editar faixa --}}
<div id="modalEsocialForm" class="fixed inset-0 z-[100] hidden bg-black/50 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-5xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 id="esocialFormTitle" class="text-base font-semibold">Nova Faixa</h3>
                <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                        onclick="closeEsocialForm()">✕</button>
            </div>

            <form id="formEsocialFaixa" class="p-6 space-y-4">
                <input type="hidden" id="esocial_faixa_id" value="">
                <x-toggle-ativo
                    id="esocial_ativo"
                    name="ativo"
                    label-id="esocial_ativo_label"
                    :checked="true"
                    on-label="Ativo"
                    off-label="Inativo"
                    text-class="text-sm font-medium text-slate-700"
                />

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Início *</label>
                        <input id="esocial_inicio" type="number" min="1"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Fim *</label>
                        <input id="esocial_fim" type="number" min="1"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">
                        <p class="text-[11px] text-slate-500 mt-1">Use 999999 para “acima de”.</p>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Descrição</label>
                    <input id="esocial_descricao" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           placeholder="Ex: 01 até 10 colaboradores">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Preço *</label>
                    <input id="esocial_preco_view" type="text" inputmode="decimal" autocomplete="off"
                           placeholder="R$ 0,00"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">
                    <input id="esocial_preco" type="hidden" value="0.00">
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button"
                            class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                            onclick="closeEsocialForm()">
                        Cancelar
                    </button>

                    <button id="btnSalvarFaixa" type="submit"
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
        (function () {
            const PERMS = {
                create: @json((bool) $canCreate),
                update: @json((bool) $canUpdate),
                delete: @json((bool) $canDelete),
            };
            const deny = (msg) => window.uiAlert?.(msg || 'Usuário sem permissão.');
            const modal = document.getElementById('modalEsocial');
            const modalForm = document.getElementById('modalEsocialForm');

            if (!modal) return;

            const ESOCIAL_STORE_URL = @json(route($routePrefix.'.esocial.faixas.store'));
            const ESOCIAL_UPDATE_URL = @json(route($routePrefix.'.esocial.faixas.update', ['faixa' => '__ID__']));
            const ESOCIAL_DESTROY_URL = @json(route($routePrefix.'.esocial.faixas.destroy', ['faixa' => '__ID__']));
            const ESOCIAL = {
                urls: {
                    list:    @json(route($routePrefix.'.esocial.faixas.json')),
                    store:   ESOCIAL_STORE_URL,
                    update:  (id) => ESOCIAL_UPDATE_URL.replace('__ID__', id),
                    destroy: (id) => ESOCIAL_DESTROY_URL.replace('__ID__', id),
                },
                csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                state: { faixas: [] },
                dom: {
                    alert: document.getElementById('esocialAlert'),
                    list: document.getElementById('esocialFaixas'),
                    btnNova: document.getElementById('btnNovaFaixa'),

                    modalForm,
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

            function escapeHtml(str) {
                return String(str)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", "&#039;");
            }

            function esocialAlert(type, msg) {
                if (!ESOCIAL.dom.alert) return;
                ESOCIAL.dom.alert.classList.remove('hidden');
                ESOCIAL.dom.alert.className = 'mb-4 rounded-xl border px-4 py-3 text-sm';
                if (type === 'ok') ESOCIAL.dom.alert.classList.add('bg-emerald-50','border-emerald-200','text-emerald-800');
                else ESOCIAL.dom.alert.classList.add('bg-red-50','border-red-200','text-red-800');
                ESOCIAL.dom.alert.textContent = msg;
            }
            function esocialAlertHide() {
                ESOCIAL.dom.alert?.classList.add('hidden');
            }

            function syncEsocialAtivoLabel() {
                if (!ESOCIAL.dom.ativo || !ESOCIAL.dom.ativoLabel) return;
                ESOCIAL.dom.ativoLabel.textContent = ESOCIAL.dom.ativo.checked ? 'Ativo' : 'Inativo';
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
                    const faixaTxt = `${String(f.inicio).padStart(2,'0')} até ${f.fim == 999999 ? 'acima' : String(f.fim).padStart(2,'0')}`;
                    const precoTxt = Number(f.preco || 0).toLocaleString('pt-BR', {style:'currency', currency:'BRL'});

                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 px-3 py-2';

                    row.innerHTML = `
                <div class="col-span-2">
                    <div class="text-sm font-semibold text-slate-800">${faixaTxt}</div>
                    <div class="text-[11px] text-slate-500">#${f.id}</div>
                </div>

                <div class="col-span-5 text-sm text-slate-700">
                    ${f.descricao ? escapeHtml(f.descricao) : '<span class="text-slate-400">—</span>'}
                </div>

                <div class="col-span-1 text-right text-sm font-semibold text-slate-800">${precoTxt}</div>

                <div class="col-span-2 text-center">
                    ${f.ativo
                        ? '<span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">Ativo</span>'
                        : '<span class="text-xs px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100">Inativo</span>'}
                </div>

                <div class="col-span-2 text-center">
                    <button type="button" class="text-sm ${PERMS.update ? 'text-blue-600 hover:underline' : 'text-slate-400 cursor-not-allowed'}" data-action="edit" ${PERMS.update ? '' : 'disabled title=\"Usuário sem permissão\"'}>Editar</button>
                    <button type="button" class="text-sm ${PERMS.delete ? 'text-red-600 hover:underline' : 'text-slate-400 cursor-not-allowed'}" data-action="del" ${PERMS.delete ? '' : 'disabled title=\"Usuário sem permissão\"'}>Excluir</button>
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
                    const res = await fetch(ESOCIAL.urls.list, { headers: { 'Accept': 'application/json' }});
                    const json = await res.json();
                    ESOCIAL.state.faixas = json.data || [];
                    renderEsocialFaixas();
                } catch (e) {
                    console.error(e);
                    esocialAlert('err', 'Falha ao carregar faixas do eSocial.');
                }
            }

            // ---- mask (centavos) ----
            let maskReady = false;
            function onlyDigits(str){ return String(str||'').replace(/\D+/g,''); }
            function centsToNumber(d){ return (parseInt(d||'0',10)/100); }
            function formatBRL(n){ return Number(n||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }

            function setPrecoFromNumber(n){
                const v = ESOCIAL.dom.precoView, h = ESOCIAL.dom.precoHidden;
                if (!v || !h) return;
                const num = Number(n||0);
                h.value = num.toFixed(2);
                v.value = formatBRL(num);
                v.dataset.digits = onlyDigits(Math.round(num*100));
            }

            function attachMask(){
                const v = ESOCIAL.dom.precoView, h = ESOCIAL.dom.precoHidden;
                if (!v || !h || maskReady) return;
                maskReady = true;

                setPrecoFromNumber(Number(h.value||0));

                v.addEventListener('input', () => {
                    const digits = onlyDigits(v.value);
                    v.dataset.digits = digits;
                    const num = centsToNumber(digits);
                    h.value = num.toFixed(2);
                    v.value = formatBRL(num);
                    requestAnimationFrame(()=>v.setSelectionRange(v.value.length, v.value.length));
                });

                v.addEventListener('keydown', (e) => {
                    const nav = ['Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End','Delete'];
                    if (e.ctrlKey || e.metaKey) return;

                    if (e.key === 'Backspace') {
                        e.preventDefault();
                        const d = v.dataset.digits || '';
                        const nd = d.slice(0,-1);
                        v.dataset.digits = nd;
                        const num = centsToNumber(nd);
                        h.value = num.toFixed(2);
                        v.value = formatBRL(num);
                        requestAnimationFrame(()=>v.setSelectionRange(v.value.length, v.value.length));
                        return;
                    }
                    if (nav.includes(e.key)) return;
                    if (!/^\d$/.test(e.key)) e.preventDefault();
                });

                v.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const text = (e.clipboardData || window.clipboardData).getData('text');
                    const digits = onlyDigits(text);
                    v.dataset.digits = digits;
                    const num = centsToNumber(digits);
                    h.value = num.toFixed(2);
                    v.value = formatBRL(num);
                    requestAnimationFrame(()=>v.setSelectionRange(v.value.length, v.value.length));
                });

                v.addEventListener('focus', () => {
                    requestAnimationFrame(()=>v.setSelectionRange(v.value.length, v.value.length));
                });
            }

            function openEsocialForm(faixa = null) {
                if (!ESOCIAL.dom.modalForm) return;
                if (faixa && !PERMS.update) return deny('Usuário sem permissão para editar.');
                if (!faixa && !PERMS.create) return deny('Usuário sem permissão para criar.');

                ESOCIAL.dom.title.textContent = faixa ? 'Editar Faixa' : 'Nova Faixa';

                ESOCIAL.dom.id.value = faixa?.id ?? '';
                ESOCIAL.dom.inicio.value = faixa?.inicio ?? '';
                ESOCIAL.dom.fim.value = faixa?.fim ?? '';
                ESOCIAL.dom.descricao.value = faixa?.descricao ?? '';
                ESOCIAL.dom.ativo.checked = faixa ? !!faixa.ativo : true;
                syncEsocialAtivoLabel();

                ESOCIAL.dom.precoHidden.value = faixa ? Number(faixa.preco||0).toFixed(2) : '0.00';
                attachMask();
                setPrecoFromNumber(Number(ESOCIAL.dom.precoHidden.value||0));

                ESOCIAL.dom.modalForm.classList.remove('hidden');
            }

            function closeEsocialForm() {
                ESOCIAL.dom.modalForm?.classList.add('hidden');
            }

            async function saveEsocialFaixa(e) {
                e.preventDefault();
                esocialAlertHide();

                const id = ESOCIAL.dom.id.value;
                if (id && !PERMS.update) return deny('Usuário sem permissão para editar.');
                if (!id && !PERMS.create) return deny('Usuário sem permissão para criar.');
                const inicio = Number(ESOCIAL.dom.inicio.value || 0);
                const fim = Number(ESOCIAL.dom.fim.value || 0);
                const descricao = ESOCIAL.dom.descricao.value || null;
                const preco = Number(ESOCIAL.dom.precoHidden.value || 0);
                const ativo = ESOCIAL.dom.ativo.checked ? 1 : 0;

                if (!inicio || !fim) return esocialAlert('err','Informe início e fim.');
                if (inicio > fim) return esocialAlert('err','Início não pode ser maior que o fim.');
                if (preco < 0) return esocialAlert('err','Preço inválido.');

                const payload = { inicio, fim, descricao, preco, ativo };

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
                if (!PERMS.delete) return deny('Usuário sem permissão para excluir.');
                const ok = await window.uiConfirm('Deseja remover esta faixa?');
                if (!ok) return;

                try {
                    const res = await fetch(ESOCIAL.urls.destroy(id), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': ESOCIAL.csrf,
                        }
                    });

                    const json = await res.json().catch(() => ({}));

                    if (!res.ok) return esocialAlert('err', json?.message || 'Erro ao excluir faixa.');

                    await loadEsocialFaixas();
                    esocialAlert('ok', 'Faixa removida.');
                } catch (err) {
                    console.error(err);
                    esocialAlert('err', 'Falha ao excluir faixa.');
                }
            }

            // ====== modal open/close (externo) ======
            async function openEsocialModal() {
                modal.classList.remove('hidden');
                await loadEsocialFaixas();
            }
            function closeEsocialModal() {
                modal.classList.add('hidden');
            }

            // clicar fora e ESC (somente do ESOCIAL)
            document.addEventListener('click', (e) => {
                if (!modal.classList.contains('hidden') && e.target === modal) closeEsocialModal();
                if (ESOCIAL.dom.modalForm && !ESOCIAL.dom.modalForm.classList.contains('hidden') && e.target === ESOCIAL.dom.modalForm) closeEsocialForm();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') return;
                if (!modal.classList.contains('hidden')) closeEsocialModal();
                if (ESOCIAL.dom.modalForm && !ESOCIAL.dom.modalForm.classList.contains('hidden')) closeEsocialForm();
            });

            // binds
            if (ESOCIAL.dom.btnNova) ESOCIAL.dom.btnNova.addEventListener('click', () => openEsocialForm(null));
            if (ESOCIAL.dom.form) ESOCIAL.dom.form.addEventListener('submit', saveEsocialFaixa);
            if (ESOCIAL.dom.ativo) ESOCIAL.dom.ativo.addEventListener('change', syncEsocialAtivoLabel);

            // expoe no window (para onclick="" no index)
            window.openEsocialModal = openEsocialModal;
            window.closeEsocialModal = closeEsocialModal;
            window.openEsocialForm = openEsocialForm;
            window.closeEsocialForm = closeEsocialForm;
            window.loadEsocialFaixas = loadEsocialFaixas;
        })();
    </script>
@endpush
