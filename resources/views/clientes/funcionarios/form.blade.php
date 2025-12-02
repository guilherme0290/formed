{{-- resources/views/cliente/funcionarios/form.blade.php --}}
@extends('layouts.cliente')

@section('title', 'Novo Funcionário')

@section('content')
    <h1 class="text-lg font-semibold text-slate-800 mb-4">
        Novo Funcionário
    </h1>

    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <form method="post" action="{{ route('cliente.funcionarios.store') }}" class="space-y-4">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nome *</label>
                    <input type="text" name="nome" value="{{ old('nome') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                           required>
                    @error('nome')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Função</label>
                    <input type="text" name="funcao" value="{{ old('funcao') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    @error('funcao')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">CPF</label>
                    <input type="text" name="cpf" value="{{ old('cpf') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    @error('cpf')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">RG</label>
                    <input type="text" name="rg" value="{{ old('rg') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    @error('rg')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Data de Nascimento</label>
                    <input type="date" name="data_nascimento" value="{{ old('data_nascimento') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    @error('data_nascimento')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Data de Admissão</label>
                    <input type="date" name="data_admissao" value="{{ old('data_admissao') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    @error('data_admissao')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3 pt-2 border-t border-slate-100 mt-2">
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

            <div class="flex justify-end gap-2 pt-4">
                <a href="{{ route('cliente.funcionarios.index') }}"
                   class="px-3 py-2 text-xs rounded-lg border border-slate-300 text-slate-700">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-3 py-2 text-xs rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                    Salvar funcionário
                </button>
            </div>
        </form>
    </div>
@endsection
