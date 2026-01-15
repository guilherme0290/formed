@php
    $user = auth()->user();
    $layout = 'layouts.app';

    if ($user && optional($user->papel)->nome === 'Operacional') {
        $layout = 'layouts.operacional';
    } else if ($user && optional($user->papel)->nome === 'Master') {
        $layout = 'layouts.master';
    } else if ($user && optional($user->papel)->nome === 'Comercial') {
        $layout = 'layouts.comercial';
    }
@endphp

@extends($layout)

@section('content')
    @php($routePrefix = $routePrefix ?? 'clientes')
    <div class="w-full mx-auto px-4 md:px-6 xl:px-8 py-6 space-y-6">
        @if ($user && optional($user->papel)->nome === 'Master')
            <div>
                <a href="{{ route('master.dashboard') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                    Voltar ao Painel
                </a>
            </div>
        @endif

        {{-- MENSAGENS --}}
        @if (session('ok'))
            <div class="mb-2 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        @if (session('erro'))
            <div class="mb-2 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('erro') }}
            </div>
        @endif

        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">Clientes</h1>
                <p class="text-sm text-gray-500">Gerencie os clientes da sua empresa.</p>
            </div>

            <a href="{{ route($routePrefix.'.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700">
                Novo Cliente
            </a>
        </div>

        {{-- FILTRO --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <form method="GET" class="grid md:grid-cols-4 gap-4 items-end">

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1 text-slate-700">Busca (razão social, nome fantasia ou CNPJ)</label>
                    <input type="search" name="q" id="cliente-search" value="{{ $q }}"
                           list="clientes-sugestoes"
                           placeholder="Ex: 12.345.678/0001-00, Razão Social ou Nome Fantasia"
                           class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <datalist id="clientes-sugestoes">
                        @foreach ($clientes as $cliente)
                            @if($cliente->razao_social)
                                <option value="{{ $cliente->razao_social }}"></option>
                            @endif
                            @if($cliente->nome_fantasia)
                                <option value="{{ $cliente->nome_fantasia }}"></option>
                            @endif
                            @if($cliente->cnpj)
                                <option value="{{ $cliente->cnpj }}"></option>
                            @endif
                        @endforeach
                    </datalist>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 text-slate-700">Status</label>
                    <select name="status" class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="todos"  {{ $status=='todos'   ? 'selected' : '' }}>Todos</option>
                        <option value="ativo"  {{ $status=='ativo'   ? 'selected' : '' }}>Ativo</option>
                        <option value="inativo"{{ $status=='inativo' ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow">
                        Filtrar
                    </button>
                    <a href="{{ route($routePrefix.'.index') }}"
                       class="px-4 py-2 bg-gray-200 rounded-lg">
                        Limpar
                    </a>
                </div>

            </form>
        </div>

        {{-- TABELA --}}
        <div class="bg-white shadow-sm rounded-3xl border border-slate-100 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-2 text-left w-64">Cliente</th>
                    <th class="px-4 py-2 text-left w-40">CNPJ</th>
                    <th class="px-4 py-2 text-left w-52">Contato</th>
                    <th class="px-4 py-2 text-center w-20 whitespace-nowrap">Acesso</th>
                    <th class="px-4 py-2 text-center w-24">Status</th>
                    <th class="px-4 py-2 text-center w-28 whitespace-nowrap">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($clientes as $cliente)
                    <tr data-razao="{{ $cliente->razao_social }}"
                        data-fantasia="{{ $cliente->nome_fantasia }}"
                        data-cnpj="{{ preg_replace('/\D+/', '', $cliente->cnpj ?? '') }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $cliente->razao_social }}</div>
                            @if($cliente->nome_fantasia)
                                <div class="text-xs text-gray-500">{{ $cliente->nome_fantasia }}</div>
                            @endif
                        </td>

                        <td class="px-4 py-3">{{ $cliente->cnpj }}</td>

                        <td class="px-4 py-3">
                            {{ $cliente->email }} <br>
                            {{ $cliente->telefone }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if($cliente->userCliente)
                                <span class="inline-flex items-center justify-center w-9 h-9 text-emerald-700 bg-emerald-100 rounded-full text-base"
                                      title="Acesso criado"
                                      aria-label="Acesso criado">
                                    🔓
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center w-9 h-9 text-slate-600 bg-slate-100 rounded-full text-base"
                                      title="Sem acesso"
                                      aria-label="Sem acesso">
                                    🔒
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
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route($routePrefix.'.edit', $cliente) }}"
                                   class="px-3 py-2 text-blue-700 bg-blue-100 rounded-lg text-xs"
                                   title="Editar"
                                   aria-label="Editar">
                                    ✏️
                                </a>

                                <form action="{{ route($routePrefix.'.destroy', $cliente) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Tem certeza que deseja excluir este cliente?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-2 text-red-700 bg-red-100 rounded-lg text-xs"
                                            title="Excluir"
                                            aria-label="Excluir">
                                        🗑️
                                    </button>
                                </form>

                                <a href="{{ route($routePrefix.'.acesso.form', $cliente) }}"
                                   class="px-3 py-2 text-indigo-700 bg-indigo-100 rounded-lg text-xs {{ $cliente->email ? '' : 'opacity-50 cursor-not-allowed' }}"
                                   title="Criar acesso"
                                   aria-label="Criar acesso"
                                   {{ $cliente->email ? '' : 'aria-disabled=true tabindex=-1' }}>
                                    🔑
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
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('cliente-search');
            const rows = Array.from(document.querySelectorAll('tbody tr[data-razao]'));

            if (!input || rows.length === 0) {
                return;
            }

            const normalize = (value) => (value || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/\p{Diacritic}/gu, '');

            const onlyDigits = (value) => (value || '').toString().replace(/\D+/g, '');

            const filterRows = () => {
                const term = normalize(input.value.trim());
                const termDigits = onlyDigits(term);

                rows.forEach((row) => {
                    const razao = normalize(row.dataset.razao);
                    const fantasia = normalize(row.dataset.fantasia);
                    const cnpj = onlyDigits(row.dataset.cnpj);

                const matchesTexto = term === '' || razao.includes(term) || fantasia.includes(term);
                const matchesCnpj = termDigits !== '' && cnpj.includes(termDigits);

                row.style.display = matchesTexto || matchesCnpj ? '' : 'none';
            });
        };

            input.addEventListener('input', filterRows);
            filterRows();
        })();
    </script>
@endpush
