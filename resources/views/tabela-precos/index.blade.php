@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold">Tabela de Preços</h1>
                <p class="text-sm text-slate-500">Valores globais dos serviços.</p>
            </div>

            <button x-data x-on:click="$dispatch('open-modal','preco-form')"
                    class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">
                + Novo Item
            </button>
        </div>

        @if (session('ok'))
            <div class="mb-4 rounded-xl bg-emerald-50 text-emerald-800 px-4 py-3 border border-emerald-200">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-xl bg-rose-50 text-rose-800 px-4 py-3 border border-rose-200">
                <div class="font-semibold mb-1">Erro ao salvar:</div>
                <ul class="list-disc ml-5 text-sm">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ======================== FILTROS ======================== --}}
        <form method="GET" class="grid md:grid-cols-3 gap-3 mb-4">
            <input name="q" value="{{ request('q', $busca ?? '') }}" class="input" placeholder="Buscar...">

            @php($statusSel = request('status', $status ?? ''))
            <select name="status" class="input">
                <option value="">Todos</option>
                <option value="ativos"   @selected($statusSel==='ativos')>Ativos</option>
                <option value="inativos" @selected($statusSel==='inativos')>Inativos</option>
            </select>

            @php($tipoSel = request('tipo', $tipo ?? ''))
            <select name="tipo" class="input">
                <option value="">Todos os tipos</option>
                @foreach($tipos as $t)
                    <option value="{{ $t }}" @selected($tipoSel===$t)>{{ $t }}</option>
                @endforeach
            </select>

            <button class="rounded-xl bg-slate-900 text-white px-4">Filtrar</button>
        </form>


        {{-- ======================== LISTAGEM ======================== --}}
        <div class="bg-white rounded-2xl shadow-sm border">
            <div class="grid grid-cols-12 text-xs font-semibold text-slate-500 px-4 py-3 border-b">
                <div class="col-span-5">Serviço</div>
                <div class="col-span-2">Código</div>
                <div class="col-span-2">Preço (R$)</div>
                <div class="col-span-1">Status</div>
                <div class="col-span-2 text-right">Ações</div>
            </div>

            @forelse($itens as $item)
                <div class="grid grid-cols-12 items-center px-4 py-3 border-b last:border-0">

                    {{-- Serviço --}}
                    <div class="col-span-5">
                        <div class="font-medium">{{ $item->servico->nome ?? '—' }}</div>
                        @if($item->descricao)
                            <div class="text-xs text-slate-500">{{ $item->descricao }}</div>
                        @endif
                    </div>

                    {{-- Código --}}
                    <div class="col-span-2 text-sm">{{ $item->codigo ?? '—' }}</div>

                    {{-- Preço --}}
                    <div class="col-span-2 font-semibold">
                        {{ number_format($item->preco ?? 0, 2, ',', '.') }}
                    </div>

                    {{-- Status --}}
                    <div class="col-span-1">
                        <span class="text-xs px-2 py-1 rounded-md {{ $item->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $item->ativo ? 'ativo' : 'inativo' }}
                        </span>
                    </div>

                    {{-- Ações --}}
                    <div class="col-span-2 text-right">
                        <div class="inline-flex gap-2">

                            {{-- EDITAR — modo sempre "novo_servico" --}}
                            <button x-data
                                    x-on:click="$dispatch('open-modal',{
                                        name:'preco-form',
                                        params:{
                                            id: {{ $item->id }},

                                            // dados do serviço
                                            novo_nome: '{{ addslashes($item->servico->nome ?? '') }}',
                                            novo_tipo: '{{ addslashes($item->servico->tipo ?? '') }}',
                                            novo_esocial: '{{ addslashes($item->servico->esocial ?? '') }}',
                                            novo_valor_base: '{{ number_format($item->servico->valor_base ?? 0,2,',','.') }}',

                                            // dados do item
                                            codigo: '{{ addslashes($item->codigo ?? '') }}',
                                            descricao: '{{ addslashes($item->descricao ?? '') }}',
                                            preco: '{{ number_format($item->preco ?? 0,2,',','.') }}',
                                            ativo: {{ $item->ativo ? 1 : 0 }},

                                            update_url: '{{ route('tabela-precos.items.update',$item) }}'
                                        }
                                    })"
                                    class="text-indigo-600 hover:underline text-sm">
                                Editar
                            </button>

                            {{-- Toggle --}}
                            <form method="POST" action="{{ route('tabela-precos.items.toggle',$item) }}">
                                @csrf @method('PATCH')
                                <button class="text-slate-600 hover:underline text-sm">
                                    {{ $item->ativo ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>

                            {{-- Excluir --}}
                            <form method="POST" action="{{ route('tabela-precos.items.destroy',$item) }}"
                                  onsubmit="return confirm('Excluir permanentemente?');">
                                @csrf @method('DELETE')
                                <button class="text-rose-600 hover:underline text-sm">
                                    Excluir
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            @empty
                <div class="px-4 py-10 text-center text-slate-500">
                    Nenhum item cadastrado ainda.
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $itens->links() }}</div>
    </div>

    @include('tabela-precos._modal_form')
@endsection
