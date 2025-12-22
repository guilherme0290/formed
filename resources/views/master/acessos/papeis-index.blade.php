{{-- resources/views/master/acessos/papeis-index.blade.php --}}
<div class="bg-white rounded-2xl shadow p-5">
    <div class="text-xl font-semibold mb-4">Perfis</div>

    <div class="flex flex-wrap items-center gap-2 mb-4 opacity-60 cursor-not-allowed select-none">
        <input name="nome" class="rounded border-gray-200 px-3 py-2 bg-slate-100" placeholder="Nome do perfil *" disabled>
        <input name="descricao" class="rounded border-gray-200 px-3 py-2 bg-slate-100" placeholder="Descrição (opcional)" disabled>
        <label class="inline-flex items-center gap-2 text-sm text-slate-500">
            <input type="checkbox" name="ativo" value="1" checked disabled>
            ativo
        </label>
        <button type="button" class="px-4 py-2 bg-slate-200 text-slate-500 rounded" disabled title="Indisponível no momento">+ Novo Perfil</button>
    </div>

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
                    <form method="POST" action="{{ route('master.papeis.destroy', $papel) }}" class="inline" onsubmit="return confirm('Excluir perfil?')">
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
