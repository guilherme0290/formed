@props([
    'label'   => 'Cadastrar nova função',
    // emerald (default) | orange | sky | slate...
    'variant' => 'emerald',
    // permite sobrescrever a rota se precisar
    'route'   => route('operacional.funcoes.store-ajax'),
    'allowCreate' => true,
])

@php
    $baseBtn = 'inline-flex items-center gap-1 px-3 py-1.5 rounded-lg
                text-white text-xs font-semibold shadow-sm
                focus:outline-none focus:ring-2 focus:ring-offset-1';

    $variantBtn = match ($variant) {
        'orange' => 'bg-orange-500 hover:bg-orange-600 focus:ring-orange-400',
        'sky'    => 'bg-sky-500 hover:bg-sky-600 focus:ring-sky-400',
        'slate'  => 'bg-slate-700 hover:bg-slate-800 focus:ring-slate-500',
        'red'    => 'bg-red-600 hover:bg-red-700 focus:ring-red-400',
        default  => 'bg-emerald-500 hover:bg-emerald-600 focus:ring-emerald-400',
    };

    $btnOpenClasses = $baseBtn.' '.$variantBtn;

    $baseSave = 'px-4 py-2 text-xs rounded-lg text-white font-semibold disabled:opacity-60';

    $variantSave = match ($variant) {
        'orange' => 'bg-orange-500 hover:bg-orange-600',
        'sky'    => 'bg-sky-500 hover:bg-sky-600',
        'slate'  => 'bg-slate-700 hover:bg-slate-800',
        'red'    => 'bg-red-600 hover:bg-red-700',
        default  => 'bg-emerald-600 hover:bg-emerald-700',
    };

    $btnSaveClasses = $baseSave.' '.$variantSave;
@endphp


<div class="inline-flex flex-col gap-2"
     data-funcao-create-wrapper
     data-funcao-route="{{ $route }}"
     data-funcao-csrf="{{ csrf_token() }}">

    @if ($allowCreate)
        {{-- Botão principal --}}
        <button type="button"
                class="{{ $btnOpenClasses }}"
                data-funcao-open>
            <span class="text-base leading-none">+</span>
            <span class="leading-none">
                {{ $label }}
            </span>
        </button>

        {{-- Modal (JS puro) --}}
        <div class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/50 p-4 overflow-y-auto"
             data-overlay-root="true"
             data-funcao-modal>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
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
                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
                           placeholder="Ex: Eletricista, Pedreiro, Operador de Máquinas">
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button"
                            class="px-3 py-2 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                            data-funcao-cancel>
                        Cancelar
                    </button>
                    <button type="button"
                            class="{{ $btnSaveClasses }}"
                            data-funcao-save>
                        Salvar Função
                    </button>
                </div>
            </div>
        </div>
    @else
        <button type="button"
                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-100 text-slate-400 text-xs font-semibold cursor-not-allowed"
                disabled
                title="Fale com seu comercial para adicionar uma nova função">
            <span class="text-base leading-none">+</span>
            <span class="leading-none">
                {{ $label }}
            </span>
        </button>
    @endif
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (!window.showFuncaoFeedbackPopup) {
                    window.showFuncaoFeedbackPopup = function (message, type = 'success') {
                        const popup = document.createElement('div');
                        const success = type === 'success';
                        popup.className = [
                            'fixed z-[9999] left-1/2 top-6 -translate-x-1/2',
                            'rounded-xl border px-4 py-3 text-sm font-semibold shadow-xl',
                            'transition-all duration-300 opacity-0 -translate-y-2',
                            success
                                ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                                : 'bg-red-50 border-red-200 text-red-700'
                        ].join(' ');
                        popup.textContent = message;

                        document.body.appendChild(popup);
                        requestAnimationFrame(function () {
                            popup.classList.remove('opacity-0', '-translate-y-2');
                        });

                        setTimeout(function () {
                            popup.classList.add('opacity-0', '-translate-y-2');
                            setTimeout(function () {
                                popup.remove();
                            }, 250);
                        }, 2800);
                    };
                }

                document.querySelectorAll('[data-funcao-create-wrapper]').forEach(function (wrapper) {
                    const route      = wrapper.getAttribute('data-funcao-route');
                    const csrfToken  = wrapper.getAttribute('data-funcao-csrf');

                    const btnOpen    = wrapper.querySelector('[data-funcao-open]');
                    const modal      = wrapper.querySelector('[data-funcao-modal]');
                    const inputNome  = wrapper.querySelector('[data-funcao-input]');
                    const btnCancel  = wrapper.querySelector('[data-funcao-cancel]');
                    const btnSave    = wrapper.querySelector('[data-funcao-save]');
                    const erroModal  = wrapper.querySelector('[data-funcao-error-modal]');

                    if (!btnOpen || !modal || !inputNome || !btnCancel || !btnSave) {
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

                    btnOpen.addEventListener('click', abrirModal);
                    btnCancel.addEventListener('click', fecharModal);

                    // fecha clicando no fundo
                    modal.addEventListener('click', function (e) {
                        if (e.target === modal) {
                            fecharModal();
                        }
                    });

                    btnSave.addEventListener('click', function () {
                        const nome = (inputNome.value || '').trim();
                        if (!nome) {
                            window.showFuncaoFeedbackPopup('Informe o nome da função.', 'error');
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
                                    window.showFuncaoFeedbackPopup(msg, 'error');
                                    return;
                                }

                                // Atualiza TODAS as selects de função da tela
                                const selects = document.querySelectorAll('select[name^="funcoes"][name$="[funcao_id]"]');

                                selects.forEach(function (select) {
                                    const jaExiste = Array.from(select.options).some(function (opt) {
                                        return String(opt.value) === String(json.id);
                                    });

                                    if (!jaExiste) {
                                        const opt = document.createElement('option');
                                        opt.value = json.id;
                                        opt.textContent = json.nome;
                                        select.appendChild(opt);
                                    }
                                });
                                window.showFuncaoFeedbackPopup(
                                    json.existing
                                        ? 'Função já cadastrada e selecionada com sucesso.'
                                        : 'Função cadastrada com sucesso.',
                                    'success'
                                );

                                fecharModal();
                            })
                            .catch(() => {
                                window.showFuncaoFeedbackPopup('Erro na comunicação com o servidor.', 'error');
                            })
                            .finally(() => {
                                btnSave.disabled = false;
                            });
                    });
                });
            });
        </script>
    @endpush
@endonce
