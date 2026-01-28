<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>RelatÃ³rio de Tarefas</title>
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
            <h1>RelatÃ³rios de Tarefas</h1>
            <span class="badge">{{ strtoupper((string) ($status_selecionado ?? 'TODOS')) }}</span>
        </div>
    </div>

    <div class="section">
        <table class="grid">
            <tr>
                <td style="width: 25%; padding-right: 8px;">
                    <div class="box">
                        <div class="muted">Pendentes</div>
                        <div class="value">{{ $resumo['pendentes'] ?? 0 }}</div>
                    </div>
                </td>
                <td style="width: 25%; padding: 0 4px;">
                    <div class="box">
                        <div class="muted">Finalizadas</div>
                        <div class="value">{{ $resumo['finalizadas'] ?? 0 }}</div>
                    </div>
                </td>
                <td style="width: 25%; padding: 0 4px;">
                    <div class="box">
                        <div class="muted">Em execuÃ§Ã£o</div>
                        <div class="value">{{ $resumo['em_execucao'] ?? 0 }}</div>
                    </div>
                </td>
                <td style="width: 25%; padding-left: 8px;">
                    <div class="box">
                        <div class="muted">Aguardando fornecedor</div>
                        <div class="value">{{ $resumo['aguardando_fornecedor'] ?? 0 }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="width: 25%; padding-right: 8px; padding-top: 8px;">
                    <div class="box">
                        <div class="muted">CorreÃ§Ã£o</div>
                        <div class="value">{{ $resumo['correcao'] ?? 0 }}</div>
                    </div>
                </td>
                <td style="width: 25%; padding: 8px 4px 0;">
                    <div class="box">
                        <div class="muted">Atrasados</div>
                        <div class="value">{{ $resumo['atrasados'] ?? 0 }}</div>
                    </div>
                </td>
                <td style="width: 25%; padding: 8px 4px 0;">
                    <div class="box">
                        <div class="muted">Total</div>
                        <div class="value">{{ $resumo['total'] ?? 0 }}</div>
                    </div>
                </td>
                <td style="width: 25%; padding-left: 8px; padding-top: 8px;">
                    <div class="box">
                        <div class="muted">Status</div>
                        <div class="value">{{ $status_selecionado ?? 'Todos' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>ServiÃ§o</th>
                    <th>ResponsÃ¡vel</th>
                    <th>Status</th>
                    <th>InÃ­cio previsto</th>
                    <th>Fim previsto</th>
                    <th>Finalizado em</th>
                    <th>Criado em</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($tarefas ?? []) as $tarefa)
                    @php
                        $inicioPrevisto = $tarefa->inicio_previsto
                            ? \Carbon\Carbon::parse($tarefa->inicio_previsto)->format('d/m/Y')
                            : '-';
                        $fimPrevisto = $tarefa->fim_previsto
                            ? \Carbon\Carbon::parse($tarefa->fim_previsto)->format('d/m/Y')
                            : '-';
                        $finalizadoEm = $tarefa->finalizado_em
                            ? \Carbon\Carbon::parse($tarefa->finalizado_em)->format('d/m/Y')
                            : '-';
                        $criadoEm = $tarefa->created_at
                            ? \Carbon\Carbon::parse($tarefa->created_at)->format('d/m/Y')
                            : '-';
                    @endphp
                    <tr>
                        <td>{{ $tarefa->id }}</td>
                        <td>{{ optional($tarefa->cliente)->razao_social ?? '-' }}</td>
                        <td>{{ optional($tarefa->servico)->nome ?? '-' }}</td>
                        <td>{{ optional($tarefa->responsavel)->name ?? '-' }}</td>
                        <td>{{ optional($tarefa->coluna)->nome ?? '-' }}</td>
                        <td>{{ $inicioPrevisto }}</td>
                        <td>{{ $fimPrevisto }}</td>
                        <td>{{ $finalizadoEm }}</td>
                        <td>{{ $criadoEm }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="color: #6b7280;">Nenhuma tarefa encontrada para os filtros selecionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
