@extends('layouts.comercial')
@section('title', 'Apresentação da Proposta')

@section('content')
    @php
        $themeBySegment = [
            'construcao-civil' => [
                'headerBg' => 'bg-amber-700',
                'apresentacaoBg' => 'bg-amber-50',
                'apresentacaoBar' => 'bg-amber-600',
                'titulo' => 'CONSTRUÇÃO CIVIL',
            ],
            'industria' => [
                'headerBg' => 'bg-blue-600',
                'apresentacaoBg' => 'bg-blue-50',
                'apresentacaoBar' => 'bg-blue-600',
                'titulo' => 'INDÚSTRIA',
            ],
            'comercio' => [
                'headerBg' => 'bg-emerald-600',
                'apresentacaoBg' => 'bg-emerald-50',
                'apresentacaoBar' => 'bg-emerald-600',
                'titulo' => 'COMÉRCIO / VAREJO / SUPERMERCADOS',
            ],
            'restaurante' => [
                'headerBg' => 'bg-red-600',
                'apresentacaoBg' => 'bg-rose-50',
                'apresentacaoBar' => 'bg-red-600',
                'titulo' => 'RESTAURANTE / ALIMENTAÇÃO',
            ],
        ];

        $theme = $themeBySegment[$segmento] ?? $themeBySegment['construcao-civil'];

        $conteudo = [
            'construcao-civil' => [
                'intro' => [
                    'A FORMED apoia empresas da construção civil com uma apresentação clara e objetiva dos serviços de SST, alinhada às exigências legais e à rotina do canteiro.',
                    'Nosso foco é reduzir riscos, organizar documentação e manter a operação em conformidade de forma simples e contínua.',
                ],
                'servicos' => [
                    'PGR e inventário de riscos',
                    'PCMSO e gestão de exames ocupacionais',
                    'ASO (admissional, periódico e demissional)',
                    'Treinamentos obrigatórios (NRs)',
                    'Gestão de documentos e laudos (LTCAT, APR, PAE)',
                ],
                'beneficios' => 'Organização e previsibilidade no atendimento, redução de riscos e suporte contínuo para manter a obra em conformidade.',
            ],
            'industria' => [
                'intro' => [
                    'Para operações industriais, a FORMED entrega uma solução robusta de SST com foco em controle de riscos, gestão documental e suporte contínuo.',
                    'Prontidão para auditorias, conformidade e rotina organizada para a equipe de segurança e RH.',
                ],
                'servicos' => [
                    'PGR (inventário e plano de ação)',
                    'PCMSO e gestão de ASOs',
                    'Gestão de exames complementares',
                    'Laudos técnicos e documentação (LTCAT)',
                    'Treinamentos e capacitações (NRs)',
                ],
                'beneficios' => 'Menos retrabalho, mais controle, prontidão para auditorias e melhoria contínua de segurança e produtividade.',
            ],
            'comercio' => [
                'intro' => [
                    'A FORMED simplifica a gestão de SST para empresas de comércio e varejo com atendimento objetivo e documentação organizada.',
                    'Apoio recorrente para manter a empresa regular sem travar a operação.',
                ],
                'servicos' => [
                    'PGR e documentação obrigatória',
                    'PCMSO e ASO',
                    'Gestão de exames ocupacionais',
                    'Treinamentos aplicáveis (NRs)',
                    'Suporte para rotinas e fiscalizações',
                ],
                'beneficios' => 'Regularidade com agilidade, redução de riscos e tranquilidade para o gestor focar na operação.',
            ],
            'restaurante' => [
                'intro' => [
                    'A FORMED oferece suporte completo em SST para o segmento de alimentação, com foco em conformidade e prevenção de riscos.',
                    'Documentação em dia e apoio contínuo para manter a operação segura e regular.',
                ],
                'servicos' => [
                    'PGR e documentação de SST',
                    'PCMSO e ASO',
                    'Gestão de exames ocupacionais',
                    'Treinamentos e orientações aplicáveis',
                    'Suporte contínuo para adequações',
                ],
                'beneficios' => 'Atendimento prático, documentação em dia e apoio para manter a operação segura e regular.',
            ],
        ][$segmento] ?? [];
    @endphp

    <div class="min-h-[calc(100vh-64px)] bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 md:px-6 py-6">
            <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">

                {{-- Header do documento --}}
                <div class="{{ $theme['headerBg'] }} px-6 py-4 flex items-center justify-between gap-3">
                    <div class="text-white">
                        <div class="text-lg font-semibold leading-tight">FORMED</div>
                        <div class="text-xs text-white/80">Medicina e Segurança do Trabalho</div>
                    </div>

                    <div class="flex items-center gap-2 print:hidden">
                        <button type="button"
                                onclick="window.print()"
                                class="rounded-xl bg-white/95 hover:bg-white border border-white/50 text-slate-800 px-3 py-1.5 text-xs font-semibold">
                            Imprimir
                        </button>
                        <a href="{{ route('comercial.apresentacao.segmento') }}"
                           class="rounded-xl bg-white/95 hover:bg-white border border-white/50 text-slate-800 px-3 py-1.5 text-xs font-semibold">
                            Voltar
                        </a>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Apresentação para --}}
                    <div class="rounded-2xl {{ $theme['apresentacaoBg'] }} border border-slate-200 overflow-hidden">
                        <div class="grid grid-cols-[6px,1fr]">
                            <div class="{{ $theme['apresentacaoBar'] }}"></div>
                            <div class="p-5">
                                <div class="font-semibold text-slate-900">Apresentação para:</div>
                                <div class="mt-2 space-y-1 text-sm text-slate-700">
                                    <div><span class="text-slate-500">Razão Social:</span> <span class="font-semibold">{{ $cliente['razao_social'] ?? '—' }}</span></div>
                                    <div><span class="text-slate-500">CNPJ:</span> <span class="font-semibold">{{ $cliente['cnpj'] ?? '—' }}</span></div>
                                    <div><span class="text-slate-500">Contato:</span> <span class="font-semibold">{{ $cliente['contato'] ?? '—' }}</span></div>
                                    <div><span class="text-slate-500">Telefone:</span> <span class="font-semibold">{{ $cliente['telefone'] ?? '—' }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bloco do segmento (azul-marinho fixo) --}}
                    <div class="rounded-2xl bg-slate-900 text-white p-6 text-center">
                        <div class="inline-flex items-center gap-2 justify-center">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-500">
                                <span class="h-2 w-2 rounded-full bg-white/90"></span>
                            </span>
                            <span class="text-sm font-extrabold tracking-wide">{{ $theme['titulo'] }}</span>
                        </div>

                        <div class="mt-4 space-y-2 text-sm text-white/85 text-left md:text-center">
                            <p>{{ $conteudo['intro'][0] ?? '' }}</p>
                            <p>{{ $conteudo['intro'][1] ?? '' }}</p>
                        </div>
                    </div>

                    {{-- Serviços Essenciais --}}
                    <div class="rounded-2xl bg-white border border-slate-200 p-6">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-6 4h6m-7 4h8m-10 6h12V4H6v16z"/>
                            </svg>
                            <h3 class="text-sm font-semibold text-slate-900">Serviços Essenciais</h3>
                        </div>

                        <ul class="mt-4 space-y-2 text-sm text-slate-700">
                            @foreach(($conteudo['servicos'] ?? []) as $s)
                                <li class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6L9 17l-5-5"/>
                                    </svg>
                                    <span>{{ $s }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Benefícios --}}
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 overflow-hidden">
                        <div class="grid grid-cols-[6px,1fr]">
                            <div class="bg-emerald-500"></div>
                            <div class="p-6">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6L9 17l-5-5"/>
                                    </svg>
                                    <h3 class="text-sm font-semibold text-emerald-800">Benefícios</h3>
                                </div>
                                <p class="mt-3 text-sm text-emerald-900/80">
                                    {{ $conteudo['beneficios'] ?? '' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Rodapé --}}
                    <div class="pt-4">
                        <div class="h-px bg-blue-600/30"></div>
                        <div class="mt-4 text-center text-sm">
                            <div class="font-semibold text-blue-700">FORMED</div>
                            <div class="text-slate-600 text-xs mt-0.5">Medicina e Segurança do Trabalho</div>
                            <div class="text-slate-500 text-xs mt-2">comercial@formed.com.br • (00) 0000-0000</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
