<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Proposta Comercial</title>
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
        .right { text-align: right; }
        .total { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 12px; }
        .footer { margin-top: 18px; font-size: 10px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    @php
        $status = strtoupper((string) ($proposta->status ?? 'PENDENTE'));
        $hasEsocialItem = $proposta->itens->contains(fn ($it) => strtoupper((string) ($it->tipo ?? '')) === 'ESOCIAL');
    @endphp

    <div class="header">
        <div class="brand">
            @if($logoData)
                <img src="{{ $logoData }}" alt="Formed" style="height: 56px;">
            @endif
        </div>
        <div class="title">
            <h1>Proposta Comercial</h1>
            <p>{{ str_pad((int) $proposta->id, 2, '0', STR_PAD_LEFT) }} — {{ optional($proposta->created_at)->format('d/m/Y') ?? '—' }}</p>
            <span class="badge">{{ str_replace('_', ' ', $status) }}</span>
        </div>
    </div>

    <div class="section">
        <table class="grid">
            <tr>
                <td style="width: 50%; padding-right: 8px;">
                    <div class="box">
                        <div class="muted">Contratada</div>
                        <div class="value">{{ $empresa->nome ?? $empresa->nome_fantasia ?? 'FORMED' }}</div>
                        <div>{{ $empresa->cnpj ?? '' }}</div>
                    </div>
                </td>
                <td style="width: 50%; padding-left: 8px;">
                    <div class="box">
                        <div class="muted">Cliente final</div>
                        <div class="value">{{ $proposta->cliente->razao_social ?? '-' }}</div>
                        <div>{{ $proposta->cliente->cnpj ?? '' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="muted">Itens da proposta</div>
        <table>
            <thead>
            <tr>
                <th>Serviço</th>
                <th>Prazo</th>
                <th class="right">Qtd</th>
                <th class="right">Valor unit.</th>
                <th class="right">Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($proposta->itens as $item)
                <tr>
                    <td>
                        <div style="font-weight: bold;">{{ $item->nome }}</div>
                        @if($item->descricao)
                            <div style="color: #6b7280; font-size: 10px;">{{ $item->descricao }}</div>
                        @endif
                    </td>
                    <td>{{ $item->prazo ?? '—' }}</td>
                    <td class="right">{{ $item->quantidade }}</td>
                    <td class="right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
{{--            @if(!empty($gheSnapshot['ghes']))--}}
{{--                @foreach($gheSnapshot['ghes'] as $ghe)--}}
{{--                    @php--}}
{{--                        $totais = $ghe['total_por_tipo'] ?? [];--}}
{{--                    @endphp--}}
{{--                    <tr>--}}
{{--                        <td>--}}
{{--                            <div style="font-weight: bold;">GHE - {{ $ghe['nome'] ?? '—' }}</div>--}}
{{--                            <div style="color: #6b7280; font-size: 10px;">{{ $ghe['protocolo']['titulo'] ?? 'Sem protocolo' }}</div>--}}
{{--                            <div style="color: #6b7280; font-size: 10px;">--}}
{{--                                Admissional: R$ {{ number_format((float) ($totais['admissional'] ?? 0), 2, ',', '.') }} ·--}}
{{--                                Periódico: R$ {{ number_format((float) ($totais['periodico'] ?? 0), 2, ',', '.') }} ·--}}
{{--                                Demissional: R$ {{ number_format((float) ($totais['demissional'] ?? 0), 2, ',', '.') }}--}}
{{--                            </div>--}}
{{--                            <div style="color: #6b7280; font-size: 10px;">--}}
{{--                                Mudança: R$ {{ number_format((float) ($totais['mudanca_funcao'] ?? 0), 2, ',', '.') }} ·--}}
{{--                                Retorno: R$ {{ number_format((float) ($totais['retorno_trabalho'] ?? 0), 2, ',', '.') }}--}}
{{--                            </div>--}}
{{--                        </td>--}}
{{--                        <td>—</td>--}}
{{--                        <td class="right">1</td>--}}
{{--                        <td class="right">R$ {{ number_format((float) ($ghe['total_exames'] ?? 0), 2, ',', '.') }}</td>--}}
{{--                        <td class="right">R$ {{ number_format((float) ($ghe['total_exames'] ?? 0), 2, ',', '.') }}</td>--}}
{{--                    </tr>--}}
{{--                @endforeach--}}
{{--            @endif--}}
            </tbody>
        </table>
    </div>

    @if($proposta->incluir_esocial)
        <div class="section box" style="border-color: #fcd34d; background: #fffbeb;">
            <div class="muted">eSocial (mensal)</div>
            <div class="value">
                {{ $proposta->esocial_qtd_funcionarios }} colaboradores — R$
                {{ number_format($proposta->esocial_valor_mensal, 2, ',', '.') }}/mês
            </div>
        </div>
    @endif

    <div class="section">
        <table class="grid">
            <tr>
                <td style="width: 55%; padding-right: 8px;">
                    <div class="box">
                        <div class="muted">Forma de pagamento</div>
                        <div class="value">{{ $proposta->forma_pagamento ?? '—' }}</div>
                        <div style="margin-top: 6px; color: #6b7280;">Itens: {{ $proposta->itens->count() + (!$hasEsocialItem && $proposta->incluir_esocial ? 1 : 0) }}</div>
                        <div style="margin-top: 6px; color: #6b7280;">Data de vencimento: {{ $proposta->vencimento_servicos ?? '-' }}</div>
                        <div style="margin-top: 6px; color: #6b7280;">Prazo da proposta: {{ $proposta->prazo_dias ?? 7 }} dias</div>
                    </div>
                </td>
                <td style="width: 45%; padding-left: 8px;">
                    <div class="total">
                        <div class="muted">Valor total</div>
                        <div style="font-size: 20px; font-weight: bold; color: #065f46; margin-top: 6px;">
                            R$ {{ number_format($proposta->valor_total, 2, ',', '.') }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if($proposta->unidades->count())
        <div class="section">
            <div class="muted">Cl&iacute;nicas credenciadas</div>
            <table>
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Endereço</th>
                </tr>
                </thead>
                <tbody>
                @foreach($proposta->unidades as $unidade)
                    <tr>
                        <td>{{ $unidade->nome }}</td>
                        <td>{{ $unidade->endereco }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        Proposta gerada em {{ now()->format('d/m/Y H:i') }} · Formed
    </div>
</body>
</html>
