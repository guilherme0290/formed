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
        $contatoNome     = optional($cliente->vendedor)->name ?? "Comercial n\u{00E3}o informado";
        $contatoTelefone = optional($cliente->vendedor)->telefone ?? '-';
        $contatoEmail    = optional($cliente->vendedor)->email ?? '-';
        $vendedorTelefone = $vendedorTelefone ?? '';
        $whatsappBase = $vendedorTelefone ? 'https://wa.me/'.$vendedorTelefone : '#';
        $whatsappComercial = $whatsappBase !== '#'
            ? $whatsappBase.'?text='.urlencode("Ol\u{00E1}! Gostaria de falar com o comercial sobre o cliente ".($cliente->razao_social ?? "minha empresa").".")
            : '#';
        $whatsappPericiaMedica = $whatsappBase !== '#'
            ? $whatsappBase.'?text='.urlencode("Ol\u{00E1}! Gostaria de consultar valores para Per\u{00ED}cia M\u{00E9}dica para o cliente ".($cliente->razao_social ?? "minha empresa").".")
            : '#';
        $whatsappPericiaTecnica = $whatsappBase !== '#'
            ? $whatsappBase.'?text='.urlencode("Ol\u{00E1}! Gostaria de consultar valores para Per\u{00ED}cia T\u{00E9}cnica para o cliente ".($cliente->razao_social ?? "minha empresa").".")
            : '#';
    @endphp

    {{-- TITULO DO PAINEL --}}
    <section class="w-full px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h2 class="text-xl md:text-2xl font-semibold text-slate-900">
                Painel do Cliente
            </h2>
            <p class="text-xs md:text-sm text-slate-500">
                Gerencie seus servi&ccedil;os e solicita&ccedil;&otilde;es.
            </p>
        </div>

        {{-- CARDS PRINCIPAIS (SEU COMERCIAL + FATURA ATUAL) --}}
        <div class="grid gap-4 md:gap-6 sm:grid-cols-2 lg:grid-cols-2 mb-8">

            {{-- Card: Seu Comercial (azul) --}}
            <div class="rounded-3xl bg-[#1554d9] text-white shadow-lg shadow-blue-900/20 p-5 md:p-6 flex flex-col justify-between">
                <div class="flex items-start gap-3 mb-4">
                    <div class="h-9 w-9 rounded-2xl bg-white/15 flex items-center justify-center text-[10px] font-semibold">
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
                        <p class="text-xs text-blue-100/80 mt-1">
                            {{ $contatoEmail }}
                        </p>
                    </div>
                </div>

                <a
                    href="{{ $whatsappComercial }}"
                    @if($whatsappComercial !== '#') target="_blank" rel="noopener noreferrer" @endif
                    class="mt-2 inline-flex items-center justify-center w-full rounded-full
                           bg-emerald-500 hover:bg-emerald-400 text-xs md:text-sm font-semibold
                           text-white py-2.5 transition">
                    <span class="mr-1.5 text-[10px] font-semibold">💬</span>
                    Falar no WhatsApp
                </a>
            </div>

            {{-- Card: Fatura Atual (verde) --}}
            <div class="rounded-3xl bg-[#059669] text-white shadow-lg shadow-emerald-900/25 p-5 md:p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-start gap-3 mb-4">
                        <div class="h-9 w-9 rounded-2xl bg-white/15 flex items-center justify-center text-base font-semibold">
                            💰
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-emerald-50/90">
                                Fatura Atual
                            </p>
                            <p class="mt-1 text-lg md:text-2xl font-semibold">
                                R$ {{ number_format($faturaTotal ?? 0, 2, ',', '.') }}
                            </p>
                            <p class="text-[11px] text-emerald-50/90 mt-1">
                                Soma das contas a receber em aberto
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

                {{-- Botao amarelo "Realizar Pagamento" (desabilitado por ora) --}}
                <button
                    type="button"
                    disabled
                    class="w-full rounded-md bg-amber-300/70 text-xs md:text-[13px] font-semibold
                           text-slate-600 px-3 py-2 flex items-center justify-center gap-2 shadow-inner shadow-amber-200/50 cursor-not-allowed">
                    <span class="text-[10px] font-semibold">💳</span>
                    Realizar Pagamento (em breve)
                </button>
            </div>

        </div>

        {{-- SECAO: SERVICOS DISPONIVEIS (BARRA AZUL-MARINHO + GRID DE CARDS) --}}
        @include('clientes.partials.servicos')

        {{-- SECAO: PERICIAS (CARD ROSA + AZUL PETROLEO) --}}
        <section class="mt-8 grid gap-6 md:grid-cols-2">
            {{-- Pericia Medica --}}
            <article class="bg-white rounded-3xl shadow-md border border-slate-200 overflow-hidden">
                <header class="bg-[#c0145f] text-white px-6 py-3 flex items-center gap-2 text-sm font-semibold">
                    <span>PM</span>
                    <span>Per&iacute;cia M&eacute;dica</span>
                </header>
                <div class="px-6 py-4 text-sm text-slate-600 space-y-2">
                    <p class="font-semibold text-slate-800">
                        Atendemos todo o Brasil!
                    </p>
                    <p class="text-xs md:text-sm">
                        Ajudamos empresas em processos trabalhistas enviando assistente m&eacute;dico para per&iacute;cia com
                        laudo e impugna&ccedil;&otilde;es necess&aacute;rias, auxiliando o advogado da empresa.
                    </p>
                </div>
                <div class="px-6 pb-5">
                    <a
                        href="{{ $whatsappPericiaMedica }}"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-md
                               bg-emerald-600 hover:bg-emerald-500 text-xs md:text-sm font-semibold
                               text-white py-2.5">
                        <span class="text-[10px] font-semibold">💬</span>
                        Consultar Valor no WhatsApp
                    </a>
                </div>
            </article>

            {{-- Pericia Tecnica --}}
            <article class="bg-white rounded-3xl shadow-md border border-slate-200 overflow-hidden">
                <header class="bg-[#046c82] text-white px-6 py-3 flex items-center gap-2 text-sm font-semibold">
                    <span>PT</span>
                    <span>Per&iacute;cia T&eacute;cnica</span>
                </header>
                <div class="px-6 py-4 text-sm text-slate-600 space-y-2">
                    <p class="font-semibold text-slate-800">
                        Atendemos todo o Brasil!
                    </p>
                    <p class="text-xs md:text-sm">
                        Apoiamos sua empresa com perito engenheiro para per&iacute;cia com laudo t&eacute;cnico
                        e pareceres complementares, auxiliando o advogado da empresa.
                    </p>
                </div>
                <div class="px-6 pb-5">
                    <a
                        href="{{ $whatsappPericiaTecnica }}"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-md
                               bg-emerald-600 hover:bg-emerald-500 text-xs md:text-sm font-semibold
                               text-white py-2.5">
                        <span class="text-[10px] font-semibold">💬</span>
                        Consultar Valor no WhatsApp
                    </a>
                </div>
            </article>
        </section>
    </section>
@endsection
