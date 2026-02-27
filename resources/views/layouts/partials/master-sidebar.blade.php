{{-- Sidebar Master compartilhada --}}
<div id="master-sidebar-backdrop"
     class="fixed inset-0 bg-black/50 z-[9998] opacity-0 pointer-events-none transition-opacity duration-200 md:hidden"></div>

<aside id="master-sidebar"
       class="fixed inset-y-0 left-0 z-[9999] w-64 bg-slate-950 text-slate-100 shadow-2xl
              transform -translate-x-full transition-all duration-200 ease-in-out
              flex flex-col relative overflow-hidden
              md:static md:translate-x-0">

    <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06]">
        <img src="{{ asset('storage/logo.svg') }}" alt="FORMED" class="w-40">
    </div>

    <div class="relative z-10 h-16 flex items-center justify-between px-4 border-b border-slate-800">
        <div class="flex items-center gap-2">
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
                <span data-sidebar-label-header class="text-sm font-semibold text-slate-100">Master</span>
                <span class="text-[11px] text-slate-500" data-sidebar-label>Modulo</span>
            </div>
        </div>

        <button type="button"
                class="inline-flex items-center justify-center p-2 rounded-lg text-slate-300 hover:bg-slate-800 md:hidden"
                data-sidebar-close>
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>

    @php
        $navItems = [
            [
                'label' => 'Dados da Empresa',
                'icon' => 'fa-regular fa-building',
                'route' => route('master.empresa.edit'),
                'active' => request()->routeIs('master.empresa.*'),
            ],
            [
                'label' => 'Comercial',
                'icon' => 'fa-solid fa-briefcase',
                'route' => '#',
                'active' => request()->routeIs('comercial.*')
                    || request()->routeIs('master.agenda-vendedores.*')
                    || request()->routeIs('master.tabela-precos.*')
                    || request()->routeIs('master.comissoes*')
                    || request()->routeIs('comercial.funcoes.*'),
                'children' => [
                    [
                        'label' => 'Tabela de Precos',
                        'icon' => 'fa-solid fa-tags',
                        'route' => route('master.tabela-precos.itens.index'),
                        'active' => request()->routeIs('master.tabela-precos.*'),
                    ],
                    [
                        'label' => 'Comissoes',
                        'icon' => 'fa-solid fa-coins',
                        'route' => route('master.comissoes.index'),
                        'active' => request()->routeIs('master.comissoes.index')
                            || request()->routeIs('master.comissoes.store')
                            || request()->routeIs('master.comissoes.update')
                            || request()->routeIs('master.comissoes.destroy')
                            || request()->routeIs('master.comissoes.bulk'),
                    ],
                    [
                        'label' => 'Comissoes (Vendedores)',
                        'icon' => 'fa-solid fa-chart-column',
                        'route' => route('master.comissoes.vendedores'),
                        'active' => request()->routeIs('master.comissoes.vendedores'),
                    ],
                    [
                        'label' => 'Funcoes',
                        'icon' => 'fa-solid fa-puzzle-piece',
                        'route' => route('comercial.funcoes.index'),
                        'active' => request()->routeIs('comercial.funcoes.*'),
                    ],
                ],
            ],
            [
                'label' => 'Operacional',
                'icon' => 'fa-solid fa-screwdriver-wrench',
                'route' => route('operacional.kanban'),
                'active' => request()->routeIs('operacional.*'),
            ],
            [
                'label' => 'Financeiro',
                'icon' => 'fa-solid fa-wallet',
                'route' => route('financeiro.dashboard'),
                'active' => request()->routeIs('financeiro.*'),
            ],
            [
                'label' => 'Acessos',
                'icon' => 'fa-solid fa-key',
                'route' => route('master.acessos'),
                'active' => request()->routeIs('master.acessos*'),
            ],
            [
                'label' => 'Clientes',
                'icon' => 'fa-regular fa-user',
                'route' => route('clientes.index'),
                'active' => request()->routeIs('clientes.*'),
            ],
            [
                'label' => 'Configuração',
                'icon' => 'fa-solid fa-gear',
                'route' => route('master.email-caixas.index'),
                'active' => request()->routeIs('master.email-caixas.*'),
            ],
        ];

        array_unshift($navItems, [
            'label' => 'Painel Master',
            'icon' => 'fa-solid fa-chart-line',
            'route' => route('master.dashboard'),
            'active' => request()->routeIs('master.dashboard'),
        ]);

        $menuState = function (bool $active = false): string {
            if ($active) {
                return 'bg-slate-800/80 text-white border-l-2 border-emerald-400';
            }

            return 'text-slate-300 hover:text-white hover:bg-slate-800/70 border-l-2 border-transparent';
        };

        $iconState = function (bool $active = false): string {
            return $active ? 'text-emerald-300' : 'text-slate-400 group-hover:text-slate-200';
        };
    @endphp

    <nav class="relative z-10 flex-1 px-3 mt-4 space-y-1 overflow-y-auto">
        @foreach($navItems as $item)
            @php
                $isActive = $item['active'];
                $children = $item['children'] ?? [];
            @endphp

            @if(!empty($children))
                <details class="group" @if($isActive) open @endif>
                    <summary class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition cursor-pointer list-none {{ $menuState($isActive) }}">
                        <a href="{{ route('comercial.dashboard') }}"
                           class="flex min-w-0 items-center gap-3 flex-1"
                           onclick="event.stopPropagation()">
                            <span class="{{ $iconState($isActive) }}">
                                <i class="{{ $item['icon'] }} w-4 text-center"></i>
                            </span>
                            <span data-sidebar-label class="truncate">{{ $item['label'] }}</span>
                        </a>
                        <span data-sidebar-chevron class="ml-auto text-slate-400 group-open:rotate-180 transition-transform" aria-hidden="true">
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
                                $childActive = $child['active'];
                            @endphp
                            <a href="{{ $child['route'] }}"
                               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition min-w-0 {{ $menuState($childActive) }}">
                                <span class="{{ $iconState($childActive) }}">
                                    <i class="{{ $child['icon'] }} w-4 text-center"></i>
                                </span>
                                <span data-sidebar-label class="truncate">{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @else
                <a href="{{ $item['route'] }}"
                   class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition min-w-0 {{ $menuState($isActive) }}">
                    <span class="{{ $iconState($isActive) }}">
                        <i class="{{ $item['icon'] }} w-4 text-center"></i>
                    </span>
                    <span data-sidebar-label class="truncate">{{ $item['label'] }}</span>
                </a>
            @endif
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
