@extends('layouts.app')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-semibold">Painel Operacional</h1>
            <p class="text-sm text-gray-500">Suas tarefas atribuídas - {{ auth()->user()->name }}</p>
        </div>
        <button x-data x-on:click="$dispatch('open-modal','novo-servico')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
            + Nova Tarefa (Loja)
        </button>
    </div>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto px-0 py-0">

        {{-- Cards resumo --}}
        <div class="grid grid-cols-5 gap-4 mb-6">
            <x-card-metric label="Pendente" :value="$totais['pendente']" />
            <x-card-metric label="Em Execução" :value="$totais['execucao']" />
            <x-card-metric label="Aguardando Cliente" :value="$totais['aguardando_cliente']" />
            <x-card-metric label="Concluído" :value="$totais['concluido']" />
            <x-card-metric label="Atrasado" :value="$totais['atrasado']" />
        </div>

        {{-- Listagem de tarefas --}}
        <div class="grid md:grid-cols-2 gap-6">
            <x-coluna-tarefas titulo="Pendente" :tarefas="$pendentes" />
            <x-coluna-tarefas titulo="Em Execução" :tarefas="$execucao" />
            <x-coluna-tarefas titulo="Aguardando Cliente" :tarefas="$aguardando" />
            <x-coluna-tarefas titulo="Concluído" :tarefas="$concluidos" />
            <x-coluna-tarefas titulo="Atrasado" :tarefas="$atrasados" />
        </div>
    </div>

    {{-- Modal Nova Tarefa --}}
    <x-modal name="novo-servico">
        <div x-data="{tab:'existente'}" class="p-6">
            <div class="flex gap-2 mb-4">
                <button class="px-3 py-1 rounded-lg border" :class="tab==='existente' && 'bg-gray-100'" @click="tab='existente'">Cliente existente</button>
                <button class="px-3 py-1 rounded-lg border" :class="tab==='novo' && 'bg-gray-100'" @click="tab='novo'">Novo cliente</button>
            </div>

            {{-- EXISTENTE --}}
            <form x-show="tab==='existente'" method="POST" action="{{ route('operacional.tarefas.loja.existente') }}" class="space-y-3">
                @csrf
                <x-select-ajax name="cliente_id" label="Cliente (CNPJ/Razão)" endpoint="{{ route('api.clientes.index') }}" />
                @include('operacional.painel._campos_tarefa')
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Salvar</button>
            </form>

            {{-- NOVO CLIENTE --}}
            <form x-show="tab==='novo'" method="POST" action="{{ route('operacional.tarefas.loja.novo') }}" class="space-y-3">
                @csrf
                <div class="grid md:grid-cols-2 gap-3">
                    <input name="razao_social"  class="input" placeholder="Razão Social *">
                    <input name="nome_fantasia" class="input" placeholder="Nome Fantasia">
                    <input name="cnpj"          class="input" placeholder="CNPJ">
                    <input name="email"         class="input" placeholder="E-mail">
                    <input name="telefone"      class="input" placeholder="Telefone">
                    <input name="endereco"      class="input md:col-span-2" placeholder="Endereço">
                </div>
                @include('operacional.painel._campos_tarefa')
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Salvar</button>
            </form>
        </div>
    </x-modal>
@endsection
