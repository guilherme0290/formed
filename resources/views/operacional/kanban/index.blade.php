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
                                    $funcionarioCpf  = optional($tarefa->funcionario)->cpf ?? null;
                                    $funcionarioFuncao= optional($tarefa->funcionario)->funcao_nome ?? null;
                                    $slaData      = $tarefa->fim_previsto
                                                    ? \Carbon\Carbon::parse($tarefa->fim_previsto)->format('d/m/Y')
                                                    : '-';
                                    $obs          = $tarefa->descricao ?? '';

                                    $clienteCnpj  = optional($tarefa->cliente)->cnpj ?? '';
                                    $clienteTel   = optional($tarefa->cliente)->telefone ?? '';
                                    $pgr = $tarefa->pgrSolicitacao ?? null;
                                     $ltip = $tarefa->ltipSolicitacao;
                                @endphp



                                    <article
                                        class="kanban-card bg-white rounded-2xl shadow-md border border-slate-200 border-l-4
                                        px-3 py-3 text-xs cursor-pointer hover:shadow-lg transition hover:-translate-y-0.5"
                                        style="border-left-color: {{ $coluna->cor ?? '#38bdf8' }};"

                                        data-move-url="{{ route('operacional.tarefas.mover', $tarefa) }}"

                                        data-id="{{ $tarefa->id }}"
                                        data-cliente="{{ $clienteNome }}"
                                        data-cnpj="{{ $clienteCnpj }}"
                                        data-telefone="{{ $clienteTel }}"
                                        data-servico="{{ $servicoNome }}"
                                        data-responsavel="{{ $respNome }}"
                                        data-datahora="{{ $dataHora }}"
                                        data-sla="{{ $slaData }}"
                                        data-prioridade="{{ ucfirst($tarefa->prioridade) }}"
                                        data-status="{{ $coluna->nome }}"
                                        data-observacoes="{{ e($obs) }}"
                                        data-funcionario="{{ $funcionarioNome . ' | CPF '. $funcionarioCpf }}"
                                        data-funcionario-funcao="{{ $funcionarioFuncao }}"
                                        data-observacao-interna="{{ e($tarefa->observacao_interna) }}"
                                        data-observacao-url="{{ route('operacional.tarefas.observacao', $tarefa) }}"

                                        {{-- PGR --}}
                                        @if($pgr)
                                            data-pgr-tipo="{{ $pgr->tipo }}"
                                            data-pgr-com-art="{{ $pgr->com_art ? '1' : '0' }}"
                                            data-pgr-qtd-homens="{{ $pgr->qtd_homens }}"
                                            data-pgr-qtd-mulheres="{{ $pgr->qtd_mulheres }}"
                                            data-pgr-total-trabalhadores="{{ $pgr->total_trabalhadores }}"
                                            data-pgr-com-pcmso="{{ $pgr->com_pcms0 ? '1' : '0' }}"
                                            data-pgr-contratante="{{ $pgr->contratante_nome }}"
                                            data-pgr-contratante-cnpj="{{ $pgr->contratante_cnpj }}"
                                            data-pgr-obra-nome="{{ $pgr->obra_nome }}"
                                            data-pgr-obra-endereco="{{ $pgr->obra_endereco }}"
                                            data-pgr-obra-cej-cno="{{ $pgr->obra_cej_cno }}"
                                            data-pgr-obra-turno="{{ $pgr->obra_turno_trabalho }}"
                                            data-pgr-funcoes="{{ $pgr->funcoes_resumo }}"
                                        @endif

                                        {{-- APR --}}
                                        @if($tarefa->aprSolicitacao)
                                            data-apr-endereco="{{ $tarefa->aprSolicitacao->endereco_atividade }}"
                                            data-apr-funcoes="{{ e($tarefa->aprSolicitacao->funcoes_envolvidas) }}"
                                            data-apr-etapas="{{ e($tarefa->aprSolicitacao->etapas_atividade) }}"
                                        @endif

                                        {{-- LTCAT --}}
                                        @if($tarefa->ltcatSolicitacao)
                                            data-ltcat-tipo="{{ $tarefa->ltcatSolicitacao->tipo }}"
                                        data-ltcat-endereco="{{ $tarefa->ltcatSolicitacao->endereco_avaliacoes }}"
                                        data-ltcat-total-funcoes="{{ $tarefa->ltcatSolicitacao->total_funcoes }}"
                                        data-ltcat-total-funcionarios="{{ $tarefa->ltcatSolicitacao->total_funcionarios }}"
                                        data-ltcat-funcoes="{{ $tarefa->ltcatSolicitacao->funcoes_resumo }}"
                                        @endif

                                        {{-- LTIP --}}
                                        @if($servicoNome === 'LTIP' && $ltip)
                                            {{-- data-* usados no modal --}}

                                            data-ltip-endereco="{{ $ltip->endereco_avaliacoes }}"
                                        data-ltip-total-funcionarios="{{ $ltip->total_funcionarios }}"
                                        data-ltip-funcoes="{{ collect($ltip->funcoes ?? [])->map(function($f){
                                            $fn = \App\Models\Funcao::find($f['funcao_id'] ?? null);
                                            return ($fn?->nome ?? 'Função') . ' (' . ($f['quantidade'] ?? 0) . ')';
                                        })->implode(' | ') }}"
                                        @endif

                                        {{-- PAE --}}
                                        @if($tarefa->paeSolicitacao)
                                            @php $pae = $tarefa->paeSolicitacao; @endphp
                                            data-pae-endereco="{{ $pae->endereco_local }}"
                                        data-pae-total-funcionarios="{{ $pae->total_funcionarios }}"
                                        data-pae-descricao="{{ $pae->descricao_instalacoes }}"
                                        @endif

                                        {{-- PCMSO --}}
                                        @if($tarefa->pcmsoSolicitacao)
                                            @php $pcmso = $tarefa->pcmsoSolicitacao; @endphp
                                            data-pcmso-tipo="{{ $pcmso->tipo }}"
                                            data-pcmso-prazo="{{ $pcmso->prazo_dias }}"
                                            data-pcmso-obra-nome="{{ $pcmso->obra_nome }}"
                                            data-pcmso-obra-cnpj="{{ $pcmso->obra_cnpj_contratante }}"
                                            data-pcmso-obra-cei="{{ $pcmso->obra_cei_cno }}"
                                            data-pcmso-obra-endereco="{{ $pcmso->obra_endereco }}"
                                            @if($pcmso->pgr_arquivo_path)
                                                data-pcmso-pgr-url="{{ asset('storage/'.$pcmso->pgr_arquivo_path) }}"
                                            @endif
                                        @endif
                                        @if($tarefa->treinamentoNr && $tarefa->treinamentoNrDetalhes)
                                            @php
                                                $treiFuncs = $tarefa->treinamentoNr()->with('funcionario')->get();
                                                $treiDet   = $tarefa->treinamentoNrDetalhes;
                                                $listaNomes = $treiFuncs->pluck('funcionario.nome')->join(', ');
                                                $listaFuncoes = $treiFuncs->pluck('funcionario.funcao')->join(', ');
                                            @endphp

                                            data-treinamento-participantes="{{ $listaNomes }}"
                                        data-treinamento-funcoes="{{ $listaFuncoes }}"
                                        data-treinamento-local="{{ $treiDet->local_tipo }}"
                                        data-treinamento-unidade="{{ optional($treiDet->unidade)->nome ?? '' }}"
                                        @endif
                                    >
                                        {{-- ================== CABEÇALHO ================== --}}
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="text-[13px] font-semibold text-slate-800">
                                                {{ $servicoNome }}
                                            </h4>
                                            <span class="text-[10px] px-2 py-0.5 rounded bg-slate-100 border text-slate-500">
                                            #{{ $tarefa->id }}
                                        </span>
                                        </div>

                                        {{-- ================== CLIENTE ================== --}}
                                        <p class="text-[11px] text-slate-700 font-medium">
                                            {{ $clienteNome }}
                                        </p>

                                        {{-- ================== BLOCO DINÂMICO POR SERVIÇO ================== --}}

                                        @if($servicoNome === 'ASO')
                                            @php
                                                $func = optional($tarefa->funcionario);
                                            @endphp
                                            <div class="mt-2 text-[11px] space-y-0.5">
                                                <p><span class="font-medium">Funcionário:</span> {{ $func->nome ?? '—' }}</p>
                                                <p><span class="font-medium">Função:</span> {{  $funcionarioFuncao ?? '—' }}</p>
                                            </div>
                                        @endif

                                        {{-- PGR --}}
                                        @if($servicoNome === 'PGR' && $pgr)
                                            <div class="mt-2 bg-emerald-50 border border-emerald-100 rounded p-2 text-[11px]">
                                                <p><b>Tipo:</b> {{ $pgr->tipo }}</p>
                                                <p><b>Total trabalhadores:</b> {{ $pgr->total_trabalhadores }}</p>

                                                <p class="mt-1"><b>Funções:</b></p>
                                                <ul class="list-disc list-inside text-[10px]">
                                                    @foreach($pgr->funcoes ?? [] as $f)
                                                        @php $fn = \App\Models\Funcao::find($f['funcao_id']); @endphp
                                                        <li>{{ $fn?->nome }} ({{ $f['quantidade'] }})</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        {{-- APR --}}
                                        @if($servicoNome === 'APR' && $tarefa->aprSolicitacao)
                                            <div class="mt-2 bg-purple-50 border border-purple-100 rounded p-2 text-[11px]">
                                                <p><b>Endereço:</b> {{ $tarefa->aprSolicitacao->endereco_atividade }}</p>

                                                @if($tarefa->aprSolicitacao->funcoes_envolvidas)
                                                    <p class="mt-1">
                                                        <b>Funções envolvidas:</b>
                                                        {{ $tarefa->aprSolicitacao->funcoes_envolvidas }}
                                                    </p>
                                                @endif

                                                @if($tarefa->aprSolicitacao->etapas_atividade)
                                                    <p class="mt-1">
                                                        <b>Etapas da atividade:</b>
                                                        {{ $tarefa->aprSolicitacao->etapas_atividade }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- LTCAT --}}
                                        @if($servicoNome === 'LTCAT' && $tarefa->ltcatSolicitacao)
                                            @php
                                                $lt = $tarefa->ltcatSolicitacao;
                                                $tipoLabel = $lt->tipo === 'matriz'
                                                    ? 'Matriz'
                                                    : ($lt->tipo === 'especifico' ? 'Específico' : ucfirst($lt->tipo));
                                            @endphp

                                            <div class="mt-2 bg-orange-50 border border-orange-100 rounded p-2 text-[11px] space-y-0.5">
                                                <p><b>Tipo:</b> {{ $tipoLabel ?: '—' }}</p>

                                                @if($lt->endereco_avaliacoes)
                                                    <p><b>Endereço:</b> {{ $lt->endereco_avaliacoes }}</p>
                                                @endif

                                                <p>
                                                    <b>Total Funções:</b> {{ $lt->total_funcoes ?? '—' }}
                                                    &nbsp;|&nbsp;
                                                    <b>Total Funcionários:</b> {{ $lt->total_funcionarios ?? '—' }}
                                                </p>

                                                @if($lt->funcoes_resumo)
                                                    <p><b>Funções:</b> {{ $lt->funcoes_resumo }}</p>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- LTIP --}}
                                        @if($servicoNome === 'LTIP' && $ltip)
                                            <div class="mt-2 bg-blue-50 border border-blue-100 rounded p-2 text-[11px]">
                                                <p><b>Endereço:</b> {{ $ltip->endereco_avaliacoes }}</p>
                                                <p><b>Total Funcionários:</b> {{ $ltip->total_funcionarios }}</p>

                                                @if(is_array($ltip->funcoes))
                                                    <p class="mt-1"><b>Funções:</b></p>
                                                    <ul class="list-disc list-inside text-[10px]">
                                                        @foreach($ltip->funcoes as $f)
                                                            @php
                                                                $fn = \App\Models\Funcao::find($f['funcao_id'] ?? null);
                                                            @endphp
                                                            <li>{{ $fn?->nome ?? 'Função' }} ({{ $f['quantidade'] ?? 0 }})</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- PAE --}}
                                        @if($servicoNome === 'PAE' && $tarefa->paeSolicitacao)
                                            @php $pae = $tarefa->paeSolicitacao; @endphp
                                            <div class="mt-2 bg-red-50 border border-red-100 rounded p-2 text-[11px] space-y-0.5">
                                                <p><b>Endereço Local:</b> {{ $pae->endereco_local }}</p>
                                                <p><b>Total de funcionários:</b> {{ $pae->total_funcionarios }}</p>
                                                @if($pae->descricao_instalacoes)
                                                    <p><b>Instalações:</b> {{ $pae->descricao_instalacoes }}</p>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- PCMSO --}}
                                        @if($servicoNome === 'PCMSO' && $tarefa->pcmsoSolicitacao)
                                            <div class="mt-2 bg-cyan-50 border border-cyan-100 rounded p-2 text-[11px]">
                                                <p><b>Tipo:</b> {{ $tarefa->pcmsoSolicitacao->tipo }}</p>
                                                <p><b>Prazo:</b> {{ $tarefa->pcmsoSolicitacao->prazo_dias }} dias</p>
                                            </div>
                                        @endif
                                        @if($servicoNome === 'Treinamentos NRs' && $tarefa->treinamentoNr && $tarefa->treinamentoNrDetalhes)
                                            @php
                                                $treiFuncs = $tarefa->treinamentoNr()->with('funcionario.funcao')->get();
                                                $treiDet   = $tarefa->treinamentoNrDetalhes;

                                                $listaParticipantes = $treiFuncs->pluck('funcionario.nome')->join(', ');

                                                $listaFuncoes = $treiFuncs->map(function($t){
                                                    return $t->funcionario->funcao_nome; // accessor
                                                })->join(', ');
                                            @endphp

                                            {{-- Datasets usados no modal --}}
                                            <span
                                                data-trei-local="{{ $treiDet->local_tipo }}"
                                                data-trei-unidade="{{ optional($treiDet->unidade)->nome }}"
                                                data-trei-participantes="{{ $listaParticipantes }}"
                                                data-trei-funcoes="{{ $listaFuncoes }}"
                                            ></span>

                                            <div class="mt-2 bg-indigo-50 border border-indigo-100 rounded p-2 text-[11px] space-y-1">
                                                <p><b>Local:</b>
                                                    {{ $treiDet->local_tipo === 'clinica' ? 'Clínica' : 'In Company' }}
                                                    @if($treiDet->local_tipo === 'clinica')
                                                        · {{ optional($treiDet->unidade)->nome }}
                                                    @endif
                                                </p>

                                                <p><b>Participantes:</b> {{ $listaParticipantes }}</p>
                                                <p><b>Funções:</b> {{ $listaFuncoes }}</p>
                                            </div>
                                        @endif

                                        {{-- ================== RODAPÉ ================== --}}
                                        <div class="mt-3 border-t pt-2 text-[10px] text-slate-500 flex justify-between">
                                            <span>{{ $dataHora }}</span>
                                            <span>{{ ucfirst($tarefa->prioridade) }}</span>
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

                            {{-- BLOCO ESPECÍFICO: ASO --}}

                            <div id="modal-bloco-aso" class="mt-2 space-y-1">
                                <div>
                                    <dt class="text-[11px] text-slate-500">Funcionário</dt>
                                    <dd class="font-medium" id="modal-funcionario">—</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] text-slate-500">Função</dt>
                                    <dd class="font-medium" id="modal-funcionario-funcao">—</dd>
                                </div>
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
                    {{-- BLOCO PGR (aparece só em serviços PGR) --}}
                    <section id="modal-bloco-pgr"
                             class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-emerald-700 mb-2">
                            4. DETALHES DO PGR
                        </h3>

                        <dl class="space-y-1 text-sm">
                            <div>
                                <dt class="text-[11px] text-slate-600">Tipo PGR</dt>
                                <dd class="font-medium" id="modal-pgr-tipo">—</dd>
                            </div>

                            <div class="mt-1">
                                <dt class="text-[11px] text-slate-600">ART</dt>
                                <dd class="font-medium" id="modal-pgr-art">—</dd>
                            </div>

                            <div class="grid grid-cols-3 gap-3 mt-2">
                                <div>
                                    <dt class="text-[11px] text-slate-600">Homens</dt>
                                    <dd class="font-medium" id="modal-pgr-qtd-homens">—</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] text-slate-600">Mulheres</dt>
                                    <dd class="font-medium" id="modal-pgr-qtd-mulheres">—</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] text-slate-600">Total</dt>
                                    <dd class="font-medium" id="modal-pgr-total-trabalhadores">—</dd>
                                </div>
                            </div>

                            <div class="mt-2">
                                <dt class="text-[11px] text-slate-600">Com PCMSO?</dt>
                                <dd class="font-medium" id="modal-pgr-com-pcmso">—</dd>
                            </div>

                            <div class="mt-3">
                                <dt class="text-[11px] text-slate-600">Contratante</dt>
                                <dd class="font-medium" id="modal-pgr-contratante">—</dd>
                                <dd class="text-xs text-slate-500" id="modal-pgr-contratante-cnpj">—</dd>
                            </div>

                            <div class="mt-3">
                                <dt class="text-[11px] text-slate-600">Obra</dt>
                                <dd class="font-medium" id="modal-pgr-obra-nome">—</dd>
                                <dd class="text-xs text-slate-500" id="modal-pgr-obra-endereco">—</dd>
                                <dd class="text-xs text-slate-500" id="modal-pgr-obra-cej-cno">—</dd>
                                <dd class="text-xs text-slate-500" id="modal-pgr-obra-turno">—</dd>
                            </div>

                            <div class="mt-3">
                                <dt class="text-[11px] text-slate-600">Funções / Cargos</dt>
                                <dd id="modal-pgr-funcoes" class="text-sm">—</dd>
                            </div>
                        </dl>
                    </section>
                    {{-- ====================== APR ====================== --}}
                    <section id="modal-bloco-apr"
                             class="bg-purple-50 border border-purple-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-purple-700 mb-2">DETALHES DO APR</h3>

                        <p><b>Endereço da atividade:</b> <span id="modal-apr-endereco"></span></p>
                        <p class="mt-1"><b>Funções envolvidas:</b> <span id="modal-apr-funcoes"></span></p>
                        <p class="mt-1"><b>Etapas da atividade:</b> <span id="modal-apr-etapas"></span></p>
                    </section>

                    {{-- ====================== LTCAT ====================== --}}
                    <section id="modal-bloco-ltcat"
                             class="bg-orange-50 border border-orange-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-orange-700 mb-2">DETALHES DO LTCAT</h3>

                        <p><b>Tipo:</b> <span id="modal-ltcat-tipo"></span></p>
                        <p><b>Endereço das Avaliações:</b> <span id="modal-ltcat-endereco"></span></p>

                        <p class="mt-1">
                            <b>Total Funções:</b> <span id="modal-ltcat-total-funcoes"></span>
                            &nbsp;|&nbsp;
                            <b>Total Funcionários:</b> <span id="modal-ltcat-total-func"></span>
                        </p>

                        <p class="mt-1">
                            <b>Funções:</b>
                            <span id="modal-ltcat-funcoes"></span>
                        </p>
                    </section>

                    {{-- ====================== LTIP ====================== --}}
                    <section id="modal-bloco-ltip"
                             class="bg-blue-50 border border-blue-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-blue-700 mb-2">DETALHES DO LTIP</h3>
                        <p><b>Endereço das avaliações:</b> <span id="modal-ltip-endereco"></span></p>
                        <p><b>Funções:</b> <span id="modal-ltip-funcoes"></span></p>
                        <p><b>Total Funcionários:</b> <span id="modal-ltip-total-func"></span></p>
                    </section>
                    {{-- ====================== PAE ====================== --}}
                    <section id="modal-bloco-pae"
                             class="bg-red-50 border border-red-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-red-700 mb-2">DETALHES DO PAE</h3>

                        <p><b>Endereço Local:</b> <span id="modal-pae-endereco"></span></p>
                        <p><b>Total de funcionários:</b> <span id="modal-pae-total-func"></span></p>

                        <div class="mt-1">
                            <p class="text-[11px] text-red-700 font-semibold mb-0.5">Descrição das instalações</p>
                            <p class="text-sm" id="modal-pae-descricao">—</p>
                        </div>
                    </section>
                    {{-- ==================== TREINAMENTO NR ==================== --}}
                    <section id="modal-bloco-treinamento"
                             class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-indigo-700 mb-2">DETALHES DO TREINAMENTO NR</h3>

                        <p><b>Local:</b> <span id="modal-treinamento-local"></span></p>

                        <p><b>Unidade / Clínica:</b> <span id="modal-treinamento-unidade"></span></p>

                        <div class="mt-2">
                            <p class="text-[11px] font-semibold text-indigo-700">Participantes</p>
                            <p id="modal-treinamento-participantes" class="text-sm">—</p>
                        </div>

                        <div class="mt-2">
                            <p class="text-[11px] font-semibold text-indigo-700">Funções</p>
                            <p id="modal-treinamento-funcoes" class="text-sm">—</p>
                        </div>
                    </section>

                    {{-- ====================== PCMSO ====================== --}}
                    <section id="modal-bloco-pcmso"
                             class="bg-cyan-50 border border-cyan-100 rounded-xl p-4 hidden">
                        <h3 class="text-xs font-semibold text-cyan-700 mb-2">DETALHES DO PCMSO</h3>

                        <p><b>Tipo:</b> <span id="modal-pcmso-tipo"></span></p>
                        <p><b>Prazo:</b> <span id="modal-pcmso-prazo"></span> dias</p>

                        <div class="mt-2 space-y-0.5">
                            <p><b>Obra / Unidade:</b> <span id="modal-pcmso-obra-nome"></span></p>
                            <p><b>CNPJ Contratante:</b> <span id="modal-pcmso-obra-cnpj"></span></p>
                            <p><b>CEI / CNO:</b> <span id="modal-pcmso-obra-cei"></span></p>
                            <p><b>Endereço da Obra:</b> <span id="modal-pcmso-obra-endereco"></span></p>
                        </div>

                        <div id="modal-pcmso-pgr-wrapper" class="mt-3">
                            <a id="modal-pcmso-pgr-link"
                               href="#"
                               target="_blank"
                               class="inline-flex items-center gap-1 text-xs font-semibold text-cyan-700 hover:text-cyan-900 underline">
                                📎 Abrir PGR anexado (PDF)
                            </a>
                        </div>
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
                {{-- BLOCO ESPECÍFICO: PGR --}}
                <section id="modal-bloco-pgr"
                         class="bg-sky-50 border border-sky-100 rounded-xl p-4 hidden">
                    <h3 class="text-xs font-semibold text-sky-700 mb-3">
                        8. DADOS DO PGR
                    </h3>

                    <dl class="space-y-1 text-sm">
                        <div>
                            <dt class="text-[11px] text-slate-600">Tipo PGR</dt>
                            <dd class="font-medium" id="modal-pgr-tipo">—</dd>
                        </div>

                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">ART</dt>
                            <dd class="font-medium" id="modal-pgr-art">—</dd>
                        </div>

                        <div class="mt-2 grid grid-cols-3 gap-3">
                            <div>
                                <dt class="text-[11px] text-slate-600">Homens</dt>
                                <dd class="font-medium" id="modal-pgr-qtd-homens">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-600">Mulheres</dt>
                                <dd class="font-medium" id="modal-pgr-qtd-mulheres">—</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-slate-600">Total</dt>
                                <dd class="font-medium" id="modal-pgr-total-trabalhadores">—</dd>
                            </div>
                        </div>

                        <div class="mt-2">
                            <dt class="text-[11px] text-slate-600">Com PCMSO?</dt>
                            <dd class="font-medium" id="modal-pgr-com-pcmso">—</dd>
                        </div>

                        <hr class="my-3 border-sky-100">

                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">Contratante</dt>
                            <dd class="font-medium" id="modal-pgr-contratante">—</dd>
                        </div>
                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">CNPJ Contratante</dt>
                            <dd class="font-medium" id="modal-pgr-contratante-cnpj">—</dd>
                        </div>

                        <div class="mt-3">
                            <dt class="text-[11px] text-slate-600">Nome da Obra</dt>
                            <dd class="font-medium" id="modal-pgr-obra-nome">—</dd>
                        </div>
                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">Endereço da Obra</dt>
                            <dd class="font-medium" id="modal-pgr-obra-endereco">—</dd>
                        </div>
                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">CEJ/CNO</dt>
                            <dd class="font-medium" id="modal-pgr-obra-cej-cno">—</dd>
                        </div>
                        <div class="mt-1">
                            <dt class="text-[11px] text-slate-600">Turno(s) de Trabalho</dt>
                            <dd class="font-medium" id="modal-pgr-obra-turno">—</dd>
                        </div>

                        <div class="mt-3">
                            <dt class="text-[11px] text-slate-600">Funções e Cargos</dt>
                            <dd class="font-medium">
                                <ul id="modal-pgr-funcoes" class="list-disc list-inside text-xs space-y-0.5">
                                    {{-- preenchido via JS --}}
                                </ul>
                            </dd>
                        </div>
                    </dl>
                </section>



                {{-- Coluna direita --}}
                <div class="space-y-4">
                    {{-- 4. Ações rápidas --}}
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <h3 class="text-xs font-semibold text-slate-500 mb-3">
                            4. AÇÕES RÁPIDAS
                        </h3>

                        <div class="space-y-3">
                            <button type="button"
                                    data-coluna-id="2"
                                    class="js-mover-coluna w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                       bg-[color:var(--color-brand-azul,#2563eb)] text-white text-sm font-semibold shadow-sm
                       hover:bg-blue-700 transition">
                                Mover para: Em Execução
                            </button>

                            <button type="button"
                                    data-coluna-id="6"
                                    class="js-mover-coluna w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
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
                            id="btn-salvar-observacao"
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
            const modal    = document.getElementById('tarefa-modal');
            const closeBtn = document.getElementById('tarefa-modal-close');



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
            const textareaObsInterna = document.getElementById('modal-observacao-interna');

            // 🔹 bloco de informações específicas de ASO (funcionário)
            const blocoAso = document.getElementById('modal-bloco-aso');

            // 🔹 bloco de informações específicas de PGR
            const blocoPgr        = document.getElementById('modal-bloco-pgr');
            const spanPgrTipo     = document.getElementById('modal-pgr-tipo');
            const spanPgrArt      = document.getElementById('modal-pgr-art');
            const spanPgrHomens   = document.getElementById('modal-pgr-qtd-homens');
            const spanPgrMulheres = document.getElementById('modal-pgr-qtd-mulheres');
            const spanPgrTotal    = document.getElementById('modal-pgr-total-trabalhadores');
            const spanPgrComPcmso = document.getElementById('modal-pgr-com-pcmso');
            const spanPgrContr    = document.getElementById('modal-pgr-contratante');
            const spanPgrContrCnpj= document.getElementById('modal-pgr-contratante-cnpj');
            const spanPgrObraNome = document.getElementById('modal-pgr-obra-nome');
            const spanPgrObraEnd  = document.getElementById('modal-pgr-obra-endereco');
            const spanPgrObraCej  = document.getElementById('modal-pgr-obra-cej-cno');
            const spanPgrObraTurno= document.getElementById('modal-pgr-obra-turno');
            const ulPgrFuncoes    = document.getElementById('modal-pgr-funcoes');

            const blocoTreinamento = document.getElementById('modal-bloco-treinamento');
            const spanTreinLocal   = document.getElementById('modal-treinamento-local');
            const spanTreinUnidade = document.getElementById('modal-treinamento-unidade');
            const spanTreinPart    = document.getElementById('modal-treinamento-participantes');
            const spanTreinFuncs   = document.getElementById('modal-treinamento-funcoes');

            // abre modal ao clicar em qualquer card
            document.addEventListener('click', function (e) {
                const card = e.target.closest('.kanban-card');
                console.log(card)
                if (!card) return;

                // Campos básicos
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

                // funcionário (valor bruto – depois a gente mostra/oculta por tipo)
                spanFuncionario.textContent       = card.dataset.funcionario || '—';
                spanFuncionarioFuncao.textContent = card.dataset.funcionarioFuncao || '—';


                spanObs.textContent = card.dataset.observacoes ?? '';
                if (textareaObsInterna) {
                    textareaObsInterna.value = card.dataset.observacaoInterna || '';
                }
                modal.dataset.observacaoUrl = card.dataset.observacaoUrl || '';

                // === REGRA POR TIPO DE SERVIÇO (sem quebrar o que já existia) ===
                const tipoServico = (card.dataset.servico || '').toLowerCase();
                const isAso = tipoServico.includes('aso');
                const isPgr = tipoServico.includes('pgr');

                // --- ASO: mostra bloco de funcionário ---
                if (blocoAso) {
                    if (isAso) {
                        blocoAso.classList.remove('hidden');
                        spanFuncionario.textContent       = card.dataset.funcionario || '—';
                        spanFuncionarioFuncao.textContent = card.dataset.funcionarioFuncao || '—';
                    } else {
                        // esconde quando não for ASO
                        blocoAso.classList.add('hidden');
                        spanFuncionario.textContent       = '—';
                        spanFuncionarioFuncao.textContent = '—';
                    }
                }

                // --- PGR: mostra bloco PGR e preenche ---
                if (blocoPgr) {
                    if (isPgr) {
                        blocoPgr.classList.remove('hidden');

                        spanPgrTipo.textContent = card.dataset.pgrTipo || '—';

                        spanPgrArt.textContent  = card.dataset.pgrComArt === '1'
                            ? 'Com ART'
                            : (card.dataset.pgrComArt === '0' ? 'Sem ART' : '—');

                        spanPgrHomens.textContent   = card.dataset.pgrQtdHomens || '0';
                        spanPgrMulheres.textContent = card.dataset.pgrQtdMulheres || '0';
                        spanPgrTotal.textContent    = card.dataset.pgrTotalTrabalhadores || '0';

                        spanPgrComPcmso.textContent = card.dataset.pgrComPcmso === '1'
                            ? 'Sim, PGR + PCMSO'
                            : (card.dataset.pgrComPcmso === '0' ? 'Não, apenas PGR' : '—');

                        spanPgrContr.textContent     = card.dataset.pgrContratante || '—';
                        spanPgrContrCnpj.textContent = card.dataset.pgrContratanteCnpj || '—';

                        spanPgrObraNome.textContent  = card.dataset.pgrObraNome || '—';
                        spanPgrObraEnd.textContent   = card.dataset.pgrObraEndereco || '—';
                        spanPgrObraCej.textContent   = card.dataset.pgrObraCejCno || '—';
                        spanPgrObraTurno.textContent = card.dataset.pgrObraTurno || '—';

                        // resumo das funções (ex.: "Carpinteiro (3), Servente (2)")
                        if (ulPgrFuncoes) {
                            ulPgrFuncoes.textContent = card.dataset.pgrFuncoes || '—';
                        }
                    } else {
                        // se não for PGR, esconde bloco PGR
                        blocoPgr.classList.add('hidden');
                    }
                }

                // limpa blocos
                ['apr','ltcat','ltip','pae','pcmso'].forEach(s => {
                    document.getElementById(`modal-bloco-${s}`).classList.add('hidden');
                });

// APR
                if (card.dataset.servico === 'APR') {
                    document.getElementById('modal-apr-endereco').textContent =
                        card.dataset.aprEndereco || '—';

                    document.getElementById('modal-apr-funcoes').textContent =
                        card.dataset.aprFuncoes || '—';

                    document.getElementById('modal-apr-etapas').textContent =
                        card.dataset.aprEtapas || '—';

                    document.getElementById('modal-bloco-apr').classList.remove('hidden');
                }

                // TREINAMENTO NR
                if (card.dataset.servico === 'Treinamentos NRs') {

                    blocoTreinamento.classList.remove('hidden');

                    const localTipo = card.querySelector('[data-trei-local]')?.dataset.treiLocal || '—';
                    const unidade   = card.querySelector('[data-trei-unidade]')?.dataset.treiUnidade || '—';
                    const participantes = card.querySelector('[data-trei-participantes]')?.dataset.treiParticipantes || '—';
                    const funcoes       = card.querySelector('[data-trei-funcoes]')?.dataset.treiFuncoes || '—';

                    spanTreinLocal.textContent   = localTipo === 'clinica' ? 'Clínica' : 'In Company';
                    spanTreinUnidade.textContent = unidade;
                    spanTreinPart.textContent    = participantes;
                    spanTreinFuncs.textContent   = funcoes;

                } else {
                    blocoTreinamento.classList.add('hidden');
                }

// LTCAT
                if (card.dataset.servico === 'LTCAT') {
                    const tipoBruto = card.dataset.ltcatTipo || '';
                    let tipoLabel = '—';

                    if (tipoBruto === 'matriz') {
                        tipoLabel = 'Matriz';
                    } else if (tipoBruto === 'especifico') {
                        tipoLabel = 'Específico';
                    } else if (tipoBruto) {
                        tipoLabel = tipoBruto.charAt(0).toUpperCase() + tipoBruto.slice(1);
                    }

                    document.getElementById('modal-ltcat-tipo').textContent      = tipoLabel;
                    document.getElementById('modal-ltcat-endereco').textContent  = card.dataset.ltcatEndereco || '—';
                    document.getElementById('modal-ltcat-total-funcoes').textContent =
                        card.dataset.ltcatTotalFuncoes || '—';
                    document.getElementById('modal-ltcat-total-func').textContent =
                        card.dataset.ltcatTotalFuncionarios || '—';
                    document.getElementById('modal-ltcat-funcoes').textContent   =
                        card.dataset.ltcatFuncoes || '—';

                    document.getElementById('modal-bloco-ltcat').classList.remove('hidden');
                }

// LTIP
                if (card.dataset.servico === 'LTIP') {
                    document.getElementById('modal-ltip-endereco').textContent =
                        card.dataset.ltipEndereco || '—';

                    document.getElementById('modal-ltip-funcoes').textContent =
                        card.dataset.ltipFuncoes || '—';

                    document.getElementById('modal-ltip-total-func').textContent =
                        card.dataset.ltipTotalFuncionarios || '—';

                    document.getElementById('modal-bloco-ltip').classList.remove('hidden');
                }

// PAE
                if (card.dataset.servico === 'PAE') {
                    document.getElementById('modal-pae-endereco').textContent =
                        card.dataset.paeEndereco || '—';

                    document.getElementById('modal-pae-total-func').textContent =
                        card.dataset.paeTotalFuncionarios || '—';

                    document.getElementById('modal-pae-descricao').textContent =
                        card.dataset.paeDescricao || '—';

                    document.getElementById('modal-bloco-pae').classList.remove('hidden');
                }

// PCMSO
                if (card.dataset.servico === 'PCMSO') {
                    const tipoBruto = card.dataset.pcmsoTipo || '';
                    let tipoLabel = '—';

                    if (tipoBruto === 'matriz') {
                        tipoLabel = 'Matriz';
                    } else if (tipoBruto === 'especifico') {
                        tipoLabel = 'Específico';
                    } else if (tipoBruto) {
                        tipoLabel = tipoBruto.charAt(0).toUpperCase() + tipoBruto.slice(1);
                    }

                    document.getElementById('modal-pcmso-tipo').textContent       = tipoLabel;
                    document.getElementById('modal-pcmso-prazo').textContent      = card.dataset.pcmsoPrazo || '—';
                    document.getElementById('modal-pcmso-obra-nome').textContent  = card.dataset.pcmsoObraNome || '—';
                    document.getElementById('modal-pcmso-obra-cnpj').textContent  = card.dataset.pcmsoObraCnpj || '—';
                    document.getElementById('modal-pcmso-obra-cei').textContent   = card.dataset.pcmsoObraCei || '—';
                    document.getElementById('modal-pcmso-obra-endereco').textContent =
                        card.dataset.pcmsoObraEndereco || '—';

                    const linkWrapper = document.getElementById('modal-pcmso-pgr-wrapper');
                    const linkEl      = document.getElementById('modal-pcmso-pgr-link');
                    const url         = card.dataset.pcmsoPgrUrl || '';

                    if (url) {
                        linkEl.href = url;
                        linkWrapper.classList.remove('hidden');
                    } else {
                        linkEl.href = '#';
                        linkWrapper.classList.add('hidden');
                    }

                    document.getElementById('modal-bloco-pcmso').classList.remove('hidden');
                }


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

                modal.dataset.moveUrl  = card.dataset.moveUrl || '';
                modal.dataset.tarefaId = card.dataset.id || '';

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

            // Sortable (drag & drop) – mantém exatamente a mesma lógica
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

                                    const colunaSection = card.closest('section');
                                    const headerTitleEl = colunaSection
                                        ? colunaSection.querySelector('header h2')
                                        : null;

                                    const statusName = data.status_label
                                        || (headerTitleEl ? headerTitleEl.textContent.trim() : '');

                                    const statusSpan = card.querySelector('[data-role="card-status-label"]');
                                    if (statusSpan && statusName) {
                                        statusSpan.textContent = statusName;
                                    }

                                    if (statusName) {
                                        card.dataset.status = statusName;
                                    }

                                    const respBadge = card.querySelector('[data-role="card-responsavel-badge"]');
                                    if (respBadge && colunaCor) {
                                        respBadge.style.borderColor = colunaCor;
                                        respBadge.style.color = colunaCor;
                                    }

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
                                    // pode colocar um toast de erro aqui se quiser
                                });
                        }
                    });
                });
            }

        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.auto-dismiss');

            alerts.forEach(function (alert) {
                // tempo em ms (ex: 5000 = 5s)
                const timeout = 5000;

                setTimeout(function () {
                    // animação simples se estiver usando Tailwind
                    alert.classList.add('transition', 'opacity-0', 'translate-y-2');

                    // remove do DOM depois da animação
                    setTimeout(function () {
                        alert.remove();
                    }, 300); // 300ms pra bater com a transition
                }, timeout);
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.js-mover-coluna');

            const modal   = document.getElementById('tarefa-modal');
            const statusText = document.getElementById('modal-status-text');


            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    const colunaId = this.dataset.colunaId;

                    // Rota do método mover
                    const url      = modal.dataset.moveUrl;

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            coluna_id: colunaId
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.ok) {
                                // Se tiver um badge de status, atualiza:
                                const statusBadge = document.querySelector('#tarefa-status-label');
                                if (statusBadge && data.status_label) {
                                    statusBadge.textContent = data.status_label;
                                }

                                // Opcional: recarregar página/fechar modal
                                 location.reload();

                                console.log('Movido com sucesso:', data);
                            } else {
                                alert('Não foi possível mover a tarefa.');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Erro ao mover a tarefa.');
                        });
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const modal             = document.getElementById('tarefa-modal');
            const textareaObsInterna = document.getElementById('modal-observacao-interna');
            const btnSalvarObs      = document.getElementById('btn-salvar-observacao');

            if (btnSalvarObs && textareaObsInterna) {
                btnSalvarObs.addEventListener('click', function () {
                    const url = modal.dataset.observacaoUrl;

                    if (!url) {
                        alert('Nenhuma tarefa selecionada para salvar observação.');
                        return;
                    }

                    const valor = textareaObsInterna.value;

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            observacao_interna: valor
                        })
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.ok) {
                                // feedback simples
                                btnSalvarObs.textContent = 'Observação salva!';
                                setTimeout(() => {
                                    btnSalvarObs.textContent = 'Salvar Observação';
                                }, 1500);
                            } else {
                                alert('Não foi possível salvar a observação.');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Erro ao salvar a observação.');
                        });
                });
            }
        });

    </script>



@endpush
