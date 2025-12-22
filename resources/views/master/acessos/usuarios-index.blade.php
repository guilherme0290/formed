{{-- USUÁRIOS (visual moderno) --}}
<div x-data="{ open:false }" class="bg-white rounded-2xl shadow-sm border p-6">

    {{-- Cabeçalho --}}
    <div class="flex flex-col gap-4 mb-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="text-xl font-semibold">Usuários</div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('master.acessos') }}" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            <input type="hidden" name="tab" value="usuarios">
            <input name="q" value="{{ request('q') }}" placeholder="Buscar por nome ou e-mail..."
                   class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2 sm:w-64">
            <select name="papel_id" class="w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2 pr-10 sm:w-52">
                <option value="">Todos os papéis</option>
                @foreach($papeis as $p)
                    <option value="{{ $p->id }}" @selected(request('papel_id') == $p->id)>{{ $p->nome }}</option>
                @endforeach
            </select>
            <select name="status" class="w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2 pr-10 sm:w-40">
                <option value="">Todos</option>
                <option value="ativos" @selected(request('status') === 'ativos')>Ativos</option>
                <option value="inativos" @selected(request('status') === 'inativos')>Inativos</option>
            </select>
            <button class="w-full px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-gray-800 sm:w-auto">Filtrar</button>
            <button type="button" @click="open=true"
                    class="w-full px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 sm:w-auto">
                + Novo Usuário
            </button>
        </form>
    </div>

    {{-- Tabela --}}
    <div class="overflow-hidden rounded-xl border">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500">
            <tr>
                <th class="text-left py-3 px-4">Nome</th>
                <th class="text-left py-3 px-4">E-mail</th>
                <th class="text-left py-3 px-4">Papel</th>
                <th class="text-left py-3 px-4">Status</th>
                <th class="text-left py-3 px-4">Último Acesso</th>
                <th class="py-3 px-4 text-right w-40">Ações</th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @forelse($usuarios as $u)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium">{{ $u->name }}</td>
                    <td class="py-3 px-4 text-gray-600">{{ $u->email }}</td>
                    <td class="py-3 px-4">
                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700">
                            {{ optional($u->papel)->nome ?? '—' }}
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <span class="text-xs px-2 py-1 rounded-full {{ $u->ativo ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-700' }}">
                            {{ $u->ativo ? 'ativo' : 'inativo' }}
                        </span>
                    </td>
                    <td class="py-3 px-4">{{ $u->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>

                    <td class="py-3 px-4 text-right">
                        <div class="inline-flex gap-2 text-gray-600">

                            {{-- Editar (depois você liga ao seu modal) --}}
                            <button type="button" class="hover:text-gray-900" title="Editar"
                                    x-on:click="$dispatch('editar-usuario', { id: {{ $u->id }} })">✏️</button>

                            {{-- Redefinir senha (ENVIA POST) --}}
                            <form method="POST" action="{{ route('master.usuarios.reset', $u) }}" class="inline">
                                @csrf
                                <button type="submit" class="hover:text-gray-900" title="Redefinir senha">🔑</button>
                            </form>

                            {{-- Ativar / Desativar (ENVIA POST) --}}
                            <form method="POST" action="{{ route('master.usuarios.toggle', $u) }}" class="inline"
                                  onsubmit="return confirm('Confirmar {{ $u->ativo ? 'desativação' : 'ativação' }} de {{ $u->name }}?')">
                                @csrf
                                <button type="submit" class="hover:text-gray-900" title="{{ $u->ativo ? 'Desativar' : 'Ativar' }}">⏻</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-gray-500">Nenhum usuário cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    <div class="mt-4">
        {{ $usuarios->links() }}
    </div>

    {{-- Modal novo usuário --}}
    <div x-cloak x-show="open" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
         @keydown.escape.window="open=false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Criar Novo Usuário</h3>
                <button class="text-gray-500" @click="open=false">&times;</button>
            </div>
            <form method="POST" action="{{ route('master.usuarios.store') }}" class="space-y-3">
                @csrf
                <input name="name" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2" placeholder="Nome Completo *" required>
                <input name="email" type="email" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2" placeholder="E-mail corporativo *" required>
                <div class="relative" x-data="{ showPassword: false }">
                    <input name="password"
                           :type="showPassword ? 'text' : 'password'"
                           class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2 pr-11"
                           placeholder="Senha">
                    <button type="button"
                            class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-gray-500 hover:text-gray-700"
                            :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                            @click="showPassword = !showPassword">
                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 3c-4.5 0-8.1 3.2-9 7 0 0 2.9 7 9 7s9-7 9-7c-.9-3.8-4.5-7-9-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/>
                        </svg>
                        <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M4.03 3.97a.75.75 0 0 0-1.06 1.06l1.6 1.6C2.83 8.1 1.66 9.9 1 10c.9 3.8 4.5 7 9 7 1.7 0 3.3-.5 4.6-1.3l1.4 1.4a.75.75 0 1 0 1.06-1.06l-12-12zm5.97 10.53a4 4 0 0 1-4-4c0-.5.1-1 .3-1.5l5.2 5.2c-.5.2-1 .3-1.5.3zm4.9-1.4-1.1-1.1a4 4 0 0 0-5.2-5.2L7.5 5.7c.8-.4 1.6-.7 2.5-.7 4.5 0 8.1 3.2 9 7-.3 1.1-1 2.3-2.1 3.6z"/>
                        </svg>
                    </button>
                </div>
                <input name="telefone" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2" placeholder="Telefone (opcional)">
                <select name="papel_id" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2" required>
                    <option value="">Selecione o papel</option>
                    @foreach($papeis as $p)
                        <option value="{{ $p->id }}">{{ $p->nome }}</option>
                    @endforeach
                </select>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 rounded-xl border" @click="open=false">Cancelar</button>
                    <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Salvar e Criar</button>
                </div>
            </form>
        </div>
    </div>
</div>
