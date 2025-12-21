{{-- MODAL TREINAMENTOS (CRUD) --}}
@php($routePrefix = $routePrefix ?? 'comercial')

<div id="modalTreinamentosCrud" class="fixed inset-0 z-50 hidden bg-black/40">
    <div class="min-h-full w-full flex items-center justify-center p-4 md:p-6">
        <div class="bg-white w-full max-w-5xl rounded-2xl shadow-xl overflow-hidden max-h-[85vh] flex flex-col">

            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">Treinamentos (NRs)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Cadastro dos treinamentos. O preço continua na Tabela de Preços (itens).</p>
                </div>

                <button type="button"
                        onclick="closeTreinamentosCrudModal()"
                        class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <div class="px-6 py-4 border-b border-slate-100">
                <div class="flex flex-col md:flex-row gap-3 md:items-end md:justify-between">
                    <div class="flex-1">
                        <label class="text-xs font-semibold text-slate-600">Buscar</label>
                        <input id="trn_q"
                               type="text"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="Buscar por código ou título...">
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input id="trn_somente_ativos" type="checkbox" class="rounded border-slate-300" checked>
                        Somente ativos
                    </label>

                    <button type="button"
                            onclick="openTreinamentoFormModal()"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl
                                   bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800
                                   text-white px-5 py-2.5 text-sm font-semibold shadow-sm
                                   ring-1 ring-emerald-600/20 hover:ring-emerald-700/30 transition">
                        <span class="text-base leading-none">＋</span>
                        <span>Novo Treinamento</span>
                    </button>
                </div>

                <div id="trn_alert" class="hidden mt-4 rounded-xl border px-4 py-3 text-sm"></div>
            </div>

            <div class="px-6 py-5 overflow-y-auto">
                <div class="grid grid-cols-12 gap-2 text-xs font-semibold text-slate-500 mb-2">
                    <div class="col-span-2">Código</div>
                    <div class="col-span-7">Título</div>
                    <div class="col-span-1 text-center">Ativo</div>
                    <div class="col-span-2 text-right">Ações</div>
                </div>

                <div id="trn_list" class="space-y-2"></div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button type="button"
                        onclick="closeTreinamentosCrudModal()"
                        class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL FORM TREINAMENTO --}}
