@extends('layouts.financeiro')
@section('title', 'Nova Conta a Pagar')
@section('page-container', 'w-full p-0')

@section('content')
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-[0.2em] text-indigo-400">Financeiro</div>
                <h1 class="text-3xl font-semibold text-slate-900">Nova conta a pagar</h1>
            </div>
            <a href="{{ route('financeiro.contas-pagar.index') }}"
               class="px-3 py-2 rounded-lg bg-slate-200 text-slate-800 text-xs font-semibold hover:bg-slate-300">Voltar</a>
        </div>

        @include('financeiro.partials.tabs')

        @if(session('error'))
            <div class="rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('financeiro.contas-pagar.store') }}" class="space-y-6" id="contaPagarForm">
            @csrf

            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 grid gap-4 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Fornecedor</label>
                    <select name="fornecedor_id" required class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                        <option value="">Selecione...</option>
                        @foreach($fornecedores as $fornecedor)
                            <option value="{{ $fornecedor->id }}" @selected(old('fornecedor_id') == $fornecedor->id)>
                                {{ $fornecedor->razao_social }}
                            </option>
                        @endforeach
                    </select>
                    <a href="{{ route('financeiro.fornecedores.create') }}" class="mt-2 inline-block text-xs text-indigo-600 hover:text-indigo-700">Cadastrar fornecedor</a>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Vencimento padrão</label>
                    <input type="date" name="vencimento" value="{{ old('vencimento') }}" required
                           class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Pago em (opcional)</label>
                    <input type="date" name="pago_em" value="{{ old('pago_em') }}"
                           class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                </div>

                <div class="md:col-span-4">
                    <label class="text-xs font-semibold text-slate-600">Observação</label>
                    <textarea name="observacao" rows="2" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">{{ old('observacao') }}</textarea>
                </div>
            </section>

            <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Itens da despesa</h2>
                        <p class="text-xs text-slate-500">Inclua todas as despesas desta conta</p>
                    </div>
                    <button type="button" id="addItemBtn" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">Novo item</button>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm" id="itensTable">
                        <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Categoria</th>
                            <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                            <th class="px-4 py-3 text-left font-semibold">Competência</th>
                            <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                            <th class="px-4 py-3 text-right font-semibold">Valor</th>
                            <th class="px-4 py-3 text-right font-semibold">Ação</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="itensBody"></tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Adicione ao menos um item para salvar a conta.</span>
                    <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Salvar conta</button>
                </div>
            </section>
        </form>
    </div>

    <template id="itemTemplate">
        <tr>
            <td class="px-4 py-3"><input type="text" data-field="categoria" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" placeholder="Água, Luz, Aluguel"></td>
            <td class="px-4 py-3"><input type="text" data-field="descricao" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" placeholder="Descrição da despesa" required></td>
            <td class="px-4 py-3"><input type="date" data-field="data_competencia" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm"></td>
            <td class="px-4 py-3"><input type="date" data-field="vencimento" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm"></td>
            <td class="px-4 py-3"><input type="number" step="0.01" min="0.01" data-field="valor" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm text-right" required></td>
            <td class="px-4 py-3 text-right">
                <button type="button" data-remove-row class="px-2 py-1 rounded-md bg-rose-50 text-rose-700 text-xs font-semibold hover:bg-rose-100">Remover</button>
            </td>
        </tr>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.getElementById('itensBody');
            const template = document.getElementById('itemTemplate');
            const addBtn = document.getElementById('addItemBtn');
            const form = document.getElementById('contaPagarForm');

            const addRow = () => {
                const clone = template.content.cloneNode(true);
                const row = clone.querySelector('tr');
                row.querySelector('[data-remove-row]').addEventListener('click', () => row.remove());
                body.appendChild(row);
            };

            addBtn.addEventListener('click', addRow);
            addRow();

            form.addEventListener('submit', (event) => {
                const rows = Array.from(body.querySelectorAll('tr'));
                if (rows.length === 0) {
                    event.preventDefault();
                    window.uiAlert('Adicione ao menos um item.');
                    return;
                }

                form.querySelectorAll('input[name^="itens["]').forEach((input) => input.remove());

                let hasError = false;
                rows.forEach((row, index) => {
                    const get = (field) => row.querySelector(`[data-field="${field}"]`)?.value?.trim() || '';
                    const descricao = get('descricao');
                    const valor = get('valor');

                    if (!descricao || !valor || parseFloat(valor) <= 0) {
                        hasError = true;
                        return;
                    }

                    const data = {
                        categoria: get('categoria'),
                        descricao,
                        data_competencia: get('data_competencia'),
                        vencimento: get('vencimento'),
                        valor,
                    };

                    Object.entries(data).forEach(([key, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `itens[${index}][${key}]`;
                        input.value = value;
                        form.appendChild(input);
                    });
                });

                if (hasError) {
                    event.preventDefault();
                    window.uiAlert('Preencha descrição e valor válido para todos os itens.');
                }
            });
        });
    </script>
@endsection
