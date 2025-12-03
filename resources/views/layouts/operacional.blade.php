<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Painel Operacional') - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="bg-slate-900">
<div class="min-h-screen flex relative">

    {{-- BACKDROP (mobile) --}}
    <div id="operacional-sidebar-backdrop"
         class="fixed inset-0 bg-black/40 z-20 opacity-0 pointer-events-none transition-opacity duration-200 md:hidden"></div>

    {{-- Sidebar esquerda --}}
    <aside id="operacional-sidebar"
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
                    {{-- Chevron simples --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <span data-sidebar-label-header>Operacional</span>
            </div>

            {{-- Botão fechar (somente mobile) --}}
            <button type="button"
                    class="inline-flex items-center justify-center p-2 rounded-lg text-slate-300 hover:bg-slate-800 md:hidden"
                    data-sidebar-close>
                ✕
            </button>
        </div>

        <nav class="flex-1 px-3 mt-4 space-y-1">
            <a href="{{ route('operacional.kanban') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-800 text-slate-50 text-sm font-medium">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-700">
                    🗂️
                </span>
                <span data-sidebar-label>Painel Operacional</span>
            </a>
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

        <header class="bg-blue-900 text-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 md:px-6 h-14 flex items-center justify-between gap-3">

                <div class="flex items-center gap-3">
                    {{-- Botão abrir/fechar sidebar (MOBILE) --}}
                    <button type="button"
                            class="inline-flex md:hidden items-center justify-center p-2 rounded-lg text-blue-50 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-white"
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
                        <span class="text-[11px] md:text-xs text-blue-100">
                            Medicina e Segurança do Trabalho
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-xs md:text-sm text-blue-50">
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

            {{-- Conteúdo das telas operacionais fica por cima --}}
            <div class="relative z-10">
                @yield('content')
            </div>
        </main>
    </div>
</div>

{{-- SortableJS para drag & drop do Kanban --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const MOBILE_BREAKPOINT = 768;

            const sidebar       = document.getElementById('operacional-sidebar');
            const backdrop      = document.getElementById('operacional-sidebar-backdrop');
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
                    // encolhe a largura e esconde labels
                    sidebar.style.width = '4rem';
                    labels.forEach(el => el.classList.add('hidden'));
                    if (headerTitle) headerTitle.classList.add('hidden');
                } else {
                    // volta ao normal (usa w-64 do Tailwind)
                    sidebar.style.width = '';
                    labels.forEach(el => el.classList.remove('hidden'));
                    if (headerTitle) headerTitle.classList.remove('hidden');
                }
            }

            // --- Clique no botão do header (MOBILE) ---
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

            // --- Clique no botão de colapse (DESKTOP) ---
            if (btnCollapse) {
                btnCollapse.addEventListener('click', function () {
                    if (isMobile()) return;
                    setDesktopCollapsed(!desktopCollapsed);
                });
            }

            // Botões de fechar (só mobile, já estão md:hidden)
            btnCloses.forEach(btn => {
                btn.addEventListener('click', function () {
                    if (isMobile()) {
                        fecharSidebarMobile();
                    }
                });
            });

            // Clicar no backdrop fecha (mobile)
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
                setDesktopCollapsed(false); // começa expandido no desktop
            }

            // Se a tela for redimensionada, ajusta comportamento
            window.addEventListener('resize', function () {
                if (isMobile()) {
                    // Voltou para mobile: garante expandido internamente e fecha overlay
                    setDesktopCollapsed(false);
                    fecharSidebarMobile();
                } else {
                    // Voltou para desktop: garante que não fique com translate-x-full
                    sidebar.classList.remove('-translate-x-full', 'translate-x-0');
                    if (backdrop) {
                        backdrop.classList.add('opacity-0', 'pointer-events-none');
                        backdrop.classList.remove('opacity-100');
                    }
                }
            });

            // ------------------ MODAL DE FUNÇÃO (código original) ------------------ //

            function abrirModal(modalId, targetSelectId) {
                const modal = document.getElementById(modalId);
                if (!modal) return;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.dataset.funcaoTarget = targetSelectId;

                const input = modal.querySelector('[data-funcao-input]');
                const erro  = modal.querySelector('[data-funcao-error]');

                if (erro) {
                    erro.textContent = '';
                    erro.classList.add('hidden');
                }
                if (input) {
                    input.value = '';
                    input.focus();
                }
            }

            function fecharModal(modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.dataset.funcaoTarget = '';
            }

            // Clique no botão "+"
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-funcao-open-modal]');
                if (btn) {
                    const modalId   = btn.getAttribute('data-funcao-open-modal');
                    const targetSel = btn.getAttribute('data-funcao-target');
                    abrirModal(modalId, targetSel);
                }
            });

            // Eventos internos do modal (cancelar / salvar)
            document.addEventListener('click', function (e) {
                const modal = e.target.closest('[data-funcao-modal]');
                if (!modal) return;

                // Cancelar
                if (e.target.matches('[data-funcao-cancel]')) {
                    fecharModal(modal);
                    return;
                }

                // Salvar
                if (e.target.matches('[data-funcao-save]')) {
                    const input  = modal.querySelector('[data-funcao-input]');
                    const erroEl = modal.querySelector('[data-funcao-error]');
                    const nome   = (input?.value || '').trim();

                    if (!nome) {
                        if (erroEl) {
                            erroEl.textContent = 'Informe o nome da função.';
                            erroEl.classList.remove('hidden');
                        }
                        if (input) input.focus();
                        return;
                    }

                    const route    = modal.dataset.funcaoRoute;
                    const token    = modal.dataset.funcaoCsrf;
                    const targetId = modal.dataset.funcaoTarget;

                    if (!route || !token || !targetId) return;

                    const btnSave = e.target;
                    btnSave.disabled = true;

                    fetch(route, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ nome: nome })
                    })
                        .then(r => r.json())
                        .then(json => {
                            if (!json.ok) {
                                if (erroEl) {
                                    erroEl.textContent = json.message || 'Não foi possível salvar a função.';
                                    erroEl.classList.remove('hidden');
                                }
                                return;
                            }

                            const select = document.getElementById(targetId);
                            if (select) {
                                const opt = document.createElement('option');
                                opt.value = json.id;
                                opt.textContent = json.nome;
                                select.appendChild(opt);
                                select.value = json.id;
                            }

                            fecharModal(modal);
                        })
                        .catch(() => {
                            if (erroEl) {
                                erroEl.textContent = 'Erro na comunicação com o servidor.';
                                erroEl.classList.remove('hidden');
                            }
                        })
                        .finally(() => {
                            btnSave.disabled = false;
                        });
                }
            });

            // Fechar modal clicando no fundo
            document.addEventListener('click', function (e) {
                const modal = e.target.closest('[data-funcao-modal]');
                if (!modal) return;

                if (e.target === modal) {
                    fecharModal(modal);
                }
            });
        });
    </script>


@stack('scripts')

</body>
</html>
