{{-- Sidebar Master compartilhada --}}
<div id="master-sidebar-backdrop"
     class="fixed inset-0 bg-black/40 z-20 opacity-0 pointer-events-none transition-opacity duration-200 md:hidden"></div>

<aside id="master-sidebar"
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

            <span data-sidebar-label-header>Master</span>
        </div>

        {{-- Botão fechar (somente mobile) --}}
        <button type="button"
                class="inline-flex items-center justify-center p-2 rounded-lg text-slate-300 hover:bg-slate-800 md:hidden"
                data-sidebar-close>
            ✕
        </button>
    </div>

    @php
        $navItems = [
            [
                'label' => 'Painel Master',
                'icon' => '📊',
                'route' => route('master.dashboard'),
                'active' => request()->routeIs('master.dashboard'),
            ],
            [
                'label' => 'Dados da Empresa',
                'icon' => '🏢',
                'route' => route('master.empresa.edit'),
                'active' => request()->routeIs('master.empresa.*'),
            ],
            [
                'label' => 'Comercial',
                'icon' => '🧭',
                'route' => route('comercial.dashboard'),
                'active' => request()->routeIs('comercial.*'),
            ],
            [
                'label' => 'Operacional',
                'icon' => '🛠️',
                'route' => route('operacional.kanban'),
                'active' => request()->routeIs('operacional.*'),
            ],
            [
                'label' => 'Financeiro',
                'icon' => '💳',
                'route' => route('financeiro.dashboard'),
                'active' => request()->routeIs('financeiro.*'),
            ],
            [
                'label' => 'Acessos',
                'icon' => '🔐',
                'route' => route('master.acessos'),
                'active' => request()->routeIs('master.acessos*'),
            ],
            [
                'label' => 'Tabela de Preços',
                'icon' => '💰',
                'route' => route('master.tabela-precos.itens.index'),
                'active' => request()->routeIs('master.tabela-precos.*'),
            ],
            [
                'label' => 'Comissões',
                'icon' => '💸',
                'route' => route('master.comissoes.index'),
                'active' => request()->routeIs('master.comissoes*'),
            ],
            [
                'label' => 'Comissões (Vendedores)',
                'icon' => '📈',
                'route' => route('master.comissoes.vendedores'),
                'active' => request()->routeIs('master.comissoes.vendedores'),
            ],
            [
                'label' => 'Clientes',
                'icon' => '👤',
                'route' => route('clientes.index'),
                'active' => request()->routeIs('clientes.*'),
            ],
        ];
    @endphp

    <nav class="relative z-10 flex-1 px-3 mt-4 space-y-1">
        @foreach($navItems as $item)
            @php
                $isActive = $item['active'];
                $baseClasses = 'flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition';
                $activeClasses = $isActive
                    ? 'bg-slate-800 text-slate-50 font-semibold'
                    : 'text-slate-200 hover:bg-slate-800';
            @endphp
            <a href="{{ $item['route'] }}"
               class="{{ $baseClasses }} {{ $activeClasses }}">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800">
                    {{ $item['icon'] }}
                </span>
                <span data-sidebar-label>{{ $item['label'] }}</span>
            </a>
        @endforeach
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
