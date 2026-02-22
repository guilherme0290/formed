@php
    use Carbon\Carbon;

    $cliente = $conta->cliente;
    $empresa = $conta->empresa;

    $total = (float) ($conta->total ?? 0);
    $pago = (float) ($conta->total_baixado ?? 0);
    $saldo = (float) ($conta->total_aberto ?? 0);

    $itens = $conta->itens
        ->sortBy(function ($item) {
            return [optional($item->data_realizacao)->timestamp ?? 0, $item->id];
        })
        ->values();

    $subtotal = (float) $itens->sum(fn ($item) => (float) ($item->valor ?? 0));
    $desconto = $subtotal > $total ? ($subtotal - $total) : 0.0;
    $acrescimos = $total > $subtotal ? ($total - $subtotal) : 0.0;

    $clienteNome = $cliente->razao_social ?? $cliente->nome_fantasia ?? 'Cliente';
    $empresaNome = $empresa->nome ?? 'FORMED MEDICINA E SEGURANÇA DO TRABALHO LTDA';

    $primeiraVenda = $itens->map(fn ($item) => $item->venda)->filter()->first();
    $primeiraTarefa = $primeiraVenda?->tarefa;
    $primeiroContrato = $primeiraVenda?->contrato;
    $propostaOrigem = $primeiroContrato?->propostaOrigem;
    $propostaId = $primeiroContrato?->proposta_id_origem ?? $propostaOrigem?->id;

    $datasExecucao = $itens->pluck('data_realizacao')->filter();
    $execInicio = $datasExecucao->min();
    $execFim = $datasExecucao->max();

    $faturaCodigo = now()->format('Y') . '-' . str_pad((string) $conta->id, 5, '0', STR_PAD_LEFT);
    $contratoLabel = $primeiroContrato
        ? ('CT-' . (optional($primeiroContrato->created_at)->format('Y') ?: now()->format('Y')) . '-' . str_pad((string) $primeiroContrato->id, 3, '0', STR_PAD_LEFT))
        : '—';
    $propostaLabel = $propostaId
        ? ('PROP-' . (optional($propostaOrigem?->created_at)->format('Y') ?: now()->format('Y')) . '-' . str_pad((string) $propostaId, 3, '0', STR_PAD_LEFT))
        : '—';
    $osLabel = $primeiraTarefa
        ? ('OS-' . (optional($primeiraTarefa->created_at)->format('Y') ?: now()->format('Y')) . '-' . str_pad((string) $primeiraTarefa->id, 3, '0', STR_PAD_LEFT))
        : '—';

    $badgeContratoAtivo = $primeiroContrato && strtoupper((string) ($primeiroContrato->status ?? '')) === 'ATIVO';

    $empresaEndereco = collect([
        $empresa->endereco ?? null,
        $empresa->numero ?? null,
        $empresa->bairro ?? null,
        $empresa->cidade?->nome ? ($empresa->cidade->nome . '/' . ($empresa->cidade->uf ?? '')) : null,
    ])->filter()->implode(' - ');

    $clienteEndereco = collect([
        $cliente->endereco ?? null,
        $cliente->numero ?? null,
        $cliente->bairro ?? null,
        $cliente->cidade?->nome ? ($cliente->cidade->nome . '/' . ($cliente->cidade->uf ?? '')) : null,
    ])->filter()->implode(' - ');

    $observacoes = [
        'Serviços executados conforme contrato/proposta aceita e execução concluída.',
        'Documentos entregues via portal do cliente ou fluxo acordado entre as partes.',
        'Em caso de atraso, poderá incidir multa de 2% e juros de 1% ao mês.',
    ];
    if ($contratoLabel !== '—') {
        $observacoes[] = 'Esta fatura está vinculada ao contrato ' . $contratoLabel . '.';
    }

    $formatMoney = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $formatDate = fn ($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '—';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fatura {{ $faturaCodigo }}</title>
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
        .section-body {
            padding: 8px 9px;
        }
        .small { font-size: 9px; }
        .muted { color: #64748b; }
        .label {
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .value {
            color: #111827;
            font-weight: 700;
            font-size: 10px;
        }
        .header-wrap {
            border: 1px solid #1e3a8a;
        }
        .header-top {
            background: #1f2a7c;
            color: #ffffff;
            padding: 12px 14px;
        }
        .header-bottom {
            background: #eef4ff;
            border-top: 1px solid #c9d9ff;
            padding: 8px 14px;
        }
        .brand-name {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .02em;
        }
        .brand-wrap {
            width: auto;
            border-collapse: collapse;
        }
        .brand-logo-cell {
            width: 38px;
            vertical-align: middle;
            padding-right: 8px;
        }
        .brand-text-cell {
            vertical-align: middle;
        }
        .brand-logo {
            display: inline-block;
            width: 32px;
            height: 32px;
            color: #ffffff;
        }
        .brand-sub {
            font-size: 9px;
            margin-top: 1px;
            color: #dbeafe;
        }
        .invoice-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: #dbeafe;
        }
        .invoice-number {
            font-size: 20px;
            font-weight: 700;
            margin-top: 2px;
            color: #ffffff;
        }
        .date-box {
            border: 1px solid #c7d2fe;
            background: #ffffff;
            padding: 6px 8px;
        }
        .date-box .label { color: #64748b; }
        .date-box .value { font-size: 10px; }
        .badge-ok {
            display: inline-block;
            border: 1px solid #86efac;
            background: #ecfdf5;
            color: #065f46;
            font-size: 8px;
            font-weight: 700;
            padding: 2px 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-services {
            table-layout: fixed;
        }
        .table-services th:nth-child(1), .table-services td:nth-child(1) { width: 34%; }
        .table-services th:nth-child(2), .table-services td:nth-child(2) { width: 12%; }
        .table-services th:nth-child(3), .table-services td:nth-child(3) { width: 24%; }
        .table-services th:nth-child(4), .table-services td:nth-child(4) { width: 8%; }
        .table-services th:nth-child(5), .table-services td:nth-child(5) { width: 11%; }
        .table-services th:nth-child(6), .table-services td:nth-child(6) { width: 11%; }
        .table-services th {
            background: #1f2a7c;
            color: #ffffff;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 7px 8px;
            text-align: left;
        }
        .table-services td {
            padding: 7px 8px;
            border-bottom: 1px solid #e5ebf3;
            font-size: 9px;
            vertical-align: middle;
        }
        .table-services tr.alt td { background: #f3f4f6; }
        .table-services td:nth-child(1),
        .table-services td:nth-child(3) {
            word-break: break-word;
        }
        .table-services td:nth-child(2),
        .table-services td:nth-child(4),
        .table-services td:nth-child(5),
        .table-services td:nth-child(6) {
            white-space: nowrap;
        }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .service-title {
            font-weight: 700;
            color: #111827;
        }
        .service-sub {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
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
        .total-highlight table td {
            padding: 0;
            color: #ffffff;
        }
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
        .line {
            border-top: 1px solid #dbe4f0;
            margin: 8px 0;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            margin-top: 30px;
            padding-top: 4px;
        }
        .text-right { text-align: right; }
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
                                    <svg class="brand-logo" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <circle cx="24" cy="24" r="22" fill="currentColor" opacity=".14"/>
                                        <path d="M14 30l10-18 10 18h-6l-4-8-4 8h-6z" fill="currentColor"/>
                                    </svg>
                                </td>
                                <td class="brand-text-cell">
                                    <div class="brand-name">FORMED</div>
                                    <div class="brand-sub">Medicina e Segurança do Trabalho</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width:42%; vertical-align:top;" class="text-right">
                        <div class="invoice-label">Fatura</div>
                        <div class="invoice-number">Nº {{ $faturaCodigo }}</div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="header-bottom">
            <table>
                <tr>
                    <td style="width:34%; padding-right:6px; vertical-align:top;">
                        <div class="date-box">
                            <div class="label">Data de Emissão</div>
                            <div class="value">{{ $formatDate($conta->created_at) }}</div>
                        </div>
                    </td>
                    <td style="width:34%; padding-right:6px; vertical-align:top;">
                        <div class="date-box">
                            <div class="label">Vencimento</div>
                            <div class="value">{{ $formatDate($conta->vencimento) }}</div>
                        </div>
                    </td>
                    <td style="width:32%; vertical-align:middle;" class="text-right">
                        <div class="small" style="color:#1e3a8a;"><strong>Saldo total:</strong> {{ $formatMoney($saldo) }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <table class="mb-8">
        <tr>
            <td style="width:50%; vertical-align:top; padding-right:4px;">
                <div class="section">
                    <div class="section-title">Dados da Empresa Emissora</div>
                    <div class="section-body">
                        <div class="value">{{ mb_strtoupper($empresaNome) }}</div>
                        <div class="small" style="margin-top:4px;">
                            <div><strong>CNPJ:</strong> {{ $empresa->cnpj ?? '—' }}</div>
                            <div><strong>Endereço:</strong> {{ $empresaEndereco !== '' ? $empresaEndereco : '—' }}</div>
                            <div><strong>Telefone:</strong> {{ $empresa->telefone ?? '—' }}</div>
                            <div><strong>Email:</strong> {{ $empresa->email ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </td>
            <td style="width:50%; vertical-align:top; padding-left:4px;">
                <div class="section">
                    <div class="section-title" style="background:#eef4ff; color:#1e3a8a;">Dados do Cliente</div>
                    <div class="section-body">
                        <table>
                            <tr>
                                <td style="padding:0; vertical-align:top;">
                                    <div class="label">Cliente</div>
                                    <div class="value" style="margin-top:2px;">{{ $clienteNome }}</div>
                                </td>
                                <td style="padding:0; vertical-align:top;" class="text-right">
                                    @if($badgeContratoAtivo)
                                        <span class="badge-ok">Contrato Ativo</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        <div class="small" style="margin-top:4px;">
                            <div><strong>CNPJ:</strong> {{ $cliente->cnpj ?? '—' }}</div>
                            <div><strong>Contato:</strong> {{ $cliente->contato ?? '—' }}</div>
                            <div><strong>Email:</strong> {{ $cliente->email ?? '—' }}</div>
                            <div><strong>Telefone:</strong> {{ $cliente->telefone ?? '—' }}</div>
                            <div><strong>Endereço:</strong> {{ $clienteEndereco !== '' ? $clienteEndereco : '—' }}</div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>



    <div class="section mb-8">
        <table class="table-services">
            <thead>
                <tr>
                    <th>Serviço</th>
                    <th>Data</th>
                    <th>Funcionário</th>
                    <th class="center">Qtde</th>
                    <th class="num">Valor Unit</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itens as $index => $item)
                    @php
                        $servicoNome = $item->servico?->nome ?? $item->descricao ?? $item->vendaItem?->descricao_snapshot ?? 'Serviço';
                        $funcionarioNome = $item->venda?->tarefa?->funcionario?->nome ?? '-';
                        $valorLinha = (float) ($item->valor ?? 0);
                        $realizacao = $item->data_realizacao ? Carbon::parse($item->data_realizacao)->format('d/m/Y') : null;
                    @endphp
                    <tr class="{{ $index % 2 === 1 ? 'alt' : '' }}">
                        <td>
                            <div class="service-title">{{ $servicoNome }}</div>
                        </td>
                        <td>{{ $realizacao ?? '—' }}</td>
                        <td>{{ $funcionarioNome }}</td>
                        <td class="center">1</td>
                        <td class="num">{{ $formatMoney($valorLinha) }}</td>
                        <td class="num"><strong>{{ $formatMoney($valorLinha) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:12px; text-align:center; color:#64748b;">Nenhum item encontrado nesta fatura.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <table class="mb-8">
        <tr>

            <td style="width:38%; vertical-align:top; padding-left:4px;">
                <div class="section">
                    <div class="section-title">Resumo Financeiro</div>
                    <div class="section-body">
                        <table class="summary-table">
                            <tr>
                                <td>Subtotal</td>
                                <td>{{ $formatMoney($subtotal) }}</td>
                            </tr>
                            <tr>
                                <td>Desconto</td>
                                <td>{{ $formatMoney($desconto) }}</td>
                            </tr>
                            <tr>
                                <td>Acréscimos</td>
                                <td>{{ $formatMoney($acrescimos) }}</td>
                            </tr>
                            <tr>
                                <td>Pago</td>
                                <td>{{ $formatMoney($pago) }}</td>
                            </tr>
                            <tr>
                                <td>Saldo</td>
                                <td>{{ $formatMoney($saldo) }}</td>
                            </tr>
                        </table>

                        <div class="line"></div>

                        <div class="total-highlight">
                            <table>
                                <tr>
                                    <td class="total-label">Total da Fatura</td>
                                    <td class="total-value">{{ $formatMoney($total) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Assinatura / Responsável</div>
        <div class="section-body">
            <table>
                <tr>
                    <td style="width:50%; vertical-align:top; padding-right:8px;">
                        <div class="signature-line">
                            <div class="small"><strong>Responsável Técnico</strong></div>
                            <div class="small muted">CRM: ____________________</div>
                        </div>
                    </td>
                    <td style="width:50%; vertical-align:top; padding-left:8px;">
                        <div class="signature-line">
                            <div class="small"><strong>Financeiro FORMED</strong></div>
                            <div class="small muted">{{ $empresa->email ?? 'financeiro@formed.com.br' }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
