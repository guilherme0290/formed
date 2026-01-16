@extends('layouts.comercial')
@section('title', 'Nova vigência - Contrato #' . $contrato->id)

@section('content')
    <div class="max-w-[1800px] mx-auto px-3 sm:px-4 lg:px-8 py-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('comercial.contratos.show', $contrato) }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                    ← Voltar
                </a>
                <h1 class="text-2xl font-semibold text-slate-900 mt-1">Nova vigência</h1>
                <p class="text-sm text-slate-500">Contrato #{{ $contrato->id }} • {{ $contrato->cliente->razao_social ?? 'Cliente' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('comercial.contratos.vigencia.store', $contrato) }}" class="space-y-6">
            @csrf

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 space-y-4">
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Vigência início</label>
                        <input type="date" name="vigencia_inicio" value="{{ old('vigencia_inicio') }}"
                               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                        @error('vigencia_inicio')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Vigência fim (opcional)</label>
                        <input type="date" name="vigencia_fim" value="{{ old('vigencia_fim') }}"
                               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                        @error('vigencia_fim')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Observação</label>
                        <input type="text" name="observacao" value="{{ old('observacao') }}"
                               placeholder="Ex: reajuste anual, novo escopo..."
                               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                        @error('observacao')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="text-xs text-slate-500">Ao salvar, a vigência atual será registrada no histórico e os novos preços passam a valer a partir da data escolhida.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-800">Ajuste de preços</h2>
                    <span class="text-xs text-slate-500">Edite apenas o valor unitário</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                        <tr class="text-left text-slate-600">
                            <th class="px-5 py-3 font-semibold">Serviço</th>
                            <th class="px-5 py-3 font-semibold">Descrição</th>
                            <th class="px-5 py-3 font-semibold w-40">Valor atual</th>
                            <th class="px-5 py-3 font-semibold w-40">Novo valor</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @foreach($contrato->itens as $item)
                            <tr>
                                <td class="px-5 py-3 font-semibold text-slate-800">{{ $item->servico->nome ?? 'Serviço' }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $item->descricao_snapshot ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-700">
                                    R$ {{ number_format((float) $item->preco_unitario_snapshot, 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3">
                                    <input type="hidden" name="itens[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                    <input type="number" step="0.01" min="0"
                                           name="itens[{{ $loop->index }}][preco_unitario_snapshot]"
                                           value="{{ old('itens.'.$loop->index.'.preco_unitario_snapshot', $item->preco_unitario_snapshot) }}"
                                           class="w-full rounded-lg border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                                    @error('itens.'.$loop->index.'.preco_unitario_snapshot')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('comercial.contratos.show', $contrato) }}"
                   class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700">Cancelar</a>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 shadow">
                    Salvar nova vigência
                </button>
            </div>
        </form>
    </div>
@endsection
