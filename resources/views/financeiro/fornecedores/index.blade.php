@extends('layouts.financeiro')
@section('title', 'Fornecedores')
@section('page-container', 'w-full p-0')

@section('content')
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-[0.2em] text-indigo-400">Cadastro</div>
                <h1 class="text-3xl font-semibold text-slate-900">Fornecedores</h1>
                <p class="text-sm text-slate-500">Gerencie fornecedores para contas a pagar</p>
            </div>
            <a href="{{ route('financeiro.fornecedores.create') }}"
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
                        <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ $fornecedor->razao_social }}</div>
                                <div class="text-xs text-slate-500">{{ $fornecedor->nome_fantasia ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $fornecedor->cpf_cnpj ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                <div>{{ $fornecedor->email ?: '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $fornecedor->telefone ?: '—' }}</div>
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
                                    <a href="{{ route('financeiro.fornecedores.edit', $fornecedor) }}"
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
@endsection
