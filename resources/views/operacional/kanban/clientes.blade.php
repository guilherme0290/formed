@extends('layouts.operacional')

@section('title', 'Nova Tarefa - Selecionar Empresa')

@section('content')
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Voltar ao painel --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('operacional.kanban') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>←</span>
                <span>Voltar ao Painel</span>
            </a>
            <span class="text-xs text-slate-500">
                Selecione um cliente para criar a tarefa.
            </span>
        </div>

        {{-- Card principal --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            {{-- Cabeçalho azul --}}
            <div class="bg-[color:var(--color-brand-azul)] px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-lg md:text-xl font-semibold text-white">
                        Nova Tarefa - Selecione a Empresa
                    </h1>
                </div>
            </div>

            {{-- Conteúdo --}}
            <div class="px-6 py-5 space-y-5">

                {{-- Busca --}}
                <form method="GET" class="mb-4 flex flex-col sm:flex-row gap-2 sm:items-center">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">🔍</span>
                        <input type="text"
                               id="kanban-clientes-input"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="Pesquisar por razão social, fantasia ou CNPJ..."
                               autocomplete="off"
                               class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                        <div id="kanban-clientes-list"
                             class="absolute z-20 mt-1 w-full max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg hidden"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                            Buscar
                        </button>
                        <a href="{{ route('operacional.kanban.aso.clientes') }}"
                           class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-600 hover:bg-slate-50">
                            Limpar
                        </a>
                    </div>
                </form>

                {{-- Grid de empresas --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @forelse($clientes as $cliente)
                        @php
                            $temContrato = (bool) ($cliente->tem_contrato_ativo ?? false);
                        @endphp
                        <div class="group flex items-center justify-between px-4 py-3 rounded-xl border border-slate-200 bg-white {{ $temContrato ? 'hover:bg-sky-50 hover:border-sky-300' : 'opacity-60' }} transition">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-sky-50 text-sky-600 text-lg">
                                    🏢
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-sm font-medium text-slate-900">
                                        {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        @if($cliente->cnpj)
                                            {{ $cliente->cnpj }}
                                        @else
                                            &nbsp;
                                        @endif
                                    </p>
                                    @if($cliente->cidade)
                                        <p class="text-[11px] text-slate-400">
                                            {{ $cliente->cidade->nome }} - {{ $cliente->cidade->uf }}
                                        </p>
                                    @endif
                                    @unless($temContrato)
                                        <p class="text-[11px] text-amber-600">
                                            Sem contrato ativo — acione o Comercial.
                                        </p>
                                    @endunless
                                </div>
                            </div>

                            @if($temContrato)
                                <a href="{{ route('operacional.kanban.servicos', $cliente) }}"
                                   class="flex items-center gap-1 text-xs font-medium text-sky-600 group-hover:text-sky-700">
                                    <span>Selecionar</span>
                                    <span>›</span>
                                </a>
                            @else
                                <span class="text-[11px] text-slate-400">Bloqueado</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Nenhum cliente encontrado.</p>
                    @endforelse
                </div>

                {{-- Paginação --}}
                <div class="pt-2">
                    {{ $clientes->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initTailwindAutocomplete?.(
                'kanban-clientes-input',
                'kanban-clientes-list',
                @json($clienteAutocomplete ?? [])
            );
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form[method="GET"]');
            const input = document.getElementById('kanban-clientes-input');
            if (!form || !input) return;

            let timer = null;
            const delay = 350;

            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    form.submit();
                }, delay);
            });
        });
    </script>
@endpush
