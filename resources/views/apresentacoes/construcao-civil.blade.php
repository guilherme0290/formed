@extends('layouts.landing')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/apresentacoes/construcao-civil.css'])
@endpush

@section('content')
    <div class="cc-page">
        <nav class="navbar navbar-expand-lg cc-navbar position-fixed top-0 start-0 w-100">
            <div class="container cc-container">
                <a class="navbar-brand d-flex align-items-center gap-3" href="#">
                    <span class="cc-brand-mark">
                        <img src="{{ asset('storage/iconFormed.png') }}" alt="FORMED">
                    </span>
                    <span class="cc-brand-copy">
                        <strong>FORMED</strong>
                        <small>Saúde e Segurança do Trabalho</small>
                    </span>
                </a>
                <a href="#contato" class="btn cc-btn cc-btn-outline">Solicitar atendimento</a>
            </div>
        </nav>

        <main>
            <section class="cc-section cc-hero d-flex align-items-center" id="inicio">
                <div class="container cc-container">
                    <div class="row align-items-center gy-5">
                        <div class="col-lg-6">
                            <div class="cc-reveal">
                                <span class="cc-kicker">Soluções para Construção Civil</span>
                                <h1 class="cc-display mt-3">Construção Civil</h1>
                                <h2 class="cc-subdisplay mt-3">Sua obra não pode parar</h2>
                                <p class="cc-lead mt-4">
                                    Soluções rápidas e completas em Saúde e Segurança do Trabalho para empresas da
                                    construção civil.
                                </p>
                                <div class="d-flex flex-wrap gap-3 mt-4">
                                    <a href="#contato" class="btn cc-btn cc-btn-primary btn-lg">Solicitar atendimento</a>
                                    <a href="#solucoes" class="btn cc-btn cc-btn-ghost btn-lg">Conhecer soluções</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="cc-hero-visual cc-reveal">
                                <div class="cc-hero-card">
                                    <img src="{{ asset('assets/apresentacao/construcao-civil/obra-capa.avif') }}"
                                         alt="Obra com trabalhadores usando EPI">
                                </div>
                                <div class="cc-floating-badge cc-floating-badge--one">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                    Atendimento ágil
                                </div>
                                <div class="cc-floating-badge cc-floating-badge--two">
                                    <i class="bi bi-shield-check"></i>
                                    SST em conformidade
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cc-section cc-section-soft" id="problemas">
                <div class="container cc-container">
                    <div class="cc-section-heading text-center cc-reveal">
                        <span class="cc-kicker">Problemas do Setor</span>
                        <h2>Os principais desafios das empresas da construção civil</h2>
                    </div>
                    <div class="row g-4 mt-2">
                        @php
                            $problemas = [
                                ['icon' => 'bi-clock-history', 'text' => 'Atraso na entrega do ASO (mais de 5 dias)'],
                                ['icon' => 'bi-person-workspace', 'text' => 'Funcionários não conseguem iniciar atividades'],
                                ['icon' => 'bi-file-earmark-text', 'text' => 'Demora na emissão de PGR e PCMSO'],
                                ['icon' => 'bi-calendar2-check', 'text' => 'Dificuldade no agendamento de exames'],
                                ['icon' => 'bi-geo-alt', 'text' => 'Deslocamento do colaborador para outras unidades'],
                                ['icon' => 'bi-exclamation-triangle', 'text' => 'Burocracia no atendimento'],
                            ];
                        @endphp
                        @foreach ($problemas as $problema)
                            <div class="col-md-6 col-xl-4">
                                <article class="cc-card cc-reveal h-100">
                                    <div class="cc-icon"><i class="bi {{ $problema['icon'] }}"></i></div>
                                    <p class="mb-0">{{ $problema['text'] }}</p>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="cc-section" id="impactos">
                <div class="container cc-container">
                    <div class="row align-items-center gy-4">
                        <div class="col-lg-5">
                            <div class="cc-section-heading cc-reveal">
                                <span class="cc-kicker">Impactos</span>
                                <h2>Consequências desses problemas</h2>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="cc-panel cc-reveal">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    @foreach (['Atrasos na obra', 'Prejuízos financeiros', 'Riscos trabalhistas', 'Problemas com fiscalização', 'Baixa produtividade'] as $impacto)
                                        <div class="col">
                                            <div class="cc-list-item">
                                                <i class="bi bi-check2-circle"></i>
                                                <span>{{ $impacto }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cc-section cc-section-dark" id="solucoes">
                <div class="container cc-container">
                    <div class="cc-section-heading text-center cc-reveal">
                        <span class="cc-kicker">Soluções</span>
                        <h2>Como resolvemos isso</h2>
                    </div>
                    <div class="row g-4 mt-2">
                        @foreach ([
                            ['icon' => 'bi-stopwatch', 'title' => 'Atendimento rápido', 'text' => 'Fluxo ágil para liberação de exames e documentos ocupacionais.'],
                            ['icon' => 'bi-heart-pulse', 'title' => 'Exames ocupacionais ágeis', 'text' => 'Execução organizada para reduzir espera e liberar equipes com rapidez.'],
                            ['icon' => 'bi-file-earmark-medical', 'title' => 'Programas completos de segurança', 'text' => 'PGR, PCMSO e documentos obrigatórios com entrega acelerada.'],
                            ['icon' => 'bi-diagram-3', 'title' => 'Integração com eSocial', 'text' => 'Envios e controles alinhados com as exigências legais e operacionais.'],
                        ] as $solucao)
                            <div class="col-md-6 col-xl-3">
                                <article class="cc-card cc-card-accent cc-reveal h-100">
                                    <div class="cc-icon"><i class="bi {{ $solucao['icon'] }}"></i></div>
                                    <h3>{{ $solucao['title'] }}</h3>
                                    <p>{{ $solucao['text'] }}</p>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="cc-section" id="aso-altura">
                <div class="container cc-container">
                    <div class="row align-items-center gy-4">
                        <div class="col-lg-6">
                            <div class="cc-section-heading cc-reveal">
                                <span class="cc-kicker">ASO Trabalho em Altura</span>
                                <h2>ASO para Trabalho em Altura</h2>
                                <p>Entrega em até 24 horas para liberação rápida do colaborador.</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="cc-panel cc-reveal">
                                @foreach (['Atendimento rápido', 'Exames ocupacionais completos', 'Liberação para início imediato das atividades'] as $item)
                                    <div class="cc-list-item">
                                        <i class="bi bi-arrow-right-short"></i>
                                        <span>{{ $item }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cc-section cc-section-soft" id="programas">
                <div class="container cc-container">
                    <div class="cc-section-heading text-center cc-reveal">
                        <span class="cc-kicker">Programas de Segurança</span>
                        <h2>Programas obrigatórios</h2>
                        <p>Entrega em até 2 dias.</p>
                    </div>
                    <div class="row justify-content-center g-4 mt-2">
                        @foreach ([
                            ['sigla' => 'PGR', 'nome' => 'Programa de Gerenciamento de Riscos'],
                            ['sigla' => 'PCMSO', 'nome' => 'Programa de Controle Médico de Saúde Ocupacional'],
                        ] as $programa)
                            <div class="col-md-6 col-xl-5">
                                <article class="cc-card cc-program-card cc-reveal h-100">
                                    <span class="cc-program-badge">{{ $programa['sigla'] }}</span>
                                    <h3>{{ $programa['nome'] }}</h3>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="cc-section" id="treinamentos">
                <div class="container cc-container">
                    <div class="cc-section-heading text-center cc-reveal">
                        <span class="cc-kicker">Treinamentos NRs</span>
                        <h2>Treinamentos obrigatórios</h2>
                    </div>
                    <div class="cc-badge-grid mt-4 cc-reveal">
                        @foreach (['NR-01', 'NR-06', 'NR-11', 'NR-12', 'NR-18', 'NR-33', 'NR-35'] as $nr)
                            <div class="cc-badge-card">{{ $nr }}</div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="cc-section cc-section-dark" id="esocial">
                <div class="container cc-container">
                    <div class="cc-section-heading text-center cc-reveal">
                        <span class="cc-kicker">eSocial</span>
                        <h2>Integração com eSocial</h2>
                    </div>
                    <div class="row g-4 mt-2">
                        @foreach ([
                            ['code' => 'S-2210', 'text' => 'Comunicação de Acidente de Trabalho'],
                            ['code' => 'S-2220', 'text' => 'Monitoramento da Saúde do Trabalhador'],
                            ['code' => 'S-2240', 'text' => 'Condições Ambientais do Trabalho'],
                        ] as $evento)
                            <div class="col-md-4">
                                <article class="cc-card cc-card-dark cc-reveal h-100">
                                    <span class="cc-event-code">{{ $evento['code'] }}</span>
                                    <p>{{ $evento['text'] }}</p>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="cc-section" id="laudos">
                <div class="container cc-container">
                    <div class="row align-items-center gy-4">
                        <div class="col-lg-5">
                            <div class="cc-section-heading cc-reveal">
                                <span class="cc-kicker">Laudos</span>
                                <h2>Laudos técnicos</h2>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <article class="cc-card cc-reveal h-100">
                                        <h3>LTIP</h3>
                                        <p>Laudo Técnico de Insalubridade e Periculosidade</p>
                                    </article>
                                </div>
                                <div class="col-md-6">
                                    <article class="cc-card cc-reveal h-100">
                                        <h3>Documentos técnicos</h3>
                                        <p>Outros documentos técnicos de segurança ocupacional.</p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cc-section cc-section-soft" id="beneficios">
                <div class="container cc-container">
                    <div class="cc-section-heading text-center cc-reveal">
                        <span class="cc-kicker">Benefícios</span>
                        <h2>Benefícios para sua empresa</h2>
                    </div>
                    <div class="row g-3 mt-3">
                        @foreach ([
                            'Redução de atrasos na obra',
                            'Mais segurança para os colaboradores',
                            'Conformidade com a legislação',
                            'Mais produtividade',
                            'Menos burocracia',
                        ] as $beneficio)
                            <div class="col-md-6 col-xl-4">
                                <div class="cc-list-item cc-list-item-card cc-reveal">
                                    <i class="bi bi-shield-check"></i>
                                    <span>{{ $beneficio }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="cc-section cc-cta" id="contato">
                <div class="container cc-container">
                    <div class="cc-cta-box text-center cc-reveal">
                        <span class="cc-kicker">Call To Action</span>
                        <h2 class="mt-3">Sua obra não pode parar</h2>
                        <p class="mt-3">
                            Conte com soluções rápidas e seguras em Saúde e Segurança do Trabalho.
                        </p>
                        <a href="mailto:gestao@formedseg.com.br" class="btn cc-btn cc-btn-primary btn-lg mt-3">
                            Solicitar atendimento
                        </a>
                    </div>
                </div>
            </section>
        </main>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    @vite(['resources/js/apresentacoes/construcao-civil.js'])
@endpush
