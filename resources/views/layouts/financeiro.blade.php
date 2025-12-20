<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Painel Financeiro') - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="bg-slate-950 text-slate-50">
<div class="min-h-screen flex relative">
    {{-- Sidebar --}}
    <aside class="hidden md:flex flex-col w-60 bg-slate-950 text-slate-100 border-r border-slate-900">
        <div class="h-16 flex items-center px-5 text-lg font-semibold border-b border-slate-900">
            Financeiro
        </div>
        <nav class="flex-1 px-3 mt-4 space-y-1">
            @php
                $links = [
                    ['label' => 'Dashboard', 'icon' => '📊', 'route' => route('financeiro.dashboard'), 'active' => request()->routeIs('financeiro.dashboard')],
                    ['label' => 'Contratos', 'icon' => '📄', 'route' => route('financeiro.contratos'), 'active' => request()->routeIs('financeiro.contratos*')],
                ];
            @endphp
            @foreach($links as $link)
                <a href="{{ $link['route'] }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm {{ $link['active'] ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-200 hover:bg-slate-800' }}">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800">{{ $link['icon'] }}</span>
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="px-4 py-4 border-t border-slate-900 space-y-2 text-sm">
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-slate-300 hover:text-white">
                <span>⏪</span><span>Voltar</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex items-center gap-2 text-rose-400 hover:text-rose-300">
                    <span>🚪</span> Sair
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col bg-slate-50">
        <header class="bg-indigo-700 text-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex flex-col">
                        <span class="font-semibold text-lg leading-none">FORMED</span>
                        <span class="text-[11px] text-indigo-100">Módulo Financeiro</span>
                    </div>
                </div>
                <div class="text-xs md:text-sm text-indigo-50">
                    {{ auth()->user()->name ?? '' }}
                </div>
            </div>
        </header>

        <main class="flex-1 relative overflow-hidden">
            <div class="relative z-10">
                @yield('content')
            </div>
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
