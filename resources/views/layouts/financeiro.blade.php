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
    @php $isMaster = auth()->user()?->isMaster(); @endphp

    @if($isMaster)
        @include('layouts.partials.master-sidebar')
    @else
        {{-- Sidebar --}}
        <aside class="hidden md:flex flex-col w-60 bg-slate-950 text-slate-100 border-r border-slate-900 relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
                <img src="{{ asset('storage/logo.svg') }}" alt="FORMED" class="w-36">
            </div>
            <div class="relative z-10 h-16 flex items-center px-5 text-lg font-semibold border-b border-slate-900">
                Financeiro
            </div>
            <nav class="relative z-10 flex-1 px-3 mt-4 space-y-1">
                @php
                    $links = [
                        ['label' => 'Dashboard', 'icon' => '📊', 'route' => route('financeiro.dashboard'), 'active' => request()->routeIs('financeiro.dashboard')],
                        ['label' => 'Contratos', 'icon' => '📄', 'route' => route('financeiro.contratos'), 'active' => request()->routeIs('financeiro.contratos*')],
                        ['label' => 'Contas a Receber', 'icon' => '💳', 'route' => route('financeiro.contas-receber'), 'active' => request()->routeIs('financeiro.contas-receber*')],
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
            <div class="relative z-10 px-4 py-4 border-t border-slate-900 space-y-2 text-sm">
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
    @endif

    {{-- Main --}}
    <div class="flex-1 flex flex-col bg-slate-50">
        <header class="bg-indigo-700 text-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if($isMaster)
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
                    @endif
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
@if($isMaster)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const MOBILE_BREAKPOINT = 768;

            const sidebar = document.getElementById('master-sidebar');
            const backdrop = document.getElementById('master-sidebar-backdrop');
            const btnToggleMob = document.querySelector('[data-sidebar-toggle]');
            const btnCloses = document.querySelectorAll('[data-sidebar-close]');
            const btnCollapse = document.querySelector('[data-sidebar-collapse]');
            const labels = document.querySelectorAll('[data-sidebar-label]');
            const headerTitle = document.querySelector('[data-sidebar-label-header]');

            let desktopCollapsed = false;

            function isMobile() {
                return window.innerWidth < MOBILE_BREAKPOINT;
            }

            function abrirSidebarMobile() {
                if (!sidebar) return;
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');

                if (backdrop) {
                    backdrop.classList.remove('opacity-0', 'pointer-events-none');
                    backdrop.classList.add('opacity-100');
                }
            }

            function fecharSidebarMobile() {
                if (!sidebar) return;
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');

                if (backdrop) {
                    backdrop.classList.add('opacity-0', 'pointer-events-none');
                    backdrop.classList.remove('opacity-100');
                }
            }

            function setDesktopCollapsed(collapsed) {
                if (!sidebar) return;
                desktopCollapsed = collapsed;

                sidebar.classList.toggle('w-64', !desktopCollapsed);
                sidebar.classList.toggle('w-16', desktopCollapsed);
                labels.forEach(el => el.classList.toggle('hidden', desktopCollapsed));
                if (headerTitle) headerTitle.classList.toggle('hidden', desktopCollapsed);
            }

            if (btnToggleMob) {
                btnToggleMob.addEventListener('click', function () {
                    if (!isMobile()) return;
                    if (sidebar.classList.contains('-translate-x-full')) {
                        abrirSidebarMobile();
                    } else {
                        fecharSidebarMobile();
                    }
                });
            }

            if (btnCollapse) {
                btnCollapse.addEventListener('click', function () {
                    if (isMobile()) return;
                    setDesktopCollapsed(!desktopCollapsed);
                });
            }

            btnCloses.forEach(btn => {
                btn.addEventListener('click', function () {
                    if (isMobile()) {
                        fecharSidebarMobile();
                    }
                });
            });

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    if (isMobile()) {
                        fecharSidebarMobile();
                    }
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && isMobile()) {
                    fecharSidebarMobile();
                }
            });

            if (isMobile()) {
                fecharSidebarMobile();
            } else {
                setDesktopCollapsed(false);
            }

            window.addEventListener('resize', function () {
                if (isMobile()) {
                    setDesktopCollapsed(false);
                    fecharSidebarMobile();
                } else {
                    sidebar.classList.remove('-translate-x-full', 'translate-x-0');
                    if (backdrop) {
                        backdrop.classList.add('opacity-0', 'pointer-events-none');
                        backdrop.classList.remove('opacity-100');
                    }
                }
            });
        });
    </script>
@endif
@stack('scripts')
</body>
</html>
