@php
    $itens = $proposta->itens ?? collect();
    $printMode = !empty($printMode);
    $dataEmissao = optional($proposta->created_at ?? now())->format('d/m/Y');
    $diasValidade = max(1, (int) ($proposta->prazo_dias ?? 7));
    $dataVencimento = optional($proposta->created_at ?? now())->copy()->addDays($diasValidade)->format('d/m/Y');
    $descontoPercentual = (float) ($proposta->desconto_percentual ?? 0);
    $valorTotalLiquido = (float) ($proposta->valor_total ?? 0);
    $totaisItensComDesconto = [];
    $acumuladoItensComDesconto = 0.0;
    $ultimoIndiceItem = max(0, $itens->count() - 1);
    foreach ($itens as $indiceItem => $itemDocumento) {
        $valorLinhaOriginal = (float) ($itemDocumento->valor_total ?? 0);
        $valorLinhaComDesconto = round($valorLinhaOriginal * (1 - ($descontoPercentual / 100)), 2);
        if ($indiceItem === $ultimoIndiceItem) {
            $valorLinhaComDesconto = round($valorTotalLiquido - $acumuladoItensComDesconto, 2);
        } else {
            $acumuladoItensComDesconto += $valorLinhaComDesconto;
        }
        $totaisItensComDesconto[$indiceItem] = max(0, $valorLinhaComDesconto);
    }
    $logoPdfPath = null;
    foreach ([public_path('storage/logo.svg'), storage_path('app/public/logo.svg'), public_path('favicon.png')] as $candidateLogoPath) {
        if (is_string($candidateLogoPath) && file_exists($candidateLogoPath)) {
            $logoPdfPath = $candidateLogoPath;
            break;
        }
    }
    $logoWebUrl = asset('favicon.png');
@endphp

