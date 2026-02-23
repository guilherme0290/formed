{{-- Sidebar Master compartilhada --}}
<div id="master-sidebar-backdrop"
     class="fixed inset-0 bg-black/40 z-20 opacity-0 pointer-events-none transition-opacity duration-200 md:hidden"></div>

<aside id="master-sidebar"
       class="fixed inset-y-0 left-0 z-30 w-64 shrink-0 bg-slate-950 text-slate-100
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
                'route' => '#',
                'active' => request()->routeIs('comercial.*')
                    || request()->routeIs('master.agenda-vendedores.*')
                    || request()->routeIs('master.tabela-precos.*')
                    || request()->routeIs('master.comissoes*')
                    || request()->routeIs('comercial.funcoes.*'),
                'children' => [
                    [
                        'label' => 'Agenda Vendedores',
                        'icon' => '📅',
                        'route' => route('master.agenda-vendedores.index'),
                        'active' => request()->routeIs('master.agenda-vendedores.*'),
                    ],
//                    [
//                        'label' => 'Propostas',
//                        'icon' => '📑',
//                        'route' => route('comercial.propostas.index'),
//                        'active' => request()->routeIs('comercial.propostas.*'),
//                    ],
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
                        'active' => request()->routeIs('master.comissoes.index')
                            || request()->routeIs('master.comissoes.store')
                            || request()->routeIs('master.comissoes.update')
                            || request()->routeIs('master.comissoes.destroy')
                            || request()->routeIs('master.comissoes.bulk'),
                    ],
                    [
                        'label' => 'Comissões (Vendedores)',
                        'icon' => '📈',
                        'route' => route('master.comissoes.vendedores'),
                        'active' => request()->routeIs('master.comissoes.vendedores'),
                    ],
                    [
                        'label' => 'Funções',
                        'icon' => '🧩',
                        'route' => route('comercial.funcoes.index'),
                        'active' => request()->routeIs('comercial.funcoes.*'),
                    ],
                ],
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
                'label' => 'Clientes',
                'icon' => '👤',
                'route' => route('clientes.index'),
                'active' => request()->routeIs('clientes.*'),
            ],
            [
                'label' => 'Configuração',
                'icon' => '⚙️',
                'route' => route('master.email-caixas.index'),
                'active' => request()->routeIs('master.email-caixas.*'),
            ],
        ];
    @endphp

    <nav class="relative z-10 flex-1 px-3 mt-4 space-y-1 overflow-y-auto">
        @foreach($navItems as $item)
            @php
                $isActive = $item['active'];
                $baseClasses = 'flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition';
                $activeClasses = $isActive
                    ? 'bg-slate-800 text-slate-50 font-semibold'
                    : 'text-slate-200 hover:bg-slate-800';
                $children = $item['children'] ?? [];
            @endphp

            @if(!empty($children))
                <details class="group" @if($isActive) open @endif>
                    <summary class="{{ $baseClasses }} {{ $activeClasses }} min-w-0 cursor-pointer list-none">
                        <a href="{{ route('comercial.dashboard') }}"
                           class="flex min-w-0 items-center gap-2 flex-1"
                           onclick="event.stopPropagation()">
                            @if(!empty($item['icon']))
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800">
                                    {{ $item['icon'] }}
                                </span>
                            @endif
                            <span data-sidebar-label class="truncate">{{ $item['label'] }}</span>
                        </a>
                        <span data-sidebar-chevron class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-200 bg-slate-900/60 border border-slate-700 group-open:rotate-180 transition-transform" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </span>
                        <span class="sr-only">Expandir/Recolher menu Comercial</span>
                    </summary>

                    <div data-sidebar-children class="mt-1 space-y-1 pl-3 min-w-0">
                        @foreach($children as $child)
                            @php
                                $childBase = 'flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition';
                                $childActive = $child['active']
                                    ? 'bg-slate-800 text-slate-50 font-semibold'
                                    : 'text-slate-200 hover:bg-slate-800';
                            @endphp
                            <a href="{{ $child['route'] }}"
                               class="{{ $childBase }} {{ $childActive }} min-w-0">
                                @if(!empty($child['icon']))
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800">
                                        {{ $child['icon'] }}
                                    </span>
                                @endif
                                <span data-sidebar-label class="truncate">{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @else
                <a href="{{ $item['route'] }}"
                   class="{{ $baseClasses }} {{ $activeClasses }} min-w-0">
                    @if(!empty($item['icon']))
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800">
                            {{ $item['icon'] }}
                        </span>
                    @endif
                    <span data-sidebar-label class="truncate">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="relative z-10 px-4 py-4 border-t border-slate-800 space-y-2 text-sm">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="flex items-center gap-2 text-rose-400 hover:text-rose-300">
                <span>🚪</span>
                <span data-sidebar-label>Sair</span>
            </button>
        </form>
    </div>
</aside>






