{{-- resources/views/clientes/partials/servicos.blade.php --}}
<section class="bg-white rounded-3xl shadow-md border border-slate-200 overflow-hidden">
    {{-- Barra azul marinho de título --}}
    <header class="bg-[#1b2738] px-5 md:px-6 py-3 flex items-center justify-between">
        <div>
            <h3 class="text-sm md:text-base font-semibold text-white">
                Serviços Disponíveis
            </h3>
            <p class="hidden md:block text-[11px] text-sky-100/80">
                Selecione um serviço para solicitar atendimentos e laudos para o seu quadro de colaboradores.
            </p>
        </div>
    </header>

    @php
        $temTabela = $temTabela ?? false;
        $precos = $precos ?? [];
        $contratoAtivo = $contratoAtivo ?? null;
        $servicosContrato = $servicosContrato ?? [];
        $servicosIds = $servicosIds ?? [];
        $temContratoAtivo = (bool) $contratoAtivo;
        $servicosExecutados = $servicosExecutados ?? [];

        $permitidos = [
            'aso' => $temContratoAtivo
                && in_array($servicosIds['aso'] ?? null, $servicosContrato)
                && !in_array($servicosIds['aso'] ?? null, $servicosExecutados, true),
            'pgr' => $temContratoAtivo
                && in_array($servicosIds['pgr'] ?? null, $servicosContrato)
                && !in_array($servicosIds['pgr'] ?? null, $servicosExecutados, true),
            'pcmso' => $temContratoAtivo
                && in_array($servicosIds['pcmso'] ?? null, $servicosContrato)
                && !in_array($servicosIds['pcmso'] ?? null, $servicosExecutados, true),
            'ltcat' => $temContratoAtivo
                && in_array($servicosIds['ltcat'] ?? null, $servicosContrato)
                && !in_array($servicosIds['ltcat'] ?? null, $servicosExecutados, true),
            'apr' => $temContratoAtivo
                && in_array($servicosIds['apr'] ?? null, $servicosContrato)
                && !in_array($servicosIds['apr'] ?? null, $servicosExecutados, true),
            'treinamentos' => $temContratoAtivo
                && in_array($servicosIds['treinamentos'] ?? null, $servicosContrato)
                && !in_array($servicosIds['treinamentos'] ?? null, $servicosExecutados, true),
        ];

        $vendedorTelefone = preg_replace('/\D+/', '', $vendedorTelefone ?? optional($cliente->vendedor)->telefone ?? '');
        $cards = [
            [
                'slug' => 'funcionarios',
                'titulo' => 'Meus Funcionários',
                'desc' => 'Gerencie seus colaboradores e documentação.',
                'icone' => '👥',
                'badge' => 'Gestão',
                'rota' => route('cliente.funcionarios.index'),
                'disabled' => false,
            ],
            [
                'slug' => 'aso',
                'titulo' => 'Agendar ASO',
                'desc' => 'Agende exames ocupacionais para seus colaboradores.',
                'icone' => '📅',
                'preco' => $precos['aso'] ?? null,
                'permitido' => $permitidos['aso'] ?? false,
                'rota' => route('cliente.servicos.aso'),
                'disabled' => !($permitidos['aso'] ?? false),
            ],
            [
                'slug' => 'pgr',
                'titulo' => 'Solicitar PGR',
                'desc' => 'Programa de Gerenciamento de Riscos.',
                'icone' => '📋',
                'preco' => $precos['pgr'] ?? null,
                'permitido' => $permitidos['pgr'] ?? false,
                'rota' => route('cliente.servicos.pgr'),
                'disabled' => !($permitidos['pgr'] ?? false),
            ],
            [
                'slug' => 'pcmso',
                'titulo' => 'Solicitar PCMSO',
                'desc' => 'Programa de Controle Médico de Saúde Ocupacional.',
                'icone' => '📑',
                'preco' => $precos['pcmso'] ?? null,
                'permitido' => $permitidos['pcmso'] ?? false,
                'rota' => route('cliente.servicos.pcmso'),
                'disabled' => !($permitidos['pcmso'] ?? false),
            ],
            [
                'slug' => 'ltcat',
                'titulo' => 'Solicitar LTCAT',
                'desc' => 'Laudo Técnico das Condições Ambientais do Trabalho.',
                'icone' => '📄',
                'preco' => $precos['ltcat'] ?? null,
                'permitido' => $permitidos['ltcat'] ?? false,
                'rota' => route('cliente.servicos.ltcat'),
                'disabled' => !($permitidos['ltcat'] ?? false),
            ],
            [
                'slug' => 'apr',
                'titulo' => 'Solicitar APR',
                'desc' => 'Análise Preliminar de Riscos.',
                'icone' => '⚠️',
                'preco' => $precos['apr'] ?? null,
                'permitido' => $permitidos['apr'] ?? false,
                'rota' => route('cliente.servicos.apr'),
                'disabled' => !($permitidos['apr'] ?? false),
            ],
            [
                'slug' => 'treinamentos',
                'titulo' => 'Solicitar Treinamentos',
                'desc' => 'Treinamentos de Normas Regulamentadoras.',
                'icone' => '🎓',
                'preco' => $precos['treinamentos'] ?? null,
                'permitido' => $permitidos['treinamentos'] ?? false,
                'rota' => route('cliente.servicos.treinamentos'),
                'disabled' => !($permitidos['treinamentos'] ?? false),
            ],
            [
                'slug' => 'arquivos',
                'titulo' => 'Meus Arquivos',
                'desc' => 'Acesse todos os documentos e certificados liberados.',
                'icone' => '📁',
                'badge' => 'Downloads',
                'rota' => route('cliente.arquivos.index'),
                'disabled' => false,
            ],
        ];

        $cardStyles = [
            'funcionarios' => [
                'card' => 'border-blue-100 bg-blue-50/80 hover:bg-blue-100 hover:border-blue-200',
                'icon' => 'bg-blue-500 text-white',
                'link' => 'text-blue-700 group-hover:text-blue-800',
                'badge' => 'bg-blue-50 text-blue-700 border border-blue-100',
            ],
            'aso' => [
                'card' => 'border-emerald-100 bg-emerald-50/80 hover:bg-emerald-100 hover:border-emerald-200',
                'icon' => 'bg-emerald-500 text-white',
                'link' => 'text-emerald-700 group-hover:text-emerald-800',
                'badge' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
            ],
            'pgr' => [
                'card' => 'border-green-100 bg-green-50/80 hover:bg-green-100 hover:border-green-200',
                'icon' => 'bg-green-500 text-white',
                'link' => 'text-green-700 group-hover:text-green-800',
                'badge' => 'bg-green-50 text-green-700 border border-green-100',
            ],
            'pcmso' => [
                'card' => 'border-indigo-100 bg-indigo-50/80 hover:bg-indigo-100 hover:border-indigo-200',
                'icon' => 'bg-indigo-500 text-white',
                'link' => 'text-indigo-700 group-hover:text-indigo-800',
                'badge' => 'bg-indigo-50 text-indigo-700 border border-indigo-100',
            ],
            'ltcat' => [
                'card' => 'border-purple-100 bg-purple-50/80 hover:bg-purple-100 hover:border-purple-200',
                'icon' => 'bg-purple-500 text-white',
                'link' => 'text-purple-700 group-hover:text-purple-800',
                'badge' => 'bg-purple-50 text-purple-700 border border-purple-100',
            ],
            'apr' => [
                'card' => 'border-rose-100 bg-rose-50/80 hover:bg-rose-100 hover:border-rose-200',
                'icon' => 'bg-rose-500 text-white',
                'link' => 'text-rose-700 group-hover:text-rose-800',
                'badge' => 'bg-rose-50 text-rose-700 border border-rose-100',
            ],
            'treinamentos' => [
                'card' => 'border-amber-100 bg-amber-50/80 hover:bg-amber-100 hover:border-amber-200',
                'icon' => 'bg-amber-500 text-white',
                'link' => 'text-amber-700 group-hover:text-amber-800',
                'badge' => 'bg-amber-50 text-amber-700 border border-amber-100',
            ],
            'arquivos' => [
                'card' => 'border-slate-100 bg-slate-50/80 hover:bg-slate-100 hover:border-slate-200',
                'icon' => 'bg-slate-600 text-white',
                'link' => 'text-slate-700 group-hover:text-slate-800',
                'badge' => 'bg-slate-50 text-slate-700 border border-slate-100',
            ],
        ];
    @endphp

    <div class="px-4 md:px-6 py-4 md:py-5">
        <div class="grid gap-4 md:gap-5 md:grid-cols-2 lg:grid-cols-4">
            @foreach($cards as $card)
                @php
                    $disabled = $card['disabled'] ?? false;
                    $badge = $card['badge'] ?? null;
                    $servicoIdAtual = $servicosIds[$card['slug']] ?? null;
                    $executado = $servicoIdAtual && in_array($servicoIdAtual, $servicosExecutados, true);
                    if (!$badge && array_key_exists('preco', $card)) {
                        $badge = $card['preco'] ? 'R$ '.number_format($card['preco'], 2, ',', '.') : '';
                    }
                    $showComercial = array_key_exists('preco', $card) && !($card['permitido'] ?? true);
                    $whatsappMensagem = 'Olá! Gostaria de contratar o serviço "'.$card['titulo'].'" para minha empresa. '.$cliente->razao_social;
                    $whatsappUrl = $vendedorTelefone
                        ? 'https://wa.me/'.$vendedorTelefone.'?text='.urlencode($whatsappMensagem)
                        : '#';
                    $cardUrl = $showComercial ? $whatsappUrl : $card['rota'];
                    $cardTarget = $showComercial ? '_blank' : null;
                    $cardRel = $showComercial ? 'noopener noreferrer' : null;
                    $style = $cardStyles[$card['slug']] ?? [
                        'card' => 'border-slate-100 bg-white hover:bg-slate-50 hover:border-slate-200',
                        'icon' => 'bg-slate-100 text-slate-700',
                        'link' => 'text-slate-700 group-hover:text-slate-800',
                        'badge' => 'bg-slate-50 text-slate-700 border border-slate-100',
                    ];

                @endphp
                <a href="{{ $cardUrl }}"
                   @if($cardTarget) target="{{ $cardTarget }}" @endif
                   @if($cardRel) rel="{{ $cardRel }}" @endif
                   class="group rounded-2xl border shadow-sm p-4 flex flex-col justify-between transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg {{ $style['card'] }}">
                    <div>
                        <div class="inline-flex items-center justify-center h-9 w-9 rounded-2xl mb-3 text-lg transition {{ $style['icon'] }}">
                            {{ $card['icone'] }}
                        </div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            {{ $card['titulo'] }}
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $card['desc'] }}
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-xs">
                        @if($badge)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $style['badge'] }}">
                                {{ $badge }}
                            </span>
                        @endif

                        @if($showComercial)
                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                                💬 Conversar com o comercial
                            </span>
                        @else
                            <span class="{{ $style['link'] }} font-medium group-hover:opacity-100 opacity-80">
                                {{ $card['slug'] === 'funcionarios' ? 'Acessar' : 'Solicitar' }}
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
