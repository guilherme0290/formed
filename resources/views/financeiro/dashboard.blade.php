@extends('layouts.financeiro')
@section('title', 'Painel Financeiro')
@section('page-container', 'w-full px-4 sm:px-6 lg:px-8 py-6')

@section('content')
    <div class="space-y-8">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Dashboard Financeiro</h1>
                <p class="text-sm text-slate-500 mt-1">Gerencie contratos, faturamento e documentos fiscais.</p>
            </div>
        </div>

        @include('financeiro.partials.tabs')

        {{-- Indicadores --}}
        <section id="dashboard" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @php
                    $cards = [
                        ['titulo' => 'Contratos Ativos', 'valor' => $cards['contratos_ativos'] ?? 0, 'sub' => 'Empresas em carteira', 'cor' => 'from-indigo-500 to-blue-600', 'icone' => '&#128196;'],
//                        ['titulo' => 'Faturamento Mensal', 'valor' => 'R$ '.number_format($cards['faturamento_mensal'] ?? 0, 2, ',', '.'), 'sub' => 'Receita recorrente', 'cor' => 'from-emerald-500 to-emerald-600', 'icone' => '&#128176;'],
                        ['titulo' => 'Aprovados', 'valor' => $cards['aprovados'] ?? 0, 'sub' => 'Faturamentos confirmados', 'cor' => 'from-purple-500 to-indigo-600', 'icone' => '&#9989;'],
                        ['titulo' => 'Pendentes', 'valor' => $cards['pendentes'] ?? 0, 'sub' => 'Aguardando aprova&ccedil;&atilde;o', 'cor' => 'from-amber-500 to-orange-600', 'icone' => '&#9203;'],
                        [
                            'titulo' => 'Itens em Aberto',
                            'valor' => 'R$ '.number_format($cards['total_aberto'] ?? 0, 2, ',', '.'),
                            'sub' => 'Total pendente a receber',
                            'cor' => 'from-slate-600 to-slate-800',
                            'icone' => '&#128204;',
                            'link' => route('financeiro.faturamento-detalhado', [
                                'status' => 'pendente',
                                'filtrar' => 1,
                                'data_inicio' => now()->subMonth()->format('Y-m-d'),
                                'data_fim' => now()->format('Y-m-d'),
                            ]),
                        ],
                        [
                            'titulo' => 'Recebido em Caixa',
                            'valor' => 'R$ '.number_format($cards['total_recebido'] ?? 0, 2, ',', '.'),
                            'sub' => 'Baixas registradas',
                            'cor' => 'from-teal-500 to-emerald-700',
                            'icone' => '&#127974;',
                            'link' => route('financeiro.faturamento-detalhado', [
                                'status' => 'recebido',
                                'filtrar' => 1,
                                'data_inicio' => now()->subMonth()->format('Y-m-d'),
                                'data_fim' => now()->format('Y-m-d'),
                            ]),
                        ],
                    ];
                @endphp
                @foreach($cards as $card)
                    @php $tag = !empty($card['link']) ? 'a' : 'div'; @endphp
                    <{{ $tag }}
                        @if(!empty($card['link'])) href="{{ $card['link'] }}" @endif
                        class="rounded-3xl bg-gradient-to-br {{ $card['cor'] }} text-white shadow-lg shadow-slate-900/20 p-5 flex flex-col gap-3 animate-[fadeIn_0.4s_ease] {{ !empty($card['link']) ? 'hover:opacity-95 transition' : '' }}">
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white/15 text-xl">
                            {!! $card['icone'] !!}
                        </div>
                        <div>
                            <p class="text-sm font-semibold opacity-90">{{ $card['titulo'] }}</p>
                            <p class="text-2xl font-bold leading-tight mt-1">{{ $card['valor'] }}</p>
                            <p class="text-xs text-white/80 mt-1">{!! $card['sub'] !!}</p>
                        </div>
                    </{{ $tag }}>
                @endforeach
            </div>

        {{-- Fluxo de faturamento --}}
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">📌 Fluxo de Faturamento</h2>
                    <p class="text-xs text-slate-500">Etapas recorrentes do ciclo financeiro</p>
                </div>
            </div>

            @php
                $etapas = [
                    [
                        'titulo' => 'Dia 1 a 30',
                        'texto' => 'Per&iacute;odo de presta&ccedil;&atilde;o de servi&ccedil;os',
                        'badge' => 'Em andamento',
                        'cor' => 'bg-blue-50 text-blue-700 border-blue-100',
                        'accent' => 'text-blue-700 ring-blue-200 bg-blue-50',
                        'dot' => 'bg-blue-500',
                    ],
                    [
                        'titulo' => 'At&eacute; dia 3',
                        'texto' => 'Prazo para cliente validar faturamento',
                        'badge' => 'Aguardando',
                        'cor' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'accent' => 'text-amber-700 ring-amber-200 bg-amber-50',
                        'dot' => 'bg-amber-500',
                    ],
                    [
                        'titulo' => 'Dia 15',
                        'texto' => 'Emiss&atilde;o de NF e boletos',
                        'badge' => 'Autom&aacute;tico',
                        'cor' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                        'accent' => 'text-emerald-700 ring-emerald-200 bg-emerald-50',
                        'dot' => 'bg-emerald-500',
                    ],
                ];
            @endphp

            <div class="divide-y divide-slate-100">
                @foreach($etapas as $etapa)
                    <div class="px-5 py-4 flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $etapa['accent'] }}">
                                <span class="inline-block h-2 w-2 rounded-full {{ $etapa['dot'] }}"></span>
                                {!! $etapa['titulo'] !!}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{!! $etapa['texto'] !!}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full border text-xs font-semibold {{ $etapa['cor'] }}">
                            {{ $etapa['badge'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        </section>

        {{-- Contratos --}}
        <section id="contratos" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Contratos Ativos</h2>
                    <p class="text-sm text-slate-500">Gestão visual dos contratos com faturamento</p>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($contratos as $contrato)
                    @php
                        $status = strtoupper((string) $contrato->status);
                        $badge = match($status) {
                            'ATIVO' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                            'PENDENTE' => 'bg-amber-50 text-amber-700 border-amber-100',
                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                        };
                    @endphp
                    <div class="rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition">
                        <div class="px-5 py-4 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="h-11 w-11 rounded-2xl bg-indigo-50 text-indigo-600 grid place-items-center text-xl">📄</div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $contrato->cliente->razao_social ?? 'Cliente' }}</p>
                                    <div class="text-xs text-slate-500 flex flex-wrap gap-3">
{{--                                        <span>Valor do Contrato: <strong class="text-slate-800">R$ {{ number_format((float) $contrato->valor_mensal, 2, ',', '.') }}</strong></span>--}}
                                        <span>Vigência: {{ optional($contrato->vigencia_inicio)->format('d/m/Y') ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full border text-xs font-semibold {{ $badge }}">
                                    {{ ucfirst(strtolower($status)) }}
                                </span>
                                <a href="{{ route('financeiro.contratos.show', $contrato) }}"
                                   class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                                    Ver Contrato
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
