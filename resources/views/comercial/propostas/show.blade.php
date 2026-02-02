@extends('layouts.comercial')
@section('title', 'Proposta Comercial')

@section('content')
    @php
        $status = strtoupper((string) ($proposta->status ?? 'PENDENTE'));
        $hasEsocialItem = $proposta->itens->contains(fn ($it) => strtoupper((string) ($it->tipo ?? '')) === 'ESOCIAL');
        $asoServicoId = (int) (config('services.aso_id') ?? 0);
        $hasAsoItem = $proposta->itens->contains(function ($it) use ($asoServicoId) {
            if ($asoServicoId && (int) ($it->servico_id ?? 0) === $asoServicoId) {
                return true;
            }

            $tipo = strtoupper((string) ($it->tipo ?? ''));
            $nome = strtoupper((string) ($it->nome ?? ''));

            return $tipo === 'ASO' || str_contains($nome, 'ASO');
        });
        $propostaSequencial = str_pad((int) $proposta->id, 2, '0', STR_PAD_LEFT);
        $statusBadge = match ($status) {
            'PENDENTE' => 'bg-amber-50 text-amber-800 border-amber-200',
            'ENVIADA' => 'bg-blue-50 text-blue-700 border-blue-200',
            'FECHADA' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'CANCELADA' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    @endphp

    <div class="max-w-6xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div>
            @if($canEdit)
                <a href="{{ route('comercial.propostas.edit', $proposta) }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                    ← Voltar
                </a>
            @else
                <a href="{{ route('comercial.propostas.index') }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                    ← Voltar para propostas
                </a>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 bg-gradient-to-r from-emerald-700 via-emerald-600 to-emerald-500 text-white">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-2xl bg-white/15 flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('storage/logo.svg') }}" alt="Formed" class="h-9 w-auto">
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-[0.2em] text-emerald-100">Proposta Comercial</div>
                            <h1 class="text-xl font-semibold tracking-tight">{{ $propostaSequencial }}</h1>
                            <p class="text-xs text-emerald-100 mt-1">
                                Criada em {{ optional($proposta->created_at)->format('d/m/Y') ?? '—' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $statusBadge }}">
                            {{ str_replace('_', ' ', $status) }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-900/40 text-emerald-50">
                            Vendedor: {{ $proposta->vendedor->name ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                @if (session('ok'))
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                        {{ session('ok') }}
                    </div>
                @endif
                @if (session('erro'))
                    <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
                        {{ session('erro') }}
                    </div>
                @endif
                @if(!$canEdit)
                    @php
                        $solicitEmail = $proposta->vendedor->email ?? '';
                        $solicitSubject = rawurlencode('Solicitação de acesso à proposta #' . $propostaSequencial);
                        $solicitBody = rawurlencode('Olá, poderia liberar o acesso para eu editar esta proposta? Obrigado.');
                        $mailTo = $solicitEmail ? "mailto:{$solicitEmail}?subject={$solicitSubject}&body={$solicitBody}" : null;
                    @endphp
                    <div class="rounded-2xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            Já existe uma proposta para este cliente. Solicite ao responsável ou ao Master para editar.
                        </div>
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="text-xs font-semibold text-slate-500 uppercase mb-2">Contratada</div>
                        <div class="text-sm font-semibold text-slate-900">
                            {{ $empresa->nome ?? $empresa->nome_fantasia ?? 'FORMED' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $empresa->cnpj ?? '' }}
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4 bg-white">
                        <div class="text-xs font-semibold text-slate-500 uppercase mb-2">Cliente final</div>
                        <div class="text-sm font-semibold text-slate-900">
                            {{ $proposta->cliente->razao_social ?? '-' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $proposta->cliente->cnpj ?? '' }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="text-xs font-semibold text-slate-500 uppercase">Vendedor</div>
                        <div class="text-sm font-semibold text-slate-900 mt-1">
                            {{ $proposta->vendedor->name ?? '-' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $proposta->vendedor->email ?? '' }}
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="text-xs font-semibold text-slate-500 uppercase">Forma de pagamento</div>
                        <div class="text-sm font-semibold text-slate-900 mt-1">
                            {{ $proposta->forma_pagamento ?? '—' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            Itens: {{ $proposta->itens->count() + (!$hasEsocialItem && $proposta->incluir_esocial ? 1 : 0) }}
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="text-xs font-semibold text-slate-500 uppercase">Dia de vencimento</div>
                        <div class="text-sm font-semibold text-slate-900 mt-1">
                            {{ $proposta->vencimento_servicos ?? '-'  }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            Prazo da proposta: {{ $proposta->prazo_dias ?? 7 }} dias
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                        <h2 class="text-sm font-semibold text-slate-700">Itens da proposta</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white">
                            <tr class="text-left text-slate-500">
                                <th class="px-4 py-2 font-semibold">Serviço</th>
                                <th class="px-4 py-2 font-semibold">Prazo</th>
                                <th class="px-4 py-2 font-semibold text-right">Qtd</th>
                                <th class="px-4 py-2 font-semibold text-right">Valor unit.</th>
                                <th class="px-4 py-2 font-semibold text-right">Total</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                            @foreach($proposta->itens as $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800">{{ $item->nome }}</div>
                                        @if($item->descricao)
                                            <div class="text-xs text-slate-500">{{ $item->descricao }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $item->prazo ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ $item->quantidade }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">
                                        R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                        R$ {{ number_format($item->valor_total, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
{{--                            @if(!empty($gheSnapshot['ghes']))--}}
{{--                                @foreach($gheSnapshot['ghes'] as $ghe)--}}
{{--                                    @php--}}
{{--                                        $totais = $ghe['total_por_tipo'] ?? [];--}}
{{--                                    @endphp--}}
{{--                                    <tr class="bg-amber-50/40">--}}
{{--                                        <td class="px-4 py-3">--}}
{{--                                            <div class="font-medium text-slate-800">GHE - {{ $ghe['nome'] ?? '—' }}</div>--}}
{{--                                            <div class="text-xs text-slate-500">--}}
{{--                                                {{ $ghe['protocolo']['titulo'] ?? 'Sem protocolo' }}--}}
{{--                                            </div>--}}
{{--                                            <div class="text-xs text-slate-500 mt-1">--}}
{{--                                                Admissional: R$ {{ number_format((float) ($totais['admissional'] ?? 0), 2, ',', '.') }}--}}
{{--                                                • Periódico: R$ {{ number_format((float) ($totais['periodico'] ?? 0), 2, ',', '.') }}--}}
{{--                                                • Demissional: R$ {{ number_format((float) ($totais['demissional'] ?? 0), 2, ',', '.') }}--}}
{{--                                            </div>--}}
{{--                                            <div class="text-xs text-slate-500">--}}
{{--                                                Mudança: R$ {{ number_format((float) ($totais['mudanca_funcao'] ?? 0), 2, ',', '.') }}--}}
{{--                                                • Retorno: R$ {{ number_format((float) ($totais['retorno_trabalho'] ?? 0), 2, ',', '.') }}--}}
{{--                                            </div>--}}
{{--                                        </td>--}}
{{--                                        <td class="px-4 py-3 text-slate-600">—</td>--}}
{{--                                        <td class="px-4 py-3 text-right text-slate-700">1</td>--}}
{{--                                        <td class="px-4 py-3 text-right text-slate-700">--}}
{{--                                            R$ {{ number_format((float) ($ghe['total_exames'] ?? 0), 2, ',', '.') }}--}}
{{--                                        </td>--}}
{{--                                        <td class="px-4 py-3 text-right font-semibold text-slate-800">--}}
{{--                                            R$ {{ number_format((float) ($ghe['total_exames'] ?? 0), 2, ',', '.') }}--}}
{{--                                        </td>--}}
{{--                                    </tr>--}}
{{--                                @endforeach--}}
{{--                            @endif--}}
                            </tbody>
                        </table>
                    </div>
                </div>

                @php
                    $itensEsocial = $proposta->itens->filter(function ($it) {
                        $tipo = strtoupper((string) ($it->tipo ?? ''));
                        $nome = strtoupper((string) ($it->nome ?? ''));
                        return $tipo === 'ESOCIAL' || str_contains($nome, 'ESOCIAL');
                    });
                    $esocialValor = (float) $itensEsocial->sum('valor_total');
                    if ($proposta->incluir_esocial && $esocialValor <= 0) {
                        $esocialValor = (float) ($proposta->esocial_valor_mensal ?? 0);
                    }
                    $subtotalItens = (float) $proposta->itens
                        ->reject(fn ($it) => $itensEsocial->contains('id', $it->id))
                        ->sum('valor_total');
                @endphp
                <div class="border-t border-slate-200 pt-3">
                    <div class="ml-auto w-full md:w-[420px] rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
                        <div class="grid grid-cols-2 gap-1 text-xs text-slate-900">
                            <span class="uppercase tracking-wide">Subtotal</span>
                            <span class="text-right font-semibold text-slate-900">
                                R$ {{ number_format($subtotalItens, 2, ',', '.') }}
                            </span>
                            @if($proposta->incluir_esocial)
                                <span class="uppercase tracking-wide">eSocial</span>
                                <span class="text-right font-semibold text-slate-900">
                                    R$ {{ number_format($esocialValor, 2, ',', '.') }}
                                </span>
                            @endif
                        </div>
                        <div class="mt-2 flex items-center justify-between border-t border-emerald-200/60 pt-2">
                            <span class="text-xs font-semibold uppercase text-slate-900">Valor total</span>
                            <span class="text-lg font-semibold text-slate-900">
                                R$ {{ number_format($proposta->valor_total, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                @if($hasAsoItem && $proposta->asoGrupos->count())
                    @php
                        $examesAso = $proposta->asoGrupos
                            ->flatMap(fn ($grupoRow) => $grupoRow->grupo?->itens?->map(fn ($it) => $it->exame)->filter() ?? collect())
                            ->unique('id')
                            ->values();
                    @endphp
                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                            <h2 class="text-sm font-semibold text-slate-700">Exames vinculados ao ASO</h2>
                        </div>
                        <div class="p-4 space-y-4">
                            @if($examesAso->count())
                                <ul class="text-sm text-slate-700 space-y-1">
                                    @foreach($examesAso as $exame)
                                        <li>{{ $exame->titulo ?? 'Exame' }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-xs text-slate-500">Nenhum exame vinculado.</div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($proposta->incluir_esocial)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <div class="text-xs font-semibold text-slate-900 uppercase">eSocial (mensal)</div>
                        <p class="text-sm text-slate-900 mt-1">
                            {{ $proposta->esocial_qtd_funcionarios }} colaboradores — R$
                            {{ number_format($proposta->esocial_valor_mensal, 2, ',', '.') }}/mês
                        </p>
                    </div>
                @endif

                @if($unidades->count())
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="text-xs font-semibold text-slate-900 uppercase mb-2">Cl&iacute;nicas credenciadas</div>
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach($unidades as $unidade)
                                <div class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                                    <div class="font-semibold text-slate-900">{{ $unidade->nome }}</div>
                                    <div class="text-slate-900">{{ $unidade->endereco }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="pt-4 border-t flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        @if(!in_array($status, ['FECHADA', 'CANCELADA'], true))
                            <a href="{{ route('comercial.propostas.edit', $proposta) }}"
                               class="px-4 py-2.5 rounded-lg border border-blue-200 bg-blue-50 text-xs text-blue-700 hover:bg-blue-100">
                                Editar proposta
                            </a>
                        @endif
                        <a href="{{ route('comercial.propostas.create') }}"
                           class="px-4 py-2.5 rounded-lg border border-emerald-200 bg-emerald-50 text-xs text-emerald-700 hover:bg-emerald-100">
                            Nova proposta
                        </a>
                        @if($canEdit)
                            <button type="button"
                                    class="px-4 py-2.5 rounded-lg border border-blue-200 bg-blue-50 text-xs text-blue-700 hover:bg-blue-100"
                                    id="btnEnviarWhatsapp">
                                Enviar WhatsApp
                            </button>
                            <button type="button"
                                    class="px-4 py-2.5 rounded-lg border border-emerald-200 bg-emerald-50 text-xs text-emerald-700 hover:bg-emerald-100"
                                    id="btnEnviarEmail">
                                Enviar E-mail
                            </button>
                            <a href="{{ route('comercial.propostas.pdf', $proposta) }}"
                               class="px-4 py-2.5 rounded-lg border border-indigo-200 bg-indigo-50 text-xs text-indigo-700 hover:bg-indigo-100">
                                Baixar PDF
                            </a>
                        @endif
                    </div>
                    @if($canEdit)
                        <div class="flex flex-wrap items-center gap-2 md:justify-end">
                            @if(!in_array($status, ['FECHADA', 'CANCELADA'], true))
                                <form method="POST"
                                      action="{{ route('comercial.propostas.fechar', $proposta) }}">
                                    @csrf
                                    <button type="submit"
                                            title="Aprova a proposta internamente, sem aceite do cliente, e gera o contrato."
                                            class="px-4 py-2.5 rounded-lg border border-amber-300 bg-amber-50 text-amber-800 text-xs font-semibold hover:bg-amber-100">
                                        Gerar contrato
                                    </button>
                                </form>
                            @endif
                            <form method="POST"
                                  action="{{ route('comercial.propostas.contrato.gerar', $proposta) }}"
                                  id="formGerarContratoIa">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2.5 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 text-xs font-semibold hover:bg-indigo-100">
                                    Gerar contrato com IA
                                </button>
                            </form>
                            <form method="POST"
                                  action="{{ route('comercial.propostas.contrato.gerar-template', $proposta) }}">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 text-xs font-semibold hover:bg-slate-100">
                                    Gerar contrato (template)
                                </button>
                            </form>
                            <a href="{{ route('comercial.propostas.print', $proposta) }}"
                               target="_blank"
                               rel="noopener"
                               class="px-4 py-2.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-semibold hover:bg-emerald-100">
                                Imprimir
                            </a>
                            <form method="POST"
                                  action="{{ route('comercial.propostas.destroy', $proposta) }}"
                                  data-confirm="Deseja excluir esta proposta?">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-4 py-2.5 rounded-lg border border-red-200 bg-red-50 text-red-700 text-xs font-semibold hover:bg-red-100">
                                    Excluir proposta
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div id="loadingContratoIa" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/60">
        <div class="bg-white rounded-2xl shadow-xl p-6 text-center w-full max-w-sm">
            <img src="{{ asset('storage/logo.png') }}" alt="Logo" class="mx-auto h-12 w-auto mb-4">
            <h3 class="text-sm font-semibold text-slate-800">Gerando contrato com IA...</h3>
            <p class="text-xs text-slate-500 mt-1">Isso pode levar alguns segundos.</p>
            <div class="mt-4 h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full w-1/2 bg-emerald-500 animate-pulse"></div>
            </div>
        </div>
    </div>

    @if($canEdit)
    {{-- Modal WhatsApp --}}
    <div id="modalWhatsapp" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Enviar proposta por WhatsApp</h3>
                    <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                            onclick="closeWhatsappModal()">✕</button>
                </div>

                <form id="formWhatsapp" method="POST" class="p-6 space-y-4"
                      action="{{ route('comercial.propostas.enviar-whatsapp', $proposta) }}">
                    @csrf

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Telefone</label>
                        <input id="whatsappTelefone" name="telefone"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="(11) 99999-9999"
                               value="{{ $proposta->cliente->telefone ?? '' }}">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Mensagem</label>
                        <textarea id="whatsappMensagem" name="mensagem" rows="4"
                                  class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                  placeholder="Mensagem..."></textarea>
                        <p class="text-xs text-slate-500 mt-1">O link público da proposta será incluído automaticamente.</p>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button"
                                class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                onclick="closeWhatsappModal()">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 text-sm font-semibold">
                            Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal E-mail --}}
    <div id="modalEmail" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Enviar proposta por e-mail</h3>
                    <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                            onclick="closeEmailModal()">✕</button>
                </div>

                <form id="formEmail" method="POST" class="p-6 space-y-4"
                      action="{{ route('comercial.propostas.enviar-email', $proposta) }}">
                    @csrf

                    <div>
                        <label class="text-xs font-semibold text-slate-600">E-mail</label>
                        <input id="emailTo" name="email" type="email"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               value="{{ $proposta->cliente->email ?? '' }}"
                               placeholder="email@cliente.com">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Assunto</label>
                        <input id="emailAssunto" name="assunto"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               value="Proposta {{ $propostaSequencial }}">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Mensagem</label>
                        <textarea id="emailMensagem" name="mensagem" rows="5"
                                  class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                  placeholder="Mensagem..."></textarea>
                        <p class="text-xs text-slate-500 mt-1">O link público da proposta será incluído automaticamente.</p>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button"
                                class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                onclick="closeEmailModal()">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold">
                            Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
    const formContratoIa = document.getElementById('formGerarContratoIa');
    const loadingContratoIa = document.getElementById('loadingContratoIa');
    if (formContratoIa && loadingContratoIa) {
        formContratoIa.addEventListener('submit', () => {
            loadingContratoIa.classList.remove('hidden');
            loadingContratoIa.classList.add('flex');
        });
    }
</script>
@endpush

    @push('scripts')
        <script>
            (function () {
                const modalWhatsapp = document.getElementById('modalWhatsapp');
                const modalEmail = document.getElementById('modalEmail');
                const whatsappMensagem = document.getElementById('whatsappMensagem');
                const emailMensagem = document.getElementById('emailMensagem');
                const emailAssunto = document.getElementById('emailAssunto');
                const propostaRef = @json($propostaSequencial);
                const publicLink = @json($publicLink ?? '');

                function openWhatsappModal() {
                    if (!modalWhatsapp) return;
                    if (whatsappMensagem) {
                        whatsappMensagem.value = `Olá! Segue a proposta ${propostaRef}.\n\nLink: ${publicLink}`;
                    }
                    modalWhatsapp.classList.remove('hidden');
                }

                function openEmailModal() {
                    if (!modalEmail) return;
                    if (emailAssunto) emailAssunto.value = `Proposta ${propostaRef}`;
                    if (emailMensagem) {
                        emailMensagem.value = `Olá! Segue a proposta ${propostaRef}.\n\nAcesse e responda: ${publicLink}`;
                    }
                    modalEmail.classList.remove('hidden');
                }

                window.closeWhatsappModal = () => modalWhatsapp?.classList.add('hidden');
                window.closeEmailModal = () => modalEmail?.classList.add('hidden');

                document.getElementById('btnEnviarWhatsapp')?.addEventListener('click', openWhatsappModal);
                document.getElementById('btnEnviarEmail')?.addEventListener('click', openEmailModal);
            })();
        </script>
    @endpush

@endsection
