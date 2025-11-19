@extends('layouts.operacional')
@section('title', 'Painel Operacional')

@section('content')
    <div class="max-w-7xl mx-auto px-6 py-6">

        {{-- Barra de busca --}}
        <div class="flex items-center gap-4 mb-6">
            <div class="flex-1">
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">🔍</span>
                    <input type="text" placeholder="Buscar..."
                           class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                </div>
            </div>

            <button id="btnNovaTarefaLoja"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium shadow-sm">
                <span>+ Nova Tarefa (Loja)</span>
            </button>
        </div>

        {{-- Título --}}
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-900">Painel Operacional</h1>
            <p class="text-sm text-slate-500">
                Suas tarefas atribuídas - {{ $usuario->name }}
            </p>
        </div>

        {{-- Cards de status --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            @php
                $stats = $stats ?? [];
            @endphp

            <div class="bg-white rounded-2xl shadow-sm border border-amber-100 px-4 py-4">
                <p class="text-xs font-medium text-amber-500 mb-1">Pendente</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['pendente'] ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-indigo-100 px-4 py-4">
                <p class="text-xs font-medium text-indigo-500 mb-1">Em Execução</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['em_execucao'] ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-purple-100 px-4 py-4">
                <p class="text-xs font-medium text-purple-500 mb-1">Aguardando Cliente</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['aguardando_cliente'] ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 px-4 py-4">
                <p class="text-xs font-medium text-emerald-500 mb-1">Concluído</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['concluido'] ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 px-4 py-4">
                <p class="text-xs font-medium text-rose-500 mb-1">Atrasado</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['atrasado'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" class="grid md:grid-cols-3 gap-4 mb-6 text-sm">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Tipo de Serviço</label>
                <select name="servico_id"
                        class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                    <option value="">Todos os serviços</option>
                    @foreach($servicos as $servico)
                        <option value="{{ $servico->id }}" @selected($filtroServico == $servico->id)>
                            {{ $servico->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Responsável</label>
                <select name="responsavel_id"
                        class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                    <option value="">Todos os responsáveis</option>
                    @foreach($responsaveis as $resp)
                        <option value="{{ $resp->id }}" @selected($filtroResponsavel == $resp->id)>
                            {{ $resp->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">
                    Filtro bloqueado: você vê apenas suas tarefas
                </p>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select name="status"
                        class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                    <option value="">Todos os status</option>
                    @foreach($colunas as $coluna)
                        <option value="{{ $coluna->slug }}" @selected($filtroStatus == $coluna->slug)>
                            {{ $coluna->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        {{-- Board Kanban --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @foreach($colunas as $coluna)
                @php
                    $lista = $tarefasPorColuna->get($coluna->id, collect());
                @endphp
                <section class="bg-slate-50">
                    <div class="mb-2">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-800">
                                {{ $coluna->nome }}
                            </h2>
                            <span class="text-[11px] text-slate-400">
                                {{ $lista->count() }} tarefas
                            </span>
                        </div>
                    </div>

                    <div class="js-kanban-column space-y-3 min-h-[80px] bg-slate-100/80 rounded-2xl p-2"
                         data-column-id="{{ $coluna->id }}">
                        @foreach($lista as $tarefa)
                            <article
                                class="kanban-card bg-white rounded-2xl shadow-sm border border-slate-100 px-3 py-3 text-xs cursor-move"
                                data-id="{{ $tarefa->id }}"
                                data-move-url="{{ route('operacional.tarefas.mover', $tarefa) }}"
                            >
                                <p class="text-[11px] font-semibold text-slate-900 mb-1">
                                    {{ optional($tarefa->cliente)->nome_fantasia ?? 'Cliente não informado' }}
                                </p>
                                <p class="text-[11px] text-slate-500 mb-2">
                                    {{ optional($tarefa->servico)->nome ?? $tarefa->titulo }}
                                </p>

                                <div class="flex items-center justify-between mb-2">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-600">
                                        {{ optional($tarefa->responsavel)->name ?? 'Sem responsável' }}
                                    </span>

                                    @php
                                        $coresPrioridade = [
                                            'baixa' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                            'media' => 'bg-amber-50 text-amber-600 border-amber-100',
                                            'alta'  => 'bg-rose-50 text-rose-600 border-rose-100',
                                        ];
                                        $classePrioridade = $coresPrioridade[$tarefa->prioridade] ?? 'bg-slate-50 text-slate-600 border-slate-100';
                                    @endphp

                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium {{ $classePrioridade }}">
                                        {{ ucfirst($tarefa->prioridade) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-[10px] text-slate-400">
                                    @if($tarefa->data_prevista)
                                        <span>📅 {{ $tarefa->data_prevista->format('d/m/Y') }}</span>
                                    @else
                                        <span>📅 Sem data</span>
                                    @endif

                                    <span>
                                        @if($coluna->slug === 'concluido')
                                            Concluído
                                        @elseif($coluna->slug === 'atrasado')
                                            Atrasado!
                                        @else
                                            {{ ucfirst(str_replace('_',' ',$coluna->slug)) }}
                                        @endif
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>



    {{-- MODAL NOVA TAREFA (LOJA) --}}
    <div id="modalNovaTarefa"
         class="fixed inset-0 z-40 hidden">
        {{-- fundo escuro --}}
        <div class="absolute inset-0 bg-slate-900/60"></div>

        {{-- conteúdo --}}
        <div class="relative z-50 flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl">
                <form id="formNovaTarefa"
                      method="POST"
                      data-url-existente="{{ route('operacional.tarefas.loja.existente') }}"
                      data-url-novo="{{ route('operacional.tarefas.loja.novo') }}">
                    @csrf

                    <input type="hidden" name="tipo_cliente" id="tipoCliente" value="existente">
                    <input type="hidden" name="status_inicial" id="statusInicial" value="pendente">

                    {{-- Cabeçalho --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">
                                Nova Tarefa (Loja) - <span id="etapaTitulo">Passo 1 de 3</span>
                            </h2>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-slate-600" data-modal-close>
                            ✕
                        </button>
                    </div>

                    {{-- Corpo: PASSOS --}}
                    <div class="px-6 py-5 space-y-6">

                        {{-- PASSO 1 --}}
                        <div class="passo" data-step="1">
                            <p class="text-sm font-medium text-slate-800 mb-3">Tipo de Cliente</p>

                            <div class="flex gap-6 mb-4 text-sm">
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="tipo_cliente_radio" value="existente" checked
                                           class="text-sky-500 border-slate-300 focus:ring-sky-500">
                                    <span>Cliente Existente</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="tipo_cliente_radio" value="novo"
                                           class="text-sky-500 border-slate-300 focus:ring-sky-500">
                                    <span>Cliente Manual (novo)</span>
                                </label>
                            </div>

                            {{-- Cliente EXISTENTE --}}
                            <div id="blocoClienteExistente" class="space-y-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Cliente *</label>
                                    <select name="cliente_id"
                                            class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                        <option value="">Selecione...</option>
                                        @foreach($clientes as $cli)
                                            <option value="{{ $cli->id }}">
                                                {{ $cli->nome_fantasia ?? $cli->razao_social }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Unidade/Local *</label>
                                    <select name="unidade_id"
                                            class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                        <option value="">Matriz</option>
                                        {{-- quando tiver tabela de unidades, preencher aqui --}}
                                    </select>
                                </div>
                            </div>

                            {{-- Cliente NOVO --}}
                            <div id="blocoClienteNovo" class="grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Razão Social *</label>
                                    <input type="text" name="razao_social"
                                           class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Nome Fantasia</label>
                                    <input type="text" name="nome_fantasia"
                                           class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">CNPJ/CPF</label>
                                    <input type="text" name="cnpj"
                                           class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Contato/WhatsApp</label>
                                    <input type="text" name="telefone"
                                           class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">E-mail</label>
                                    <input type="email" name="email"
                                           class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Unidade/Local *</label>
                                    <select name="unidade_id"
                                            class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                        <option value="">Matriz</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- PASSO 2 --}}
                        <div class="passo hidden" data-step="2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Tipo de Serviço *</label>
                                    <select name="servico_id"
                                            class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                        <option value="">Selecione...</option>
                                        @foreach($servicos as $serv)
                                            <option value="{{ $serv->id }}">{{ $serv->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">Data *</label>
                                        <input type="date" name="data"
                                               class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">Horário *</label>
                                        <input type="time" name="hora"
                                               class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Prioridade *</label>
                                    <div class="flex gap-4 mt-1 text-sm">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="radio" name="prioridade" value="baixa"
                                                   class="text-sky-500 border-slate-300 focus:ring-sky-500">
                                            <span>Baixa</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2">
                                            <input type="radio" name="prioridade" value="media" checked
                                                   class="text-sky-500 border-slate-300 focus:ring-sky-500">
                                            <span>Média</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2">
                                            <input type="radio" name="prioridade" value="alta"
                                                   class="text-sky-500 border-slate-300 focus:ring-sky-500">
                                            <span>Alta</span>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Prazo (SLA)</label>
                                    <input type="date" name="prazo_sla"
                                           class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Observações</label>
                                <textarea name="observacoes" rows="3"
                                          class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400"
                                          placeholder="Informações adicionais sobre a tarefa..."></textarea>
                            </div>
                        </div>

                        {{-- PASSO 3 --}}
                        <div class="passo hidden" data-step="3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Responsável *</label>
                                    <input type="text" readonly
                                           value="{{ $usuario->name }}"
                                           class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 px-3 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Status Inicial *</label>
                                    <select id="selectStatusInicial"
                                            class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                                        @foreach($colunas as $col)
                                            <option value="{{ $col->slug }}">{{ $col->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 border border-slate-100 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-700"
                                 id="resumoTarefa">
                                {{-- aqui o JS pode montar um pequeno resumo depois --}}
                                Resumo da tarefa será exibido aqui ao salvar.
                            </div>
                        </div>
                    </div>

                    {{-- Rodapé botões --}}
                    <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100">
                        <div>
                            <button type="button"
                                    class="text-sm text-slate-500 hover:text-slate-700"
                                    data-btn-voltar>
                                ◀ Voltar
                            </button>
                        </div>
                        <div class="space-x-2">
                            <button type="button"
                                    class="px-4 py-2 rounded-xl text-sm text-slate-600 hover:bg-slate-100"
                                    data-modal-close>
                                Cancelar
                            </button>
                            <button type="button"
                                    class="px-4 py-2 rounded-xl text-sm font-medium bg-sky-500 text-white hover:bg-sky-600"
                                    data-btn-proximo>
                                Próximo ▷
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 rounded-xl text-sm font-medium bg-sky-500 text-white hover:bg-sky-600 hidden"
                                    data-btn-finalizar>
                                Criar Tarefa
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- JS Kanban + Modal Nova Tarefa --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // =======================
            // 1) DRAG & DROP (KANBAN)
            // =======================
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : null;

            document.querySelectorAll('.js-kanban-column').forEach(columnEl => {
                if (typeof Sortable === 'undefined') {
                    console.error('SortableJS não está disponível. Importe em resources/js/app.js');
                    return;
                }

                new Sortable(columnEl, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'opacity-50',
                    onEnd: function (evt) {
                        const card = evt.item;
                        const moveUrl = card.dataset.moveUrl;
                        const newColumnId = evt.to.dataset.columnId;

                        if (!moveUrl || !newColumnId || !csrfToken) return;

                        fetch(moveUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ coluna_id: newColumnId })
                        })
                            .then(r => r.json())
                            .then(data => {
                                if (!data.ok) {
                                    console.error('Erro ao mover tarefa');
                                }
                            })
                            .catch(err => console.error(err));
                    }
                });
            });

            // ===========================
            // 2) MODAL NOVA TAREFA (LOJA)
            // ===========================
            const btnAbrir = document.getElementById('btnNovaTarefaLoja');
            const modal = document.getElementById('modalNovaTarefa');
            if (!btnAbrir || !modal) return;

            const form = document.getElementById('formNovaTarefa');
            const passos = modal.querySelectorAll('.passo');
            const btnFechar = modal.querySelectorAll('[data-modal-close]');
            const btnProximo = modal.querySelector('[data-btn-proximo]');
            const btnVoltar = modal.querySelector('[data-btn-voltar]');
            const btnFinalizar = modal.querySelector('[data-btn-finalizar]');
            const tituloEtapa = document.getElementById('etapaTitulo');
            const selectStatusInicial = document.getElementById('selectStatusInicial');
            const inputStatusInicial = document.getElementById('statusInicial');
            const tipoClienteHidden = document.getElementById('tipoCliente');
            const radiosTipoCliente = modal.querySelectorAll('input[name="tipo_cliente_radio"]');
            const blocoExistente = document.getElementById('blocoClienteExistente');
            const blocoNovo = document.getElementById('blocoClienteNovo');

            let step = 1;

            function atualizarVisao() {
                passos.forEach(p => {
                    const s = parseInt(p.dataset.step);
                    p.classList.toggle('hidden', s !== step);
                });
                tituloEtapa.textContent = `Passo ${step} de 3`;
                btnVoltar.disabled = (step === 1);
                btnVoltar.classList.toggle('opacity-40', step === 1);

                if (step === 3) {
                    btnProximo.classList.add('hidden');
                    btnFinalizar.classList.remove('hidden');
                } else {
                    btnProximo.classList.remove('hidden');
                    btnFinalizar.classList.add('hidden');
                }
            }

            // tipo de cliente (troca blocos + action do form)
            radiosTipoCliente.forEach(r => {
                r.addEventListener('change', () => {
                    const val = r.value;
                    tipoClienteHidden.value = val;

                    if (val === 'existente') {
                        blocoExistente.classList.remove('hidden');
                        blocoNovo.classList.add('hidden');
                        form.action = form.dataset.urlExistente;
                    } else {
                        blocoExistente.classList.add('hidden');
                        blocoNovo.classList.remove('hidden');
                        form.action = form.dataset.urlNovo;
                    }
                });
            });

            // abre modal
            btnAbrir.addEventListener('click', () => {
                step = 1;
                atualizarVisao();
                modal.classList.remove('hidden');

                const tipoAtual = document.querySelector('input[name="tipo_cliente_radio"]:checked').value;
                form.action = (tipoAtual === 'existente')
                    ? form.dataset.urlExistente
                    : form.dataset.urlNovo;
            });

            // fechar modal (botões + clicando em Cancelar)
            btnFechar.forEach(b => b.addEventListener('click', () => {
                modal.classList.add('hidden');
            }));

            // botão voltar
            btnVoltar.addEventListener('click', () => {
                if (step > 1) {
                    step--;
                    atualizarVisao();
                }
            });

            // botão próximo
            btnProximo.addEventListener('click', () => {
                if (step < 3) {
                    step++;
                    atualizarVisao();
                }
            });

            // status inicial -> preenche hidden que vai pro controller
            if (selectStatusInicial && inputStatusInicial) {
                selectStatusInicial.addEventListener('change', () => {
                    inputStatusInicial.value = selectStatusInicial.value;
                });
                inputStatusInicial.value = selectStatusInicial.value;
            }
        });
    </script>
@endsection

