@extends('layouts.operacional')

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

                        <button type="button" id="ltip-btn-add-funcao"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-sky-600 text-white text-xs font-semibold hover:bg-sky-700">
                            <span>+</span> <span>Adicionar Função</span>
                        </button>
                    </div>

                    <div id="ltip-funcoes-wrapper" class="space-y-3">
                        @foreach($funcoesForm as $idx => $f)
                            <div class="ltip-funcao-item rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span data-role="ltip-funcao-badge"
                                          class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-semibold">
                                        Função {{ $idx + 1 }}
                                    </span>
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

                btnAdd.addEventListener('click', function () {
                    const itens  = wrapper.querySelectorAll('.ltip-funcao-item');
                    const index  = itens.length;

                    const base   = itens[0];
                    const clone  = base.cloneNode(true);

                    // SELECTS
                    clone.querySelectorAll('select').forEach(select => {
                        if (select.name && select.name.includes('funcoes[')) {
                            select.name = select.name.replace(/\[\d+]/, '[' + index + ']');
                        }
                        if (select.id && select.id.startsWith('funcoes_')) {
                            select.id = select.id.replace(/_\d+_funcao_id$/, '_' + index + '_funcao_id');
                        }
                        select.value = '';
                    });

                    // INPUTS
                    clone.querySelectorAll('input').forEach(input => {
                        if (input.name && input.name.includes('funcoes[')) {
                            input.name = input.name.replace(/\[\d+]/, '[' + index + ']');
                        }

                        if (input.name.includes('[quantidade]')) {
                            input.value = 1;
                        } else {
                            input.value = '';
                        }
                    });

                    // Badge
                    const badge = clone.querySelector('[data-role="ltip-funcao-badge"]');
                    if (badge) {
                        badge.textContent = 'Função ' + (index + 1);
                    }

                    wrapper.appendChild(clone);
                });
            });
        </script>
    @endpush
@endsection
