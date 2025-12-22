@extends('layouts.comercial')
@section('title', 'Contrato #' . $contrato->id)

@section('content')
    <div class="max-w-6xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div>
            <a href="{{ route('comercial.contratos.index') }}"
               class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800">
                ← Voltar
            </a>
            <a href="{{ route('comercial.contratos.vigencia', $contrato) }}"
               class="ml-3 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-semibold">
                ➕ Nova vigência
            </a>
        </div>

        <header class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Contrato #{{ $contrato->id }}</h1>
                <p class="text-slate-500 text-sm mt-1">Cliente: {{ $contrato->cliente->razao_social ?? '—' }}</p>
            </div>
            @php
                $status = strtoupper((string) $contrato->status);
                $badge = match($status) {
                    'ATIVO' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'PENDENTE' => 'bg-amber-50 text-amber-800 border-amber-200',
                    'EM_ABERTO' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'FECHADO' => 'bg-blue-100 text-blue-900 border-blue-200',
                    'CANCELADO' => 'bg-slate-100 text-red-700 border-slate-200',
                    'SUBSTITUIDO' => 'bg-slate-50 text-slate-600 border-slate-200',
                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
            @endphp
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold border {{ $badge }}">
                {{ str_replace('_',' ', $status) }}
            </span>
        </header>

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
                <p class="text-xs text-slate-500">Snapshot da proposta</p>
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

        {{-- Timeline de vigências --}}
        <section class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800">Histórico de Vigências</h2>
                <span class="text-xs text-slate-500">Atual + anteriores</span>
            </div>

            <div class="divide-y divide-slate-100">
                {{-- Vigência atual (contrato) --}}
                <div class="p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-indigo-50/50">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide">Vigência Atual</p>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ optional($contrato->vigencia_inicio)->format('d/m/Y') ?? '—' }}
                            @if($contrato->vigencia_fim)
                                até {{ optional($contrato->vigencia_fim)->format('d/m/Y') }}
                            @else
                                (em aberto)
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-4 text-sm text-slate-700 flex-wrap">
                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                            Valor: R$ {{ number_format((float) $contrato->valor_mensal, 2, ',', '.') }}
                        </span>
                        <a href="{{ route('comercial.contratos.vigencia', $contrato) }}"
                           class="text-indigo-600 font-semibold">Criar nova vigência</a>
                    </div>
                </div>

                @forelse($contrato->vigencias->sortByDesc('vigencia_inicio') as $vigencia)
                    <div class="p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wide">Vigência</p>
                            <p class="text-sm font-semibold text-slate-900">
                                {{ optional($vigencia->vigencia_inicio)->format('d/m/Y') ?? '—' }}
                                @if($vigencia->vigencia_fim)
                                    até {{ optional($vigencia->vigencia_fim)->format('d/m/Y') }}
                                @else
                                    (em aberto)
                                @endif
                            </p>
                            @if($vigencia->observacao)
                                <p class="text-xs text-slate-500 mt-1">{{ $vigencia->observacao }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-4 text-sm text-slate-700 flex-wrap">
                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                Itens: {{ $vigencia->itens->count() }}
                            </span>
                            <div class="text-xs text-slate-500">
                                Registrado em {{ optional($vigencia->created_at)->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>

                    @if($vigencia->itens->count())
                        <div class="px-5 pb-5 text-sm text-slate-700">
                            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($vigencia->itens as $item)
                                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2">
                                        <div class="font-semibold text-slate-900 text-sm">
                                            {{ $item->servico->nome ?? $item->descricao_snapshot ?? 'Serviço' }}
                                        </div>
                                        <div class="text-xs text-slate-600">
                                            R$ {{ number_format((float) $item->preco_unitario_snapshot, 2, ',', '.') }}
                                            @if($item->unidade_cobranca)
                                                • {{ $item->unidade_cobranca }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="p-5 text-sm text-slate-500">
                        Nenhuma vigência anterior registrada.
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Timeline de alterações --}}
        <section class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800">Histórico de Alterações</h2>
                <span class="text-xs text-slate-500">Criações e ajustes do contrato</span>
            </div>

            @if($contrato->logs->isEmpty())
                <div class="p-5 text-sm text-slate-500">Nenhuma alteração registrada.</div>
            @else
                <div class="p-5 space-y-4">
                    @foreach($contrato->logs->sortByDesc('created_at') as $log)
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <span class="h-3 w-3 rounded-full bg-indigo-500 mt-1"></span>
                                <span class="flex-1 w-px bg-slate-200"></span>
                            </div>
                            <div class="flex-1 bg-slate-50/70 border border-slate-100 rounded-xl px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-sm font-semibold text-slate-800">
                                        {{ $log->acao }}
                                        @if($log->servico)
                                            • {{ $log->servico->nome }}
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ optional($log->created_at)->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                                <p class="text-sm text-slate-700 mt-2">{{ $log->descricao }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-600">
                                    <span>Usuário: {{ $log->user->name ?? 'Sistema' }}</span>
                                    @if($log->motivo)
                                        <span>Motivo: {{ $log->motivo }}</span>
                                    @endif
                                    @if($log->valor_anterior !== null || $log->valor_novo !== null)
                                        <span>
                                            Valor: {{ $log->valor_anterior !== null ? 'R$ ' . number_format((float) $log->valor_anterior, 2, ',', '.') : '—' }}
                                            → {{ $log->valor_novo !== null ? 'R$ ' . number_format((float) $log->valor_novo, 2, ',', '.') : '—' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
