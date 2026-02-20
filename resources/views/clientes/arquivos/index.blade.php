@extends('layouts.cliente')
@section('title', 'Meus Arquivos')

@section('content')
    <div class="max-w-6xl mx-auto px-4 md:px-0 py-6 space-y-5">
        <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-50 via-white to-cyan-50"></div>
            <div class="relative px-5 py-5 md:px-6 md:py-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Meus Arquivos</h1>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        {{ $arquivos->count() }} documento(s)
                    </span>
                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ ($funcionariosComArquivos ?? collect())->count() }} funcionário(s)
                    </span>
                </div>
            </div>
        </section>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/70 space-y-4">
                @if(($funcionariosComArquivos ?? collect())->isNotEmpty())
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                        <p class="text-xs font-semibold text-slate-700 mb-2">Baixar todos por funcionário</p>
                        <form method="POST" action="{{ route('cliente.arquivos.download-por-funcionario') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                            @csrf
                            <div class="md:col-span-3">
                                <label class="text-xs font-semibold text-slate-600">Funcionário</label>
                                <select
                                    name="funcionario_id"
                                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400"
                                >
                                    <option value="0">Todos os funcionários</option>
                                    @foreach($funcionariosComArquivos as $func)
                                        <option value="{{ $func->id }}">{{ $func->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex md:justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition"
                                >
                                    Baixar ZIP
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                    <p class="text-xs font-semibold text-slate-700 mb-2">Filtrar listagem</p>
                    <form method="GET" action="{{ route('cliente.arquivos.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Data início</label>
                            <input
                                type="date"
                                name="data_inicio"
                                value="{{ request('data_inicio') }}"
                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-600">Data fim</label>
                            <input
                                type="date"
                                name="data_fim"
                                value="{{ request('data_fim') }}"
                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-600">Tipo de serviço</label>
                            <select
                                name="servico"
                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400"
                            >
                                <option value="">Todos</option>
                                @foreach($servicos as $servico)
                                    <option value="{{ $servico->id }}" @selected((string) $servico->id === request('servico'))>
                                        {{ $servico->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-3 flex gap-2 md:justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition"
                            >
                                Filtrar
                            </button>
                            <a
                                href="{{ route('cliente.arquivos.index') }}"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition"
                            >
                                Limpar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto bg-white">
                <form method="POST" action="{{ route('cliente.arquivos.download-selecionados') }}">
                    @csrf
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100/80 text-slate-700">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold w-10">
                                    <input type="checkbox" class="rounded border-slate-300 js-check-all-arquivos">
                                </th>
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
                                $certificadosTreinamento = ($tarefa->anexos ?? collect())->filter(function ($anexo) {
                                    return mb_strtolower((string) ($anexo->servico ?? '')) === 'certificado_treinamento';
                                });
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-5 py-3">
                                        <input type="checkbox" name="tarefa_ids[]" value="{{ $tarefa->id }}" class="rounded border-slate-300 js-check-item-arquivo">
                                    </td>
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
                                    @if(!$tarefa->documento_link && $certificadosTreinamento->isEmpty())
                                        <span class="text-xs text-slate-400">Indisponível</span>
                                    @else
                                        <div class="flex flex-col items-center gap-1">
                                            @if($tarefa->documento_link)
                                                <a href="{{ $tarefa->documento_link }}"
                                                   class="inline-flex items-center px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition"
                                                   target="_blank" rel="noopener">
                                                    ASO
                                                </a>
                                            @endif
                                            @foreach($certificadosTreinamento as $certificado)
                                                <a href="{{ $certificado->url }}"
                                                   class="px-3 py-1.5 rounded-lg border border-indigo-200 text-indigo-700 text-xs font-semibold hover:bg-indigo-50"
                                                   target="_blank" rel="noopener">
                                                    Certificado {{ $loop->iteration }}
                                                </a>
                                            @endforeach
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-6 text-center text-slate-500">
                                        Nenhum documento disponível até o momento.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="px-5 py-4 border-t border-slate-200 bg-slate-50/60 flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-600">Marque os arquivos desejados para baixar em um único ZIP</span>
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                            Baixar selecionados
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.querySelector('.js-check-all-arquivos');
    if (!checkAll) return;

    const items = Array.from(document.querySelectorAll('.js-check-item-arquivo'));
    checkAll.addEventListener('change', function () {
        items.forEach((item) => {
            item.checked = checkAll.checked;
        });
    });
});
</script>
@endpush
