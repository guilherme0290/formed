@extends('layouts.comercial')
@section('title', 'Cláusulas de Contrato')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
        @if(session('ok'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('ok') }}</div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Catálogo de Cláusulas</h1>
                <p class="text-xs text-slate-500">Gerencie as cláusulas usadas no contrato dinâmico.</p>
            </div>
            <a href="{{ route('comercial.contratos.clausulas.create') }}"
               class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                Nova cláusula
            </a>
        </div>

        <form method="GET" class="bg-white border border-slate-200 rounded-xl p-3 flex flex-wrap gap-2 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo serviço</label>
                <select name="servico" class="rounded-lg border border-slate-300 text-sm px-3 py-2 w-64">
                    <option value="">Todos</option>
                    @foreach(($serviceTypes ?? []) as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}" @selected($servico === $typeKey)>{{ $typeLabel }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filtrar</button>
            <a href="{{ route('comercial.contratos.clausulas.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpar</a>
        </form>

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Ordem</th>
                        <th class="px-4 py-3 text-left font-semibold">Tipo</th>
                        <th class="px-4 py-3 text-left font-semibold">Slug</th>
                        <th class="px-4 py-3 text-left font-semibold">Título</th>
                        <th class="px-4 py-3 text-left font-semibold">Ativo</th>
                        <th class="px-4 py-3 text-right font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($clausulas as $clausula)
                        <tr>
                            <td class="px-4 py-3">{{ $clausula->ordem }}</td>
                            <td class="px-4 py-3">{{ $clausula->servico_tipo }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $clausula->slug }}</td>
                            <td class="px-4 py-3">{{ $clausula->titulo }}</td>
                            <td class="px-4 py-3">{{ $clausula->ativo ? 'Sim' : 'Não' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('comercial.contratos.clausulas.edit', $clausula) }}"
                                       class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Editar</a>
                                    <form method="POST" action="{{ route('comercial.contratos.clausulas.destroy', $clausula) }}"
                                          onsubmit="return confirm('Remover esta cláusula?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg border border-rose-300 text-xs font-semibold text-rose-700 hover:bg-rose-50">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Nenhuma cláusula encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $clausulas->links() }}
    </div>
@endsection
