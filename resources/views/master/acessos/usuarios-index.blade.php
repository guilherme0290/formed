{{-- USUÁRIOS (visual moderno) --}}
<div x-data="{ open:false }" class="bg-white rounded-2xl shadow-sm border p-6">

    {{-- Cabeçalho --}}
    <div class="flex items-center justify-between gap-4 mb-5">
        <div class="text-xl font-semibold">Usuários</div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('master.acessos') }}" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="usuarios">
            <input name="q" value="{{ request('q') }}" placeholder="Buscar por nome ou e-mail..."
                   class="w-64 rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2">
            <select name="papel_id" class="rounded-xl border-gray-200 bg-gray-50 px-3 py-2">
                <option value="">Todos os papéis</option>
                @foreach($papeis as $p)
                    <option value="{{ $p->id }}" @selected(request('papel_id') == $p->id)>{{ $p->nome }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border-gray-200 bg-gray-50 px-3 py-2">
                <option value="">Todos</option>
                <option value="ativos" @selected(request('status') === 'ativos')>Ativos</option>
                <option value="inativos" @selected(request('status') === 'inativos')>Inativos</option>
            </select>
            <button class="px-4 py-2 rounded-xl bg-gray-900 text-white hover:bg-gray-800">Filtrar</button>
            <button type="button" @click="open=true"
                    class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">
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
                <input name="password" type="password" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2" placeholder="Senha">
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
