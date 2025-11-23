<div id="modal-tarefa-detalhes"
     x-data="{ open: false, tarefa: {}, solicitacao: {}, tipo: '', funcoes: [] }"
     x-show="open"
     style="display: none;"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 overflow-y-auto">

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl p-6 space-y-6 animate-fade-in">
        {{-- Cabeçalho --}}
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <div>
                <h2 class="text-lg font-bold text-slate-800">
                    Detalhes da Tarefa <span class="text-slate-400 text-sm" x-text="tarefa.id ? ('#' + tarefa.id) : ''"></span>
                </h2>
                <p class="text-xs text-slate-500" x-text="tarefa.titulo"></p>
            </div>
            <button @click="open = false"
                    class="text-slate-500 hover:text-slate-800 transition">
                ✕
            </button>
        </div>

        <div class="grid grid-cols-12 gap-4">

            {{-- 1. Dados do Cliente --}}
            <div class="col-span-6">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <h3 class="text-xs font-semibold text-slate-600 mb-3">1. DETALHES DA SOLICITAÇÃO</h3>
                    <p class="text-sm font-medium text-slate-800" x-text="tarefa.cliente?.razao_social"></p>
                    <p class="text-xs text-slate-600 mt-1" x-text="'CNPJ: ' + (tarefa.cliente?.cnpj ?? '-')"></p>
                    <p class="text-xs text-slate-600" x-text="'Telefone: ' + (tarefa.cliente?.telefone ?? '-')"></p>
                    <p class="text-xs text-slate-600" x-text="'Responsável: ' + (tarefa.responsavel?.name ?? '-')"></p>
                </div>
            </div>

            {{-- 2. Status Atual + Ações Rápidas --}}
            <div class="col-span-6 flex flex-col gap-3">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center justify-between">
                    <h3 class="text-xs font-semibold text-slate-600">2. STATUS ATUAL</h3>
                    <span class="px-3 py-1 rounded-lg text-xs font-semibold"
                          :class="{
                            'bg-yellow-100 text-yellow-700': tarefa.status === 'Pendente',
                            'bg-blue-100 text-blue-700': tarefa.status === 'Em Execução',
                            'bg-green-100 text-green-700': tarefa.status === 'Finalizada'
                          }"
                          x-text="tarefa.status">
                    </span>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-3 flex flex-col gap-2">
                    <h3 class="text-xs font-semibold text-slate-600 mb-1">3. AÇÕES RÁPIDAS</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <button class="bg-yellow-400 hover:bg-yellow-500 text-white text-xs font-semibold py-2 rounded-lg">
                            Aguardar Fornecedor
                        </button>
                        <button class="bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold py-2 rounded-lg">
                            Correção Necessária
                        </button>
                        <button class="bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold py-2 rounded-lg">
                            Finalizar
                        </button>
                    </div>
                </div>
            </div>

            {{-- 3. Descrição e Tipo de Serviço --}}
            <div class="col-span-6">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <h3 class="text-xs font-semibold text-slate-600 mb-3">4. DESCRIÇÃO DA TAREFA</h3>
                    <p class="text-sm font-semibold text-slate-800" x-text="tarefa.descricao ?? '-'"></p>

                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-[13px] text-slate-500">Tipo:</span>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-md bg-indigo-100 text-indigo-700"
                              x-text="tarefa.servico?.nome ?? '-'">
                        </span>
                    </div>
                </div>
            </div>

            {{-- 4. Funções Envolvidas (para PGR, LTCAT, LTIP, etc) --}}
            <div class="col-span-6">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <h3 class="text-xs font-semibold text-slate-600 mb-3">5. FUNÇÕES ENVOLVIDAS</h3>

                    <template x-if="funcoes && funcoes.length">
                        <ul class="space-y-2">
                            <template x-for="(f, i) in funcoes" :key="i">
                                <li class="flex justify-between text-sm bg-white border border-slate-100 rounded-lg px-3 py-2">
                                    <span x-text="f.nome"></span>
                                    <span class="text-slate-500 text-xs" x-text="f.quantidade + ' colaborador(es)'"></span>
                                </li>
                            </template>
                        </ul>
                    </template>

                    <template x-if="!funcoes || funcoes.length === 0">
                        <p class="text-xs text-slate-400">Nenhuma função cadastrada.</p>
                    </template>
                </div>
            </div>

            {{-- 5. Datas Importantes --}}
            <div class="col-span-6">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <h3 class="text-xs font-semibold text-slate-600 mb-3">6. DATAS</h3>
                    <p class="text-xs text-slate-500">
                        Criada em: <span class="font-medium text-slate-700" x-text="tarefa.created_at"></span>
                    </p>
                    <p class="text-xs text-slate-500">
                        Prazo: <span class="font-medium text-slate-700" x-text="tarefa.fim_previsto ?? '-'"></span>
                    </p>
                    <p class="text-xs text-slate-500">
                        Prioridade:
                        <span class="inline-block ml-1 px-2 py-0.5 rounded-md text-xs font-semibold"
                              :class="{
                                'bg-red-100 text-red-700': tarefa.prioridade === 'alta',
                                'bg-yellow-100 text-yellow-700': tarefa.prioridade === 'media',
                                'bg-green-100 text-green-700': tarefa.prioridade === 'baixa'
                              }"
                              x-text="tarefa.prioridade">
                        </span>
                    </p>
                </div>
            </div>

            {{-- 6. Observações Internas --}}
            <div class="col-span-6">
                <div class="bg-white border border-slate-200 rounded-xl p-4">
                    <h3 class="text-xs font-semibold text-slate-600 mb-3">7. OBSERVAÇÕES INTERNAS</h3>
                    <textarea rows="3"
                              placeholder="Digite suas observações..."
                              class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 focus:ring focus:ring-indigo-100"></textarea>
                    <button class="mt-2 px-4 py-2 rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-semibold">
                        Salvar Observação
                    </button>
                </div>
            </div>

        </div>

        <div class="flex justify-end">
            <button @click="open = false"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 bg-slate-50 hover:bg-slate-100">
                Fechar
            </button>
        </div>
    </div>
</div>
