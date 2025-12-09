@extends('layouts.comercial')
@section('title', 'Criar Proposta Comercial')

@section('content')
    <div class="mb-4">
        <a href="{{ route('comercial.dashboard') }}"
           class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800">
            ← Voltar ao Painel
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-200">
        <div class="px-6 py-4 border-b bg-blue-600 text-white rounded-t-2xl">
            <h1 class="text-lg font-semibold">Criar Nova Proposta Comercial</h1>
        </div>

        <div class="p-6 space-y-8">
            {{-- 1. Cliente Final --}}
            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-700">
                    1. Cliente Final
                </h2>

                <div class="flex flex-wrap gap-2">
                    {{-- botões de aba: Cliente Final / Novo Cliente Final --}}
                    {{-- aqui dá pra usar Alpine para alternar --}}
                </div>

                <div class="mt-3">
                    <label class="text-xs font-medium text-slate-600">Cliente Final</label>
                    <select name="cliente_id" class="mt-1 w-full border rounded-lg text-sm px-3 py-2">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            {{-- 2. Vendedor --}}
            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-700">
                    2. Vendedor
                </h2>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-slate-600">Nome</label>
                        <input type="text" class="mt-1 w-full border rounded-lg text-sm px-3 py-2 bg-slate-50"
                               value="{{ $user->name }}" readonly>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600">Telefone</label>
                        <input type="text" class="mt-1 w-full border rounded-lg text-sm px-3 py-2"
                               value="{{ $user->telefone ?? '' }}" readonly>
                    </div>
                </div>
            </section>

            {{-- 3. Serviços (botões de seleção) --}}
            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-700">
                    3. Serviços
                </h2>

                <div class="flex flex-wrap gap-2">
                    @foreach($servicos as $servico)
                        <button type="button"
                                class="px-3 py-2 rounded-lg border text-sm bg-slate-50 hover:bg-slate-100"
                            {{-- aqui você dispara JS para adicionar o serviço na lista de itens --}}
                        >
                            + {{ $servico->nome }}
                        </button>
                    @endforeach

                    {{-- botão especial Pacote de Exames abre modal --}}
                    <button type="button"
                            class="px-3 py-2 rounded-lg border text-sm bg-blue-50 hover:bg-blue-100">
                        + Pacote de Exames
                    </button>
                </div>

                {{-- Serviços Adicionados (preenchimento de valor/prazo/qtd) --}}
                <div class="mt-5">
                    <h3 class="text-xs font-semibold text-slate-600 mb-2">
                        Serviços Adicionados
                    </h3>

                    <div id="lista-itens">
                        {{-- aqui JS vai renderizar linhas de itens (nome, valor, prazo, qtd, total) --}}
                    </div>
                </div>
            </section>

            {{-- 4. E-Social (Opcional) --}}
            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-700">
                    4. E-Social (Opcional)
                </h2>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="incluir_esocial" value="1" class="rounded border-slate-300">
                    Incluir E-Social (mensal)
                </label>

                {{-- box amarelo com qtd funcionários + valor mensal --}}
                {{-- você controla via JS quando exibir --}}
            </section>

            {{-- 5. Forma de Pagamento --}}
            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-700">
                    5. Forma de Pagamento *
                </h2>

                <select name="forma_pagamento" class="w-full border rounded-lg text-sm px-3 py-2">
                    <option value="">Selecione...</option>
                    @foreach($formasPagamento as $fp)
                        <option value="{{ $fp }}">{{ $fp }}</option>
                    @endforeach
                </select>
            </section>

            {{-- Rodapé: Valor Total + Botões --}}
            <section class="pt-4 border-t flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="bg-blue-600 text-white rounded-2xl px-6 py-4">
                    <p class="text-xs uppercase tracking-wide">Valor Total</p>
                    <p class="text-2xl font-semibold" id="valor-total-display">
                        R$ 0,00
                    </p>
                    <p class="text-[11px] opacity-80">*E-Social mensal incluso se selecionado</p>
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium">
                        Salvar &amp; Proposta
                    </button>
                    {{-- botão Apresentação fica pra depois --}}
                </div>
            </section>
        </div>
    </div>
@endsection
