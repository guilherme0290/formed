<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Portal do Cliente') - Formed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- opcional, mas útil pro JS/AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800">
<div class="min-h-screen flex flex-col">

    {{-- TOP BAR IGUAL AO OPERACIONAL (mesmo azul) --}}
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between">

            {{-- Lado esquerdo: FORMED + subtítulo --}}
            <div class="flex items-baseline gap-3">
                <span class="font-semibold text-lg tracking-tight">
                    FORMED
                </span>
                <span class="text-xs md:text-sm text-blue-100">
                    Medicina e Segurança do Trabalho
                </span>
            </div>

            {{-- Lado direito: usuário + botão trocar --}}
            <div class="flex items-center gap-4 text-xs md:text-sm text-blue-50">

                <span class="hidden md:inline">
                    @auth
                        {{ auth()->user()->name ?? 'Usuário Portal Cliente' }}
                    @else
                        Usuário Portal Cliente
                    @endauth
                </span>

                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1 rounded-full border border-blue-300/60
                                       px-3 py-1 text-[11px] font-medium text-blue-50
                                       hover:bg-white/10 hover:text-white transition">
                            <span class="text-xs">⇄</span>
                            Trocar usuário
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    {{-- 🔵 FAIXA AZUL DO CLIENTE --}}
    @isset($cliente)
        @php
            $razaoOuFantasia = $cliente->nome_fantasia ?: $cliente->razao_social;
            $cnpjFormatado   = $cliente->cnpj ?? '';
            $contatoNome     = $cliente->contato_nome ?? (auth()->user()->name ?? 'Contato não informado');
            $contatoTelefone = $cliente->telefone ?? (auth()->user()->telefone ?? '(00) 0000-0000');
            $contatoEmail    = $cliente->email ?? (auth()->user()->email ?? 'email@dominio.com');
        @endphp

        <section
            class="w-full bg-[#1450d2] text-white shadow-lg shadow-slate-900/20
                   py-5 md:py-6">

            <div class="max-w-7xl mx-auto px-6 md:px-8 flex flex-col md:flex-row
                        md:items-center md:justify-between gap-6">

                {{-- Nome do cliente + CNPJ --}}
                <div class="flex items-start gap-3">
                    <div class="h-12 w-12 rounded-2xl bg-white/10 flex items-center justify-center text-2xl">
                        🏢
                    </div>
                    <div>
                        <h1 class="text-lg md:text-xl font-semibold leading-tight">
                            {{ $razaoOuFantasia }}
                        </h1>

                        @if($cnpjFormatado)
                            <p class="text-xs md:text-sm text-blue-100 mt-1">
                                CNPJ: {{ $cnpjFormatado }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Dados de contato --}}
                <div class="flex flex-col text-xs md:text-sm text-blue-50 md:text-right">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-end gap-6 lg:gap-12">

                        <div class="md:mr-10 lg:mr-16">
                            <span class="uppercase text-[10px] tracking-[0.18em] text-blue-100/70 block">
                                Contato
                            </span>
                            <span class="font-medium">{{ $contatoNome }}</span>
                        </div>

                        <div class="md:mr-10 lg:mr-16 md:mt-0 mt-2">
                            <span class="uppercase text-[10px] tracking-[0.18em] text-blue-100/70 block">
                                Telefone
                            </span>
                            <span class="font-medium">{{ $contatoTelefone }}</span>
                        </div>

                        <div class="md:mt-0 mt-2">
                            <span class="uppercase text-[10px] tracking-[0.18em] text-blue-100/70 block">
                                E-mail
                            </span>
                            <span class="font-medium break-all">{{ $contatoEmail }}</span>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    @endisset
    {{-- 🔵 FIM FAIXA CLIENTE --}}

    {{-- ALERTAS --}}
    @if (session('ok'))
        <div class="max-w-7xl mx-auto mt-4 px-6">
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 shadow">
                {{ session('ok') }}
            </div>
        </div>
    @endif

    @if (session('erro'))
        <div class="max-w-7xl mx-auto mt-4 px-6">
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 shadow">
                {{ session('erro') }}
            </div>
        </div>
    @endif

    {{-- CONTEÚDO + SIDEBAR --}}
    <div class="flex flex-1">
        {{-- SIDEBAR DO CLIENTE --}}
        <aside class="hidden md:flex flex-col w-56 bg-slate-950 text-slate-100">
            <div class="h-16 flex items-center px-6 text-lg font-semibold">
                Portal do Cliente
            </div>

            <nav class="flex-1 px-3 mt-4 space-y-1">
                <a href="{{ route('cliente.dashboard') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-800 text-slate-50 text-sm font-medium">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-700">
                        🏠
                    </span>
                    <span>Painel do Cliente</span>
                </a>
            </nav>

            <div class="px-4 py-4 border-t border-slate-800 space-y-2 text-sm">
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-slate-300 hover:text-white">
                    <span>⏪</span> <span>Voltar ao Início</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex items-center gap-2 text-rose-400 hover:text-rose-300">
                        <span>🚪</span> Sair
                    </button>
                </form>
            </div>
        </aside>

        {{-- CONTEÚDO COM MARCA D'ÁGUA (IGUAL ESTAVA) --}}
        <main class="flex-1 relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
                <img src="{{ asset('storage/logo.svg') }}"
                     alt="FORMED"
                     class="max-w-[512px] w-full">
            </div>

            <div class="relative z-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
