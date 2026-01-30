@extends('layouts.comercial')
@section('title', $clausula->exists ? 'Editar Cláusula' : 'Nova Cláusula')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">
                    {{ $clausula->exists ? 'Editar Cláusula' : 'Nova Cláusula' }}
                </h1>
                <p class="text-xs text-slate-500">Defina o texto HTML e o serviço relacionado.</p>
            </div>
            <a href="{{ route('comercial.contratos.clausulas.index') }}"
               class="px-4 py-2 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">
                Voltar
            </a>
        </div>

        <form method="POST"
              action="{{ $clausula->exists ? route('comercial.contratos.clausulas.update', $clausula) : route('comercial.contratos.clausulas.store') }}"
              class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            @csrf
            @if($clausula->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">
                        Serviço <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" name="servico_tipo" value="{{ old('servico_tipo', $clausula->servico_tipo ?? 'GERAL') }}"
                           class="w-full rounded-xl border @error('servico_tipo') border-rose-300 @else border-slate-200 @enderror text-sm px-3 py-2"
                           required>
                    @error('servico_tipo')
                        <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">
                        Slug <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" name="slug" value="{{ old('slug', $clausula->slug) }}"
                           class="w-full rounded-xl border @error('slug') border-rose-300 @else border-slate-200 @enderror text-sm px-3 py-2"
                           required>
                    @error('slug')
                        <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ordem</label>
                    <input type="number" min="0" name="ordem" value="{{ old('ordem', $clausula->ordem ?? 0) }}"
                           class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">
                    Título <span class="text-rose-600">*</span>
                </label>
                <input type="text" name="titulo" value="{{ old('titulo', $clausula->titulo) }}"
                       class="w-full rounded-xl border @error('titulo') border-rose-300 @else border-slate-200 @enderror text-sm px-3 py-2"
                       required>
                @error('titulo')
                    <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-medium text-slate-600">
                        Conteúdo da cláusula <span class="text-rose-600">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" id="btnVisual"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 text-white">
                            Renderizado
                        </button>
                        <button type="button" id="btnHtml"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600">
                            HTML
                        </button>
                    </div>
                </div>

                <div id="tabVisual">
                    <textarea id="editor_html" name="html_template" rows="10"
                              class="w-full rounded-xl border @error('html_template') border-rose-300 @else border-slate-200 @enderror text-xs p-3">{{ old('html_template', $clausula->html_template) }}</textarea>
                </div>

                <div id="tabHtml" class="hidden">
                    <textarea id="editor_raw" rows="12"
                              class="w-full rounded-xl border border-slate-200 text-xs font-mono p-3">{{ old('html_template', $clausula->html_template) }}</textarea>
                    <div class="mt-2 flex items-center gap-2">
                        <button type="button" id="btnApplyHtml"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            Aplicar HTML
                        </button>
                        <span class="text-[11px] text-slate-500">Aplique antes de salvar.</span>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500 mt-2">
                    Use placeholders como @{{CONTRATADA_RAZAO}}, @{{CONTRATANTE_RAZAO}}, @{{DATA_HOJE}}.
                </p>
                @error('html_template')
                    <p class="text-[11px] text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="ativo" value="1" id="ativo"
                       {{ old('ativo', $clausula->ativo ?? true) ? 'checked' : '' }}>
                <label for="ativo" class="text-xs text-slate-600">Ativa</label>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="px-4 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-xs text-emerald-700 hover:bg-emerald-100">
                    Salvar
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    const btnVisual = document.getElementById('btnVisual');
    const btnHtml = document.getElementById('btnHtml');
    const tabVisual = document.getElementById('tabVisual');
    const tabHtml = document.getElementById('tabHtml');
    const rawTextarea = document.getElementById('editor_raw');

    function setTab(tab) {
        const visualActive = tab === 'visual';
        tabVisual.classList.toggle('hidden', !visualActive);
        tabHtml.classList.toggle('hidden', visualActive);
        btnVisual.classList.toggle('bg-blue-600', visualActive);
        btnVisual.classList.toggle('text-white', visualActive);
        btnHtml.classList.toggle('bg-blue-600', !visualActive);
        btnHtml.classList.toggle('text-white', !visualActive);
        btnVisual.classList.toggle('bg-slate-100', !visualActive);
        btnVisual.classList.toggle('text-slate-600', !visualActive);
        btnHtml.classList.toggle('bg-slate-100', visualActive);
        btnHtml.classList.toggle('text-slate-600', visualActive);
    }

    btnVisual?.addEventListener('click', () => setTab('visual'));
    btnHtml?.addEventListener('click', () => setTab('html'));

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
        if (rawTextarea) {
            rawTextarea.value = editor.getData();
        }

        editor.model.document.on('change:data', () => {
            if (rawTextarea) {
                rawTextarea.value = editor.getData();
            }
        });
    });

    document.getElementById('btnApplyHtml')?.addEventListener('click', () => {
        if (!editorInstance || !rawTextarea) return;
        editorInstance.setData(rawTextarea.value || '');
    });
</script>
@endpush
