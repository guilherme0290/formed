<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 py-6">
        <h1 class="text-2xl font-semibold mb-4">Kanban</h1>

        @if(session('ok'))
            <div class="mb-4 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2">
                {{ session('ok') }}
            </div>
        @endif

        {{-- Exemplo de renderização das colunas/tarefas --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @forelse($colunas as $coluna)
                <div class="bg-white border rounded-xl p-4">
                    <div class="font-semibold mb-2">{{ $coluna->nome }}</div>
                    <div class="space-y-2">
                        @forelse($coluna->tarefas as $t)
                            <div class="border rounded p-2 text-sm">
                                <div class="font-medium">{{ $t->titulo ?? 'Tarefa' }}</div>
                                <div class="text-gray-500">{{ $t->descricao ?? '' }}</div>
                            </div>
                        @empty
                            <div class="text-gray-400 text-sm">Sem tarefas</div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="text-gray-500">Nenhuma coluna configurada.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
