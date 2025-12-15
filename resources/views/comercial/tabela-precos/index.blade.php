@extends('layouts.comercial')
@section('title', 'Tabela de Preços')

@section('content')
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">

        <header>
            <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Tabela de Preços</h1>
            <p class="text-slate-500 text-sm md:text-base mt-1">
                Edite a tabela padrão. Ela serve como base para novas propostas.
            </p>
        </header>

        @if (session('ok'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                <p class="font-medium mb-1">Ocorreram erros:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            <div class="px-5 md:px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm md:text-base font-semibold text-slate-800">{{ $padrao->nome }}</h2>
                    <p class="text-xs text-slate-500 mt-1">Atualize os valores com cuidado: propostas já salvas não mudam.</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">
                Ativa
            </span>
            </div>

            <form method="POST" action="{{ route('comercial.tabela-precos.update') }}">
                @csrf

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                        <tr class="text-left text-slate-600">
                            <th class="px-5 py-3 font-semibold">Serviço</th>
                            <th class="px-5 py-3 font-semibold">Descrição</th>
                            <th class="px-5 py-3 font-semibold w-40">Preço</th>
                            <th class="px-5 py-3 font-semibold w-24">Ativo</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($itens as $k => $item)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-800">{{ $item->servico?->nome ?? '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->codigo }}</div>
                                    <input type="hidden" name="itens[{{ $k }}][id]" value="{{ $item->id }}">
                                </td>
                                <td class="px-5 py-3 text-slate-600">
                                    {{ $item->descricao }}
                                </td>
                                <td class="px-5 py-3">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="itens[{{ $k }}][preco]"
                                        value="{{ old("itens.$k.preco", $item->preco) }}"
                                        class="w-full rounded-xl border-slate-200 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-300"
                                    >
                                </td>
                                <td class="px-5 py-3">
                                    <label class="inline-flex items-center gap-2 text-slate-700">
                                        <input
                                            type="checkbox"
                                            name="itens[{{ $k }}][ativo]"
                                            value="1"
                                            @checked(old("itens.$k.ativo", $item->ativo))
                                            class="rounded border-slate-300 text-blue-600"
                                        >
                                        <span class="text-xs">Sim</span>
                                    </label>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-6 text-center text-slate-500">
                                    Nenhum item cadastrado na tabela padrão.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 md:px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end">
                    <button
                        class="rounded-2xl bg-slate-900 hover:bg-slate-800 text-white px-5 py-2 text-sm font-semibold shadow"
                        type="submit"
                    >
                        Salvar alterações
                    </button>
                </div>
            </form>
        </section>

    </div>
@endsection
