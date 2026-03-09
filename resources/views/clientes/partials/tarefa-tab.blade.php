<div data-tab-panel="tarefa" data-tab-panel-root="cliente" class="hidden">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b bg-cyan-700 text-white">
                <h1 class="text-lg font-semibold">Criar Tarefa</h1>
            </div>

            <div class="p-6 space-y-4">
                <a href="{{ route('operacional.kanban.servicos', [
                    'cliente' => $cliente,
                    'origem' => 'cliente-edit',
                    'return_url' => route($routePrefix.'.edit', ['cliente' => $cliente->id, 'tab' => 'tarefa']),
                ]) }}"
                   class="block rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm font-semibold text-cyan-800 hover:bg-cyan-100">
                    Escolher serviço e criar tarefa
                </a>
            </div>
        </div>
    </div>
</div>
