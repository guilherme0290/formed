<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>RelatÃ³rio de Produtividade</title>
    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }
        body { color: #1f2937; font-size: 12px; }
        .header { border-bottom: 2px solid #0f766e; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { display: inline-block; vertical-align: middle; background: #ffffff; border: 1px solid #e5e7eb; padding: 8px 12px; border-radius: 10px; }
        .title { display: inline-block; vertical-align: middle; margin-left: 12px; }
        .title h1 { margin: 0; font-size: 18px; }
        .title p { margin: 4px 0 0; font-size: 11px; color: #4b5563; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 10px; background: #e2e8f0; color: #334155; }
        .section { margin-top: 14px; }
        .grid { width: 100%; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
        .muted { color: #6b7280; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; }
        .value { font-size: 12px; font-weight: bold; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f8fafc; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.08em; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            @if(!empty($logoData))
                <img src="{{ $logoData }}" alt="Formed" style="height: 56px;">
            @else
                <strong>Formed</strong>
            @endif
        </div>
        <div class="title">
            <h1>RelatÃ³rios de Produtividade</h1>
            <span class="badge">{{ strtoupper((string) ($status_proposta_selecionado ?? 'FECHADA')) }}</span>
        </div>
    </div>

    <div class="section">
        <table class="grid">
            <tr>
                <td style="width: 33.33%; padding-right: 8px;">
                    <div class="box">
                        <div class="muted">ServiÃ§os finalizados</div>
                        <div class="value">{{ $servicos_total ?? 0 }}</div>
                    </div>
                </td>
                <td style="width: 33.33%; padding: 0 4px;">
                    <div class="box">
                        <div class="muted">Propostas ({{ $status_proposta_selecionado ?? 'FECHADA' }})</div>
                        <div class="value">{{ $propostas_total ?? 0 }}</div>
                    </div>
                </td>
                <td style="width: 33.33%; padding-left: 8px;">
                    <div class="box">
                        <div class="muted">Valor total propostas</div>
                        <div class="value">R$ {{ number_format($propostas_valor_total ?? 0, 2, ',', '.') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if(($setor_selecionado ?? 'todos') !== 'comercial')
        <div class="section">
            <div class="muted">ServiÃ§os finalizados por tipo</div>
            <table>
                <thead>
                    <tr>
                        <th>ServiÃ§o</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($servicos_por_servico ?? []) as $row)
                        <tr>
                            <td>{{ $row->servico_nome }}</td>
                            <td>{{ $row->total }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="color: #6b7280;">Nenhum serviÃ§o finalizado no perÃ­odo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="muted">ServiÃ§os finalizados (detalhado)</div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>ServiÃ§o</th>
                        <th>ResponsÃ¡vel</th>
                        <th>Finalizado em</th>
                        <th>DescriÃ§Ã£o</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($servicos_tarefas ?? []) as $tarefa)
                        @php
                            $finalizadoEm = $tarefa->finalizado_em
                                ? \Carbon\Carbon::parse($tarefa->finalizado_em)->format('d/m/Y')
                                : '-';
                        @endphp
                        <tr>
                            <td>{{ $tarefa->id }}</td>
                            <td>{{ optional($tarefa->cliente)->razao_social ?? '-' }}</td>
                            <td>{{ optional($tarefa->servico)->nome ?? '-' }}</td>
                            <td>{{ optional($tarefa->responsavel)->name ?? '-' }}</td>
                            <td>{{ $finalizadoEm }}</td>
                            <td>{{ $tarefa->descricao ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="color: #6b7280;">Nenhum serviÃ§o finalizado para os filtros selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if(($setor_selecionado ?? 'todos') !== 'operacional')
        <div class="section">
            <div class="muted">Propostas comerciais ({{ $status_proposta_selecionado ?? 'FECHADA' }})</div>
            <table>
                <thead>
                    <tr>
                        <th>CÃ³digo</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Atualizado em</th>
                        <th>DescriÃ§Ã£o</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($propostas ?? []) as $proposta)
                        @php
                            $atualizadoEm = $proposta->updated_at
                                ? \Carbon\Carbon::parse($proposta->updated_at)->format('d/m/Y')
                                : '-';
                        @endphp
                        <tr>
                            <td>{{ $proposta->codigo ?? $proposta->id }}</td>
                            <td>{{ optional($proposta->cliente)->razao_social ?? '-' }}</td>
                            <td>{{ optional($proposta->vendedor)->name ?? '-' }}</td>
                            <td>{{ $proposta->status ?? '-' }}</td>
                            <td>R$ {{ number_format($proposta->valor_total ?? 0, 2, ',', '.') }}</td>
                            <td>{{ $atualizadoEm }}</td>
                            <td>{{ $proposta->observacoes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="color: #6b7280;">Nenhuma proposta encontrada para os filtros selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</body>
</html>
