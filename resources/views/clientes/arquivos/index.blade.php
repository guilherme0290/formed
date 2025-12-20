@extends('layouts.cliente')
@section('title', 'Meus Arquivos')

@section('content')
    <div class="max-w-6xl mx-auto px-4 md:px-0 py-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Meus Arquivos</h1>
                <p class="text-sm text-slate-500">Documentos liberados pelo operacional.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Serviço</th>
                            <th class="px-5 py-3 text-left font-semibold">Tarefa</th>
                            <th class="px-5 py-3 text-left font-semibold">Finalizado em</th>
                            <th class="px-5 py-3 text-left font-semibold">Status</th>
                            <th class="px-5 py-3 text-center font-semibold">Documento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($arquivos as $tarefa)
                            @php
                                $coluna = $tarefa->coluna;
                                $statusLabel = $coluna?->nome ?? 'Finalizado';
                                $badge = ($coluna && $coluna->finaliza)
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                                    : 'bg-slate-100 text-slate-700 border-slate-200';
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 text-slate-800">
                                    {{ $tarefa->servico->nome ?? 'Serviço' }}
                                </td>
                                <td class="px-5 py-3 text-slate-700">
                                    {{ $tarefa->titulo ?? 'Tarefa' }}
                                </td>
                                <td class="px-5 py-3 text-slate-700">
                                    {{ optional($tarefa->finalizado_em)->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full border text-xs font-semibold {{ $badge }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($tarefa->arquivo_cliente_url)
                                        <a href="{{ $tarefa->arquivo_cliente_url }}"
                                           class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700"
                                           target="_blank" rel="noopener">
                                            Ver / Baixar
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">Indisponível</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-6 text-center text-slate-500">
                                    Nenhum documento disponível até o momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
