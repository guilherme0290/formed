<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Portal do Cliente') - Formed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- opcional, mas útil pro JS/AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>

<body class="font-sans antialiased bg-slate-50 text-slate-800">

<div class="min-h-screen flex">

    {{-- SIDEBAR DO CLIENTE (vai até o topo) --}}
    <aside class="hidden md:flex flex-col w-56 bg-slate-950 text-slate-100 relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
            <img src="{{ asset('storage/logo.svg') }}" alt="FORMED" class="w-36">
        </div>
        <div class="relative z-10 h-16 flex items-center px-6 text-lg font-semibold">
            Portal do Cliente
        </div>

        @php
            $temTabelaSidebar = $temTabela ?? false;
            $precosSidebar = $precos ?? [];
            $contratoAtivoSidebar = $contratoAtivo ?? null;
            $servicosContratoSidebar = $servicosContrato ?? [];
            $servicosIdsSidebar = $servicosIds ?? [];
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
                    'icone' => '🏠',
                    'rota' => route('cliente.dashboard'),
                    'disabled' => false,
                    'principal' => true,
                ],
                [
                    'titulo' => 'Funcionários',
                    'icone' => '👥',
                    'rota' => route('cliente.funcionarios.index'),
                    'disabled' => false,
                ],
                [
                    'titulo' => 'Agendar ASO',
                    'icone' => '📅',
                    'rota' => route('cliente.servicos.aso'),
                    'disabled' => !($permitidosSidebar['aso'] ?? false),
                ],
                [
                    'titulo' => 'Solicitar PGR',
                    'icone' => '📋',
                    'rota' => route('cliente.servicos.pgr'),
                    'disabled' => !($permitidosSidebar['pgr'] ?? false),
                ],
                [
                    'titulo' => 'Solicitar PCMSO',
                    'icone' => '📑',
                    'rota' => route('cliente.servicos.pcmso'),
                    'disabled' => !($permitidosSidebar['pcmso'] ?? false),
                ],
                [
                    'titulo' => 'Solicitar LTCAT',
                    'icone' => '📄',
                    'rota' => route('cliente.servicos.ltcat'),
                    'disabled' => !($permitidosSidebar['ltcat'] ?? false),
                ],
                [
                    'titulo' => 'Solicitar APR',
                    'icone' => '⚠️',
                    'rota' => route('cliente.servicos.apr'),
                    'disabled' => !($permitidosSidebar['apr'] ?? false),
                ],
                [
                    'titulo' => 'Treinamentos',
                    'icone' => '🎓',
                    'rota' => route('cliente.servicos.treinamentos'),
                    'disabled' => !($permitidosSidebar['treinamentos'] ?? false),
                ],
            ];
        @endphp

        <nav class="relative z-10 flex-1 px-3 mt-4 space-y-1">
            @foreach($linksRapidos as $link)
                @php
                    $disabled = $link['disabled'] ?? false;
                    $classes = $link['principal'] ?? false
                        ? 'bg-slate-800 text-slate-50 font-medium'
                        : 'text-slate-100 hover:bg-slate-800/80';
                    $opacity = $disabled ? 'opacity-60 pointer-events-none cursor-not-allowed' : '';
                @endphp
                <a href="{{ $link['rota'] }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition {{ $classes }} {{ $opacity }}">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800">
                        {{ $link['icone'] }}
                    </span>
                    <span>{{ $link['titulo'] }}</span>
                </a>
            @endforeach

            <div class="mt-4 px-2">
                <p class="text-[11px] uppercase tracking-[0.16em] text-slate-400 mb-2">Status</p>
                <div class="rounded-lg bg-slate-900/50 border border-slate-800 px-3 py-2 text-[12px] text-slate-200">
                    @if($temTabelaSidebar)
                        Tabela de preços ativa para este cliente.
                    @else
                        Tabela de preços não definida — alguns serviços ficam indisponíveis.
                    @endif
                </div>
            </div>
        </nav>

        <div class="relative z-10 px-4 py-4 border-t border-slate-800 space-y-2 text-sm">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex items-center gap-2 text-rose-400 hover:text-rose-300">
                    <span>🚪</span> Sair
                </button>
            </form>
        </div>
    </aside>

    {{-- COLUNA DA DIREITA (Header + Faixa + Conteúdo) --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- TOP BAR IGUAL AO OPERACIONAL (mesmo azul) --}}
        <header class="bg-blue-900 text-white shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">

                {{-- Lado esquerdo: FORMED + subtítulo --}}
                <div class="flex items-baseline gap-3">
                    <span class="font-semibold text-lg tracking-tight">FORMED</span>
                    <span class="text-xs md:text-sm text-blue-100">
                        Medicina e Segurança do Trabalho
                    </span>
                </div>

                {{-- Lado direito: botao trocar --}}
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1 rounded-full border border-blue-300/60
                                       px-3 py-1 text-[11px] font-medium text-blue-50
                                       hover:bg-white/10 hover:text-white transition">
                            Trocar usuário
                        </button>
                    </form>
                @endauth

            </div>
        </header>

        {{-- 🔵 FAIXA AZUL DO CLIENTE --}}
        @isset($cliente)
            @php
                $razaoOuFantasia = $cliente->nome_fantasia ?: $cliente->razao_social;
                $cnpjFormatado   = $cliente->cnpj ?? '';
                $contatoNome     = optional($cliente->vendedor)->name ?? 'Comercial nao informado';
                $contatoTelefone = optional($cliente->vendedor)->telefone ?? '(00) 0000-0000';
                $contatoEmail    = optional($cliente->vendedor)->email ?? 'email@dominio.com';
            @endphp

            <section class="w-full bg-[#1450d2] text-white shadow-lg shadow-slate-900/20 py-5 md:py-6">
                <div class="w-full px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6">

                    {{-- Nome do cliente + CNPJ --}}
                    <div class="flex items-start gap-3">
                        <div class="h-12 w-12 rounded-2xl bg-white/10 flex items-center justify-center text-2xl">
                            🏢
                        </div>
                        <div>
                            <h1 class="text-lg md:text-xl font-semibold leading-tight">
                                {{ $razaoOuFantasia }}
                            </h1>

                            @if($cnpjFormatado)
                                <p class="text-xs md:text-sm text-blue-100 mt-1">
                                    CNPJ: {{ $cnpjFormatado }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Dados de contato --}}
                    <div class="flex flex-col text-xs md:text-sm text-blue-50 md:text-right">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-end gap-6 lg:gap-12">

                            <div class="md:mr-10 lg:mr-16">
                                <span class="uppercase text-[10px] tracking-[0.18em] text-blue-100/70 block">
                                    Contato
                                </span>
                                <span class="font-medium">{{ $contatoNome }}</span>
                            </div>

                            <div class="md:mr-10 lg:mr-16 md:mt-0 mt-2">
                                <span class="uppercase text-[10px] tracking-[0.18em] text-blue-100/70 block">
                                    Telefone
                                </span>
                                <span class="font-medium">{{ $contatoTelefone }}</span>
                            </div>

                            <div class="md:mt-0 mt-2">
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
        {{-- 🔵 FIM FAIXA CLIENTE --}}

        {{-- ALERTAS --}}
        @if (session('ok'))
            <div class="w-full mt-4 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 shadow">
                    {{ session('ok') }}
                </div>
            </div>
        @endif

        @if (session('erro'))
            <div class="w-full mt-4 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 shadow">
                    {{ session('erro') }}
                </div>
            </div>
        @endif

        {{-- CONTEÚDO COM MARCA D'ÁGUA (IGUAL ESTAVA) --}}
        <main class="flex-1 relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
                <img src="{{ asset('storage/logo.svg') }}"
                     alt="FORMED"
                     class="max-w-[512px] w-full">
            </div>

            <div class="relative z-10">
                <div class="@yield('page-container', 'w-full px-4 sm:px-6 lg:px-8 py-6')">
                    @yield('content')
                </div>
            </div>
        </main>

    </div>
</div>

@stack('scripts')
</body>
</html>
