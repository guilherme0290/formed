@extends('layouts.financeiro')
@section('title', 'Fornecedores')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $modalCfg = $modalFornecedor ?? ['aberto' => false, 'modo' => 'create', 'fornecedorEdicao' => null];
        $modalAberto = (bool) ($modalCfg['aberto'] ?? false);
        $modalModo = (string) ($modalCfg['modo'] ?? 'create');
        $fornecedorEdicao = $modalCfg['fornecedorEdicao'] ?? null;
        $isEdit = $modalModo === 'edit' && $fornecedorEdicao;

        $querySemModal = request()->except(['modal', 'fornecedor']);
        $modalCloseUrl = route('financeiro.fornecedores.index', $querySemModal);
        $modalCreateUrl = route('financeiro.fornecedores.index', array_merge($querySemModal, ['modal' => 'create']));

        $modalTitle = $isEdit ? 'Editar fornecedor' : 'Novo fornecedor';
        $modalSubtitle = $isEdit
            ? ($fornecedorEdicao->razao_social ?? 'Atualize os dados do fornecedor')
            : 'Cadastro rápido para uso em contas a pagar';
        $modalAction = $isEdit
            ? route('financeiro.fornecedores.update', $fornecedorEdicao)
            : route('financeiro.fornecedores.store');

        $enderecoOpen = collect([
            old('cep', $fornecedorEdicao->cep ?? ''),
            old('logradouro', $fornecedorEdicao->logradouro ?? ''),
            old('numero', $fornecedorEdicao->numero ?? ''),
            old('complemento', $fornecedorEdicao->complemento ?? ''),
            old('bairro', $fornecedorEdicao->bairro ?? ''),
            old('cidade', $fornecedorEdicao->cidade ?? ''),
            old('uf', $fornecedorEdicao->uf ?? ''),
        ])->contains(fn ($value) => filled($value));
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-[0.2em] text-indigo-400">Cadastro</div>
                <h1 class="text-3xl font-semibold text-slate-900">Fornecedores</h1>
                <p class="text-sm text-slate-500">Gerencie fornecedores para contas a pagar</p>
            </div>
            <a href="{{ $modalCreateUrl }}"
               class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                Novo Fornecedor
            </a>
        </div>

        @include('financeiro.partials.tabs')

        @if(session('error'))
            <div class="rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
            <form method="GET" class="grid gap-3 md:grid-cols-4 items-end">
                <div class="md:col-span-3">
                    <label class="text-xs font-semibold text-slate-600">Busca</label>
                    <input type="text" name="busca" value="{{ $filtros['busca'] }}" placeholder="Razão social, fantasia ou CPF/CNPJ"
                           class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                </div>
                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Filtrar</button>
                    <a href="{{ route('financeiro.fornecedores.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Limpar</a>
                </div>
            </form>
        </section>

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Razão social</th>
                        <th class="px-4 py-3 text-left font-semibold">Documento</th>
                        <th class="px-4 py-3 text-left font-semibold">Contato</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($fornecedores as $fornecedor)
                        @php
                            $editUrl = route('financeiro.fornecedores.index', array_merge($querySemModal, [
                                'modal' => 'edit',
                                'fornecedor' => $fornecedor->id,
                            ]));
                        @endphp
                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ $fornecedor->razao_social }}</div>
                                <div class="text-xs text-slate-500">{{ $fornecedor->nome_fantasia ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $fornecedor->cpf_cnpj ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                <div>{{ $fornecedor->contato_nome ?: '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $fornecedor->email ?: '—' }} · {{ $fornecedor->telefone ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($fornecedor->ativo)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">Inativo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="{{ $editUrl }}"
                                       class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">Editar</a>
                                    <form method="POST" action="{{ route('financeiro.fornecedores.destroy', $fornecedor) }}"
                                          onsubmit="return confirm('Deseja excluir este fornecedor?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-2 rounded-lg bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Nenhum fornecedor encontrado.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-slate-100">
                {{ $fornecedores->links() }}
            </div>
        </section>
    </div>

    @if($modalAberto)
        <div class="fixed inset-0 z-[95] overflow-y-auto" data-fornecedor-modal-root>
            <a href="{{ $modalCloseUrl }}" class="absolute inset-0 bg-slate-900/60 backdrop-blur-[1px]" aria-label="Fechar modal"></a>

            <div class="relative min-h-screen px-3 py-6 md:px-6 md:py-10 flex items-start justify-center">
                <div class="relative w-full max-w-4xl rounded-3xl border border-slate-200 bg-white shadow-2xl overflow-hidden">
                    <div class="px-5 md:px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs uppercase tracking-[0.2em] text-indigo-500">Cadastro rápido</div>
                                <h2 class="text-xl md:text-2xl font-semibold text-slate-900">{{ $modalTitle }}</h2>
                                <p class="text-sm text-slate-500 mt-1">{{ $modalSubtitle }}</p>
                            </div>
                            <a href="{{ $modalCloseUrl }}"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:bg-slate-50"
                               aria-label="Fechar modal">
                                ✕
                            </a>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="mx-5 md:mx-6 mt-4 rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">
                            <div class="font-semibold">Revise os campos obrigatórios.</div>
                            <div class="mt-1">{{ $errors->first() }}</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ $modalAction }}" class="px-5 md:px-6 py-5 space-y-5">
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif
                        <input type="hidden" name="fornecedor_modal_context" value="{{ $isEdit ? 'edit' : 'create' }}">
                        <input type="hidden" name="fornecedor_modal_edit_id" value="{{ $isEdit ? $fornecedorEdicao->id : '' }}">

                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <div class="grid gap-4 md:grid-cols-12 items-start">
                                <div class="md:col-span-8">
                                    <label class="text-xs font-semibold text-slate-600">Razão social *</label>
                                    <input type="text" name="razao_social"
                                           value="{{ old('razao_social', $fornecedorEdicao->razao_social ?? '') }}"
                                           required
                                           autofocus
                                           placeholder="Ex.: Clínica XYZ Serviços Médicos Ltda"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-xs font-semibold text-slate-600">Tipo *</label>
                                    <select name="tipo_pessoa"
                                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                        <option value="PJ" @selected(old('tipo_pessoa', $fornecedorEdicao->tipo_pessoa ?? 'PJ') === 'PJ')>PJ</option>
                                        <option value="PF" @selected(old('tipo_pessoa', $fornecedorEdicao->tipo_pessoa ?? 'PJ') === 'PF')>PF</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-xs font-semibold text-slate-600">Status</label>
                                    <div class="mt-1 h-[42px] rounded-xl border border-slate-200 bg-white px-3 flex items-center justify-center">
                                        <x-toggle-ativo
                                            name="ativo"
                                            :checked="(bool) old('ativo', $fornecedorEdicao?->ativo ?? true)"
                                            on-label="Ativo"
                                            off-label="Inativo"
                                            text-class="text-sm font-medium text-slate-700"
                                        />
                                    </div>
                                </div>

                                <div class="md:col-span-4">
                                    <label class="text-xs font-semibold text-slate-600">CPF/CNPJ</label>
                                    <input type="text" name="cpf_cnpj"
                                           value="{{ old('cpf_cnpj', $fornecedorEdicao->cpf_cnpj ?? '') }}"
                                           placeholder="Somente números ou formatado"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>

                                <div class="md:col-span-4">
                                    <label class="text-xs font-semibold text-slate-600">Contato</label>
                                    <input type="text" name="contato_nome"
                                           value="{{ old('contato_nome', $fornecedorEdicao->contato_nome ?? '') }}"
                                           placeholder="Nome da pessoa de contato"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>

                                <div class="md:col-span-4">
                                    <label class="text-xs font-semibold text-slate-600">Telefone</label>
                                    <input type="text" name="telefone"
                                           value="{{ old('telefone', $fornecedorEdicao->telefone ?? '') }}"
                                           placeholder="(00) 00000-0000"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>

                                <div class="md:col-span-6">
                                    <label class="text-xs font-semibold text-slate-600">E-mail</label>
                                    <input type="email" name="email"
                                           value="{{ old('email', $fornecedorEdicao->email ?? '') }}"
                                           placeholder="financeiro@fornecedor.com"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>

                                <div class="md:col-span-6">
                                    <label class="text-xs font-semibold text-slate-600">Nome fantasia</label>
                                    <input type="text" name="nome_fantasia"
                                           value="{{ old('nome_fantasia', $fornecedorEdicao->nome_fantasia ?? '') }}"
                                           placeholder="Opcional"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>
                            </div>
                        </div>

                        <details class="rounded-2xl border border-slate-200 bg-white" @if($enderecoOpen) open @endif>
                            <summary class="cursor-pointer list-none px-4 py-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Dados complementares (endereço)</p>
                                    <p class="text-xs text-slate-500">Opcional. Útil para referência fiscal e operacional.</p>
                                </div>
                                <span class="text-xs text-indigo-600 font-semibold">Expandir</span>
                            </summary>
                            <div class="px-4 pb-4 pt-1 grid gap-4 md:grid-cols-6 border-t border-slate-100">
                                <div class="md:col-span-2">
                                    <label class="text-xs font-semibold text-slate-600">CEP</label>
                                    <input type="text" name="cep" value="{{ old('cep', $fornecedorEdicao->cep ?? '') }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>
                                <div class="md:col-span-4">
                                    <label class="text-xs font-semibold text-slate-600">Logradouro</label>
                                    <input type="text" name="logradouro" value="{{ old('logradouro', $fornecedorEdicao->logradouro ?? '') }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>
                                <div class="md:col-span-1">
                                    <label class="text-xs font-semibold text-slate-600">Nº</label>
                                    <input type="text" name="numero" value="{{ old('numero', $fornecedorEdicao->numero ?? '') }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-xs font-semibold text-slate-600">Complemento</label>
                                    <input type="text" name="complemento" value="{{ old('complemento', $fornecedorEdicao->complemento ?? '') }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-xs font-semibold text-slate-600">Bairro</label>
                                    <input type="text" name="bairro" value="{{ old('bairro', $fornecedorEdicao->bairro ?? '') }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>
                                <div class="md:col-span-1">
                                    <label class="text-xs font-semibold text-slate-600">UF</label>
                                    <input type="text" name="uf" maxlength="2" value="{{ old('uf', $fornecedorEdicao->uf ?? '') }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5 uppercase">
                                </div>
                                <div class="md:col-span-6">
                                    <label class="text-xs font-semibold text-slate-600">Cidade</label>
                                    <input type="text" name="cidade" value="{{ old('cidade', $fornecedorEdicao->cidade ?? '') }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 bg-white text-slate-900 text-sm px-3 py-2.5">
                                </div>
                            </div>
                        </details>

                        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-1 border-t border-slate-100">
                            <a href="{{ $modalCloseUrl }}"
                               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Cancelar
                            </a>
                            <button class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                                {{ $isEdit ? 'Salvar alterações' : 'Cadastrar fornecedor' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') return;
                const url = @json($modalCloseUrl);
                window.location.href = url;
            });
        </script>
    @endif
@endsection
