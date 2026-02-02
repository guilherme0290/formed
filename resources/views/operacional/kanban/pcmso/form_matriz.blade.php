@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')


@php
    /** @var \App\Models\PcmsoSolicitacoes|null $pcmso */
    $isEdit = isset($pcmso);
    $anexos = $anexos ?? collect();
     use App\Helpers\S3Helper;
     use Illuminate\Support\Facades\Storage;
     use Illuminate\Support\Str;
@endphp


@section('pageTitle', 'PCMSO - Matriz')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <a href="{{ route('operacional.pcmso.possui-pgr', [$cliente, 'matriz']) }}"
           class="inline-flex items-center gap-2 text-xs text-slate-600 mb-4">
            ← Voltar
        </a>

        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-fuchsia-500 text-white">
                <h1 class="text-lg font-semibold">PCMSO - Matriz</h1>
                <p class="text-xs text-purple-100 mt-1">
                    {{ $cliente->razao_social ?? $cliente->nome }}
                </p>
            </div>

            <form method="POST"
                  action="{{ $isEdit
                        ? route('operacional.kanban.pcmso.update', $pcmso->tarefa_id)
                        : route('operacional.pcmso.store-com-pgr', [$cliente, 'matriz']) }}"
                  enctype="multipart/form-data"
                  class="p-6 space-y-4">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700 mb-3">
                        <ul class="list-disc ms-4">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="border-b border-slate-200 mb-4">
                    <nav class="flex gap-6 text-sm">
                        <button type="button"
                                class="pcmso-tab-btn border-b-2 border-sky-500 text-sky-600 font-semibold pb-2"
                                data-tab="dados">
                            Dados do PCMSO
                        </button>

                        <button type="button"
                                class="pcmso-tab-btn text-slate-500 hover:text-slate-700 pb-2"
                                data-tab="anexos">
                            Anexos
                        </button>
                    </nav>
                </div>
                <div id="pcmso-tab-dados" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Inserir PGR / Inventário de Risco {{ $isEdit ? '' : '*' }}
                        </label>

                        <input type="file" name="pgr_arquivo" accept="application/pdf"
                               class="w-full text-sm rounded-lg border border-slate-200 px-3 py-2
                      file:mr-3 file:px-3 file:py-2 file:rounded-lg
                      file:border-0 file:bg-purple-600 file:text-white
                      hover:file:bg-purple-700">

                        @if($isEdit && !empty($pcmso->pgr_arquivo_path))

                            <div class="mt-2 text-xs text-slate-600">
                                <p>
                                    Arquivo atual:
                                    <a href="{{ $pcmso->pgr_arquivo_path ? S3Helper::url($pcmso->pgr_arquivo_path) : '#' }}"
                                       target="_blank"
                                       class="text-purple-600 underline">
                                        Abrir PGR atual
                                    </a>
                                </p>

                                <label class="inline-flex items-center gap-2 mt-1 text-[11px] text-red-600">
                                    <input type="checkbox" name="remover_arquivo" value="1"
                                           class="rounded border-slate-300">
                                    <span>Remover arquivo atual</span>
                                </label>
                            </div>
                        @endif
                    </div>


                    <section>
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h2 class="text-sm font-semibold text-slate-800">
                                Funcoes e Cargos
                            </h2>

                            <x-funcoes.create-button label="Cadastrar nova funcao" variant="sky" :allowCreate="true" />
                        </div>

                        @php
                            $funcoesForm = old('funcoes');

                            if ($funcoesForm === null) {
                                if (isset($pcmso) && is_array($pcmso->funcoes)) {
                                    $funcoesForm = $pcmso->funcoes;
                                } else {
                                    $funcoesForm = [
                                        ['funcao_id' => null, 'quantidade' => 1, 'cbo' => null, 'descricao' => null],
                                    ];
                                }
                            }
                        @endphp

                        <div id="pcmso-funcoes-wrapper" class="space-y-3">
                            @foreach($funcoesForm as $idx => $f)
                                <div class="funcao-item rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
                                     data-funcao-index="{{ $idx }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="badge-funcao text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-semibold">
                                            Funcao {{ $idx + 1 }}
                                        </span>

                                        <button type="button"
                                                class="btn-remove-funcao inline-flex items-center gap-1 text-[11px] text-red-600 hover:text-red-800">
                                            Remover
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-12 gap-3">
                                        <div class="col-span-5 funcao-select-wrapper">
                                            <x-funcoes.select-with-create
                                                name="funcoes[{{ $idx }}][funcao_id]"
                                                field-id="funcoes_{{ $idx }}_funcao_id"
                                                label="Cargo"
                                                help-text="Funcoes listadas por GHE, pre-configuradas pelo vendedor/comercial."
                                                :funcoes="$funcoes"
                                                :selected="old('funcoes.'.$idx.'.funcao_id', $f['funcao_id'] ?? null)"
                                                :show-create="true"
                                                :allowCreate="true"
                                            />
                                        </div>

                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-slate-500 mb-1">Qtd</label>
                                            <input type="number"
                                                   name="funcoes[{{ $idx }}][quantidade]"
                                                   class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                                   value="{{ old('funcoes.'.$idx.'.quantidade', $f['quantidade'] ?? 1) }}"
                                                   min="1">
                                        </div>

                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-slate-500 mb-1">CBO</label>
                                            <input type="text"
                                                   name="funcoes[{{ $idx }}][cbo]"
                                                   class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                                   value="{{ old('funcoes.'.$idx.'.cbo', $f['cbo'] ?? '') }}"
                                                   placeholder="0000-00">
                                        </div>

                                        <div class="col-span-3">
                                            <label class="block text-xs font-medium text-slate-500 mb-1">
                                                Descricao (opcional)
                                            </label>
                                            <input type="text"
                                                   name="funcoes[{{ $idx }}][descricao]"
                                                   class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                                   value="{{ old('funcoes.'.$idx.'.descricao', $f['descricao'] ?? '') }}"
                                                   placeholder="Atividades...">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-end mt-3">
                            <button type="button" id="pcmso-btn-add-funcao"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-sky-500 text-white text-xs font-semibold hover:bg-sky-600">
                                <span>+</span>
                                <span>Adicionar</span>
                            </button>
                        </div>

                        @error('funcoes')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </section>

                    <div class="rounded-xl bg-purple-50 border border-purple-100 px-4 py-3 text-xs text-purple-800">
                        Tarefa de PCMSO Matriz será criada com prazo de <strong>10 dias</strong>.
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-2">
                        <a href="{{ route('operacional.pcmso.possui-pgr', [$cliente, 'matriz']) }}"
                           class="flex-1 text-center rounded-lg bg-slate-50 border border-slate-200 text-sm font-semibold
                                  text-slate-700 py-2.5 hover:bg-slate-100 transition">
                            Voltar
                        </a>

                        <button type="submit"
                                class="flex-1 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-sm
                   font-semibold py-2.5 transition">
                            {{ $isEdit ? 'Salvar alterações' : 'Criar Tarefa PCMSO' }}
                        </button>

                    </div>
                </div>
                <div id="pcmso-tab-anexos" class="space-y-4 hidden">
                    <p class="text-xs text-slate-600">
                        Anexe aqui documentos relacionados ao PCMSO (PDF, DOC, DOCX).
                        Você pode arrastar e soltar ou clicar na área abaixo.
                    </p>

                    {{-- Dropzone --}}
                    <div id="pcmso-dropzone-anexos"
                         class="flex flex-col items-center justify-center px-6 py-10 border-2 border-dashed rounded-2xl
                border-slate-300 bg-slate-50 text-center cursor-pointer
                hover:border-sky-400 hover:bg-sky-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M3 15.75V18a3 3 0 003 3h12a3 3 0 003-3v-2.25M16.5 9.75L12 5.25m0 0L7.5 9.75M12 5.25V15"/>
                        </svg>
                        <p class="text-sm text-slate-700">
                            Arraste arquivos aqui
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1">
                            ou clique para selecionar
                        </p>

                        <input id="pcmso-input-anexos"
                               type="file"
                               name="anexos[]"
                               multiple
                               accept=".pdf,.doc,.docx"
                               class="hidden">
                    </div>

                    {{-- Lista de arquivos selecionados (novos) --}}
                    <ul id="pcmso-lista-anexos" class="mt-3 text-xs text-slate-600 space-y-1"></ul>

                    {{-- Anexos já salvos --}}
                    @if($isEdit)
                        <div class="mt-6">
                            <h3 class="text-sm font-semibold text-slate-800 mb-3">
                                Anexos desta tarefa
                            </h3>

                            @if($anexos->isEmpty())
                                <p class="text-xs text-slate-400">
                                    Nenhum anexo cadastrado ainda.
                                </p>
                            @else
                                <ul class="divide-y divide-slate-100 border border-slate-200 rounded-2xl overflow-hidden">
                                    @foreach($anexos as $anexo)
                                        @php
                                            $ext = strtolower(pathinfo($anexo->nome_original, PATHINFO_EXTENSION));

                                            $iconClasses = match($ext) {
                                                'pdf'          => 'bg-red-100 text-red-600',
                                                'doc', 'docx'  => 'bg-blue-100 text-blue-600',
                                                default        => 'bg-slate-100 text-slate-600',
                                            };

                                            $sizeKb = $anexo->tamanho
                                                ? round($anexo->tamanho / 1024, 1)
                                                : null;
                                        @endphp

                                        <li class="flex items-center justify-between px-4 py-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div
                                                    class="h-9 w-9 rounded-xl flex items-center justify-center text-[11px] font-semibold {{ $iconClasses }}">
                                                    {{ strtoupper($ext ?: 'ARQ') }}
                                                </div>

                                                <div class="min-w-0">
                                                    <p class="text-sm text-slate-800 truncate max-w-xs">
                                                        {{ $anexo->nome_original }}
                                                    </p>
                                                    <p class="text-[11px] text-slate-400">
                                                        @if($sizeKb)
                                                            {{ number_format($sizeKb, 1, ',', '.') }} KB ·
                                                        @endif
                                                        Enviado em {{ $anexo->created_at?->format('d/m/Y H:i') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('operacional.anexos.view', $anexo) }}"
                                                   target="_blank"
                                                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium
                                          border border-slate-200 text-slate-700 hover:bg-slate-50">
                                                    Ver
                                                </a>

                                                <a href="{{ route('operacional.anexos.download', $anexo) }}"
                                                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium
                                          bg-sky-500 text-white hover:bg-sky-600">
                                                    Download
                                                </a>

                                                {{-- Botão lixeira (excluir) --}}
                                                <button type="button"
                                                        class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-red-100
                                               text-red-500 hover:bg-red-50 text-xs"
                                                        title="Excluir anexo"
                                                        data-delete-anexo="{{ route('operacional.anexos.destroy', $anexo) }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                         viewBox="0 0 24 24"
                                                         fill="none"
                                                         stroke="currentColor"
                                                         stroke-width="1.7"
                                                         class="w-4 h-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              d="M9.75 9.75v6.75M14.25 9.75v6.75M4.5 6.75h15M18.75 6.75
                                                 l-.861 12.067A2.25 2.25 0 0 1 15.648 21H8.352a2.25 2.25 0 0 1-2.241-2.183L5.25 6.75M9 6.75V4.5
                                                 A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5v2.25"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    <div class="mt-4">
                        <button type="submit"
                                class="w-full px-6 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium shadow-sm">
                            {{ $isEdit ? 'Salvar alterações' : 'Criar Tarefa PCMSO' }}
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // ----- TABS -----
                const tabButtons = document.querySelectorAll('.pcmso-tab-btn');
                const tabDados = document.getElementById('pcmso-tab-dados');
                const tabAnexos = document.getElementById('pcmso-tab-anexos');

                tabButtons.forEach(btn => {
                    btn.addEventListener('click', function () {
                        const tab = this.dataset.tab;

                        if (tab === 'dados') {
                            tabDados.classList.remove('hidden');
                            tabAnexos.classList.add('hidden');
                        } else {
                            tabAnexos.classList.remove('hidden');
                            tabDados.classList.add('hidden');
                        }

                        tabButtons.forEach(b => {
                            b.classList.remove('border-b-2', 'border-sky-500', 'text-sky-600', 'font-semibold');
                            b.classList.add('text-slate-500');
                        });

                        this.classList.remove('text-slate-500');
                        this.classList.add('border-b-2', 'border-sky-500', 'text-sky-600', 'font-semibold');
                    });
                });

                // ----- FUNCOES DINAMICAS -----
                const funcoesWrapper = document.getElementById('pcmso-funcoes-wrapper');
                const btnAddFuncao = document.getElementById('pcmso-btn-add-funcao');

                if (funcoesWrapper && btnAddFuncao) {
                    btnAddFuncao.addEventListener('click', function () {
                        const itens = funcoesWrapper.querySelectorAll('.funcao-item');
                        const novoIndex = itens.length;

                        const base = itens[itens.length - 1];
                        const clone = base.cloneNode(true);

                        clone.dataset.funcaoIndex = String(novoIndex);

                        clone.querySelectorAll('input, select').forEach(function (el) {
                            if (el.name && el.name.includes('funcoes[')) {
                                el.name = el.name.replace(/\[\d+]/, '[' + novoIndex + ']');
                            }

                            if (el.tagName === 'SELECT') {
                                el.value = '';
                            } else if (el.name.includes('[quantidade]')) {
                                el.value = '1';
                            } else {
                                el.value = '';
                            }

                            if (el.id && el.id.startsWith('funcoes_')) {
                                el.id = el.id.replace(/_\d+_funcao_id$/, '_' + novoIndex + '_funcao_id');
                            }
                        });

                        const badge = clone.querySelector('.badge-funcao');
                        if (badge) {
                            badge.textContent = 'Funcao ' + (novoIndex + 1);
                        }

                        funcoesWrapper.appendChild(clone);
                    });

                    funcoesWrapper.addEventListener('click', function (e) {
                        const btn = e.target.closest('.btn-remove-funcao');
                        if (!btn) return;

                        const itens = funcoesWrapper.querySelectorAll('.funcao-item');
                        if (itens.length <= 1) {
                            window.uiAlert('E necessario pelo menos uma funcao.');
                            return;
                        }

                        const item = btn.closest('.funcao-item');
                        if (item) {
                            item.remove();
                            reindexFuncoes(funcoesWrapper);
                        }
                    });
                }

                function reindexFuncoes(wrapper) {
                    const itens = wrapper.querySelectorAll('.funcao-item');

                    itens.forEach((item, idx) => {
                        item.dataset.funcaoIndex = String(idx);

                        const badge = item.querySelector('.badge-funcao');
                        if (badge) {
                            badge.textContent = 'Funcao ' + (idx + 1);
                        }

                        item.querySelectorAll('input, select').forEach(function (el) {
                            if (el.name && el.name.includes('funcoes[')) {
                                el.name = el.name.replace(/funcoes\[\d+]/, 'funcoes[' + idx + ']');
                            }

                            if (el.id && /^funcoes_\d+_funcao_id$/.test(el.id)) {
                                el.id = 'funcoes_' + idx + '_funcao_id';
                            }
                        });
                    });
                }

                // ----- DROPZONE -----
                const dropzone = document.getElementById('pcmso-dropzone-anexos');
                const inputFiles = document.getElementById('pcmso-input-anexos');
                const lista = document.getElementById('pcmso-lista-anexos');

                if (dropzone && inputFiles && lista) {
                    function atualizarLista() {
                        lista.innerHTML = '';
                        if (!inputFiles.files.length) {
                            const li = document.createElement('li');
                            li.textContent = 'Nenhum arquivo selecionado.';
                            lista.appendChild(li);
                            return;
                        }
                        Array.from(inputFiles.files).forEach(file => {
                            const li = document.createElement('li');
                            li.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                            lista.appendChild(li);
                        });
                    }

                    dropzone.addEventListener('click', () => inputFiles.click());
                    inputFiles.addEventListener('change', atualizarLista);

                    dropzone.addEventListener('dragover', e => {
                        e.preventDefault();
                        dropzone.classList.add('border-sky-400', 'bg-sky-50');
                    });

                    dropzone.addEventListener('dragleave', e => {
                        e.preventDefault();
                        dropzone.classList.remove('border-sky-400', 'bg-sky-50');
                    });

                    dropzone.addEventListener('drop', e => {
                        e.preventDefault();
                        dropzone.classList.remove('border-sky-400', 'bg-sky-50');
                        if (!e.dataTransfer.files.length) return;
                        inputFiles.files = e.dataTransfer.files;
                        atualizarLista();
                    });

                    atualizarLista();
                }

                // ----- DELETE ANEXO -----
                document.querySelectorAll('[data-delete-anexo]').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const url = this.dataset.deleteAnexo;
                        if (!url) return;

                        const ok = await window.uiConfirm('Confirma excluir este anexo?');
                        if (!ok) return;

                        let form = document.getElementById('form-delete-anexo');
                        if (!form) {
                            form = document.createElement('form');
                            form.id = 'form-delete-anexo';
                            form.method = 'POST';
                            form.style.display = 'none';
                            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                            form.innerHTML = `
                                <input type="hidden" name="_token" value="${token}">
                                <input type="hidden" name="_method" value="DELETE">
                            `;
                            document.body.appendChild(form);
                        }

                        form.action = url;
                        form.submit();
                    });
                });
            });
        </script>
    @endpush
@endsection
