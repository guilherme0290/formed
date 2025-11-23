<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-5xl shadow-xl overflow-hidden animate-fadeIn">

        {{-- CABEÇALHO --}}
        <div class="bg-slate-900 text-white px-6 py-4 flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                Detalhes da Tarefa — #{{ $tarefa->id }}
            </h2>
            <button onclick="window.fecharModalDetalhes()" class="text-white/70 hover:text-white text-xl">×</button>
        </div>

        <div class="p-6 space-y-6">

            {{-- 1. INFORMAÇÕES DO CLIENTE --}}
            <section class="bg-white border rounded-xl p-4">
                <h3 class="text-xs font-bold text-slate-700 mb-2">1. DETALHES DA SOLICITAÇÃO</h3>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="font-semibold">{{ $tarefa->cliente->razao_social }}</p>
                        <p class="text-xs text-slate-500">Razão Social</p>
                    </div>
                    <div>
                        <p class="font-semibold">{{ $tarefa->cliente->cnpj }}</p>
                        <p class="text-xs text-slate-500">CNPJ</p>
                    </div>
                    <div>
                        <p class="font-semibold">{{ $tarefa->cliente->telefone }}</p>
                        <p class="text-xs text-slate-500">Telefone</p>
                    </div>
                    <div>
                        <p class="font-semibold">{{ $tarefa->responsavel->name ?? '—' }}</p>
                        <p class="text-xs text-slate-500">Responsável</p>
                    </div>
                </div>
            </section>

            {{-- 2. STATUS ATUAL --}}
            <section class="bg-white border rounded-xl p-4">
                <h3 class="text-xs font-bold text-slate-700 mb-2">2. STATUS ATUAL</h3>
                <span class="inline-flex px-4 py-2 rounded-lg text-sm font-semibold bg-sky-100 text-sky-700">
                    {{ $tarefa->status }}
                </span>
            </section>

            {{-- 3. DESCRIÇÃO --}}
            <section class="bg-white border rounded-xl p-4">
                <h3 class="text-xs font-bold text-slate-700 mb-2">3. DESCRIÇÃO DA TAREFA</h3>
                <p class="text-sm text-slate-800">{{ $tarefa->descricao ?? '—' }}</p>
            </section>

            {{-- 4. TIPO DE SERVIÇO --}}
            <section class="bg-white border rounded-xl p-4">
                <h3 class="text-xs font-bold text-slate-700 mb-2">4. TIPO DE SERVIÇO</h3>
                <p class="text-sm font-semibold text-slate-700">{{ $tarefa->servico->nome }}</p>
            </section>

            {{-- 5. FUNÇÕES (PGR / LTCAT / LTIP / APR) --}}
            @php
                $sol = $tarefa->pgrSolicitacao
                    ?? $tarefa->ltcatSolicitacao
                    ?? $tarefa->ltipSolicitacao
                    ?? $tarefa->aprSolicitacao
                    ?? null;
            @endphp

            @if($sol && is_array($sol->funcoes))
                <section class="bg-white border rounded-xl p-4">
                    <h3 class="text-xs font-bold text-slate-700 mb-3">5. FUNÇÕES</h3>

                    <div class="space-y-2">
                        @foreach($sol->funcoes as $f)
                            <div class="px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 text-sm flex justify-between">
                                <span>{{ $funcoesMap[$f['funcao_id']] ?? 'Função não encontrada' }}</span>
                                <span class="font-semibold">Qtd: {{ $f['quantidade'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- 6. TREINAMENTO NR --}}
            @if($tarefa->treinamentoNrs->count())
                <section class="bg-white border rounded-xl p-4">
                    <h3 class="text-xs font-bold text-slate-700 mb-3">6. PARTICIPANTES DO TREINAMENTO</h3>

                    <ul class="space-y-1">
                        @foreach($tarefa->treinamentoNrs as $tr)
                            <li class="text-sm bg-slate-50 border border-slate-200 px-3 py-2 rounded-lg">
                                <span class="font-semibold">{{ $tr->funcionario->nome }}</span>
                                — {{ optional($tr->funcionario->funcao)->nome ?? 'Sem função' }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- 7. DATAS --}}
            <section class="bg-white border rounded-xl p-4">
                <h3 class="text-xs font-bold text-slate-700 mb-2">7. INFORMAÇÕES DE DATA</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="font-semibold">{{ $tarefa->created_at->format('d/m/Y H:i') }}</span>
                        <p class="text-xs text-slate-500">Criada em</p>
                    </div>
                    <div>
                        <span class="font-semibold">{{ $tarefa->data_prevista?->format('d/m/Y') ?? '—' }}</span>
                        <p class="text-xs text-slate-500">Prazo</p>
                    </div>
                </div>
            </section>

        </div>

        <div class="p-4 border-t bg-slate-50 text-right">
            <button onclick="window.fecharModalDetalhes()"
                    class="px-5 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold">
                Fechar
            </button>
        </div>

    </div>
</div>
