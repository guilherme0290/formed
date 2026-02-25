<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .line { border-bottom: 1px solid #d1d5db; margin: 10px 0; }
        .header { width: 100%; }
        .header td { vertical-align: top; }
        .logo-box { width: 86px; height: 48px; border: 1px dashed #9ca3af; color: #6b7280; text-align: center; line-height: 48px; font-size: 9px; }
        .title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .subtitle { color: #4b5563; font-size: 10px; }
        .block { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .block th { text-align: left; background: #f3f4f6; border: 1px solid #e5e7eb; padding: 6px; font-size: 10px; }
        .block td { border: 1px solid #e5e7eb; padding: 6px; }
        .num { text-align: right; white-space: nowrap; }
        .small { color: #6b7280; font-size: 9px; }
        .footer { position: fixed; bottom: 8px; left: 28px; right: 28px; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
@php
    $subtotal = (float) $conta->itens->sum('valor');
    $descontos = 0.00;
    $acrescimos = 0.00;
    $total = (float) $conta->total;
    $pago = (float) $conta->total_baixado;
    $saldo = max($total - $pago, 0);
@endphp

<table class="header" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width: 95px;"><div class="logo-box">LOGO</div></td>
        <td>
            <div class="title">FORMED - Medicina e Seguranca do Trabalho</div>
            <div class="subtitle">CNPJ: {{ $empresa->cnpj ?? '00.000.000/0001-00' }} | Endereco: {{ $empresa->endereco ?? 'Preencher endereco da empresa' }}</div>
            <div class="subtitle">Contato: {{ $empresa->email ?? 'financeiro@formed.com.br' }} | WhatsApp: {{ $empresa->telefone ?? '(17) 0000-0000' }}</div>
        </td>
    </tr>
</table>

<div class="line"></div>

<table class="block" cellspacing="0" cellpadding="0">
    <tr>
        <th colspan="2">Dados do Cliente</th>
        <th colspan="2">Identificacao da Fatura</th>
    </tr>
    <tr>
        <td style="width:20%;"><strong>Razao Social</strong></td>
        <td style="width:30%;">{{ $conta->cliente->razao_social ?? '-' }}</td>
        <td style="width:20%;"><strong>Numero</strong></td>
        <td style="width:30%;">FAT-{{ str_pad((string) $conta->id, 6, '0', STR_PAD_LEFT) }}</td>
    </tr>
    <tr>
        <td><strong>CNPJ/CPF</strong></td>
        <td>{{ $conta->cliente->cnpj ?? '-' }}</td>
        <td><strong>Emissao</strong></td>
        <td>{{ optional($conta->created_at)->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td><strong>Endereco</strong></td>
        <td>{{ trim(($conta->cliente->endereco ?? '-') . ' ' . ($conta->cliente->numero ?? '')) }}</td>
        <td><strong>Vencimento</strong></td>
        <td>{{ optional($conta->vencimento)->format('d/m/Y') ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>E-mail</strong></td>
        <td>{{ $conta->cliente->email ?? '-' }}</td>
        <td><strong>Competencia</strong></td>
        <td>{{ optional(($conta->itens->first()?->data_realizacao ?? $conta->created_at))->format('m/Y') }}</td>
    </tr>
</table>

<table class="block" cellspacing="0" cellpadding="0" style="margin-top: 12px;">
    <thead>
    <tr>
        <th style="width: 20%;">Item / Servico</th>
        <th style="width: 44%;">Descricao</th>
        <th style="width: 8%;" class="num">Qtd.</th>
        <th style="width: 14%;" class="num">Valor unit.</th>
        <th style="width: 14%;" class="num">Subtotal</th>
    </tr>
    </thead>
    <tbody>
    @foreach($conta->itens as $item)
        <tr>
            <td>{{ $item->servico?->nome ?? 'Servico avulso' }}</td>
            <td>{{ $item->descricao ?? $item->vendaItem?->descricao_snapshot ?? '-' }}</td>
            <td class="num">1</td>
            <td class="num">R$ {{ number_format((float) $item->valor, 2, ',', '.') }}</td>
            <td class="num">R$ {{ number_format((float) $item->valor, 2, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="block" cellspacing="0" cellpadding="0" style="margin-top: 10px; width: 52%; margin-left: auto;">
    <tr><td><strong>Subtotal</strong></td><td class="num">R$ {{ number_format($subtotal, 2, ',', '.') }}</td></tr>
    <tr><td><strong>Descontos</strong></td><td class="num">R$ {{ number_format($descontos, 2, ',', '.') }}</td></tr>
    <tr><td><strong>Acrescimos</strong></td><td class="num">R$ {{ number_format($acrescimos, 2, ',', '.') }}</td></tr>
    <tr><td><strong>Total</strong></td><td class="num"><strong>R$ {{ number_format($total, 2, ',', '.') }}</strong></td></tr>
    <tr><td><strong>Valor pago (parcial)</strong></td><td class="num">R$ {{ number_format($pago, 2, ',', '.') }}</td></tr>
    <tr><td><strong>Saldo</strong></td><td class="num"><strong>R$ {{ number_format($saldo, 2, ',', '.') }}</strong></td></tr>
</table>

<table class="block" cellspacing="0" cellpadding="0" style="margin-top: 12px;">
    <tr><th>Secao de Pagamento</th></tr>
    <tr>
        <td>
            <strong>Meios aceitos:</strong> Pix, Boleto bancario e Transferencia.<br>
            <strong>Instrucoes:</strong> Favor identificar no comprovante o numero da fatura FAT-{{ str_pad((string) $conta->id, 6, '0', STR_PAD_LEFT) }}.<br>
            <strong>Observacoes:</strong> Pagamentos apos o vencimento podem sofrer multa e juros conforme contrato vigente.
        </td>
    </tr>
</table>

<div class="footer">
    <div style="border-top: 1px solid #d1d5db; padding-top: 5px;">
        FORMED - Cuidado com pessoas, rigor com conformidade. | financeiro@formed.com.br | WhatsApp (17) 0000-0000
        <span style="float: right;">Pagina 1/1</span>
    </div>
</div>
</body>
</html>
