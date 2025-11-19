<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Formed') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-slate-50">
<div x-data="{ open:false }" class="min-h-screen flex">

    {{-- Sidebar --}}
    <aside class="hidden md:flex w-64 flex-col bg-[#0F172A] text-slate-200">
        <div class="h-16 border-b border-white/10 flex items-center px-5">
            <span class="text-white font-semibold text-lg">Formed</span>
        </div>

        <nav class="flex-1 px-3 py-4 text-sm space-y-1">
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10">
                <span>🏠</span> Dashboard
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10">
                <span>📈</span> Comercial
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10">
                <span>👥</span> Cliente
            </a>
            <a href="{{ route('operacional.painel') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10">
                <span>🗂️</span> Operacional
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10">
                <span>💰</span> Financeiro
            </a>

            <a href="{{ route('master.dashboard') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg
               {{ request()->routeIs('master.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-white/10 text-gray-200' }}">
                <span>🏢</span> Painel Master
            </a>

            <a href="{{ route('master.acessos', ['tab' => 'usuarios']) }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg
               {{ request()->routeIs('master.acessos') ? 'bg-indigo-600 text-white' : 'hover:bg-white/10 text-gray-200' }}">
                <span>⚙️</span> Acessos & Usuários
            </a>
        </nav>

        <div class="mt-auto border-t border-white/10 p-3 space-y-1">
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10">
                <span>🔧</span> Configurações
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10 text-rose-300">
                    <span>🚪</span> Sair
                </button>
            </form>
        </div>
    </aside>

    {{-- Wrapper --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Topbar --}}
        <header class="h-16 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60 border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between">
                <div class="flex items-center gap-3 w-full">
                    <button class="md:hidden" @click="open = !open">☰</button>
                    <input
                        class="hidden md:block w-[26rem] rounded-2xl bg-slate-100 border-0 focus:ring-0 px-4 py-2 text-sm"
                        placeholder="Buscar...">
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-slate-400 text-lg">🔔</span>
                    <div class="h-9 w-9 rounded-full bg-indigo-600 grid place-items-center text-white text-sm">
                        {{ \Illuminate\Support\Str::of(Auth::user()->name ?? 'U')->substr(0,1) }}
                    </div>
                    <div class="leading-tight">
                        <div class="text-sm font-semibold">{{ Auth::user()->name ?? 'Administrador' }}</div>
                        <div class="text-[11px] text-slate-500">Comercial</div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Conteúdo --}}
        <main class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                {{-- header vindo por <x-app-layout> (slot) --}}
                @if (isset($header))
                    <div class="mb-6">
                        {{ $header }}
                    </div>
                @endif

                {{-- header vindo por @section('header') --}}
                @if (!isset($header))
                    @hasSection('header')
                        <div class="mb-6">
                            @yield('header')
                        </div>
                    @endif
                @endif

                {{-- conteúdo: prioriza $slot (Breeze). Se não tiver, usa @section('content') --}}
                @if (isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif

            </div>
        </main>
    </div>
</div>

@stack('modals')
@stack('scripts')
</body>
</html>
