@extends('layouts.operacional')
@section('title', 'Painel Operacional')

@section('content')

    @if (session('ok'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('ok') }}
        </div>
    @endif

    @if (session('erro'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ session('erro') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <p class="font-medium mb-1">Ocorreram alguns erros ao salvar:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="w-full px-4 md:px-8 py-8">

        {{-- Barra de busca --}}
        <div class="flex items-center gap-4 mb-6">
            <div class="flex-1">
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">🔍</span>
                    <input type="text" placeholder="Buscar..."
                           class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                </div>
            </div>

            <a href="{{ route('operacional.kanban.aso.clientes') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium shadow-sm">
                <span>Nova Tarefa</span>
            </a>
        </div>

        {{-- Título --}}
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-900">Painel Operacional</h1>
            <p class="text-sm text-slate-500">
                Suas tarefas atribuídas - {{ $usuario->name }}
            </p>
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
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status (Coluna)</label>
                <select name="coluna_id"
                        class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
                    <option value="">Todos os status</option>
                    @foreach($colunas as $col)
                        <option value="{{ $col->id }}" @selected($filtroColuna == $col->id)>
                            {{ $col->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Data inicial</label>
                <input type="date" name="de" value="{{ $filtroDe }}"
                       class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Data final</label>
                <input type="date" name="ate" value="{{ $filtroAte }}"
                       class="w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:ring-sky-400 focus:border-sky-400">
            </div>

            <div class="flex items-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-medium shadow-sm hover:bg-slate-800">
                    Filtrar
                </button>
            </div>
        </form>

        {{-- Kanban --}}
        <div class="w-full px-2 md:px-4 py-6">
            <div class="flex gap-3 md:gap-4 overflow-x-auto pb-4 justify-start md:justify-center">


            @foreach($colunas as $coluna)
                    @php
                        $tarefasColuna = $tarefasPorColuna[$coluna->id] ?? collect();
                    @endphp

                        <section
                            class="bg-white border border-slate-200 rounded-2xl flex flex-col h-[68vh]
                                 w-60 md:w-64 lg:w-72 flex-shrink-0 shadow-md"
                            >
                                <header
                                    class="flex items-center justify-between px-3 py-2 border-b border-slate-100
                                    bg-slate-50/90 rounded-t-2xl"
                                >
                                <div class="flex items-center gap-2 text-slate-700">
                                    <div class="w-2.5 h-2.5 rounded-full bg-[color:var(--color-brand-azul)] shadow-sm"></div>
                                    <h2 class="text-[13px] font-semibold tracking-tight">
                                        {{ $coluna->nome }}
                                    </h2>
                                </div>

                                <span
                                    class="inline-flex items-center justify-center min-w-[2rem] h-7 rounded-full
                                           bg-white text-[11px] font-semibold text-slate-600 border border-slate-200
                                           shadow-sm"
                                                        >
                                    {{ $tarefasColuna->count() }}
                                </span>
                        </header>

                            <div class="flex-1 overflow-y-auto px-3 py-3 space-y-3 kanban-column"
                                 data-coluna-id="{{ $coluna->id }}"
                                 data-coluna-cor="{{ $coluna->cor ?? '#38bdf8' }}">
                            @forelse($tarefasColuna as $tarefa)
                                @php
                                    $clienteNome  = optional($tarefa->cliente)->razao_social ?? 'Sem cliente';
                                    $servicoNome  = optional($tarefa->servico)->nome ?? 'Sem serviço';
                                    $respNome     = optional($tarefa->responsavel)->name ?? 'Sem responsável';
                                    $dataHora     = $tarefa->inicio_previsto
                                                    ? \Carbon\Carbon::parse($tarefa->inicio_previsto)->format('d/m/Y H:i')
                                                    : 'Sem data';
                                    $funcionarioNome  = optional($tarefa->funcionario)->nome ?? null;
                                    $funcionarioFuncao= optional($tarefa->funcionario)->funcao ?? null;
                                    $slaData      = $tarefa->fim_previsto
                                                    ? \Carbon\Carbon::parse($tarefa->fim_previsto)->format('d/m/Y')
                                                    : '-';
                                    $obs          = $tarefa->descricao ?? '';

                                    $clienteCnpj  = optional($tarefa->cliente)->cnpj ?? '';
                                    $clienteTel   = optional($tarefa->cliente)->telefone ?? '';
                                @endphp

                                <article
                                    class="kanban-card bg-white rounded-2xl shadow-md border border-slate-200 border-l-4
                                       px-3 py-3 text-xs cursor-pointer hover:shadow-lg transition
                                       hover:-translate-y-0.5"
                                    style="border-left-color: {{ $coluna->cor ?? '#38bdf8' }};"
                                    data-id="{{ $tarefa->id }}"
                                    data-move-url="{{ route('operacional.tarefas.mover', $tarefa) }}"
                                    {{-- dados para o modal de detalhes --}}
                                    data-cliente="{{ $clienteNome }}"
                                    data-cnpj="{{ $clienteCnpj }}"
                                    data-telefone="{{ $clienteTel }}"
                                    data-servico="{{ $servicoNome }}"
                                    data-responsavel="{{ $respNome }}"
                                    data-datahora="{{ $dataHora }}"
                                    data-funcionario="{{ $funcionarioNome }}"
                                    data-funcionario-funcao="{{ $funcionarioFuncao }}"
                                    data-sla="{{ $slaData }}"
                                    data-prioridade="{{ ucfirst($tarefa->prioridade) }}"
                                    data-status="{{ $coluna->nome }}"
                                    data-observacoes="{{ e($obs) }}"
                                >
                                    <p class="text-[11px] font-semibold text-slate-900 mb-1">
                                        {{ $clienteNome }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 mb-2">
                                        {{ $servicoNome }}
                                    </p>

                                    <div class="flex items-center justify-between mb-2">
                                        <span class="inline-flex items-center rounded-full bg-[color:var(--color-brand-azul)]/5
                                                     px-2 py-0.5 text-[10px] text-[color:var(--color-brand-azul)]">
                                            {{ $respNome }}
                                        </span>

                                        @php
                                            $coresPrioridade = [
                                                'baixa' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                'media' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'alta'  => 'bg-rose-100 text-rose-700 border-rose-200',
                                            ];
                                            $classePrioridade = $coresPrioridade[$tarefa->prioridade] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        @endphp

                                        <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-medium {{ $classePrioridade }}">
                        {{ ucfirst($tarefa->prioridade) }}
                    </span>
                                    </div>

{{--                                    @php--}}
{{--                                        $ultimoLog = optional($tarefa->logs)->sortByDesc('created_at')->first();--}}
{{--                                    @endphp--}}

{{--                                    @if($ultimoLog)--}}
{{--                                        <div class="mt-2 pt-2 border-t border-dashed border-slate-200 text-[10px] text-slate-400">--}}
{{--                                            <div class="flex items-center justify-between gap-2">--}}
{{--                            <span class="inline-flex items-center gap-1">--}}
{{--                                <span>🔁</span>--}}
{{--                                <span>--}}
{{--                                    {{ optional($ultimoLog->deColuna)->nome ?? 'Início' }}--}}
{{--                                    →--}}
{{--                                    {{ optional($ultimoLog->paraColuna)->nome ?? '-' }}--}}
{{--                                </span>--}}
{{--                            </span>--}}
{{--                                                <span class="text-[10px] text-slate-400">--}}
{{--                                {{ optional($ultimoLog->user)->name ?? 'Sistema' }}--}}
{{--                                · {{ optional($ultimoLog->created_at)->format('d/m H:i') }}--}}
{{--                            </span>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    @endif--}}

                                    <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                                        @if($tarefa->inicio_previsto)
                                            <span>📅 {{ \Carbon\Carbon::parse($tarefa->inicio_previsto)->format('d/m/Y H:i') }}</span>
                                        @else
                                            <span>📅 Sem data</span>
                                        @endif

                                            <span class="font-medium text-[color:var(--color-brand-azul)]"
                                                  data-role="card-status-label">
                                                {{ $coluna->nome }}
                                            </span>

                                    </div>
                                </article>
                            @empty
                                <p class="text-[11px] text-slate-400 italic">Nenhuma tarefa nesta coluna.</p>
                            @endforelse
                        </div>
                    </section>


                @endforeach
            </div>
        </div>
    </div>

    {{-- Modal de Detalhes da Tarefa --}}
    <div id="tarefa-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col">
            {{-- Cabeçalho --}}
            <header class="flex items-center justify-between px-6 py-4 bg-[color:var(--color-brand-azul)] text-white rounded-t-2xl">
                <div>
                    <h2 class="text-lg font-semibold">Detalhes da Tarefa</h2>
                    <p class="text-xs text-white/80">
                        ID: <span id="modal-tarefa-id">-</span>
                    </p>
                </div>
                <button type="button"
                        id="tarefa-modal-close"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20">
                    ✕
                </button>
            </header>

            {{-- Conteúdo --}}
            <div class="flex-1 overflow-y-auto p-6 grid grid-cols-1 lg:grid-cols-[2fr,1.5fr] gap-5 text-sm text-slate-700">
                {{-- Coluna esquerda --}}
                <div class="space-y-4">
                    {{-- 1. Detalhes da solicitação --}}
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-slate-500 mb-3">
                            1. DETALHES DA SOLICITAÇÃO
                        </h3>
                        <dl class="space-y-1 text-sm">
                            <div>
                                <dt class="text-[11px] text-slate-500">Razão Social</dt>
                                <dd class="font-semibold" id="modal-cliente"></dd>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                                <div>
                                    <dt class="text-[11px] text-slate-500">CNPJ</dt>
                                    <dd class="font-medium" id="modal-cnpj">—</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] text-slate-500">Telefone</dt>
                                    <dd class="font-medium" id="modal-telefone">—</dd>
                                </div>
                            </div>
                            <div class="mt-2">
                                <dt class="text-[11px] text-slate-500">Responsável</dt>
                                <dd class="font-medium" id="modal-responsavel"></dd>
                            </div>

                            <div class="mt-2">
                                <dt class="text-[11px] text-slate-500">Funcionário</dt>
                                <dd class="font-medium" id="modal-funcionario">—</dd>
                            </div>
                            <div class="mt-1">
                                <dt class="text-[11px] text-slate-500">Função</dt>
                                <dd class="font-medium" id="modal-funcionario-funcao">—</dd>
                            </div>
                        </dl>
                    </section>

                    {{-- 2. Status atual --}}
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-slate-500 mb-3">
                            2. STATUS ATUAL
                        </h3>
                        <span id="modal-status-badge"
                              class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200">
                        <span id="modal-status-text"></span>
                    </span>
                    </section>

                    {{-- 3. Descrição da tarefa --}}
                    <section class="bg-violet-50 border border-violet-100 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-violet-700 mb-2">
                            3. DESCRIÇÃO DA TAREFA
                        </h3>
                        <p class="font-semibold text-slate-800 mb-1" id="modal-servico"></p>
                        <p class="text-sm text-slate-700" id="modal-observacoes"></p>
                    </section>

                    {{-- 6. Tipo de serviço --}}
                    <section class="bg-emerald-50 border border-emerald-100 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-emerald-700 mb-2">
                            6. TIPO DE SERVIÇO
                        </h3>
                        <p class="font-semibold" id="modal-tipo-servico"></p>
                    </section>

                    {{-- 7. Informações de data --}}
                    <section class="bg-amber-50 border border-amber-100 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-amber-700 mb-3">
                            7. INFORMAÇÕES DE DATA
                        </h3>
                        <dl class="space-y-1 text-sm">
                            <div>
                                <dt class="text-[11px] text-slate-600">Início previsto</dt>
                                <dd class="font-medium" id="modal-datahora"></dd>
                            </div>
                            <div class="mt-2">
                                <dt class="text-[11px] text-slate-600">Prazo / SLA</dt>
                                <dd class="font-medium" id="modal-sla"></dd>
                            </div>
                            <div class="mt-2">
                                <dt class="text-[11px] text-slate-600">Prioridade</dt>
                                <dd class="font-medium" id="modal-prioridade"></dd>
                            </div>
                        </dl>
                    </section>
                </div>

                {{-- Coluna direita --}}
                <div class="space-y-4">
                    {{-- 4. Ações rápidas --}}
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-slate-500 mb-3">
                            4. AÇÕES RÁPIDAS
                        </h3>

                        <div class="space-y-3">
                            <button type="button"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
               bg-[var(--color-brand-azul)] text-white text-sm font-semibold shadow-sm
               hover:bg-blue-700 transition">
                                Mover para: Em Execução
                            </button>

                            <button type="button"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                                       bg-rose-500 text-white text-sm font-semibold shadow-sm
                                       hover:bg-rose-600 transition">
                                Mover para: Atrasado
                            </button>
                        </div>
                    </section>

                    {{-- 6. Adicionar observação interna --}}
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-4 h-full">
                        <h3 class="text-xs font-semibold text-slate-500 mb-3">
                            6. ADICIONAR OBSERVAÇÃO INTERNA
                        </h3>
                        <textarea
                            id="modal-observacao-interna"
                            rows="6"
                            class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2
                               focus:outline-none focus:ring-2 focus:ring-[color:var(--color-brand-azul)]/60
                               focus:border-[color:var(--color-brand-azul)] resize-none"
                            placeholder="Digite suas observações..."
                        ></textarea>

                        <button
                            type="button"
                            class="mt-3 w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                               bg-indigo-400 text-white text-sm font-semibold shadow-sm
                               hover:bg-indigo-500 transition">
                            Salvar Observação
                        </button>
                    </section>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal      = document.getElementById('tarefa-modal');
            const closeBtn   = document.getElementById('tarefa-modal-close');

            const spanId         = document.getElementById('modal-tarefa-id');
            const spanCliente    = document.getElementById('modal-cliente');
            const spanCnpj       = document.getElementById('modal-cnpj');
            const spanFuncionario       = document.getElementById('modal-funcionario');
            const spanFuncionarioFuncao = document.getElementById('modal-funcionario-funcao');
            const spanTelefone   = document.getElementById('modal-telefone');
            const spanResp       = document.getElementById('modal-responsavel');
            const spanServico    = document.getElementById('modal-servico');
            const spanTipoServ   = document.getElementById('modal-tipo-servico');
            const spanDataHora   = document.getElementById('modal-datahora');
            const spanSla        = document.getElementById('modal-sla');
            const spanPrioridade = document.getElementById('modal-prioridade');
            const spanStatusText = document.getElementById('modal-status-text');
            const badgeStatus    = document.getElementById('modal-status-badge');
            const spanObs        = document.getElementById('modal-observacoes');

            // abre modal ao clicar em qualquer card
            document.addEventListener('click', function (e) {
                const card = e.target.closest('.kanban-card');
                if (!card) return;

                // Preenche os campos
                spanId.textContent         = card.dataset.id ?? '';
                spanCliente.textContent    = card.dataset.cliente ?? '';
                spanCnpj.textContent       = card.dataset.cnpj || '—';
                spanTelefone.textContent   = card.dataset.telefone || '—';
                spanResp.textContent       = card.dataset.responsavel ?? '';
                spanServico.textContent    = card.dataset.servico ?? '';
                spanTipoServ.textContent   = card.dataset.servico ?? '';
                spanDataHora.textContent   = card.dataset.datahora ?? '';
                spanSla.textContent        = card.dataset.sla ?? '-';
                spanPrioridade.textContent = card.dataset.prioridade ?? '';
                spanStatusText.textContent = card.dataset.status ?? '';
                spanObs.textContent        = card.dataset.observacoes ?? '';
                spanFuncionario.textContent       = card.dataset.funcionario || '—';
                spanFuncionarioFuncao.textContent = card.dataset.funcionarioFuncao || '—';

                // ajusta cor do badge de status conforme o texto
                const status = (card.dataset.status || '').toLowerCase();
                badgeStatus.className =
                    'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border ';

                if (status.includes('pendente')) {
                    badgeStatus.className += 'bg-amber-100 text-amber-700 border-amber-200';
                } else if (status.includes('execução') || status.includes('execucao')) {
                    badgeStatus.className += 'bg-blue-100 text-blue-700 border-blue-200';
                } else if (status.includes('atrasado')) {
                    badgeStatus.className += 'bg-rose-100 text-rose-700 border-rose-200';
                } else {
                    badgeStatus.className += 'bg-slate-100 text-slate-700 border-slate-200';
                }

                // mostra modal
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });

            // fechar modal
            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            closeBtn.addEventListener('click', closeModal);

            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            if (window.Sortable) {
                document.querySelectorAll('.kanban-column').forEach(function (colunaEl) {
                    new Sortable(colunaEl, {
                        group: 'kanban',
                        animation: 150,
                        handle: '.kanban-card',
                        draggable: '.kanban-card',
                        onEnd: function (evt) {
                            const card = evt.item;
                            const colunaId = card.closest('.kanban-column').dataset.colunaId;
                            const moveUrl  = card.dataset.moveUrl;

                            const colunaCor = colunaEl?.dataset.colunaCor || '#38bdf8';
                            if (colunaCor) {
                                card.style.borderLeftColor = colunaCor;
                            }

                            if (!moveUrl || !colunaId) return;

                            fetch(moveUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    coluna_id: colunaId,
                                }),
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (!data || !data.ok) return;

                                    // nome da coluna (status)
                                    const colunaSection = card.closest('section');
                                    const headerTitleEl = colunaSection
                                        ? colunaSection.querySelector('header h2')
                                        : null;

                                    const statusName = data.status_label
                                        || (headerTitleEl ? headerTitleEl.textContent.trim() : '');

                                    // Atualiza texto do status no card
                                    const statusSpan = card.querySelector('[data-role="card-status-label"]');
                                    if (statusSpan && statusName) {
                                        statusSpan.textContent = statusName;
                                    }

                                    // Atualiza atributo data-status (usado no modal)
                                    if (statusName) {
                                        card.dataset.status = statusName;
                                    }

                                    const respBadge = card.querySelector('[data-role="card-responsavel-badge"]');
                                    if (respBadge && colunaCor) {
                                        respBadge.style.borderColor = colunaCor;
                                        respBadge.style.color = colunaCor;
                                    }

                                    // Atualiza bloco de log, se veio no retorno
                                    if (data.log) {
                                        const logContainer = card.querySelector('[data-role="card-last-log"]');
                                        if (logContainer) {
                                            logContainer.innerHTML = `
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center gap-1">
                                            <span>🔁</span>
                                            <span>
                                                ${(data.log.de || 'Início')}
                                                &rarr;
                                                ${(data.log.para || '-')}
                                            </span>
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                            ${(data.log.user || 'Sistema')}
                                            · ${(data.log.data || '')}
                                        </span>
                                    </div>
                                `;
                                        }
                                    }
                                })
                                .catch(() => {
                                    // aqui dá pra fazer um toast de erro se quiser
                                });
                        }
                    });
                });
            }

        });
    </script>

@endpush
