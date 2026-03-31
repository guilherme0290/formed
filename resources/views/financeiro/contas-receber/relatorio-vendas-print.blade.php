@php
    use Carbon\Carbon;

    $empresaNome = $empresa->nome ?? 'FORMED MEDICINA E SEGURANÇA DO TRABALHO LTDA';
    $empresaDocumento = $empresa->cnpj ?? '—';
    $empresaTelefone = $empresa->telefone ?? '—';
    $empresaEmail = $empresa->email ?? '—';
    $empresaEndereco = collect([
        $empresa->endereco ?? null,
        $empresa->numero ?? null,
        $empresa->bairro ?? null,
        $empresa->cidade?->nome ? ($empresa->cidade->nome . '/' . ($empresa->cidade->uf ?? '')) : null,
    ])->filter()->implode(' - ');

    $formatMoney = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $formatDate = fn ($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '—';

    $tipoDataLabel = ($filtros['tipo_data'] ?? 'venda') === 'finalizacao' ? 'Data de finalização' : 'Data da venda';
    $statusLabel = match ($filtros['status_finalizacao'] ?? 'todas') {
        'finalizadas' => 'Finalizadas',
        'nao_finalizadas' => 'Não finalizadas',
        default => 'Todas',
    };
    $clienteLabel = trim((string) ($filtros['cliente'] ?? '')) !== '' ? (string) $filtros['cliente'] : 'Todos os clientes';
    $periodoLabel = trim((string) ($filtros['data_inicio'] ?? '')) || trim((string) ($filtros['data_fim'] ?? ''))
        ? (($formatDate($filtros['data_inicio'] ?? null)) . ' a ' . ($formatDate($filtros['data_fim'] ?? null)))
        : 'Período não informado';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas</title>
    <style>
        @page { margin: 12mm 12mm 14mm; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 10px;
            line-height: 1.35;
            background: #ffffff;
        }
        * { box-sizing: border-box; }
        .mb-8 { margin-bottom: 8px; }
        .mb-10 { margin-bottom: 10px; }
        .section {
            border: 1px solid #dbe4f0;
            background: #ffffff;
        }
        .section-title {
            background: #f4f7fb;
            border-bottom: 1px solid #dbe4f0;
            padding: 6px 9px;
            font-size: 8px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .section-body { padding: 8px 9px; }
        .header-wrap { border: 1px solid #1e3a8a; }
        .header-top { background: #1f2a7c; color: #ffffff; padding: 12px 14px; }
        .header-bottom { background: #eef4ff; border-top: 1px solid #c9d9ff; padding: 8px 14px; }
        .brand-name { font-size: 18px; font-weight: 700; letter-spacing: .02em; }
        .brand-wrap { width: auto; border-collapse: collapse; }
        .brand-logo-cell { width: 38px; vertical-align: middle; padding-right: 8px; }
        .brand-text-cell { vertical-align: middle; }
        .brand-logo { display: inline-block; width: 32px; height: 32px; object-fit: contain; }
        .brand-sub { font-size: 9px; margin-top: 1px; color: #dbeafe; }
        .report-label { font-size: 8px; text-transform: uppercase; letter-spacing: .14em; color: #dbeafe; }
        .report-number { font-size: 18px; font-weight: 700; margin-top: 2px; color: #ffffff; }
        .date-box {
            border: 1px solid #c7d2fe;
            background: #ffffff;
            padding: 6px 8px;
            min-height: 44px;
        }
        .date-box .label {
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .date-box .value { font-size: 10px; font-weight: 700; color: #111827; }
        .text-right { text-align: right; }
        .small { font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        .table-services {
            table-layout: fixed;
            border: 1px solid #dbe4f0;
        }
        .table-services th {
            background: #1f2a7c;
            color: #ffffff;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 7px 6px;
            text-align: left;
        }
        .table-services td {
            padding: 6px;
            border-bottom: 1px solid #e5ebf3;
            font-size: 9px;
            vertical-align: top;
            word-break: break-word;
        }
        .table-services tr.alt td { background: #f8fafc; }
        .table-services .num { text-align: right; white-space: nowrap; }
        .group-title {
            background: #eef4ff;
            border: 1px solid #c9d9ff;
            color: #1e3a8a;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 6px 8px;
            margin-top: 8px;
        }
        .group-subtitle {
            float: right;
            text-transform: none;
            letter-spacing: 0;
            font-size: 8px;
            color: #334155;
        }
        .summary-table td {
            padding: 3px 0;
            font-size: 9px;
        }
        .summary-table td:last-child {
            text-align: right;
            font-weight: 600;
            white-space: nowrap;
        }
        .total-highlight {
            background: #1f2a7c;
            color: #ffffff;
            padding: 8px 10px;
            margin-top: 8px;
        }
        .total-highlight table td { padding: 0; color: #ffffff; }
        .total-highlight .total-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .total-highlight .total-value {
            text-align: right;
            font-size: 15px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="header-wrap mb-10">
        <div class="header-top">
            <table>
                <tr>
                    <td style="width:58%; vertical-align:top;">
                        <table class="brand-wrap">
                            <tr>
                                <td class="brand-logo-cell">
                                    @if(!empty($logoSrc))
                                        <img src="{{ $logoSrc }}" alt="Formed" class="brand-logo">
                                    @endif
                                </td>
                                <td class="brand-text-cell">
                                    <div class="brand-name">FORMED</div>
                                    <div class="brand-sub">MEDICINA E SEGURANCA DO TRABALHO LTDA</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width:42%; vertical-align:top;" class="text-right">
                        <div class="report-label">Relatório</div>
                        <div class="report-number">Vendas por {{ $agrupamento === 'cliente' ? 'Cliente' : 'Serviço' }}</div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="header-bottom">
            <table>
                <tr>
                    <td style="width:33%; padding-right:6px; vertical-align:top;">
                        <div class="date-box">
                            <div class="label">Período</div>
                            <div class="value">{{ $periodoLabel }}</div>
                        </div>
                    </td>
                    <td style="width:33%; padding-right:6px; vertical-align:top;">
                        <div class="date-box">
                            <div class="label">Tipo de Data / Status</div>
                            <div class="value">{{ $tipoDataLabel }} / {{ $statusLabel }}</div>
                        </div>
                    </td>
                    <td style="width:34%; vertical-align:top;">
                        <div class="date-box">
                            <div class="label">Cliente (filtro)</div>
                            <div class="value">{{ $clienteLabel }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <table class="mb-8">
        <tr>
            <td style="width:100%; vertical-align:top;">
                <div class="section">
                    <div class="section-title">Empresa Emissora</div>
                    <div class="section-body">
                        <div style="font-size:10px; font-weight:700; color:#111827;">{{ mb_strtoupper($empresaNome) }}</div>
                        <div class="small" style="margin-top:4px;">
                            <div><strong>CNPJ:</strong> {{ $empresaDocumento }}</div>
                            <div><strong>Endereço:</strong> {{ $empresaEndereco !== '' ? $empresaEndereco : '—' }}</div>
                            <div><strong>Telefone:</strong> {{ $empresaTelefone }}</div>
                            <div><strong>Email:</strong> {{ $empresaEmail }}</div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section mb-8">
        <div class="section-title">Itens do Relatório</div>
        <div class="section-body" style="padding-top: 4px;">
            @forelse($grupos as $grupo)
                <div class="group-title">
                    {{ $grupo['titulo'] }}
                    <span class="group-subtitle">{{ $grupo['quantidade'] }} item(ns) | Subtotal {{ $formatMoney($grupo['subtotal']) }}</span>
                </div>
                <table class="table-services">
                    <thead>
                        <tr>
                            <th style="width:10%;">Registro</th>
                            <th style="width:11%;">Data</th>
                            <th style="width:22%;">Cliente</th>
                            <th style="width:45%;">Item de Serviço</th>
                            <th style="width:12%;" class="num">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grupo['itens'] as $index => $item)
                            <tr class="{{ $index % 2 === 1 ? 'alt' : '' }}">
                                <td>{{ $item['registro'] ?? '—' }}</td>
                                <td>{{ $formatDate($item['data_referencia'] ?? null) }}</td>
                                <td>{{ $item['cliente_nome'] ?? '—' }}</td>
                                <td>{{ $item['descricao'] ?? '—' }}</td>
                                <td class="num"><strong>{{ $formatMoney($item['valor'] ?? 0) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @empty
                <table class="table-services">
                    <tbody>
                        <tr>
                            <td style="padding: 12px; text-align:center; color:#64748b;">Nenhum item encontrado com os filtros atuais.</td>
                        </tr>
                    </tbody>
                </table>
            @endforelse
        </div>
    </div>

    <table>
        <tr>
            <td style="width:38%; vertical-align:top; padding-left:4px;">
                <div class="section">
                    <div class="section-title">Resumo Financeiro</div>
                    <div class="section-body">
                        <table class="summary-table">
                            <tr>
                                <td>Agrupamento</td>
                                <td>{{ $agrupamento === 'cliente' ? 'Cliente' : 'Serviço' }}</td>
                            </tr>
                            <tr>
                                <td>Grupos</td>
                                <td>{{ count($grupos) }}</td>
                            </tr>
                            <tr>
                                <td>Itens</td>
                                <td>{{ $totalItens }}</td>
                            </tr>
                        </table>

                        <div class="total-highlight">
                            <table>
                                <tr>
                                    <td class="total-label">Total do Relatório</td>
                                    <td class="total-value">{{ $formatMoney($totalGeral) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
