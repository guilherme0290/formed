<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Painel Financeiro') - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="bg-slate-50 text-slate-900">
<div class="min-h-screen md:flex relative overflow-x-hidden">
    @php
        $authUser = auth()->user();
        $isMaster = $authUser?->isMaster();
    @endphp

    @if($isMaster)
        @include('layouts.partials.master-sidebar')
    @else
        @include('layouts.partials.financeiro-sidebar')
    @endif

    {{-- Main --}}
    <div class="flex-1 min-h-screen flex flex-col bg-slate-50">
        <header class="bg-indigo-700 text-white shadow-sm">
            <div class="w-full px-4 md:px-6 h-16 flex items-center">
                <div class="flex items-center gap-3 w-1/3">
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
                        <span class="font-semibold text-lg leading-none">FORMED</span>
                        <span class="text-[11px] text-indigo-100">M&oacute;dulo Financeiro</span>
                    </div>
                </div>
                <div class="w-1/3"></div>
                <div class="w-1/3 flex justify-end text-xs md:text-sm text-indigo-50">
                    <div x-data="{ open: false }" class="relative">
                        <button type="button"
                                class="flex items-center gap-2 rounded-full bg-indigo-600/40 px-2.5 py-1.5 hover:bg-indigo-600/60 transition"
                                @click="open = !open"
                                @keydown.escape="open = false"
                                :aria-expanded="open.toString()"
                                aria-haspopup="true">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-indigo-500 text-white text-xs font-semibold">
                                {{ \Illuminate\Support\Str::of(auth()->user()->name ?? 'U')->substr(0,1) }}
                            </span>
                            <span class="hidden md:inline">
                                {{ auth()->user()->name ?? '' }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-100" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open"
                             x-transition
                             @click.away="open = false"
                             class="absolute right-0 mt-2 w-56 rounded-xl bg-white text-slate-700 shadow-lg border border-slate-200 overflow-hidden z-30">
                            <a href="{{ route('master.acessos', ['tab' => 'senhas']) }}"
                               data-only-my-password
                               class="flex items-center gap-2 px-4 py-3 text-sm hover:bg-slate-50">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">🔒</span>
                                <span>Alterar Senha</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-3 text-sm hover:bg-slate-50 text-left">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">🚪</span>
                                    <span>Sair</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 relative bg-slate-50 overflow-x-hidden overflow-y-auto">
            <div class="relative z-10">
                <div class="@yield('page-container', 'w-full px-4 sm:px-6 lg:px-8 py-6')">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const flashOk = @json(session('ok'));
        const flashErr = @json(session('error') ?? session('erro'));
        if (typeof window.uiAlert === 'function' && !window.__financeiroFlashShown) {
            window.__financeiroFlashShown = true;
            if (flashOk) {
                window.uiAlert(flashOk, { icon: 'success', title: 'Sucesso' });
            } else if (flashErr) {
                window.uiAlert(flashErr);
            }
        }

        const MOBILE_BREAKPOINT = 768;

        const sidebarId = @json($isMaster ? 'master-sidebar' : 'financeiro-sidebar');
        const backdropId = @json($isMaster ? 'master-sidebar-backdrop' : 'financeiro-sidebar-backdrop');

        const sidebar = document.getElementById(sidebarId);
        const backdrop = document.getElementById(backdropId);
        const btnToggleMob = document.querySelector('[data-sidebar-toggle]');
        const btnCloses = document.querySelectorAll('[data-sidebar-close]');
        const btnCollapse = document.querySelector('[data-sidebar-collapse]');
        const labels = document.querySelectorAll('[data-sidebar-label]');
        const headerTitle = document.querySelector('[data-sidebar-label-header]');

        let desktopCollapsed = false;
        let mobileHideTimer = null;

        function isMobile() {
            return window.innerWidth < MOBILE_BREAKPOINT;
        }

        function isSidebarOpenMobile() {
            if (!sidebar) return false;
            return sidebar.classList.contains('translate-x-0');
        }

        function abrirSidebarMobile() {
            if (!sidebar) return;
            if (mobileHideTimer) {
                clearTimeout(mobileHideTimer);
                mobileHideTimer = null;
            }

            sidebar.style.setProperty('display', 'flex');
            desktopCollapsed = false;
            sidebar.style.setProperty('position', 'fixed');
            sidebar.style.setProperty('top', '0');
            sidebar.style.setProperty('bottom', '0');
            sidebar.style.setProperty('left', '0');
            sidebar.style.setProperty('right', 'auto');
            sidebar.style.setProperty('z-index', '9999');
            sidebar.style.setProperty('width', window.innerWidth <= 640 ? '100vw' : 'min(22rem, 92vw)');
            sidebar.style.setProperty('max-width', '100vw');
            labels.forEach(el => el.classList.remove('hidden'));
            if (headerTitle) headerTitle.classList.remove('hidden');

            sidebar.classList.remove('opacity-0', 'invisible', 'pointer-events-none', '-translate-x-full');
            sidebar.classList.add('opacity-100', 'visible', 'pointer-events-auto', 'translate-x-0');
            sidebar.style.transform = 'translateX(0)';
            document.body.classList.add('overflow-hidden');

            if (backdrop) {
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                backdrop.classList.add('opacity-100');
            }
        }

        function fecharSidebarMobile() {
            if (!sidebar) return;
            sidebar.classList.remove('opacity-100', 'visible', 'pointer-events-auto', 'translate-x-0');
            sidebar.classList.add('opacity-0', 'invisible', 'pointer-events-none', '-translate-x-full');
            sidebar.style.transform = 'translateX(-100%)';

            if (backdrop) {
                backdrop.classList.add('opacity-0', 'pointer-events-none');
                backdrop.classList.remove('opacity-100');
            }
            document.body.classList.remove('overflow-hidden');

            if (mobileHideTimer) {
                clearTimeout(mobileHideTimer);
            }
            mobileHideTimer = setTimeout(() => {
                if (isMobile()) {
                    sidebar.style.setProperty('display', 'none');
                }
            }, 220);
        }

        function setDesktopCollapsed(collapsed) {
            if (!sidebar) return;
            desktopCollapsed = collapsed;

            if (collapsed) {
                sidebar.style.width = 'clamp(3.5rem, 6vw, 4rem)';
                labels.forEach(el => el.classList.add('hidden'));
                if (headerTitle) headerTitle.classList.add('hidden');
            } else {
                sidebar.style.width = 'clamp(14rem, 18vw, 18rem)';
                labels.forEach(el => el.classList.remove('hidden'));
                if (headerTitle) headerTitle.classList.remove('hidden');
            }
        }

        if (btnToggleMob) {
            btnToggleMob.addEventListener('click', function () {
                if (!isMobile() || !sidebar) return;
                if (isSidebarOpenMobile()) {
                    fecharSidebarMobile();
                } else {
                    abrirSidebarMobile();
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

        if (sidebar && isMobile()) {
            sidebar.style.setProperty('display', 'none');
            fecharSidebarMobile();
        } else if (sidebar) {
            sidebar.style.setProperty('display', 'flex');
            sidebar.classList.remove('opacity-0', 'invisible', 'pointer-events-none', '-translate-x-full');
            sidebar.classList.add('opacity-100', 'visible', 'pointer-events-auto', 'translate-x-0');
            sidebar.style.removeProperty('transform');
            sidebar.style.removeProperty('position');
            sidebar.style.removeProperty('top');
            sidebar.style.removeProperty('bottom');
            sidebar.style.removeProperty('left');
            sidebar.style.removeProperty('right');
            sidebar.style.removeProperty('z-index');
            sidebar.style.removeProperty('max-width');
            sidebar.style.removeProperty('width');
            setDesktopCollapsed(false);
        }

        window.addEventListener('resize', function () {
            if (!sidebar) return;
            if (isMobile()) {
                setDesktopCollapsed(false);
                fecharSidebarMobile();
            } else {
                if (mobileHideTimer) {
                    clearTimeout(mobileHideTimer);
                    mobileHideTimer = null;
                }
                sidebar.style.setProperty('display', 'flex');
                sidebar.classList.remove('opacity-0', 'invisible', 'pointer-events-none', '-translate-x-full');
                sidebar.classList.add('opacity-100', 'visible', 'pointer-events-auto', 'translate-x-0');
                sidebar.style.removeProperty('transform');
                sidebar.style.removeProperty('position');
                sidebar.style.removeProperty('top');
                sidebar.style.removeProperty('bottom');
                sidebar.style.removeProperty('left');
                sidebar.style.removeProperty('right');
                sidebar.style.removeProperty('z-index');
                sidebar.style.removeProperty('max-width');
                sidebar.style.removeProperty('width');
                document.body.classList.remove('overflow-hidden');
                if (backdrop) {
                    backdrop.classList.add('opacity-0', 'pointer-events-none');
                    backdrop.classList.remove('opacity-100');
                }
            }
        });
    });
</script>
@stack('scripts')
</body>
</html>
