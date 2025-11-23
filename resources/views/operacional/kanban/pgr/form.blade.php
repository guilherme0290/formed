@extends('layouts.operacional')

@section('title', 'PGR - ' . $tipoLabel)

@section('content')
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">

        <div class="mb-4">
            <a href="{{ route('operacional.kanban.pgr.tipo', $cliente) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>←</span>
                <span>Voltar</span>
            </a>
        </div>

        <form method="POST" action="{{ route('operacional.kanban.pgr.store', $cliente) }}">
            @csrf

            <input type="hidden" name="tipo" value="{{ $tipo }}">

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

                        <input type="hidden" name="com_art" id="input-com-art" value="1">

                        <div id="alert-art" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            ⚠ Custo adicional de R$ {{ number_format($valorArt, 2, ',', '.') }}
                        </div>
                    </section>

                    @if($tipo === 'especifico')
                        <section class="mb-6 bg-white border border-slate-200 rounded-2xl p-5">
                            <h2 class="text-sm font-semibold text-slate-800 mb-4">2. Contratante</h2>

                            <div class="grid md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Nome/Razão Social</label>
                                    <input type="text" name="contratante_nome"
                                           value="{{ old('contratante_nome') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">CNPJ</label>
                                    <input type="text" name="contratante_cnpj"
                                           value="{{ old('contratante_cnpj') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                            </div>

                            <h2 class="text-sm font-semibold text-slate-800 mb-3 mt-2">3. Obra</h2>

                            <div class="grid md:grid-cols-2 gap-4 mb-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Nome da Obra</label>
                                    <input type="text" name="obra_nome"
                                           value="{{ old('obra_nome') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Endereço da Obra</label>
                                    <input type="text" name="obra_endereco"
                                           value="{{ old('obra_endereco') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">CEJ/CNO</label>
                                    <input type="text" name="obra_cej_cno"
                                           value="{{ old('obra_cej_cno') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Turno(s) de Trabalho</label>
                                    <input type="text" name="obra_turno_trabalho"
                                           value="{{ old('obra_turno_trabalho') }}"
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
                                <input type="number" name="qtd_homens" value="{{ old('qtd_homens', 0) }}"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Funcionárias Mulheres
                                </label>
                                <input type="number" name="qtd_mulheres" value="{{ old('qtd_mulheres', 0) }}"
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
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-sm font-semibold text-slate-800">3. Funções e Cargos</h2>


                        </div>

                        <div id="funcoes-wrapper" class="space-y-3">
                            {{-- linha base (Função 1) --}}
                            <div class="funcao-item rounded-xl border border-slate-200 bg-slate-50 px-4 py-3" data-funcao-index="0">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="badge-funcao text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-semibold">
                                        Função 1
                                    </span>
                                </div>

                                <div class="grid grid-cols-12 gap-3">
                                    <div class="col-span-5 funcao-select-wrapper">
                                        <x-funcoes.select-with-create
                                            name="funcoes[0][funcao_id]"
                                            field-id="funcoes_0_funcao_id"
                                            label="Cargo"
                                            :funcoes="$funcoes"
                                            :selected="old('funcoes.0.funcao_id')"
                                            :show-create="false"  {{-- no PGR é melhor não ter "+" por causa das linhas dinâmicas --}}
                                        />
                                    </div>

                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-slate-500 mb-1">Qtd</label>
                                        <input type="number" name="funcoes[0][quantidade]" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                               value="1" min="1">
                                    </div>

                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-slate-500 mb-1">CBO</label>
                                        <input type="text" name="funcoes[0][cbo]" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                               placeholder="0000-00">
                                    </div>

                                    <div class="col-span-3">
                                        <label class="block text-xs font-medium text-slate-500 mb-1">Descrição (opcional)</label>
                                        <input type="text" name="funcoes[0][descricao]" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                               placeholder="Atividades...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mb-3">
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
                        <a href="{{ route('operacional.kanban.pgr.tipo', $cliente) }}"
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
                const btnsArt   = document.querySelectorAll('.btn-art');
                const inputComArt = document.getElementById('input-com-art');
                const alertArt  = document.getElementById('alert-art');

                btnsArt.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const value = btn.dataset.artValue;

                        inputComArt.value = value;

                        btnsArt.forEach(b => {
                            b.classList.remove('bg-slate-900', 'text-white');
                            b.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
                        });

                        btn.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
                        btn.classList.add('bg-slate-900', 'text-white');

                        alertArt.style.display = (value === '1') ? 'block' : 'none';
                    });
                });

                // ========= TOTAL TRABALHADORES =========
                const inputHomens   = document.querySelector('input[name="qtd_homens"]');
                const inputMulheres = document.querySelector('input[name="qtd_mulheres"]');
                const totalEl       = document.getElementById('total-trabalhadores');

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
                const btnAdd  = document.getElementById('btn-add-funcao');

                if (wrapper && btnAdd) {
                    btnAdd.addEventListener('click', function () {
                        const itens = wrapper.querySelectorAll('.funcao-item');
                        const novoIndex = itens.length;

                        // clona o último item (para manter qualquer ajuste de layout que vc faça depois)
                        const base = itens[itens.length - 1];
                        const clone = base.cloneNode(true);

                        // atualiza índice no data-attribute
                        clone.dataset.funcaoIndex = String(novoIndex);

                        // atualiza names e limpa valores
                        clone.querySelectorAll('input, select').forEach(function (el) {
                            if (el.name && el.name.includes('funcoes[')) {
                                el.name = el.name.replace(/\[\d+]/, '[' + novoIndex + ']');
                            }

                            // limpa valores (mantém quantidade = 1)
                            if (el.tagName === 'SELECT') {
                                el.value = '';
                            } else if (el.name.includes('[quantidade]')) {
                                el.value = '1';
                            } else {
                                el.value = '';
                            }

                            // se tiver id do tipo funcoes_0_funcao_id, ajusta também
                            if (el.id && el.id.startsWith('funcoes_')) {
                                el.id = el.id.replace(/_\d+_funcao_id$/, '_' + novoIndex + '_funcao_id');
                            }
                        });

                        // atualiza o label "Função X"
                        const badge = clone.querySelector('.badge-funcao');
                        if (badge) {
                            badge.textContent = 'Função ' + (novoIndex + 1);
                        }

                        wrapper.appendChild(clone);
                    });
                }
            });
        </script>


    @endpush
@endsection
