{{-- resources/views/cliente/funcionarios/index.blade.php --}}
@extends('layouts.cliente')

@section('title', 'Meus Funcionários')

@section('content')
    {{-- Cabeçalho / breadcrumb simples --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg md:text-xl font-semibold text-slate-900">
                Meus Funcionários
            </h1>
            <p class="text-xs md:text-sm text-slate-500">
                Funcionários vinculados ao cliente
                <span class="font-medium text-slate-700">
                    {{ $cliente->nome_fantasia ?? $cliente->razao_social }}
                </span>
            </p>
        </div>

        <a href="{{ route('cliente.dashboard') }}"
           class="hidden sm:inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium
                  border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
            ← Voltar aos serviços
        </a>
    </div>

    {{-- Cards de resumo (Ativos, Inativos, Docs a vencer, Docs vencidos) --}}
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm">
            <p class="text-[11px] uppercase tracking-wide text-slate-500">
                Funcionários Ativos
            </p>
            <p class="mt-2 text-2xl font-semibold text-emerald-600">
                {{ $totalAtivos ?? 0 }}
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm">
            <p class="text-[11px] uppercase tracking-wide text-slate-500">
                Inativos
            </p>
            <p class="mt-2 text-2xl font-semibold text-slate-700">
                {{ $totalInativos ?? 0 }}
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm">
            <p class="text-[11px] uppercase tracking-wide text-slate-500">
                Docs Vencendo
            </p>
            <p class="mt-2 text-2xl font-semibold text-amber-500">
                {{ $totalDocsVencendo ?? 0 }}
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm">
            <p class="text-[11px] uppercase tracking-wide text-slate-500">
                Docs Vencidos
            </p>
            <p class="mt-2 text-2xl font-semibold text-rose-500">
                {{ $totalDocsVencidos ?? 0 }}
            </p>
        </div>
    </div>

    {{-- Barra de filtros --}}
    <div class="bg-white border border-slate-200 rounded-2xl px-4 py-3 md:px-5 md:py-4 shadow-sm mb-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex-1 flex flex-col md:flex-row md:items-center md:gap-3">
                <div class="relative flex-1">
                    <form method="get" id="filtro-funcionarios-form">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">
                            🔍
                        </span>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q }}"
                            placeholder="Buscar por nome, CPF ou função..."
                            class="w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 text-sm
                                   placeholder:text-slate-400 focus:outline-none
                                   focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </form>
                </div>

                <div class="mt-2 md:mt-0">
                    <select
                        form="filtro-funcionarios-form"
                        name="status"
                        onchange="this.form.submit()"
                        class="w-full md:w-40 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs
                               text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="todos"   @selected($status === 'todos')>Todos</option>
                        <option value="ativos"  @selected($status === 'ativos')>Ativos</option>
                        <option value="inativos" @selected($status === 'inativos')>Inativos</option>
                    </select>
                </div>
            </div>

            <div class="flex-shrink-0">
                <a href="{{ route('cliente.funcionarios.create') }}"
                   class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-semibold
                          bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                    + Novo funcionário
                </a>
            </div>
        </div>
    </div>

    {{-- Grid de cards de funcionários --}}
    @if($funcionarios->count())
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($funcionarios as $f)
                <a href="{{ route('cliente.funcionarios.show', $f) }}"
                   class="group bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm
                          hover:border-sky-400 hover:shadow-md transition">
                    <div class="flex justify-between gap-2 mb-1.5">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 group-hover:text-sky-700">
                                {{ $f->nome }}
                            </p>
                            <p class="text-[11px] text-slate-500">
                                {{ $f->funcao->nome ?? 'Função não informada' }}
                            </p>
                        </div>

                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium
                                     {{ $f->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $f->ativo ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>

                    <div class="mt-2 space-y-1 text-[11px] text-slate-500">
                        <p>
                            <span class="font-medium text-slate-600">CPF:</span>
                            {{ $f->cpf }}
                        </p>
                        @if($f->telefone ?? false)
                            <p>
                                <span class="font-medium text-slate-600">Telefone:</span>
                                {{ $f->telefone }}
                            </p>
                        @endif
                        @if($f->vaga_atual ?? false)
                            <p>
                                <span class="font-medium text-slate-600">Vaga atual:</span>
                                {{ $f->vaga_atual }}
                            </p>
                        @endif
                    </div>

                    <div class="mt-3 flex items-center justify-between text-[11px] text-slate-400">
                        <span>
                            Admissão:
                            @if($f->data_admissao)
                                <span class="text-slate-600 font-medium">
                                    {{ \Carbon\Carbon::parse($f->data_admissao)->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-slate-500">—</span>
                            @endif
                        </span>

                        <span class="inline-flex items-center gap-0.5 text-sky-500 group-hover:text-sky-600">
                            <span>Ver detalhes</span>
                            <span>›</span>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- paginação --}}
        @if($funcionarios->hasPages())
            <div class="mt-5">
                {{ $funcionarios->links() }}
            </div>
        @endif
    @else
        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-6 text-center">
            <p class="text-xs md:text-sm text-slate-500">
                Nenhum funcionário cadastrado ainda.
            </p>
        </div>
    @endif
@endsection
