@extends('layouts.operacional')

@section('pageTitle', "LTCAT - {$tipoLabel}")

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('operacional.ltcat.tipo', $cliente) }}"
               class="inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50">
                ← Voltar
            </a>
        </div>

        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            {{-- Cabeçalho --}}
            <div class="px-6 py-4 bg-gradient-to-r from-orange-600 to-orange-700 text-white">
                <h1 class="text-lg font-semibold">
                    LTCAT - {{ $tipoLabel }}
                </h1>
                <p class="text-xs text-white/80 mt-1">
                    {{ $cliente->razao_social }}
                </p>
            </div>

            <form method="POST"
                  action="{{ route('operacional.ltcat.store', $cliente) }}"
                  class="p-6 space-y-6">
                @csrf

                <input type="hidden" name="tipo" value="{{ $tipo }}">

                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700">
                        <ul class="list-disc ms-4">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- 1. Local --}}
                {{-- 1. Local --}}
                <section class="space-y-2">
                    <h2 class="text-sm font-semibold text-slate-800">1. Local</h2>

                    @if($tipo === 'especifico')
                        {{-- LTCAT Específico: dados da obra --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Nome da Obra *
                                </label>
                                <input type="text"
                                       name="nome_obra"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                       placeholder="Nome da obra"
                                       value="{{ old('nome_obra') }}">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    CNPJ do Contratante *
                                </label>
                                <input type="text"
                                       name="cnpj_contratante"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                       placeholder="00.000.000/0000-00"
                                       value="{{ old('cnpj_contratante') }}">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    CEI/CNO *
                                </label>
                                <input type="text"
                                       name="cei_cno"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                       placeholder="Número do CEI ou CNO"
                                       value="{{ old('cei_cno') }}">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Endereço da Obra *
                                </label>
                                <textarea
                                    name="endereco_obra"
                                    rows="3"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                    placeholder="Endereço completo da obra onde serão realizadas as avaliações"
                                >{{ old('endereco_obra') }}</textarea>
                            </div>
                        </div>
                    @else
                        {{-- LTCAT Matriz: endereço único das avaliações --}}
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Endereço das Avaliações *
                                </label>
                                <textarea
                                    name="endereco_avaliacoes"
                                    rows="3"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                    placeholder="Endereço completo onde serão realizadas as avaliações"
                                >{{ old('endereco_avaliacoes') }}</textarea>
                            </div>
                        </div>
                    @endif
                </section>


                {{-- 2. Resumo --}}
                <section class="space-y-2">
                    <h2 class="text-sm font-semibold text-slate-800">2. Resumo</h2>

                    <div class="rounded-xl border border-orange-100 bg-orange-50 px-4 py-3 text-xs text-orange-800 flex justify-between">
                        <div>
                            <p class="font-semibold">
                                LTCAT {{ $tipoLabel }}
                            </p>
                            <p class="mt-1">
                                Funções cadastradas:
                                <span id="ltcat-total-funcoes" class="font-bold">1</span>
                            </p>
                            <p>
                                Total de funcionários:
                                <span id="ltcat-total-funcionarios" class="font-bold">1</span>
                            </p>
                        </div>

                        <div class="text-[11px] text-orange-700/80 flex items-center">
                            O resumo é atualizado conforme você adiciona ou altera as funções.
                        </div>
                    </div>
                </section>

                {{-- 3. Funções e Quantidades --}}
                <section>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-slate-800">3. Funções e Quantidades</h2>

                        <button type="button" id="ltcat-btn-add-funcao"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-orange-500 text-white text-xs font-semibold hover:bg-orange-600">
                            <span>+</span>
                            <span>Adicionar Função</span>
                        </button>
                    </div>

                    <div id="ltcat-funcoes-wrapper" class="space-y-3">
                        {{-- linha base --}}
                        <div class="ltcat-funcao-item rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <span data-role="ltcat-funcao-badge"
                                      class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-semibold">
                                    Função 1
                                </span>
                            </div>

                            <div class="grid grid-cols-12 gap-3 items-end">
                                <div class="col-span-8">
                                    <x-funcoes.select-with-create
                                        name="funcoes[0][funcao_id]"
                                        field-id="funcoes_0_funcao_id"
                                        label="Função"
                                        :funcoes="$funcoes"
                                        :selected="old('funcoes.0.funcao_id')"
                                        :show-create="false"
                                    />
                                </div>

                                <div class="col-span-4">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">
                                        Quantidade
                                    </label>
                                    <input type="number"
                                           name="funcoes[0][quantidade]"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 ltcat-qtd-input"
                                           value="{{ old('funcoes.0.quantidade', 1) }}"
                                           min="1">
                                </div>
                            </div>
                        </div>
                    </div>

                    @error('funcoes')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </section>

                {{-- Footer --}}
                <div class="flex flex-col md:flex-row gap-3 pt-4 border-t border-slate-100 mt-4">
                    <a href="{{ route('operacional.ltcat.tipo', $cliente) }}"
                       class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                        Voltar
                    </a>

                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600">
                        Criar Tarefa LTCAT
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Script LTCAT --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wrapper = document.getElementById('ltcat-funcoes-wrapper');
            const btnAdd  = document.getElementById('ltcat-btn-add-funcao');

            const totalFuncoesEl      = document.getElementById('ltcat-total-funcoes');
            const totalFuncionariosEl = document.getElementById('ltcat-total-funcionarios');

            function atualizarResumo() {
                const itens = wrapper.querySelectorAll('.ltcat-funcao-item');
                let totalFuncoes = itens.length;
                let totalFuncionarios = 0;

                itens.forEach(item => {
                    const qtdInput = item.querySelector('.ltcat-qtd-input');
                    const qtd = parseInt(qtdInput?.value || '0', 10);
                    totalFuncionarios += isNaN(qtd) ? 0 : qtd;
                });

                totalFuncoesEl.textContent      = totalFuncoes.toString();
                totalFuncionariosEl.textContent = totalFuncionarios.toString();
            }

            // Atualiza ao digitar quantidade
            wrapper.addEventListener('input', function (e) {
                if (e.target.classList.contains('ltcat-qtd-input')) {
                    atualizarResumo();
                }
            });

            btnAdd.addEventListener('click', function () {
                const itens = wrapper.querySelectorAll('.ltcat-funcao-item');
                const index = itens.length;

                const base = itens[0];
                const clone = base.cloneNode(true);

                clone.querySelectorAll('input, select').forEach(el => {
                    if (el.name && el.name.includes('funcoes[')) {
                        el.name = el.name.replace(/\[\d+]/, '[' + index + ']');
                    }

                    // reseta valores
                    if (el.tagName === 'SELECT') {
                        el.value = '';
                        // atualiza o id para manter unicidade
                        if (el.id && el.id.startsWith('funcoes_')) {
                            el.id = el.id.replace(/_\d+_funcao_id$/, '_' + index + '_funcao_id');
                        }
                    } else if (el.name.includes('[quantidade]')) {
                        el.value = '1';
                    } else {
                        el.value = '';
                    }
                });

                // Atualiza badge "Função X"
                const badge = clone.querySelector('[data-role="ltcat-funcao-badge"]');
                if (badge) {
                    badge.textContent = 'Função ' + (index + 1);
                }

                wrapper.appendChild(clone);
                atualizarResumo();
            });

            // Inicializa resumo
            atualizarResumo();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // pega o primeiro input com name="cnpj" da página
            var cnpjInput = document.querySelector('input[name="cnpj_contratante"]');
            if (!cnpjInput) return;

            // máscara enquanto digita
            cnpjInput.addEventListener('input', function () {
                var v = cnpjInput.value.replace(/\D/g, '');   // só números
                v = v.slice(0, 14);                           // máximo 14 dígitos

                if (v.length > 12) {
                    // 00.000.000/0000-00
                    cnpjInput.value = v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})/, "$1.$2.$3/$4-$5");
                } else if (v.length > 8) {
                    // 00.000.000/0000
                    cnpjInput.value = v.replace(/(\d{2})(\d{3})(\d{3})(\d{1,4})/, "$1.$2.$3/$4");
                } else if (v.length > 5) {
                    // 00.000.000
                    cnpjInput.value = v.replace(/(\d{2})(\d{3})(\d{1,3})/, "$1.$2.$3");
                } else if (v.length > 2) {
                    // 00.000
                    cnpjInput.value = v.replace(/(\d{2})(\d{1,3})/, "$1.$2");
                } else {
                    cnpjInput.value = v;
                }
            });

            // validação ao sair do campo
            cnpjInput.addEventListener('blur', function () {
                var cnpjLimpo = cnpjInput.value.replace(/\D/g, '');

                if (cnpjLimpo === '') {
                    limparErroCNPJ(cnpjInput);
                    return;
                }

                if (!cnpjValido(cnpjLimpo)) {
                    mostrarErroCNPJ(cnpjInput, 'CNPJ inválido');
                } else {
                    limparErroCNPJ(cnpjInput);
                }
            });
        });

        // valida CNPJ (algoritmo padrão)
        function cnpjValido(cnpj) {
            if (!cnpj || cnpj.length !== 14) return false;

            // elimina sequências como 00.000.000/0000-00, 11..., etc.
            if (/^(\d)\1{13}$/.test(cnpj)) return false;

            var tamanho = 12;
            var numeros = cnpj.substring(0, tamanho);
            var digitos = cnpj.substring(tamanho);
            var soma = 0;
            var pos = tamanho - 7;

            for (var i = tamanho; i >= 1; i--) {
                soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
                if (pos < 2) pos = 9;
            }

            var resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            if (resultado !== parseInt(digitos.charAt(0))) return false;

            tamanho = 13;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;

            for (var j = tamanho; j >= 1; j--) {
                soma += parseInt(numeros.charAt(tamanho - j)) * pos--;
                if (pos < 2) pos = 9;
            }

            resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            if (resultado !== parseInt(digitos.charAt(1))) return false;

            return true;
        }

        // mostra mensagem de erro logo abaixo do input
        function mostrarErroCNPJ(input, mensagem) {
            limparErroCNPJ(input);

            input.style.borderColor = '#dc2626'; // vermelho
            var p = document.createElement('p');
            p.className = 'cnpj-error';
            p.style.color = '#dc2626';
            p.style.fontSize = '12px';
            p.style.marginTop = '4px';
            p.textContent = mensagem;

            if (input.parentNode) {
                input.parentNode.appendChild(p);
            }
        }

        // remove mensagem de erro e estilo
        function limparErroCNPJ(input) {
            input.style.borderColor = '';

            if (!input.parentNode) return;
            var erro = input.parentNode.querySelector('.cnpj-error');
            if (erro) {
                erro.remove();
            }
        }
    </script>

@endsection
