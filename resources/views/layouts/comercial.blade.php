<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Painel Comercial') - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="bg-slate-900 comercial-layout">
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
        {{-- BACKDROP (mobile) --}}
        <div id="comercial-sidebar-backdrop"
             class="fixed inset-0 bg-black/50 z-[9998] opacity-0 pointer-events-none transition-opacity duration-200 md:hidden"></div>

        {{-- Sidebar esquerda --}}
        <aside id="comercial-sidebar"
               class="fixed inset-y-0 left-0 z-[9999] w-64 bg-slate-950 text-slate-100 shadow-2xl
                      transform -translate-x-full transition-all duration-200 ease-in-out
                      opacity-0 invisible pointer-events-none
                      flex flex-col overflow-hidden
                      md:static md:translate-x-0 md:opacity-100 md:visible md:pointer-events-auto">

        <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
            <img src="{{ asset('storage/logo.svg') }}" alt="FORMED" class="w-40">
        </div>

        <div class="relative z-10 h-16 flex items-center justify-between px-4 border-b border-slate-800">

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

                <div class="flex flex-col leading-tight">
                    <span data-sidebar-label-header class="text-sm font-semibold text-slate-100">Comercial</span>
                    <span class="text-[11px] text-slate-500" data-sidebar-label>Modulo</span>
                </div>
            </div>

            {{-- Botão fechar (somente mobile) --}}
            <button type="button"
                    class="inline-flex items-center justify-center p-2 rounded-lg text-slate-300 hover:bg-slate-800 md:hidden"
                    data-sidebar-close>
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <nav class="relative z-10 flex-1 px-3 mt-4 space-y-1">
            @php
                $menuState = function (bool $enabled, bool $active = false): string {
                    if (!$enabled) {
                        return 'text-slate-600 cursor-not-allowed border-l-2 border-transparent';
                    }

                    if ($active) {
                        return 'bg-slate-800/80 text-white border-l-2 border-emerald-400';
                    }

                    return 'text-slate-300 hover:text-white hover:bg-slate-800/70 border-l-2 border-transparent';
                };
                $iconState = function (bool $enabled, bool $active = false): string {
                    if (!$enabled) {
                        return 'text-slate-700';
                    }

                    return $active ? 'text-emerald-300' : 'text-slate-400 group-hover:text-slate-200';
                };
            @endphp

            @php $canDashboard = $can('comercial.dashboard.view'); @endphp
            @php $activeDashboard = request()->routeIs('comercial.dashboard'); @endphp
            <a href="{{ $canDashboard ? route('comercial.dashboard') : 'javascript:void(0)' }}"
               @if(!$canDashboard) title="Usuário sem permissão" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $menuState($canDashboard, $activeDashboard) }}">
                <span class="{{ $iconState($canDashboard, $activeDashboard) }}">
                    <i class="fa-solid fa-chart-line w-4 text-center"></i>
                </span>
                <span data-sidebar-label>Painel Comercial</span>
            </a>

            @php $canComissoes = $can('comercial.comissoes.view'); @endphp
            @php $activeComissoes = request()->routeIs('comercial.comissoes.*'); @endphp
            <a href="{{ $canComissoes ? route('comercial.comissoes.index') : 'javascript:void(0)' }}"
               @if(!$canComissoes) title="Usuário sem permissão" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ $menuState($canComissoes, $activeComissoes) }}">
                <span class="{{ $iconState($canComissoes, $activeComissoes) }}">
                    <i class="fa-solid fa-coins w-4 text-center"></i>
                </span>
                <span data-sidebar-label>Minhas Comissões</span>
            </a>

            @php $canAgenda = $can('comercial.agenda.view'); @endphp
            @php $activeAgenda = request()->routeIs('comercial.agenda.*'); @endphp
            <a href="{{ $canAgenda ? route($isMaster ? 'master.agenda-vendedores.index' : 'comercial.agenda.index') : 'javascript:void(0)' }}"
               @if(!$canAgenda) title="Usuário sem permissão" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ $menuState($canAgenda, $activeAgenda) }}">
                <span class="{{ $iconState($canAgenda, $activeAgenda) }}">
                    <i class="fa-regular fa-calendar w-4 text-center"></i>
                </span>
                <span data-sidebar-label>Agenda</span>
            </a>

            @php $canPropostas = $can('comercial.propostas.view'); @endphp
            @php $activePropostas = request()->routeIs('comercial.propostas.*'); @endphp
            <a href="{{ $canPropostas ? route('comercial.propostas.index') : 'javascript:void(0)' }}"
               @if(!$canPropostas) title="Usuário sem permissão" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ $menuState($canPropostas, $activePropostas) }}">
                <span class="{{ $iconState($canPropostas, $activePropostas) }}">
                    <i class="fa-regular fa-file-lines w-4 text-center"></i>
                </span>
                <span data-sidebar-label>Propostas</span>
            </a>
            @php $canApresentacao = $can('comercial.propostas.view'); @endphp
            @php $activeApresentacao = request()->routeIs('comercial.apresentacao.*'); @endphp
            <a href="{{ $canApresentacao ? route('comercial.apresentacao.cliente') : 'javascript:void(0)' }}"
               @if(!$canApresentacao) title="Usuário sem permissão" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ $menuState($canApresentacao, $activeApresentacao) }}">
                <span class="{{ $iconState($canApresentacao, $activeApresentacao) }}">
                    <i class="fa-solid fa-display w-4 text-center"></i>
                </span>
                <span data-sidebar-label>Gerar Apresentação</span>
            </a>

            @php $canPipeline = $can('comercial.pipeline.view'); @endphp
            @php $activePipeline = request()->routeIs('comercial.pipeline.*'); @endphp
            <a href="{{ $canPipeline ? route('comercial.pipeline.index') : 'javascript:void(0)' }}"
               @if(!$canPipeline) title="Usuário sem permissão" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ $menuState($canPipeline, $activePipeline) }}">
                <span class="{{ $iconState($canPipeline, $activePipeline) }}">
                    <i class="fa-solid fa-chart-column w-4 text-center"></i>
                </span>
                <span data-sidebar-label>Acompanhamento</span>
            </a>

            @php $canTabela = $can('comercial.tabela-precos.view'); @endphp
            @php $activeTabela = request()->routeIs('comercial.tabela-precos.*'); @endphp
            <a href="{{ $canTabela ? route('comercial.tabela-precos.index') : 'javascript:void(0)' }}"
               @if(!$canTabela) title="Usuário sem permissão" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ $menuState($canTabela, $activeTabela) }}">
                <span class="{{ $iconState($canTabela, $activeTabela) }}">
                    <i class="fa-solid fa-tags w-4 text-center"></i>
                </span>
                <span data-sidebar-label>Tabela de Preços</span>
            </a>

            @php $canContratos = $can('comercial.contratos.view'); @endphp
            @php $activeContratos = request()->routeIs('comercial.contratos.*'); @endphp
            <a href="{{ $canContratos ? route('comercial.contratos.index') : 'javascript:void(0)' }}"
               @if(!$canContratos) title="Usuário sem permissão" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ $menuState($canContratos, $activeContratos) }}">
                <span class="{{ $iconState($canContratos, $activeContratos) }}">
                    <i class="fa-regular fa-folder-open w-4 text-center"></i>
                </span>
                <span data-sidebar-label>Contratos</span>
            </a>

            @php $canClientes = $can('comercial.clientes.view'); @endphp
            @php $activeClientes = request()->routeIs('comercial.clientes.*'); @endphp
            <a href="{{ $canClientes ? route('comercial.clientes.index') : 'javascript:void(0)' }}"
               @if(!$canClientes) title="Usuário sem permissão" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition {{ $menuState($canClientes, $activeClientes) }}">
                <span class="{{ $iconState($canClientes, $activeClientes) }}">
                    <i class="fa-regular fa-user w-4 text-center"></i>
                </span>
                <span data-sidebar-label>Clientes</span>
            </a>

        </nav>

        <div class="relative z-10 px-3 py-4 border-t border-slate-800 text-sm">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="group w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/70 transition">
                    <span class="text-slate-400 group-hover:text-slate-200">
                        <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
                    </span>
                    <span data-sidebar-label>Sair</span>
                </button>
            </form>
        </div>
        </aside>
    @endif

    {{-- Área principal --}}
    <div class="flex-1 min-h-screen flex flex-col bg-slate-50">

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
                    <div x-data="{ open: false }" class="relative">
                        <button type="button"
                                class="flex items-center gap-2 rounded-full bg-emerald-600/40 px-2.5 py-1.5 hover:bg-emerald-600/60 transition"
                                @click="open = !open"
                                @keydown.escape="open = false"
                                :aria-expanded="open.toString()"
                                aria-haspopup="true">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500 text-white text-xs font-semibold">
                                {{ \Illuminate\Support\Str::of(auth()->user()->name ?? 'U')->substr(0,1) }}
                            </span>
                            <span class="hidden lg:inline">
                                {{ auth()->user()->name ?? '' }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-100" fill="none"
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
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">🔒</span>
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

            {{-- Marca d’água com a logo da FORMED --}}
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
                <img src="{{ asset('storage/logo.svg') }}"
                     alt="FORMED"
                     class="max-w-[512px] w-full">
            </div>

            {{-- Conteúdo das telas comerciais fica por cima --}}
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
        let mobileHideTimer = null;

        let desktopCollapsed = false;

        function isMobile() {
            return window.innerWidth < MOBILE_BREAKPOINT;
        }

        function isSidebarOpenMobile() {
            if (!sidebar) return false;
            return sidebar.classList.contains('translate-x-0');
        }

        // --- MOBILE: abrir/fechar overlay ---
        function abrirSidebarMobile() {
            if (!sidebar) return;
            if (mobileHideTimer) {
                clearTimeout(mobileHideTimer);
                mobileHideTimer = null;
            }
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
            sidebar.classList.remove('opacity-0', 'invisible', 'pointer-events-none');
            sidebar.classList.add('opacity-100', 'visible', 'pointer-events-auto');
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            sidebar.style.transform = 'translateX(0)';
            document.body.classList.add('overflow-hidden');

            if (backdrop) {
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                backdrop.classList.add('opacity-100');
            }
        }

        function fecharSidebarMobile() {
            if (!sidebar) return;
            sidebar.classList.remove('opacity-100', 'visible', 'pointer-events-auto');
            sidebar.classList.add('opacity-0', 'invisible', 'pointer-events-none');
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
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
                    sidebar.classList.remove('translate-x-0');
                    sidebar.classList.add('-translate-x-full');
                }
            }, 220);
        }

        // --- DESKTOP: colapsar/expandir (ícones x texto) ---
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

        // Botão do header (MOBILE)
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

        if (sidebar) {
            sidebar.querySelectorAll('a[href]').forEach((link) => {
                link.addEventListener('click', function () {
                    const href = link.getAttribute('href') || '';
                    if (!isMobile() || href === '' || href.startsWith('javascript')) return;
                    fecharSidebarMobile();
                });
            });
        }

        // ESC fecha (mobile)
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isMobile()) {
                fecharSidebarMobile();
            }
        });

        // Estado inicial
        if (sidebar && isMobile()) {
            fecharSidebarMobile();
        } else if (sidebar) {
            sidebar.classList.remove('opacity-0', 'invisible', 'pointer-events-none');
            sidebar.classList.add('opacity-100', 'visible', 'pointer-events-auto');
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
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

        // Resize
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
                sidebar.classList.remove('opacity-0', 'invisible', 'pointer-events-none');
                sidebar.classList.add('opacity-100', 'visible', 'pointer-events-auto');
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
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

<script src="https://unpkg.com/currency.js@2.0.4/dist/currency.min.js"></script>


@stack('scripts')

</body>
</html>

