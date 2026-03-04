{{-- Sidebar Financeiro no mesmo modelo visual da Master --}}
<div id="financeiro-sidebar-backdrop"
     class="fixed inset-0 bg-black/50 z-[9998] opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden"></div>

<aside id="financeiro-sidebar"
       class="fixed inset-y-0 left-0 z-[9999] w-64 bg-slate-950 text-slate-100 shadow-2xl
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
                <span data-sidebar-label-header class="text-sm font-semibold text-slate-100">Financeiro</span>
                <span class="text-[11px] text-slate-500" data-sidebar-label>Modulo</span>
            </div>
        </div>

        <button type="button"
                class="inline-flex items-center justify-center p-2 rounded-lg text-slate-300 hover:bg-slate-800 lg:hidden"
                data-sidebar-close>
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>

    @php
        $isMaster = auth()->user()?->isMaster();
        $permissionMap = auth()->user()?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $can = fn (string $key): bool => $isMaster || isset($permissionMap[$key]);

        $links = [
            [
                'label' => 'Dashboard',
                'icon' => 'fa-solid fa-chart-pie',
                'route' => route('financeiro.dashboard'),
                'active' => request()->routeIs('financeiro.dashboard'),
                'perm' => 'financeiro.dashboard.view',
            ],
            [
                'label' => 'Contratos',
                'icon' => 'fa-regular fa-file-lines',
                'route' => route('financeiro.contratos'),
                'active' => request()->routeIs('financeiro.contratos*'),
                'perm' => 'financeiro.contratos.view',
            ],
            [
                'label' => 'Contas a Receber',
                'icon' => 'fa-solid fa-money-bill-trend-up',
                'route' => route('financeiro.contas-receber'),
                'active' => request()->routeIs('financeiro.contas-receber*'),
                'perm' => 'financeiro.contas-receber.view',
            ],
            [
                'label' => 'Contas a Pagar',
                'icon' => 'fa-solid fa-money-bill-transfer',
                'route' => route('financeiro.contas-pagar.index'),
                'active' => request()->routeIs('financeiro.contas-pagar*'),
                'perm' => 'financeiro.contas-receber.view',
            ],
            [
                'label' => 'Detalhamento',
                'icon' => 'fa-solid fa-chart-column',
                'route' => route('financeiro.faturamento-detalhado'),
                'active' => request()->routeIs('financeiro.faturamento-detalhado*'),
                'perm' => 'financeiro.faturamento.view',
            ],
        ];
    @endphp

    <nav class="relative z-10 flex-1 px-3 mt-4 space-y-1 overflow-y-auto">
        @foreach($links as $link)
            @php
                $enabled = $link['perm'] ? $can($link['perm']) : true;
            @endphp
            <a href="{{ $enabled ? $link['route'] : 'javascript:void(0)' }}"
               @if(!$enabled) title="Usuario sem permissao" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition min-w-0
                      {{ $link['active'] && $enabled ? 'bg-slate-800/80 text-white border-l-2 border-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800/70 border-l-2 border-transparent' }}
                      {{ !$enabled ? 'opacity-60 cursor-not-allowed pointer-events-none' : '' }}">
                <span class="{{ $link['active'] && $enabled ? 'text-emerald-300' : 'text-slate-400 group-hover:text-slate-200' }}">
                    <i class="{{ $link['icon'] }} w-4 text-center"></i>
                </span>
                <span data-sidebar-label class="truncate">{{ $link['label'] }}</span>
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
