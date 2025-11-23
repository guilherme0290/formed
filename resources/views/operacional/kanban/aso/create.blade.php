@extends('layouts.operacional')

@section('title', 'Agendar ASO')

@section('content')
    <div class="max-w-4xl mx-auto px-6 py-8">

        {{-- Voltar --}}
        <div class="mb-4">
            <a href="{{ route('operacional.kanban.servicos', $cliente) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>←</span>
                <span>Voltar</span>
            </a>
        </div>

        {{-- Card principal --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            {{-- Cabeçalho azul igual protótipo --}}
            <div class="bg-[color:var(--color-brand-azul)] px-6 py-4">
                <h1 class="text-lg md:text-xl font-semibold text-white mb-1">
                    Agendar ASO
                </h1>
                <p class="text-xs md:text-sm text-blue-100">
                    {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                </p>
            </div>

            {{-- Conteúdo / Form --}}
            <div class="px-6 py-6">
                @if($errors->any())
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700">
                        <p class="font-medium mb-1">Ocorreram alguns erros ao salvar:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('operacional.kanban.aso.store', $cliente) }}" class="space-y-6">
                    @csrf

                    {{-- Tipo de ASO (linha 1 do protótipo) --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-medium text-slate-600">
                            Tipo de ASO *
                        </label>
                        <select name="tipo_aso"
                                class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3 bg-white
                                       focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                            <option value="">Selecione o tipo de ASO</option>
                            @foreach($tiposAso as $key => $label)
                                <option value="{{ $key }}" @selected(old('tipo_aso') == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Bloco de colaborador + dados do funcionário --}}
                    <div class="space-y-6"
                         x-data="{ temFuncionario: {{ old('funcionario_id') ? 'true' : 'false' }} }">

                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-slate-600">
                                Colaborador *
                            </label>
                            <select name="funcionario_id"
                                    class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3 bg-white
                                        focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
                                    x-on:change="temFuncionario = $el.value !== ''">
                                <option value="">Selecione o colaborador</option>
                                @foreach($funcionarios as $func)
                                    <option value="{{ $func->id }}" @selected(old('funcionario_id') == $func->id)>
                                        {{ $func->nome }} @if($func->cpf) - {{ $func->cpf }} @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400">
                                Se for um colaborador novo, deixe o campo acima em branco e preencha os dados abaixo.
                            </p>
                        </div>

                        {{-- Nome completo / Função --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">
                                    Nome Completo *
                                </label>
                                <input type="text"
                                       name="nome"
                                       value="{{ old('nome') }}"
                                       placeholder="Nome completo"
                                       :disabled="temFuncionario"
                                       class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                                            focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
                                       :class="temFuncionario ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'">
                            </div>

                            <x-funcoes.select-with-create
                                name="funcao_id"
                                label="Função"
                                :funcoes="$funcoes"
                                :selected="old('funcao_id')"
                            />
                        </div>

                        {{-- CPF / RG / Data Nascimento --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">
                                    CPF *
                                </label>
                                <input type="text"
                                       name="cpf"
                                       value="{{ old('cpf') }}"
                                       placeholder="000.000.000-00"
                                       :disabled="temFuncionario"
                                       class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                          focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
                                       :class="temFuncionario ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">
                                    RG *
                                </label>
                                <input type="text"
                                       name="rg"
                                       value="{{ old('rg') }}"
                                       placeholder="00.000.000-0"
                                       :disabled="temFuncionario"
                                       class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                          focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
                                       :class="temFuncionario ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">
                                    Data de Nascimento *
                                </label>
                                <input type="date"
                                       name="data_nascimento"
                                       value="{{ old('data_nascimento') }}"
                                       :disabled="temFuncionario"
                                       class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                          focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
                                       :class="temFuncionario ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'">
                            </div>
                        </div>
                    </div>

                    {{-- E-mail para envio do ASO (linha 4 do protótipo) --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            E-mail para envio de ASO
                        </label>
                        <input type="email"
                               name="email_aso"
                               value="{{ old('email_aso') }}"
                               placeholder="email@exemplo.com"
                               class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                                      focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                    </div>

                    {{-- Vai fazer treinamento conosco? (toggle + lista) --}}
                    <div class="space-y-3"
                         x-data="{ treina: {{ old('vai_fazer_treinamento', 0) ? 1 : 0 }} }">

                        <p class="text-xs font-medium text-slate-600">
                            Vai fazer treinamento conosco?
                        </p>

                        {{-- campo real enviado pro backend --}}
                        <input type="hidden"
                               name="vai_fazer_treinamento"
                               x-ref="campoTreinamento"
                               :value="treina">

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            {{-- SIM --}}
                            <button type="button"
                                    @click="treina = 1; $refs.campoTreinamento.value = 1"
                                    :class="treina == 1
                                        ? 'px-4 py-2 rounded-xl border text-center text-xs font-medium bg-slate-900 text-white border-slate-900'
                                        : 'px-4 py-2 rounded-xl border text-center text-xs font-medium bg-white text-slate-700 border-slate-300'">
                                Sim
                            </button>

                            {{-- NÃO --}}
                            <button type="button"
                                    @click="treina = 0; $refs.campoTreinamento.value = 0"
                                    :class="treina == 0
                                        ? 'px-4 py-2 rounded-xl border text-center text-xs font-medium bg-slate-900 text-white border-slate-900'
                                        : 'px-4 py-2 rounded-xl border text-center text-xs font-medium bg-white text-slate-700 border-slate-300'">
                                Não
                            </button>
                        </div>

                        {{-- Lista de treinamentos (expande igual protótipo) --}}
                        <div id="listaTreinamentos"
                             class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2"
                             x-show="treina == 1"
                             x-cloak>
                            @foreach($treinamentosDisponiveis as $key => $label)
                                <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                                    <input type="checkbox"
                                           name="treinamentos[]"
                                           value="{{ $key }}"
                                           @checked(collect(old('treinamentos'))->contains($key))
                                           class="rounded border-slate-300 text-sky-500 focus:ring-sky-400">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach

                            <p class="mt-1 text-[11px] text-slate-400 md:col-span-2">
                                Você pode selecionar mais de um treinamento.
                            </p>
                        </div>
                    </div>

                    {{-- Data e Local de Realização (bloco final do protótipo) --}}
                    <div class="border-t border-slate-100 pt-4 mt-2 space-y-4">
                        <h2 class="text-sm font-semibold text-slate-800">
                            Data e Local de Realização
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">
                                    Data de Realização *
                                </label>
                                <input type="date"
                                       name="data_aso"
                                       value="{{ old('data_aso') }}"
                                       class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                                              focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">
                                    Unidade *
                                </label>
                                <select name="unidade_id"
                                        class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3 bg-white
                                               focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                                    <option value="">Selecione a unidade</option>
                                    @foreach($unidades as $unidade)
                                        <option value="{{ $unidade->id }}" @selected(old('unidade_id') == $unidade->id)>
                                            {{ $unidade->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Botão final (full width, azul, igual protótipo) --}}
                    <div class="mt-4">
                        <button type="submit"
                                class="w-full px-6 py-3 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium shadow-sm">
                            Criar Tarefa ASO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
