<div class="bg-white shadow rounded p-4 text-sm">
    <div class="flex items-center justify-between mb-3">
        <div class="font-semibold">Anexos</div>
        <form method="POST" action="{{ route('tarefas.anexos.store',$tarefa) }}" enctype="multipart/form-data" class="flex items-center gap-2">
            @csrf
            <input type="file" name="arquivo" class="text-sm">
            <button class="px-3 py-1 bg-gray-800 text-white rounded">Enviar</button>
        </form>
    </div>
    @php
        $anexos = \App\Models\Anexo::whereMorphedTo('anexavel',$tarefa)->latest()->get();
    @endphp
    @if($anexos->isEmpty())
        <div class="text-gray-600">Sem anexos.</div>
    @else
        <ul class="space-y-2">
            @foreach($anexos as $a)
                <li class="flex items-center justify-between border rounded p-2">
                    <div>
                        <div class="font-medium">{{ $a->nome }}</div>
                        <div class="text-gray-500 text-xs">{{ $a->mime }} • {{ number_format($a->tamanho/1024,0) }} KB</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a class="text-blue-600" href="{{ Storage::url($a->caminho) }}" target="_blank">Abrir</a>
                        <form method="POST" action="{{ route('tarefas.anexos.destroy',[$tarefa,$a]) }}" onsubmit="return confirm('Excluir anexo?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600">Excluir</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
