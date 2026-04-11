<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Painel Operacional') - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="bg-slate-50">
<div class="min-h-screen md:flex relative overflow-x-hidden">
    @php
        $authUser = auth()->user();
        $isMaster = $authUser?->isMaster();
    @endphp

    @if($isMaster)
        @include('layouts.partials.master-sidebar')
    @else
        @include('layouts.partials.operacional-sidebar')
    @endif

    {{-- Área principal --}}
    <div class="flex-1 min-h-screen flex flex-col bg-slate-50">

        <header class="bg-blue-900 text-white shadow-sm">
            <div class="w-full px-4 md:px-6 h-14 flex items-center justify-between gap-3">


            <div class="flex items-center gap-3">
                    {{-- Botão abrir/fechar sidebar (MOBILE) --}}
                    <button type="button"
                            class="inline-flex items-center justify-center p-2 rounded-lg text-blue-50 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-white"
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
                    <div x-data="{ open: false }" class="relative">
                        <button type="button"
                                class="flex items-center gap-2 rounded-full bg-blue-600/40 px-2.5 py-1.5 hover:bg-blue-600/60 transition"
                                @click="open = !open"
                                @keydown.escape="open = false"
                                :aria-expanded="open.toString()"
                                aria-haspopup="true">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-500 text-white text-xs font-semibold">
                                {{ \Illuminate\Support\Str::of(auth()->user()->name ?? 'U')->substr(0,1) }}
                            </span>
                            <span class="hidden md:inline">
                                {{ auth()->user()->name ?? '' }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-100" fill="none"
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
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">&#x1F512;</span>
                                <span>Alterar Senha</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-3 text-sm hover:bg-slate-50 text-left">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">&#x1F6AA;</span>
                                    <span>Sair</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 relative bg-slate-50 overflow-x-hidden overflow-y-auto">

            {{-- Marca d'água com a logo da FORMED --}}
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

<div id="app-overlay-root" class="fixed inset-0 z-[20000] pointer-events-none"></div>

{{-- SortableJS para drag & drop do Kanban --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const flashOk = @json(session('ok'));
            const flashErr = @json(session('error') ?? session('erro'));
            const validationErrors = @json($errors->toArray());
            const uploadMaxFilesize = @json(ini_get('upload_max_filesize') ?: '2M');
            const resolveFileErrorPopup = (errors) => {
                const entries = Object.entries(errors || {});
                for (const [field, messages] of entries) {
                    const list = Array.isArray(messages) ? messages : [messages];
                    for (const rawMessage of list) {
                        const message = String(rawMessage || '').trim();
                        if (!message) continue;

                        const hasFileField = /(anexo|arquivo|documento|certificado|pgr_arquivo)/i.test(field);
                        const hasFileText = /(failed to upload|falhou no upload|upload|arquivo|documento|pdf|docx?|mimes?|mimetypes?|certificado)/i.test(message);

                        if (!hasFileField && !hasFileText) {
                            continue;
                        }

                        if (/failed to upload/i.test(message)) {
                            return {
                                title: 'Erro ao enviar arquivo',
                                message: `O upload do arquivo falhou. Verifique se ele nao ultrapassa o limite atual do servidor (${uploadMaxFilesize}).`,
                                icon: 'error',
                            };
                        }

                        if (/(nao pode|must not|deve ter no maximo|maximo|kilobytes|megabytes|max:)/i.test(message)) {
                            return {
                                title: 'Arquivo acima do limite',
                                message,
                                icon: 'error',
                            };
                        }

                        if (/(mimes?|mimetypes?|formato|deve ser um pdf|devem ser pdf|doc|docx|jpg|jpeg|png)/i.test(message)) {
                            return {
                                title: 'Formato de arquivo invalido',
                                message,
                                icon: 'error',
                            };
                        }

                        if (/(required|obrigat|necessario manter|anexe o arquivo)/i.test(message)) {
                            return {
                                title: 'Arquivo obrigatorio',
                                message,
                                icon: 'warning',
                            };
                        }

                        return {
                            title: 'Erro com arquivo',
                            message,
                            icon: 'error',
                        };
                    }
                }

                return null;
            };
            const fileValidationPopup = resolveFileErrorPopup(validationErrors);

            if (typeof window.uiAlert === 'function' && !window.__operacionalFlashShown) {
                window.__operacionalFlashShown = true;
                if (fileValidationPopup) {
                    document.querySelectorAll('[data-validation-summary="1"]').forEach((el) => {
                        el.classList.add('hidden');
                    });
                    window.uiAlert(fileValidationPopup.message, {
                        icon: fileValidationPopup.icon,
                        title: fileValidationPopup.title,
                    });
                } else if (flashOk) {
                    window.uiAlert(flashOk, { icon: 'success', title: 'Sucesso' });
                } else if (flashErr) {
                    window.uiAlert(flashErr);
                }
            }

            const sidebarId = @json($isMaster ? 'master-sidebar' : 'operacional-sidebar');
            const backdropId = @json($isMaster ? 'master-sidebar-backdrop' : 'operacional-sidebar-backdrop');

            const sidebar       = document.getElementById(sidebarId);
            const backdrop      = document.getElementById(backdropId);
            const btnToggleMob  = document.querySelector('[data-sidebar-toggle]');
            const btnCloses     = document.querySelectorAll('[data-sidebar-close]');
            const btnCollapse   = document.querySelector('[data-sidebar-collapse]');
            const labels        = document.querySelectorAll('[data-sidebar-label]');
            const headerTitle   = document.querySelector('[data-sidebar-label-header]');
            const submenuWraps  = document.querySelectorAll('[data-sidebar-children]');
            const submenuToggles = document.querySelectorAll('[data-sidebar-chevron]');
            const overlayRoot   = document.getElementById('app-overlay-root');
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

            function mountOverlayModals() {
                if (!overlayRoot) return;
                document.querySelectorAll('[data-overlay-root="true"]').forEach((modal) => {
                    if (!modal || modal.parentElement === overlayRoot) return;
                    modal.classList.add('pointer-events-auto');
                    overlayRoot.appendChild(modal);
                });
            }

            mountOverlayModals();

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
                sidebar.style.setProperty('right', 'auto');
                sidebar.style.setProperty('z-index', '9999');
                sidebar.style.setProperty('width', window.innerWidth <= 640 ? '100vw' : 'min(22rem, 92vw)');
                sidebar.style.setProperty('max-width', '100vw');
            }

            function isSidebarOpenMobile() {
                if (!sidebar) return false;
                return !sidebar.classList.contains('-translate-x-full');
            }

            function abrirSidebarMobile() {
                if (!sidebar) return;
                desktopCollapsed = false;
                applyMobileDrawerStyles();
                labels.forEach(el => el.classList.remove('hidden'));
                if (headerTitle) headerTitle.classList.remove('hidden');
                submenuWraps.forEach(el => el.classList.remove('hidden'));
                submenuToggles.forEach(el => el.classList.remove('hidden'));
                sidebar.classList.remove('opacity-0', 'invisible', 'pointer-events-none', '-translate-x-full', 'w-16');
                sidebar.classList.add('opacity-100', 'visible', 'pointer-events-auto', 'translate-x-0');
                sidebar.classList.add('w-64');
                document.body.classList.add('overflow-hidden');

                if (backdrop) {
                    backdrop.classList.remove('opacity-0', 'pointer-events-none');
                    backdrop.classList.add('opacity-100');
                }
            }

            function fecharSidebarMobile() {
                if (!sidebar) return;
                applyMobileDrawerStyles();
                sidebar.classList.remove('opacity-100', 'visible', 'pointer-events-auto', 'translate-x-0');
                sidebar.classList.add('opacity-0', 'invisible', 'pointer-events-none', '-translate-x-full');
                if (backdrop) {
                    backdrop.classList.add('opacity-0', 'pointer-events-none');
                    backdrop.classList.remove('opacity-100');
                }
                document.body.classList.remove('overflow-hidden');
            }

            // --- DESKTOP: colapsar/expandir (ícones x texto) ---

            function setDesktopCollapsed(collapsed) {
                if (!sidebar) return;
                desktopCollapsed = collapsed;

                if (collapsed) {
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-16');
                    labels.forEach(el => el.classList.add('hidden'));
                    submenuWraps.forEach(el => el.classList.add('hidden'));
                    submenuToggles.forEach(el => el.classList.add('hidden'));
                    if (headerTitle) headerTitle.classList.add('hidden');
                } else {
                    sidebar.classList.remove('w-16');
                    sidebar.classList.add('w-64');
                    labels.forEach(el => el.classList.remove('hidden'));
                    submenuWraps.forEach(el => el.classList.remove('hidden'));
                    submenuToggles.forEach(el => el.classList.remove('hidden'));
                    if (headerTitle) headerTitle.classList.remove('hidden');
                }
            }

            function syncSidebarState() {
                if (!sidebar) return;

                if (isMobile()) {
                    setDesktopCollapsed(false);
                    applyMobileDrawerStyles();
                    const isOpen = !sidebar.classList.contains('-translate-x-full')
                        && sidebar.classList.contains('visible')
                        && !sidebar.classList.contains('invisible');
                    if (isOpen) {
                        abrirSidebarMobile();
                    } else {
                        fecharSidebarMobile();
                    }
                    return;
                }

                sidebar.style.removeProperty('position');
                sidebar.style.removeProperty('top');
                sidebar.style.removeProperty('bottom');
                sidebar.style.removeProperty('left');
                sidebar.style.removeProperty('right');
                sidebar.style.removeProperty('z-index');
                sidebar.style.removeProperty('max-width');
                sidebar.style.removeProperty('width');

                sidebar.classList.remove('opacity-0', 'invisible', 'pointer-events-none', '-translate-x-full', 'translate-x-0');
                sidebar.classList.add('opacity-100', 'visible', 'pointer-events-auto');
                if (backdrop) {
                    backdrop.classList.add('opacity-0', 'pointer-events-none');
                    backdrop.classList.remove('opacity-100');
                }
                document.body.classList.remove('overflow-hidden');
                setDesktopCollapsed(desktopCollapsed);
            }

            // --- Clique no botão do header (MOBILE) ---
            if (btnToggleMob) {
                btnToggleMob.addEventListener('click', function () {
                    if (!sidebar) return;
                    const isHidden = sidebar.classList.contains('-translate-x-full')
                        || sidebar.classList.contains('invisible')
                        || sidebar.classList.contains('opacity-0');

                    if (isHidden) {
                        abrirSidebarMobile();
                        return;
                    }
                    fecharSidebarMobile();
                });
            }

            // --- Clique no botão de colapse (DESKTOP) ---
            if (btnCollapse) {
                btnCollapse.addEventListener('click', function () {
                    if (isMobile()) return;
                    setDesktopCollapsed(!desktopCollapsed);
                });
            }

            // Botões de fechar (só mobile, já estão lg:hidden)
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
                    fecharSidebarMobile();
                });
            }

            // ESC fecha (mobile)
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    fecharSidebarMobile();
                }
            });

            if (sidebar) {
                sidebar.querySelectorAll('a[href]').forEach((link) => {
                    link.addEventListener('click', () => {
                        if (!isMobile()) return;
                        fecharSidebarMobile();
                    });
                });
            }

            syncSidebarState();
            window.addEventListener('resize', syncSidebarState);
            window.addEventListener('orientationchange', syncSidebarState);
            window.visualViewport?.addEventListener('resize', syncSidebarState);
            const mediaMobile = window.matchMedia('(max-width: 1279.98px)');
            mediaMobile.addEventListener?.('change', () => {
                syncSidebarState();
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
