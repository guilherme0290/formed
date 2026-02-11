@extends('layouts.comercial')
@section('title', 'Acompanhamento de Propostas')
@section('page-container', 'w-full p-0')

@section('content')
    <div class="min-h-screen bg-slate-50">
        <div class="w-full px-2 sm:px-3 md:px-4 py-2 md:py-3 space-y-6">

            <div>
                <a href="{{ route('comercial.dashboard') }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                    ← Voltar ao Painel
                </a>
            </div>

            {{-- KPIs --}}
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @php
                    $kpiCards = [
                        ['label' => 'Total de Propostas', 'value' => $kpi['total'] ?? 0, 'icon' => 'doc', 'color' => 'text-slate-800', 'bg' => 'bg-white'],
                        ['label' => 'Em Aberto', 'value' => $kpi['emAberto'] ?? 0, 'icon' => 'clock', 'color' => 'text-amber-700', 'bg' => 'bg-white'],
                        ['label' => 'Fechadas', 'value' => $kpi['fechadas'] ?? 0, 'icon' => 'check', 'color' => 'text-emerald-700', 'bg' => 'bg-white'],
                        ['label' => 'Taxa de Conversão', 'value' => ($kpi['taxaConversao'] ?? 0) . '%', 'icon' => 'chart', 'color' => 'text-blue-700', 'bg' => 'bg-white'],
                        ['label' => 'Em Negociação (R$)', 'value' => 'R$ ' . number_format($kpi['emNegociacaoValor'] ?? 0, 2, ',', '.'), 'icon' => 'bars', 'color' => 'text-indigo-700', 'bg' => 'bg-white'],
                    ];
                @endphp
                @foreach($kpiCards as $card)
                    <div class="{{ $card['bg'] }} rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">{{ $card['label'] }}</p>
                            <p class="text-xl font-bold {{ $card['color'] }} mt-1">{{ $card['value'] }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600">
                            @switch($card['icon'])
                                @case('doc')
                                    📄
                                    @break
                                @case('clock')
                                    ⏱️
                                    @break
                                @case('check')
                                    ✔
                                    @break
                                @case('chart')
                                    📈
                                    @break
                                @case('bars')
                                    📊
                                    @break
                            @endswitch
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Filtros --}}
            <section class="bg-white/95 rounded-2xl shadow border border-slate-100 p-4 md:p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-800">Filtros e Busca</h2>
                </div>
                <form method="GET" action="{{ route('comercial.pipeline.index') }}" class="grid gap-3 md:grid-cols-4">
                    <div class="col-span-2 md:col-span-1">
                        <label class="text-xs font-semibold text-slate-600">Busca</label>
                        <div class="mt-1 relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">🔍</span>
                            <input type="text" name="q" id="pipeline-autocomplete-input" value="{{ $busca }}"
                                   autocomplete="off"
                                   class="w-full rounded-xl border border-slate-200 text-sm px-9 py-2"
                                   placeholder="Buscar por cliente ou nº proposta…">
                            <div id="pipeline-autocomplete-list"
                                 class="absolute z-20 mt-1 w-full max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg hidden"></div>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Status</label>
                        <select name="status"
                                class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                            <option value="" @selected($statusFiltro === '')>Todos</option>
                            @foreach($colunasMeta as $slug => $label)
                                <option value="{{ $slug }}" @selected($slug === $statusFiltro)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Vendedores</label>
                        <select name="vendedor_id" class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                            <option value="">Todos os Vendedores</option>
                            @foreach($vendedores as $vendedor)
                                <option value="{{ $vendedor->id }}" @selected((int) $vendedorFiltro === (int) $vendedor->id)>
                                    {{ $vendedor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-3 flex items-center justify-end gap-2">
                        <a href="{{ route('comercial.pipeline.index') }}"
                           class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Limpar
                        </a>
                        <button type="submit"
                                class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold shadow-sm">
                            Filtrar
                        </button>
                    </div>
                </form>
            </section>

            {{-- Kanban --}}
            <div class="overflow-x-auto pb-6 xl:overflow-x-visible">
                <div class="flex gap-3 md:gap-4 min-w-max">
                    @php
                        $cores = [
                            'CONTATO_INICIAL' => 'from-sky-400 to-cyan-400',
                            'PROPOSTA_ENVIADA' => 'from-indigo-500 to-blue-500',
                            'EM_NEGOCIACAO' => 'from-amber-400 to-orange-400',
                            'FECHAMENTO' => 'from-emerald-500 to-green-500',
                            'PERDIDO' => 'from-rose-500 to-red-500',
                        ];
                        $bordas = [
                            'CONTATO_INICIAL' => '#0ea5e9',
                            'PROPOSTA_ENVIADA' => '#6366f1',
                            'EM_NEGOCIACAO' => '#f59e0b',
                            'FECHAMENTO' => '#10b981',
                            'PERDIDO' => '#f43f5e',
                        ];
                        $cardBg = [
                            'CONTATO_INICIAL' => 'bg-sky-50',
                            'PROPOSTA_ENVIADA' => 'bg-indigo-50',
                            'EM_NEGOCIACAO' => 'bg-amber-50',
                            'FECHAMENTO' => 'bg-emerald-50',
                            'PERDIDO' => 'bg-rose-50',
                        ];
                        $borderClasses = [
                            'CONTATO_INICIAL' => 'border-sky-200',
                            'PROPOSTA_ENVIADA' => 'border-indigo-200',
                            'EM_NEGOCIACAO' => 'border-amber-200',
                            'FECHAMENTO' => 'border-emerald-200',
                            'PERDIDO' => 'border-rose-200',
                        ];
                        $badgeClasses = [
                            'CONTATO_INICIAL' => 'bg-sky-100 text-sky-700 border border-sky-200',
                            'PROPOSTA_ENVIADA' => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
                            'EM_NEGOCIACAO' => 'bg-amber-100 text-amber-700 border border-amber-200',
                            'FECHAMENTO' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                            'PERDIDO' => 'bg-rose-100 text-rose-700 border border-rose-200',
                        ];
                    @endphp
                    @foreach($colunas as $slug => $coluna)
                        @php
                            $grad = $cores[$slug] ?? 'from-slate-400 to-slate-500';
                            $count = count($coluna['cards']);
                            $borda = $bordas[$slug] ?? '#94a3b8';
                            $cardBgClass = $cardBg[$slug] ?? 'bg-white';
                            $borderClass = $borderClasses[$slug] ?? 'border-slate-200';
                            $badgeClass = $badgeClasses[$slug] ?? 'bg-blue-100 text-blue-700 border border-blue-200';
                        @endphp
                        <section class="flex flex-col w-56 md:w-60 lg:w-64 flex-shrink-0 gap-3">

                            <article class="rounded-2xl px-4 py-3 bg-gradient-to-r {{ $grad }} text-white shadow-md flex items-center justify-between">
                                <div>
                                    <h3 class="text-[11px] md:text-xs font-semibold uppercase tracking-wide opacity-90">
                                        {{ $coluna['titulo'] }}
                                    </h3>
                                    <p class="mt-1 text-xl md:text-2xl font-bold leading-none">
                                        {{ $count }}
                                    </p>
                                </div>
                                <div class="text-2xl md:text-3xl opacity-70">
                                    📌
                                </div>
                            </article>

                            <article class="bg-white border border-slate-200 rounded-2xl flex flex-col h-[64vh] md:h-[68vh] shadow-md"
                                     data-card-bg="{{ $cardBgClass }}"
                                     data-border-class="{{ $borderClass }}"
                                     data-border-color="{{ $borda }}"
                                     data-badge-class="{{ $badgeClass }}"
                                     data-badge-label="{{ $coluna['titulo'] }}">
                                <div class="flex-1 overflow-y-auto px-3 py-3 space-y-3 kanban-column"
                                     data-coluna="{{ $slug }}">
                                    @forelse($coluna['cards'] as $p)
                                        @php
                                            $tag = $coluna['titulo'];
                                            $codigo = str_pad((int) $p->id, 2, '0', STR_PAD_LEFT);
                                            $valor = number_format((float) $p->valor_total, 2, ',', '.');
                                            $hasEsocialItem = $p->itens->contains(fn ($it) => strtoupper((string) ($it->tipo ?? '')) === 'ESOCIAL');
                                            $servicesCount = ($p->itens->count() ?? 0) + (!$hasEsocialItem && $p->incluir_esocial ? 1 : 0);
                                            $dataEnvio = optional($p->updated_at)->format('d/m/Y');
                                            $badgeTipo = 'bg-blue-100 text-blue-700 border border-blue-200';
                                            $prazoDias = (int) ($p->prazo_dias ?? 7);
                                            $avisos = [];
                                            if ($p->pipeline_status === 'PERDIDO') {
                                                $avisos[] = 'Perdido: ' . ($p->perdido_motivo ?? 'motivo não informado');
                                            }
                                            $itensJson = $p->itens->map(function ($i) {
                                                return [
                                                    'nome' => $i->nome,
                                                    'quantidade' => $i->quantidade,
                                                    'valor_total' => number_format((float) $i->valor_total, 2, ',', '.'),
                                                ];
                                            })->values();
                                            $timelineJson = [
                                                ['titulo' => 'Proposta criada', 'data' => optional($p->created_at)->format('d/m/Y \\à\\s H:i'), 'por' => $p->vendedor->name ?? '—'],
                                                ['titulo' => 'Última atualização', 'data' => optional($p->updated_at)->format('d/m/Y \\à\\s H:i'), 'por' => $p->vendedor->name ?? '—'],
                                            ];
                                        @endphp
                                        <article class="kanban-card {{ $cardBgClass }} rounded-2xl shadow-md border border-slate-200 border-l-4 px-3 py-3 text-xs hover:shadow-lg transition hover:-translate-y-0.5 cursor-pointer"
                                                 style="border-left-color: {{ $borda }};"
                                                 data-card="{{ $p->id }}"
                                                 data-cliente="{{ $p->cliente->razao_social ?? '—' }}"
                                                 data-telefone="{{ $p->cliente->telefone ?? '' }}"
                                                 data-email="{{ $p->cliente->email ?? 'Não informado' }}"
                                                 data-codigo="{{ str_pad((int) $p->id, 2, '0', STR_PAD_LEFT) }}"
                                                 data-status-label="{{ $p->status ?? '—' }}"
                                                 data-pipeline-status="{{ strtoupper((string) ($p->pipeline_status ?? 'CONTATO_INICIAL')) }}"
                                                 data-esocial-enabled="{{ $p->incluir_esocial ? '1' : '0' }}"
                                                 data-esocial-qtd="{{ $p->esocial_qtd_funcionarios ?? 0 }}"
                                                 data-esocial-valor="{{ number_format((float) ($p->esocial_valor_mensal ?? 0), 2, ',', '.') }}"
                                                 data-valor="{{ number_format((float) $p->valor_total, 2, ',', '.') }}"
                                                 data-servicos="{{ $servicesCount }}"
                                                 data-vendedor="{{ $p->vendedor->name ?? '—' }}"
                                                 data-envio="{{ optional($p->created_at)->format('d/m/Y') }}"
                                                 data-validade="{{ optional($p->created_at)->addDays($prazoDias)->format('d/m/Y') }}"
                                                 data-ultimo-contato="{{ $dataEnvio }}"
                                                 data-itens='@json($itensJson)'
                                                 data-timeline='@json($timelineJson)'>
                                            <div class="flex items-center justify-between">
                                                <div class="font-semibold text-slate-900 line-clamp-2">{{ $p->cliente->razao_social ?? '—' }}</div>
                                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badgeTipo }}" data-card-badge>{{ $tag }}</span>
                                            </div>
                                            <div class="text-xs text-slate-500">{{ $codigo }}</div>

                                            @foreach($avisos as $av)
                                                <div class="text-[11px] px-3 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200">
                                                    {{ $av }}
                                                </div>
                                            @endforeach

                                            <div class="flex items-center justify-between">
                                                <div class="text-sm font-bold text-emerald-700">R$ {{ $valor }}</div>
                                                <div class="text-[11px] text-slate-500">{{ $servicesCount }} serviço(s)</div>
                                            </div>
                                            <div class="text-[11px] text-slate-500">Atualizada: {{ $dataEnvio }}</div>

                                            <div class="flex items-center gap-2 pt-1">
                                                <button class="flex-1 inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                                    <span>WhatsApp</span>
                                                </button>
                                                <button class="flex-1 inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 text-xs font-semibold">
                                                    <span>Ligar</span>
                                                </button>
                                            </div>
                                            <div class="flex items-center gap-2 pt-1">
                                                <div class="h-7 w-7 rounded-full bg-slate-200 flex items-center justify-center text-xs font-semibold text-slate-700">
                                                    {{ strtoupper(substr($p->vendedor->name ?? 'V',0,1)) }}
                                                </div>
                                                <div class="text-xs text-slate-600 line-clamp-1">{{ $p->vendedor->name ?? '—' }}</div>
                                            </div>
                                        </article>
                                    @empty
                                        <div class="text-xs text-slate-500">Nenhuma proposta.</div>
                                    @endforelse
                                </div>
                            </article>
                        </section>
                    @endforeach
                </div>
        </div>
    </div>
