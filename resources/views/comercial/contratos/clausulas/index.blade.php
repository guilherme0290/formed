@extends('layouts.comercial')
@section('title', 'Cláusulas do Contrato')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Catálogo de Cláusulas</h1>
                <p class="text-xs text-slate-500">Gerencie as cláusulas usadas na geração dos contratos.</p>
            </div>
            <a href="{{ route('comercial.contratos.clausulas.create') }}"
               class="px-4 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-xs text-emerald-700 hover:bg-emerald-100">
                Nova cláusula
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
            <div class="p-4 border-b border-slate-100">
                <form method="GET" class="flex flex-wrap gap-2">
                    <input type="text" name="servico" value="{{ $servico ?? '' }}"
                           placeholder="Filtrar por serviço (ASO, PGR, GERAL...)"
                           class="w-64 rounded-xl border border-slate-200 text-xs px-3 py-2">
                    <button type="submit"
                            class="px-3 py-2 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">
                        Filtrar
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-5 py-3 text-left">Ordem</th>
                        <th class="px-5 py-3 text-left">Serviço</th>
                        <th class="px-5 py-3 text-left">Título</th>
                        <th class="px-5 py-3 text-left">Slug</th>
                        <th class="px-5 py-3 text-left">Ativa</th>
                        <th class="px-5 py-3 text-left">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($clausulas as $clausula)
                        <tr>
                            <td class="px-5 py-3">{{ $clausula->ordem }}</td>
                            <td class="px-5 py-3">{{ $clausula->servico_tipo }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $clausula->titulo }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $clausula->slug }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-1 rounded-full text-xs border {{ $clausula->ativo ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                    {{ $clausula->ativo ? 'Sim' : 'Não' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('comercial.contratos.clausulas.edit', $clausula) }}"
                                       class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('comercial.contratos.clausulas.destroy', $clausula) }}"
                                          onsubmit="return confirm('Deseja remover esta cláusula?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1.5 rounded-lg border border-red-200 text-xs text-red-700 hover:bg-red-50">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-500">Nenhuma cláusula cadastrada.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $clausulas->links() }}
            </div>
        </div>
    </div>
@endsection
