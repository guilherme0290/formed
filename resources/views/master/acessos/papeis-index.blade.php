{{-- resources/views/master/acessos/papeis-index.blade.php --}}
<div class="bg-white rounded-2xl shadow p-5">
    <div class="text-xl font-semibold mb-4">Perfis</div>

    <form method="POST" action="{{ route('master.papeis.store') }}" class="flex flex-wrap items-center gap-2 mb-4">
        @csrf
        <x-toggle-ativo
            name="ativo"
            :checked="true"
            on-label="ativo"
            off-label="inativo"
            text-class="text-sm text-slate-600"
        />
        <input name="nome" class="rounded border-gray-200 px-3 py-2" placeholder="Nome do perfil *" required>
        <input name="descricao" class="rounded border-gray-200 px-3 py-2" placeholder="Descrição (opcional)">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">+ Novo Perfil</button>
    </form>

    <table class="w-full text-sm">
        <thead>
        <tr class="text-left text-gray-500">
            <th class="py-2">Nome</th>
            <th class="py-2">Descrição</th>
            <th class="py-2">Status</th>
            <th class="py-2 text-right">Ações</th>
        </tr>
        </thead>
        <tbody>
        @forelse($papeis as $papel)
            <tr @class(['border-t', 'bg-slate-50' => $loop->odd])>
                <td class="py-2">{{ $papel->nome }}</td>
                <td class="py-2 text-gray-500">{{ $papel->descricao }}</td>
                <td class="py-2">{{ $papel->ativo ? 'ativo' : 'inativo' }}</td>
                <td class="py-2 text-right space-x-2">
                    <form method="POST" action="{{ route('master.papeis.update', $papel) }}" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="ativo" value="{{ $papel->ativo ? 0 : 1 }}">
                        <button class="px-2 py-1 rounded bg-gray-800 text-white text-xs">
                            {{ $papel->ativo ? 'Desativar' : 'Ativar' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('master.papeis.destroy', $papel) }}" class="inline" data-confirm="Excluir perfil?">
                        @csrf @method('DELETE')
                        <button class="px-2 py-1 rounded bg-red-600 text-white text-xs">Excluir</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="py-4 text-center text-gray-500">Nenhum perfil cadastrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
