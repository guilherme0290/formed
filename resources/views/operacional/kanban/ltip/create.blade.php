@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')


@section('pageTitle', 'LTIP - Insalubridade e Periculosidade')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('operacional.kanban.servicos', $cliente) }}"
               class="inline-flex items-center gap-2 text-xs text-slate-600 mb-4">
                ← Voltar
            </a>
        </div>

        <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            {{-- Cabeçalho --}}
            <div class="px-6 py-4 bg-gradient-to-r from-red-700 to-red-600 text-white">
                <h1 class="text-lg font-semibold">
                    LTIP - Insalubridade e Periculosidade {{ !empty($isEdit) ? '(Editar)' : '' }}
                </h1>
                <p class="text-xs text-white/80 mt-1">
                    {{ $cliente->razao_social }}
                </p>
            </div>

            <form method="POST"
                  action="{{ !empty($isEdit) && $ltip
                        ? route('operacional.ltip.update', $ltip)
                        : route('operacional.ltip.store', $cliente) }}"
                  class="p-6 space-y-6">
                @csrf
                @if(!empty($isEdit) && $ltip)
                    @method('PUT')
                @endif

                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700">
                        <ul class="list-disc ms-4">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Endereço --}}
                <section class="space-y-2">
                    <h2 class="text-sm font-semibold text-slate-800">
                        Endereço onde as avaliações serão realizadas *
                    </h2>
                    <input type="text"
                           name="endereco_avaliacoes"
                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                           placeholder="Endereço completo"
                           value="{{ old('endereco_avaliacoes', $ltip->endereco_avaliacoes ?? '') }}">
                </section>

                {{-- Funções e Quantidades --}}
                <section>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-slate-800">Funções e Quantidades</h2>

                        <div class="flex items-center gap-3">
                            {{-- Botão cadastrar nova função (modal) --}}
                            <x-funcoes.create-button
                                label="Cadastrar nova função"
                                variant="red"
                            />

                            {{-- Botão adicionar linha de função --}}
                            <button type="button" id="ltip-btn-add-funcao"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-600
                           text-white text-xs font-semibold hover:bg-red-700">
                                <span>+</span>
                                <span>Adicionar função</span>
                            </button>
                        </div>
                    </div>

                    <div id="ltip-funcoes-wrapper" class="space-y-3">
                        @foreach($funcoesForm as $idx => $f)
                            <div class="ltip-funcao-item rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-center justify-between mb-2">
                    <span data-role="ltip-funcao-badge"
                          class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-semibold">
                        Função {{ $idx + 1 }}
                    </span>

                                    {{-- Botão remover função --}}
                                    <button type="button"
                                            class="ltip-btn-remove-funcao inline-flex items-center gap-1 px-2 py-0.5
                                   rounded-full text-[11px] border border-red-200 text-red-600
                                   hover:bg-red-50">
                                        ✕ Remover
                                    </button>
                                </div>

                                <div class="grid grid-cols-12 gap-3 items-end">
                                    <div class="col-span-8">
                                        <x-funcoes.select-with-create
                                            name="funcoes[{{ $idx }}][funcao_id]"
                                            field-id="funcoes_{{ $idx }}_funcao_id"
                                            label="Função"
                                            :funcoes="$funcoes"
                                            :selected="old('funcoes.'.$idx.'.funcao_id', $f['funcao_id'] ?? null)"
                                            :show-create="false"
                                        />
                                    </div>

                                    <div class="col-span-4">
                                        <label class="block text-xs font-medium text-slate-500 mb-1">
                                            Quantidade
                                        </label>
                                        <input type="number"
                                               name="funcoes[{{ $idx }}][quantidade]"
                                               class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 ltip-qtd-input"
                                               value="{{ old('funcoes.'.$idx.'.quantidade', $f['quantidade'] ?? 1) }}"
                                               min="1">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('funcoes')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </section>


                {{-- Footer --}}
                <div class="pt-4 border-t border-slate-100 mt-4">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700">
                        {{ !empty($isEdit) ? 'Salvar alterações' : 'Criar Tarefa LTIP' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const wrapper = document.getElementById('ltip-funcoes-wrapper');
                const btnAdd  = document.getElementById('ltip-btn-add-funcao');

                if (!wrapper || !btnAdd) return;

                function renumerarFuncoes() {
                    const itens      = wrapper.querySelectorAll('.ltip-funcao-item');
                    const podeRemover = itens.length > 1;

                    itens.forEach((item, index) => {
                        // Badge "Função X"
                        const badge = item.querySelector('[data-role="ltip-funcao-badge"]');
                        if (badge) {
                            badge.textContent = 'Função ' + (index + 1);
                        }

                        // Mostra/oculta botão remover conforme quantidade
                        const btnRemove = item.querySelector('.ltip-btn-remove-funcao');
                        if (btnRemove) {
                            btnRemove.style.display = podeRemover ? 'inline-flex' : 'none';
                        }

                        // Reindexa names e ids
                        item.querySelectorAll('select, input').forEach(el => {
                            if (el.name && el.name.includes('funcoes[')) {
                                el.name = el.name.replace(/funcoes\[\d+]/, 'funcoes[' + index + ']');
                            }

                            if (el.id && /^funcoes_\d+_funcao_id$/.test(el.id)) {
                                el.id = 'funcoes_' + index + '_funcao_id';
                            }
                        });
                    });
                }

                // Clique em "Adicionar Função"
                btnAdd.addEventListener('click', function () {
                    const itens = wrapper.querySelectorAll('.ltip-funcao-item');
                    if (itens.length === 0) return;

                    const index = itens.length;
                    const base  = itens[0];
                    const clone = base.cloneNode(true);

                    // Limpa e reindexa selects
                    clone.querySelectorAll('select').forEach(select => {
                        if (select.name && select.name.includes('funcoes[')) {
                            select.name = select.name.replace(/\[\d+]/, '[' + index + ']');
                        }
                        if (select.id && select.id.startsWith('funcoes_')) {
                            select.id = select.id.replace(/_\d+_funcao_id$/, '_' + index + '_funcao_id');
                        }
                        select.value = '';
                    });

                    // Limpa e reindexa inputs
                    clone.querySelectorAll('input').forEach(input => {
                        if (input.name && input.name.includes('funcoes[')) {
                            input.name = input.name.replace(/\[\d+]/, '[' + index + ']');
                        }

                        if (input.classList.contains('ltip-qtd-input') || input.name.includes('[quantidade]')) {
                            input.value = 1;
                        } else {
                            input.value = '';
                        }
                    });

                    wrapper.appendChild(clone);
                    renumerarFuncoes();
                });

                // Clique em "Remover" (delegação)
                wrapper.addEventListener('click', function (e) {
                    const btn = e.target.closest('.ltip-btn-remove-funcao');
                    if (!btn) return;

                    const item  = btn.closest('.ltip-funcao-item');
                    const itens = wrapper.querySelectorAll('.ltip-funcao-item');
                    if (!item || itens.length <= 1) return; // nunca deixa zerar

                    item.remove();
                    renumerarFuncoes();
                });

                // Inicializa estado (badge + visibilidade do remover)
                renumerarFuncoes();
            });
        </script>
    @endpush
@endsection
