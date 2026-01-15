@extends('layouts.financeiro')
@section('title', 'Contrato #' . $contrato->id)

@section('content')
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('financeiro.contratos') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                    ← Voltar
                </a>
                <h1 class="text-2xl font-semibold text-slate-900 mt-1">Contrato #{{ $contrato->id }}</h1>
                <p class="text-slate-500 text-sm mt-1">Cliente: {{ $contrato->cliente->razao_social ?? '—' }}</p>
            </div>
            @php
                $status = strtoupper((string) $contrato->status);
                $badge = match($status) {
                    'ATIVO' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'PENDENTE' => 'bg-amber-50 text-amber-800 border-amber-200',
                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
            @endphp
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold border {{ $badge }}">
                {{ str_replace('_',' ', $status) }}
            </span>
        </div>

        <section class="bg-white rounded-2xl shadow border border-slate-100 p-5 space-y-4">
            <div class="grid md:grid-cols-2 gap-4 text-sm text-slate-700">
                <div>
                    <div class="text-xs text-slate-500">Vigência Início</div>
                    <div class="font-semibold text-slate-900">{{ optional($contrato->vigencia_inicio)->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Vigência Fim</div>
                    <div class="font-semibold text-slate-900">{{ optional($contrato->vigencia_fim)->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Valor Mensal</div>
                    <div class="font-semibold text-slate-900">R$ {{ number_format((float) $contrato->valor_mensal, 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Proposta Origem</div>
                    <div class="font-semibold text-slate-900">#{{ $contrato->proposta_id_origem }}</div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800">Itens do Contrato</h2>
                <p class="text-xs text-slate-500">Somente leitura</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-5 py-3 font-semibold">Serviço</th>
                        <th class="px-5 py-3 font-semibold">Descrição</th>
                        <th class="px-5 py-3 font-semibold">Valor</th>
                        <th class="px-5 py-3 font-semibold">Unidade</th>
                        <th class="px-5 py-3 font-semibold">Ativo</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($contrato->itens as $item)
                        <tr>
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $item->servico->nome ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $item->descricao_snapshot ?? '—' }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-800">R$ {{ number_format((float) $item->preco_unitario_snapshot, 2, ',', '.') }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $item->unidade_cobranca }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border {{ $item->ativo ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                    {{ $item->ativo ? 'Sim' : 'Não' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-500">Nenhum item neste contrato.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
