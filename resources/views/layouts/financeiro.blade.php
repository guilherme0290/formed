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
    @php
        $authUser = auth()->user();
        $isMaster = $authUser?->isMaster();
        $permissionMap = $authUser?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $can = function (string $key) use ($isMaster, $permissionMap): bool {
            return $isMaster || isset($permissionMap[$key]);
        };
    @endphp

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
                        ['label' => 'Dashboard', 'icon' => 'ðŸ“Š', 'route' => route('financeiro.dashboard'), 'active' => request()->routeIs('financeiro.dashboard')],
                        ['label' => 'Contratos', 'icon' => 'ðŸ“„', 'route' => route('financeiro.contratos'), 'active' => request()->routeIs('financeiro.contratos*')],
                        ['label' => 'Contas a Receber', 'icon' => 'ðŸ’³', 'route' => route('financeiro.contas-receber'), 'active' => request()->routeIs('financeiro.contas-receber*')],
                        ['label' => 'Contas a Pagar', 'icon' => 'ðŸ’¸', 'route' => route('financeiro.contas-pagar.index'), 'active' => request()->routeIs('financeiro.contas-pagar*')],
                        ['label' => 'Fornecedores', 'icon' => 'ðŸ¢', 'route' => route('financeiro.fornecedores.index'), 'active' => request()->routeIs('financeiro.fornecedores*')],
                    ];
                @endphp
                @foreach($links as $link)
                    @php
                        $perm = match ($link['label']) {
                            'Dashboard' => 'financeiro.dashboard.view',
                            'Contratos' => 'financeiro.contratos.view',
                            default => 'financeiro.contas-receber.view',
                        };
                        $enabled = $can($perm);
                    @endphp
                    <a href="{{ $enabled ? $link['route'] : 'javascript:void(0)' }}"
                       @if(!$enabled) title="Usuário sem permissão" aria-disabled="true" @endif
                       class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm {{ $link['active'] && $enabled ? 'bg-indigo-600 text-white font-semibold' : ($enabled ? 'text-slate-200 hover:bg-slate-800' : 'text-slate-500 bg-slate-900 cursor-not-allowed') }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800">{{ $link['icon'] }}</span>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="relative z-10 px-4 py-4 border-t border-slate-900 space-y-2 text-sm">
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-slate-300 hover:text-white">
                    <span>âª</span><span>Voltar</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex items-center gap-2 text-rose-400 hover:text-rose-300">
                        <span>ðŸšª</span> Sair
                    </button>
                </form>
            </div>
        </aside>
    @endif

    {{-- Main --}}
    <div class="flex-1 flex flex-col bg-slate-50">
        <header class="bg-indigo-700 text-white shadow-sm">
            <div class="w-full px-4 md:px-6 h-16 flex items-center">
                <div class="flex items-center gap-3 w-1/3">
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
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">ðŸ”’</span>
                                <span>Alterar Senha</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-3 text-sm hover:bg-slate-50 text-left">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">ðŸšª</span>
                                    <span>Sair</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 relative overflow-hidden">
            <div class="relative z-10">
                <div class="@yield('page-container', 'w-full px-4 sm:px-6 lg:px-8 py-6')">
                    @if(session('error') || session('erro'))
                        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ session('error') ?? session('erro') }}
                        </div>
                    @endif
                    @yield('content')
                </div>
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

                if (desktopCollapsed) {
                    sidebar.style.width = 'clamp(3.5rem, 6vw, 4rem)';
                } else {
                    sidebar.style.width = 'clamp(14rem, 18vw, 18rem)';
                }
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

