<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Apresentação Comercial</title>
    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }
        body { color: #0f172a; font-size: 12px; }
        .page { padding: 28px; }
        .header { padding: 18px 20px; color: #fff; border-radius: 12px 12px 0 0; }
        .card { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .meta { background: #f8fafc; border-radius: 12px; padding: 16px; }
        .grid { width: 100%; }
        .title { font-size: 18px; font-weight: bold; margin: 0; }
        .subtitle { font-size: 11px; color: rgba(255,255,255,0.8); margin-top: 2px; }
        .section { margin-top: 16px; }
        .section-title { font-weight: bold; font-size: 12px; margin-bottom: 8px; }
        .list { padding-left: 14px; margin: 8px 0 0; }
        .list li { margin-bottom: 6px; }
        .benefits { border-radius: 12px; padding: 16px; border: 1px solid #a7f3d0; background: #ecfdf5; }
        .footer { text-align: center; margin-top: 18px; color: #475569; font-size: 10px; }
    </style>
</head>
<body>
    @php
        $themeBySegment = [
            'construcao-civil' => [
                'header' => '#b45309',
                'bar' => '#d97706',
                'bg' => '#fffbeb',
                'titulo' => 'CONSTRUÇÃO CIVIL',
            ],
            'industria' => [
                'header' => '#2563eb',
                'bar' => '#2563eb',
                'bg' => '#eff6ff',
                'titulo' => 'INDÚSTRIA',
            ],
            'comercio' => [
                'header' => '#059669',
                'bar' => '#059669',
                'bg' => '#ecfdf5',
                'titulo' => 'COMÉRCIO / VAREJO / SUPERMERCADOS',
            ],
            'restaurante' => [
                'header' => '#dc2626',
                'bar' => '#dc2626',
                'bg' => '#fff1f2',
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

    <div class="page">
        <div class="card">
            <div class="header" style="background: {{ $theme['header'] }}">
                <table class="grid">
                    <tr>
                        <td style="width: 65%;">
                            <div class="title">FORMED</div>
                            <div class="subtitle">Medicina e Segurança do Trabalho</div>
                        </td>
                        <td style="width: 35%; text-align: right;">
                            @if($logoData)
                                <img src="{{ $logoData }}" alt="Formed" style="height: 36px;">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div style="padding: 18px;">
                <div class="meta" style="background: {{ $theme['bg'] }}">
                    <table class="grid">
                        <tr>
                            <td style="width: 6px; background: {{ $theme['bar'] }};"></td>
                            <td style="padding-left: 12px;">
                                <div style="font-weight: bold;">Apresentação para:</div>
                                <div style="margin-top: 6px;">
                                    <div><strong>Razão Social:</strong> {{ $cliente['razao_social'] ?? '—' }}</div>
                                    <div><strong>CNPJ:</strong> {{ $cliente['cnpj'] ?? '—' }}</div>
                                    <div><strong>Contato:</strong> {{ $cliente['contato'] ?? '—' }}</div>
                                    <div><strong>Telefone:</strong> {{ $cliente['telefone'] ?? '—' }}</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="section" style="background: #0f172a; color: #fff; border-radius: 12px; padding: 16px; text-align: center;">
                    <div style="font-weight: bold; letter-spacing: 0.12em; font-size: 12px;">
                        {{ $theme['titulo'] }}
                    </div>
                    <div style="margin-top: 12px; text-align: left;">
                        <div>{{ $conteudo['intro'][0] ?? '' }}</div>
                        <div style="margin-top: 6px;">{{ $conteudo['intro'][1] ?? '' }}</div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">Serviços Essenciais</div>
                    <ul class="list">
                        @foreach(($conteudo['servicos'] ?? []) as $s)
                            <li>{{ $s }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="section benefits">
                    <div class="section-title" style="color: #065f46;">Benefícios</div>
                    <div>{{ $conteudo['beneficios'] ?? '' }}</div>
                </div>

                <div class="footer">
                    <div><strong>FORMED</strong> • Medicina e Segurança do Trabalho</div>
                    <div>comercial@formed.com.br • (00) 0000-0000</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
