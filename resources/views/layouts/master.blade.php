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

{{-- Configuracoes do painel (modal) --}}
<div id="dashboard-config-backdrop" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[80] hidden"></div>
<div id="dashboard-config-modal" class="fixed inset-0 z-[90] hidden items-center justify-center p-4 overflow-y-auto">
    <div class="w-full max-w-3xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between px-6 py-5 border-b border-slate-100">
            <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    ⚙️
                </div>
                <div>
                    <div class="text-lg font-semibold text-slate-900">Configurações do Painel Master</div>
                    <div class="text-sm text-slate-500">Personalize os indicadores exibidos no dashboard</div>
                </div>
            </div>
            <button type="button" class="h-9 w-9 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" data-config-close>×</button>
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
