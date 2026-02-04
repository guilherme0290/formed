@extends('layouts.cliente')

@section('title', 'Detalhes do Funcionário')
@section('page-container', 'w-full p-0')

@section('content')

    {{-- 🔧 ALTERAÇÃO: wrapper centralizado e com mais respiro na tela --}}
    <div class="w-full px-3 md:px-5 py-4 md:py-5">

        {{-- Barra de navegação local --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex gap-2">
                <a href="{{ route('cliente.dashboard') }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium
                          border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                    ← Voltar ao início
                </a>

                <a href="{{ route('cliente.funcionarios.index') }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium
                          border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                    ← Voltar para lista
                </a>
            </div>
            <a href="{{ route('cliente.funcionarios.edit', $funcionario) }}"
               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold
                      bg-indigo-600 text-white hover:bg-indigo-700">
                ✏️ Editar informações
            </a>
        </div>

        {{-- Card principal do funcionário --}}
        {{-- 🔧 ALTERAÇÃO: sombra um pouco mais forte --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-md overflow-hidden">
            {{-- faixa azul com nome + status --}}
            <div class="bg-sky-700 text-white px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold">
                        {{ $funcionario->nome }}
                    </h1>
                    <p class="text-xs text-sky-100">
                        {{ $funcionario->funcao->nome ?? 'Função não informada' }}
                    </p>
                </div>

                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                             {{ $funcionario->ativo ? 'bg-emerald-400 text-slate-900' : 'bg-slate-100 text-slate-700' }}">
                    {{ $funcionario->ativo ? 'Ativo' : 'Inativo' }}
                </span>
            </div>

            {{-- conteúdo em 2 colunas --}}
            {{-- 🔧 ALTERAÇÃO: padding e espaçamento um pouco maiores --}}
            <div class="px-6 py-6 grid gap-6 md:gap-8 md:grid-cols-2">
                {{-- Informações pessoais --}}
                <div>
                    <h2 class="text-sm font-semibold text-slate-800 mb-3">
                        Informações Pessoais
                    </h2>

                    <dl class="space-y-2 text-sm text-slate-700">
                        <div>
                            <dt class="text-xs font-medium text-slate-500">CPF</dt>
                            <dd>{{ $funcionario->cpf }}</dd>
                        </div>

                        {{-- *** ALTERADO: usar CELULAR em vez de telefone *** --}}
                        @if($funcionario->celular ?? false)
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Celular</dt>
                                <dd>{{ $funcionario->celular }}</dd>
                            </div>
                        @endif

                        {{-- *** NOVO: mostrar SETOR se tiver *** --}}
                        @if($funcionario->setor ?? false)
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Setor</dt>
                                <dd>{{ $funcionario->setor }}</dd>
                            </div>
                        @endif

                        @if($funcionario->data_nascimento ?? false)
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Data de Nascimento</dt>
                                <dd>{{ \Carbon\Carbon::parse($funcionario->data_nascimento)->format('d/m/Y') }}</dd>
                            </div>
                        @endif

                        @if($funcionario->data_admissao ?? false)
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Data de Admissão</dt>
                                <dd>{{ \Carbon\Carbon::parse($funcionario->data_admissao)->format('d/m/Y') }}</dd>
                            </div>
                        @endif

                        @if($funcionario->vaga_atual ?? false)
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Vaga ocupada atualmente</dt>
                                <dd>{{ $funcionario->vaga_atual }}</dd>
                            </div>
                        @endif
                    </dl>

                    {{-- 🔧 ALTERAÇÃO: botão de inativar / reativar agora envia pro backend --}}
                    <div class="mt-5">
                        <form method="POST"
                              action="{{ route('cliente.funcionarios.toggle-status', $funcionario) }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold
                                           {{ $funcionario->ativo
                                                ? 'border border-red-300 text-red-700 bg-red-50 hover:bg-red-100'
                                                : 'border border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                {{ $funcionario->ativo ? 'Inativar Funcionário' : 'Reativar Funcionário' }}
                            </button>
                        </form>
                    </div>
                </div>

{{--                --}}{{-- Documentação (placeholder para o futuro) --}}
{{--                <div>--}}
{{--                    <h2 class="text-sm font-semibold text-slate-800 mb-3">--}}
{{--                        Documentação--}}
{{--                    </h2>--}}

{{--                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-6 text-sm text-slate-500">--}}
{{--                        Em breve você poderá acompanhar aqui os ASOs, treinamentos e outros documentos--}}
{{--                        do colaborador (válidos, a vencer e vencidos).--}}
{{--                    </div>--}}
{{--                </div>--}}
            </div>
        </div>
    </div>
@endsection
