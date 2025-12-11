@extends('layouts.operacional')

@section('title', 'PGR - ' . $tipoLabel)

@section('content')
    @php
        $origem = request()->query('origem'); // 'cliente' ou null

        // rota para o PASSO 1 (selecionar tipo), preservando origem
        $rotaVoltarTipo = route('operacional.kanban.pgr.tipo', [
            'cliente' => $cliente->id,
            'origem'  => $origem,
        ]);

        /** @var string $modo */ // 'create' ou 'edit'
        $modo = $modo ?? 'create';
    @endphp

    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">

        {{-- BOTÃO VOLTAR CORRETO --}}
        <div class="mb-4">
            <a href="{{ $rotaVoltarTipo }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>←</span>
                <span>Voltar</span>
            </a>
        </div>

        <form method="POST"
              action="{{ $modo === 'edit'
                    ? route('operacional.kanban.pgr.update', $tarefa)
                    : route('operacional.kanban.pgr.store', $cliente) }}">
            @csrf
            @if($modo === 'edit')
                @method('PUT')
            @endif

            <input type="hidden" name="tipo" value="{{ old('tipo', $tipo ?? ($pgr->tipo ?? 'matriz')) }}">

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                {{-- Cabeçalho --}}
                <div class="bg-emerald-700 px-6 py-4">
                    <h1 class="text-lg md:text-xl font-semibold text-white mb-1">
                        PGR - {{ $tipoLabel }}
                    </h1>
                    <p class="text-xs md:text-sm text-emerald-100">
                        {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                    </p>
                </div>

                <div class="px-6 py-6 space-y-6">

                    {{-- 1. ART --}}
                    <section>
                        <h2 class="text-sm font-semibold text-slate-800 mb-3">1. ART</h2>

                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <button type="button"
                                    data-art-value="1"
                                    class="btn-art w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold
                                           border bg-slate-900 text-white">
                                Com ART
                            </button>
                            <button type="button"
                                    data-art-value="0"
                                    class="btn-art w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold
                                           border border-slate-200 bg-white text-slate-700">
                                Sem ART
                            </button>
                        </div>

                        <input type="hidden" name="com_art" id="input-com-art"
                               value="{{ old('com_art', isset($pgr) ? (int) $pgr->com_art : 1) }}">

                        <div id="alert-art"
                             class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            ⚠ Custo adicional de R$ {{ number_format($valorArt, 2, ',', '.') }}
                        </div>
                    </section>

                    @if($tipo === 'especifico')
                        <section class="mb-6 bg-white border border-slate-200 rounded-2xl p-5">
                            <h2 class="text-sm font-semibold text-slate-800 mb-4">2. Contratante</h2>

                            <div class="grid md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">
                                        Nome/Razão Social
                                    </label>
                                    <input type="text" name="contratante_nome"
                                           value="{{ old('contratante_nome', $pgr->contratante_nome ?? '') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">CNPJ</label>
                                    <input type="text" name="contratante_cnpj"
                                           value="{{ old('contratante_cnpj', $pgr->contratante_cnpj ?? '') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                            </div>

                            <h2 class="text-sm font-semibold text-slate-800 mb-3 mt-2">3. Obra</h2>

                            <div class="grid md:grid-cols-2 gap-4 mb-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Nome da Obra</label>
                                    <input type="text" name="obra_nome"
                                           value="{{ old('obra_nome', $pgr->obra_nome ?? '') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Endereço da Obra</label>
                                    <input type="text" name="obra_endereco"
                                           value="{{ old('obra_endereco', $pgr->obra_endereco ?? '') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">CEJ/CNO</label>
                                    <input type="text" name="obra_cej_cno"
                                           value="{{ old('obra_cej_cno', $pgr->obra_cej_cno ?? '') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">
                                        Turno(s) de Trabalho
                                    </label>
                                    <input type="text" name="obra_turno_trabalho"
                                           value="{{ old('obra_turno_trabalho', $pgr->obra_turno_trabalho ?? '') }}"
                                           placeholder="Ex: Diurno (7h às 17h)"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                            </div>
                        </section>
                    @endif

                    {{-- 2. Trabalhadores --}}
                    <section>
                        <h2 class="text-sm font-semibold text-slate-800 mb-3">2. Trabalhadores</h2>

                        <div class="grid grid-cols-1 md:grid-cols-[1.2fr,1.2fr,auto] gap-3 items-end">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Funcionários Homens
                                </label>
                                <input type="number" name="qtd_homens"
                                       value="{{ old('qtd_homens', $pgr->qtd_homens ?? 0) }}"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Funcionárias Mulheres
                                </label>
                                <input type="number" name="qtd_mulheres"
                                       value="{{ old('qtd_mulheres', $pgr->qtd_mulheres ?? 0) }}"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            </div>

                            <div class="flex flex-col items-center justify-center">
                                <span class="text-xs font-medium text-slate-500 mb-1">Total</span>
                                <div id="total-trabalhadores"
                                     class="inline-flex items-center justify-center px-4 py-2 rounded-full bg-sky-500 text-white text-sm font-semibold">
                                    0
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- 3. Funções e Cargos --}}
                    <section>
                        {{-- Cabeçalho da seção: título + botão alinhados --}}
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h2 class="text-sm font-semibold text-slate-800">
                                3. Funções e Cargos
                            </h2>

                            <x-funcoes.create-button label="Cadastrar nova função" variant="emerald" />
                        </div>

                        @php
                            $funcoesForm = old('funcoes');

                            if ($funcoesForm === null) {
                                if (isset($pgr) && is_array($pgr->funcoes)) {
                                    $funcoesForm = $pgr->funcoes;
                                } else {
                                    $funcoesForm = [
                                        ['funcao_id' => null, 'quantidade' => 1, 'cbo' => null, 'descricao' => null],
                                    ];
                                }
                            }
                        @endphp

                        <div id="funcoes-wrapper" class="space-y-3">
                            @foreach($funcoesForm as $idx => $f)
                                <div class="funcao-item rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
                                     data-funcao-index="{{ $idx }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <span
                                            class="badge-funcao text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-semibold">
                                            Função {{ $idx + 1 }}
                                        </span>

                                        {{-- Botão remover --}}
                                        <button type="button"
                                                class="btn-remove-funcao inline-flex items-center gap-1 text-[11px] text-red-600 hover:text-red-800">
                                            ✕ Remover
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-12 gap-3">
                                        <div class="col-span-5 funcao-select-wrapper">
                                            <x-funcoes.select-with-create
                                                name="funcoes[{{ $idx }}][funcao_id]"
                                                field-id="funcoes_{{ $idx }}_funcao_id"
                                                label="Cargo"
                                                :funcoes="$funcoes"
                                                :selected="old('funcoes.'.$idx.'.funcao_id', $f['funcao_id'] ?? null)"
                                                :show-create="false"
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
                                                Descrição (opcional)
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
                            <button type="button" id="btn-add-funcao"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600">
                                <span>+</span>
                                <span>Adicionar</span>
                            </button>
                        </div>

                        @error('funcoes')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </section>

                    {{-- Rodapé --}}
                    <div class="flex flex-col md:flex-row gap-3 mt-4">
                        <a href="{{ $rotaVoltarTipo }}"
                           class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                            Voltar
                        </a>

                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                            Finalizar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // ========= ART =========
                const btnsArt = document.querySelectorAll('.btn-art');
                const inputComArt = document.getElementById('input-com-art');
                const alertArt = document.getElementById('alert-art');

                function aplicarEstadoArt(valor) {
                    if (!inputComArt) return;
                    inputComArt.value = valor;

                    btnsArt.forEach(b => {
                        b.classList.remove('bg-slate-900', 'text-white');
                        b.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
                    });

                    btnsArt.forEach(b => {
                        if (b.dataset.artValue === String(valor)) {
                            b.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
                            b.classList.add('bg-slate-900', 'text-white');
                        }
                    });

                    if (alertArt) {
                        alertArt.style.display = (String(valor) === '1') ? 'block' : 'none';
                    }
                }

                btnsArt.forEach(btn => {
                    btn.addEventListener('click', () => {
                        aplicarEstadoArt(btn.dataset.artValue);
                    });
                });

                // ao carregar, aplica o valor que veio do backend (create ou edit)
                if (inputComArt) {
                    aplicarEstadoArt(inputComArt.value || '1');
                }

                // ========= TOTAL TRABALHADORES =========
                const inputHomens = document.querySelector('input[name="qtd_homens"]');
                const inputMulheres = document.querySelector('input[name="qtd_mulheres"]');
                const totalEl = document.getElementById('total-trabalhadores');

                function atualizarTotal() {
                    const h = parseInt(inputHomens?.value || '0', 10);
                    const m = parseInt(inputMulheres?.value || '0', 10);
                    totalEl.textContent = String(h + m);
                }

                if (inputHomens && inputMulheres && totalEl) {
                    inputHomens.addEventListener('input', atualizarTotal);
                    inputMulheres.addEventListener('input', atualizarTotal);
                    atualizarTotal();
                }

                // ========= FUNÇÕES E CARGOS (dinâmico) =========
                const wrapper = document.getElementById('funcoes-wrapper');
                const btnAdd = document.getElementById('btn-add-funcao');

                if (wrapper && btnAdd) {
                    btnAdd.addEventListener('click', function () {
                        const itens = wrapper.querySelectorAll('.funcao-item');
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
                            badge.textContent = 'Função ' + (novoIndex + 1);
                        }

                        wrapper.appendChild(clone);
                    });

                    // remover função com delegação de evento
                    wrapper.addEventListener('click', function (e) {
                        const btn = e.target.closest('.btn-remove-funcao');
                        if (!btn) return;

                        const itens = wrapper.querySelectorAll('.funcao-item');
                        if (itens.length <= 1) {
                            alert('É necessário pelo menos uma função.');
                            return;
                        }

                        const item = btn.closest('.funcao-item');
                        if (item) {
                            item.remove();
                            reindexFuncoes(wrapper);
                        }
                    });
                }

                // função auxiliar para reindexar os índices/names/labels
                function reindexFuncoes(wrapper) {
                    const itens = wrapper.querySelectorAll('.funcao-item');

                    itens.forEach((item, idx) => {
                        item.dataset.funcaoIndex = String(idx);

                        const badge = item.querySelector('.badge-funcao');
                        if (badge) {
                            badge.textContent = 'Função ' + (idx + 1);
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

            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // pega o primeiro input com name="contratante_cnpj" da página
                var cnpjInput = document.querySelector('input[name="contratante_cnpj"]');
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
    @endpush
@endsection
