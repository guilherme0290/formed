@extends('layouts.comercial')
@section('title', 'Proposta Comercial')

@section('content')
    @php
        $status = strtoupper((string) ($proposta->status ?? 'PENDENTE'));
        $hasEsocialItem = $proposta->itens->contains(fn ($it) => strtoupper((string) ($it->tipo ?? '')) === 'ESOCIAL');
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
            <a href="{{ route('comercial.propostas.edit', $proposta) }}"
               class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800">
                ← Voltar
            </a>
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

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
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
                        <div class="text-xs font-semibold text-slate-500 uppercase">Data de vencimento</div>
                        <div class="text-sm font-semibold text-slate-900 mt-1">
                            {{ $proposta->vencimento_servicos ?? '-'  }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            Prazo da proposta: {{ $proposta->prazo_dias ?? 7 }} dias
                        </div>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                        <div class="text-xs font-semibold text-emerald-700 uppercase">Valor total</div>
                        <div class="text-2xl font-semibold text-emerald-800 mt-2">
                            R$ {{ number_format($proposta->valor_total, 2, ',', '.') }}
                        </div>
                        <div class="text-xs text-emerald-700 mt-1">
                            {{ $proposta->incluir_esocial ? 'Inclui eSocial' : 'Sem eSocial' }}
                        </div>
                    </div>
                </div>

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
                    <div class="rounded-2xl border border-slate-200 p-4 bg-slate-50">
                        <div class="text-xs font-semibold text-slate-500 uppercase mb-2">Cliente final</div>
                        <div class="text-sm font-semibold text-slate-900">
                            {{ $proposta->cliente->razao_social ?? '-' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $proposta->cliente->cnpj ?? '' }}
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

                @if($proposta->incluir_esocial)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <div class="text-xs font-semibold text-amber-700 uppercase">eSocial (mensal)</div>
                        <p class="text-sm text-amber-800 mt-1">
                            {{ $proposta->esocial_qtd_funcionarios }} colaboradores — R$
                            {{ number_format($proposta->esocial_valor_mensal, 2, ',', '.') }}/mês
                        </p>
                    </div>
                @endif

                @if($unidades->count())
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="text-xs font-semibold text-slate-500 uppercase mb-2">Cl&iacute;nicas credenciadas</div>
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach($unidades as $unidade)
                                <div class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                                    <div class="font-semibold text-slate-800">{{ $unidade->nome }}</div>
                                    <div class="text-slate-500">{{ $unidade->endereco }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="pt-4 border-t flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('comercial.propostas.index') }}"
                       class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">
                        Voltar para propostas
                    </a>
                    <a href="{{ route('comercial.propostas.create') }}"
                       class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">
                        Nova proposta
                    </a>
                    <button type="button"
                            class="px-4 py-2 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-sm font-semibold hover:bg-emerald-100"
                            id="btnEnviarWhatsapp">
                        Enviar WhatsApp
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded-xl border border-blue-200 bg-blue-50 text-blue-800 text-sm font-semibold hover:bg-blue-100"
                            id="btnEnviarEmail">
                        Enviar E-mail
                    </button>
                    <form method="POST"
                          action="{{ route('comercial.propostas.destroy', $proposta) }}"
                          onsubmit="return confirm('Deseja excluir esta proposta?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm font-semibold hover:bg-red-100">
                            Excluir proposta
                        </button>
                    </form>
                    <a href="{{ route('comercial.propostas.pdf', $proposta) }}"
                       class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                        Baixar PDF
                    </a>
                    <a href="{{ route('comercial.propostas.print', $proposta) }}"
                       target="_blank"
                       rel="noopener"
                       class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                        Imprimir
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal WhatsApp --}}
    <div id="modalWhatsapp" class="fixed inset-0 z-50 hidden bg-black/40">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden">
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
    <div id="modalEmail" class="fixed inset-0 z-50 hidden bg-black/40">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden">
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