<div id="modalTreinamentoForm" class="fixed inset-0 z-[60] hidden bg-black/40">
    <div class="min-h-full w-full flex items-center justify-center p-4 md:p-6">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[85vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 id="trn_form_title" class="text-lg font-semibold text-slate-800">Novo Treinamento</h3>

                <button type="button"
                        onclick="closeTreinamentoFormModal()"
                        class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <form id="trn_form" class="flex-1 flex flex-col">
                <div class="px-6 py-5 space-y-4 overflow-y-auto">
                    <input type="hidden" id="trn_id">

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Código (ex: NR-10) *</label>
                        <input id="trn_codigo" type="text"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="NR-10">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Título *</label>
                        <input id="trn_titulo" type="text"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="Elétrica">
                    </div>

                    <div class="grid grid-cols-12 gap-3 items-center">
                        <div class="col-span-6">
                            <label class="text-xs font-semibold text-slate-600">Ordem</label>
                            <input id="trn_ordem" type="number" min="0"
                                   class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                   placeholder="0">
                        </div>

                        <div class="col-span-6 flex items-center gap-3 mt-6">
                            <div class="relative inline-block w-11 h-5">
                                <input id="trn_ativo" type="checkbox" value="1"
                                       class="peer appearance-none w-11 h-5 rounded-full cursor-pointer transition-colors duration-300
                                              bg-red-600 checked:bg-green-600" checked>
                                <label for="trn_ativo"
                                       class="absolute top-0 left-0 w-5 h-5 bg-white rounded-full border shadow-sm cursor-pointer
                                              transition-transform duration-300 border-red-600 peer-checked:border-green-600
                                              peer-checked:translate-x-6"></label>
                            </div>

                            <span id="trn_ativo_label" class="text-sm font-medium text-slate-700">Ativo</span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500">
                        * O preço do treinamento é cadastrado na Tabela de Preços (Itens), usando o serviço “Treinamentos NRs” e o código NR (ex: NR-10).
                    </p>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button"
                            onclick="closeTreinamentoFormModal()"
                            class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 text-sm font-semibold">
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
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const URLS = {
                list:   @json(route($routePrefix.'.treinamentos-nrs.json')),
                store:  @json(route($routePrefix.'.treinamentos-nrs.store')),
                update: (id) => @json(route($routePrefix.'.treinamentos-nrs.update', ['nr' => '__ID__'])).replace('__ID__', id),
                destroy:(id) => @json(route($routePrefix.'.treinamentos-nrs.destroy', ['nr' => '__ID__'])).replace('__ID__', id),
            };

            const dom = {
                modal: document.getElementById('modalTreinamentosCrud'),
                list: document.getElementById('trn_list'),
                alert: document.getElementById('trn_alert'),
                q: document.getElementById('trn_q'),
                onlyActive: document.getElementById('trn_somente_ativos'),

                modalForm: document.getElementById('modalTreinamentoForm'),
                form: document.getElementById('trn_form'),
                formTitle: document.getElementById('trn_form_title'),
                id: document.getElementById('trn_id'),
                codigo: document.getElementById('trn_codigo'),
                titulo: document.getElementById('trn_titulo'),
                ordem: document.getElementById('trn_ordem'),
                ativo: document.getElementById('trn_ativo'),
                ativoLabel: document.getElementById('trn_ativo_label'),
            };

            if (!dom.modal || !dom.list) return;

            const state = { data: [] };
            let debounceT = null;

            function alertBox(type, msg) {
                dom.alert.classList.remove('hidden');
                dom.alert.className = 'mt-4 rounded-xl border px-4 py-3 text-sm';
                if (type === 'ok') dom.alert.classList.add('bg-emerald-50','border-emerald-200','text-emerald-800');
                else dom.alert.classList.add('bg-red-50','border-red-200','text-red-800');
                dom.alert.textContent = msg;
            }
            function alertHide() { dom.alert.classList.add('hidden'); }

            function esc(s) {
                return String(s || '')
                    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
                    .replaceAll('"','&quot;').replaceAll("'","&#039;");
            }

            function syncAtivoLabel() {
                dom.ativoLabel.textContent = dom.ativo.checked ? 'Ativo' : 'Inativo';
            }

            async function load() {
                alertHide();
                dom.list.innerHTML = `<div class="text-sm text-slate-500 py-3">Carregando...</div>`;

                const q = (dom.q?.value || '').trim();
                const somente_ativos = dom.onlyActive?.checked ? 1 : 0;

                const url = new URL(URLS.list, window.location.origin);
                url.searchParams.set('somente_ativos', String(somente_ativos));
                if (q) url.searchParams.set('q', q);

                try {
                    const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
                    const json = await res.json();
                    state.data = json.data || [];
                    render();
                } catch (e) {
                    dom.list.innerHTML = '';
                    alertBox('err', 'Falha ao carregar treinamentos.');
                    console.error(e);
                }
            }

            function render() {
                dom.list.innerHTML = '';

                if (!state.data.length) {
                    dom.list.innerHTML = `<div class="text-sm text-slate-500 py-3">Nenhum treinamento encontrado.</div>`;
                    return;
                }

                state.data.forEach(nr => {
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 px-3 py-2';

                    row.innerHTML = `
                <div class="col-span-2">
                    <div class="text-sm font-semibold text-slate-800">${esc(nr.codigo)}</div>
                    <div class="text-[11px] text-slate-500">#${nr.id}</div>
                </div>

                <div class="col-span-7 text-sm text-slate-700">
                    ${esc(nr.titulo)}
                </div>

                <div class="col-span-1 text-center">
                    ${nr.ativo
                        ? '<span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">Ativo</span>'
                        : '<span class="text-xs px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100">Inativo</span>'}
                </div>

                <div class="col-span-2 text-right">
                    <button type="button" class="text-blue-600 hover:underline text-sm mr-3" data-act="edit">Editar</button>
                    <button type="button" class="text-red-600 hover:underline text-sm" data-act="del">Excluir</button>
                </div>
            `;

                    row.querySelector('[data-act="edit"]').addEventListener('click', () => openTreinamentoFormModal(nr));
                    row.querySelector('[data-act="del"]').addEventListener('click', () => destroy(nr.id));

                    dom.list.appendChild(row);
                });
            }

            function openCrud() {
                dom.modal.classList.remove('hidden');
                load();
            }
            function closeCrud() {
                dom.modal.classList.add('hidden');
            }

            function openForm(nr = null) {
                dom.formTitle.textContent = nr ? 'Editar Treinamento' : 'Novo Treinamento';
                dom.id.value = nr?.id || '';
                dom.codigo.value = nr?.codigo || '';
                dom.titulo.value = nr?.titulo || '';
                dom.ordem.value = (nr?.ordem ?? 0);
                dom.ativo.checked = nr ? !!nr.ativo : true;
                syncAtivoLabel();
                dom.modalForm.classList.remove('hidden');
            }
            function closeForm() {
                dom.modalForm.classList.add('hidden');
            }

            async function save(e) {
                e.preventDefault();
                alertHide();

                const id = dom.id.value;
                const payload = {
                    codigo: (dom.codigo.value || '').trim(),
                    titulo: (dom.titulo.value || '').trim(),
                    ordem: Number(dom.ordem.value || 0),
                    ativo: dom.ativo.checked ? 1 : 0,
                };

                if (!payload.codigo) return alertBox('err', 'Informe o código (ex: NR-10).');
                if (!payload.titulo) return alertBox('err', 'Informe o título.');

                const isEdit = !!id;
                const url = isEdit ? URLS.update(id) : URLS.store;

                try {
                    const res = await fetch(url, {
                        method: isEdit ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                        },
                        body: JSON.stringify(payload),
                    });

                    const json = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        if (json?.errors) {
                            const first = Object.values(json.errors)[0]?.[0] || 'Erro ao salvar.';
                            return alertBox('err', first);
                        }
                        return alertBox('err', json?.message || 'Erro ao salvar.');
                    }

                    closeForm();
                    await load();
                    alertBox('ok', isEdit ? 'Treinamento atualizado.' : 'Treinamento criado.');

                    window.dispatchEvent(new Event('treinamentos-nrs:changed'));

                } catch (err) {
                    console.error(err);
                    alertBox('err', 'Falha ao salvar.');
                }
            }

            async function destroy(id) {
                if (!confirm('Deseja excluir este treinamento?')) return;

                try {
                    const res = await fetch(URLS.destroy(id), {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    });

                    const json = await res.json().catch(() => ({}));
                    if (!res.ok) return alertBox('err', json?.message || 'Erro ao excluir.');

                    await load();
                    alertBox('ok', 'Treinamento excluído.');
                    window.dispatchEvent(new Event('treinamentos-nrs:changed'));
                } catch (err) {
                    console.error(err);
                    alertBox('err', 'Falha ao excluir.');
                }
            }

            // Eventos
            dom.q?.addEventListener('input', () => {
                clearTimeout(debounceT);
                debounceT = setTimeout(load, 250);
            });

            dom.onlyActive?.addEventListener('change', load);
            dom.form?.addEventListener('submit', save);
            dom.ativo?.addEventListener('change', syncAtivoLabel);

            document.addEventListener('click', (e) => {
                if (!dom.modal.classList.contains('hidden') && e.target === dom.modal) closeCrud();
                if (!dom.modalForm.classList.contains('hidden') && e.target === dom.modalForm) closeForm();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') return;
                if (!dom.modalForm.classList.contains('hidden')) return closeForm();
                if (!dom.modal.classList.contains('hidden')) return closeCrud();
            });

            // expõe global
            window.openTreinamentosCrudModal = openCrud;
            window.openNovoTreinamentoItemModal = openCrud;
            window.closeTreinamentosCrudModal = closeCrud;
            window.openTreinamentoFormModal = openForm;
            window.closeTreinamentoFormModal = closeForm;
        })();
    </script>
@endpush
