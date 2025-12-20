{{-- resources/views/clientes/dashboard.blade.php --}}
@extends('layouts.cliente')

@section('title', 'Painel do Cliente')

@section('content')
    @php
        /** @var \App\Models\Cliente $cliente */
        /** @var \App\Models\User $user */
        $temTabela = $temTabela ?? false;
        $precos = $precos ?? [];

        // Usados apenas nos cards "Seu Comercial"
        $contatoNome     = $cliente->contato_nome ?? $user->name ?? 'Contato não informado';
        $contatoTelefone = $cliente->telefone ?? $user->telefone ?? '(00) 0000-0000';
    @endphp

    {{-- TÍTULO DO PAINEL --}}
    <section class="max-w-6xl mx-auto px-4 md:px-0">
        <div class="mb-6">
            <h2 class="text-xl md:text-2xl font-semibold text-slate-900">
                Painel do Cliente
            </h2>
            <p class="text-xs md:text-sm text-slate-500">
                Gerencie seus serviços e solicitações.
            </p>
        </div>

        {{-- CARDS PRINCIPAIS (SEU COMERCIAL + FATURA ATUAL) --}}
        <div class="grid gap-4 md:gap-6 md:grid-cols-2 mb-8">

            {{-- Card: Seu Comercial (azul) --}}
            <div class="rounded-3xl bg-[#1554d9] text-white shadow-lg shadow-blue-900/20 p-5 md:p-6 flex flex-col justify-between">
                <div class="flex items-start gap-3 mb-4">
                    <div class="h-9 w-9 rounded-2xl bg-white/15 flex items-center justify-center text-xl">
                        📞
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/90">
                            Seu Comercial
                        </p>
                        <p class="mt-1 font-semibold text-base md:text-lg">
                            {{ $contatoNome }}
                        </p>
                        <p class="text-xs text-blue-100/80 mt-1">
                            {{ $contatoTelefone }}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="mt-2 inline-flex items-center justify-center w-full rounded-full
                           bg-emerald-500 hover:bg-emerald-400 text-xs md:text-sm font-semibold
                           text-white py-2.5 transition">
                    <span class="mr-1.5 text-sm">💬</span>
                    Falar no WhatsApp
                </button>
            </div>

            {{-- Card: Fatura Atual (verde) --}}
            <div class="rounded-3xl bg-[#059669] text-white shadow-lg shadow-emerald-900/25 p-5 md:p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-start gap-3 mb-4">
                        <div class="h-9 w-9 rounded-2xl bg-white/15 flex items-center justify-center text-xl">
                            💲
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-emerald-50/90">
                                Fatura Atual
                            </p>
                            <p class="mt-1 text-lg md:text-2xl font-semibold">
                                R$ {{ number_format($faturaTotal ?? 0, 2, ',', '.') }}
                            </p>
                            <p class="text-[11px] text-emerald-50/90 mt-1">
                                Soma dos serviços finalizados (kanban)
                            </p>
                        </div>
                    </div>

                    {{-- Barra branca "Ver Detalhes" --}}
                    <a
                        href="{{ route('cliente.faturas') }}"
                        class="w-full inline-flex items-center justify-center rounded-md bg-white text-xs md:text-[13px] font-semibold
                               text-slate-900 px-3 py-2 mb-3 text-center shadow-sm hover:bg-slate-50 transition">
                        Ver Detalhes
                    </a>
                </div>

                {{-- Botão amarelo "Realizar Pagamento" (desabilitado por ora) --}}
                <button
                    type="button"
                    disabled
                    class="w-full rounded-md bg-amber-300/70 text-xs md:text-[13px] font-semibold
                           text-slate-600 px-3 py-2 flex items-center justify-center gap-2 shadow-inner shadow-amber-200/50 cursor-not-allowed">
                    <span class="text-sm">💳</span>
                    Realizar Pagamento (em breve)
                </button>
            </div>
        </div>

        {{-- SEÇÃO: SERVIÇOS DISPONÍVEIS (BARRA AZUL-MARINHO + GRID DE CARDS) --}}
        @include('clientes.partials.servicos')

        {{-- SEÇÃO: PERÍCIAS (CARD ROSA + AZUL PETRÓLEO) --}}
        <section class="mt-8 grid gap-6 md:grid-cols-2">
            {{-- Perícia Médica --}}
            <article class="bg-white rounded-3xl shadow-md border border-slate-200 overflow-hidden">
                <header class="bg-[#c0145f] text-white px-6 py-3 flex items-center gap-2 text-sm font-semibold">
                    <span>⚖️</span>
                    <span>Perícia Médica</span>
                </header>
                <div class="px-6 py-4 text-sm text-slate-600 space-y-2">
                    <p class="font-semibold text-slate-800">
                        Atendemos todo o Brasil!
                    </p>
                    <p class="text-xs md:text-sm">
                        Ajudamos empresas em processos trabalhistas enviando assistente médico para perícia com
                        laudo e impugnações necessárias, auxiliando o advogado da empresa.
                    </p>
                </div>
                <div class="px-6 pb-5">
                    <button
                        type="button"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-md
                               bg-emerald-600 hover:bg-emerald-500 text-xs md:text-sm font-semibold
                               text-white py-2.5">
                        <span class="text-sm">💬</span>
                        Consultar Valor no WhatsApp
                    </button>
                </div>
            </article>

            {{-- Perícia Técnica --}}
            <article class="bg-white rounded-3xl shadow-md border border-slate-200 overflow-hidden">
                <header class="bg-[#046c82] text-white px-6 py-3 flex items-center gap-2 text-sm font-semibold">
                    <span>⚖️</span>
                    <span>Perícia Técnica</span>
                </header>
                <div class="px-6 py-4 text-sm text-slate-600 space-y-2">
                    <p class="font-semibold text-slate-800">
                        Atendemos todo o Brasil!
                    </p>
                    <p class="text-xs md:text-sm">
                        Apoiamos sua empresa com perito engenheiro para perícia com laudo técnico
                        e pareceres complementares, auxiliando o advogado da empresa.
                    </p>
                </div>
                <div class="px-6 pb-5">
                    <button
                        type="button"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-md
                               bg-emerald-600 hover:bg-emerald-500 text-xs md:text-sm font-semibold
                               text-white py-2.5">
                        <span class="text-sm">💬</span>
                        Consultar Valor no WhatsApp
                    </button>
                </div>
            </article>
        </section>
    </section>
@endsection
