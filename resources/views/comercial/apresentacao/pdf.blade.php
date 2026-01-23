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
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #475569; }
        .right { text-align: right; }
        .zebra tbody tr:nth-child(odd) { background: #f8fafc; }
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
    @endphp

    <div class="page">
        <div class="card">
            @php
                $logoPngData = null;
                $logoSvgPath = storage_path('app/public/logo.svg');
                if (is_file($logoSvgPath)) {
                    $svg = file_get_contents($logoSvgPath);
                    if (preg_match('/data:image\/png;base64,([^"]+)/', $svg, $match)) {
                        $logoPngData = $match[1];
                    }
                }
                $logoFormedPath = storage_path('app/public/logo (1)-transparente.png');
                $logoFormedData = null;
                if (is_file($logoFormedPath)) {
                    $logoFormedData = base64_encode(file_get_contents($logoFormedPath));
                }
                $canRenderLogo = extension_loaded('gd') && !empty($logoPngData);
            @endphp
            <div class="header" style="background: {{ $theme['header'] }}">
                <table class="grid">
                    <tr>
                        <td style="width: 70%; text-align: left;">
                            <div style="display: inline-block; vertical-align: middle;">
                                @if($logoFormedData)
                                    <img src="data:image/png;base64,{{ $logoFormedData }}" alt="Formed" style="height: 80px; vertical-align: middle; margin-right: 12px;">
                                @endif
                                <div style="display: inline-block; vertical-align: middle;">
                                    <div class="title">FORMED</div>
                                    <div class="subtitle">Medicina e Segurança do Trabalho</div>
                                </div>
                            </div>
                        </td>
                        <td style="width: 30%; text-align: right;">
                            @if($clienteLogoData)
                                <img src="{{ $clienteLogoData }}" alt="Logo do cliente" style="height: 80px; margin-left: 8px;">
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
                        {{ $tituloSegmento }}
                    </div>
                    <div style="margin-top: 12px; text-align: left;">
                        <div>{{ $conteudo['intro'][0] ?? '' }}</div>
                        <div style="margin-top: 6px;">{{ $conteudo['intro'][1] ?? '' }}</div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">Serviços Essenciais</div>
                    <table class="zebra">
                        <thead>
                        <tr>
                            <th>Item</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach(($conteudo['servicos'] ?? []) as $s)
                            <tr>
                                <td>{{ $s }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @if(($precos ?? collect())->count())
                    <div class="section">
                        <div class="section-title">Serviços</div>
                        <table class="zebra">
                            <thead>
                            <tr>
                                <th>Item</th>
                                <th class="right">Qtd</th>
                                <th class="right">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($precos as $preco)
                                @php
                                    $item = $preco->tabelaPrecoItem;
                                    $descricao = $item?->descricao ?: $item?->servico?->nome ?: 'Item';
                                    $qtd = (float) $preco->quantidade;
                                    $valor = (float) ($item?->preco ?? 0);
                                    $total = $qtd * $valor;
                                @endphp
                                <tr>
                                    <td>
                                        <div style="font-weight: bold;">{{  $item?->codigo}}</div>

                                            <div style="color: #6b7280; font-size: 10px;"> {{  $descricao }}</div>

                                    </td>
                                    <td class="right">{{ number_format($qtd, 2, ',', '.') }}</td>
                                    <td class="right">R$ {{ number_format($total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(($exames ?? collect())->count())
                    <div class="section">
                        <div class="section-title">Exames</div>
                        <table class="zebra">
                            <thead>
                            <tr>
                                <th>Exame</th>
                                <th class="right">Qtd</th>
                                <th class="right">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($exames as $exameRow)
                                @php
                                    $exame = $exameRow->exame ?? null;
                                    $qtd = (float) ($exameRow->quantidade ?? 1);
                                    $valor = (float) ($exame?->preco ?? 0);
                                    $total = $qtd * $valor;
                                @endphp
                                <tr>
                                    <td>
                                        <div style="font-weight: bold;">{{ $exame?->titulo ?? 'Exame' }}</div>
                                        @if(!empty($exame?->descricao))
                                            <div style="color: #6b7280; font-size: 10px;">{{ $exame->descricao }}</div>
                                        @endif
                                    </td>
                                    <td class="right">{{ number_format($qtd, 2, ',', '.') }}</td>
                                    <td class="right">R$ {{ number_format($total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(($treinamentos ?? collect())->count())
                    <div class="section">
                        <div class="section-title">Treinamentos</div>
                        <table class="zebra">
                            <thead>
                            <tr>
                                <th>Treinamento</th>
                                <th class="right">Qtd</th>
                                <th class="right">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($treinamentos as $treinamentoRow)
                                @php
                                    $treinamento = $treinamentoRow->treinamento ?? null;
                                    $qtd = (float) ($treinamentoRow->quantidade ?? 1);
                                    $valor = (float) ($treinamentoRow->tabelaItem?->preco ?? 0);
                                    $total = $qtd * $valor;
                                @endphp
                                <tr>
                                    <td>
                                        <div style="font-weight: bold;">{{ $treinamento?->codigo ?? 'NR' }} — {{ $treinamento?->titulo ?? 'Treinamento' }}</div>
                                    </td>
                                    <td class="right">{{ number_format($qtd, 2, ',', '.') }}</td>
                                    <td class="right">R$ {{ number_format($total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(($tabelasManuais ?? collect())->count())
                    @foreach($tabelasManuais as $tabela)
                        @php
                            $colunas = $tabela->colunas ?? [];
                            $linhas = $tabela->linhas ?? collect();
                        @endphp
                        <div class="section">
                            <div class="section-title">{{ $tabela->titulo ?: 'Tabela' }}</div>
                            @if(!empty($tabela->subtitulo))
                                <div style="margin-bottom: 6px; color: #64748b;">{{ $tabela->subtitulo }}</div>
                            @endif
                            @if(!empty($colunas))
                                <table class="zebra">
                                    <thead>
                                    <tr>
                                        @foreach($colunas as $col)
                                            <th>{{ $col }}</th>
                                        @endforeach
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($linhas as $linha)
                                        @php $valores = $linha->valores ?? []; @endphp
                                        <tr>
                                            @foreach($colunas as $index => $col)
                                                <td>{{ $valores[$index] ?? '' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div style="color: #94a3b8;">Sem colunas definidas.</div>
                            @endif
                        </div>
                    @endforeach
                @endif

                @if(($esocialFaixas ?? collect())->count())
                    <div class="section">
                        <div class="section-title">eSocial</div>
                        @if(!empty($esocialDescricao))
                            <div style="margin-bottom: 8px;">{!! $esocialDescricao !!}</div>
                        @endif
                        <table class="zebra">
                            <thead>
                            <tr>
                                <th>Faixa</th>
                                <th>Descrição</th>
                                <th class="right">Valor</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($esocialFaixas as $faixa)
                                <tr>
                                    <td>{{ $faixa->inicio }}@if($faixa->fim) - {{ $faixa->fim }}@else+@endif</td>
                                    <td>{{ $faixa->descricao }}</td>
                                    <td class="right">R$ {{ number_format((float) $faixa->preco, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="section benefits">
                    <div class="section-title" style="color: #065f46;">Benefícios</div>
                    <div>{{ $conteudo['beneficios'] ?? '' }}</div>
                </div>

                <div class="footer">
                    <div><strong>FORMED</strong> • Medicina e Segurança do Trabalho</div>
                    <div>{{ $conteudo['rodape'] ?? 'comercial@formed.com.br • (00) 0000-0000' }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
