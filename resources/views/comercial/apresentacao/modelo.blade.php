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
    @endpush
@endsection
