<div class="bg-white shadow rounded p-4 text-sm">
    <div class="flex items-center justify-between mb-3">
        <div class="font-semibold">Checklist</div>
        <a class="text-blue-600" href="{{ route('tarefas.checklists.edit',$tarefa) }}">Gerenciar</a>
    </div>
    @php
        $itens = \App\Models\TarefaChecklistItem::with('checklistItem')
                  ->where('tarefa_id', $tarefa->id)->get();
    @endphp
    @if($itens->isEmpty())
        <div class="text-gray-600">Sem itens de checklist.</div>
    @else
        <ul class="space-y-2">
            @foreach($itens as $i)
                <li class="flex items-center gap-2">
                    <form method="POST" action="{{ route('tarefas.checklists.toggle',[$tarefa,$i->id]) }}">
                        @csrf
                        <button class="w-5 h-5 border rounded {{ $i->feito?'bg-green-500':'' }}"></button>
                    </form>
                    <span class="{{ $i->feito ? 'line-through text-gray-500' : '' }}">{{ $i->checklistItem->descricao }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
