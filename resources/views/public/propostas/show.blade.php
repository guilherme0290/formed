<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proposta FORMED</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        :root {
            --formed-ink: #0f172a;
            --formed-muted: #64748b;
            --formed-emerald: #0f766e;
            --formed-emerald-dark: #115e59;
            --formed-emerald-light: #99f6e4;
            --formed-sun: #f59e0b;
            --formed-bg: #f8fafc;
            --formed-card: #ffffff;
            --formed-border: #e2e8f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: var(--formed-ink);
            background: radial-gradient(circle at top left, rgba(16, 185, 129, 0.12), transparent 45%),
                        radial-gradient(circle at bottom right, rgba(14, 116, 144, 0.12), transparent 45%),
                        var(--formed-bg);
            min-height: 100vh;
        }
        .shell {
            max-width: 980px;
            margin: 0 auto;
            padding: 32px 20px 60px;
        }
        .hero {
            background: linear-gradient(120deg, #0f766e, #14b8a6);
            border-radius: 24px;
            color: #fff;
            padding: 28px 28px 22px;
            box-shadow: 0 20px 40px rgba(15, 118, 110, 0.18);
            position: relative;
            overflow: hidden;
        }
        .hero::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            right: -120px;
            top: -120px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.4), transparent 60%);
        }
        .hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }
        .logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        .logo-mark {
            height: 48px;
            width: 48px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.18);
            display: grid;
            place-items: center;
            overflow: hidden;
        }
        .logo-mark img {
            height: 30px;
            width: auto;
        }
        .logo-text {
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-weight: 700;
        }
        .status-pill {
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: rgba(15, 23, 42, 0.2);
        }
        .hero-title {
            margin: 18px 0 4px;
            font-size: 24px;
            font-weight: 800;
        }
        .hero-subtitle {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .card {
            background: var(--formed-card);
            border-radius: 20px;
            border: 1px solid var(--formed-border);
            padding: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }
        .grid {
            display: grid;
            gap: 16px;
        }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: var(--formed-muted);
            font-weight: 700;
        }
        .value {
            font-size: 16px;
            font-weight: 700;
            margin-top: 6px;
        }
        .muted {
            color: var(--formed-muted);
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 8px;
            border-bottom: 1px solid var(--formed-border);
            text-align: left;
            font-size: 14px;
        }
        th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--formed-muted);
        }
        .total-box {
            background: linear-gradient(120deg, rgba(16, 185, 129, 0.16), rgba(14, 116, 144, 0.08));
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 18px;
            padding: 18px;
        }
        .total-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--formed-emerald-dark);
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .btn {
            border: none;
            padding: 12px 18px;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-accept {
            background: var(--formed-emerald);
            color: #fff;
        }
        .btn-decline {
            background: #fff;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .alert {
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 14px;
        }
        .alert-ok {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .alert-err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: var(--formed-muted);
        }
        @media (max-width: 640px) {
            .hero-title { font-size: 20px; }
            .total-value { font-size: 22px; }
        }
    </style>
</head>
<body>
@php
    $status = strtoupper((string) ($proposta->status ?? 'PENDENTE'));
    $hasEsocialItem = $proposta->itens->contains(fn ($it) => strtoupper((string) ($it->tipo ?? '')) === 'ESOCIAL');
    $statusLabel = str_replace('_', ' ', $status);
    $podeResponder = !in_array($status, ['FECHADA', 'CANCELADA'], true);
    $showSuccess = session('ok');
@endphp

@if($showSuccess)
    <style>
        .success-wrap {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 20px 60px;
        }
        .success-card {
            background: linear-gradient(140deg, #ffffff, #f0fdf4);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 28px;
            padding: 36px 32px;
            max-width: 680px;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 118, 110, 0.18);
        }
        .success-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.12);
            color: #065f46;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .success-title {
            margin: 18px 0 8px;
            font-size: 28px;
            font-weight: 800;
            color: #0f766e;
        }
        .success-subtitle {
            margin: 0 auto;
            font-size: 15px;
            color: #334155;
            max-width: 520px;
            line-height: 1.6;
        }
        .success-actions {
            margin-top: 26px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .success-btn {
            border-radius: 14px;
            padding: 12px 20px;
            font-weight: 700;
            font-size: 14px;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .success-btn.primary {
            background: #0f766e;
            color: #fff;
            border-color: #0f766e;
        }
        .success-btn.ghost {
            background: #fff;
            color: #0f172a;
            border-color: #e2e8f0;
        }
        .confetti-canvas {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .confetti {
            position: absolute;
            width: 10px;
            height: 14px;
            opacity: 0.9;
            animation: confetti-fall linear forwards;
        }
        @keyframes confetti-fall {
            0% {
                transform: translateY(-20vh) rotate(0deg);
            }
            100% {
                transform: translateY(120vh) rotate(360deg);
            }
        }
        @media (max-width: 640px) {
            .success-title { font-size: 22px; }
        }
    </style>

    <div class="success-wrap">
        <div class="success-card">
            <div class="confetti-canvas" id="confettiCanvas"></div>
            <div class="success-badge">Proposta aceita</div>
            <h1 class="success-title">Bem-vindo à FORMED</h1>
            <p class="success-subtitle">
                Obrigado por confiar na nossa equipe. Sua proposta foi confirmada e estamos prontos para iniciar
                os próximos passos com agilidade e cuidado.
            </p>
            <div class="success-actions">
                <a class="success-btn primary" href="{{ route('propostas.public.show', $proposta->public_token) }}">Ver proposta</a>
                <button class="success-btn ghost" type="button" onclick="window.close()">Fechar janela</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const canvas = document.getElementById('confettiCanvas');
            if (!canvas) return;
            const colors = ['#0f766e', '#10b981', '#22d3ee', '#f59e0b', '#f97316', '#38bdf8'];
            const total = 70;
            for (let i = 0; i < total; i++) {
                const piece = document.createElement('div');
                piece.className = 'confetti';
                piece.style.left = Math.random() * 100 + '%';
                piece.style.background = colors[i % colors.length];
                piece.style.opacity = String(0.6 + Math.random() * 0.4);
                piece.style.transform = `translateY(-20vh) rotate(${Math.random() * 360}deg)`;
                piece.style.animationDuration = `${2.8 + Math.random() * 2.2}s`;
                piece.style.animationDelay = `${Math.random() * 0.4}s`;
                piece.style.borderRadius = Math.random() > 0.6 ? '50%' : '2px';
                piece.style.width = `${8 + Math.random() * 8}px`;
                piece.style.height = `${10 + Math.random() * 12}px`;
                canvas.appendChild(piece);
            }
        })();
    </script>
@else
<div class="shell">
    <section class="hero">
        <div class="hero-top">
            <div class="logo">
                <div class="logo-mark">
                    <img src="{{ asset('storage/logo.svg') }}" alt="Formed">
                </div>
                <div>
                    <div class="logo-text">Formed</div>
                    <div class="muted" style="color: rgba(255,255,255,0.8); font-size: 12px;">Proposta Comercial</div>
                </div>
            </div>
            <div class="status-pill">{{ $statusLabel }}</div>
        </div>
        <h1 class="hero-title">{{ str_pad((int) $proposta->id, 2, '0', STR_PAD_LEFT) }}</h1>
        <p class="hero-subtitle">Criada em {{ optional($proposta->created_at)->format('d/m/Y') ?? '—' }}</p>
    </section>

    <div style="height: 20px;"></div>

    @if (session('ok'))
        <div class="alert alert-ok">{{ session('ok') }}</div>
    @endif
    @if (session('erro'))
        <div class="alert alert-err">{{ session('erro') }}</div>
    @endif

    <div style="height: 16px;"></div>

    <section class="grid grid-3">
        <div class="card">
            <div class="label">Cliente</div>
            <div class="value">{{ $proposta->cliente->razao_social ?? '—' }}</div>
            <div class="muted">{{ $proposta->cliente->email ?? 'E-mail não informado' }}</div>
        </div>
        <div class="card">
            <div class="label">Forma de pagamento</div>
            <div class="value">{{ $proposta->forma_pagamento ?? '—' }}</div>
            <div class="muted">Itens: {{ $proposta->itens->count() + (!$hasEsocialItem && $proposta->incluir_esocial ? 1 : 0) }}</div>
        </div>
        <div class="total-box">
            <div class="label">Valor total</div>
            <div class="total-value">R$ {{ number_format($proposta->valor_total, 2, ',', '.') }}</div>
            <div class="muted">{{ $proposta->incluir_esocial ? 'Inclui eSocial' : 'Sem eSocial' }}</div>
        </div>
    </section>

    <div style="height: 16px;"></div>

    <section class="grid grid-2">
        <div class="card">
            <div class="label">Contratada</div>
            <div class="value">{{ $proposta->empresa->nome_fantasia ?? 'FORMED' }}</div>
            <div class="muted">{{ $proposta->empresa->cnpj ?? '' }}</div>
        </div>
        <div class="card">
            <div class="label">Vendedor</div>
            <div class="value">{{ $proposta->vendedor->name ?? '—' }}</div>
            <div class="muted">{{ $proposta->vendedor->email ?? 'Contato via FORMED' }}</div>
        </div>
    </section>

    <div style="height: 16px;"></div>

    <section class="card">
        <div class="label">Itens da proposta</div>
        <div style="height: 10px;"></div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                <tr>
                    <th>Serviço</th>
                    <th>Prazo</th>
                    <th style="text-align:right;">Qtd</th>
                    <th style="text-align:right;">Valor unit.</th>
                    <th style="text-align:right;">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($proposta->itens as $item)
                    <tr>
                        <td>
                            <div style="font-weight: 600;">{{ $item->nome }}</div>
                            @if($item->descricao)
                                <div class="muted">{{ $item->descricao }}</div>
                            @endif
                        </td>
                        <td class="muted">{{ $item->prazo ?? '—' }}</td>
                        <td style="text-align:right;">{{ $item->quantidade }}</td>
                        <td style="text-align:right;">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td style="text-align:right; font-weight: 700;">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
{{--                @if(!empty($gheSnapshot['ghes']))--}}
{{--                    @foreach($gheSnapshot['ghes'] as $ghe)--}}
{{--                        @php--}}
{{--                            $totais = $ghe['total_por_tipo'] ?? [];--}}
{{--                        @endphp--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <div style="font-weight: 600;">GHE - {{ $ghe['nome'] ?? '—' }}</div>--}}
{{--                                <div class="muted">{{ $ghe['protocolo']['titulo'] ?? 'Sem protocolo' }}</div>--}}
{{--                                <div class="muted">--}}
{{--                                    Admissional: R$ {{ number_format((float) ($totais['admissional'] ?? 0), 2, ',', '.') }} ·--}}
{{--                                    Periodico: R$ {{ number_format((float) ($totais['periodico'] ?? 0), 2, ',', '.') }} ·--}}
{{--                                    Demissional: R$ {{ number_format((float) ($totais['demissional'] ?? 0), 2, ',', '.') }}--}}
{{--                                </div>--}}
{{--                                <div class="muted">--}}
{{--                                    Mudanca: R$ {{ number_format((float) ($totais['mudanca_funcao'] ?? 0), 2, ',', '.') }} ·--}}
{{--                                    Retorno: R$ {{ number_format((float) ($totais['retorno_trabalho'] ?? 0), 2, ',', '.') }}--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                            <td class="muted">—</td>--}}
{{--                            <td style="text-align:right;">1</td>--}}
{{--                            <td style="text-align:right;">R$ {{ number_format((float) ($ghe['total_exames'] ?? 0), 2, ',', '.') }}</td>--}}
{{--                            <td style="text-align:right; font-weight: 700;">R$ {{ number_format((float) ($ghe['total_exames'] ?? 0), 2, ',', '.') }}</td>--}}
{{--                        </tr>--}}
{{--                    @endforeach--}}
{{--                @endif--}}
                </tbody>
            </table>
        </div>
    </section>

    <div style="height: 18px;"></div>

    <section class="card">
        <div class="label">Decisão do cliente</div>
        <div style="height: 10px;"></div>
        @if(!$podeResponder)
            <p class="muted">Esta proposta já foi encerrada.</p>
        @else
            <div class="actions">
                <form method="POST" action="{{ route('propostas.public.responder', $proposta->public_token) }}">
                    @csrf
                    <input type="hidden" name="acao" value="aceitar">
                    <button class="btn btn-accept" type="submit">Aceitar proposta</button>
                </form>
                <form method="POST" action="{{ route('propostas.public.responder', $proposta->public_token) }}">
                    @csrf
                    <input type="hidden" name="acao" value="recusar">
                    <button class="btn btn-decline" type="submit">Recusar proposta</button>
                </form>
            </div>
        @endif
    </section>

    <div class="footer">
        FORMED • Saúde e Segurança do Trabalho
    </div>
</div>
@endif
</body>
</html>
