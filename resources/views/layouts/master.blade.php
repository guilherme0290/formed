<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Painel Master') - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="bg-slate-900">
<div class="min-h-screen flex relative">

    {{-- BACKDROP (mobile) --}}
    <div id="master-sidebar-backdrop"
         class="fixed inset-0 bg-black/40 z-20 opacity-0 pointer-events-none transition-opacity duration-200 md:hidden"></div>

    {{-- Sidebar esquerda --}}
    <aside id="master-sidebar"
           class="fixed inset-y-0 left-0 z-30 w-64 bg-slate-950 text-slate-100
                  transform -translate-x-full transition-transform duration-200 ease-in-out
                  flex flex-col
                  md:static md:translate-x-0">

        <div class="h-16 flex items-center justify-between px-4 text-lg font-semibold border-b border-slate-800">

            <div class="flex items-center gap-2">
                {{-- Botão de colapse (DESKTOP) --}}
                <button type="button"
                        class="hidden md:inline-flex items-center justify-center p-1.5 rounded-lg text-slate-300 hover:bg-slate-800"
                        data-sidebar-collapse
                        title="Recolher/expandir">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <span data-sidebar-label-header>Master</span>
            </div>

            {{-- Botão fechar (somente mobile) --}}
            <button type="button"
                    class="inline-flex items-center justify-center p-2 rounded-lg text-slate-300 hover:bg-slate-800 md:hidden"
                    data-sidebar-close>
                ✕
            </button>
        </div>

        @php
            $navItems = [
                [
                    'label' => 'Painel Master',
                    'icon' => '📊',
                    'route' => route('master.dashboard'),
                    'active' => request()->routeIs('master.dashboard'),
                ],
                [
                    'label' => 'Acessos',
                    'icon' => '🔐',
                    'route' => route('master.acessos'),
                    'active' => request()->routeIs('master.acessos*'),
                ],
                [
                    'label' => 'Tabela de Preços',
                    'icon' => '💰',
                    'route' => route('master.tabela-precos.itens.index'),
                    'active' => request()->routeIs('master.tabela-precos.*'),
                ],
                [
                    'label' => 'Comissões',
                    'icon' => '💸',
                    'route' => route('master.comissoes.index'),
                    'active' => request()->routeIs('master.comissoes*'),
                ],
                [
                    'label' => 'Comissões (Vendedores)',
                    'icon' => '📈',
                    'route' => route('master.comissoes.vendedores'),
                    'active' => request()->routeIs('master.comissoes.vendedores'),
                ],
                [
                    'label' => 'Clientes',
                    'icon' => '👤',
                    'route' => route('clientes.index'),
                    'active' => request()->routeIs('clientes.*'),
                ],
            ];
        @endphp

        <nav class="flex-1 px-3 mt-4 space-y-1">
            @foreach($navItems as $item)
                @php
                    $isActive = $item['active'];
                    $baseClasses = 'flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition';
                    $activeClasses = $isActive
                        ? 'bg-slate-800 text-slate-50 font-semibold'
                        : 'text-slate-200 hover:bg-slate-800';
                @endphp
                <a href="{{ $item['route'] }}"
                   class="{{ $baseClasses }} {{ $activeClasses }}">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800">
                        {{ $item['icon'] }}
                    </span>
                    <span data-sidebar-label>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="px-4 py-4 border-t border-slate-800 space-y-2 text-sm">
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-slate-300 hover:text-white">
                <span>⏪</span>
                <span data-sidebar-label>Voltar ao Início</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex items-center gap-2 text-rose-400 hover:text-rose-300">
                    <span>🚪</span>
                    <span data-sidebar-label>Sair</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Área principal --}}
    <div class="flex-1 flex flex-col bg-slate-50">

        <header class="bg-indigo-700 text-white shadow-sm sticky top-0 z-20">
            <div class="w-full px-4 md:px-6 h-16 flex items-center justify-between gap-3 py-2">

                <div class="flex items-center gap-3">
                    {{-- Botão abrir/fechar sidebar (MOBILE) --}}
                    <button type="button"
                            class="inline-flex md:hidden items-center justify-center p-2 rounded-lg text-indigo-50 hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-white"
                            data-sidebar-toggle>
                        <span class="sr-only">Abrir menu</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="flex flex-col">
                        <span class="font-semibold text-lg tracking-tight leading-none">FORMED</span>
                        <span class="text-[11px] md:text-xs text-indigo-100">
                            Módulo Master
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-xs md:text-sm text-indigo-50">
                    <span class="hidden md:inline">
                        {{ auth()->user()->name ?? '' }}
                    </span>
                </div>
            </div>
        </header>

        <main class="flex-1 relative overflow-hidden">

            {{-- Marca d’água --}}
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.05]">
                <img src="{{ asset('storage/logo.svg') }}"
                     alt="FORMED"
                     class="max-w-[512px] w-full">
            </div>

            <div class="relative z-10">
                @yield('content')
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar       = document.getElementById('master-sidebar');
        const backdrop      = document.getElementById('master-sidebar-backdrop');
        const btnToggleMob  = document.querySelector('[data-sidebar-toggle]');
        const btnCloses     = document.querySelectorAll('[data-sidebar-close]');
        const btnCollapse   = document.querySelector('[data-sidebar-collapse]');
        const labels        = document.querySelectorAll('[data-sidebar-label]');
        const headerTitle   = document.querySelector('[data-sidebar-label-header]');

        let desktopCollapsed = false;

        function isMobile() {
            return window.innerWidth < 768;
        }

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
        }

        function closeSidebar() {
            if (isMobile()) {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('opacity-0', 'pointer-events-none');
            }
        }

        btnToggleMob?.addEventListener('click', openSidebar);
        btnCloses.forEach(btn => btn.addEventListener('click', closeSidebar));
        backdrop?.addEventListener('click', closeSidebar);

        btnCollapse?.addEventListener('click', () => {
            desktopCollapsed = !desktopCollapsed;
            sidebar.classList.toggle('w-64', !desktopCollapsed);
            sidebar.classList.toggle('w-16', desktopCollapsed);
            labels.forEach(l => l.classList.toggle('hidden', desktopCollapsed));
            if (headerTitle) headerTitle.textContent = desktopCollapsed ? 'M' : 'Master';
        });
    });
</script>
<script src="https://unpkg.com/currency.js@2.0.4/dist/currency.min.js"></script>

@stack('scripts')
</body>
</html>
