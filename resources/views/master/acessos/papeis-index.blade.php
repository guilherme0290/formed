{{-- resources/views/master/acessos/papeis-index.blade.php --}}
<div class="bg-white rounded-2xl shadow p-5">
    <div class="text-xl font-semibold mb-4">Papéis</div>

    <form method="POST" action="{{ route('master.papeis.store') }}" class="flex flex-wrap items-center gap-2 mb-4">
        @csrf
        <input name="nome" class="rounded border-gray-300 px-3 py-2" placeholder="Nome do papel *" required>
        <input name="descricao" class="rounded border-gray-300 px-3 py-2" placeholder="Descrição (opcional)">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="ativo" value="1" checked>
            ativo
        </label>
        <button class="px-4 py-2 bg-indigo-600 text-white rounded">+ Novo Papel</button>
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
            <tr class="border-t">
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
                    <form method="POST" action="{{ route('master.papeis.destroy', $papel) }}" class="inline" onsubmit="return confirm('Excluir papel?')">
                        @csrf @method('DELETE')
                        <button class="px-2 py-1 rounded bg-red-600 text-white text-xs">Excluir</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="py-4 text-center text-gray-500">Nenhum papel cadastrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
