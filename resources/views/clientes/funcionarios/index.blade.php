{{-- resources/views/cliente/funcionarios/index.blade.php --}}
@extends('layouts.cliente')

@section('title', 'Funcionários')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                Funcionários
            </h1>
            <p class="text-xs text-slate-500">
                Funcionários vinculados ao cliente {{ $cliente->nome_fantasia ?? $cliente->razao_social }}
            </p>
        </div>

        <a href="{{ route('cliente.funcionarios.create') }}"
           class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-medium
                  bg-emerald-600 text-white hover:bg-emerald-700">
            + Novo funcionário
        </a>
    </div>

    <form method="get" class="mb-4">
        <input type="text"
               name="q"
               value="{{ $q }}"
               placeholder="Buscar por nome ou CPF"
               class="w-full sm:w-64 border border-slate-300 rounded-lg px-3 py-2 text-sm">
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
            <tr>
                <th class="px-4 py-2 text-left">Nome</th>
                <th class="px-4 py-2 text-left">CPF</th>
                <th class="px-4 py-2 text-left">Função</th>
                <th class="px-4 py-2 text-left">Admissão</th>
                <th class="px-4 py-2"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($funcionarios as $f)
                <tr>
                    <td class="px-4 py-2">{{ $f->nome }}</td>
                    <td class="px-4 py-2">{{ $f->cpf }}</td>
                    <td class="px-4 py-2">{{ $f->funcao }}</td>
                    <td class="px-4 py-2">
                        {{ optional($f->data_admissao)->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-2 text-right">
                        <a href="{{ route('cliente.funcionarios.show', $f) }}"
                           class="text-xs text-[color:var(--color-brand-azul)] hover:underline">
                            Detalhes
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-6 text-center text-xs text-slate-500" colspan="5">
                        Nenhum funcionário cadastrado.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        @if($funcionarios->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 text-xs text-slate-500">
                {{ $funcionarios->links() }}
            </div>
        @endif
    </div>
@endsection
