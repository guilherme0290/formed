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

    @include('layouts.partials.master-sidebar')

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
