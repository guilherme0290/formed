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
                    <label class="block text-sm font-medium mb-1 text-slate-700">Busca (raz&atilde;o social, nome fantasia ou CNPJ)</label>
                    <div class="relative">
                        <input type="search" name="q" id="cliente-search" value="{{ $q }}"
                               autocomplete="off"
                               placeholder="Ex: 12.345.678/0001-00, Raz&atilde;o Social ou Nome Fantasia"
                               class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div id="clientes-autocomplete"
                             class="absolute z-20 mt-1 w-full max-h-64 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden">
                        </div>
                    </div>
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
                    <th class="px-4 py-2 text-center w-28 whitespace-nowrap">A&ccedil;&otilde;es</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($clientes as $cliente)
                    <tr data-razao="{{ $cliente->razao_social }}"
                        data-fantasia="{{ $cliente->nome_fantasia }}"
                        data-cnpj="{{ preg_replace('/\D+/', '', $cliente->cnpj ?? '') }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 uppercase">{{ $cliente->razao_social }}</div>
                            @if($cliente->nome_fantasia)
                                <div class="text-xs text-gray-500 uppercase">{{ $cliente->nome_fantasia }}</div>
                            @endif
                            @php($tipoCliente = ($cliente->tipo_cliente ?? 'final') === 'parceiro' ? 'parceiro' : 'final')
                            <div class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $tipoCliente === 'parceiro' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}" title="{{ $tipoCliente === 'parceiro' ? 'Cliente Parceiro' : 'Cliente Final' }}">
                                @if($tipoCliente === 'parceiro')
                                    <span aria-hidden="true">&#129309;</span>
                                    <span>Parceiro</span>
                                @else
                                    <span aria-hidden="true">&#128100;</span>
                                    <span>Final</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-4 py-3">{{ $cliente->cnpj }}</td>

                        <td class="px-4 py-3">
                            {{ $cliente->email }} <br>
                            {{ $cliente->telefone }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if($cliente->userCliente)
                                <button type="button"
                                        class="inline-flex items-center justify-center w-9 h-9 text-emerald-700 bg-emerald-100 rounded-full text-base"
                                        title="Acesso criado"
                                        aria-label="Acesso criado"
                                        onclick="openAcessoModal(this)"
                                        data-cliente-nome="{{ $cliente->razao_social }}"
                                        data-user-id="{{ $cliente->userCliente->id }}"
                                        data-user-name="{{ $cliente->userCliente->name }}"
                                        data-user-login="{{ $cliente->userCliente->email ?: $cliente->userCliente->documento }}"
                                        data-user-status="{{ $cliente->userCliente->ativo ? 'Ativo' : 'Inativo' }}"
                                        data-user-created="{{ optional($cliente->userCliente->created_at)->format('d/m/Y H:i') }}">
                                    &#128275;
                                </button>
                            @else
                                <span class="inline-flex items-center justify-center w-9 h-9 text-slate-600 bg-slate-100 rounded-full text-base"
                                      title="Sem acesso"
                                      aria-label="Sem acesso">
                                    &#128274;
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
                                    &#9998;
                                </a>

                                <form action="{{ route($routePrefix.'.destroy', $cliente) }}"
                                      method="POST"
                                      class="inline"
                                      data-confirm="Tem certeza que deseja excluir este cliente?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-2 text-red-700 bg-red-100 rounded-lg text-xs"
                                            title="Excluir"
                                            aria-label="Excluir">
                                        &#128465;
                                    </button>
                                </form>

                                <a href="{{ route($routePrefix.'.acesso.form', $cliente) }}"
                                   class="px-3 py-2 text-indigo-700 bg-indigo-100 rounded-lg text-xs"
                                   title="{{ $cliente->userCliente ? 'Ver acesso' : 'Criar acesso' }}"
                                   aria-label="{{ $cliente->userCliente ? 'Ver acesso' : 'Criar acesso' }}"
                                   @if($cliente->userCliente)
                                       onclick="openAcessoModal(this); return false;"
                                       data-cliente-nome="{{ $cliente->razao_social }}"
                                       data-user-id="{{ $cliente->userCliente->id }}"
                                       data-user-name="{{ $cliente->userCliente->name }}"
                                       data-user-login="{{ $cliente->userCliente->email ?: $cliente->userCliente->documento }}"
                                       data-user-status="{{ $cliente->userCliente->ativo ? 'Ativo' : 'Inativo' }}"
                                       data-user-created="{{ optional($cliente->userCliente->created_at)->format('d/m/Y H:i') }}"
                                   @endif>
                                    &#128273;
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

    <div id="modalAcessoCliente" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Acesso do cliente</h3>
                    <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                            onclick="closeAcessoModal()">&times;</button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="space-y-2 text-sm text-slate-700">
                        <div><span class="text-slate-500">Cliente:</span> <span id="acessoClienteNome">-</span></div>
                        <div><span class="text-slate-500">Usu&aacute;rio:</span> <span id="acessoUserNome">-</span></div>
                        <div><span class="text-slate-500">Login:</span> <span id="acessoUserLogin">-</span></div>
                        <div><span class="text-slate-500">Status:</span> <span id="acessoUserStatus">-</span></div>
                        <div><span class="text-slate-500">Criado em:</span> <span id="acessoUserCreated">-</span></div>
                    </div>

                    <form id="acessoResetForm" method="POST" action="" data-confirm="Enviar link de redefini&ccedil;&atilde;o de senha para este usu&aacute;rio?">
                        @csrf
                        <button type="submit"
                                class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold">
                            Redefinir senha
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('modalAcessoCliente');
            const resetForm = document.getElementById('acessoResetForm');
            const resetRouteTemplate = @json(route('master.usuarios.reset', ['user' => '__id__']));

            function openAcessoModal(button) {
                const data = button.dataset;

                document.getElementById('acessoClienteNome').textContent = data.clienteNome || '-';
                document.getElementById('acessoUserNome').textContent = data.userName || '-';
                document.getElementById('acessoUserLogin').textContent = data.userLogin || '-';
                document.getElementById('acessoUserStatus').textContent = data.userStatus || '-';
                document.getElementById('acessoUserCreated').textContent = data.userCreated || '-';

                resetForm.action = resetRouteTemplate.replace('__id__', data.userId || '');
                modal.classList.remove('hidden');
            }

            function closeAcessoModal() {
                modal.classList.add('hidden');
            }

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeAcessoModal();
                }
            });

            window.openAcessoModal = openAcessoModal;
            window.closeAcessoModal = closeAcessoModal;
        })();
    </script>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initTailwindAutocomplete?.(
                'cliente-search',
                'clientes-autocomplete',
                @json($autocompleteOptions)
            );
        });
    </script>

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
            const onlyText = (value) => (value || '')
                .toString()
                .replace(/\d+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            const filterRows = () => {
                const raw = input.value.trim();
                const term = normalize(onlyText(raw));
                const termDigits = onlyDigits(raw);

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

