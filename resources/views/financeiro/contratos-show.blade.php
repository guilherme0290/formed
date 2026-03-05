@extends('layouts.financeiro')
@section('title', 'Contrato #' . $contrato->id)
@section('page-container', 'w-full p-0')

@section('content')
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
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

        @include('financeiro.partials.tabs')

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
{{--                <div>--}}
{{--                    <div class="text-xs text-slate-500">Valor Mensal</div>--}}
{{--                    <div class="font-semibold text-slate-900">R$ {{ number_format((float) $contrato->valor_mensal, 2, ',', '.') }}</div>--}}
{{--                </div>--}}
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-blue-200 bg-blue-50/70 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-blue-800">Itens do Contrato</h2>
                <p class="text-xs text-blue-700">Somente leitura</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-blue-50">
                    <tr class="text-left text-blue-700">
                        <th class="px-5 py-3 font-semibold">Serviço</th>
                        <th class="px-5 py-3 font-semibold">Descrição</th>
                        <th class="px-5 py-3 font-semibold">Detalhes</th>
                        <th class="px-5 py-3 font-semibold text-right whitespace-nowrap">Valor</th>
                        <th class="px-5 py-3 font-semibold">Unidade</th>
                        <th class="px-5 py-3 font-semibold">Ativo</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($contrato->itens as $item)
                        @php
                            $snapshot = is_array($item->regras_snapshot) ? $item->regras_snapshot : [];
                            $servicoNome = (string) ($item->servico->nome ?? '');
                            $descricaoItem = (string) ($item->descricao_snapshot ?? '');
                            $isAsoItem = !empty($snapshot['aso_tipo'])
                                || !empty($snapshot['ghes'])
                                || str_contains(strtoupper($servicoNome), 'ASO');

                            $asoTipo = (string) ($snapshot['aso_tipo'] ?? '');
                            $grupoId = (int) ($snapshot['grupo_id'] ?? 0);
                            $grupo = $grupoId > 0 ? ($protocolosAso[$grupoId] ?? null) : null;

                            $examesAso = collect();
                            if ($grupo && $grupo->relationLoaded('itens')) {
                                $examesAso = $grupo->itens
                                    ->map(fn ($grupoItem) => $grupoItem->exame)
                                    ->filter();
                            } elseif (!empty($snapshot['ghes']) && is_array($snapshot['ghes'])) {
                                $examesAso = collect($snapshot['ghes'])
                                    ->flatMap(function ($ghe) use ($asoTipo) {
                                        if (!is_array($ghe)) {
                                            return [];
                                        }
                                        if ($asoTipo !== '' && !empty($ghe['exames_por_tipo'][$asoTipo]) && is_array($ghe['exames_por_tipo'][$asoTipo])) {
                                            return $ghe['exames_por_tipo'][$asoTipo];
                                        }
                                        return is_array($ghe['exames'] ?? null) ? $ghe['exames'] : [];
                                    })
                                    ->map(function ($exame) {
                                        if (is_array($exame)) {
                                            return (object) ['titulo' => (string) ($exame['titulo'] ?? 'Exame')];
                                        }
                                        return $exame;
                                    });
                            }

                            $gheNomes = collect();
                            if (!empty($snapshot['ghes']) && is_array($snapshot['ghes'])) {
                                $gheNomes = collect($snapshot['ghes'])
                                    ->filter(function ($ghe) use ($grupoId, $asoTipo) {
                                        if (!is_array($ghe) || $grupoId <= 0) {
                                            return true;
                                        }
                                        $protocolos = is_array($ghe['protocolos'] ?? null) ? $ghe['protocolos'] : [];
                                        $protocoloTipoId = (int) ($protocolos[$asoTipo]['id'] ?? 0);
                                        $protocoloAdmissionalId = (int) ($ghe['protocolo']['id'] ?? 0);
                                        return $protocoloTipoId === $grupoId || $protocoloAdmissionalId === $grupoId;
                                    })
                                    ->map(fn ($ghe) => trim((string) ($ghe['nome'] ?? '')))
                                    ->filter()
                                    ->unique()
                                    ->values();
                            }

                            $isTreinamentoServico = str_contains(strtoupper($servicoNome), 'TREINAMENTO');
                            $isPacoteNr = $isTreinamentoServico && str_contains(strtoupper($descricaoItem), 'PACOTE COM');
                            $pacoteQtdTreinamentos = null;
                            if ($isPacoteNr && preg_match('/pacote com\s+(\d+)\s+treinamento/i', $descricaoItem, $mQtd)) {
                                $pacoteQtdTreinamentos = (int) ($mQtd[1] ?? 0);
                            }
                            $descricaoPacoteCurta = $isPacoteNr
                                ? ('Pacote com ' . ($pacoteQtdTreinamentos ?? 0) . ' treinamento' . (($pacoteQtdTreinamentos ?? 0) === 1 ? '' : 's'))
                                : $descricaoItem;

                            $nrDetalhes = collect();
                            if ($isPacoteNr && preg_match('/:\s*(.+)$/', $descricaoItem, $mLista)) {
                                $nrDetalhes = collect(explode(',', (string) ($mLista[1] ?? '')))
                                    ->map(fn ($nr) => trim((string) $nr))
                                    ->filter()
                                    ->values();
                            }

                            $detalhesTitulo = $item->servico->nome ?? 'Serviço';
                            $detalhesSourceId = "item-detalhes-{$item->id}";
                        @endphp
                        <tr class="{{ $loop->even ? 'bg-slate-50/70' : 'bg-white' }}">
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $item->servico->nome ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-700 align-top">{{ $descricaoPacoteCurta !== '' ? $descricaoPacoteCurta : '—' }}</td>
                            <td class="px-5 py-3 align-top min-w-[260px]">
                                @if($isAsoItem)
                                    <button type="button"
                                            class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                            data-aso-detalhes-open
                                            data-aso-detalhes-title="Detalhes - {{ $detalhesTitulo }}"
                                            data-aso-detalhes-source="{{ $detalhesSourceId }}">
                                        Ver detalhes
                                    </button>
                                    <div id="{{ $detalhesSourceId }}" class="hidden">
                                        <div class="space-y-3">
                                            <div>
                                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">GHE vinculado</div>
                                                @if($gheNomes->isNotEmpty())
                                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                                        @foreach($gheNomes as $gheNome)
                                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                                                {{ $gheNome }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="mt-1 text-slate-500">Não identificado</div>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Exames</div>
                                                @if($examesAso->isNotEmpty())
                                                    <ul class="mt-1 space-y-1 max-h-60 overflow-y-auto pr-1">
                                                        @foreach($examesAso as $exame)
                                                            <li class="font-semibold text-slate-800">{{ $exame->titulo ?? 'Exame' }}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <div class="mt-1 font-semibold text-slate-700">Sem exames vinculados</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @elseif($isPacoteNr)
                                    <button type="button"
                                            class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                            data-aso-detalhes-open
                                            data-aso-detalhes-title="Detalhes - {{ $detalhesTitulo }}"
                                            data-aso-detalhes-source="{{ $detalhesSourceId }}">
                                        Ver detalhes
                                    </button>
                                    <div id="{{ $detalhesSourceId }}" class="hidden">
                                        <div class="space-y-3">
                                            <div>
                                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Treinamentos do pacote</div>
                                                @if($nrDetalhes->isNotEmpty())
                                                    <ul class="mt-1 space-y-1 max-h-60 overflow-y-auto pr-1">
                                                        @foreach($nrDetalhes as $nrItem)
                                                            <li class="font-semibold text-slate-800">{{ $nrItem }}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <div class="mt-1 font-semibold text-slate-700">Detalhes de NRs não disponíveis neste contrato.</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <button type="button"
                                            class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                            data-aso-detalhes-open
                                            data-aso-detalhes-title="Detalhes - {{ $detalhesTitulo }}"
                                            data-aso-detalhes-source="{{ $detalhesSourceId }}">
                                        Ver detalhes
                                    </button>
                                    <div id="{{ $detalhesSourceId }}" class="hidden">
                                        <div class="space-y-3">
                                            <div>
                                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Serviço</div>
                                                <div class="mt-1 font-semibold text-slate-800">{{ $item->servico->nome ?? '—' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Descrição</div>
                                                <div class="mt-1 font-semibold text-slate-800">{{ $item->descricao_snapshot ?? '—' }}</div>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Valor</div>
                                                    <div class="mt-1 font-semibold text-slate-800">R$ {{ number_format((float) $item->preco_unitario_snapshot, 2, ',', '.') }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Unidade</div>
                                                    <div class="mt-1 font-semibold text-slate-800">{{ $item->unidade_cobranca ?: '—' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right align-top">
                                <span class="inline-flex min-w-[120px] justify-end whitespace-nowrap font-semibold tabular-nums text-slate-800">
                                    R$ {{ number_format((float) $item->preco_unitario_snapshot, 2, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $item->unidade_cobranca }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border {{ $item->ativo ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                    {{ $item->ativo ? 'Sim' : 'Não' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">Nenhum item neste contrato.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="asoDetalhesModal" class="fixed inset-0 z-[120] hidden">
        <div class="absolute inset-0 bg-slate-900/50" data-aso-detalhes-close></div>
        <div class="relative z-10 h-full w-full flex items-center justify-center p-4">
            <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-blue-200 bg-gradient-to-r from-blue-600 to-blue-500 flex items-center justify-between">
                    <h3 id="asoDetalhesModalTitle" class="text-sm font-semibold text-white">Detalhes ASO</h3>
                    <button type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-300 text-white hover:bg-blue-600"
                            data-aso-detalhes-close>
                        ✕
                    </button>
                </div>
                <div id="asoDetalhesModalBody" class="p-5 text-sm text-slate-700 font-semibold max-h-[70vh] overflow-y-auto"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('asoDetalhesModal');
            const modalTitle = document.getElementById('asoDetalhesModalTitle');
            const modalBody = document.getElementById('asoDetalhesModalBody');
            if (!modal || !modalTitle || !modalBody) return;

            const openButtons = document.querySelectorAll('[data-aso-detalhes-open]');
            const closeButtons = modal.querySelectorAll('[data-aso-detalhes-close]');

            const closeModal = () => {
                modal.classList.add('hidden');
                modalBody.innerHTML = '';
            };

            openButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const sourceId = btn.getAttribute('data-aso-detalhes-source') || '';
                    const title = btn.getAttribute('data-aso-detalhes-title') || 'Detalhes ASO';
                    const sourceEl = sourceId ? document.getElementById(sourceId) : null;

                    modalTitle.textContent = title;
                    modalBody.innerHTML = sourceEl ? sourceEl.innerHTML : '<p class="text-slate-500">Sem detalhes disponíveis.</p>';
                    modal.classList.remove('hidden');
                });
            });

            closeButtons.forEach((btn) => {
                btn.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
@endpush
