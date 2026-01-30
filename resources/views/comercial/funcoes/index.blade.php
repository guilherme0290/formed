@extends('layouts.comercial')

@section('title', 'Funções')

@section('content')
    <div class="w-full mx-auto px-4 md:px-6 xl:px-8 py-6 space-y-6">
        <div>
            <a href="{{ route('master.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                Voltar ao Painel
            </a>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Funções</h1>
                <p class="text-sm text-slate-500">Gerencie as funções usadas nos serviços e GHE.</p>
            </div>
        </div>

        @if (session('ok'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
            <form method="GET" class="flex flex-col gap-3 md:flex-row md:items-end">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Buscar</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔎</span>
                        <input name="q" value="{{ $q }}" placeholder="Nome da função..."
                               list="funcoes-autocomplete"
                               class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-slate-200 focus:border-slate-300">
                        <datalist id="funcoes-autocomplete">
                            @foreach($funcoes->pluck('nome')->unique() as $nome)
                                <option value="{{ $nome }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        <option value="ativos" @selected($status === 'ativos')>Ativos</option>
                        <option value="inativos" @selected($status === 'inativos')>Inativos</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">
                        Filtrar
                    </button>
                    <a href="{{ route('comercial.funcoes.index') }}"
                       class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Limpar filtros
                    </a>
                </div>
            </form>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
                <h2 class="text-sm font-semibold text-slate-800">Nova função</h2>
                <form method="POST" action="{{ route('comercial.funcoes.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nome *</label>
                        <input name="nome" value="{{ old('nome') }}" required
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">CBO</label>
                        <input name="cbo" value="{{ old('cbo') }}"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Descrição</label>
                        <textarea name="descricao" rows="3"
                                  class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('descricao') }}</textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="ativo" value="1" checked class="rounded border-slate-300">
                        Ativa
                    </label>
                    <div class="flex justify-end">
                        <button class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-3">Função</th>
                        <th class="text-left px-4 py-3">CBO</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-right px-4 py-3">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($funcoes as $funcao)
                        @php
                            $temVinculo = ($funcao->funcionarios_count ?? 0) > 0 || ($funcao->ghe_funcoes_count ?? 0) > 0;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $funcao->nome }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $funcao->cbo ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full {{ $funcao->ativo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $funcao->ativo ? 'ativa' : 'inativa' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    <button type="button"
                                            class="text-slate-600 hover:text-slate-900"
                                            title="Editar"
                                            data-funcao-edit
                                            data-funcao-id="{{ $funcao->id }}"
                                            data-funcao-nome="{{ $funcao->nome }}"
                                            data-funcao-cbo="{{ $funcao->cbo }}"
                                            data-funcao-descricao="{{ $funcao->descricao }}"
                                            data-funcao-ativo="{{ $funcao->ativo ? 1 : 0 }}">
                                        ✏️
                                    </button>
                                    <form method="POST" action="{{ route('comercial.funcoes.destroy', $funcao) }}"
                                          onsubmit="return confirm('{{ $temVinculo ? 'Esta função possui vínculos e será inativada. Deseja continuar?' : 'Deseja excluir esta função?' }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-slate-600 hover:text-slate-900"
                                                title="{{ $temVinculo ? 'Inativar' : 'Excluir' }}">
                                            {{ $temVinculo ? '⏻' : '🗑️' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                                Nenhuma função cadastrada.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-4">
                    {{ $funcoes->links() }}
                </div>
            </div>
        </div>
    </div>

    <div id="modalFuncao" class="fixed inset-0 z-50 hidden bg-black/40">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Editar função</h3>
                    <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                            id="modalFuncaoClose">✕</button>
                </div>

                <form id="modalFuncaoForm" method="POST" class="p-6 space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nome *</label>
                        <input name="nome" id="modalFuncaoNome" required
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">CBO</label>
                        <input name="cbo" id="modalFuncaoCbo"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Descrição</label>
                        <textarea name="descricao" id="modalFuncaoDescricao" rows="3"
                                  class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="ativo" id="modalFuncaoAtivo" value="1"
                               class="rounded border-slate-300">
                        Ativa
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border text-sm" id="modalFuncaoCancel">
                            Cancelar
                        </button>
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold">
                            Salvar alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const modal = document.getElementById('modalFuncao');
                const closeBtn = document.getElementById('modalFuncaoClose');
                const cancelBtn = document.getElementById('modalFuncaoCancel');
                const form = document.getElementById('modalFuncaoForm');
                const nome = document.getElementById('modalFuncaoNome');
                const cbo = document.getElementById('modalFuncaoCbo');
                const descricao = document.getElementById('modalFuncaoDescricao');
                const ativo = document.getElementById('modalFuncaoAtivo');

                function closeModal() {
                    modal?.classList.add('hidden');
                }

                document.querySelectorAll('[data-funcao-edit]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const id = btn.dataset.funcaoId;
                        form.action = '{{ route('comercial.funcoes.update', ':id') }}'.replace(':id', id);
                        nome.value = btn.dataset.funcaoNome || '';
                        cbo.value = btn.dataset.funcaoCbo || '';
                        descricao.value = btn.dataset.funcaoDescricao || '';
                        ativo.checked = btn.dataset.funcaoAtivo === '1';
                        modal.classList.remove('hidden');
                    });
                });

                closeBtn?.addEventListener('click', closeModal);
                cancelBtn?.addEventListener('click', closeModal);
                modal?.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
            })();
        </script>
    @endpush
@endsection
