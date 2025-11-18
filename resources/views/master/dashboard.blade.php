<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold">Painel Master</h1>
        <p class="text-sm text-slate-500 -mt-1">Visão administrativa completa do sistema</p>
    </x-slot>

    {{-- Cards métricas --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <span class="text-slate-500 text-sm">Clientes Ativos</span>
                <span class="h-9 w-9 rounded-full bg-indigo-50 text-indigo-600 grid place-items-center">👥</span>
            </div>
            <div class="mt-2 text-3xl font-bold">84</div>
            <div class="text-emerald-600 text-sm mt-1">+ 8</div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <span class="text-slate-500 text-sm">Faturamento Global</span>
                <span class="h-9 w-9 rounded-full bg-emerald-50 text-emerald-600 grid place-items-center">💵</span>
            </div>
            <div class="mt-2 text-3xl font-bold">R$ 125.430</div>
            <div class="text-emerald-600 text-sm mt-1">+12.5%</div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <span class="text-slate-500 text-sm">Tempo Médio de Execução</span>
                <span class="h-9 w-9 rounded-full bg-violet-50 text-violet-600 grid place-items-center">⏱️</span>
            </div>
            <div class="mt-2 text-3xl font-bold">48h</div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <span class="text-slate-500 text-sm">Serviços Consumidos</span>
                <span class="h-9 w-9 rounded-full bg-sky-50 text-sky-600 grid place-items-center">📈</span>
            </div>
            <div class="mt-2 text-3xl font-bold">156</div>
            <div class="text-emerald-600 text-sm mt-1">+23</div>
        </div>
    </div>

    {{-- Acessos rápidos --}}
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <a href="{{ route('master.acessos') }}" class="bg-white rounded-2xl shadow-sm border p-5 flex items-center gap-3 hover:shadow transition">
            <span class="h-10 w-10 rounded-xl bg-gray-900/5 text-gray-900 grid place-items-center text-xl">🧑‍💼</span>
            <div>
                <div class="font-semibold">Acessos & Usuários</div>
                <div class="text-sm text-slate-500">Gerencie papéis, usuários e senhas</div>
            </div>
        </a>

        <a href="{{ route('tabela-precos.index') }}" class="bg-white rounded-2xl shadow-sm border p-5 flex items-center gap-3 hover:shadow transition">
            <span class="h-10 w-10 rounded-xl bg-yellow-500/10 text-yellow-600 grid place-items-center text-xl">💰</span>
            <div>
                <div class="font-semibold">Tabela de Preços</div>
                <div class="text-sm text-slate-500">Cadastro e políticas de preço</div>
            </div>
        </a>

        <a href="{{ route('master.dashboard') }}" class="bg-white rounded-2xl shadow-sm border p-5 flex items-center gap-3 hover:shadow transition">
            <span class="h-10 w-10 rounded-xl bg-indigo-500/10 text-indigo-600 grid place-items-center text-xl">📊</span>
            <div>
                <div class="font-semibold">Painel Master</div>
                <div class="text-sm text-slate-500">Visão geral e relatórios</div>
            </div>
        </a>

        <a href="{{ route('clientes.index') }}"
           class="bg-white rounded-2xl shadow-sm border p-5 flex items-center gap-3 hover:shadow transition">
            <span class="h-10 w-10 rounded-xl bg-blue-500/10 text-blue-600 grid place-items-center text-xl">👤</span>
            <div>
                <div class="font-semibold">Clientes</div>
                <div class="text-sm text-slate-500">Cadastrar e gerenciar clientes</div>
            </div>
        </a>
    </div>

    {{-- Relatórios avançados --}}
    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Relatórios Avançados</h3>
            <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm flex items-center gap-2">
                📊 Gerar Relatório
            </button>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <div class="text-xs font-semibold text-slate-500 mb-2">MÉTRICAS OPERACIONAIS</div>
                <ul class="space-y-2">
                    <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                        <span>Taxa de Conclusão</span><span class="font-semibold text-emerald-600">94%</span>
                    </li>
                    <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                        <span>Tarefas Atrasadas</span><span class="font-semibold text-rose-600">6</span>
                    </li>
                    <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                        <span>SLA Médio</span><span class="font-semibold text-indigo-600">96%</span>
                    </li>
                </ul>
            </div>

            <div>
                <div class="text-xs font-semibold text-slate-500 mb-2">MÉTRICAS COMERCIAIS</div>
                <ul class="space-y-2">
                    <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                        <span>Ticket Médio</span><span class="font-semibold">R$ 9.800</span>
                    </li>
                    <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                        <span>Taxa de Conversão</span><span class="font-semibold text-emerald-600">75%</span>
                    </li>
                    <li class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2">
                        <span>Propostas em Aberto</span><span class="font-semibold">24</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
