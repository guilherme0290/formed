{{-- resources/views/cliente/funcionarios/index.blade.php --}}
@extends('layouts.cliente')

@section('title', 'Meus Funcion&aacute;rios')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canCreate = $isMaster || isset($permissionMap['cliente.funcionarios.create']);
        $canUpdate = $isMaster || isset($permissionMap['cliente.funcionarios.update']);
        $totalFuncionarios = (int) (($totalAtivos ?? 0) + ($totalInativos ?? 0));
        $opcoesBuscaFuncionarios = collect($funcionariosBusca ?? [])
            ->map(function ($funcionarioNome) {
                return (string) $funcionarioNome;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
    @endphp

    <section class="overflow-hidden rounded-2xl border border-blue-300 shadow-sm mb-6">
        <div class="bg-gradient-to-r from-[#123fbe] to-[#1a5de8] px-4 py-4 md:px-6 md:py-5 text-white">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg md:text-xl font-semibold">Meus Funcion&aacute;rios</h1>
                </div>

                <a href="{{ route('cliente.dashboard') }}"
                   class="inline-flex w-full sm:w-auto items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold border border-blue-200/50 bg-white/10 text-white hover:bg-white/20 transition">
                    &larr; Voltar aos servi&ccedil;os
                </a>
            </div>
        </div>

        <div class="bg-blue-50/70 border-t border-blue-200 px-4 py-4 md:px-6 md:py-5">
            <p class="text-[11px] md:text-xs font-semibold uppercase tracking-wide text-blue-800">Resumo de Funcion&aacute;rios</p>

            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <article class="rounded-xl border border-blue-200 bg-white px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-blue-700">Total de Funcion&aacute;rios</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-800">{{ $totalFuncionarios }}</p>
                </article>

                <article class="rounded-xl border border-blue-200 bg-white px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-blue-700">Funcion&aacute;rios Ativos</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-800">{{ $totalAtivos ?? 0 }}</p>
                </article>

                <article class="rounded-xl border border-blue-200 bg-white px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-blue-700">Inativos</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-800">{{ $totalInativos ?? 0 }}</p>
                </article>
            </div>
        </div>
    </section>

    <div class="rounded-2xl border border-blue-200 bg-blue-50/40 shadow-inner overflow-hidden">
        <div class="px-4 py-3 border-b border-blue-200 bg-blue-100/60">
            <form method="get" id="filtro-funcionarios-form" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 mb-2">Funcion&aacute;rios</p>
                    <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                        <div class="w-full sm:max-w-[260px]">
                            <select
                                name="q"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                <option value="">Selecionar funcion&aacute;rio</option>
                                @foreach($opcoesBuscaFuncionarios as $nomeFuncionario)
                                    <option value="{{ $nomeFuncionario }}" @selected((string) $q === (string) $nomeFuncionario)>
                                        {{ $nomeFuncionario }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex w-full sm:w-auto items-center justify-center px-3 py-2 rounded-xl bg-blue-700 text-white text-xs font-semibold hover:bg-blue-800 transition">
                            Buscar
                        </button>
                    </div>
                </div>

                <div class="flex-shrink-0 w-full lg:w-auto">
                    <a href="{{ $canCreate ? route('cliente.funcionarios.create') : 'javascript:void(0)' }}"
                       @if(!$canCreate) title="Usu&aacute;rio sem permiss&atilde;o." aria-disabled="true" @endif
                       class="inline-flex w-full lg:w-auto items-center justify-center gap-1 px-3 py-2 rounded-xl text-xs font-semibold shadow-sm {{ $canCreate ? 'bg-blue-700 text-white hover:bg-blue-800' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                        + Novo funcion&aacute;rio
                    </a>
                </div>
            </form>
        </div>

        <div class="p-4 md:p-5">
            <div class="rounded-xl border border-blue-200 bg-white p-3 md:p-4 shadow-sm">
                @if($funcionarios->count())
                    <div class="max-h-[52vh] md:max-h-[62vh] overflow-y-auto pr-1">
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($funcionarios as $f)
                                <div
                                    class="group bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm hover:border-sky-400 hover:shadow-md transition cursor-pointer"
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
                                                {{ $f->funcao->nome ?? 'Fun&ccedil;&atilde;o n&atilde;o informada' }}
                                            </p>
                                        </div>

                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $f->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $f->ativo ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </div>

                                    <div class="mt-2 space-y-1 text-[11px] text-slate-500">
                                        <p>
                                            <span class="font-medium text-slate-600">CPF:</span>
                                            {{ $f->cpf }}
                                        </p>

                                        @if($f->celular ?? false)
                                            <p>
                                                <span class="font-medium text-slate-600">Celular:</span>
                                                {{ $f->celular }}
                                            </p>
                                        @endif

                                        @if($f->setor ?? false)
                                            <p>
                                                <span class="font-medium text-slate-600">Setor:</span>
                                                {{ $f->setor }}
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
                                            Admiss&atilde;o:
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
                                               @if(!$canUpdate) title="Usu&aacute;rio sem permiss&atilde;o." aria-disabled="true" @endif
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

                    @if($funcionarios->hasPages())
                        <div class="mt-5">
                            {{ $funcionarios->links() }}
                        </div>
                    @endif
                @else
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-6 text-center">
                        <p class="text-xs md:text-sm text-slate-500">
                            Nenhum funcion&aacute;rio cadastrado ainda.
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
