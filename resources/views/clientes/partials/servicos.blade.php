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
                'rota' => route('cliente.servicos.aso'),
                'disabled' => !$temTabela || !($precos['aso'] ?? null),
            ],
            [
                'slug' => 'pgr',
                'titulo' => 'Solicitar PGR',
                'desc' => 'Programa de Gerenciamento de Riscos.',
                'icone' => '📋',
                'preco' => $precos['pgr'] ?? null,
                'rota' => route('cliente.servicos.pgr'),
                'disabled' => !$temTabela || !($precos['pgr'] ?? null),
            ],
            [
                'slug' => 'pcmso',
                'titulo' => 'Solicitar PCMSO',
                'desc' => 'Programa de Controle Médico de Saúde Ocupacional.',
                'icone' => '📑',
                'preco' => $precos['pcmso'] ?? null,
                'rota' => route('cliente.servicos.pcmso'),
                'disabled' => !$temTabela || !($precos['pcmso'] ?? null),
            ],
            [
                'slug' => 'ltcat',
                'titulo' => 'Solicitar LTCAT',
                'desc' => 'Laudo Técnico das Condições Ambientais do Trabalho.',
                'icone' => '📄',
                'preco' => $precos['ltcat'] ?? null,
                'rota' => route('cliente.servicos.ltcat'),
                'disabled' => !$temTabela || !($precos['ltcat'] ?? null),
            ],
            [
                'slug' => 'apr',
                'titulo' => 'Solicitar APR',
                'desc' => 'Análise Preliminar de Riscos.',
                'icone' => '⚠️',
                'preco' => $precos['apr'] ?? null,
                'rota' => route('cliente.servicos.apr'),
                'disabled' => !$temTabela || !($precos['apr'] ?? null),
            ],
            [
                'slug' => 'treinamentos',
                'titulo' => 'Solicitar Treinamentos',
                'desc' => 'Treinamentos de Normas Regulamentadoras.',
                'icone' => '🎓',
                'preco' => $precos['treinamentos'] ?? null,
                'rota' => route('cliente.servicos.treinamentos'),
                'disabled' => !$temTabela || !($precos['treinamentos'] ?? null),
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
    @endphp

    <div class="px-4 md:px-6 py-4 md:py-5">
        <div class="grid gap-4 md:gap-5 md:grid-cols-2 lg:grid-cols-4">
            @foreach($cards as $card)
                @php
                    $disabled = $card['disabled'] ?? false;
                    $badge = $card['badge'] ?? null;
                    if (!$badge && array_key_exists('preco', $card)) {
                        $badge = $card['preco'] ? 'R$ '.number_format($card['preco'], 2, ',', '.') : 'Preço não definido';
                    }
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between {{ $disabled ? 'opacity-60' : '' }}">
                    <div>
                        <div class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-slate-100 text-slate-700 mb-3 text-lg">
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
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                                {{ $badge }}
                            </span>
                        @endif

                        @if($disabled)
                            <span class="text-slate-400 font-medium">Indisponível</span>
                        @else
                            <a href="{{ $card['rota'] }}"
                               class="text-[color:var(--color-brand-azul)] font-medium hover:opacity-100 opacity-80">
                                {{ $card['slug'] === 'funcionarios' ? 'Acessar' : 'Solicitar' }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
