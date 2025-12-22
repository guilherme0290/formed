<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Painel Comercial') - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="bg-slate-900">
<div class="min-h-screen flex relative">
    @php $isMaster = auth()->user()?->isMaster(); @endphp

    @if($isMaster)
        @include('layouts.partials.master-sidebar')
    @else
        {{-- BACKDROP (mobile) --}}
        <div id="comercial-sidebar-backdrop"
             class="fixed inset-0 bg-black/40 z-20 opacity-0 pointer-events-none transition-opacity duration-200 md:hidden"></div>

        {{-- Sidebar esquerda --}}
        <aside id="comercial-sidebar"
               class="fixed inset-y-0 left-0 z-30 w-64 bg-slate-950 text-slate-100
                      transform -translate-x-full transition-transform duration-200 ease-in-out
                      flex flex-col relative overflow-hidden
                      md:static md:translate-x-0">

        <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
            <img src="{{ asset('storage/logo.svg') }}" alt="FORMED" class="w-40">
        </div>

        <div class="relative z-10 h-16 flex items-center justify-between px-4 text-lg font-semibold border-b border-slate-800">

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

                <span data-sidebar-label-header>Comercial</span>
            </div>

            {{-- Botão fechar (somente mobile) --}}
            <button type="button"
                    class="inline-flex items-center justify-center p-2 rounded-lg text-slate-300 hover:bg-slate-800 md:hidden"
                    data-sidebar-close>
                ✕
            </button>
        </div>

        <nav class="relative z-10 flex-1 px-3 mt-4 space-y-1">
            {{-- Ajuste o nome da rota aqui se precisar --}}
            <a href="{{ route('comercial.dashboard') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-800 text-slate-50 text-sm font-medium">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-700">
                    📊
                </span>
                <span data-sidebar-label>Painel Comercial</span>
            </a>

            <a href="{{ route('comercial.comissoes.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl text-slate-200 hover:bg-slate-800 text-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500/20 text-orange-400">
                    $
                </span>
                <span data-sidebar-label>Minhas Comissões</span>
            </a>

            <a href="{{ route('comercial.agenda.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl text-slate-200 hover:bg-slate-800 text-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-200">
                    🗓️
                </span>
                <span data-sidebar-label>Agenda</span>
            </a>

            <a href="{{ route('comercial.propostas.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl text-slate-200 hover:bg-slate-800 text-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500/20 text-blue-200">
                    📄
                </span>
                <span data-sidebar-label>Propostas</span>
            </a>

            <a href="{{ route('comercial.pipeline.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl text-slate-200 hover:bg-slate-800 text-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/20 text-rose-200">
                    📈
                </span>
                <span data-sidebar-label>Acompanhamento</span>
            </a>

            <a href="{{ route('comercial.tabela-precos.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl text-slate-200 hover:bg-slate-800 text-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-green-500/20 text-green-200">
                    💰
                </span>
                <span data-sidebar-label>Tabela de Preços</span>
            </a>

            <a href="{{ route('comercial.contratos.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl text-slate-200 hover:bg-slate-800 text-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-500/20 text-purple-200">
                    📑
                </span>
                <span data-sidebar-label>Contratos</span>
            </a>

            <a href="{{ route('comercial.clientes.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl text-slate-200 hover:bg-slate-800 text-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/20 text-amber-200">
                    👤
                </span>
                <span data-sidebar-label>Clientes</span>
            </a>
        </nav>

        <div class="relative z-10 px-4 py-4 border-t border-slate-800 space-y-2 text-sm">
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
    @endif

    {{-- Área principal --}}
    <div class="flex-1 flex flex-col bg-slate-50">

        <header class="bg-emerald-700 text-white shadow-sm">
            <div class="w-full px-4 md:px-6 h-14 flex items-center justify-between gap-3">

                <div class="flex items-center gap-3">
                    {{-- Botão abrir/fechar sidebar (MOBILE) --}}
                    <button type="button"
                            class="inline-flex md:hidden items-center justify-center p-2 rounded-lg text-emerald-50 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-white"
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
                        <span class="text-[11px] md:text-xs text-emerald-100">
                            Módulo Comercial
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-xs md:text-sm text-emerald-50">
                    <span class="hidden md:inline">
                        {{ auth()->user()->name ?? '' }}
                    </span>
                </div>
            </div>
        </header>

        <main class="flex-1 relative overflow-hidden">

            {{-- Marca d’água com a logo da FORMED --}}
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
                <img src="{{ asset('storage/logo.svg') }}"
                     alt="FORMED"
                     class="max-w-[512px] w-full">
            </div>

            {{-- Conteúdo das telas comerciais fica por cima --}}
            <div class="relative z-10">
                @yield('content')
            </div>
        </main>
    </div>
</div>

{{-- Sortable ou outros scripts globais que você quiser reaproveitar --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const MOBILE_BREAKPOINT = 768;

        const sidebarId = @json($isMaster ? 'master-sidebar' : 'comercial-sidebar');
        const backdropId = @json($isMaster ? 'master-sidebar-backdrop' : 'comercial-sidebar-backdrop');

        const sidebar       = document.getElementById(sidebarId);
        const backdrop      = document.getElementById(backdropId);
        const btnToggleMob  = document.querySelector('[data-sidebar-toggle]');
        const btnCloses     = document.querySelectorAll('[data-sidebar-close]');
        const btnCollapse   = document.querySelector('[data-sidebar-collapse]');
        const labels        = document.querySelectorAll('[data-sidebar-label]');
        const headerTitle   = document.querySelector('[data-sidebar-label-header]');

        let desktopCollapsed = false;

        function isMobile() {
            return window.innerWidth < MOBILE_BREAKPOINT;
        }

        // --- MOBILE: abrir/fechar overlay ---
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

        // --- DESKTOP: colapsar/expandir (ícones x texto) ---
        function setDesktopCollapsed(collapsed) {
            if (!sidebar) return;
            desktopCollapsed = collapsed;

            if (collapsed) {
                sidebar.style.width = '4rem';
                labels.forEach(el => el.classList.add('hidden'));
                if (headerTitle) headerTitle.classList.add('hidden');
            } else {
                sidebar.style.width = '';
                labels.forEach(el => el.classList.remove('hidden'));
                if (headerTitle) headerTitle.classList.remove('hidden');
            }
        }

        // Botão do header (MOBILE)
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

        // Botão de colapse (DESKTOP)
        if (btnCollapse) {
            btnCollapse.addEventListener('click', function () {
                if (isMobile()) return;
                setDesktopCollapsed(!desktopCollapsed);
            });
        }

        // Botões de fechar (mobile)
        btnCloses.forEach(btn => {
            btn.addEventListener('click', function () {
                if (isMobile()) {
                    fecharSidebarMobile();
                }
            });
        });

        // Clique no backdrop fecha (mobile)
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                if (isMobile()) {
                    fecharSidebarMobile();
                }
            });
        }

        // ESC fecha (mobile)
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isMobile()) {
                fecharSidebarMobile();
            }
        });

        // Estado inicial
        if (isMobile()) {
            fecharSidebarMobile();
        } else {
            setDesktopCollapsed(false);
        }

        // Resize
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

<script src="https://unpkg.com/currency.js@2.0.4/dist/currency.min.js"></script>


@stack('scripts')

</body>
</html>
