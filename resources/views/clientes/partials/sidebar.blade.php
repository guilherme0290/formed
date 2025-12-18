{{-- resources/views/clientes/partials/sidebar.blade.php --}}
<aside class="hidden md:flex flex-col w-56 bg-slate-950 text-slate-100">
    {{-- Topo --}}
    <div class="h-16 flex items-center px-6 text-lg font-semibold">
        Portal do Cliente
    </div>

    {{-- Menu principal --}}
    <nav class="flex-1 px-3 mt-4 space-y-1">
        <a href="{{ route('cliente.dashboard') }}"
           class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-800 text-slate-50 text-sm font-medium">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-700">
                🏠
            </span>
            <span>Painel do Cliente</span>
        </a>

        {{-- aqui depois você pode colocar mais itens, tipo:
        <a href="{{ route('cliente.financeiro') }}" ...>Financeiro</a>
        --}}
    </nav>

    {{-- Rodapé: voltar / sair --}}
    <div class="px-4 py-4 border-t border-slate-800 space-y-2 text-sm">
        <a href="{{ url('/') }}" class="flex items-center gap-2 text-slate-300 hover:text-white">
            <span>⏪</span> <span>Voltar ao Início</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="flex items-center gap-2 text-rose-400 hover:text-rose-300">
                <span>🚪</span> Sair
            </button>
        </form>
    </div>
</aside>
