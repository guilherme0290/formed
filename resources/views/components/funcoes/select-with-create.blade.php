{{-- resources/views/components/funcoes/select-with-create.blade.php --}}
@props([
    'name'           => 'funcao_id',
    'fieldId'        => null,
    'label'          => 'Função',
    'funcoes'        => [],
    'selected'       => null,
    'modalId'        => 'modal-create-funcao',
    'showCreate'     => true,      // permite esconder o botão "+" em telas dinâmicas
    'disabled'       => false,     // desabilitar via Blade (true/false)
    'alpineDisabled' => null,      // desabilitar via Alpine: ex. "desabilitarFuncao"
])

@php
    // se não vier um id explícito, gera um id a partir do name
    $fieldId = $fieldId ?: str_replace(['[', ']'], '_', $name);
@endphp

<div class="space-y-1">
    {{-- Label --}}
    <label for="{{ $fieldId }}" class="block text-xs font-medium text-slate-600">
        {{ $label }}
    </label>

    {{-- Select + botão "+" inline --}}
    <div class="flex items-center gap-2">
        <select
            id="{{ $fieldId }}"
            name="{{ $name }}"
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
            <button
                type="button"
                @if($disabled) disabled @endif
                @if($alpineDisabled) x-bind:disabled="{{ $alpineDisabled }}" @endif
                class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-slate-200
                       bg-white text-slate-600 text-lg leading-none
                       hover:bg-slate-100 hover:border-slate-300 disabled:opacity-60 disabled:cursor-not-allowed"
                data-funcao-open-modal="{{ $modalId }}"
                data-funcao-target="{{ $fieldId }}"
                title="Nova função"
            >
                +
            </button>
        @endif
    </div>
</div>

{{-- Modal genérico para criação de Função --}}
<div
    x-data="{ open: false, nome: '', salvando: false, erro: '', target: null }"
    x-on:open-funcao-modal.window="
        if ($event.detail.modalId === '{{ $modalId }}') {
            open  = true;
            erro  = '';
            nome  = '';
            target = $event.detail.targetId || null;
        }
    "
    x-show="open"
    style="display: none;"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">
            Nova Função
        </h2>

        <template x-if="erro">
            <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700" x-text="erro"></div>
        </template>

        <div class="space-y-2 mb-4">
            <label class="block text-xs font-medium text-slate-600">
                Nome da Função
            </label>
            <input type="text"
                   x-model="nome"
                   class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                   placeholder="Ex: Eletricista, Pedreiro, Operador de Máquinas">
        </div>

        <div class="flex justify-end gap-2">
            <button type="button"
                    class="px-3 py-2 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                    @click="open = false">
                Cancelar
            </button>
            <button type="button"
                    class="px-4 py-2 text-xs rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 disabled:opacity-60"
                    :disabled="salvando || !nome.trim()"
                    @click.prevent="
                        salvando = true;
                        erro = '';
                        fetch('{{ route('operacional.funcoes.store-ajax') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({ nome: nome })
                        })
                        .then(r => r.json())
                        .then(json => {
                            if (!json.ok) {
                                erro = json.message || 'Não foi possível salvar a função.';
                                return;
                            }

                            if (target) {
                                const select = document.getElementById(target);
                                if (select) {
                                    const opt = document.createElement('option');
                                    opt.value = json.id;
                                    opt.textContent = json.nome;
                                    select.appendChild(opt);
                                    select.value = json.id;
                                }
                            }

                            open = false;
                        })
                        .catch(() => {
                            erro = 'Erro na comunicação com o servidor.';
                        })
                        .finally(() => {
                            salvando = false;
                        });
                    ">
                Salvar Função
            </button>
        </div>
    </div>
</div>
