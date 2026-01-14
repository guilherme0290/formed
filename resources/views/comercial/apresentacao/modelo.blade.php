@extends('layouts.comercial')
@section('title', 'Modelo Comercial')

@section('content')
    <div class="max-w-5xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <a href="{{ route('comercial.apresentacao.show', $segmento) }}"
                   class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800">
                    ← Voltar para apresentação
                </a>
                <h1 class="text-2xl font-semibold text-slate-900 mt-2">Modelo Comercial</h1>
                <p class="text-sm text-slate-500">Segmento: <span class="font-semibold text-slate-700">{{ $segmentoNome }}</span></p>
            </div>
        </div>

        @if (session('ok'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                Revise os campos informados e tente novamente.
            </div>
        @endif

        <form method="POST" action="{{ route('comercial.apresentacao.modelo.store', $segmento) }}"
              class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            @csrf

            <div class="px-6 py-4 border-b bg-slate-50">
                <h2 class="text-sm font-semibold text-slate-800">Textos da apresentação</h2>
                <p class="text-xs text-slate-500 mt-1">Edite os blocos exibidos no PDF e na tela.</p>
            </div>

            <div class="p-6 space-y-5">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Título do segmento</label>
                    <input name="titulo" type="text"
                           value="{{ old('titulo', $tituloSegmento) }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                           placeholder="Ex: CONSTRUÇÃO CIVIL">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Introdução (linha 1)</label>
                        <textarea name="intro_1" rows="4"
                                  class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                  placeholder="Mensagem principal">{{ old('intro_1', $conteudo['intro'][0] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Introdução (linha 2)</label>
                        <textarea name="intro_2" rows="4"
                                  class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                  placeholder="Complemento da introdução">{{ old('intro_2', $conteudo['intro'][1] ?? '') }}</textarea>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Benefícios</label>
                    <textarea name="beneficios" rows="4"
                              class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                              placeholder="Resumo dos benefícios">{{ old('beneficios', $conteudo['beneficios'] ?? '') }}</textarea>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Rodapé</label>
                    <input name="rodape" type="text"
                           value="{{ old('rodape', $conteudo['rodape'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                           placeholder="contato@empresa.com.br • (00) 0000-0000">
                </div>
            </div>

            <div class="px-6 py-4 border-t bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">Serviços essenciais</h3>
                <p class="text-xs text-slate-500 mt-1">Digite um serviço por linha. Linhas vazias são ignoradas.</p>
            </div>

            <div class="p-6">
                <textarea name="servicos" rows="10"
                          class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                          placeholder="Ex: PCMSO e gestão de exames ocupacionais">{{ old('servicos', $servicos) }}</textarea>
            </div>

            <div class="px-6 py-4 border-t bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">Serviços</h3>
                <p class="text-xs text-slate-500 mt-1">Selecione serviços da tabela de preço padrão e informe a quantidade.</p>
            </div>

            <div class="p-6 space-y-3">
                @forelse($tabelaItens as $item)
                    @php
                        $servicoNome = $item->servico?->nome;
                        $descricao = trim((string) $item->descricao);
                        $codigo = trim((string) $item->codigo);
                        $label = $descricao ?: $servicoNome ?: 'Item';
                        $quantidadeValue = old('preco_qtd.' . $item->id, $precoQuantidades[$item->id] ?? 1);
                    @endphp
                    <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 p-4 md:flex-row md:items-center md:gap-4">
                        <label class="flex items-start gap-3">
                            <input type="checkbox"
                                   name="preco_itens[]"
                                   value="{{ $item->id }}"
                                   class="mt-1 rounded border-slate-300 text-blue-600"
                                   @checked(in_array($item->id, old('preco_itens', $precoSelecionados ?? []), true))>
                            <div class="text-sm text-slate-700">
                                <div class="font-semibold text-slate-900">{{ $label }}</div>
                                <div class="text-xs text-slate-500">
                                    @if($codigo)
                                        <span>Código: {{ $codigo }}</span>
                                    @endif
                                    @if($servicoNome)
                                        <span class="ml-2">Serviço: {{ $servicoNome }}</span>
                                    @endif
                                </div>
                            </div>
                        </label>
                        <div class="flex items-center gap-3 md:ml-auto">
                            <div class="text-xs text-slate-500">R$ {{ number_format((float) $item->preco, 2, ',', '.') }}</div>
                            <input type="number"
                                   name="preco_qtd[{{ $item->id }}]"
                                   min="0"
                                   step="0.01"
                                   class="w-24 rounded-xl border border-slate-200 px-2 py-1 text-sm"
                                   value="{{ $quantidadeValue }}">
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">Nenhum item ativo na tabela de preço padrão.</div>
                @endforelse
            </div>

            <div class="px-6 py-4 border-t bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">Exames</h3>
                <p class="text-xs text-slate-500 mt-1">Selecione exames ou marque para incluir todos.</p>
            </div>

            <div class="p-6 space-y-3">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="usar_todos_exames" value="1"
                           class="rounded border-slate-300 text-blue-600"
                           @checked(old('usar_todos_exames', $usarTodosExames ?? false))>
                    Incluir todos os exames cadastrados
                </label>

                <div class="mt-3 space-y-2">
                    @forelse($examesList as $exame)
                        @php
                            $quantidadeValue = old('exames_qtd.' . $exame->id, $examesQuantidades[$exame->id] ?? 1);
                        @endphp
                        <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 p-4 md:flex-row md:items-center md:gap-4">
                            <label class="flex items-start gap-3">
                                <input type="checkbox"
                                       name="exames[]"
                                       value="{{ $exame->id }}"
                                       class="mt-1 rounded border-slate-300 text-blue-600"
                                       @checked(in_array($exame->id, old('exames', $examesSelecionados ?? []), true))>
                                <div class="text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">{{ $exame->titulo }}</div>
                                    @if($exame->descricao)
                                        <div class="text-xs text-slate-500">{{ $exame->descricao }}</div>
                                    @endif
                                </div>
                            </label>
                            <div class="flex items-center gap-3 md:ml-auto">
                                <div class="text-xs text-slate-500">R$ {{ number_format((float) $exame->preco, 2, ',', '.') }}</div>
                                <input type="number"
                                       name="exames_qtd[{{ $exame->id }}]"
                                       min="0"
                                       step="0.01"
                                       class="w-24 rounded-xl border border-slate-200 px-2 py-1 text-sm"
                                       value="{{ $quantidadeValue }}">
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">Nenhum exame ativo cadastrado.</div>
                    @endforelse
                </div>
            </div>

            <div class="px-6 py-4 border-t bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">Treinamentos</h3>
                <p class="text-xs text-slate-500 mt-1">Selecione treinamentos e informe a quantidade.</p>
            </div>

            <div class="p-6 space-y-2">
                @forelse($treinamentosList as $treinamento)
                    @php
                        $quantidadeValue = old('treinamentos_qtd.' . $treinamento->id, $treinamentosQuantidades[$treinamento->id] ?? 1);
                    @endphp
                    <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 p-4 md:flex-row md:items-center md:gap-4">
                        <label class="flex items-start gap-3">
                            <input type="checkbox"
                                   name="treinamentos[]"
                                   value="{{ $treinamento->id }}"
                                   class="mt-1 rounded border-slate-300 text-blue-600"
                                   @checked(in_array($treinamento->id, old('treinamentos', $treinamentosSelecionados ?? []), true))>
                            <div class="text-sm text-slate-700">
                                <div class="font-semibold text-slate-900">{{ $treinamento->codigo }} — {{ $treinamento->titulo }}</div>
                            </div>
                        </label>
                        <div class="flex items-center gap-3 md:ml-auto">
                            <input type="number"
                                   name="treinamentos_qtd[{{ $treinamento->id }}]"
                                   min="0"
                                   step="0.01"
                                   class="w-24 rounded-xl border border-slate-200 px-2 py-1 text-sm"
                                   value="{{ $quantidadeValue }}">
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">Nenhum treinamento ativo cadastrado.</div>
                @endforelse
            </div>

            @php
                $manualTablesOld = old('manual_tables');
                if (is_array($manualTablesOld)) {
                    $manualTables = $manualTablesOld;
                    $manualTablesOrder = old('manual_tables_order', array_keys($manualTables));
                } else {
                    $manualTables = ($tabelasManuais ?? collect())
                        ->mapWithKeys(function ($tabela) {
                            $rows = $tabela->linhas
                                ->sortBy('ordem')
                                ->mapWithKeys(function ($linha) {
                                    return [(string) $linha->id => $linha->valores ?? []];
                                })
                                ->all();

                            return [(string) $tabela->id => [
                                'titulo' => $tabela->titulo,
                                'subtitulo' => $tabela->subtitulo,
                                'columns' => $tabela->colunas ?? [],
                                'rows' => $rows,
                            ]];
                        })
                        ->all();
                    $manualTablesOrder = ($tabelasManuais ?? collect())
                        ->sortBy('ordem')
                        ->pluck('id')
                        ->map(fn ($id) => (string) $id)
                        ->all();
                }
            @endphp

            <div class="px-6 py-4 border-t bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">Tabelas manuais</h3>
                <p class="text-xs text-slate-500 mt-1">Crie tabelas livres e organize a ordem arrastando.</p>
            </div>

            <div class="p-6 space-y-4">
                <div id="manual-tables-wrapper" class="space-y-4">
                    @foreach($manualTablesOrder as $tableId)
                        @php
                            $table = $manualTables[$tableId] ?? [];
                            $columns = $table['columns'] ?? [];
                            $rows = $table['rows'] ?? [];
                            $rowsOrder = $table['rows_order'] ?? array_keys($rows);
                        @endphp
                        <div class="manual-table rounded-2xl border border-slate-200 bg-slate-50/40 p-4 space-y-4"
                             draggable="true"
                             data-table-id="{{ $tableId }}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                    <span class="manual-table-handle cursor-move rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px]">Arrastar</span>
                                    <span>Tabela manual</span>
                                </div>
                                <button type="button"
                                        class="btn-remove-table text-xs text-rose-600 hover:text-rose-700">
                                    Remover tabela
                                </button>
                            </div>

                            <div class="grid md:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Título da tabela</label>
                                    <input type="text"
                                           name="manual_tables[{{ $tableId }}][titulo]"
                                           value="{{ old('manual_tables.' . $tableId . '.titulo', $table['titulo'] ?? '') }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                           placeholder="Ex: Programas">
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Subtítulo (opcional)</label>
                                    <input type="text"
                                           name="manual_tables[{{ $tableId }}][subtitulo]"
                                           value="{{ old('manual_tables.' . $tableId . '.subtitulo', $table['subtitulo'] ?? '') }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                           placeholder="Texto auxiliar da tabela">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-semibold text-slate-600">Colunas</label>
                                    <button type="button"
                                            class="btn-add-col text-xs text-slate-600 hover:text-slate-800">
                                        + Adicionar coluna
                                    </button>
                                </div>
                                <div class="manual-columns grid md:grid-cols-3 gap-2">
                                    @foreach($columns as $colIndex => $column)
                                        <div class="manual-column flex items-center gap-2" data-col-index="{{ $colIndex }}">
                                            <input type="text"
                                                   name="manual_tables[{{ $tableId }}][columns][]"
                                                   value="{{ $column }}"
                                                   class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                                   placeholder="Ex: Programa">
                                            <button type="button"
                                                    class="btn-remove-col text-xs text-rose-600 hover:text-rose-700">
                                                ✕
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-semibold text-slate-600">Linhas</label>
                                    <button type="button"
                                            class="btn-add-row text-xs text-slate-600 hover:text-slate-800">
                                        + Adicionar linha
                                    </button>
                                </div>
                                <div class="manual-rows space-y-2">
                                    @foreach($rowsOrder as $rowKey)
                                        @php
                                            $rowValues = $rows[$rowKey] ?? [];
                                        @endphp
                                        <div class="manual-row rounded-xl border border-slate-200 bg-white p-3 space-y-2"
                                             data-row-id="{{ $rowKey }}">
                                            <div class="grid gap-2 manual-row-cells"
                                                 style="grid-template-columns: repeat({{ max(count($columns), 1) }}, minmax(0, 1fr));">
                                                @foreach($columns as $colIndex => $column)
                                                    <div class="manual-cell" data-col-index="{{ $colIndex }}">
                                                        <input type="text"
                                                               name="manual_tables[{{ $tableId }}][rows][{{ $rowKey }}][]"
                                                               value="{{ $rowValues[$colIndex] ?? '' }}"
                                                               class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                                               placeholder="Valor">
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="flex justify-end">
                                                <button type="button"
                                                        class="btn-remove-row text-xs text-rose-600 hover:text-rose-700">
                                                    Remover linha
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="manual-rows-order"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div id="manual-tables-order"></div>

                <button type="button" id="btn-add-manual-table"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    + Nova tabela manual
                </button>
            </div>

            <div class="px-6 py-4 border-t bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">eSocial</h3>
                <p class="text-xs text-slate-500 mt-1">Texto livre e tabela de faixas vigente.</p>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Descrição do eSocial</label>
                    <textarea id="esocial_descricao"
                              name="esocial_descricao"
                              class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                              rows="6">{{ old('esocial_descricao', $esocialDescricao ?? '') }}</textarea>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Faixa</th>
                            <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                            <th class="px-4 py-3 text-right font-semibold">Valor</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($esocialFaixas as $faixa)
                            <tr>
                                <td class="px-4 py-3">
                                    {{ $faixa->inicio }}@if($faixa->fim) - {{ $faixa->fim }}@else+@endif
                                </td>
                                <td class="px-4 py-3">{{ $faixa->descricao }}</td>
                                <td class="px-4 py-3 text-right">R$ {{ number_format((float) $faixa->preco, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-sm text-slate-500">Nenhuma faixa ativa cadastrada.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="px-6 py-4 border-t bg-white flex items-center justify-end gap-2">
                <a href="{{ route('comercial.apresentacao.show', $segmento) }}"
                   class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Cancelar
                </a>
                <button type="submit"
                        class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold">
                    Salvar modelo
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script src="https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.js"></script>
        <script>
            (function () {
                const textarea = document.getElementById('esocial_descricao');
                if (!textarea || !window.ClassicEditor) return;

                ClassicEditor.create(textarea, {
                    toolbar: ['heading', '|', 'bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo'],
                }).catch((error) => {
                    console.error(error);
                });
            })();
        </script>
        <template id="manual-table-template">
            <div class="manual-table rounded-2xl border border-slate-200 bg-slate-50/40 p-4 space-y-4"
                 draggable="true"
                 data-table-id="__ID__">
                <div class="flex items-center justify-between gap-3">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <span class="manual-table-handle cursor-move rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px]">Arrastar</span>
                        <span>Tabela manual</span>
                    </div>
                    <button type="button"
                            class="btn-remove-table text-xs text-rose-600 hover:text-rose-700">
                        Remover tabela
                    </button>
                </div>

                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Título da tabela</label>
                        <input type="text"
                               name="manual_tables[__ID__][titulo]"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="Ex: Programas">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Subtítulo (opcional)</label>
                        <input type="text"
                               name="manual_tables[__ID__][subtitulo]"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="Texto auxiliar da tabela">
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-600">Colunas</label>
                        <button type="button"
                                class="btn-add-col text-xs text-slate-600 hover:text-slate-800">
                            + Adicionar coluna
                        </button>
                    </div>
                    <div class="manual-columns grid md:grid-cols-3 gap-2"></div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-600">Linhas</label>
                        <button type="button"
                                class="btn-add-row text-xs text-slate-600 hover:text-slate-800">
                            + Adicionar linha
                        </button>
                    </div>
                    <div class="manual-rows space-y-2"></div>
                    <div class="manual-rows-order"></div>
                </div>
            </div>
        </template>

        <script>
            (function () {
                const wrapper = document.getElementById('manual-tables-wrapper');
                const orderWrap = document.getElementById('manual-tables-order');
                const btnAdd = document.getElementById('btn-add-manual-table');
                const template = document.getElementById('manual-table-template');
                if (!wrapper || !orderWrap || !btnAdd || !template) return;

                let draggedTable = null;

                function syncTablesOrder() {
                    orderWrap.innerHTML = '';
                    wrapper.querySelectorAll('.manual-table').forEach((table) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'manual_tables_order[]';
                        input.value = table.dataset.tableId || '';
                        orderWrap.appendChild(input);
                    });
                }

                function syncRowsOrder(table) {
                    const rowsWrap = table.querySelector('.manual-rows-order');
                    if (!rowsWrap) return;
                    rowsWrap.innerHTML = '';
                    table.querySelectorAll('.manual-row').forEach((row) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `manual_tables[${table.dataset.tableId}][rows_order][]`;
                        input.value = row.dataset.rowId || '';
                        rowsWrap.appendChild(input);
                    });
                }

                function reindexColumns(table) {
                    const columns = table.querySelectorAll('.manual-column');
                    columns.forEach((col, idx) => {
                        col.dataset.colIndex = String(idx);
                    });
                    table.querySelectorAll('.manual-row').forEach((row) => {
                        row.querySelectorAll('.manual-cell').forEach((cell, idx) => {
                            cell.dataset.colIndex = String(idx);
                        });
                        const cols = Math.max(columns.length, 1);
                        const cellsWrap = row.querySelector('.manual-row-cells');
                        if (cellsWrap) {
                            cellsWrap.style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;
                        }
                    });
                }

                function addColumn(table) {
                    const columnsWrap = table.querySelector('.manual-columns');
                    if (!columnsWrap) return;
                    const colIndex = columnsWrap.querySelectorAll('.manual-column').length;

                    const col = document.createElement('div');
                    col.className = 'manual-column flex items-center gap-2';
                    col.dataset.colIndex = String(colIndex);
                    col.innerHTML = `
                        <input type="text"
                               name="manual_tables[${table.dataset.tableId}][columns][]"
                               class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="Ex: Coluna">
                        <button type="button"
                                class="btn-remove-col text-xs text-rose-600 hover:text-rose-700">
                            ✕
                        </button>
                    `;
                    columnsWrap.appendChild(col);

                    table.querySelectorAll('.manual-row').forEach((row) => {
                        const cellsWrap = row.querySelector('.manual-row-cells');
                        if (!cellsWrap) return;
                        const cell = document.createElement('div');
                        cell.className = 'manual-cell';
                        cell.dataset.colIndex = String(colIndex);
                        cell.innerHTML = `
                            <input type="text"
                                   name="manual_tables[${table.dataset.tableId}][rows][${row.dataset.rowId}][]"
                                   class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                   placeholder="Valor">
                        `;
                        cellsWrap.appendChild(cell);
                    });
                    reindexColumns(table);
                }

                function addRow(table) {
                    const rowsWrap = table.querySelector('.manual-rows');
                    const columns = table.querySelectorAll('.manual-column');
                    if (!rowsWrap) return;
                    const rowId = `new_${Date.now()}`;
                    const row = document.createElement('div');
                    row.className = 'manual-row rounded-xl border border-slate-200 bg-white p-3 space-y-2';
                    row.dataset.rowId = rowId;

                    const cols = Math.max(columns.length, 1);
                    const cells = Array.from({ length: cols }).map((_, idx) => {
                        return `
                            <div class="manual-cell" data-col-index="${idx}">
                                <input type="text"
                                       name="manual_tables[${table.dataset.tableId}][rows][${rowId}][]"
                                       class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                       placeholder="Valor">
                            </div>
                        `;
                    }).join('');

                    row.innerHTML = `
                        <div class="grid gap-2 manual-row-cells" style="grid-template-columns: repeat(${cols}, minmax(0, 1fr));">${cells}</div>
                        <div class="flex justify-end">
                            <button type="button"
                                    class="btn-remove-row text-xs text-rose-600 hover:text-rose-700">
                                Remover linha
                            </button>
                        </div>
                    `;
                    rowsWrap.appendChild(row);
                    syncRowsOrder(table);
                }

                function initTable(table) {
                    table.addEventListener('dragstart', (e) => {
                        draggedTable = table;
                        e.dataTransfer.effectAllowed = 'move';
                    });
                    table.addEventListener('dragover', (e) => {
                        e.preventDefault();
                    });
                    table.addEventListener('drop', (e) => {
                        e.preventDefault();
                        if (!draggedTable || draggedTable === table) return;
                        const tables = Array.from(wrapper.querySelectorAll('.manual-table'));
                        const draggedIndex = tables.indexOf(draggedTable);
                        const targetIndex = tables.indexOf(table);
                        if (draggedIndex < targetIndex) {
                            wrapper.insertBefore(draggedTable, table.nextSibling);
                        } else {
                            wrapper.insertBefore(draggedTable, table);
                        }
                        syncTablesOrder();
                    });

                    table.querySelector('.btn-remove-table')?.addEventListener('click', () => {
                        table.remove();
                        syncTablesOrder();
                    });

                    table.querySelector('.btn-add-col')?.addEventListener('click', () => {
                        addColumn(table);
                    });

                    table.querySelector('.btn-add-row')?.addEventListener('click', () => {
                        addRow(table);
                    });

                    table.addEventListener('click', (e) => {
                        const btnRemoveCol = e.target.closest('.btn-remove-col');
                        if (btnRemoveCol) {
                            const col = btnRemoveCol.closest('.manual-column');
                            if (!col) return;
                            const colIndex = Number(col.dataset.colIndex || 0);
                            col.remove();
                            table.querySelectorAll('.manual-row').forEach((row) => {
                                const cell = row.querySelector(`.manual-cell[data-col-index=\"${colIndex}\"]`);
                                cell?.remove();
                            });
                            reindexColumns(table);
                            return;
                        }

                        const btnRemoveRow = e.target.closest('.btn-remove-row');
                        if (btnRemoveRow) {
                            const row = btnRemoveRow.closest('.manual-row');
                            row?.remove();
                            syncRowsOrder(table);
                        }
                    });

                    syncRowsOrder(table);
                    reindexColumns(table);
                }

                btnAdd.addEventListener('click', () => {
                    const id = `new_${Date.now()}`;
                    const html = template.innerHTML.replaceAll('__ID__', id);
                    const wrapperDiv = document.createElement('div');
                    wrapperDiv.innerHTML = html;
                    const table = wrapperDiv.firstElementChild;
                    if (!table) return;
                    wrapper.appendChild(table);
                    initTable(table);
                    syncTablesOrder();
                    addColumn(table);
                    addRow(table);
                });

                wrapper.querySelectorAll('.manual-table').forEach(initTable);
                syncTablesOrder();
            })();
        </script>
    @endpush
@endsection
