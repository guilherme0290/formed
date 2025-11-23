@extends('layouts.operacional')

@section('pageTitle', 'LTIP - Insalubridade e Periculosidade')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('operacional.painel') }}"
               class="inline-flex items-center gap-2 text-xs text-slate-600 mb-4">
                ← Voltar ao Painel
            </a>
        </div>

        <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            {{-- Cabeçalho --}}
            <div class="px-6 py-4 bg-gradient-to-r from-red-700 to-red-600 text-white">
                <h1 class="text-lg font-semibold">
                    LTIP - Insalubridade e Periculosidade
                </h1>
                <p class="text-xs text-white/80 mt-1">
                    {{ $cliente->razao_social }}
                </p>
            </div>

            <form method="POST"
                  action="{{ route('operacional.ltip.store', $cliente) }}"
                  class="p-6 space-y-6">
                @csrf

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
                           value="{{ old('endereco_avaliacoes') }}">
                </section>

                {{-- Funções e Quantidades --}}
                <section>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-slate-800">Funções e Quantidades</h2>

                        <button type="button" id="ltip-btn-add-funcao"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700">
                            <span>+</span>
                            <span>Adicionar Função</span>
                        </button>
                    </div>

                    <div id="ltip-funcoes-wrapper" class="space-y-3">
                        {{-- linha base --}}
                        <div class="ltip-funcao-item rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <span data-role="ltip-funcao-badge"
                                      class="text-[11px] px-2 py-0.5 rounded-full bg-slate-800 text-white font-semibold">
                                    Função 1
                                </span>
                            </div>

                            <div class="grid grid-cols-12 gap-3 items-end">
                                <div class="col-span-8">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">
                                        Nome da Função
                                    </label>
                                    <input type="text"
                                           name="funcoes[0][nome]"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                           placeholder="Ex: Operador de Máquinas"
                                           value="{{ old('funcoes.0.nome') }}">
                                </div>

                                <div class="col-span-4">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">
                                        Quantidade
                                    </label>
                                    <input type="number"
                                           name="funcoes[0][quantidade]"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 ltip-qtd-input"
                                           value="{{ old('funcoes.0.quantidade', 1) }}"
                                           min="1">
                                </div>
                            </div>
                        </div>
                    </div>

                    @error('funcoes')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    {{-- Total de funcionários --}}
                    <div class="mt-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] bg-red-100 text-red-700 font-semibold">
                            Total: <span id="ltip-total-funcionarios" class="ml-1">1</span> Funcionários
                        </span>
                    </div>
                </section>

                {{-- Footer --}}
                <div class="pt-4 border-t border-slate-100 mt-4">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700">
                        Criar Tarefa LTIP
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Script LTIP --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wrapper = document.getElementById('ltip-funcoes-wrapper');
            const btnAdd  = document.getElementById('ltip-btn-add-funcao');
            const totalEl = document.getElementById('ltip-total-funcionarios');

            function atualizarTotal() {
                const itens = wrapper.querySelectorAll('.ltip-funcao-item');
                let total = 0;

                itens.forEach(function (item) {
                    const input = item.querySelector('.ltip-qtd-input');
                    const qtd = parseInt(input?.value || '0', 10);
                    if (!isNaN(qtd)) {
                        total += qtd;
                    }
                });

                totalEl.textContent = total.toString();
            }

            // Atualiza ao digitar quantidade
            wrapper.addEventListener('input', function (e) {
                if (e.target.classList.contains('ltip-qtd-input')) {
                    atualizarTotal();
                }
            });

            btnAdd.addEventListener('click', function () {
                const itens = wrapper.querySelectorAll('.ltip-funcao-item');
                const index = itens.length;
                const base  = itens[0];

                const clone = base.cloneNode(true);

                // Limpa valores
                clone.querySelectorAll('input').forEach(function (input) {
                    if (input.name.includes('[nome]')) {
                        input.value = '';
                    } else if (input.name.includes('[quantidade]')) {
                        input.value = '1';
                    }
                });

                // Ajusta names pelo índice
                clone.querySelectorAll('input').forEach(function (input) {
                    input.name = input.name.replace(/\[\d+]/, '[' + index + ']');
                });

                // Badge
                const badge = clone.querySelector('[data-role="ltip-funcao-badge"]');
                if (badge) {
                    badge.textContent = 'Função ' + (index + 1);
                }

                wrapper.appendChild(clone);
                atualizarTotal();
            });

            // inicial
            atualizarTotal();
        });
    </script>
@endsection
