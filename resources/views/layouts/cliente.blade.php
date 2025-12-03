<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Portal do Cliente') - Formed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
<div class="min-h-screen flex flex-col">

    {{-- TOP BAR AZUL ESCURO, IGUAL AO LAYOUT MODELO --}}
    <header class="bg-[#0f2647] text-white shadow-md">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">

            {{-- Lado esquerdo: voltar + logo/nome --}}
            <div class="flex items-center gap-6">

                {{-- Voltar ao início --}}
                <a href="{{ url('/entrar') }}"
                   class="hidden sm:inline-flex items-center gap-2 text-[11px] font-medium text-blue-100 hover:text-white">
                    <span class="text-sm">←</span>
                    <span>Voltar ao início</span>
                </a>

                {{-- FORMED + subtítulo --}}
                <div class="flex flex-col leading-tight">
                    <span class="font-semibold text-sm sm:text-base tracking-[0.22em] uppercase">
                        FORMED
                    </span>
                    <span class="text-[10px] sm:text-xs text-blue-100">
                        Medicina e Segurança do Trabalho
                    </span>
                </div>
            </div>

            {{-- Lado direito: usuário logado + botão trocar --}}
            <div class="flex items-center gap-4 text-[11px] sm:text-xs text-blue-100">

                @auth
                   {{-- <div class="hidden sm:flex flex-col items-end leading-tight max-w-[180px]">
                        <span class="uppercase tracking-[0.18em] text-[9px] text-blue-200">
                            Usuário
                        </span>
                        <span class="font-medium text-sm text-white truncate">
                            {{ auth()->user()->name }}
                        </span>
                    </div>--}}

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

    {{-- CONTEÚDO --}}
    <main class="flex-1">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
