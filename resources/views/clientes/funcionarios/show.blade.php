@extends('layouts.cliente')

@section('title', 'Detalhes do Funcionário')

@section('content')
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
    </div>

    {{-- Cabeçalho com cliente --}}
    <div class="mb-6 bg-[color:var(--color-brand-azul)] text-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-white/10 flex items-center justify-center">
                🏢
            </div>
            <div class="flex-1">
                <p class="text-xs text-blue-100">Cliente</p>
                <p class="text-sm font-semibold">
                    {{ $cliente->nome_fantasia ?? $cliente->razao_social }}
                </p>
                @if($cliente->cnpj ?? false)
                    <p class="text-[11px] text-blue-100">
                        CNPJ: {{ $cliente->cnpj }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Card principal do funcionário --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        {{-- faixa azul com nome + status --}}
        <div class="bg-sky-700 text-white px-5 py-4 flex items-center justify-between">
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
        <div class="px-5 py-5 grid gap-6 md:grid-cols-2">
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

                    @if($funcionario->telefone ?? false)
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Celular</dt>
                            <dd>{{ $funcionario->telefone }}</dd>
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

                {{-- botão de inativar / reativar (por enquanto só visual, sem ação) --}}
                <div class="mt-5">
                    <button type="button"
                            class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold
                                   border border-red-300 text-red-700 bg-red-50 hover:bg-red-100">
                        {{ $funcionario->ativo ? 'Inativar Funcionário' : 'Reativar Funcionário' }}
                    </button>
                </div>
            </div>

            {{-- Documentação (placeholder para o futuro) --}}
            <div>
                <h2 class="text-sm font-semibold text-slate-800 mb-3">
                    Documentação
                </h2>

                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-6 text-sm text-slate-500">
                    Em breve você poderá acompanhar aqui os ASOs, treinamentos e outros documentos
                    do colaborador (válidos, a vencer e vencidos).
                </div>
            </div>
        </div>
    </div>
@endsection
