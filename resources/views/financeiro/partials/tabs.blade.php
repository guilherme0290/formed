<div class="flex flex-wrap items-center gap-3 border-b border-slate-200 pb-2">
    <a href="{{ route('financeiro.dashboard') }}"
       class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 {{ request()->routeIs('financeiro.dashboard') ? 'bg-indigo-600 text-white shadow hover:bg-indigo-600' : '' }}">
        Dashboard
    </a>
    <a href="{{ route('financeiro.contratos') }}"
       class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 {{ request()->routeIs('financeiro.contratos*') ? 'bg-indigo-600 text-white shadow hover:bg-indigo-600' : '' }}">
        Contratos
    </a>
    <a href="{{ route('financeiro.contas-receber') }}"
       class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 {{ request()->routeIs('financeiro.contas-receber*') ? 'bg-indigo-600 text-white shadow hover:bg-indigo-600' : '' }}">
        Contas a Receber
    </a>
    <a href="{{ route('financeiro.faturamento-detalhado') }}"
       class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 {{ request()->routeIs('financeiro.faturamento-detalhado*') ? 'bg-indigo-600 text-white shadow hover:bg-indigo-600' : '' }}">
        Detalhamento
    </a>
</div>
