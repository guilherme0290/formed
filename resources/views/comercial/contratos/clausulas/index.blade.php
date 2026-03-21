@extends('layouts.comercial')
@section('title', 'Cláusulas de Contrato')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
        @if(session('ok'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('ok') }}</div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <a href="{{ url()->previous() !== request()->fullUrl() ? url()->previous() : route('comercial.contratos.index') }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 mb-2">
                    ← Voltar
                </a>
                <h1 class="text-xl font-semibold text-slate-900">Catálogo de Cláusulas</h1>
                <p class="text-xs text-slate-500">Arraste para reordenar cláusulas e subcláusulas.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="btnSalvarOrdem"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                        disabled>
                    Salvar ordem
                </button>
                <button type="button" id="btnNovaClausula"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                    Nova cláusula
                </button>
            </div>
        </div>

        <form method="GET" class="bg-white border border-slate-200 rounded-xl p-3 flex flex-wrap gap-2 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo serviço</label>
                <select name="servico" class="rounded-lg border border-slate-300 text-sm px-3 py-2 w-64">
                    <option value="">Todos</option>
                    @foreach(($serviceTypes ?? []) as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}" @selected($servico === $typeKey)>{{ $typeLabel }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filtrar</button>
            <a href="{{ route('comercial.contratos.clausulas.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpar</a>
        </form>

        <div class="space-y-3" id="rootsList">
            @forelse($roots as $rootIndex => $clausula)
                @php
                    $children = $childrenByParent->get($clausula->id, collect());
                    $numero = $rootIndex + 1;
                @endphp
                <article class="root-item sortable-item bg-white border border-slate-200 rounded-xl p-4 cursor-grab active:cursor-grabbing" draggable="true" data-id="{{ $clausula->id }}" data-level="root">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="flex-1 min-w-[240px]">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $clausula->servico_tipo }}</div>
                            <h3 class="text-sm font-semibold text-slate-900 mt-1 flex items-center gap-2">
                                <button type="button"
                                        class="toggle-children inline-flex items-center justify-center w-5 h-5 rounded hover:bg-slate-100 cursor-pointer"
                                        data-parent-id="{{ $clausula->id }}"
                                        title="Expandir/recolher subcláusulas">
                                    <span class="toggle-icon text-slate-600 text-xs">▾</span>
                                </button>
                                <span>CLÁUSULA {{ $numero }} - {{ $clausula->titulo }}</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Slug: <span class="font-mono">{{ $clausula->slug }}</span></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    class="preview-trigger inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50"
                                    data-preview-id="{{ $clausula->id }}"

                                    aria-label="Pré-visualizar cláusula">
                                <i class="fa-regular fa-eye text-sm"></i>
                            </button>
                            <button type="button" class="text-xs px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50" data-action="edit" data-id="{{ $clausula->id }}">Editar</button>
                            <button type="button" class="text-xs px-3 py-1.5 rounded-lg border border-indigo-300 text-indigo-700 hover:bg-indigo-50" data-action="create-child" data-id="{{ $clausula->id }}">+ Subcláusula</button>
                            <form method="POST" action="{{ route('comercial.contratos.clausulas.destroy', $clausula) }}" onsubmit="return confirm('Remover esta cláusula e suas subcláusulas?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-rose-300 text-rose-700 hover:bg-rose-50">Excluir</button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-3 pl-4 border-l border-slate-200 space-y-2 children-list" data-parent-id="{{ $clausula->id }}">
                        @foreach($children as $childIndex => $sub)
                            <div class="child-item sortable-item rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 cursor-grab active:cursor-grabbing" draggable="true" data-id="{{ $sub->id }}" data-level="child" data-parent-id="{{ $clausula->id }}">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="flex-1 min-w-[240px]">
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $sub->servico_tipo }}</div>
                                        <div class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                                            <span class="text-slate-600 text-xs">▾</span>
                                            <span>{{ $numero }}.{{ $childIndex + 1 }} - {{ $sub->titulo }}</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">Slug: <span class="font-mono">{{ $sub->slug }}</span></p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                class="preview-trigger inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-300 text-slate-700 hover:bg-white"
                                                data-preview-id="{{ $sub->id }}"

                                                aria-label="Pré-visualizar subcláusula">
                                            <i class="fa-regular fa-eye text-sm"></i>
                                        </button>
                                        <button type="button" class="text-xs px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white" data-action="edit" data-id="{{ $sub->id }}">Editar</button>
                                        <form method="POST" action="{{ route('comercial.contratos.clausulas.destroy', $sub) }}" onsubmit="return confirm('Remover esta subcláusula?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-rose-300 text-rose-700 hover:bg-rose-50">Excluir</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if($children->isEmpty())
                            <div class="text-xs text-slate-500">Sem subcláusulas.</div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="bg-white border border-slate-200 rounded-xl p-8 text-center text-sm text-slate-500">Nenhuma cláusula encontrada.</div>
            @endforelse
        </div>
    </div>

    <div id="modalClausula" class="fixed inset-0 z-[120] hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-modal-close></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="w-full max-w-3xl rounded-xl border border-slate-200 bg-white shadow-xl">
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h2 id="modalTitulo" class="text-sm font-semibold text-slate-800">Cláusula</h2>
                    <button type="button" class="text-slate-500 hover:text-slate-700" data-modal-close>✕</button>
                </div>

                <form id="formClausula" method="POST" class="p-4 space-y-4">
                    @csrf
                    <input type="hidden" id="_method" name="_method" value="POST">

                    <div class="text-xs text-slate-500">
                        Placeholders: <code>@{{CONTRATANTE_RAZAO}}</code>, <code>@{{CONTRATADA_RAZAO}}</code>, <code>@{{DATA_HOJE}}</code>, <code>@{{NUMERO_CLAUSULA}}</code>
                    </div>

                    <div class="grid md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo serviço</label>
                            <select id="servico_tipo" name="servico_tipo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                                @foreach(($serviceTypes ?? []) as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Slug</label>
                            <input id="slug" type="text" name="slug" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Título</label>
                            <input id="titulo" type="text" name="titulo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Cláusula pai</label>
                            <select id="parent_id" name="parent_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Sem pai (cláusula principal)</option>
                                @foreach($roots as $root)
                                    <option value="{{ $root->id }}">{{ $root->titulo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input id="ativo" type="checkbox" name="ativo" value="1" checked>
                            <label for="ativo" class="text-sm text-slate-700">Cláusula ativa</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Conteúdo da cláusula</label>
                        <div class="mb-2 flex flex-wrap items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5">
                            <button type="button" class="format-btn inline-flex items-center justify-center w-8 h-8 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 font-bold" data-format="bold" title="Negrito">B</button>
                            <button type="button" class="format-btn inline-flex items-center justify-center w-8 h-8 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 italic" data-format="italic" title="Itálico">I</button>
                            <button type="button" class="format-btn inline-flex items-center justify-center w-8 h-8 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 underline" data-format="underline" title="Sublinhado">U</button>
                            <span class="mx-1 h-6 w-px bg-slate-200"></span>
                            <button type="button" class="format-btn inline-flex items-center justify-center px-2 h-8 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 text-xs font-semibold" data-format="p" title="Parágrafo">P</button>
                            <button type="button" class="format-btn inline-flex items-center justify-center px-2 h-8 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 text-xs font-semibold" data-format="h3" title="Título 3">H3</button>
                            <button type="button" class="format-btn inline-flex items-center justify-center px-2 h-8 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 text-xs font-semibold" data-format="h4" title="Título 4">H4</button>
                            <span class="mx-1 h-6 w-px bg-slate-200"></span>
                            <button type="button" class="format-btn inline-flex items-center justify-center w-8 h-8 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 text-sm" data-format="ul" title="Lista com marcadores">•</button>
                            <button type="button" class="format-btn inline-flex items-center justify-center w-8 h-8 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 text-sm" data-format="ol" title="Lista numerada">1.</button>
                        </div>
                        <textarea id="content_text" name="content_text" rows="8" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Escreva o conteúdo em texto simples (um parágrafo por linha)."></textarea>
                        <p class="mt-1 text-[11px] text-slate-500">Ao usar os botões de formatação, o editor HTML avançado será ativado automaticamente.</p>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input id="editar_html" type="checkbox" name="editar_html" value="1">
                            Mostrar editor HTML (avançado)
                        </label>
                        <div id="html_advanced_panel" class="hidden mt-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">HTML da cláusula</label>
                            <textarea id="html_template" name="html_template" rows="10" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 justify-end">
                        <button type="button" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-modal-close>Cancelar</button>
                        <button id="btnSubmitModal" type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="clausePreviewModal" class="fixed z-[130] hidden w-[360px] max-w-[92vw] rounded-xl border border-slate-200 bg-white/95 backdrop-blur shadow-2xl">
        <div class="px-3 py-2 border-b border-slate-100">
            <div id="clausePreviewTitle" class="text-xs font-semibold text-slate-800 truncate">Pré-visualização</div>
        </div>
        <div id="clausePreviewBody" class="px-3 py-2 text-xs text-slate-700 leading-relaxed max-h-56 overflow-y-auto whitespace-pre-line"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrf = @json(csrf_token());
            const reorderUrl = @json(route('comercial.contratos.clausulas.reorder'));
            const showUrlTemplate = @json(route('comercial.contratos.clausulas.show', ['clausula' => '__ID__']));
            const storeUrl = @json(route('comercial.contratos.clausulas.store'));
            const updateUrlTemplate = @json(route('comercial.contratos.clausulas.update', ['clausula' => '__ID__']));

            const rootsList = document.getElementById('rootsList');
            const saveOrderBtn = document.getElementById('btnSalvarOrdem');
            const btnNovaClausula = document.getElementById('btnNovaClausula');

            const modal = document.getElementById('modalClausula');
            const modalTitle = document.getElementById('modalTitulo');
            const form = document.getElementById('formClausula');
            const methodInput = document.getElementById('_method');
            const btnSubmitModal = document.getElementById('btnSubmitModal');
            const previewModal = document.getElementById('clausePreviewModal');
            const previewTitle = document.getElementById('clausePreviewTitle');
            const previewBody = document.getElementById('clausePreviewBody');

            const fields = {
                servico_tipo: document.getElementById('servico_tipo'),
                slug: document.getElementById('slug'),
                titulo: document.getElementById('titulo'),
                parent_id: document.getElementById('parent_id'),
                ativo: document.getElementById('ativo'),
                content_text: document.getElementById('content_text'),
                editar_html: document.getElementById('editar_html'),
                html_template: document.getElementById('html_template'),
                html_panel: document.getElementById('html_advanced_panel'),
            };

            let dirtyOrder = false;
            let draggedItem = null;
            const clauseCache = {};
            let previewHideTimer = null;

            function setDirtyOrder(isDirty) {
                dirtyOrder = isDirty;
                saveOrderBtn.disabled = !dirtyOrder;
            }

            function getDragAfterElement(container, y, selector) {
                const draggableElements = [...container.querySelectorAll(selector + ':not(.dragging)')];

                return draggableElements.reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;
                    if (offset < 0 && offset > closest.offset) {
                        return { offset, element: child };
                    }
                    return closest;
                }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
            }

            function wireSortable(container, selector, canDrop) {
                if (!container) return;

                container.addEventListener('dragstart', (e) => {
                    const item = e.target.closest(selector);
                    if (!item) return;
                    draggedItem = item;
                    item.classList.add('dragging');
                });

                container.addEventListener('dragend', () => {
                    if (draggedItem) draggedItem.classList.remove('dragging');
                    draggedItem = null;
                });

                container.addEventListener('dragover', (e) => {
                    if (!draggedItem || !canDrop(draggedItem, container)) return;
                    e.preventDefault();

                    const afterElement = getDragAfterElement(container, e.clientY, selector);
                    if (!afterElement) {
                        container.appendChild(draggedItem);
                    } else {
                        container.insertBefore(draggedItem, afterElement);
                    }
                });

                container.addEventListener('drop', (e) => {
                    if (!draggedItem || !canDrop(draggedItem, container)) return;
                    e.preventDefault();
                    setDirtyOrder(true);
                });
            }

            wireSortable(rootsList, '.root-item', (item, container) => item.dataset.level === 'root' && container.id === 'rootsList');

            document.querySelectorAll('.children-list').forEach((childrenList) => {
                wireSortable(childrenList, '.child-item', (item, container) => {
                    return item.dataset.level === 'child' && item.dataset.parentId === container.dataset.parentId;
                });
            });

            saveOrderBtn?.addEventListener('click', async () => {
                const tree = [...document.querySelectorAll('#rootsList > .root-item')].map((rootItem) => {
                    const rootId = Number(rootItem.dataset.id || 0);
                    const children = [...rootItem.querySelectorAll('.children-list > .child-item')]
                        .map((child) => Number(child.dataset.id || 0))
                        .filter((id) => id > 0);

                    return { id: rootId, children };
                }).filter((row) => row.id > 0);

                try {
                    const res = await fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ tree }),
                    });

                    if (!res.ok) throw new Error('Falha ao salvar ordem');
                    setDirtyOrder(false);
                    window.location.reload();
                } catch (err) {
                    alert('Não foi possível salvar a nova ordem.');
                }
            });

            function setReadonlyMode(readonly) {
                const nodes = [
                    fields.servico_tipo,
                    fields.slug,
                    fields.titulo,
                    fields.parent_id,
                    fields.ativo,
                    fields.content_text,
                    fields.editar_html,
                    fields.html_template,
                ];

                nodes.forEach((node) => {
                    if (!node) return;
                    node.disabled = readonly;
                });

                btnSubmitModal.classList.toggle('hidden', readonly);
            }

            function resetForm() {
                form.reset();
                form.action = storeUrl;
                methodInput.value = 'POST';
                fields.parent_id.value = '';
                fields.ativo.checked = true;
                fields.editar_html.checked = false;
                fields.html_panel.classList.add('hidden');
                setReadonlyMode(false);
            }

            function openModal() {
                modal.classList.remove('hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
            }

            btnNovaClausula?.addEventListener('click', () => {
                resetForm();
                modalTitle.textContent = 'Nova cláusula';
                openModal();
            });

            fields.editar_html?.addEventListener('change', () => {
                fields.html_panel.classList.toggle('hidden', !fields.editar_html.checked);
            });

            document.querySelectorAll('[data-modal-close]').forEach((el) => {
                el.addEventListener('click', closeModal);
            });

            async function loadClause(id) {
                if (clauseCache[id]) return clauseCache[id];
                const url = showUrlTemplate.replace('__ID__', encodeURIComponent(String(id)));
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Falha ao carregar cláusula');
                const json = await res.json();
                clauseCache[id] = json.data || null;
                return clauseCache[id];
            }

            async function openEditView(action, id) {
                try {
                    const data = await loadClause(id);
                    if (!data) return;

                    resetForm();
                    form.action = updateUrlTemplate.replace('__ID__', encodeURIComponent(String(id)));
                    methodInput.value = 'PUT';

                    fields.servico_tipo.value = data.servico_tipo || 'GERAL';
                    fields.slug.value = data.slug || '';
                    fields.titulo.value = data.titulo || '';
                    fields.parent_id.value = data.parent_id ? String(data.parent_id) : '';
                    fields.ativo.checked = !!data.ativo;
                    fields.content_text.value = data.content_text || '';
                    fields.html_template.value = data.html_template || '';

                    const hasHtml = (data.html_template || '').trim() !== '';
                    fields.editar_html.checked = hasHtml;
                    fields.html_panel.classList.toggle('hidden', !hasHtml);

                    if (action === 'view') {
                        modalTitle.textContent = 'Visualizar cláusula';
                        setReadonlyMode(true);
                    } else {
                        modalTitle.textContent = data.parent_id ? 'Editar subcláusula' : 'Editar cláusula';
                        setReadonlyMode(false);
                    }

                    openModal();
                } catch (err) {
                    alert('Não foi possível carregar os dados da cláusula.');
                }
            }

            async function openCreateChild(parentId) {
                resetForm();
                modalTitle.textContent = 'Nova subcláusula';
                fields.parent_id.value = String(parentId);
                openModal();
            }

            document.querySelectorAll('[data-action][data-id]').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const action = btn.dataset.action;
                    const id = Number(btn.dataset.id || 0);
                    if (!id) return;

                    if (action === 'edit') {
                        await openEditView('edit', id);
                        return;
                    }

                    if (action === 'create-child') {
                        await openCreateChild(id);
                    }
                });
            });

            function htmlFromPlainText(text) {
                const lines = (text || '')
                    .split(/\r\n|\r|\n/)
                    .map((line) => line.trim())
                    .filter((line) => line !== '');
                if (!lines.length) return '';
                return lines.map((line) => `<p>${line}</p>`).join('\n');
            }

            function ensureHtmlMode() {
                fields.editar_html.checked = true;
                fields.html_panel.classList.remove('hidden');
                if ((fields.html_template.value || '').trim() === '') {
                    fields.html_template.value = htmlFromPlainText(fields.content_text.value);
                }
            }

            function wrapSelection(textarea, before, after) {
                const start = textarea.selectionStart ?? 0;
                const end = textarea.selectionEnd ?? 0;
                const value = textarea.value || '';
                const selected = value.slice(start, end);
                const fallback = selected || 'texto';
                const insert = `${before}${fallback}${after}`;
                textarea.value = value.slice(0, start) + insert + value.slice(end);
                const pos = start + insert.length;
                textarea.setSelectionRange(pos, pos);
                textarea.focus();
            }

            function applyFormat(format) {
                ensureHtmlMode();
                const t = fields.html_template;
                if (!t) return;

                if (format === 'bold') return wrapSelection(t, '<strong>', '</strong>');
                if (format === 'italic') return wrapSelection(t, '<em>', '</em>');
                if (format === 'underline') return wrapSelection(t, '<u>', '</u>');
                if (format === 'p') return wrapSelection(t, '<p>', '</p>');
                if (format === 'h3') return wrapSelection(t, '<h3>', '</h3>');
                if (format === 'h4') return wrapSelection(t, '<h4>', '</h4>');
                if (format === 'ul') return wrapSelection(t, '<ul>\n  <li>', '</li>\n</ul>');
                if (format === 'ol') return wrapSelection(t, '<ol>\n  <li>', '</li>\n</ol>');
            }

            document.querySelectorAll('.format-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const format = btn.dataset.format || '';
                    if (!format) return;
                    applyFormat(format);
                });
            });

            function positionPreview(target) {
                if (!previewModal) return;
                const rect = target.getBoundingClientRect();
                const vw = window.innerWidth;
                const vh = window.innerHeight;
                const modalW = Math.min(360, Math.floor(vw * 0.92));
                const estimatedH = 220;

                let left = rect.right + 10;
                let top = rect.top - 4;

                if (left + modalW > vw - 8) {
                    left = rect.left - modalW - 10;
                }
                if (left < 8) left = 8;

                if (top + estimatedH > vh - 8) {
                    top = vh - estimatedH - 8;
                }
                if (top < 8) top = 8;

                previewModal.style.left = `${left}px`;
                previewModal.style.top = `${top}px`;
            }

            function hidePreview() {
                if (!previewModal) return;
                previewModal.classList.add('hidden');
            }

            function scheduleHidePreview() {
                clearTimeout(previewHideTimer);
                previewHideTimer = setTimeout(hidePreview, 140);
            }

            async function showPreview(target, id) {
                if (!previewModal || !previewTitle || !previewBody) return;
                clearTimeout(previewHideTimer);

                try {
                    const data = await loadClause(id);
                    if (!data) return;

                    previewTitle.textContent = data.titulo || 'Pré-visualização';
                    previewBody.textContent = (data.content_text || 'Sem conteúdo cadastrado.').trim();
                    positionPreview(target);
                    previewModal.classList.remove('hidden');
                } catch (err) {
                    previewTitle.textContent = 'Pré-visualização';
                    previewBody.textContent = 'Não foi possível carregar o conteúdo.';
                    positionPreview(target);
                    previewModal.classList.remove('hidden');
                }
            }

            document.querySelectorAll('.preview-trigger').forEach((btn) => {
                const id = Number(btn.dataset.previewId || 0);
                if (!id) return;

                btn.addEventListener('mouseenter', () => showPreview(btn, id));
                btn.addEventListener('mouseleave', scheduleHidePreview);
                btn.addEventListener('focus', () => showPreview(btn, id));
                btn.addEventListener('blur', scheduleHidePreview);
            });

            previewModal?.addEventListener('mouseenter', () => clearTimeout(previewHideTimer));
            previewModal?.addEventListener('mouseleave', scheduleHidePreview);

            document.querySelectorAll('.toggle-children').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const parentId = btn.dataset.parentId;
                    const list = document.querySelector(`.children-list[data-parent-id="${parentId}"]`);
                    const icon = btn.querySelector('.toggle-icon');
                    if (!list || !icon) return;

                    const isHidden = list.classList.toggle('hidden');
                    icon.textContent = isHidden ? '▸' : '▾';
                });
            });

            @if($errors->any())
                resetForm();
                modalTitle.textContent = 'Corrija os campos da cláusula';
                fields.servico_tipo.value = @json(old('servico_tipo', 'GERAL'));
                fields.slug.value = @json(old('slug', ''));
                fields.titulo.value = @json(old('titulo', ''));
                fields.parent_id.value = @json((string) old('parent_id', ''));
                fields.ativo.checked = @json((bool) old('ativo', true));
                fields.content_text.value = @json(old('content_text', ''));
                fields.html_template.value = @json(old('html_template', ''));
                fields.editar_html.checked = @json((bool) old('editar_html', false));
                fields.html_panel.classList.toggle('hidden', !fields.editar_html.checked);
                openModal();
            @endif
        });
    </script>
@endsection
