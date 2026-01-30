<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 6px 0; }
        .sub { color: #6b7280; font-size: 10px; margin-bottom: 8px; }
        .summary { margin: 8px 0 12px 0; }
        .summary span { margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; }
        th { background: #f3f4f6; text-align: left; font-weight: 600; }
        td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>Detalhamento de Faturamento</h1>
    <div class="sub">Itens recebidos e pendentes por cliente e serviço.</div>
    <div class="summary">
        <span>Período: {{ \Carbon\Carbon::parse($data_inicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($data_fim)->format('d/m/Y') }}</span>
        <span>Cliente: {{ $cliente_selecionado_label ?? 'Todos os clientes' }}</span>
        <span>Status: {{ $status_selecionado_label ?? 'Todos' }}</span>
    </div>
    <div class="summary">
        <span>Recebido: R$ {{ number_format($total_recebido ?? 0, 2, ',', '.') }}</span>
        <span>Pendente: R$ {{ number_format($total_pendente ?? 0, 2, ',', '.') }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Serviço</th>
                <th>Descrição</th>
                <th>Data</th>
                <th class="num">Valor</th>
                <th class="num">Recebido</th>
                <th class="num">Pendente</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($itens as $item)
                @php
                    $valor = (float) ($item->valor ?? 0);
                    $recebido = (float) ($item->total_baixado ?? 0);
                    $pendente = max($valor - $recebido, 0);
                    $status = $pendente <= 0 ? 'Recebido' : 'Pendente';
                    $dataRef = $item->data_realizacao ?? $item->vencimento ?? $item->created_at;
                @endphp
                <tr>
                    <td>{{ $item->cliente->razao_social ?? 'Cliente' }}</td>
                    <td>{{ $item->servico->nome ?? 'Serviço' }}</td>
                    <td>{{ $item->descricao ?? '-' }}</td>
                    <td>{{ $dataRef ? \Carbon\Carbon::parse($dataRef)->format('d/m/Y') : '-' }}</td>
                    <td class="num">R$ {{ number_format($valor, 2, ',', '.') }}</td>
                    <td class="num">R$ {{ number_format($recebido, 2, ',', '.') }}</td>
                    <td class="num">R$ {{ number_format($pendente, 2, ',', '.') }}</td>
                    <td>{{ $status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
