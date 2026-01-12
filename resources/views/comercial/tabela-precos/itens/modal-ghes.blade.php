@php($routePrefix = $routePrefix ?? 'comercial')

<div id="modalGhe" class="fixed inset-0 z-50 hidden bg-black/40">
    <div class="min-h-full w-full flex items-center justify-center p-4 md:p-6">
        <div class="bg-white w-full max-w-6xl rounded-2xl shadow-xl overflow-hidden max-h-[88vh] flex flex-col">
            <div class="px-6 py-4 bg-amber-700 text-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">GHE do Cliente</h2>
                    <p class="text-xs opacity-90">Defina funções, protocolo e base do ASO.</p>
                </div>
                <button type="button"
                        onclick="closeGheModal()"
                        class="h-9 w-9 rounded-xl hover:bg-white/10 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                <div id="gheAlert" class="hidden"></div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-semibold text-slate-700">Cliente</label>
                        <input type="hidden" id="gheClienteId" value="">
                        <input id="gheClienteNome" type="text" readonly
                               class="rounded-xl border border-slate-200 text-sm px-3 py-2 min-w-[240px] bg-slate-100 cursor-not-allowed"
                               placeholder="Cliente não selecionado">
                    </div>

                    <button type="button"
                            onclick="openGheForm(null)"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl
                                   bg-amber-700 hover:bg-amber-800 active:bg-amber-900
                                   text-white px-5 py-2.5 text-sm font-semibold shadow-sm
                                   ring-1 ring-amber-600/30 transition">
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
<div id="modalGheForm" class="fixed inset-0 z-[60] hidden bg-black/50">
    <div class="min-h-full w-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-5xl rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 id="gheFormTitle" class="text-lg font-semibold text-slate-800">Novo GHE</h3>
                <button type="button" onclick="closeGheForm()"
                        class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500 flex items-center justify-center">
                    ✕
                </button>
            </div>

            <form id="formGhe" class="p-6 space-y-5">
                <div id="gheFormAlert" class="hidden"></div>
                <input type="hidden" id="ghe_id" value="">

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Nome do GHE *</label>
                        <input id="ghe_nome" type="text"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                               placeholder="Ex: Trabalho em Altura">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Protocolo</label>
                        <select id="ghe_protocolo" class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">
                            <option value="">Selecione...</option>
                        </select>
                        <div class="text-xs text-slate-500 mt-1" id="gheProtocoloResumo">—</div>
                        <div id="gheProtocoloExames" class="mt-2 text-xs text-slate-600 space-y-1"></div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Funções do GHE</label>
                        <input id="gheFuncoesFilter" type="text"
                               class="mt-1 w-full rounded-xl border-slate-200 text-sm px-3 py-2"
                               placeholder="Buscar função por nome ou descrição">
                        <div id="gheFuncoesList" class="mt-1 max-h-48 overflow-y-auto border border-slate-200 rounded-xl p-3 space-y-2 text-sm">
                            @forelse($funcoes as $funcao)
                                @php($descricao = trim((string) ($funcao->descricao ?? '')))
                                @php($search = mb_strtolower(trim($funcao->nome . ' ' . $descricao)))
                                <label class="flex items-start gap-2" data-search="{{ e($search) }}">
                                    <input type="checkbox" value="{{ $funcao->id }}">
                                    <span>
                                        <span class="block">{{ $funcao->nome }}</span>
                                        @if($descricao)
                                            <span class="block text-xs text-slate-500">{{ $descricao }}</span>
                                        @endif
                                    </span>
                                </label>
                            @empty
                                <div class="text-sm text-slate-500">Nenhuma função cadastrada.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4 space-y-2">
                        <div class="text-xs font-semibold text-amber-800 uppercase tracking-wide">Resumo de preço</div>
                        <div class="text-sm text-slate-700">Total exames: <span id="gheTotalExames">R$ 0,00</span></div>
                        <div class="text-sm text-slate-700">Admissional: <span id="gheTotalAdm">R$ 0,00</span></div>
                        <div class="text-sm text-slate-700">Periódico: <span id="gheTotalPer">R$ 0,00</span></div>
                        <div class="text-sm text-slate-700">Demissional: <span id="gheTotalDem">R$ 0,00</span></div>
                        <div class="text-sm text-slate-700">Mudança de Função: <span id="gheTotalFun">R$ 0,00</span></div>
                        <div class="text-sm text-slate-700">Retorno ao Trabalho: <span id="gheTotalRet">R$ 0,00</span></div>
                    </div>
                </div>

                <div class="grid md:grid-cols-5 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Base Admissional</label>
                        <input id="ghe_base_adm" type="number" min="0" step="0.01"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2" value="0.00">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Base Periódico</label>
                        <input id="ghe_base_per" type="number" min="0" step="0.01"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2" value="0.00">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Base Demissional</label>
                        <input id="ghe_base_dem" type="number" min="0" step="0.01"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2" value="0.00">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Base Mudança</label>
                        <input id="ghe_base_fun" type="number" min="0" step="0.01"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2" value="0.00">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Base Retorno</label>
                        <input id="ghe_base_ret" type="number" min="0" step="0.01"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2" value="0.00">
                    </div>
                </div>

                <div class="grid md:grid-cols-5 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Preço fechado Admissional</label>
                        <input id="ghe_fechado_adm" type="number" min="0" step="0.01"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Preço fechado Periódico</label>
                        <input id="ghe_fechado_per" type="number" min="0" step="0.01"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Preço fechado Demissional</label>
                        <input id="ghe_fechado_dem" type="number" min="0" step="0.01"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Preço fechado Mudança</label>
                        <input id="ghe_fechado_fun" type="number" min="0" step="0.01"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Preço fechado Retorno</label>
                        <input id="ghe_fechado_ret" type="number" min="0" step="0.01"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2" placeholder="Opcional">
                    </div>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="closeGheForm()"
                            class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="rounded-xl bg-amber-700 hover:bg-amber-800 text-white px-5 py-2 text-sm font-semibold">
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
            const FUNCOES = @json($funcoes->map(fn ($f) => ['id' => $f->id, 'nome' => $f->nome, 'descricao' => $f->descricao]));

            const GHE = {
                urls: {
                    list:   @json(route($routePrefix.'.clientes-ghes.indexJson')),
                    store:  @json(route($routePrefix.'.clientes-ghes.store')),
                    update: (id) => @json(route($routePrefix.'.clientes-ghes.update', ['ghe' => '__ID__'])).replace('__ID__', id),
                    destroy:(id) => @json(route($routePrefix.'.clientes-ghes.destroy', ['ghe' => '__ID__'])).replace('__ID__', id),
                    protocolos: @json(route($routePrefix.'.protocolos-exames.indexJson')),
                },
                state: { ghes: [], protocolos: [] },
                dom: {
                    modal: document.getElementById('modalGhe'),
                    list: document.getElementById('gheList'),
                    alert: document.getElementById('gheAlert'),
                    formAlert: document.getElementById('gheFormAlert'),
                    clienteId: document.getElementById('gheClienteId'),
                    clienteNome: document.getElementById('gheClienteNome'),
                    modalForm: document.getElementById('modalGheForm'),
                    form: document.getElementById('formGhe'),
                    title: document.getElementById('gheFormTitle'),
                    id: document.getElementById('ghe_id'),
                    nome: document.getElementById('ghe_nome'),
                    protocolo: document.getElementById('ghe_protocolo'),
                    protocoloResumo: document.getElementById('gheProtocoloResumo'),
                    protocoloExames: document.getElementById('gheProtocoloExames'),
                    funcoesList: document.getElementById('gheFuncoesList'),
                    funcoesFilter: document.getElementById('gheFuncoesFilter'),
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
                    totalExames: document.getElementById('gheTotalExames'),
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

            function alertTarget(){
                if (GHE.dom.modalForm && !GHE.dom.modalForm.classList.contains('hidden') && GHE.dom.formAlert) {
                    return GHE.dom.formAlert;
                }
                return GHE.dom.alert;
            }

            function alertBox(type,msg){
                const target = alertTarget();
                if(!target) return;
                target.classList.remove('hidden');
                target.className = 'rounded-xl border px-4 py-3 text-sm';
                if(type==='ok'){
                    target.classList.add('bg-emerald-50','border-emerald-200','text-emerald-800');
                } else {
                    target.classList.add('bg-red-50','border-red-200','text-red-800');
                }
                target.textContent = msg;
            }
            function alertHide(){
                if(GHE.dom.alert) GHE.dom.alert.classList.add('hidden');
                if(GHE.dom.formAlert) GHE.dom.formAlert.classList.add('hidden');
            }

            async function loadProtocolos(){
                if (GHE.state.protocolos.length) return;
                const res = await fetch(GHE.urls.protocolos, { headers:{'Accept':'application/json'} });
                const json = await res.json();
                GHE.state.protocolos = json.data || [];
                renderProtocolosSelect();
            }

            function renderProtocolosSelect(selectedId = null){
                const sel = GHE.dom.protocolo;
                if(!sel) return;
                sel.innerHTML = '<option value="">Selecione...</option>';
                GHE.state.protocolos.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.titulo;
                    if (selectedId && Number(selectedId) === Number(p.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
                updateProtocoloResumo();
            }

            function getProtocoloById(id){
                return GHE.state.protocolos.find(p => Number(p.id) === Number(id));
            }

            function updateProtocoloResumo(){
                const protocoloId = GHE.dom.protocolo.value;
                const protocolo = protocoloId ? getProtocoloById(protocoloId) : null;
                if (!protocolo) {
                    GHE.dom.protocoloResumo.textContent = '—';
                    if (GHE.dom.protocoloExames) {
                        GHE.dom.protocoloExames.innerHTML = '<div class="text-slate-500">Nenhum exame selecionado.</div>';
                    }
                    refreshPreviewTotals(0);
                    return;
                }
                GHE.dom.protocoloResumo.textContent = `${protocolo.exames.length} exame(s) • ${brl(protocolo.total || 0)}`;
                if (GHE.dom.protocoloExames) {
                    if (protocolo.exames?.length) {
                        GHE.dom.protocoloExames.innerHTML = protocolo.exames.map(ex => {
                            const titulo = escapeHtml(ex.titulo || 'Exame');
                            const preco = brl(ex.preco || 0);
                            return `<div class="flex items-center justify-between gap-2">
                                <span class="truncate">${titulo}</span>
                                <span class="text-slate-700 font-semibold">${preco}</span>
                            </div>`;
                        }).join('');
                    } else {
                        GHE.dom.protocoloExames.innerHTML = '<div class="text-slate-500">Nenhum exame neste protocolo.</div>';
                    }
                }
                refreshPreviewTotals(Number(protocolo.total || 0));
            }

            function refreshPreviewTotals(totalExames){
                const baseAdm = Number(GHE.dom.baseAdm.value || 0);
                const basePer = Number(GHE.dom.basePer.value || 0);
                const baseDem = Number(GHE.dom.baseDem.value || 0);
                const baseFun = Number(GHE.dom.baseFun.value || 0);
                const baseRet = Number(GHE.dom.baseRet.value || 0);

                const fechadoAdm = Number(GHE.dom.fechadoAdm.value || 0);
                const fechadoPer = Number(GHE.dom.fechadoPer.value || 0);
                const fechadoDem = Number(GHE.dom.fechadoDem.value || 0);
                const fechadoFun = Number(GHE.dom.fechadoFun.value || 0);
                const fechadoRet = Number(GHE.dom.fechadoRet.value || 0);

                GHE.dom.totalExames.textContent = brl(totalExames);
                GHE.dom.totalAdm.textContent = brl(fechadoAdm || (baseAdm + totalExames));
                GHE.dom.totalPer.textContent = brl(fechadoPer || (basePer + totalExames));
                GHE.dom.totalDem.textContent = brl(fechadoDem || (baseDem + totalExames));
                GHE.dom.totalFun.textContent = brl(fechadoFun || (baseFun + totalExames));
                GHE.dom.totalRet.textContent = brl(fechadoRet || (baseRet + totalExames));
            }

            async function loadGhes(){
                const clienteId = GHE.dom.clienteId?.value;
                if(!clienteId) {
                    GHE.state.ghes = [];
                    renderGhes();
                    notifyGheUpdated();
                    return;
                }
                try{
                    alertHide();
                    const res = await fetch(`${GHE.urls.list}?cliente_id=${clienteId}`, { headers:{'Accept':'application/json'} });
                    const json = await res.json();
                    GHE.state.ghes = json.data || [];
                    renderGhes();
                    notifyGheUpdated();
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao carregar GHEs.');
                }
            }

            function renderGhes(){
                const wrap = GHE.dom.list;
                if(!wrap) return;
                wrap.innerHTML = '';
                if(!GHE.state.ghes.length){
                    wrap.innerHTML = `<div class="text-sm text-slate-500 py-3">Nenhum GHE cadastrado para este cliente.</div>`;
                    return;
                }
                GHE.state.ghes.forEach(g=>{
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 px-3 py-2';

                    const funcoesTxt = g.funcoes?.length ? g.funcoes.map(f => f.nome).filter(Boolean).join(', ') : 'Sem funções';
                    const protocoloTxt = g.protocolo?.titulo || 'Sem protocolo';
                    const total = brl(g.total_exames || 0);

                    row.innerHTML = `
                        <div class="col-span-4">
                            <div class="font-semibold text-slate-800">${escapeHtml(g.nome)}</div>
                            <div class="text-xs text-slate-500">${escapeHtml(protocoloTxt)}</div>
                        </div>
                        <div class="col-span-5 text-xs text-slate-600">${escapeHtml(funcoesTxt)}</div>
                        <div class="col-span-2 text-right font-semibold text-slate-800">${total}</div>
                        <div class="col-span-1 flex gap-2 justify-end">
                            <button type="button" class="text-blue-600 text-sm" data-action="edit">Editar</button>
                            <button type="button" class="text-red-600 text-sm" data-action="del">Excluir</button>
                        </div>
                    `;

                    row.querySelector('[data-action="edit"]').addEventListener('click', () => openGheForm(g));
                    row.querySelector('[data-action="del"]').addEventListener('click', () => destroyGhe(g.id));
                    wrap.appendChild(row);
                });
            }

            function getSelectedFuncoes(){
                return Array.from(GHE.dom.funcoesList.querySelectorAll('input[type="checkbox"]:checked'))
                    .map(cb => Number(cb.value));
            }

            function setSelectedFuncoes(ids){
                const idSet = new Set((ids || []).map(id => Number(id)));
                GHE.dom.funcoesList.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = idSet.has(Number(cb.value));
                });
            }

            function notifyGheUpdated() {
                const total = (GHE.state.ghes || []).reduce((sum, ghe) => sum + Number(ghe.total_exames || 0), 0);
                const hasGhe = (GHE.state.ghes || []).length > 0;
                window.dispatchEvent(new CustomEvent('ghe:updated', { detail: { hasGhe, total } }));
            }

            function applyFuncoesFilter(){
                const query = (GHE.dom.funcoesFilter?.value || '').trim().toLowerCase();
                const labels = GHE.dom.funcoesList?.querySelectorAll('label[data-search]') || [];
                labels.forEach(label => {
                    const hay = label.dataset.search || '';
                    label.classList.toggle('hidden', query !== '' && !hay.includes(query));
                });
            }

            async function saveGhe(e){
                e.preventDefault();
                const clienteId = GHE.dom.clienteId?.value;
                if(!clienteId) return alertBox('err','Selecione um cliente.');

                const payload = {
                    cliente_id: Number(clienteId),
                    nome: GHE.dom.nome.value.trim(),
                    protocolo_id: GHE.dom.protocolo.value ? Number(GHE.dom.protocolo.value) : null,
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

                const id = GHE.dom.id.value;
                const url = id ? GHE.urls.update(id) : GHE.urls.store;
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
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao salvar GHE.');
                }
            }

            async function destroyGhe(id){
                if(!confirm('Deseja remover este GHE?')) return;
                try{
                    const res = await fetch(GHE.urls.destroy(id), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                    });
                    if(!res.ok) throw new Error('fail');
                    await loadGhes();
                } catch(e){
                    console.error(e);
                    alertBox('err','Falha ao excluir GHE.');
                }
            }

            window.openGheModal = async function(){
                GHE.dom.modal?.classList.remove('hidden');
                await loadProtocolos();
                await loadGhes();
            };
            window.setGheCliente = function(clienteId, clienteNome){
                if (GHE.dom.clienteId) {
                    GHE.dom.clienteId.value = clienteId || '';
                }
                if (GHE.dom.clienteNome) {
                    GHE.dom.clienteNome.value = clienteNome || '';
                }
                loadGhes();
            };
            window.closeGheModal = () => GHE.dom.modal?.classList.add('hidden');

            window.openGheForm = async function(ghe){
                await loadProtocolos();
                GHE.dom.modalForm?.classList.remove('hidden');
                alertHide();
                GHE.dom.title.textContent = ghe ? 'Editar GHE' : 'Novo GHE';
                GHE.dom.id.value = ghe?.id || '';
                GHE.dom.nome.value = ghe?.nome || '';
                renderProtocolosSelect(ghe?.protocolo?.id || null);
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
                if (GHE.dom.funcoesFilter) {
                    GHE.dom.funcoesFilter.value = '';
                    applyFuncoesFilter();
                }
                updateProtocoloResumo();
            };
            window.closeGheForm = () => GHE.dom.modalForm?.classList.add('hidden');

            GHE.dom.form?.addEventListener('submit', saveGhe);
            GHE.dom.protocolo?.addEventListener('change', updateProtocoloResumo);
            GHE.dom.funcoesFilter?.addEventListener('input', applyFuncoesFilter);
            [GHE.dom.baseAdm, GHE.dom.basePer, GHE.dom.baseDem, GHE.dom.baseFun, GHE.dom.baseRet,
             GHE.dom.fechadoAdm, GHE.dom.fechadoPer, GHE.dom.fechadoDem, GHE.dom.fechadoFun, GHE.dom.fechadoRet]
                .forEach(el => el?.addEventListener('input', () => updateProtocoloResumo()));
        })();
    </script>
@endpush
