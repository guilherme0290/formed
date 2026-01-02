@extends('layouts.cliente')

@section('title', $modo === 'edit' ? 'Editar Funcionário' : 'Novo Funcionário')

@section('content')
    <div class="max-w-3xl mx-auto mt-6">

        {{-- Caixa do formulário com borda azul no topo --}}
        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">

            {{-- FAIXA AZUL COM TÍTULO --}}
            <div class="bg-blue-700 px-6 py-3">
                <h1 class="text-white font-semibold text-base">
                    {{ $modo === 'edit' ? 'Editar Funcionário' : 'Novo Funcionário' }}
                </h1>
            </div>

            {{-- FORM --}}
            <form method="post"
                  action="{{ $modo === 'edit' ? route('cliente.funcionarios.update', $funcionario) : route('cliente.funcionarios.store') }}"
                  class="p-6 space-y-6">
                @csrf
                @if($modo === 'edit')
                    @method('PUT')
                @endif

                {{-- LINHAS DO FORMULÁRIO PRINCIPAL --}}
                <div class="grid gap-4 md:grid-cols-2">

                    {{-- Nome Completo (linha inteira) --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Nome Completo *
                        </label>
                        <input type="text" name="nome" value="{{ old('nome', $funcionario->nome ?? '') }}"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                               required>
                        @error('nome')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CPF --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            CPF *
                        </label>
                        <input type="text" name="cpf" value="{{ old('cpf', $funcionario->cpf ?? '') }}"
                               maxlength="14"
                               inputmode="numeric"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        @error('cpf')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Celular (apenas visual por enquanto) --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Celular *
                        </label>
                        <input type="text" name="celular" value="{{ old('celular', $funcionario->celular ?? '') }}"
                               maxlength="15"
                               inputmode="numeric"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        {{-- sem erro porque ainda não está no validate --}}
                    </div>

                    {{-- Função --}}
                    {{-- Função (select com opção de criar) --}}
                    <div>
                        <x-funcoes.select-with-create
                            name="funcao_id"
                            label="Função *"
                            field-id="campo_funcao"
                            help-text="Funções listadas por GHE, pré-configuradas pelo vendedor/comercial."
                            :allowCreate="false"
                            :funcoes="$funcoes"
                            :selected="old('funcao_id', $funcionario->funcao_id ?? null)"
                        />

                        @error('funcao_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror

                    </div>

                    {{-- Setor (apenas visual por enquanto) --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Setor *
                        </label>
                        <input type="text" name="setor" value="{{ old('setor', $funcionario->setor ?? '') }}"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>

                    {{-- Data de Admissão --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Data de Admissão *
                        </label>
                        <input type="date"
                               name="data_admissao"
                               value="{{ old('data_admissao', ($funcionario && $funcionario->data_admissao) ? \Carbon\Carbon::parse($funcionario->data_admissao)->format('Y-m-d') : '') }}"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        @error('data_admissao')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- EXAMES / TREINAMENTOS, caso queira no preenchimento do funcionario ) --}}
                {{--
                <div class="pt-4 mt-2 border-t border-slate-100">
                    <p class="text-xs font-semibold text-slate-700 mb-2">
                        Exames / Treinamentos (opcional)
                    </p>

                    <div class="grid gap-3 md:grid-cols-3">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" name="treinamento_nr" value="1"
                                @checked(old('treinamento_nr'))>
                            Treinamento NR
                        </label>

                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" name="exame_admissional" value="1"
                                @checked(old('exame_admissional'))>
                            Exame Admissional
                        </label>

                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" name="exame_periodico" value="1"
                                @checked(old('exame_periodico'))>
                            Exame Periódico
                        </label>

                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" name="exame_demissional" value="1"
                                @checked(old('exame_demissional'))>
                            Exame Demissional
                        </label>

                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" name="exame_mudanca_funcao" value="1"
                                @checked(old('exame_mudanca_funcao'))>
                            Mudança de Função
                        </label>

                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" name="exame_retorno_trabalho" value="1"
                                @checked(old('exame_retorno_trabalho'))>
                            Retorno ao Trabalho
                        </label>
                    </div>
                </div>--}}

                {{-- BOTÕES --}}
                <div class="flex justify-end gap-2 pt-4">
                    <a href="{{ route('cliente.funcionarios.index') }}"
                       class="px-4 py-2 text-xs rounded-lg border border-slate-300 text-slate-700">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-4 py-2 text-xs rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        {{ $modo === 'edit' ? 'Salvar alterações' : 'Cadastrar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        (function () {
            const cpfInput = document.querySelector('input[name="cpf"]');
            const celularInput = document.querySelector('input[name="celular"]');

            function onlyDigits(value) {
                return (value || '').toString().replace(/\D+/g, '');
            }

            function formatCpf(value) {
                const digits = onlyDigits(value).slice(0, 11);
                if (digits.length <= 3) return digits;
                if (digits.length <= 6) return `${digits.slice(0, 3)}.${digits.slice(3)}`;
                if (digits.length <= 9) return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
                return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
            }

            function formatPhone(value) {
                const digits = onlyDigits(value).slice(0, 11);
                if (digits.length <= 2) return digits;
                if (digits.length <= 6) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
                if (digits.length <= 10) {
                    return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
                }
                return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
            }

            function applyCpfMask() {
                if (!cpfInput) return;
                cpfInput.value = formatCpf(cpfInput.value);
            }

            function applyPhoneMask() {
                if (!celularInput) return;
                celularInput.value = formatPhone(celularInput.value);
            }

            cpfInput?.addEventListener('input', applyCpfMask);
            celularInput?.addEventListener('input', applyPhoneMask);

            applyCpfMask();
            applyPhoneMask();
        })();

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-funcao-open-modal]');
            if (!btn) return;

            window.dispatchEvent(
                new CustomEvent('open-funcao-modal', {
                    detail: {
                        modalId: btn.dataset.funcaoOpenModal,
                        targetId: btn.dataset.funcaoTarget
                    }
                })
            );
        });
    </script>
@endpush
