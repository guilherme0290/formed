@extends('layouts.comercial')
@section('title', 'Contrato Dinâmico #' . $contrato->id)

@section('content')
    <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
        @if(session('ok'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('ok') }}</div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Contrato Dinâmico do Cliente</h1>
                <p class="text-xs text-slate-500">Contrato #{{ $contrato->id }} • Cliente {{ $contrato->cliente->razao_social ?? '—' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('comercial.contratos.clausulas.index') }}"
                   class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Catálogo de cláusulas</a>
                @if($contrato->cliente)
                    <a href="{{ route('comercial.clientes.edit', $contrato->cliente) }}"
                       class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cadastro do cliente</a>
                @endif
                <a href="{{ route('comercial.contratos.show', $contrato) }}"
                   class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Voltar</a>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-3 flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('comercial.contratos.documento.gerar', $contrato) }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg border border-indigo-200 bg-indigo-50 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                    Regerar contrato (template)
                </button>
            </form>

            <button type="button" id="btnPrint"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Imprimir / PDF
            </button>
        </div>

        <form method="POST" action="{{ route('comercial.contratos.documento.update', $contrato) }}" id="formContrato"
              class="grid lg:grid-cols-2 gap-4 items-start">
            @csrf
            @method('PUT')

            <section class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-sm font-semibold text-slate-800">HTML do documento</h2>
                    <span class="text-xs text-slate-500">Status: {{ $documento->status }}</span>
                </div>

                <textarea id="htmlInput" name="html" rows="34"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-mono">{{ old('html', $documento->html) }}</textarea>

                <div class="mt-3 flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">Salvar alterações</button>
                    <button type="button" id="btnPreview" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Atualizar prévia</button>
                </div>
            </section>

            <section class="bg-white border border-slate-200 rounded-xl p-4 sticky top-4">
                <h2 class="text-sm font-semibold text-slate-800 mb-2">Pré-visualização</h2>
                <iframe id="previewFrame" class="w-full h-[80vh] rounded-lg border border-slate-200 bg-white"></iframe>
            </section>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const htmlInput = document.getElementById('htmlInput');
    const previewFrame = document.getElementById('previewFrame');
    const btnPreview = document.getElementById('btnPreview');
    const btnPrint = document.getElementById('btnPrint');

    function renderPreview() {
        if (!previewFrame || !htmlInput) return;
        const doc = previewFrame.contentWindow?.document;
        if (!doc) return;
        doc.open();
        doc.write(htmlInput.value || '');
        doc.close();
    }

    btnPreview?.addEventListener('click', renderPreview);
    btnPrint?.addEventListener('click', () => previewFrame.contentWindow?.print());
    window.addEventListener('load', renderPreview);
</script>
@endpush
