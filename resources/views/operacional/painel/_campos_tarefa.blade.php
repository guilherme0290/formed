<div class="grid md:grid-cols-2 gap-3">
    <select name="servico_id" class="input">
        <option value="">Selecione o serviço *</option>
        @foreach($servicos as $s)
            <option value="{{ $s->id }}">{{ $s->nome }}</option>
        @endforeach
    </select>

    <select name="unidade_id" class="input">
        {{-- carregar unidades --}}
    </select>

    <input name="titulo" class="input md:col-span-2" placeholder="Título da tarefa *">
    <textarea name="descricao" class="input md:col-span-2" placeholder="Descrição"></textarea>

    <select name="prioridade" class="input">
        <option value="media">Média</option>
        <option value="baixa">Baixa</option>
        <option value="alta">Alta</option>
    </select>

    <input type="date" name="data_agendada" class="input">
    <input type="time" name="hora_agendada" class="input">
</div>
