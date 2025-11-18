@props(['titulo','tarefas'])
<div class="bg-white rounded-xl border p-4">
    <div class="font-semibold mb-3">{{ $titulo }}</div>
    <div class="space-y-3">
        @forelse($tarefas as $t)
            <div class="rounded-lg border p-3 shadow-sm">
                <div class="text-sm font-medium">{{ $t->titulo }}</div>
                <div class="text-xs text-gray-500">
                    {{ optional($t->cliente)->razao_social }} •
                    {{ optional($t->servico)->nome }} •
                    {{ $t->data_agendada?->format('d/m/Y') }} {{ $t->hora_agendada }}
                </div>
                <div class="mt-2 text-xs">
                    Responsável: <span class="font-medium">{{ optional($t->responsavel)->name }}</span>
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-400">Nenhuma tarefa.</div>
        @endforelse
    </div>
</div>
