@extends('layouts.comercial')

@section('title', 'Funções')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canCreate = $isMaster || isset($permissionMap['comercial.funcoes.create']);
        $canUpdate = $isMaster || isset($permissionMap['comercial.funcoes.update']);
        $canDelete = $isMaster || isset($permissionMap['comercial.funcoes.delete']);
    @endphp
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
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
        @if (session('ignored'))
            <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800 space-y-2">
                <p class="font-semibold">Funções ignoradas (já existentes ou duplicadas):</p>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    @foreach (session('ignored', []) as $nomeIgnorado)
                        <li>{{ $nomeIgnorado }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('erro'))
            <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
                {{ session('erro') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
            <form method="GET" id="funcoes-filter-form" class="flex flex-col gap-3 md:flex-row md:items-end">
                <div class="w-full md:max-w-xl">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Buscar</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔎</span>
                        <input name="q" id="funcoes-autocomplete-input" value="{{ $q }}" placeholder="Nome da função..."
                               autocomplete="off"
                               class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-slate-200 focus:border-slate-300">
                        <div id="funcoes-autocomplete-list"
                             class="absolute z-20 mt-1 w-full max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg hidden"></div>
                    </div>

                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                    <select id="funcoes-status-filter" name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
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
            <div class="space-y-4">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
                    <h2 class="text-sm font-semibold text-slate-800">Nova função</h2>
                    <form method="POST" action="{{ route('comercial.funcoes.store') }}" class="space-y-3">
                        @csrf
                        <x-toggle-ativo
                            name="ativo"
                            :checked="true"
                            on-label="Ativa"
                            off-label="Inativa"
                            text-class="text-xs text-slate-600"
                        />
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Nome *</label>
                            <input name="nome" value="{{ old('nome') }}" required
                                   @if(!$canCreate) disabled @endif
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">CBO</label>
                            <input name="cbo" value="{{ old('cbo') }}"
                                   @if(!$canCreate) disabled @endif
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Descrição</label>
                            <textarea name="descricao" rows="3"
                                      @if(!$canCreate) disabled @endif
                                      class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('descricao') }}</textarea>
                        </div>
                        <div class="flex justify-end">
                            <button @if(!$canCreate) disabled title="Usuário sem permissão" @endif
                                    class="px-4 py-2 rounded-xl text-white text-sm font-semibold {{ $canCreate ? 'bg-emerald-600' : 'bg-slate-300 cursor-not-allowed' }}">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-800">Importar funções</h2>
                        <a href="{{ asset('templates/funcoes.xlsx') }}"
                           class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                            Baixar template
                        </a>
                    </div>
                    <div class="text-xs text-slate-600 space-y-1">
                        <p>O arquivo deve seguir exatamente o template: uma única coluna (A), sem cabeçalho, com uma função por linha.</p>
                        <p>Funções já existentes serão ignoradas e não serão substituídas.</p>
                    </div>
                    <form method="POST" action="{{ route('comercial.funcoes.import') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Arquivo (.xlsx)</label>
                            <input type="file" name="arquivo" accept=".xlsx,.csv"
                                   @if(!$canCreate) disabled @endif
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white">
                            @error('arquivo')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex justify-end">
                            <button @if(!$canCreate) disabled title="Usuário sem permissão" @endif
                                    class="px-4 py-2 rounded-xl text-white text-sm font-semibold {{ $canCreate ? 'bg-indigo-600' : 'bg-slate-300 cursor-not-allowed' }}">
                                Importar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                <table class="comercial-table w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-3">Funções</th>
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
                                <span class="inline-flex items-center gap-1.5 text-xs px-2 py-1 rounded-full {{ $funcao->ativo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                    <i class="fa-solid {{ $funcao->ativo ? 'fa-circle-check' : 'fa-circle-minus' }} text-[11px]"></i>
                                    <span>{{ $funcao->ativo ? 'ativa' : 'inativa' }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button"
                                            @if(!$canUpdate) disabled title="Usuário sem permissão" @endif
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border transition {{ $canUpdate ? 'text-blue-700 bg-blue-50 border-blue-200 hover:bg-blue-100' : 'text-slate-500 bg-slate-200 border-slate-300 cursor-not-allowed opacity-70' }}"
                                            title="Editar"
                                            aria-label="Editar"
                                            data-funcao-edit
                                            data-funcao-id="{{ $funcao->id }}"
                                            data-funcao-nome="{{ $funcao->nome }}"
                                            data-funcao-cbo="{{ $funcao->cbo }}"
                                            data-funcao-descricao="{{ $funcao->descricao }}"
                                            data-funcao-ativo="{{ $funcao->ativo ? 1 : 0 }}">
                                        <i class="fa-regular fa-pen-to-square text-sm"></i>
                                    </button>
                                    <form method="POST" action="{{ route('comercial.funcoes.destroy', $funcao) }}"
                                          data-confirm="{{ $temVinculo ? 'Esta função possui vínculos e será inativada. Deseja continuar?' : 'Deseja excluir esta função?' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                @if(!$canDelete) disabled title="Usuário sem permissão" @endif
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border transition {{ $canDelete ? 'text-rose-700 bg-rose-50 border-rose-200 hover:bg-rose-100' : 'text-slate-500 bg-slate-200 border-slate-300 cursor-not-allowed opacity-70' }}"
                                                title="{{ $temVinculo ? 'Inativar' : 'Excluir' }}"
                                                aria-label="{{ $temVinculo ? 'Inativar' : 'Excluir' }}">
                                            <i class="fa-solid {{ $temVinculo ? 'fa-power-off' : 'fa-trash-can' }} text-sm"></i>
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
                </div>
                <div class="px-4 py-4">
                    {{ $funcoes->links() }}
                </div>
            </div>
        </div>
    </div>

    <div id="modalFuncao" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Editar função</h3>
                    <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                            id="modalFuncaoClose">✕</button>
                </div>

                <form id="modalFuncaoForm" method="POST" class="p-6 space-y-3">
                    @csrf
                    @method('PUT')
                    <x-toggle-ativo
                        name="ativo"
                        id="modalFuncaoAtivo"
                        on-label="Ativa"
                        off-label="Inativa"
                        text-class="text-xs text-slate-600"
                    />
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
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border text-sm" id="modalFuncaoCancel">
                            Cancelar
                        </button>
                        <button @if(!$canUpdate) disabled title="Usuário sem permissão" @endif
                                class="px-4 py-2 rounded-xl text-white text-sm font-semibold {{ $canUpdate ? 'bg-indigo-600' : 'bg-slate-300 cursor-not-allowed' }}">
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
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.initTailwindAutocomplete?.(
                    'funcoes-autocomplete-input',
                    'funcoes-autocomplete-list',
                    @json($funcoesAutocomplete ?? [])
                );

                const form = document.getElementById('funcoes-filter-form');
                const input = document.getElementById('funcoes-autocomplete-input');
                const status = document.getElementById('funcoes-status-filter');
                let timer = null;

                const submitWithDebounce = () => {
                    clearTimeout(timer);
                    const term = String(input?.value || '').trim();
                    if (term !== '' && term.length < 3) {
                        return;
                    }
                    timer = setTimeout(() => form?.submit(), 900);
                };

                input?.addEventListener('input', submitWithDebounce);
                status?.addEventListener('change', () => form?.submit());
            });
        </script>
    @endpush
@endsection
