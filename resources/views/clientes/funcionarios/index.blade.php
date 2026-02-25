{{-- resources/views/cliente/funcionarios/index.blade.php --}}
@extends('layouts.cliente')

@section('title', 'Meus Funcionários')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canCreate = $isMaster || isset($permissionMap['cliente.funcionarios.create']);
        $canUpdate = $isMaster || isset($permissionMap['cliente.funcionarios.update']);
    @endphp
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
            &larr; Voltar aos serviços
        </a>
    </div>
    {{-- Resumo unificado --}}
    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-inner overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-indigo-200 bg-indigo-100/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Resumo de Funcionários</p>
        </div>

        <div class="p-4 md:p-5">
            <div class="rounded-xl border border-indigo-200/80 bg-white/95 p-3 md:p-4 shadow-sm">
                <div class="grid gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-wide text-emerald-700">Funcionários Ativos</p>
                        <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ $totalAtivos ?? 0 }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-wide text-slate-600">Inativos</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-700">{{ $totalInativos ?? 0 }}</p>
                    </div>

                    <div class="rounded-xl border border-amber-100 bg-amber-50/80 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-wide text-amber-700">Docs Vencendo</p>
                        <p class="mt-1 text-2xl font-semibold text-amber-500">{{ $totalDocsVencendo ?? 0 }}</p>
                    </div>

                    <div class="rounded-xl border border-rose-100 bg-rose-50/80 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-wide text-rose-700">Docs Vencidos</p>
                        <p class="mt-1 text-2xl font-semibold text-rose-500">{{ $totalDocsVencidos ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Grid de cards de funcionários --}}
    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-inner overflow-hidden">
        <div class="px-4 py-3 border-b border-indigo-200 bg-indigo-100/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Funcionários</p>
        </div>
        <div class="p-4 md:p-5">
            <div class="rounded-xl border border-indigo-200/80 bg-white/95 p-3 md:p-4 shadow-sm">
                <form method="get" id="filtro-funcionarios-form"
                      class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex-1 flex flex-col md:flex-row md:items-center md:gap-3">
                        <div class="relative flex-1">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">
                                &#128269;
                            </span>
                            <input
                                type="text"
                                name="q"
                                value="{{ $q }}"
                                placeholder="Buscar por nome, CPF ou função..."
                                class="w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 text-sm
                                       placeholder:text-slate-400 focus:outline-none
                                       focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>

                        <div class="mt-2 md:mt-0">
                            <select
                                name="funcao_id"
                                onchange="this.form.submit()"
                                class="w-full md:w-56 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs
                                       text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                <option value="">Todas as funções</option>
                                @foreach($funcoes as $funcao)
                                    <option value="{{ $funcao->id }}" @selected((int) $funcaoId === (int) $funcao->id)>
                                        {{ $funcao->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-2 md:mt-0">
                            <select
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
                        <a href="{{ $canCreate ? route('cliente.funcionarios.create') : 'javascript:void(0)' }}"
                           @if(!$canCreate) title="Usuario sem permissao." aria-disabled="true" @endif
                           class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-semibold shadow-sm
                                  {{ $canCreate ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                            + Novo funcionário
                        </a>
                    </div>
                </form>

    @if($funcionarios->count())
        <div class="max-h-[62vh] overflow-y-auto pr-1">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($funcionarios as $f)
                    <div
                        class="group bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm
                               hover:border-sky-400 hover:shadow-md transition cursor-pointer"
                        data-card-url="{{ route('cliente.funcionarios.show', $f) }}"
                        role="link"
                        tabindex="0"
                    >
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

                        {{-- CELULAR --}}
                        @if($f->celular ?? false)
                            <p>
                                <span class="font-medium text-slate-600">Celular:</span>
                                {{ $f->celular }}
                            </p>
                        @endif

                        {{-- SETOR --}}
                        @if($f->setor ?? false)
                            <p>
                                <span class="font-medium text-slate-600">Setor:</span>
                                {{ $f->setor }}
                            </p>
                        @endif

                        {{-- VAGA ATUAL (já existia) --}}
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
                                <span class="text-slate-500">-</span>
                            @endif
                        </span>

                        <span class="inline-flex items-center gap-2 text-sky-500">
                            <a href="{{ $canUpdate ? route('cliente.funcionarios.edit', $f) : 'javascript:void(0)' }}"
                               @if(!$canUpdate) title="Usuario sem permissao." aria-disabled="true" @endif
                               class="font-medium {{ $canUpdate ? 'hover:text-sky-600' : 'text-slate-400 cursor-not-allowed' }}"
                               data-card-edit
                               onclick="event.stopPropagation()">
                                Editar
                            </a>
                            <span class="text-slate-300">&bull;</span>
                            <span class="group-hover:text-sky-600">Ver detalhes</span>
                        </span>
                    </div>
                    </div>
                @endforeach
            </div>
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
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-card-url]').forEach((card) => {
            const url = card.dataset.cardUrl;
            if (!url) return;
            card.addEventListener('click', (event) => {
                if (event.target.closest('[data-card-edit]')) return;
                window.location.href = url;
            });
            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    window.location.href = url;
                }
            });
        });
    </script>
@endpush