</div>

{{-- Modal Detalhe Proposta --}}
<div id="modalProposta" class="fixed inset-0 z-[90] hidden overflow-y-auto">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative min-h-screen flex items-center justify-center px-4 py-6">
        <div class="bg-white w-full max-w-6xl h-[90vh] rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-blue-700 px-6 py-4 flex items-start justify-between text-white">
                <div class="space-y-2">
                    <h2 class="text-2xl font-semibold">Detalhes da Proposta</h2>
                    <div class="flex items-center gap-2 text-xs">
                        <span id="modalCodigo" class="px-2.5 py-1 rounded-full bg-slate-900/60 border border-white/10 font-semibold">PROP-2025-001</span>
                        <span id="modalStatus" class="px-2.5 py-1 rounded-full bg-blue-500 text-white font-semibold">Proposta Enviada</span>
                    </div>
                </div>
                <button type="button"
                        class="h-10 w-10 rounded-full bg-white/20 hover:bg-white/30 text-white text-lg font-semibold"
                        aria-label="Fechar"
                        onclick="closeModalProposta()">
                    ✕
                </button>
            </div>

            <div class="h-[calc(90vh-88px)] overflow-y-auto p-6 bg-slate-50">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div class="lg:col-span-2 space-y-5">
                        <div class="bg-white border border-blue-100 rounded-2xl shadow-sm">
                            <div class="px-4 py-3 border-b border-blue-100 flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 text-sm">👤</span>
                                <h3 class="text-sm font-semibold text-slate-800">Informações do Cliente</h3>
                            </div>
                            <div class="px-4 py-4 grid md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Razão Social</div>
                                    <div id="modalCliente" class="text-slate-900 font-semibold">—</div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Telefone</div>
                                    <div id="modalTelefone" class="text-slate-900 font-semibold">—</div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">E-mail</div>
                                    <div id="modalEmail" class="text-slate-700 font-semibold">—</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl shadow-sm">
                            <div class="px-4 py-3 border-b border-emerald-100 flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 text-sm">📝</span>
                                <h3 class="text-sm font-semibold text-slate-800">Serviços Contratados</h3>
                            </div>
                            <div class="px-4 py-4 space-y-3 text-sm" id="modalServicos">
                                <div class="flex items-center justify-between">
                                    <div class="text-slate-800 font-semibold">PGR Matriz</div>
                                    <div class="text-xs text-slate-500">Quantidade: 1</div>
                                    <div class="text-emerald-700 font-bold">R$ 600,00</div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="text-slate-800 font-semibold">PCMSO Matriz</div>
                                    <div class="text-xs text-slate-500">Quantidade: 1</div>
                                    <div class="text-emerald-700 font-bold">R$ 600,00</div>
                                </div>
                            </div>
                            <div class="px-4 py-3 border-t border-emerald-100 flex items-center justify-between">
                                <div class="text-sm font-semibold text-slate-700">Valor Total</div>
                                <div id="modalValorTotal" class="text-lg font-bold text-emerald-700">R$ —</div>
                            </div>
                        </div>

                        <div class="bg-white border border-purple-100 rounded-2xl shadow-sm">
                            <div class="px-4 py-3 border-b border-purple-100 flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-700 text-sm">🕑</span>
                                <h3 class="text-sm font-semibold text-slate-800">Linha do Tempo</h3>
                            </div>
                            <div class="px-4 py-4 space-y-4" id="modalTimeline">
                                <div class="flex items-start gap-3">
                                    <div class="mt-1 h-3 w-3 rounded-full bg-purple-400"></div>
                                    <div class="bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 w-full">
                                        <div class="text-sm font-semibold text-slate-800">Proposta criada</div>
                                        <div class="text-xs text-slate-500">15/01/2025 às 10:00 · Por Carlos Santos</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="mt-1 h-3 w-3 rounded-full bg-purple-400"></div>
                                    <div class="bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 w-full">
                                        <div class="text-sm font-semibold text-slate-800">Proposta enviada por e-mail</div>
                                        <div class="text-xs text-slate-500">15/01/2025 às 10:30 · Por Carlos Santos</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                            <div class="px-4 py-3 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-800">Ações Rápidas</h3>
                            </div>
                            <div class="p-4 space-y-2">
                                <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">📱 Enviar WhatsApp</button>
                                <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">📞 Ligar</button>
                                <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold">✉️ Enviar E-mail</button>
                                <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold">🖨️ Imprimir Proposta</button>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                            <div class="px-4 py-3 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-800">Informações</h3>
                            </div>
                            <div class="p-4 space-y-2 text-sm text-slate-700">
                                <div class="flex items-center gap-2"><span class="text-blue-500">👤</span><span class="font-semibold">Vendedor:</span><span id="modalVendedor">—</span></div>
                                <div class="flex items-center gap-2"><span class="text-blue-500">📤</span><span class="font-semibold">Data de Envio:</span><span id="modalEnvio">—</span></div>
                                <div class="flex items-center gap-2"><span class="text-blue-500">⏳</span><span class="font-semibold">Validade:</span><span id="modalValidade">—</span></div>
                                <div class="flex items-center gap-2"><span class="text-blue-500">📅</span><span class="font-semibold">Último Contato:</span><span id="modalUltimoContato">—</span></div>
                            </div>
                        </div>

                        <div class="bg-amber-50 border border-amber-100 rounded-2xl shadow-sm">
                            <div class="px-4 py-3 border-b border-amber-100">
                                <h3 class="text-sm font-semibold text-slate-800">Atualizar Status</h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="text-xs font-semibold text-slate-600">Novo Status</div>
                                <select id="modalNovoStatus" class="w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm">
                                    <option value="CONTATO_INICIAL">Contato Inicial</option>
                                    <option value="PROPOSTA_ENVIADA">Proposta Enviada</option>
                                    <option value="EM_NEGOCIACAO">Em Negociacao</option>
                                    <option value="FECHAMENTO">Fechamento</option>
                                    <option value="PERDIDO">Perdido</option>
                                </select>
                                <button id="modalAtualizarStatusBtn" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">Atualizar Status</button>
                                <p id="modalStatusMsg" class="text-xs text-amber-700 hidden"></p>
                            </div>
                        </div>

                        <div class="bg-orange-50 border border-orange-100 rounded-2xl shadow-sm">
                            <div class="px-4 py-3 border-b border-orange-100">
                                <h3 class="text-sm font-semibold text-slate-800">Agendar Contato</h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="text-xs font-semibold text-slate-600">Data do Próximo Contato</div>
                                <input type="date" value="2025-01-22" class="w-full rounded-xl border border-orange-200 bg-white px-3 py-2 text-sm">
                                <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold">Agendar</button>
                            </div>
                        </div>

                        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl shadow-sm">
                            <div class="px-4 py-3 border-b border-emerald-100">
                                <h3 class="text-sm font-semibold text-slate-800">Nova Observação</h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <textarea rows="3" class="w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm" placeholder="Digite suas observações…"></textarea>
                                <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold">Adicionar Observação</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initTailwindAutocomplete?.(
                'pipeline-autocomplete-input',
                'pipeline-autocomplete-list',
                @json($pipelineAutocomplete ?? [])
            );
        });
    </script>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        (function () {
            const columns = document.querySelectorAll('[data-coluna]');
            const endpoint = @json(route('comercial.pipeline.mover', ['proposta' => '__ID__']));
            const modal = document.getElementById('modalProposta');

            const statusSelect = document.getElementById('modalNovoStatus');
            const statusButton = document.getElementById('modalAtualizarStatusBtn');
            const statusMsg = document.getElementById('modalStatusMsg');
            let currentCard = null;

            const fields = {
                codigo: document.getElementById('modalCodigo'),
                status: document.getElementById('modalStatus'),
                cliente: document.getElementById('modalCliente'),
                telefone: document.getElementById('modalTelefone'),
                email: document.getElementById('modalEmail'),
                valor: document.getElementById('modalValorTotal'),
                vendedor: document.getElementById('modalVendedor'),
                envio: document.getElementById('modalEnvio'),
                validade: document.getElementById('modalValidade'),
                ultimo: document.getElementById('modalUltimoContato'),
                timeline: document.getElementById('modalTimeline'),
                servicos: document.getElementById('modalServicos'),
            };

            function updateCounter(colEl) {
                const badge = colEl.parentElement?.querySelector('span span.inline-flex');
                if (badge) {
                    const cards = colEl.querySelectorAll('[data-card]');
                    badge.textContent = cards.length;
                }
            }

            function setStatusMsg(type, text) {
                if (!statusMsg) return;
                statusMsg.classList.remove('hidden');
                statusMsg.className = 'text-xs';
                statusMsg.classList.add(type === 'err' ? 'text-rose-600' : 'text-amber-700');
                statusMsg.textContent = text;
            }

            function clearStatusMsg() {
                statusMsg?.classList.add('hidden');
            }

            function showToast(message, type = 'ok') {
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 z-[60] px-4 py-2 rounded-xl text-xs font-semibold shadow-lg ${type === 'err' ? 'bg-rose-600 text-white' : 'bg-emerald-600 text-white'}`;
                toast.textContent = message;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2500);
            }

            function sendMove(cardId, newStatus, motivo = '') {
                const url = endpoint.replace('__ID__', cardId);
                const form = new FormData();
                form.append('pipeline_status', newStatus);
                if (newStatus === 'PERDIDO' && motivo) {
                    form.append('perdido_motivo', motivo);
                }
                form.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

                return fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: form
                }).then(async (res) => {
                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        throw new Error(data.message || 'Erro ao mover card.');
                    }
                    return res.json().catch(() => ({}));
                });
            }

            columns.forEach(col => {
                new Sortable(col, {
                    group: 'kanban',
                    animation: 150,
                    handle: '[data-card]',
                    onAdd: async function (evt) {
                        const card = evt.item;
                        const cardId = card.dataset.card;
                        const newStatus = col.dataset.coluna;

                        try {
                            await sendMove(cardId, newStatus, newStatus === 'PERDIDO' ? 'Atualizado no kanban' : '');
                            updateCounter(col);
                            if (evt.from !== col) updateCounter(evt.from);
                        } catch (e) {
                            evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                            updateCounter(evt.from);
                            updateCounter(col);
                            window.uiAlert(e.message || 'Erro ao atualizar status.');
                        }
                    },
                });
            });

            function closeModalProposta() {
                modal?.classList.add('hidden');
            }
            window.closeModalProposta = closeModalProposta;

            function preencherTimeline(arr) {
                if (!fields.timeline) return;
                fields.timeline.innerHTML = '';
                (arr || []).forEach(ev => {
                    const row = document.createElement('div');
                    row.className = 'flex items-start gap-3';
                    row.innerHTML = `
                        <div class="mt-1 h-3 w-3 rounded-full bg-purple-400"></div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 w-full">
                            <div class="text-sm font-semibold text-slate-800">${ev.titulo || ''}</div>
                            <div class="text-xs text-slate-500">${ev.data || ''} ${ev.por ? '· ' + ev.por : ''}</div>
                        </div>
                    `;
                    fields.timeline.appendChild(row);
                });
            }

            function moveCardToColumn(card, newStatus) {
                if (!card) return;
                const target = document.querySelector(`[data-coluna="${newStatus}"]`);
                const from = card.closest('[data-coluna]');
                if (!target) return;
                target.prepend(card);
                updateCounter(target);
                if (from && from !== target) updateCounter(from);

                const targetWrap = target.closest('article');
                if (!targetWrap) return;

                const newBg = targetWrap.dataset.cardBg || '';
                if (newBg) {
                    card.className = card.className.replace(/\bbg-[^\s]+/g, '').trim();
                    card.classList.add(newBg);
                }

                const borderClasses = [
                    'border-slate-200',
                    'border-sky-200',
                    'border-indigo-200',
                    'border-amber-200',
                    'border-emerald-200',
                    'border-rose-200',
                ];
                borderClasses.forEach((cls) => card.classList.remove(cls));
                const newBorderClass = targetWrap.dataset.borderClass || '';
                if (newBorderClass) {
                    card.classList.add(newBorderClass);
                }

                const newBorder = targetWrap.dataset.borderColor || '';
                if (newBorder) {
                    card.style.borderLeftColor = newBorder;
                }

                const badgeEl = card.querySelector('[data-card-badge]');
                const newBadgeClass = targetWrap.dataset.badgeClass || '';
                if (badgeEl && newBadgeClass) {
                    badgeEl.className = `px-2 py-0.5 rounded-full text-[11px] font-semibold ${newBadgeClass}`;
                }
                const newBadgeLabel = targetWrap.dataset.badgeLabel || '';
                if (badgeEl && newBadgeLabel) {
                    badgeEl.textContent = newBadgeLabel;
                }
            }

            function openModalFromCard(card) {
                if (!card || !modal) return;
                currentCard = card;
                fields.codigo.textContent = card.dataset.codigo || '—';
                fields.cliente.textContent = card.dataset.cliente || '—';
                fields.telefone.textContent = card.dataset.telefone || '—';
                fields.email.textContent = card.dataset.email || '—';
                fields.valor.textContent = 'R$ ' + (card.dataset.valor || '0,00');
                fields.vendedor.textContent = card.dataset.vendedor || '—';
                fields.envio.textContent = card.dataset.envio || '—';
                fields.validade.textContent = card.dataset.validade || '—';
                fields.ultimo.textContent = card.dataset.ultimoContato || '—';
                fields.status.textContent = card.dataset.statusLabel || '—';
                if (statusSelect) {
                    statusSelect.value = card.dataset.pipelineStatus || 'CONTATO_INICIAL';
                }
                clearStatusMsg();

                try {
                    const timeline = JSON.parse(card.dataset.timeline || '[]');
                    preencherTimeline(timeline);
                } catch (e) {
                    preencherTimeline([]);
                }

                if (fields.servicos) {
                    fields.servicos.innerHTML = '';
                    try {
                        const itens = JSON.parse(card.dataset.itens || '[]');
                        itens.forEach(it => {
                            const row = document.createElement('div');
                            row.className = 'flex items-center justify-between';
                            row.innerHTML = `
                                <div class="text-slate-800 font-semibold">${it.nome || ''}</div>
                                <div class="text-xs text-slate-500">Quantidade: ${it.quantidade || 0}</div>
                                <div class="text-emerald-700 font-bold">R$ ${it.valor_total || '0,00'}</div>
                            `;
                            fields.servicos.appendChild(row);
                        });
                        if (card.dataset.esocialEnabled === '1') {
                            const row = document.createElement('div');
                            row.className = 'flex items-center justify-between';
                            row.innerHTML = `
                                <div class="text-slate-800 font-semibold">eSocial</div>
                                <div class="text-xs text-slate-500">Quantidade: ${card.dataset.esocialQtd || 0}</div>
                                <div class="text-emerald-700 font-bold">R$ ${card.dataset.esocialValor || '0,00'}</div>
                            `;
                            fields.servicos.appendChild(row);
                        }
                    } catch (e) {
                        // deixa vazio em caso de erro
                    }
                }

                modal.classList.remove('hidden');
            }

            document.querySelectorAll('[data-card]').forEach(card => {
                card.addEventListener('click', (e) => {
                    if (e.detail === 0) return;
                    openModalFromCard(card);
                });
            });

            modal?.addEventListener('click', (e) => {
                if (e.target === modal) closeModalProposta();
            });

            statusButton?.addEventListener('click', async () => {
                if (!currentCard || !statusSelect) return;
                const newStatus = statusSelect.value;
                clearStatusMsg();

                try {
                    await sendMove(currentCard.dataset.card, newStatus, newStatus === 'PERDIDO' ? 'Atualizado no kanban' : '');
                    currentCard.dataset.pipelineStatus = newStatus;
                    moveCardToColumn(currentCard, newStatus);
                    closeModalProposta();
                    showToast('Status atualizado.');
                } catch (e) {
                    setStatusMsg('err', e.message || 'Falha ao atualizar status.');
                }
            });
        })();
    </script>
@endpush

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.flatpickr) {
                return;
            }
            if (flatpickr.l10ns && flatpickr.l10ns.pt) {
                flatpickr.localize(flatpickr.l10ns.pt);
            }
            function maskBrDate(value) {
                const digits = (value || '').replace(/\D+/g, '').slice(0, 8);
                if (digits.length <= 2) return digits;
                if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
                return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
            }
            document.querySelectorAll('input[type="date"]').forEach((input) => {
                if (input.dataset.fpBound) return;
                input.dataset.fpBound = '1';
                const fp = flatpickr(input, {
                    allowInput: true,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    altInputClass: input.className,
                });
                if (fp && fp.altInput) {
                    fp.altInput.addEventListener('input', () => {
                        fp.altInput.value = maskBrDate(fp.altInput.value);
                    });
                }
            });
        });
    </script>
@endpush
