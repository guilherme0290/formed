@extends('layouts.master')

@section('content')

    {{-- MENSAGENS --}}
    @if (session('ok'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('ok') }}
        </div>
    @endif

    @if (session('erro'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ session('erro') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Clientes</h1>
            <p class="text-sm text-gray-500">Gerencie os clientes da sua empresa.</p>
        </div>

        <a href="{{ route('clientes.create') }}"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700">
            Novo Cliente
        </a>
    </div>

    {{-- FILTRO --}}
    <div class="bg-white rounded-xl p-5 shadow border mb-6">
        <form method="GET" class="grid md:grid-cols-4 gap-4">

            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Busca</label>
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="Buscar por nome, CNPJ, e-mail..."
                       class="w-full rounded-lg border-gray-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 px-3 py-2">
                    <option value="todos"  {{ $status=='todos'   ? 'selected' : '' }}>Todos</option>
                    <option value="ativo"  {{ $status=='ativo'   ? 'selected' : '' }}>Ativo</option>
                    <option value="inativo"{{ $status=='inativo' ? 'selected' : '' }}>Inativo</option>
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow">
                    Filtrar
                </button>
                <a href="{{ route('clientes.index') }}"
                   class="px-4 py-2 bg-gray-200 rounded-lg">
                    Limpar
                </a>
            </div>

        </form>
    </div>

    {{-- TABELA --}}
    <div class="bg-white shadow rounded-xl border overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
            <tr>
                <th class="px-4 py-2 text-left">Cliente</th>
                <th class="px-4 py-2 text-left">CNPJ</th>
                <th class="px-4 py-2 text-left">Endereço</th>
                <th class="px-4 py-2 text-left">Contato</th>
                <th class="px-4 py-2 text-center">Acesso</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2 text-center">Ações</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($clientes as $cliente)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $cliente->razao_social }}</div>
                        @if($cliente->nome_fantasia)
                            <div class="text-xs text-gray-500">{{ $cliente->nome_fantasia }}</div>
                        @endif
                    </td>

                    <td class="px-4 py-3">{{ $cliente->cnpj }}</td>

                    <td class="px-4 py-3">
                        {{ $cliente->endereco }}
                        @if($cliente->bairro)
                            <div class="text-xs text-gray-500">{{ $cliente->bairro }}</div>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        {{ $cliente->email }} <br>
                        {{ $cliente->telefone }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        @if($cliente->userCliente)
                            <span class="px-3 py-1 text-emerald-700 bg-emerald-100 rounded-full text-xs">
                                Acesso criado
                            </span>
                        @else
                            <span class="px-3 py-1 text-slate-600 bg-slate-100 rounded-full text-xs">
                                Sem acesso
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-center">
                        @if($cliente->ativo)
                            <span class="px-3 py-1 text-green-700 bg-green-100 rounded-full text-xs">
                                Ativo
                            </span>
                        @else
                            <span class="px-3 py-1 text-red-700 bg-red-100 rounded-full text-xs">
                                Inativo
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <a href="{{ route('clientes.edit', $cliente) }}"
                               class="px-3 py-1 text-blue-700 bg-blue-100 rounded-lg text-xs">
                                Editar
                            </a>

                            <form action="{{ route('clientes.destroy', $cliente) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('Tem certeza que deseja excluir este cliente?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1 text-red-700 bg-red-100 rounded-lg text-xs">
                                    Excluir
                                </button>
                            </form>

                            <a href="{{ route('clientes.acesso.form', $cliente) }}"
                               class="px-3 py-1 text-indigo-700 bg-indigo-100 rounded-lg text-xs {{ $cliente->email ? '' : 'opacity-50 cursor-not-allowed' }}"
                               {{ $cliente->email ? '' : 'aria-disabled=true tabindex=-1' }}>
                                🔑 Criar acesso
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-4 text-center text-gray-500" colspan="6">
                        Nenhum cliente encontrado.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="p-4">
            {{ $clientes->links() }}
        </div>
    </div>

@endsection