@if($printMode)
    <div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px; border: 1px solid #d6dfeb;">
            <tr>
                <td style="width: 100%; background: #262d7c; color: #ffffff; padding: 14px 12px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 54px; vertical-align: middle;">
                                @if($logoPdfPath)
                                    <img src="{{ $logoPdfPath }}" alt="FORMED" style="max-width: 40px; max-height: 40px;">
                                @endif
                            </td>
                            <td style="vertical-align: middle;">
                                <div style="font-size: 28px; font-weight: 800; line-height: 1;">FORMED</div>
                                <div style="font-size: 10px; margin-top: 4px; color: #d7deff;">Proposta Comercial</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr style="background: #f2f6fc;">
                <td style="padding: 0; border-top: 1px solid #d6dfeb;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 10px;">
                                <div style="font-size: 9px; text-transform: uppercase; color: #5f6f88; font-weight: 700;">Data de emissão</div>
                                <div style="font-size: 14px; font-weight: 800; color: #20304e; margin-top: 2px;">{{ $dataEmissao }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td style="width: 50%; padding-right: 4px; vertical-align: top;">
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #d6dfeb;">
                        <tr>
                            <td style="background: #eef3fb; border-bottom: 1px solid #d6dfeb; padding: 6px 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #53657f;">
                                Dados da Empresa Emissora
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 8px;">
                                <div style="font-size: 14px; font-weight: 800; color: #1f2f4c; line-height: 1.35;">{{ $proposta->empresa?->nome ?? 'FORMED' }}</div>
                                <div style="font-size: 11px; color: #2e3d57; margin-top: 7px;"><strong>CNPJ:</strong> {{ $proposta->empresa?->cnpj ?? '—' }}</div>
                                <div style="font-size: 11px; color: #2e3d57; margin-top: 3px;"><strong>Endereço:</strong> {{ $proposta->empresa?->endereco ?? '—' }}</div>
                                <div style="font-size: 11px; color: #2e3d57; margin-top: 3px;"><strong>Responsável:</strong> {{ $proposta->vendedor?->name ?? '—' }}</div>
                                <div style="font-size: 11px; color: #2e3d57; margin-top: 3px;"><strong>E-mail:</strong> {{ $proposta->vendedor?->email ?? '—' }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%; padding-left: 4px; vertical-align: top;">
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #d6dfeb;">
                        <tr>
                            <td style="background: #eef3fb; border-bottom: 1px solid #d6dfeb; padding: 6px 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #53657f;">
                                Dados do Cliente
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 8px;">
                                <div style="font-size: 14px; font-weight: 800; color: #1f2f4c; line-height: 1.35;">{{ $proposta->cliente?->razao_social ?? '—' }}</div>
                                <div style="font-size: 11px; color: #2e3d57; margin-top: 7px;"><strong>{{ $proposta->cliente?->documento_label ?? 'Documento' }}:</strong> {{ $proposta->cliente?->documento_principal ?? '—' }}</div>
                                <div style="font-size: 11px; color: #2e3d57; margin-top: 3px;"><strong>Contato:</strong> {{ $proposta->cliente?->contato ?? $proposta->cliente?->razao_social ?? '—' }}</div>
                                <div style="font-size: 11px; color: #2e3d57; margin-top: 3px;"><strong>E-mail:</strong> {{ $proposta->cliente?->email ?? '—' }}</div>
                                <div style="font-size: 11px; color: #2e3d57; margin-top: 3px;"><strong>Telefone:</strong> {{ $proposta->cliente?->telefone ?? '—' }}</div>
                                <div style="font-size: 11px; color: #2e3d57; margin-top: 3px;"><strong>Endereço:</strong> {{ $proposta->cliente?->endereco ?? '—' }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px; border: 1px solid #d6dfeb;">
            <thead>
            <tr style="background: #262d7c; color: #ffffff;">
                <th style="padding: 7px 8px; text-align: left; font-size: 9px; font-weight: 700;">SERVIÇO</th>
                <th style="padding: 7px 8px; text-align: left; font-size: 9px; font-weight: 700;">DATA</th>
                <th style="padding: 7px 8px; text-align: right; font-size: 9px; font-weight: 700;">QTDE</th>
                <th style="padding: 7px 8px; text-align: right; font-size: 9px; font-weight: 700;">VALOR UNIT</th>
                <th style="padding: 7px 8px; text-align: right; font-size: 9px; font-weight: 700;">TOTAL</th>
            </tr>
            </thead>
            <tbody>
            @foreach($itens as $index => $item)
                <tr style="background: {{ $index % 2 === 0 ? '#f9fbfe' : '#eef3f8' }};">
                    <td style="padding: 9px 8px; font-size: 11px; color: #233350; font-weight: 700;">
                        {{ $item->nome }}
                        @if($item->descricao)
                            <div style="font-size: 10px; font-weight: 400; color: #60708a; margin-top: 2px;">{{ $item->descricao }}</div>
                        @endif
                    </td>
                    <td style="padding: 9px 8px; font-size: 11px; color: #233350;">{{ $dataEmissao }}</td>
                    <td style="padding: 9px 8px; font-size: 11px; color: #233350; text-align: right;">{{ $item->quantidade }}</td>
                    <td style="padding: 9px 8px; font-size: 11px; color: #233350; text-align: right;">R$ {{ number_format((float) $item->valor_unitario, 2, ',', '.') }}</td>
                    <td style="padding: 9px 8px; font-size: 11px; color: #233350; text-align: right; font-weight: 700;">
                        R$ {{ number_format((float) ($totaisItensComDesconto[$index] ?? $item->valor_total), 2, ',', '.') }}
                        @if($descontoPercentual > 0)
                            <div style="font-size: 9px; font-weight: 400; color: #8a97ab; margin-top: 2px; text-decoration: line-through;">
                                R$ {{ number_format((float) $item->valor_total, 2, ',', '.') }}
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <table style="width: 100%; border-collapse: collapse; border: 1px solid #d6dfeb;">
            <tr>
                <td style="background: #eef3fb; border-bottom: 1px solid #d6dfeb; padding: 6px 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #53657f;">
                    Resumo Financeiro
                </td>
            </tr>
            <tr>
                <td style="padding: 0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 10px; font-size: 11px; color: #30415d;">Subtotal</td>
                            <td style="padding: 8px 10px; font-size: 11px; color: #30415d; text-align: right; font-weight: 700;">R$ {{ number_format((float) ($proposta->valor_bruto ?? 0), 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 10px; font-size: 11px; color: #30415d;">Desconto</td>
                            <td style="padding: 4px 10px; font-size: 11px; color: #30415d; text-align: right; font-weight: 700;">R$ {{ number_format((float) ($proposta->desconto_valor ?? 0), 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 10px 10px; font-size: 11px; color: #30415d;">Total líquido</td>
                            <td style="padding: 4px 10px 10px; font-size: 11px; color: #30415d; text-align: right; font-weight: 700;">R$ {{ number_format((float) ($proposta->valor_total ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="background: #262d7c; color: #ffffff; padding: 12px 10px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Total da Proposta</td>
                            <td style="font-size: 22px; font-weight: 800; text-align: right;">R$ {{ number_format((float) ($proposta->valor_total ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        @if(!empty($proposta->observacoes))
            <table style="width: 100%; border-collapse: collapse; margin-top: 8px; border: 1px solid #d6dfeb;">
                <tr>
                    <td style="background: #eef3fb; border-bottom: 1px solid #d6dfeb; padding: 6px 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #53657f;">
                        Observações
                    </td>
                </tr>
                <tr>
                    <td style="padding: 9px 8px; font-size: 11px; color: #30415d; line-height: 1.6;">{{ $proposta->observacoes }}</td>
                </tr>
            </table>
        @endif
    </div>
@else
    <div class="space-y-4">
        <div class="overflow-hidden border border-slate-300 bg-white shadow-sm">
            <div class="bg-indigo-900 px-4 py-5 text-white">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded bg-white/10">
                        <img src="{{ $logoWebUrl }}"
                             alt="FORMED"
                             class="max-h-9 w-auto"
                             onerror="this.style.display='none'; this.parentElement.classList.add('hidden');">
                    </div>
                    <div>
                        <div class="text-4xl font-black leading-none">FORMED</div>
                        <div class="mt-1 text-xs text-indigo-100">Proposta Comercial</div>
                    </div>
                </div>
            </div>
            <div class="grid gap-0 border-t border-slate-300 md:grid-cols-1">
                <div class="bg-slate-50 px-4 py-3">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Data de emissão</div>
                    <div class="mt-1 text-lg font-extrabold text-slate-900">{{ $dataEmissao }}</div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="border border-slate-300 bg-white shadow-sm">
                <div class="border-b border-slate-300 bg-slate-100 px-3 py-2 text-[10px] font-bold uppercase tracking-wide text-slate-600">Dados da Empresa Emissora</div>
                <div class="px-3 py-3">
                    <div class="text-xl font-extrabold leading-tight text-slate-900">{{ $proposta->empresa?->nome ?? 'FORMED' }}</div>
                    <div class="mt-3 text-sm text-slate-700"><strong>CNPJ:</strong> {{ $proposta->empresa?->cnpj ?? '—' }}</div>
                    <div class="mt-1 text-sm text-slate-700"><strong>Endereço:</strong> {{ $proposta->empresa?->endereco ?? '—' }}</div>
                    <div class="mt-1 text-sm text-slate-700"><strong>Responsável:</strong> {{ $proposta->vendedor?->name ?? '—' }}</div>
                    <div class="mt-1 text-sm text-slate-700"><strong>E-mail:</strong> {{ $proposta->vendedor?->email ?? '—' }}</div>
                </div>
            </div>

            <div class="border border-slate-300 bg-white shadow-sm">
                <div class="border-b border-slate-300 bg-slate-100 px-3 py-2 text-[10px] font-bold uppercase tracking-wide text-slate-600">Dados do Cliente</div>
                <div class="px-3 py-3">
                    <div class="text-xl font-extrabold leading-tight text-slate-900">{{ $proposta->cliente?->razao_social ?? '—' }}</div>
                    <div class="mt-3 text-sm text-slate-700"><strong>{{ $proposta->cliente?->documento_label ?? 'Documento' }}:</strong> {{ $proposta->cliente?->documento_principal ?? '—' }}</div>
                    <div class="mt-1 text-sm text-slate-700"><strong>Contato:</strong> {{ $proposta->cliente?->contato ?? $proposta->cliente?->razao_social ?? '—' }}</div>
                    <div class="mt-1 text-sm text-slate-700"><strong>E-mail:</strong> {{ $proposta->cliente?->email ?? '—' }}</div>
                    <div class="mt-1 text-sm text-slate-700"><strong>Telefone:</strong> {{ $proposta->cliente?->telefone ?? '—' }}</div>
                    <div class="mt-1 text-sm text-slate-700"><strong>Endereço:</strong> {{ $proposta->cliente?->endereco ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden border border-slate-300 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-indigo-900 text-white">
                <tr>
                    <th class="px-3 py-2 text-left text-[10px] font-bold uppercase tracking-wide">Serviço</th>
                    <th class="px-3 py-2 text-left text-[10px] font-bold uppercase tracking-wide">Data</th>
                    <th class="px-3 py-2 text-right text-[10px] font-bold uppercase tracking-wide">Qtde</th>
                    <th class="px-3 py-2 text-right text-[10px] font-bold uppercase tracking-wide">Valor Unit</th>
                    <th class="px-3 py-2 text-right text-[10px] font-bold uppercase tracking-wide">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($itens as $index => $item)
                    <tr class="{{ $index % 2 === 0 ? 'bg-slate-50' : 'bg-slate-100/80' }}">
                        <td class="px-3 py-3 align-top text-sm font-bold text-slate-900">
                            {{ $item->nome }}
                            @if($item->descricao)
                                <div class="mt-1 text-xs font-normal text-slate-500">{{ $item->descricao }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-3 align-top text-sm text-slate-700">{{ $dataEmissao }}</td>
                        <td class="px-3 py-3 align-top text-right text-sm text-slate-700">{{ $item->quantidade }}</td>
                        <td class="px-3 py-3 align-top text-right text-sm text-slate-700">R$ {{ number_format((float) $item->valor_unitario, 2, ',', '.') }}</td>
                        <td class="px-3 py-3 align-top text-right text-sm font-bold text-slate-900">
                            R$ {{ number_format((float) ($totaisItensComDesconto[$index] ?? $item->valor_total), 2, ',', '.') }}
                            @if($descontoPercentual > 0)
                                <div class="mt-1 text-[11px] font-normal text-slate-400 line-through">R$ {{ number_format((float) $item->valor_total, 2, ',', '.') }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="overflow-hidden border border-slate-300 bg-white shadow-sm">
            <div class="border-b border-slate-300 bg-slate-100 px-3 py-2 text-[10px] font-bold uppercase tracking-wide text-slate-600">Resumo Financeiro</div>
            <div class="px-3 py-3">
                <div class="flex items-center justify-between py-1 text-sm text-slate-700">
                    <span>Subtotal</span>
                    <strong>R$ {{ number_format((float) ($proposta->valor_bruto ?? 0), 2, ',', '.') }}</strong>
                </div>
                <div class="flex items-center justify-between py-1 text-sm text-slate-700">
                    <span>Desconto</span>
                    <strong>R$ {{ number_format((float) ($proposta->desconto_valor ?? 0), 2, ',', '.') }}</strong>
                </div>
                <div class="flex items-center justify-between py-1 text-sm text-slate-700">
                    <span>Total líquido</span>
                    <strong>R$ {{ number_format((float) ($proposta->valor_total ?? 0), 2, ',', '.') }}</strong>
                </div>
            </div>
            <div class="flex items-center justify-between bg-indigo-900 px-4 py-4 text-white">
                <span class="text-sm font-bold uppercase tracking-wide">Total da Proposta</span>
                <strong class="text-3xl font-black">R$ {{ number_format((float) ($proposta->valor_total ?? 0), 2, ',', '.') }}</strong>
            </div>
        </div>

        @if(!empty($proposta->observacoes))
            <div class="border border-slate-300 bg-white shadow-sm">
                <div class="border-b border-slate-300 bg-slate-100 px-3 py-2 text-[10px] font-bold uppercase tracking-wide text-slate-600">Observações</div>
                <div class="px-3 py-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $proposta->observacoes }}</div>
            </div>
        @endif
    </div>
@endif
