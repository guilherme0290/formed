{{-- resources/views/components/funcoes/select-with-create.blade.php --}}

@props([
    'name'           => 'funcao_id',
    'fieldId'        => null,
    'label'          => 'Função',
    'helpText'       => null,
    'funcoes'        => [],
    'selected'       => null,
    'modalId'        => 'modal-create-funcao',
    'showCreate'     => true,      // permite esconder o botão "+" em telas dinâmicas
    'allowCreate'    => true,      // desabilita criação (cliente/operacional)
    'disabled'       => false,     // desabilitar via Blade (true/false)
    'alpineDisabled' => null,      // mantém compatibilidade se já estiver usando em outras telas
])

@php
    // se não vier um id explícito, gera um id a partir do name
    $fieldId = $fieldId ?: str_replace(['[', ']'], '_', $name);
@endphp

<div class="space-y-1"
     data-funcao-select-wrapper
     data-funcao-route="{{ route('operacional.funcoes.store-ajax') }}"
     data-funcao-csrf="{{ csrf_token() }}">

    {{-- Label --}}
    <label for="{{ $fieldId }}" class="flex items-center gap-2 text-xs font-medium text-slate-600">
        <span>{{ $label }}</span>
        @if ($helpText)
            <span class="relative inline-flex items-center">
                <span class="group inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-100 text-[10px] text-slate-500 cursor-help">
                    ?
                    <span class="pointer-events-none absolute left-1/2 top-full z-10 mt-2 w-60 -translate-x-1/2 rounded-lg bg-slate-900 px-2.5 py-2 text-[11px] text-white opacity-0 shadow-lg transition group-hover:opacity-100">
                        {{ $helpText }}
                    </span>
                </span>
            </span>
        @endif
    </label>

    {{-- Select + botão "+" inline --}}
    <div class="flex items-center gap-2">
        <select
            id="{{ $fieldId }}"
            name="{{ $name }}"
            data-funcao-select
            @if($disabled) disabled @endif
            @if($alpineDisabled) x-bind:disabled="{{ $alpineDisabled }}" @endif
            class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                   focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
        >
            <option value="">Selecione a função</option>

            @foreach ($funcoes as $funcao)
                <option value="{{ $funcao->id }}" @selected($selected == $funcao->id)>
                    {{ $funcao->nome }}
                </option>
            @endforeach
        </select>

        @if ($showCreate)
            {{-- Botão "+" (abre modal de nova função) --}}
            @if ($allowCreate)
                <button
                    type="button"
                    data-funcao-open-modal
                    @if($disabled) disabled @endif
                    @if($alpineDisabled) x-bind:disabled="{{ $alpineDisabled }}" @endif
                    class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-slate-200
                           bg-white text-slate-600 text-lg leading-none
                           hover:bg-slate-100 hover:border-slate-300 disabled:opacity-60 disabled:cursor-not-allowed"
                    title="Nova função"
                >
                    +
                </button>
            @else
                <button
                    type="button"
                    disabled
                    class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-slate-200
                           bg-slate-100 text-slate-400 text-lg leading-none cursor-not-allowed"
                    title="Fale com seu comercial para adicionar uma nova função"
                >
                    +
                </button>
            @endif
        @endif
    </div>

    {{-- Modal genérico para criação de Função (JS puro) --}}
    @if ($allowCreate)
        <div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40"
             data-funcao-modal>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h2 class="text-sm font-semibold text-slate-800 mb-3">
                    Nova Função
                </h2>

                <div class="hidden mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700"
                     data-funcao-error-modal>
                </div>

                <div class="space-y-2 mb-4">
                    <label class="block text-xs font-medium text-slate-600">
                        Nome da Função
                    </label>
                    <input type="text"
                           data-funcao-input
                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                           placeholder="Ex: Eletricista, Pedreiro, Operador de Máquinas">
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button"
                            class="px-3 py-2 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                            data-funcao-cancel>
                        Cancelar
                    </button>
                    <button type="button"
                            class="px-4 py-2 text-xs rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 disabled:opacity-60"
                            data-funcao-save>
                        Salvar Função
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-funcao-select-wrapper]').forEach(function (wrapper) {
                    const route     = wrapper.getAttribute('data-funcao-route');
                    const csrfToken = wrapper.getAttribute('data-funcao-csrf');

                    const select    = wrapper.querySelector('[data-funcao-select]');
                    const btnOpen   = wrapper.querySelector('[data-funcao-open-modal]');
                    const modal     = wrapper.querySelector('[data-funcao-modal]');
                    const inputNome = wrapper.querySelector('[data-funcao-input]');
                    const btnCancel = wrapper.querySelector('[data-funcao-cancel]');
                    const btnSave   = wrapper.querySelector('[data-funcao-save]');
                    const erroModal = wrapper.querySelector('[data-funcao-error-modal]');

                    if (!route || !csrfToken || !select || !btnOpen || !modal || !inputNome || !btnCancel || !btnSave) {
                        return;
                    }

                    function abrirModal() {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');

                        if (erroModal) {
                            erroModal.textContent = '';
                            erroModal.classList.add('hidden');
                        }

                        inputNome.value = '';
                        inputNome.focus();
                    }

                    function fecharModal() {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }

                    btnOpen.addEventListener('click', function () {
                        if (btnOpen.disabled) return;
                        abrirModal();
                    });

                    btnCancel.addEventListener('click', fecharModal);

                    // fecha clicando no fundo escuro
                    modal.addEventListener('click', function (e) {
                        if (e.target === modal) {
                            fecharModal();
                        }
                    });

                    btnSave.addEventListener('click', function () {
                        const nome = (inputNome.value || '').trim();

                        if (!nome) {
                            if (erroModal) {
                                erroModal.textContent = 'Informe o nome da função.';
                                erroModal.classList.remove('hidden');
                            }
                            inputNome.focus();
                            return;
                        }

                        btnSave.disabled = true;
                        if (erroModal) {
                            erroModal.textContent = '';
                            erroModal.classList.add('hidden');
                        }

                        fetch(route, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ nome: nome })
                        })
                            .then(r => r.json())
                            .then(json => {
                                if (!json.ok) {
                                    const msg = json.message || 'Não foi possível salvar a função.';
                                    if (erroModal) {
                                        erroModal.textContent = msg;
                                        erroModal.classList.remove('hidden');
                                    }
                                    return;
                                }

                                // Cria <option> no select deste componente e já seleciona
                                let opt = Array.from(select.options).find(function (o) {
                                    return String(o.value) === String(json.id);
                                });

                                if (!opt) {
                                    opt = document.createElement('option');
                                    opt.value = json.id;
                                    opt.textContent = json.nome;
                                    select.appendChild(opt);
                                }

                                select.value = json.id;
                                // dispara evento change pra quem estiver ouvindo
                                select.dispatchEvent(new Event('change'));

                                fecharModal();
                            })
                            .catch(function () {
                                const msg = 'Erro na comunicação com o servidor.';
                                if (erroModal) {
                                    erroModal.textContent = msg;
                                    erroModal.classList.remove('hidden');
                                }
                            })
                            .finally(function () {
                                btnSave.disabled = false;
                            });
                    });
                });
            });
        </script>
    @endpush
@endonce
