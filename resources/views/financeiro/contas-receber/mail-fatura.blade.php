@php
    $clienteNome = $conta->cliente->razao_social ?? $conta->cliente->nome_fantasia ?? 'Cliente';
@endphp
<div style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.45;">
    <h2 style="margin:0 0 12px; color:#1d4ed8;">Fatura #{{ $conta->id }}</h2>
    <p style="margin:0 0 10px;">Olá,</p>
    <p style="margin:0 0 14px;">
        Segue em anexo a fatura <strong>#{{ $conta->id }}</strong> do cliente <strong>{{ $clienteNome }}</strong>.
    </p>

    <table cellpadding="0" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 520px; margin-bottom: 14px;">
        <tr>
            <td style="padding: 8px 10px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:600;">Emissão</td>
            <td style="padding: 8px 10px; border:1px solid #e2e8f0;">{{ optional($conta->created_at)->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 10px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:600;">Vencimento</td>
            <td style="padding: 8px 10px; border:1px solid #e2e8f0;">{{ optional($conta->vencimento)->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 10px; border:1px solid #e2e8f0; background:#f8fafc; font-weight:600;">Saldo</td>
            <td style="padding: 8px 10px; border:1px solid #e2e8f0; color:#1d4ed8; font-weight:700;">R$ {{ number_format((float) $conta->total_aberto, 2, ',', '.') }}</td>
        </tr>
    </table>

    <p style="margin:0 0 10px; color:#475569;">Em caso de dúvidas, responda este e-mail.</p>
    <p style="margin:0; color:#64748b; font-size:12px;">Mensagem automática do financeiro.</p>
</div>

