@extends('layouts.cliente')

@section('title', 'Novo Funcionário')

@section('content')
    <div class="max-w-3xl mx-auto mt-6">

        {{-- Caixa do formulário com borda azul no topo --}}
        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">

            {{-- FAIXA AZUL COM TÍTULO --}}
            <div class="bg-blue-700 px-6 py-3">
                <h1 class="text-white font-semibold text-base">
                    Novo Funcionário
                </h1>
            </div>

            {{-- FORM --}}
            <form method="post" action="{{ route('cliente.funcionarios.store') }}" class="p-6 space-y-6">
                @csrf

                {{-- LINHAS DO FORMULÁRIO PRINCIPAL --}}
                <div class="grid gap-4 md:grid-cols-2">

                    {{-- Nome Completo (linha inteira) --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Nome Completo *
                        </label>
                        <input type="text" name="nome" value="{{ old('nome') }}"
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
                        <input type="text" name="cpf" value="{{ old('cpf') }}"
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
                        <input type="text" name="celular" value="{{ old('celular') }}"
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
                            :funcoes="$funcoes"
                            :selected="old('funcao_id', $funcionario->funcao_id ?? null)"
                        />

                        @error('funcao_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror

                        @error('campo_funcao')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Setor (apenas visual por enquanto) --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Setor *
                        </label>
                        <input type="text" name="setor" value="{{ old('setor') }}"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>

                    {{-- Data de Admissão --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Data de Admissão *
                        </label>
                        <input type="date" name="data_admissao" value="{{ old('data_admissao') }}"
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
                        Cadastrar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
