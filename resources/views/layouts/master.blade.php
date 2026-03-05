<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Painel Master') - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="bg-slate-50">
<div class="min-h-screen md:flex relative overflow-x-hidden">

    @include('layouts.partials.master-sidebar')

    {{-- Área principal --}}
    <div class="flex-1 min-h-screen min-w-0 flex flex-col bg-slate-50">

        <header class="bg-indigo-700 text-white shadow-sm sticky top-0 z-20">
            <div class="w-full px-4 md:px-6 h-16 flex items-center justify-between gap-3 py-2">

                <div class="flex min-w-0 items-center gap-3">
                    {{-- Botão abrir/fechar sidebar (MOBILE) --}}
                    <button type="button"
                            class="inline-flex items-center justify-center p-2 rounded-lg text-indigo-50 hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-white"
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

                <div class="flex min-w-0 items-center gap-3 text-xs md:text-sm text-indigo-50">
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
                            <span class="hidden md:inline max-w-[14rem] truncate">
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
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><i class="fa-solid fa-key"></i></span>
                                <span>Alterar Senha</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-3 text-sm hover:bg-slate-50 text-left">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><i class="fa-solid fa-right-from-bracket"></i></span>
                                    <span>Sair</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 min-w-0 relative overflow-x-hidden overflow-y-auto">

            {{-- Marca d'água --}}
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.05]">
                <img src="{{ asset('storage/logo.svg') }}"
                     alt="FORMED"
                     class="max-w-[512px] w-full">
            </div>

            <div class="relative z-10 min-w-0">
                @yield('content')
            </div>
        </main>
    </div>
</div>

{{-- Configurações do painel (modal) --}}
<div id="dashboard-config-backdrop" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[80] hidden"></div>
<div id="dashboard-config-modal" class="fixed inset-0 z-[90] hidden items-center justify-center p-4 overflow-y-auto">
    <div class="w-full max-w-3xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between px-6 py-5 border-b border-slate-100">
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <div>
                    <div class="text-lg font-semibold text-slate-900">Configurações do Painel Master</div>
                    <div class="text-sm text-slate-500">Personalize os indicadores exibidos no dashboard</div>
                </div>
            </div>
            <button type="button" class="h-9 w-9 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" data-config-close>&times;</button>
        </div>
        <div class="p-6 space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
                    <div class="text-sm font-semibold text-slate-900">Resumo Geral</div>
                    <label class="flex items-start justify-between gap-3 text-sm text-slate-700">
                        <span>
                            <span class="font-medium">Mostrar resumo de faturamento</span>
                            <span class="block text-xs text-slate-500">Card com total financeiro global</span>
                        </span>
                        <input type="checkbox" class="h-5 w-9 accent-indigo-600" data-dashboard-toggle="faturamento-global">
                    </label>
                    <label class="flex items-start justify-between gap-3 text-sm text-slate-700">
                        <span>
                            <span class="font-medium">Mostrar resumo de serviços consumidos</span>
                            <span class="block text-xs text-slate-500">Total de itens utilizados</span>
                        </span>
                        <input type="checkbox" class="h-5 w-9 accent-indigo-600" data-dashboard-toggle="servicos-consumidos">
                    </label>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
                    <div class="text-sm font-semibold text-slate-900">Financeiro</div>
                    <label class="flex items-start justify-between gap-3 text-sm text-slate-700">
                        <span>
                            <span class="font-medium">Faturamento pendente</span>
                            <span class="block text-xs text-slate-500">Total em aberto no per&iacute;odo</span>
                        </span>
                        <input type="checkbox" class="h-5 w-9 accent-indigo-600" data-dashboard-toggle="financeiro-pendente">
                    </label>
                    <label class="flex items-start justify-between gap-3 text-sm text-slate-700">
                        <span>
                            <span class="font-medium">Faturamento recebido</span>
                            <span class="block text-xs text-slate-500">Total recebido no per&iacute;odo</span>
                        </span>
                        <input type="checkbox" class="h-5 w-9 accent-indigo-600" data-dashboard-toggle="financeiro-recebido">
                    </label>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
                    <div class="text-sm font-semibold text-slate-900">Indicadores Operacionais</div>
                    <label class="flex items-start justify-between gap-3 text-sm text-slate-700">
                        <span>
                            <span class="font-medium">Card de clientes ativos</span>
                            <span class="block text-xs text-slate-500">Clientes ativos no período</span>
                        </span>
                        <input type="checkbox" class="h-5 w-9 accent-indigo-600" data-dashboard-toggle="clientes-ativos">
                    </label>
                    <label class="flex items-start justify-between gap-3 text-sm text-slate-700">
                        <span>
                            <span class="font-medium">Card de tempo médio</span>
                            <span class="block text-xs text-slate-500">Tempo médio operacional</span>
                        </span>
                        <input type="checkbox" class="h-5 w-9 accent-indigo-600" data-dashboard-toggle="tempo-medio">
                    </label>
                    <label class="flex items-start justify-between gap-3 text-sm text-slate-700">
                        <span>
                            <span class="font-medium">Card de agendamentos do dia</span>
                            <span class="block text-xs text-slate-500">Abertas e fechadas hoje</span>
                        </span>
                        <input type="checkbox" class="h-5 w-9 accent-indigo-600" data-dashboard-toggle="agendamentos-dia">
                    </label>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
                    <div class="text-sm font-semibold text-slate-900">Relatórios</div>
                    <label class="flex items-start justify-between gap-3 text-sm text-slate-700">
                        <span>
                            <span class="font-medium">Card de relatórios master</span>
                            <span class="block text-xs text-slate-500">Atalho para relatórios</span>
                        </span>
                        <input type="checkbox" class="h-5 w-9 accent-indigo-600" data-dashboard-toggle="relatorios-master">
                    </label>
                    <label class="flex items-start justify-between gap-3 text-sm text-slate-700">
                        <span>
                            <span class="font-medium">Relatórios avançados</span>
                            <span class="block text-xs text-slate-500">Bloco de métricas avançadas</span>
                        </span>
                        <input type="checkbox" class="h-5 w-9 accent-indigo-600" data-dashboard-toggle="relatorios-avancados">
                    </label>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
                    <div class="text-sm font-semibold text-slate-900">Outras Configurações</div>
                    <div class="text-sm text-slate-600">Gerencie opções avançadas do sistema</div>
                    <a href="{{ route('master.email-caixas.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">
                        Configurar e-mails
                    </a>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-white">
            <button type="button" class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 hover:bg-slate-50" data-config-close>Cancelar</button>
            <button type="button" class="px-5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-500" data-config-close data-dashboard-save>Salvar alterações</button>
        </div>
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
        const submenuWraps  = document.querySelectorAll('[data-sidebar-children]');
        const submenuToggles = document.querySelectorAll('[data-sidebar-chevron]');
        const userAgent = String(window.navigator?.userAgent || '');
        const viewportW = Math.max(window.screen?.width || 0, window.screen?.height || 0);
            const viewportH = Math.min(window.screen?.width || 0, window.screen?.height || 0);
            const isNestViewport = (viewportW === 1280 && viewportH === 800) || (viewportW === 1024 && viewportH === 600);
            const isNestDevice = /CrKey|Fuchsia|Android.*wv/i.test(userAgent) || isNestViewport;

        let desktopCollapsed = false;

        if (isNestDevice) {
            sidebar?.classList.remove('lg:static', 'lg:translate-x-0', 'lg:opacity-100', 'lg:visible', 'lg:pointer-events-auto');
            backdrop?.classList.remove('lg:hidden');
            btnToggleMob?.classList.remove('lg:hidden');
            btnCloses.forEach((btn) => btn.classList.remove('lg:hidden'));
        }

        function isMobile() {
            const isTouch = window.matchMedia('(pointer: coarse)').matches || window.matchMedia('(hover: none)').matches;
            const byWidth = window.matchMedia('(max-width: 1279.98px)').matches;
            return isNestDevice || isTouch || byWidth;
        }

        function applyMobileDrawerStyles() {
            if (!sidebar) return;
            sidebar.style.setProperty('position', 'fixed');
            sidebar.style.setProperty('top', '0');
            sidebar.style.setProperty('bottom', '0');
            sidebar.style.setProperty('left', '0');
            sidebar.style.setProperty('width', window.innerWidth <= 640 ? '100vw' : 'min(22rem, 92vw)');
            sidebar.style.setProperty('max-width', '100vw');
            sidebar.style.setProperty('z-index', '9999');
        }

        function setDesktopCollapsed(collapsed) {
            if (!sidebar) return;
            desktopCollapsed = collapsed;
            sidebar.classList.toggle('w-64', !collapsed);
            sidebar.classList.toggle('w-16', collapsed);
            labels.forEach(l => l.classList.toggle('hidden', collapsed));
            submenuWraps.forEach(el => el.classList.toggle('hidden', collapsed));
            submenuToggles.forEach(el => el.classList.toggle('hidden', collapsed));
            if (headerTitle) headerTitle.textContent = collapsed ? 'M' : 'Master';
        }

        function isSidebarOpenMobile() {
            if (!sidebar) return false;
            return !sidebar.classList.contains('-translate-x-full');
        }

        function openSidebar() {
            if (!sidebar) return;
            if (isMobile()) {
                // No mobile sempre abre expandido como drawer.
                setDesktopCollapsed(false);
                applyMobileDrawerStyles();
                document.body.classList.add('overflow-hidden');
            }

            backdrop?.classList.remove('opacity-0', 'pointer-events-none');
            sidebar.classList.remove('-translate-x-full', 'opacity-0', 'invisible', 'pointer-events-none');
            sidebar.classList.add('translate-x-0', 'opacity-100', 'visible', 'pointer-events-auto');
        }

        function closeSidebar() {
            if (!sidebar) return;
            if (isMobile()) {
                applyMobileDrawerStyles();
            }
            sidebar.classList.remove('translate-x-0', 'opacity-100', 'visible', 'pointer-events-auto');
            sidebar.classList.add('-translate-x-full', 'opacity-0', 'invisible', 'pointer-events-none');
            backdrop?.classList.add('opacity-0', 'pointer-events-none');
            document.body.classList.remove('overflow-hidden');
        }

        function syncSidebarState() {
            if (!sidebar) return;
            if (isMobile()) {
                // Força modo drawer no mobile, evitando ocupar espaço no layout.
                applyMobileDrawerStyles();
                const isOpen = !sidebar.classList.contains('-translate-x-full')
                    && sidebar.classList.contains('visible')
                    && !sidebar.classList.contains('invisible');
                if (isOpen) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
                return;
            }

            // Desktop: sidebar sempre visível, sem backdrop.
            sidebar.style.removeProperty('position');
            sidebar.style.removeProperty('top');
            sidebar.style.removeProperty('bottom');
            sidebar.style.removeProperty('left');
            sidebar.style.removeProperty('width');
            sidebar.style.removeProperty('max-width');

            sidebar.classList.remove('-translate-x-full', 'translate-x-0', 'opacity-0', 'invisible', 'pointer-events-none');
            sidebar.classList.add('opacity-100', 'visible', 'pointer-events-auto');
            backdrop?.classList.add('opacity-0', 'pointer-events-none');
            document.body.classList.remove('overflow-hidden');
            setDesktopCollapsed(desktopCollapsed);
        }

        btnToggleMob?.addEventListener('click', () => {
            if (!sidebar) return;
            const isHidden = sidebar.classList.contains('-translate-x-full')
                || sidebar.classList.contains('invisible')
                || sidebar.classList.contains('opacity-0');
            if (isHidden) {
                openSidebar();
            } else {
                closeSidebar();
            }
        });
        btnCloses.forEach(btn => btn.addEventListener('click', closeSidebar));
        backdrop?.addEventListener('click', closeSidebar);
        sidebar?.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', () => {
                if (!isMobile()) return;
                closeSidebar();
            });
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        btnCollapse?.addEventListener('click', () => {
            if (isMobile()) return;
            setDesktopCollapsed(!desktopCollapsed);
        });

        syncSidebarState();
        window.addEventListener('resize', syncSidebarState);
        window.addEventListener('orientationchange', syncSidebarState);
        window.visualViewport?.addEventListener('resize', syncSidebarState);
        const mediaMobile = window.matchMedia('(max-width: 1023.98px)');
        mediaMobile.addEventListener?.('change', () => {
            syncSidebarState();
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const configBtn = document.querySelector('[data-dashboard-config]');
        const modal = document.getElementById('dashboard-config-modal');
        const backdrop = document.getElementById('dashboard-config-backdrop');
        const closeBtns = document.querySelectorAll('[data-config-close]');
        const toggles = Array.from(document.querySelectorAll('[data-dashboard-toggle]'));
        const saveBtn = document.querySelector('[data-dashboard-save]');
        const preferencesUrl = '{{ route('master.dashboard-preferences.show') }}';
        const saveUrl = '{{ route('master.dashboard-preferences.update') }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let currentState = {};

        function applyState(state) {
            toggles.forEach((toggle) => {
                const key = toggle.getAttribute('data-dashboard-toggle');
                const isVisible = state[key] !== false;
                toggle.checked = isVisible;
                const el = document.querySelector(`[data-dashboard-card="${key}"]`);
                if (el) {
                    el.classList.toggle('hidden', !isVisible);
                }
            });
        }

        async function loadState() {
            try {
                const response = await fetch(preferencesUrl, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!response.ok) {
                    return {};
                }
                const payload = await response.json();
                return payload.visibility || {};
            } catch (e) {
                return {};
            }
        }

        async function saveState(state) {
            try {
                await fetch(saveUrl, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    body: JSON.stringify({ visibility: state }),
                });
            } catch (e) {
                // ignore
            }
        }

        function openModal() {
            if (!modal || !backdrop) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            backdrop.classList.remove('hidden');
        }

        function closeModal() {
            if (!modal || !backdrop) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            backdrop.classList.add('hidden');
        }

        if (configBtn) {
            configBtn.addEventListener('click', openModal);
        }
        closeBtns.forEach((btn) => btn.addEventListener('click', closeModal));
        backdrop?.addEventListener('click', closeModal);

        loadState().then((state) => {
            currentState = state || {};
            applyState(currentState);
        });

        toggles.forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const key = toggle.getAttribute('data-dashboard-toggle');
                currentState = { ...currentState, [key]: toggle.checked };
                applyState(currentState);
            });
        });

        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                saveState(currentState);
            });
        }
    });
</script>
<script src="https://unpkg.com/currency.js@2.0.4/dist/currency.min.js"></script>

@stack('scripts')
</body>
</html>
