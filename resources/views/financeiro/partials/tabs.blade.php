@php
    $user = auth()->user();
    $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
    $isMaster = $user?->hasPapel('Master');
    $can = static fn (string $perm) => $isMaster || isset($permissionMap[$perm]);

    $tabs = [
        [
            'label' => 'Dashboard',
            'route' => route('financeiro.dashboard'),
            'active' => request()->routeIs('financeiro.dashboard'),
            'enabled' => $can('financeiro.dashboard.view'),
        ],
        [
            'label' => 'Contratos',
            'route' => route('financeiro.contratos'),
            'active' => request()->routeIs('financeiro.contratos*'),
            'enabled' => $can('financeiro.contratos.view'),
        ],
        [
            'label' => 'Contas a Receber',
            'route' => route('financeiro.contas-receber'),
            'active' => request()->routeIs('financeiro.contas-receber*'),
            'enabled' => $can('financeiro.contas-receber.view'),
        ],
        [
            'label' => 'Contas a Pagar',
            'route' => route('financeiro.contas-pagar.index'),
            'active' => request()->routeIs('financeiro.contas-pagar*'),
            'enabled' => true,
        ],
        // [
        //     'label' => 'Detalhamento',
        //     'route' => route('financeiro.faturamento-detalhado'),
        //     'active' => request()->routeIs('financeiro.faturamento-detalhado*'),
        //     'enabled' => $can('financeiro.faturamento.view'),
        // ],
    ];

    $tabClasses = static function (bool $enabled, bool $active): string {
        if (!$enabled) {
            return 'cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400 opacity-80';
        }

        if ($active) {
            return 'border border-indigo-500 bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-md shadow-indigo-200';
        }

        return 'border border-slate-200 bg-white/80 text-slate-700 hover:bg-slate-50 hover:border-slate-300';
    };

@endphp

<div class="rounded-2xl border border-slate-200 bg-white/70 backdrop-blur px-2.5 py-2">
    <div class="flex flex-wrap items-center gap-2">
        @foreach($tabs as $tab)
            <a href="{{ $tab['enabled'] ? $tab['route'] : 'javascript:void(0)' }}"
               @if(!$tab['enabled']) title="Usuário sem permissão" aria-disabled="true" @endif
               class="group inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition {{ $tabClasses($tab['enabled'], $tab['active']) }}">
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
