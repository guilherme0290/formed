@extends('layouts.comercial')
@section('title', 'Contrato da Proposta')

@section('content')
    <style>
        .contract-split { display: flex; gap: 12px; align-items: stretch; }
        .contract-pane { min-width: 280px; }
        .contract-resizer { width: 6px; cursor: col-resize; background: #e2e8f0; border-radius: 999px; }
        .contract-resizer:hover { background: #cbd5f5; }
        .preview-a4 { max-width: 794px; margin: 0 auto; background: #fff; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08); padding: 32px; }
        .preview-frame { background: #f8fafc; }
    </style>
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Contrato da Proposta #{{ $proposta->id }}</h1>
                <p class="text-xs text-slate-500">Edite o HTML e visualize o contrato renderizado.</p>
            </div>
        </div>

        @if(session('error'))
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Falha na integração com IA</p>
                <p>{{ session('error') }}</p>
                @if(session('error_details') && auth()->user()?->isMaster())
                    <details class="mt-2 text-xs text-rose-800">
                        <summary class="cursor-pointer">Detalhes técnicos</summary>
                        <pre class="mt-2 whitespace-pre-wrap">{{ json_encode(session('error_details'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                @endif
            </div>
        @endif

        @if(session('ok'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        <div class="sticky top-4 z-10 mb-4 rounded-2xl border border-slate-200 bg-white/90 backdrop-blur shadow-sm p-3">
            <div class="flex flex-wrap items-center gap-2 justify-between">
                <div class="flex items-center gap-2">
                    <button type="button" id="btnTabVisual"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 text-white">
                        Visual
                    </button>
                    <button type="button" id="btnTabHtml"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600">
                        HTML
                    </button>
                    <button type="button" id="btnToggleA4"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600">
                        Prévia A4
                    </button>
                    <select id="zoomSelect" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 text-slate-600">
                        <option value="0.9">90%</option>
                        <option value="1" selected>100%</option>
                        <option value="1.1">110%</option>
                        <option value="1.2">120%</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrint"
                            class="px-4 py-2 rounded-lg border border-blue-200 bg-blue-50 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                        Imprimir / PDF
                    </button>
                    <button type="button" id="btnDownloadHtml"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">
                        Baixar HTML
                    </button>
                    <a href="{{ route('comercial.contratos.clausulas.index') }}"
                       class="px-4 py-2 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">
                        Catálogo
                    </a>
                    <a href="{{ route('comercial.propostas.show', $proposta) }}"
                       class="px-4 py-2 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">
                        Voltar
                    </a>
                    <form method="POST" action="{{ route('comercial.propostas.contrato.regenerar', $proposta) }}">
                        @csrf
                        <input type="hidden" name="prompt_custom" id="prompt_custom_hidden">
                        <button type="submit"
                                class="px-4 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-xs text-emerald-700 hover:bg-emerald-100">
                            Regerar com IA
                        </button>
                    </form>
                    <button type="submit" form="formContrato"
                            class="px-4 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                        Salvar
                    </button>
                </div>
            </div>
        </div>

        <div class="contract-split">
            <div id="paneEditor" class="contract-pane flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-slate-800">Editor</h2>
                    <span class="text-[11px] text-slate-500">Status: {{ $contrato->status }}</span>
                </div>

                <div id="tabVisual">
                    <form method="POST" action="{{ route('comercial.propostas.contrato.update', $proposta) }}" id="formContrato">
                        @csrf
                        @method('PUT')
                        <textarea id="editor_html" name="html" class="hidden">{!! $contrato->html !!}</textarea>
                    </form>
                </div>

                <div id="tabHtml" class="hidden">
                    <label class="block text-xs font-medium text-slate-600 mb-2">HTML do contrato</label>
                    <textarea id="editor_raw" class="w-full h-[520px] rounded-xl border border-slate-200 text-xs font-mono p-3">{!! $contrato->html !!}</textarea>
                    <div class="mt-3 flex items-center gap-2">
                        <button type="button" id="btnApplyHtml"
                                class="px-4 py-2 rounded-lg border border-blue-200 bg-blue-50 text-xs text-blue-700 hover:bg-blue-100">
                            Aplicar HTML
                        </button>
                        <span class="text-[11px] text-slate-500">Lembre-se de salvar após aplicar.</span>
                    </div>
                </div>

                <div class="mt-6 border-t pt-4">
                    <h3 class="text-sm font-semibold text-slate-800 mb-2">Falar com a IA</h3>
                    <textarea id="prompt_custom" class="w-full rounded-xl border border-slate-200 text-xs p-3" rows="3"
                              placeholder="Peça ajustes no contrato (ex.: incluir cláusula de multa, alterar prazo, etc.)"></textarea>
                    <p class="text-[11px] text-slate-500 mt-2">Ao clicar em “Regerar com IA”, o prompt será enviado.</p>
                </div>
            </div>

            <div id="divider" class="contract-resizer"></div>

            <div id="panePreview" class="contract-pane flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-slate-800">Pré-visualização</h2>
                    <button type="button" id="btnFullscreenPreview"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600">
                        Tela cheia
                    </button>
                </div>
                <div id="previewFrame" class="preview-frame border border-slate-100 rounded-xl p-4 text-sm min-h-[640px] overflow-auto">
                    <div id="preview" class="text-sm"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    const tabVisual = document.getElementById('tabVisual');
    const tabHtml = document.getElementById('tabHtml');
    const btnTabVisual = document.getElementById('btnTabVisual');
    const btnTabHtml = document.getElementById('btnTabHtml');
    const preview = document.getElementById('preview');
    const previewFrame = document.getElementById('previewFrame');
    const rawTextarea = document.getElementById('editor_raw');
    const promptInput = document.getElementById('prompt_custom');
    const promptHidden = document.getElementById('prompt_custom_hidden');
    const btnToggleA4 = document.getElementById('btnToggleA4');
    const zoomSelect = document.getElementById('zoomSelect');
    const btnPrint = document.getElementById('btnPrint');
    const btnDownloadHtml = document.getElementById('btnDownloadHtml');
    const btnFullscreenPreview = document.getElementById('btnFullscreenPreview');
    const divider = document.getElementById('divider');
    const paneEditor = document.getElementById('paneEditor');
    const panePreview = document.getElementById('panePreview');

    const fallbackStyle = `
        table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;}
        th,td{border:1px solid #e2e8f0;padding:6px;vertical-align:top;}
        th{background:#f8fafc;text-align:left;font-weight:700;}
    `;

    function normalizeHtml(html) {
        const trimmed = (html || '').trim();
        if (!trimmed) {
            return { bodyHtml: '', styles: fallbackStyle, fullDoc: false };
        }

        const hasHtmlTag = /<html/i.test(trimmed);
        if (!hasHtmlTag) {
            return { bodyHtml: trimmed, styles: fallbackStyle, fullDoc: false };
        }

        const parser = new DOMParser();
        const doc = parser.parseFromString(trimmed, 'text/html');
        const styles = Array.from(doc.querySelectorAll('style'))
            .map(style => style.textContent || '')
            .join('\n');
        const bodyHtml = doc.body ? doc.body.innerHTML : trimmed;
        return { bodyHtml, styles: styles || fallbackStyle, fullDoc: true };
    }

    function renderPreview(html) {
        const normalized = normalizeHtml(html);
        preview.innerHTML = `<style>${normalized.styles}</style>${normalized.bodyHtml}`;
    }

    function setTab(tab) {
        const visualActive = tab === 'visual';
        tabVisual.classList.toggle('hidden', !visualActive);
        tabHtml.classList.toggle('hidden', visualActive);
        btnTabVisual.classList.toggle('bg-blue-600', visualActive);
        btnTabVisual.classList.toggle('text-white', visualActive);
        btnTabHtml.classList.toggle('bg-blue-600', !visualActive);
        btnTabHtml.classList.toggle('text-white', !visualActive);
        btnTabVisual.classList.toggle('bg-slate-100', !visualActive);
        btnTabVisual.classList.toggle('text-slate-600', !visualActive);
        btnTabHtml.classList.toggle('bg-slate-100', visualActive);
        btnTabHtml.classList.toggle('text-slate-600', visualActive);
    }

    btnTabVisual?.addEventListener('click', () => setTab('visual'));
    btnTabHtml?.addEventListener('click', () => setTab('html'));

    let editorInstance = null;
    ClassicEditor.create(document.querySelector('#editor_html'), {
        toolbar: [
            'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList',
            '|', 'insertTable', '|', 'undo', 'redo'
        ],
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
        }
    }).then(editor => {
        editorInstance = editor;
        const data = editor.getData();
        renderPreview(data);
        if (rawTextarea) {
            rawTextarea.value = data;
        }

        editor.model.document.on('change:data', () => {
            const html = editor.getData();
            renderPreview(html);
            if (rawTextarea) {
                rawTextarea.value = html;
            }
        });
    });

    document.getElementById('btnApplyHtml')?.addEventListener('click', () => {
        if (!editorInstance || !rawTextarea) return;
        editorInstance.setData(rawTextarea.value || '');
        renderPreview(rawTextarea.value || '');
    });

    promptInput?.addEventListener('input', () => {
        if (promptHidden) {
            promptHidden.value = promptInput.value || '';
        }
    });

    btnToggleA4?.addEventListener('click', () => {
        previewFrame.classList.toggle('preview-a4');
        btnToggleA4.classList.toggle('bg-blue-600');
        btnToggleA4.classList.toggle('text-white');
        btnToggleA4.classList.toggle('bg-slate-100');
        btnToggleA4.classList.toggle('text-slate-600');
    });

    zoomSelect?.addEventListener('change', () => {
        const value = zoomSelect.value || '1';
        preview.style.transformOrigin = 'top center';
        preview.style.transform = `scale(${value})`;
    });

    function getCurrentHtml() {
        if (editorInstance) return editorInstance.getData();
        if (rawTextarea) return rawTextarea.value || '';
        return '';
    }

    function buildPrintableHtml() {
        const html = getCurrentHtml();
        const normalized = normalizeHtml(html);
        if (normalized.fullDoc) {
            return html;
        }
        return `<!doctype html><html lang="pt-br"><head><meta charset="utf-8"/><title>Contrato</title><style>${normalized.styles}</style></head><body>${normalized.bodyHtml}</body></html>`;
    }

    btnPrint?.addEventListener('click', () => {
        const html = buildPrintableHtml();
        if (!html) return;
        const win = window.open('', '_blank');
        if (!win) return;
        win.document.write(html);
        win.document.close();
        win.focus();
        win.print();
    });

    btnDownloadHtml?.addEventListener('click', () => {
        const html = buildPrintableHtml();
        if (!html) return;
        const blob = new Blob([html], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `contrato-proposta-{{ $proposta->id }}.html`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });

    btnFullscreenPreview?.addEventListener('click', () => {
        if (!previewFrame) return;
        if (previewFrame.requestFullscreen) {
            previewFrame.requestFullscreen();
        }
    });

    let dragging = false;
    divider?.addEventListener('mousedown', (e) => {
        dragging = true;
        e.preventDefault();
    });
    window.addEventListener('mouseup', () => { dragging = false; });
    window.addEventListener('mousemove', (e) => {
        if (!dragging || !paneEditor || !panePreview) return;
        const container = paneEditor.parentElement;
        if (!container) return;
        const rect = container.getBoundingClientRect();
        const offset = e.clientX - rect.left;
        const min = 320;
        const max = rect.width - 320;
        const clamped = Math.min(Math.max(offset, min), max);
        paneEditor.style.flex = `0 0 ${clamped}px`;
        panePreview.style.flex = '1 1 auto';
    });
</script>
@endpush
