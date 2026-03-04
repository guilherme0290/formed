{{-- Sidebar Operacional no mesmo modelo visual da Master --}}
<div id="operacional-sidebar-backdrop"
     class="fixed inset-0 bg-black/50 z-[9998] opacity-0 pointer-events-none transition-opacity duration-200 md:hidden"></div>

<aside id="operacional-sidebar"
       class="fixed inset-y-0 left-0 z-[9999] w-64 bg-slate-950 text-slate-100 shadow-2xl
              transform -translate-x-full transition-all duration-200 ease-in-out
              opacity-0 invisible pointer-events-none
              flex flex-col relative overflow-hidden
              md:static md:translate-x-0 md:opacity-100 md:visible md:pointer-events-auto">

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
                <span data-sidebar-label-header class="text-sm font-semibold text-slate-100">Operacional</span>
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
        $isMaster = auth()->user()?->isMaster();
        $permissionMap = auth()->user()?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $can = fn (string $key): bool => $isMaster || isset($permissionMap[$key]);
        $canKanban = $can('operacional.dashboard.view');

        $navItems = [
            [
                'label' => 'Painel Operacional',
                'icon' => 'fa-solid fa-chart-line',
                'route' => $canKanban ? route('operacional.kanban') : 'javascript:void(0)',
                'active' => request()->routeIs('operacional.*'),
                'disabled' => !$canKanban,
            ],
        ];
    @endphp

    <nav class="relative z-10 flex-1 px-3 mt-4 space-y-1 overflow-y-auto">
        @foreach($navItems as $item)
            <a href="{{ $item['route'] }}"
               @if($item['disabled']) title="Usuario sem permissao" aria-disabled="true" @endif
               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition min-w-0
                      {{ $item['active'] ? 'bg-slate-800/80 text-white border-l-2 border-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800/70 border-l-2 border-transparent' }}
                      {{ $item['disabled'] ? 'opacity-60 cursor-not-allowed pointer-events-none' : '' }}">
                <span class="{{ $item['active'] ? 'text-emerald-300' : 'text-slate-400 group-hover:text-slate-200' }}">
                    <i class="{{ $item['icon'] }} w-4 text-center"></i>
                </span>
                <span data-sidebar-label class="truncate">{{ $item['label'] }}</span>
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
