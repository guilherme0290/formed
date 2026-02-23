@php
    $user = auth()->user();
    $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
    $isMaster = $user?->hasPapel('Master');
    $can = static fn (string $perm) => $isMaster || isset($permissionMap[$perm]);
@endphp

<div class="flex flex-wrap items-center gap-3 border-b border-slate-200 pb-2">
    <a href="{{ $can('financeiro.dashboard.view') ? route('financeiro.dashboard') : 'javascript:void(0)' }}"
       @if(!$can('financeiro.dashboard.view')) title="Usuário sem permissão" aria-disabled="true" @endif
       class="px-3 py-2 rounded-xl text-sm font-semibold {{ $can('financeiro.dashboard.view') ? 'text-slate-700 hover:bg-slate-100' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} {{ request()->routeIs('financeiro.dashboard') ? 'bg-indigo-600 text-white shadow hover:bg-indigo-600' : '' }}">
        Dashboard
    </a>
    <a href="{{ $can('financeiro.contratos.view') ? route('financeiro.contratos') : 'javascript:void(0)' }}"
       @if(!$can('financeiro.contratos.view')) title="Usuário sem permissão" aria-disabled="true" @endif
       class="px-3 py-2 rounded-xl text-sm font-semibold {{ $can('financeiro.contratos.view') ? 'text-slate-700 hover:bg-slate-100' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} {{ request()->routeIs('financeiro.contratos*') ? 'bg-indigo-600 text-white shadow hover:bg-indigo-600' : '' }}">
        Contratos
    </a>
    <a href="{{ $can('financeiro.contas-receber.view') ? route('financeiro.contas-receber') : 'javascript:void(0)' }}"
       @if(!$can('financeiro.contas-receber.view')) title="Usuário sem permissão" aria-disabled="true" @endif
       class="px-3 py-2 rounded-xl text-sm font-semibold {{ $can('financeiro.contas-receber.view') ? 'text-slate-700 hover:bg-slate-100' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} {{ request()->routeIs('financeiro.contas-receber*') ? 'bg-indigo-600 text-white shadow hover:bg-indigo-600' : '' }}">
        Contas a Receber
    </a>
    <a href="{{ route('financeiro.contas-pagar.index') }}"
       class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 {{ request()->routeIs('financeiro.contas-pagar*') ? 'bg-indigo-600 text-white shadow hover:bg-indigo-600' : '' }}">
        Contas a Pagar
    </a>
    <a href="{{ $can('financeiro.faturamento.view') ? route('financeiro.faturamento-detalhado') : 'javascript:void(0)' }}"
       @if(!$can('financeiro.faturamento.view')) title="Usuário sem permissão" aria-disabled="true" @endif
       class="px-3 py-2 rounded-xl text-sm font-semibold {{ $can('financeiro.faturamento.view') ? 'text-slate-700 hover:bg-slate-100' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }} {{ request()->routeIs('financeiro.faturamento-detalhado*') ? 'bg-indigo-600 text-white shadow hover:bg-indigo-600' : '' }}">
        Detalhamento
    </a>
</div>
