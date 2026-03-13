<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Portal do Cliente') - Formed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- opcional, mas útil pro JS/AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer">

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>

<body class="font-sans antialiased bg-slate-50 text-slate-800">

<div class="min-h-screen flex">

    @php
        $clienteSidebar = $cliente ?? null;
        if (!$clienteSidebar) {
            $clienteSidebarId = (int) (session('portal_cliente_id') ?: (auth()->user()->cliente_id ?? 0));
            if ($clienteSidebarId > 0) {
                $clienteSidebar = \App\Models\Cliente::query()->find($clienteSidebarId);
            }
        }

        $temTabelaSidebar = $temTabela ?? false;
        $precosSidebar = $precos ?? [];
        $contratoAtivoSidebar = $contratoAtivo ?? null;
        $servicosContratoSidebar = $servicosContrato ?? [];
        $servicosIdsSidebar = $servicosIds ?? [];

        if (!$contratoAtivoSidebar && $clienteSidebar) {
            $contratoAtivoSidebar = app(\App\Services\ContratoClienteService::class)
                ->getContratoAtivo((int) $clienteSidebar->id, (int) $clienteSidebar->empresa_id, null);
            if ($contratoAtivoSidebar && !$contratoAtivoSidebar->relationLoaded('itens')) {
                $contratoAtivoSidebar->load('itens');
            }
        }

        if (empty($servicosContratoSidebar) && $contratoAtivoSidebar) {
            $servicosContratoSidebar = $contratoAtivoSidebar->itens
                ->pluck('servico_id')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (empty($servicosIdsSidebar) && $clienteSidebar) {
            $servicosMap = [
                'aso' => ['aso'],
                'pgr' => ['pgr'],
                'pcmso' => ['pcmso'],
                'ltcat' => ['ltcat'],
                'apr' => ['apr'],
                'treinamentos' => ['treinamentos nrs', 'treinamentos nr'],
            ];
            $servicosEmpresa = \App\Models\Servico::query()
                ->where('empresa_id', (int) $clienteSidebar->empresa_id)
                ->get(['id', 'nome']);

            foreach ($servicosMap as $slug => $nomes) {
                $servico = $servicosEmpresa->first(function ($row) use ($nomes) {
                    $nome = mb_strtolower(trim((string) $row->nome));
                    return in_array($nome, $nomes, true);
                });
                $servicosIdsSidebar[$slug] = $servico?->id ? (int) $servico->id : null;
            }
        }

        $temContratoAtivoSidebar = (bool) $contratoAtivoSidebar;
        $permitidosSidebar = [
            'aso' => $temContratoAtivoSidebar && in_array($servicosIdsSidebar['aso'] ?? null, $servicosContratoSidebar),
            'pgr' => $temContratoAtivoSidebar && in_array($servicosIdsSidebar['pgr'] ?? null, $servicosContratoSidebar),
            'pcmso' => $temContratoAtivoSidebar && in_array($servicosIdsSidebar['pcmso'] ?? null, $servicosContratoSidebar),
            'ltcat' => $temContratoAtivoSidebar && in_array($servicosIdsSidebar['ltcat'] ?? null, $servicosContratoSidebar),
            'apr' => $temContratoAtivoSidebar && in_array($servicosIdsSidebar['apr'] ?? null, $servicosContratoSidebar),
            'treinamentos' => $temContratoAtivoSidebar && in_array($servicosIdsSidebar['treinamentos'] ?? null, $servicosContratoSidebar),
        ];

        $linksRapidos = [
            [
                'titulo' => 'Painel do Cliente',
                'icone' => 'fa-solid fa-house',
                'rota' => route('cliente.dashboard'),
                'disabled' => false,
                'active' => request()->routeIs('cliente.dashboard'),
            ],
            [
                'titulo' => 'Funcionários',
                'icone' => 'fa-regular fa-user',
                'rota' => route('cliente.funcionarios.index'),
                'disabled' => false,
                'active' => request()->routeIs('cliente.funcionarios.*'),
            ],
            [
                'titulo' => 'Agendar ASO',
                'icone' => 'fa-regular fa-calendar',
                'rota' => route('cliente.servicos.aso'),
                'disabled' => !($permitidosSidebar['aso'] ?? false),
                'active' => request()->routeIs('cliente.servicos.aso'),
            ],
            [
                'titulo' => 'Solicitar PGR',
                'icone' => 'fa-regular fa-file-lines',
                'rota' => route('cliente.servicos.pgr'),
                'disabled' => !($permitidosSidebar['pgr'] ?? false),
                'active' => request()->routeIs('cliente.servicos.pgr'),
            ],
            [
                'titulo' => 'Solicitar PCMSO',
                'icone' => 'fa-regular fa-folder-open',
                'rota' => route('cliente.servicos.pcmso'),
                'disabled' => !($permitidosSidebar['pcmso'] ?? false),
                'active' => request()->routeIs('cliente.servicos.pcmso'),
            ],
            [
                'titulo' => 'Solicitar LTCAT',
                'icone' => 'fa-regular fa-file',
                'rota' => route('cliente.servicos.ltcat'),
                'disabled' => !($permitidosSidebar['ltcat'] ?? false),
                'active' => request()->routeIs('cliente.servicos.ltcat'),
            ],
            [
                'titulo' => 'Solicitar APR',
                'icone' => 'fa-solid fa-triangle-exclamation',
                'rota' => route('cliente.servicos.apr'),
                'disabled' => !($permitidosSidebar['apr'] ?? false),
                'active' => request()->routeIs('cliente.servicos.apr'),
            ],
            [
                'titulo' => 'Treinamentos',
                'icone' => 'fa-solid fa-graduation-cap',
                'rota' => route('cliente.servicos.treinamentos'),
                'disabled' => !($permitidosSidebar['treinamentos'] ?? false),
                'active' => request()->routeIs('cliente.servicos.treinamentos'),
            ],
            [
                'titulo' => 'Meus Arquivos',
                'icone' => 'fa-regular fa-folder-open',
                'rota' => route('cliente.arquivos.index'),
                'disabled' => false,
                'active' => request()->routeIs('cliente.arquivos.*'),
            ],
        ];

        $menuState = function (bool $active = false, bool $disabled = false): string {
            if ($disabled) {
                return 'text-slate-500 border-l-2 border-transparent cursor-not-allowed bg-slate-900/20';
            }
            if ($active) {
                return 'bg-slate-800/80 text-white border-l-2 border-emerald-400';
            }

            return 'text-slate-300 hover:text-white hover:bg-slate-800/70 border-l-2 border-transparent';
        };

        $iconState = function (bool $active = false, bool $disabled = false): string {
            if ($disabled) {
                return 'text-slate-600';
            }

            return $active ? 'text-emerald-300' : 'text-slate-400 group-hover:text-slate-200';
        };
    @endphp

    <div id="cliente-sidebar-backdrop"
         class="fixed inset-0 bg-black/50 z-[60] opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden"></div>

    <aside id="cliente-sidebar"
           class="fixed inset-y-0 left-0 z-[70] w-64 bg-slate-950 text-slate-100 shadow-2xl
                  transform -translate-x-full transition-all duration-200 ease-in-out
                  opacity-0 invisible pointer-events-none
                  flex flex-col relative overflow-hidden lg:static lg:translate-x-0 lg:opacity-100 lg:visible lg:pointer-events-auto">
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
            <img src="{{ asset('storage/logo.svg') }}" alt="FORMED" class="w-40">
        </div>

        <div class="relative z-10 h-16 flex items-center justify-between px-4 border-b border-slate-800">
            <div class="flex items-center gap-2">
                <button type="button"
                        class="hidden lg:inline-flex items-center justify-center p-1.5 rounded-lg text-slate-300 hover:bg-slate-800"
                        data-sidebar-collapse
                        title="Recolher/expandir">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <div class="flex flex-col leading-tight">
                    <span data-sidebar-label-header class="text-sm font-semibold text-slate-100">Cliente</span>
                    <span class="text-[11px] text-slate-500" data-sidebar-label>Módulo</span>
                </div>
            </div>

            <button type="button"
                    class="inline-flex items-center justify-center p-2 rounded-lg text-slate-300 hover:bg-slate-800 lg:hidden"
                    data-sidebar-close>
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <nav class="relative z-10 flex-1 px-3 mt-4 space-y-1 overflow-y-auto">
            @foreach($linksRapidos as $link)
                @php
                    $disabled = $link['disabled'] ?? false;
                    $active = $link['active'] ?? false;
                    $hint = $disabled ? 'Servico nao disponivel no contrato ativo.' : null;
                    $href = $disabled ? 'javascript:void(0)' : $link['rota'];
                @endphp
                <a href="{{ $href }}"
                   @if($hint) title="{{ $hint }}" aria-disabled="true" @endif
                   class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition min-w-0 {{ $menuState($active, $disabled) }}">
                    <span class="{{ $iconState($active, $disabled) }}">
                        <i class="{{ $link['icone'] }} w-4 text-center"></i>
                    </span>
                    <span data-sidebar-label class="truncate">{{ $link['titulo'] }}</span>
                </a>
            @endforeach
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

    {{-- COLUNA DA DIREITA (Header + Faixa + Conteúdo) --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- TOP BAR IGUAL AO OPERACIONAL (mesmo azul) --}}
        <header class="bg-blue-900 text-white shadow-sm">
            <div class="w-full px-3 sm:px-6 lg:px-8 min-h-14 py-2 flex items-center justify-between gap-2">

                {{-- Lado esquerdo: FORMED + subtítulo --}}
                <div class="flex items-center gap-2 min-w-0">
                    <button type="button"
                            data-sidebar-toggle
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-300/60 text-blue-50 hover:bg-white/10 transition"
                            aria-label="Abrir menu">
                        &#9776;
                    </button>
                    <span class="font-semibold text-base sm:text-lg tracking-tight shrink-0">FORMED</span>
                    <span class="hidden sm:block text-xs md:text-sm text-blue-100 truncate">
                        Medicina e Segurança do Trabalho
                    </span>
                </div>

                {{-- Lado direito: botao trocar --}}
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1 rounded-full border border-blue-300/60 px-2.5 sm:px-3 py-1 text-[10px] sm:text-[11px] font-medium text-blue-50 hover:bg-white/10 hover:text-white transition shrink-0">
                            Trocar usuário
                        </button>
                    </form>
                @endauth

            </div>
        </header>

        {{-- FAIXA AZUL DO CLIENTE --}}
        @isset($cliente)
            @php
                $razaoOuFantasia = $cliente->nome_fantasia ?: $cliente->razao_social;
                $documentoFormatado = $cliente->documento_principal ?? '';
                $documentoLabel = $cliente->documento_label ?? 'Documento';
                $contatoNome     = optional($cliente->vendedor)->name ?? 'Comercial nao informado';
                $contatoTelefone = optional($cliente->vendedor)->telefone ?? '(00) 0000-0000';
                $contatoEmail    = optional($cliente->vendedor)->email ?? 'email@dominio.com';
            @endphp

            <section class="w-full bg-[#1450d2] text-white shadow-lg shadow-slate-900/20 py-4 md:py-6">
                <div class="w-full px-3 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-6">

                    {{-- Nome do cliente + documento --}}
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl bg-white/10 flex items-center justify-center text-xl">
                            <span>&#127970;</span>
                        </div>
                        <div>
                            <h1 class="text-base sm:text-lg md:text-xl font-semibold leading-tight">
                                {{ $razaoOuFantasia }}
                            </h1>

                            @if($documentoFormatado)
                                <p class="text-xs md:text-sm text-blue-100 mt-1">
                                    {{ $documentoLabel }}: {{ $documentoFormatado }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Dados de contato --}}
                    <div class="text-xs md:text-sm text-blue-50">
                        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:flex md:items-center md:justify-end md:gap-6 lg:gap-12">

                            <div class="md:mr-10 lg:mr-16">
                                <span class="uppercase text-[10px] tracking-[0.18em] text-blue-100/70 block">
                                    Contato
                                </span>
                                <span class="font-medium">{{ $contatoNome }}</span>
                            </div>

                            <div class="md:mr-10 lg:mr-16">
                                <span class="uppercase text-[10px] tracking-[0.18em] text-blue-100/70 block">
                                    Telefone
                                </span>
                                <span class="font-medium">{{ $contatoTelefone }}</span>
                            </div>

                            <div class="col-span-2 md:col-span-1">
                                <span class="uppercase text-[10px] tracking-[0.18em] text-blue-100/70 block">
                                    E-mail
                                </span>
                                <span class="font-medium break-all">{{ $contatoEmail }}</span>
                            </div>

                        </div>
                    </div>

                </div>
            </section>
        @endisset
        {{-- FIM FAIXA CLIENTE --}}

        {{-- ALERTAS --}}
        {{-- CONTEUDO COM MARCA D'AGUA (IGUAL ESTAVA) --}}
        <main class="flex-1 relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
                <img src="{{ asset('storage/logo.svg') }}"
                     alt="FORMED"
                     class="max-w-[512px] w-full">
            </div>

            <div class="relative z-10">
                <div class="@yield('page-container', 'w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6')">
                    @yield('content')
                </div>
            </div>
        </main>

    </div>
</div>

<div id="app-overlay-root" class="fixed inset-0 z-[20000] pointer-events-none"></div>

@php($showLgpdModal = (auth()->check() && auth()->user()->isCliente() && !auth()->user()->lgpd_accepted_at))
@if($showLgpdModal)
    <div id="cliente-lgpd-modal" class="fixed inset-0 z-[21000] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Termos de Privacidade (LGPD)</h2>
                <p class="text-sm text-slate-500 mt-1">Para continuar no portal, confirme a leitura e o aceite dos termos.</p>
            </div>
            <form method="POST" action="{{ route('cliente.lgpd.aceitar') }}" class="p-6 space-y-4">
                @csrf
                <div class="max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 leading-relaxed">
                    <p>A FORMED realiza o tratamento de dados pessoais para execução dos serviços de medicina e segurança do trabalho, cumprimento de obrigações legais e atendimento ao cliente.</p>
                    <p class="mt-3">Ao aceitar, você confirma que está ciente do tratamento de dados conforme a Lei Geral de Proteção de Dados (Lei nº 13.709/2018), incluindo uso, armazenamento e compartilhamento quando necessário para prestação do serviço e obrigações regulatórias.</p>
                    <p class="mt-3">Você pode solicitar esclarecimentos e exercer seus direitos de titular de dados pelos canais oficiais da empresa.</p>
                </div>

                @error('aceito_lgpd')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror

                <label class="inline-flex items-start gap-2 text-sm text-slate-700">
                    <input type="checkbox" id="cliente-lgpd-check" name="aceito_lgpd" value="1" class="mt-0.5 rounded border-slate-300 text-blue-600">
                    <span>Li e aceito os termos de privacidade e tratamento de dados (LGPD).</span>
                </label>

                <div class="flex items-center justify-end">
                    <button type="submit" id="cliente-lgpd-submit" disabled
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 text-white px-5 py-2.5 text-sm font-semibold disabled:opacity-60 disabled:cursor-not-allowed hover:bg-blue-700">
                        Aceitar e Continuar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

@stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const flashOk = @json(session('ok'));
    const flashErr = @json(session('error') ?? session('erro'));
    if (typeof window.uiAlert === 'function' && !window.__clienteFlashShown) {
        window.__clienteFlashShown = true;
        if (flashOk) {
            window.uiAlert(flashOk, { icon: 'success', title: 'Sucesso' });
        } else if (flashErr) {
            window.uiAlert(flashErr);
        }
    }

    const sidebar = document.getElementById('cliente-sidebar');
    const backdrop = document.getElementById('cliente-sidebar-backdrop');
    const btnToggleMob = document.querySelector('[data-sidebar-toggle]');
    const btnCloses = document.querySelectorAll('[data-sidebar-close]');
    const btnCollapse = document.querySelector('[data-sidebar-collapse]');
    const labels = document.querySelectorAll('[data-sidebar-label]');
    const headerTitle = document.querySelector('[data-sidebar-label-header]');
    const overlayRoot = document.getElementById('app-overlay-root');
    const userAgent = String(window.navigator?.userAgent || '');
    const viewportW = Math.max(window.screen?.width || 0, window.screen?.height || 0);
            const viewportH = Math.min(window.screen?.width || 0, window.screen?.height || 0);
            const isNestViewport = (viewportW === 1280 && viewportH === 800) || (viewportW === 1024 && viewportH === 600);
            const isNestDevice = /CrKey|Fuchsia|Android.*wv/i.test(userAgent) || isNestViewport;
    const storageKey = 'clienteSidebarCollapsed';
    const lgpdCheck = document.getElementById('cliente-lgpd-check');
    const lgpdSubmit = document.getElementById('cliente-lgpd-submit');

    function mountOverlayModals() {
        if (!overlayRoot) return;
        document.querySelectorAll('[data-overlay-root="true"]').forEach((modal) => {
            if (!modal || modal.parentElement === overlayRoot) return;
            modal.classList.add('pointer-events-auto');
            overlayRoot.appendChild(modal);
        });
    }

    mountOverlayModals();

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

    function setCollapsed(collapsed) {
        if (!sidebar || isMobile()) return;
        sidebar.classList.toggle('w-64', !collapsed);
        sidebar.classList.toggle('w-16', collapsed);
        labels.forEach((el) => el.classList.toggle('hidden', collapsed));
        if (headerTitle) headerTitle.classList.toggle('hidden', collapsed);
    }

    function isSidebarOpenMobile() {
        if (!sidebar) return false;
        return !sidebar.classList.contains('-translate-x-full');
    }

    function openSidebar() {
        if (!sidebar) return;
        if (isMobile()) {
            sidebar.style.setProperty('position', 'fixed');
            sidebar.style.setProperty('top', '0');
            sidebar.style.setProperty('bottom', '0');
            sidebar.style.setProperty('left', '0');
            sidebar.style.setProperty('width', window.innerWidth <= 640 ? '100vw' : 'min(22rem, 92vw)');
            sidebar.style.setProperty('max-width', '100vw');
            sidebar.style.setProperty('z-index', '70');
        }
        sidebar.classList.remove('-translate-x-full', 'opacity-0', 'invisible', 'pointer-events-none');
        sidebar.classList.add('translate-x-0', 'opacity-100', 'visible', 'pointer-events-auto');
        backdrop?.classList.remove('opacity-0', 'pointer-events-none');
        backdrop?.classList.add('opacity-100', 'pointer-events-auto');
        document.body.classList.add('overflow-hidden');
    }

    function closeSidebar() {
        if (!sidebar) return;
        if (isMobile()) {
            sidebar.style.setProperty('position', 'fixed');
            sidebar.style.setProperty('top', '0');
            sidebar.style.setProperty('bottom', '0');
            sidebar.style.setProperty('left', '0');
            sidebar.style.setProperty('width', window.innerWidth <= 640 ? '100vw' : 'min(22rem, 92vw)');
            sidebar.style.setProperty('max-width', '100vw');
            sidebar.style.setProperty('z-index', '70');
        }

        sidebar.classList.remove('translate-x-0', 'opacity-100', 'visible', 'pointer-events-auto');
        sidebar.classList.add('-translate-x-full', 'opacity-0', 'invisible', 'pointer-events-none');
        backdrop?.classList.remove('opacity-100', 'pointer-events-auto');
        backdrop?.classList.add('opacity-0', 'pointer-events-none');
        document.body.classList.remove('overflow-hidden');
    }

    function syncSidebarForViewport() {
        if (!sidebar) return;
        if (isMobile()) {
            sidebar.style.setProperty('position', 'fixed');
            sidebar.style.setProperty('top', '0');
            sidebar.style.setProperty('bottom', '0');
            sidebar.style.setProperty('left', '0');
            sidebar.style.setProperty('width', window.innerWidth <= 640 ? '100vw' : 'min(22rem, 92vw)');
            sidebar.style.setProperty('max-width', '100vw');
            sidebar.style.setProperty('z-index', '70');
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

        sidebar.style.removeProperty('position');
        sidebar.style.removeProperty('top');
        sidebar.style.removeProperty('bottom');
        sidebar.style.removeProperty('left');
        sidebar.style.removeProperty('width');
        sidebar.style.removeProperty('max-width');
        sidebar.style.removeProperty('z-index');
        sidebar.classList.remove('-translate-x-full', 'translate-x-0', 'opacity-0', 'invisible', 'pointer-events-none');
        sidebar.classList.add('opacity-100', 'visible', 'pointer-events-auto');
        backdrop?.classList.remove('opacity-100', 'pointer-events-auto');
        backdrop?.classList.add('opacity-0', 'pointer-events-none');
        document.body.classList.remove('overflow-hidden');

        const collapsed = localStorage.getItem(storageKey) === '1';
        setCollapsed(collapsed);
    }

    btnToggleMob?.addEventListener('click', function () {
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
    btnCloses.forEach((btn) => btn.addEventListener('click', closeSidebar));
    backdrop?.addEventListener('click', closeSidebar);

    btnCollapse?.addEventListener('click', function () {
        if (isMobile()) return;
        const collapsedNow = !(localStorage.getItem(storageKey) === '1');
        localStorage.setItem(storageKey, collapsedNow ? '1' : '0');
        setCollapsed(collapsedNow);
    });

    sidebar?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', function () {
            if (isMobile()) closeSidebar();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeSidebar();
    });

    window.addEventListener('resize', syncSidebarForViewport);
    window.addEventListener('orientationchange', syncSidebarForViewport);
    window.visualViewport?.addEventListener('resize', syncSidebarForViewport);
    const mediaMobile = window.matchMedia('(max-width: 1023.98px)');
    mediaMobile.addEventListener?.('change', () => {
        syncSidebarForViewport();
    });
    syncSidebarForViewport();

    if (lgpdCheck && lgpdSubmit) {
        const toggleLgpdSubmit = function () {
            lgpdSubmit.disabled = !lgpdCheck.checked;
        };
        lgpdCheck.addEventListener('change', toggleLgpdSubmit);
        toggleLgpdSubmit();
    }
});
</script>
</body>
</html>
