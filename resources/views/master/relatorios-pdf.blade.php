<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relat&oacute;rio de Produtividade</title>
    <style>
        @page { margin: 80px 40px 70px 40px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }
        .muted { color: #6b7280; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; }
        .title { font-size: 22px; font-weight: bold; margin: 6px 0 4px; }
        .subtitle { color: #6b7280; font-size: 12px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 10px; background: #e5e7eb; color: #374151; }
        .section { margin-top: 18px; }
        .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.08em; color: #1f2937; margin-bottom: 8px; }
        .box { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; background: #ffffff; }
        .value { font-size: 14px; font-weight: bold; margin-top: 4px; }
        .grid { width: 100%; border-collapse: collapse; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f9fafb; font-size: 10px; color: #4b5563; text-transform: uppercase; letter-spacing: 0.08em; }
        .progress { height: 6px; background: #e5e7eb; border-radius: 999px; }
        .progress > span { display: block; height: 6px; border-radius: 999px; background: #93c5fd; }
        .footer { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 6px; }
        .footer .right { text-align: right; }
        .pagenum:before { content: counter(page) " / " counter(pages); }
        .page-break { page-break-before: always; }
        .cover { text-align: center; padding-top: 80px; }
        .cover .logo { margin-bottom: 18px; }
        .cover .meta { margin-top: 18px; color: #6b7280; }
        .note { font-size: 11px; color: #4b5563; line-height: 1.5; }
    </style>
</head>
<body>
    @php
        $dataInicioFmt = !empty($data_inicio) ? \Carbon\Carbon::parse($data_inicio)->format('d/m/Y') : '-';
        $dataFimFmt = !empty($data_fim) ? \Carbon\Carbon::parse($data_fim)->format('d/m/Y') : '-';
        $setorSolicitado = $filtros_label['setor'] ?? 'Todos';
        $usuarioSolicitado = $filtros_label['usuario'] ?? 'Todos';
        $statusLinha = ($aba_selecionada ?? 'operacional') === 'operacional'
            ? 'Finalizado'
            : ($filtros_label['status_proposta'] ?? ($status_proposta_selecionado ?? 'FECHADA'));
        $totalPeriodo = ($resumo_periodo['pendentes'] ?? 0) + ($resumo_periodo['finalizadas'] ?? 0) + ($resumo_periodo['atrasadas'] ?? 0);
        $taxaConclusao = $totalPeriodo > 0 ? round((($resumo_periodo['finalizadas'] ?? 0) / $totalPeriodo) * 100) : 0;
        $servicosTotalSum = (int) (collect($servicos_resumo ?? [])->sum('total') ?: 1);
        $responsavel = auth()->user()->name ?? 'Administrador Master';
        $dataGeracao = \Carbon\Carbon::now()->format('d/m/Y H:i');
    @endphp

    <div class="footer">
        <table class="grid">
            <tr>
                <td>Formed &middot; Relat&oacute;rio corporativo</td>
                <td class="right">P&aacute;gina <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    <div class="cover">
        <div class="logo">
            @if(!empty($logoData))
                <img src="{{ $logoData }}" alt="Formed" style="height: 64px;">
            @else
                <strong>Formed</strong>
            @endif
        </div>
        <div class="title">Relat&oacute;rio de Produtividade</div>
        <div class="subtitle">Documento executivo para tomada de decis&atilde;o</div>
        <div class="meta">
            <div><span class="badge">Setor: {{ $setorSolicitado }}</span></div>
            <div style="margin-top: 8px;">Per&iacute;odo analisado: {{ $dataInicioFmt }} a {{ $dataFimFmt }}</div>
            <div>Data e respons&aacute;vel: {{ $dataGeracao }} &middot; {{ $responsavel }}</div>
        </div>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <div class="section-title">Resumo executivo</div>
        <div class="box note">
            No per&iacute;odo analisado foram registradas <strong>{{ $totalPeriodo }}</strong> tarefas, com
            taxa de conclus&atilde;o de <strong>{{ $taxaConclusao }}%</strong>. O volume operacional ativo &eacute;
            de <strong>{{ $operacional_ativo ?? 0 }}</strong> tarefas. O desempenho geral indica
            {{ $taxaConclusao >= 70 ? 'alta efici&ecirc;ncia operacional' : ($taxaConclusao >= 50 ? 'estabilidade com oportunidades de melhoria' : 'necessidade de a&ccedil;&atilde;o para redu&ccedil;&atilde;o de pend&ecirc;ncias') }}.
        </div>
    </div>

    <div class="section">
        <div class="section-title">Indicadores principais</div>
        <table class="grid">
            <tr>
                <td style="width: 20%; padding-right: 6px;"><div class="box"><div class="muted">Pendentes</div><div class="value">{{ $resumo_periodo['pendentes'] ?? 0 }}</div></div></td>
                <td style="width: 20%; padding: 0 6px;"><div class="box"><div class="muted">Finalizadas</div><div class="value">{{ $resumo_periodo['finalizadas'] ?? 0 }}</div></div></td>
                <td style="width: 20%; padding: 0 6px;"><div class="box"><div class="muted">Atrasadas</div><div class="value">{{ $resumo_periodo['atrasadas'] ?? 0 }}</div></div></td>
                <td style="width: 20%; padding: 0 6px;"><div class="box"><div class="muted">Operacional ativo</div><div class="value">{{ $operacional_ativo ?? 0 }}</div></div></td>
                <td style="width: 20%; padding-left: 6px;"><div class="box"><div class="muted">Taxa de conclus&atilde;o</div><div class="value">{{ $taxaConclusao }}%</div></div></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Produtividade por usu&aacute;rio</div>
        <table>
            <thead>
                <tr>
                    <th>Usu&aacute;rio</th>
                    <th>Finalizadas</th>
                    <th>Pendentes</th>
                    <th>Atrasadas</th>
                    <th>Servi&ccedil;os</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($produtividade_top_usuarios['labels'] ?? []) as $index => $label)
                    @php
                        $finalizadas = (int) ($produtividade_top_usuarios['servicos'][$index] ?? 0);
                        $pendentes = (int) ($produtividade_top_usuarios['pendentes'][$index] ?? 0);
                        $atrasadas = (int) ($produtividade_top_usuarios['atrasadas'][$index] ?? 0);
                        $totalServ = $finalizadas + $pendentes + $atrasadas;
                    @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $finalizadas }}</td>
                        <td>{{ $pendentes }}</td>
                        <td>{{ $atrasadas }}</td>
                        <td>{{ $totalServ }}</td>
                    </tr>
                @endforeach
                @if(empty($produtividade_top_usuarios['labels']))
                    <tr>
                        <td colspan="5" style="color: #6b7280;">Sem dados para o per&iacute;odo.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Distribui&ccedil;&atilde;o de servi&ccedil;os</div>
        <table>
            <thead>
                <tr>
                    <th>Servi&ccedil;o</th>
                    <th>Total</th>
                    <th>Percentual</th>
                    <th>Gr&aacute;fico</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($servicos_resumo ?? []) as $row)
                    @php
                        $percentual = $servicosTotalSum > 0 ? round(($row->total / $servicosTotalSum) * 100) : 0;
                    @endphp
                    <tr>
                        <td>{{ $row->servico_nome }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ $percentual }}%</td>
                        <td>
                            <div class="progress"><span style="width: {{ $percentual }}%;"></span></div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="color: #6b7280;">Sem dados para o per&iacute;odo.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Status das tarefas</div>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Pendentes</td><td>{{ $resumo_periodo['pendentes'] ?? 0 }}</td></tr>
                <tr><td>Finalizadas</td><td>{{ $resumo_periodo['finalizadas'] ?? 0 }}</td></tr>
                <tr><td>Atrasadas</td><td>{{ $resumo_periodo['atrasadas'] ?? 0 }}</td></tr>
                <tr><td>Operacional ativo</td><td>{{ $operacional_ativo ?? 0 }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Observa&ccedil;&otilde;es autom&aacute;ticas</div>
        <div class="box note">
            1) A taxa de conclus&atilde;o de {{ $taxaConclusao }}% indica {{ $taxaConclusao >= 70 ? 'excelente controle operacional' : ($taxaConclusao >= 50 ? 'desempenho consistente, com espa&ccedil;o para otimiza&ccedil;&atilde;o' : 'necessidade de interven&ccedil;&atilde;o para reduzir pend&ecirc;ncias') }}.
            <br>
            2) O volume de tarefas atrasadas &eacute; {{ ($resumo_periodo['atrasadas'] ?? 0) > 0 ? 'um ponto de aten&ccedil;&atilde;o para prioriza&ccedil;&atilde;o imediata' : 'controlado no per&iacute;odo analisado' }}.
            <br>
            3) A distribui&ccedil;&atilde;o de servi&ccedil;os evidencia concentra&ccedil;&atilde;o em {{ ($servicos_resumo ?? collect())->first()->servico_nome ?? 'servi&ccedil;os diversos' }}.
        </div>
    </div>
</body>
</html>
