{{-- resources/views/operacional/kanban/partials/anexos.blade.php --}}
@props([
    'tarefa',
])

@if($tarefa)
    <section class="mt-8 border-t border-slate-200 pt-4">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">
            Anexos (PDF / DOC / DOCX)
        </h2>

        {{-- Dropzone --}}
        <form
            id="form-anexos"
            method="POST"
            action="{{ route('operacional.tarefas.anexos.store', $tarefa) }}"
            enctype="multipart/form-data"
            class="space-y-3"
        >
            @csrf

            <div
                id="dropzone-anexos"
                class="border-2 border-dashed border-slate-300 rounded-xl px-4 py-6 text-center text-sm
                       text-slate-500 cursor-pointer bg-slate-50 hover:bg-slate-100 transition"
            >
                <p class="font-medium text-slate-700">Arraste e solte os arquivos aqui</p>
                <p class="text-xs text-slate-400 mt-1">
                    ou clique para selecionar (PDF, DOC, DOCX – máx. 10MB cada)
                </p>
                <input
                    id="input-arquivos"
                    type="file"
                    name="arquivos[]"
                    class="hidden"
                    multiple
                    accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                >
            </div>

            {{-- Lista de arquivos selecionados antes do envio --}}
            <ul id="lista-arquivos" class="text-xs text-slate-600 space-y-1"></ul>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold
                           hover:bg-emerald-700 disabled:opacity-60"
                    id="btn-upload-anexos"
                    disabled
                >
                    Enviar anexos
                </button>
            </div>
        </form>

        {{-- Últimos anexos --}}
        @php
            $anexos = $tarefa->anexos()->latest()->take(10)->get();
        @endphp

        @if($anexos->count())
            <div class="mt-4">
                <h3 class="text-xs font-semibold text-slate-600 mb-2">
                    Últimos anexos
                </h3>
                <ul class="divide-y divide-slate-100 text-xs">
                    @foreach($anexos as $anexo)
                        <li class="py-2 flex items-center justify-between gap-3">
                            <div class="flex flex-col">
                                <span class="font-medium text-slate-700">
                                    {{ $anexo->nome_original }}
                                </span>
                                <span class="text-[11px] text-slate-400">
                                    {{ strtoupper(pathinfo($anexo->nome_original, PATHINFO_EXTENSION)) }}
                                    •
                                    {{ number_format(($anexo->tamanho ?? 0) / 1024, 1, ',', '.') }} KB
                                    •
                                    {{ $anexo->created_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('operacional.anexos.download', $anexo) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:bg-slate-50">
                                    Baixar
                                </a>

                                <form method="POST"
                                      action="{{ route('operacional.anexos.destroy', $anexo) }}"
                                      onsubmit="return confirm('Remover este anexo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-red-200 text-[11px] text-red-600 hover:bg-red-50">
                                        Remover
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var dropzone   = document.getElementById('dropzone-anexos');
                var inputFiles = document.getElementById('input-arquivos');
                var lista      = document.getElementById('lista-arquivos');
                var btnUpload  = document.getElementById('btn-upload-anexos');

                if (!dropzone || !inputFiles || !lista || !btnUpload) return;

                function atualizarListaArquivos(files) {
                    lista.innerHTML = '';
                    if (!files || !files.length) {
                        btnUpload.disabled = true;
                        return;
                    }

                    Array.from(files).forEach(function (file) {
                        var li = document.createElement('li');
                        li.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                        lista.appendChild(li);
                    });

                    btnUpload.disabled = false;
                }

                dropzone.addEventListener('click', function () {
                    inputFiles.click();
                });

                inputFiles.addEventListener('change', function () {
                    atualizarListaArquivos(inputFiles.files);
                });

                dropzone.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('bg-slate-100');
                });

                dropzone.addEventListener('dragleave', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('bg-slate-100');
                });

                dropzone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('bg-slate-100');

                    var dtFiles = e.dataTransfer.files;
                    if (!dtFiles || !dtFiles.length) return;

                    // joga os arquivos do drop dentro do input[type=file]
                    inputFiles.files = dtFiles;
                    atualizarListaArquivos(dtFiles);
                });
            });
        </script>
    @endpush
@endif
