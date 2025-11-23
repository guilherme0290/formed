@extends('layouts.operacional')

@section('title', 'Nova Tarefa - Selecionar Empresa')

@section('content')
    <div class="max-w-6xl mx-auto px-6 py-8">

        {{-- Voltar ao painel --}}
        <div class="mb-4">
            <a href="{{ route('operacional.kanban') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>←</span>
                <span>Voltar ao Painel</span>
            </a>
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

                {{-- Novo Cliente (ajuste a rota se necessário) --}}
                <a href="{{ route('clientes.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-xs md:text-sm font-medium text-white shadow-sm">
                    <span>+ Novo Cliente</span>
                </a>
            </div>

            {{-- Conteúdo --}}
            <div class="px-6 py-5 space-y-5">

                {{-- Busca --}}
                <form method="GET" class="mb-4">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">🔍</span>
                        <input type="text"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="Pesquisar empresa..."
                               class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                    </div>
                </form>

                {{-- Grid de empresas --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @forelse($clientes as $cliente)
                        <a href="{{ route('operacional.kanban.aso.servicos', $cliente) }}"
                           class="group flex items-center justify-between px-4 py-3 rounded-xl border border-slate-200 bg-white hover:bg-sky-50 hover:border-sky-300 transition">
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
                                </div>
                            </div>

                            <div class="flex items-center gap-1 text-xs font-medium text-sky-600 group-hover:text-sky-700">
                                <span>Selecionar</span>
                                <span>›</span>
                            </div>
                        </a>
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
