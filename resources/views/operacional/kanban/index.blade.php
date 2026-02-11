@extends('layouts.operacional')
@section('title', 'Painel Operacional')
@php
    use Illuminate\Support\Str;
@endphp

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
    <div class="w-full px-3 md:px-5 py-4 md:py-5">

        {{-- Barra de busca + Nova Tarefa --}}
        <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-4 mb-6">
            <div class="flex-1">
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">🔍</span>
                    <input type="text" placeholder="Buscar..."
                           class="w-full pl-9 pr-3 py-2.5 rounded-2xl border border-slate-200 bg-white/95
                              text-sm text-slate-700 shadow-sm
                              focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                </div>
            </div>

            <a href="{{ route('operacional.kanban.aso.clientes') }}"
               class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl
                  bg-gradient-to-r from-sky-500 to-cyan-400
                  text-white text-sm font-semibold shadow-md shadow-sky-500/30
                  hover:from-sky-600 hover:to-cyan-500 transition">
                <span>Nova Tarefa</span>
            </a>
        </div>

        {{-- Título --}}
        <div class="mb-3 space-y-2">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-slate-900">
                Painel Operacional
            </h1>
            <p class="mt-0.5 text-xs md:text-sm text-slate-500">
                Suas tarefas atribuídas ·
                <span class="font-medium text-sky-700">{{ $usuario->name }}</span>
            </p>
        </div>

        {{-- Filtros em card --}}
        <section class="mb-4 rounded-2xl bg-white/95 border border-slate-100 shadow-sm">
            <form method="GET" class="grid md:grid-cols-3 gap-3 md:gap-4 p-3 md:p-4 text-sm">

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        Tipo de Serviço
                    </label>
                    <select name="servico_id"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-sm
                               text-slate-700
                               focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                        <option value="">Todos os serviços</option>
                        @foreach($servicos as $servico)
                            <option value="{{ $servico->id }}" @selected($filtroServico == $servico->id)>
                                {{ $servico->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        Responsável
                    </label>
                    <select name="responsavel_id"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-sm
                               text-slate-700
                               focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                        <option value="">Todos os responsáveis</option>
                        @foreach($responsaveis as $resp)
                            <option value="{{ $resp->id }}" @selected($filtroResponsavel == $resp->id)>
                                {{ $resp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        Status (Coluna)
                    </label>
                    <select name="coluna_id"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-sm
                                   text-slate-700
                                   focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                        <option value="">Todos os status</option>

                        {{-- 🔹 Novo item: Canceladas (soft delete) --}}
                        <option value="canceladas" @selected($filtroColuna === 'canceladas')>
                            Canceladas
                        </option>

                        @foreach($colunas as $col)
                            <option value="{{ $col->id }}" @selected($filtroColuna == $col->id)>
                                {{ $col->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        Data inicial
                    </label>
                    <input type="date" name="de" value="{{ $filtroDe }}"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-sm
                              text-slate-700
                              focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide mb-1">
                        Data final
                    </label>
                    <input type="date" name="ate" value="{{ $filtroAte }}"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-sm
                              text-slate-700
                              focus:bg-white focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                </div>

                <div class="flex items-end">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl
                               bg-slate-900 text-white text-sm font-semibold shadow-sm
                               hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        Filtrar
                    </button>
                </div>
            </form>
        </section>
        @php
            $statusCardsConfig = [
                'pendente'              => ['icon' => '⏳', 'bg' => 'from-amber-400 to-amber-500'],
                'pendentes'             => ['icon' => '⏳', 'bg' => 'from-amber-400 to-amber-500'],

                'em-execucao'           => ['icon' => '▶️', 'bg' => 'from-sky-500 to-blue-500'],

                'correcao'              => ['icon' => '🛠️', 'bg' => 'from-orange-500 to-orange-600'],

                'aguardando'            => ['icon' => '⏱️', 'bg' => 'from-purple-500 to-fuchsia-500'],
                'aguardando-fornecedor' => ['icon' => '⏱️', 'bg' => 'from-purple-500 to-fuchsia-500'],

                'finalizada'            => ['icon' => '✅', 'bg' => 'from-emerald-500 to-green-500'],
                'finalizadas'           => ['icon' => '✅', 'bg' => 'from-emerald-500 to-green-500'],

                'atrasado'              => ['icon' => '⛔', 'bg' => 'from-rose-500 to-red-500'],
                'atrasados'             => ['icon' => '⛔', 'bg' => 'from-rose-500 to-red-500'],
            ];
        @endphp



        {{-- Kanban --}}
        {{-- Kanban --}}
        <div class="mt-4 pb-6 overflow-x-auto xl:overflow-x-visible -mx-3 md:mx-0 px-3 md:px-0">
            <div class="flex gap-2 md:gap-3 min-w-max snap-x snap-mandatory">
                @foreach($colunas as $coluna)
                    @php
                        $slug = Str::slug($coluna->nome);
                        $config = $statusCardsConfig[$slug] ?? [
                            'icon' => '📌',
                            'bg'   => 'from-slate-400 to-slate-500',
                        ];

                        /** @var \Illuminate\Support\Collection $tarefasColuna */
                        $tarefasColuna = $tarefasPorColuna[$coluna->id] ?? collect();
                        $totalColuna = $tarefasColuna->count();
                    @endphp

                    {{-- UMA COLUNA COMPLETA (card resumo + raia) --}}

                    <section class="flex flex-col w-[clamp(210px,16vw,260px)] flex-shrink-0 gap-2 md:gap-3 snap-start">

                        {{-- Card resumo da coluna --}}
                        <article
                            class="rounded-2xl px-4 py-3 bg-gradient-to-r {{ $config['bg'] }}
                           text-white shadow-md flex items-center justify-between">

                            <div>
                                <h3 class="text-[11px] md:text-xs font-semibold uppercase tracking-wide opacity-90">
                                    {{ $coluna->nome }}
                                </h3>
                                <p class="mt-1 text-xl md:text-2xl font-bold leading-none">
                                    {{ $totalColuna }}
                                </p>
                            </div>

                            <div class="text-2xl md:text-3xl opacity-70">
                                {{ $config['icon'] }}
                            </div>
                        </article>

                        {{-- Raia do Kanban dessa coluna --}}
                        <article
                            class="bg-white border border-slate-200 rounded-2xl flex flex-col
                           h-[calc(100vh-320px)] min-h-[420px] max-h-[72vh] shadow-md">

                            {{-- cards --}}
                            <div class="flex-1 overflow-y-auto px-3 py-3 space-y-3 kanban-column"
                                 data-coluna-id="{{ $coluna->id }}"
                                 data-coluna-cor="{{ $coluna->cor }}"
                                 data-coluna-slug="{{ Str::slug($coluna->nome) }}">
                                {{-- aqui permanece o foreach dos cards de tarefa que você já tinha --}}

                                @forelse($tarefasColuna as $tarefa)
                                    @php
                                        $clienteNome  = optional($tarefa->cliente)->razao_social ?? 'Sem cliente';
                                        $servicoNome  = optional($tarefa->servico)->nome ?? 'Sem serviço';
                                        $isCancelada = $tarefa->trashed();

                                        $respNome     = optional($tarefa->responsavel)->name ?? 'Sem responsável';
                                        $dataHora     = $tarefa->inicio_previsto
                                                        ? \Carbon\Carbon::parse($tarefa->inicio_previsto)->format('d/m/Y H:i')
                                                        : 'Sem data';

                                        $funcionario   = optional($tarefa->funcionario);
                                        $funcionarioNome   = $funcionario->nome ?? null;
                                        $funcionarioCpf    = $funcionario->cpf ?? null;
                                        $funcionarioFuncao = $funcionario->funcao_nome ?? null;
                                        $funcionarioCelular = $funcionario->celular ?? null;
                                        $funcionarioNascimento = $funcionario->data_nascimento
                                            ? \Carbon\Carbon::parse($funcionario->data_nascimento)->format('d/m/Y')
                                            : null;

                                        $slaData      = $tarefa->fim_previsto
                                                        ? \Carbon\Carbon::parse($tarefa->fim_previsto)->format('d/m/Y')
                                                        : '-';
                                        $obs          = $tarefa->descricao ?? '';

                                        $clienteCnpj  = optional($tarefa->cliente)->cnpj ?? '';
                                        $clienteTel   = optional($tarefa->cliente)->telefone ?? '';

                                        $pgr  = $tarefa->pgrSolicitacao ?? null;
                                        $ltip = $tarefa->ltipSolicitacao;

                                        // ====== NOVO: dados específicos do ASO via aso_solicitacoes ======
                                        $aso                  = $tarefa->asoSolicitacao;
                                        $asoTipoLabel         = '';
                                        $asoDataFormatada     = '';
                                        $asoUnidadeNome       = '';
                                        $asoTreinamentoFlag   = '';
                                        $asoTreinamentosLista = '';
                                        $asoEmail             = '';
                                        $asoPacoteNome        = '';

                                        if ($aso) {
                                            $mapTiposAso = [
                                                'admissional'      => 'Admissional',
                                                'periodico'        => 'Periódico',
                                                'demissional'      => 'Demissional',
                                                'mudanca_funcao'   => 'Mudança de Função',
                                                'retorno_trabalho' => 'Retorno ao Trabalho',
                                            ];


                                            $asoTipoLabel     = $mapTiposAso[$aso->tipo_aso] ?? ucfirst($aso->tipo_aso);
                                            $asoDataFormatada = $aso->data_aso
                                                ? \Carbon\Carbon::parse($aso->data_aso)->format('d/m/Y')
                                                : '';

                                            $asoUnidadeNome   = optional($aso->unidade)->nome ?? '';
                                            $asoEmail         = $aso->email_aso ?? '';
                                            $asoTreinamentoFlag = $aso->vai_fazer_treinamento ? 'Sim' : 'Não';

                                            // labels dos treinamentos
                                            $mapTrein = [
                                                'nr_35' => 'NR-35 - Trabalho em Altura',
                                                'nr_18' => 'NR-18 - Integração',
                                                'nr_12' => 'NR-12 - Máquinas e Equipamentos',
                                                'nr_06' => 'NR-06 - EPI',
                                                'nr_05' => 'NR-05 - CIPA Designada',
                                                'nr_01' => 'NR-01 - Ordem de Serviço',
                                                'nr_33' => 'NR-33 - Espaço Confinado',
                                                'nr_11' => 'NR-11 - Movimentação de Carga',
                                                'nr_10' => 'NR-10 - Elétrica',
                                            ];

                                            $labelsTrein = [];
                                            foreach ((array) $aso->treinamentos as $code) {
                                                $labelsTrein[] = $mapTrein[$code] ?? strtoupper($code);
                                            }
                                            $asoTreinamentosLista = implode(', ', $labelsTrein);

                                            if (!empty($aso->treinamento_pacote)) {
                                                $pacote = (array) $aso->treinamento_pacote;
                                                $asoPacoteNome = $pacote['nome'] ?? '';
                                            }
                                        }

                                        $isAsoTask = (bool) $aso;

                                        $editUrl = null;
                                        if ($isAsoTask) {
                                            $editUrl = route('operacional.kanban.aso.editar', $tarefa);
                                        }
                                        if ($servicoNome === 'PGR') {
                                            $editUrl = route('operacional.kanban.pgr.editar', $tarefa);
                                        }
                                        if ($servicoNome === 'PCMSO') {
                                            $editUrl = route('operacional.kanban.pcmso.edit', $tarefa);
                                        }
                                        if ($servicoNome === 'LTCAT') {
                                            $editUrl = route('operacional.ltcat.edit', $tarefa);
                                        }
                                        if ($servicoNome === 'LTIP') {
                                            $editUrl = route('operacional.ltip.edit', $tarefa);
                                        }
                                        if ($servicoNome === 'APR') {
                                            $editUrl = route('operacional.apr.edit', $tarefa);
                                        }
                                        if ($servicoNome === 'PAE') {
                                            $editUrl = route('operacional.pae.edit', $tarefa);
                                        }
                                        if ($servicoNome === 'Treinamentos NRs') {
                                            $editUrl = route('operacional.treinamentos-nr.edit', $tarefa);
                                        }

                                    @endphp




                                    <article
                                        class="kanban-card bg-white rounded-2xl shadow-md border border-slate-200 border-l-4
                                        px-3 py-3 text-xs cursor-pointer hover:shadow-lg transition hover:-translate-y-0.5"
                                        @if($isCancelada) opacity-60 ring-1 ring-red-200 @endif"
                                    style="border-left-color: {{ $coluna->cor  }};"

                                    data-move-url="{{ route('operacional.tarefas.mover', $tarefa) }}"

                                    data-cancelada="{{ $isCancelada ? '1' : '0' }}"

                                    @php
                                        $anexos = $tarefa->anexos ?? collect();

                                        $anexosPayload = $anexos->map(function (\App\Models\Anexos $anexo) {
                                            return [
                                                'id'          => $anexo->id,
                                                'nome'        => $anexo->nome_original,
                                                'url'         => $anexo->url,                 // S3
                                                'mime'        => $anexo->mime_type,
                                                'tamanho'     => $anexo->tamanho_humano,      // opcional
                                                'servico'     => $anexo->servico,
                                                'uploaded_by' => optional($anexo->uploader)->name,
                                                'data'        => optional($anexo->created_at)->format('d/m/Y H:i'),
                                            ];
                                        })->values();
                                    @endphp
                                    data-tem-anexos="{{ $anexos->isNotEmpty() ? '1' : '0' }}"
                                    data-anexos='@json($anexosPayload)'
                                    data-id="{{ $tarefa->id }}"
                                    data-cliente="{{ $clienteNome }}"
                                    data-cnpj="{{ $clienteCnpj }}"
                                    data-telefone="{{ $clienteTel }}"
                                    data-servico="{{ $servicoNome }}"
                                    data-responsavel="{{ $respNome }}"
                                    data-datahora="{{ $dataHora }}"
                                    data-sla="{{ $slaData }}"
                                    data-fim-previsto="{{ optional($tarefa->fim_previsto)->toIso8601String() }}"
                                    data-move-url="{{ route('operacional.tarefas.mover', $tarefa) }}"
                                    data-finalizar-url="{{ route('operacional.tarefas.finalizar-com-arquivo', $tarefa) }}
                                    "
                                    data-prioridade="{{ ucfirst($tarefa->prioridade) }}"
                                    data-status="{{ $coluna->nome }}"
                                    data-finalizado="{{ (!empty($tarefa->finalizado_em) || ($coluna->finaliza ?? false)) ? '1' : '0' }}"
                                    data-observacoes="{{ e($obs) }}"

                                    data-funcionario="{{ $funcionarioNome . ($funcionarioCpf ? ' | CPF '.$funcionarioCpf : '') }}
                                    "
                                    data-funcionario-funcao="{{ $funcionarioFuncao }}"
                                    data-funcionario-cpf="{{ $funcionarioCpf }}"
                                    data-funcionario-celular="{{ $funcionarioCelular }}"
                                    data-funcionario-nascimento="{{ $funcionarioNascimento }}"

                                    data-aso-tipo="{{ $asoTipoLabel }}"
                                    data-aso-data="{{ $asoDataFormatada }}"
                                    data-aso-unidade="{{ $asoUnidadeNome }}"
                                    data-aso-treinamento="{{ $asoTreinamentoFlag }}"
                                    data-aso-treinamentos="{{ $asoTreinamentosLista }}"
                                    data-aso-pacote="{{ $asoPacoteNome }}"
                                    data-aso-email="{{ $asoEmail }}"
                                    data-is-aso="{{ $isAsoTask ? '1' : '0' }}"

                                    data-observacao-interna="{{ e($tarefa->observacao_interna) }}"
                                    data-observacao-url="{{ route('operacional.tarefas.observacao', $tarefa) }}"
                                    data-edit-url="{{ $editUrl }}"


                                    {{-- PGR --}}
                                    @if($pgr)
                                        @php
                                            $pgrFuncoesDetalhes = collect($pgr->funcoes ?? [])->map(function ($item) {
                                                $funcao = \App\Models\Funcao::find($item['funcao_id'] ?? null);
                                                return [
                                                    'nome' => $funcao?->nome ?? 'Funcao',
                                                    'quantidade' => (int) ($item['quantidade'] ?? 0),
                                                    'nr_altura' => (bool) ($item['nr_altura'] ?? false),
                                                    'nr_eletricidade' => (bool) ($item['nr_eletricidade'] ?? false),
                                                    'nr_espaco_confinado' => (bool) ($item['nr_espaco_confinado'] ?? false),
                                                ];
                                            })->values();
                                        @endphp
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
                                        data-pgr-funcoes-json='@json($pgrFuncoesDetalhes)'
                                    @endif
                                    @if($tarefa->documento_link)
                                        data-arquivo-cliente-url="{{ $tarefa->documento_link }}"
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
                                        data-ltcat-total-funcionarios="{{ $tarefa->ltcatSolicitacao->total_funcionarios }}
                                        "
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
                                            data-pcmso-pgr-url="{{ $pcmso->pgr_arquivo_url }}"
                                        @endif
                                    @endif
                                    @if($tarefa->treinamentoNr && $tarefa->treinamentoNrDetalhes)
                                        @php
                                            $treiFuncs = $tarefa->treinamentoNr()->with('funcionario')->get();
                                            $treiDet   = $tarefa->treinamentoNrDetalhes;
                                            $listaNomesArr = $treiFuncs->pluck('funcionario.nome')
                                                ->filter()
                                                ->sort()
                                                ->values()
                                                ->all();
                                            $listaNomes = implode(', ', $listaNomesArr);
                                            $listaFuncoes = $treiFuncs->pluck('funcionario.funcao')->join(', ');
                                            $treiPayload = $treiDet->treinamentos ?? [];
                                            $treiModo = is_array($treiPayload) ? ($treiPayload['modo'] ?? null) : null;
                                            $treiPacote = '';
                                            $treiCodigos = [];
                                            if ($treiModo === 'pacote') {
                                                $treiPacote = (string) data_get($treiPayload, 'pacote.nome', '');
                                                $treiCodigos = (array) data_get($treiPayload, 'pacote.codigos', []);
                                            } else {
                                                if (is_array($treiPayload) && array_key_exists('codigos', $treiPayload)) {
                                                    $treiCodigos = (array) ($treiPayload['codigos'] ?? []);
                                                } else {
                                                    $treiCodigos = (array) $treiPayload;
                                                }
                                            }
                                            $treiCodigos = array_values(array_filter(array_map('strval', $treiCodigos)));
                                            $treiCodigosLabel = implode(', ', $treiCodigos);
                                        @endphp

                                        data-treinamento-participantes='@json($listaNomesArr)'
                                        data-treinamento-funcoes="{{ $listaFuncoes }}"
                                        data-treinamento-local="{{ $treiDet->local_tipo }}"
                                        data-treinamento-unidade="{{ optional($treiDet->unidade)->nome ?? '' }}"
                                        data-treinamento-modo="{{ $treiModo }}"
                                        data-treinamento-pacote="{{ $treiPacote }}"
                                        data-treinamento-codigos="{{ $treiCodigosLabel }}"
                                    @endif
                                    >

                                    @php
                                        // Label da tagzinha à direita
                                        $badgeLabel    = $servicoNome;
                                        $tituloCard    = $servicoNome;      // default
                                        $subtituloCard = $clienteNome;      // default

                                        // -------- ASO --------
                                        if ($isAsoTask) {
                                            $nomeFunc = $funcionarioNome
                                                ?? optional($tarefa->funcionario)->nome
                                                ?? 'Sem funcionário';

                                            $tituloCard    = 'ASO - ' . $nomeFunc;
                                            $subtituloCard = $clienteNome;

                                        // -------- PGR --------
                                        } elseif ($servicoNome === 'PGR') {
                                            if ($pgr && $pgr->obra_nome) {
                                                $tituloCard    = 'PGR - ' . $pgr->obra_nome;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'PGR - ' . $clienteNome;
                                            }
                                            if ($pgr && !empty($pgr->com_pcms0)) {
                                                $badgeLabel = 'PGR + PCMSO';
                                            }

                                        // -------- PCMSO --------
                                        } elseif ($servicoNome === 'PCMSO') {
                                            $pcmso = $tarefa->pcmsoSolicitacao ?? null;

                                            if ($pcmso && $pcmso->obra_nome) {
                                                $tituloCard    = 'PCMSO - ' . $pcmso->obra_nome;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'PCMSO - ' . $clienteNome;
                                            }

                                        // -------- LTCAT --------
                                        } elseif ($servicoNome === 'LTCAT') {
                                            $lt = $tarefa->ltcatSolicitacao ?? null;

                                            if ($lt && $lt->endereco_avaliacoes) {
                                                $tituloCard    = 'LTCAT - ' . $lt->endereco_avaliacoes;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'LTCAT - ' . $clienteNome;
                                            }

                                        // -------- LTIP --------
                                        } elseif ($servicoNome === 'LTIP') {
                                            $ltip = $tarefa->ltipSolicitacao ?? null;

                                            if ($ltip && $ltip->endereco_avaliacoes) {
                                                $tituloCard    = 'LTIP - ' . $ltip->endereco_avaliacoes;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'LTIP - ' . $clienteNome;
                                            }

                                        // -------- APR --------
                                        } elseif ($servicoNome === 'APR') {
                                            $apr = $tarefa->aprSolicitacao ?? null;

                                            if ($apr && $apr->endereco_atividade) {
                                                $tituloCard    = 'APR - ' . $apr->endereco_atividade;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'APR - ' . $clienteNome;
                                            }

                                        // -------- PAE --------
                                        } elseif ($servicoNome === 'PAE') {
                                            $pae = $tarefa->paeSolicitacao ?? null;

                                            if ($pae && $pae->endereco_local) {
                                                $tituloCard    = 'PAE - ' . $pae->endereco_local;
                                                $subtituloCard = $clienteNome;
                                            } else {
                                                $tituloCard    = 'PAE - ' . $clienteNome;
                                            }

                                        // -------- TREINAMENTOS NRs --------
                                        } elseif ($servicoNome === 'Treinamentos NRs') {
                                            $tituloCard    = 'Treinamento NR - ' . $clienteNome;
                                            $subtituloCard = 'Colaboradores: ' . ($tarefa->treinamentoNr()->count() ?? 0);

                                        // -------- DEFAULT (qualquer outro serviço) --------
                                        } else {
                                            $tituloCard    = $servicoNome . ' - ' . $clienteNome;
                                            $subtituloCard = $clienteNome;
                                        }
                                    @endphp

                                    {{-- ================== CABEÇALHO ================== --}}
                                    <div class="flex items-start justify-between gap-2 mb-1.5">
                                        <h4 class="text-[15px] font-semibold text-slate-800 leading-snug">
                                            {{ $tituloCard }}
                                        </h4>

                                        <div class="flex flex-col items-end gap-1">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full
                                                       text-[11px] font-semibold border"
                                                data-role="card-responsavel-badge"
                                                style="border-color: {{ $coluna->cor }}; color: #0f172a; background-color: {{ $coluna->cor }}20;">
                                                {{ $badgeLabel }}
                                            </span>

                                                @if($isCancelada)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full
                                                       text-[10px] font-semibold bg-red-50 border border-red-200
                                                       text-red-700 uppercase tracking-wide">
                                                            Cancelada
                                                    </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- ================== SUBTÍTULO (CLIENTE / ENDEREÇO / OBRA) ================== --}}
                                    <p class="text-[13px] text-slate-500 font-medium mb-2">
                                        {{ $subtituloCard }}
                                    </p>

                                    {{-- ================== DATA / HORÁRIO ================== --}}
                                    <div class="flex items-center gap-1 text-[11px] text-slate-500 mb-2">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="none">
                                            <circle cx="10" cy="10" r="8" stroke="#9CA3AF" stroke-width="1.5"/>
                                            <path d="M10 5.5V10.2L13 12" stroke="#9CA3AF" stroke-width="1.5"
                                                  stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span>{{ $dataHora }}</span>
                                    </div>

                                    {{-- ================== SLA / PRAZO ================== --}}
                                    @if($slaData && $slaData !== '-')
                                        <div class="mb-2">
                                            <div
                                                class="inline-flex items-center gap-1 px-3 py-1 rounded-md border
                                                    border-amber-200 bg-amber-50 text-[11px] text-amber-700">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="none">
                                                    <circle cx="10" cy="10" r="8" stroke="#FBBF24" stroke-width="1.5"/>
                                                    <path d="M10 5.5V10.2L13 12" stroke="#F59E0B" stroke-width="1.5"
                                                          stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span>{{ $slaData }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    @if($tarefa->fim_previsto)
                                        <div class="mb-2">
                                            <div
                                                class="inline-flex items-center gap-1 px-3 py-1 rounded-md border
                                                    border-slate-200 bg-slate-50 text-[11px] text-slate-600"
                                                data-role="card-tempo-label">
                                                <span>⏱</span>
                                                <span data-role="card-tempo-text">—</span>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- ================== RODAPÉ ================== --}}
                                    <div class="mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-500
                                        flex items-center justify-between">
                                        <span>#{{ $tarefa->id }}</span>

                                        <div class="inline-flex items-center gap-1 font-medium text-slate-600">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="none">
                                                <path d="M5 5h10v10H5z" stroke="#9CA3AF" stroke-width="1.4"/>
                                            </svg>
                                            <span>Detalhes</span>
                                        </div>
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
    @include('operacional.kanban.modals.anexo-documento-cliente')

    {{-- Modal de Detalhes da Tarefa --}}
    <div id="tarefa-modal"
         class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/50 p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden">
            {{-- Cabeçalho --}}
            {{-- Cabeçalho (VERSÃO DEBUG) --}}
            <div
                class="flex items-center justify-between px-6 py-4
           rounded-t-2xl shadow-sm
           bg-gradient-to-r from-sky-800 via-sky-700 to-sky-500
           text-white border-b border-sky-900/40"
            >
                <div>
                    <h2 class="text-lg font-semibold tracking-tight">
                        Detalhes da Tarefa
                    </h2>
                    <p class="text-xs text-white/80 mt-0.5">
                        ID:
                        <span id="modal-tarefa-id"
                              class="font-mono bg-white/10 px-2 py-0.5 rounded">
                -
            </span>
                    </p>
                </div>

                <button type="button"
                        id="tarefa-modal-close"
                        class="w-8 h-8 flex items-center justify-center rounded-full
                   bg-white/15 hover:bg-white/25
                   text-white text-sm font-bold
                   transition-colors duration-150"
                        aria-label="Fechar">
                    ✕
                </button>
            </div>


            {{-- Conteúdo --}}
            <div
                class="flex-1 overflow-y-auto p-6 grid grid-cols-1 lg:grid-cols-[2fr,1.5fr] gap-5 text-sm text-slate-700">
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

                            <div id="modal-bloco-aso" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="md:col-span-2">
                                    <div class="flex items-center gap-3 py-2">
                                        <div class="h-px flex-1 bg-slate-200"></div>
                                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
                                            Dados do Funcionário
                                        </span>
                                        <div class="h-px flex-1 bg-slate-200"></div>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Funcionário</dt>
                                        <dd class="font-medium" id="modal-funcionario">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">CPF</dt>
                                        <dd class="font-medium" id="modal-funcionario-cpf">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Celular</dt>
                                        <dd class="font-medium" id="modal-funcionario-celular">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Função</dt>
                                        <dd class="font-medium" id="modal-funcionario-funcao">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Data de Nascimento</dt>
                                        <dd class="font-medium" id="modal-funcionario-nascimento">—</dd>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Tipo de ASO</dt>
                                        <dd class="font-medium" id="modal-aso-tipo">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Data de Realização</dt>
                                        <dd class="font-medium" id="modal-aso-data">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Unidade</dt>
                                        <dd class="font-medium" id="modal-aso-unidade">—</dd>
                                    </div>
                                </div>

                                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 mt-1">
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Vai fazer treinamento?</dt>
                                        <dd class="font-medium" id="modal-aso-treinamento">—</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">E-mail para envio do ASO</dt>
                                        <dd class="font-medium" id="modal-aso-email">—</dd>
                                    </div>
                                </div>

                                    <div class="md:col-span-2 mt-1">
                                        <dt class="text-[11px] text-slate-500">Treinamentos selecionados</dt>
                                        <dd class="font-medium text-sm" id="modal-aso-treinamentos">—</dd>
                                    </div>
                                    <div class="md:col-span-2 mt-1">
                                        <dt class="text-[11px] text-slate-500">Pacote de Treinamento</dt>
                                        <dd class="font-medium text-sm" id="modal-aso-pacote">—</dd>
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
                            <p class="text-[11px] font-semibold text-indigo-700">Modo</p>
                            <p id="modal-treinamento-modo" class="text-sm">—</p>
                        </div>

                        <div class="mt-2">
                            <p class="text-[11px] font-semibold text-indigo-700">Treinamentos selecionados</p>
                            <p id="modal-treinamento-codigos" class="text-sm">—</p>
                        </div>

                        <div class="mt-2">
                            <p class="text-[11px] font-semibold text-indigo-700">Pacote selecionado</p>
                            <p id="modal-treinamento-pacote" class="text-sm">—</p>
                        </div>

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
                                <dt class="text-[11px] text-slate-600">Tempo restante</dt>
                                <dd class="font-medium" id="modal-tempo-restante">—</dd>
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

                            {{-- NOVO: botão Editar Tarefa --}}
                            <button type="button"
                                    id="btn-editar-tarefa"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                       bg-emerald-500 text-white text-sm font-semibold shadow-sm
                       hover:bg-emerald-600 transition">
                                Editar Tarefa
                            </button>

                            <hr class="border-slate-200">


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
                    {{-- 5. Documento enviado ao cliente --}}
                    <section id="modal-arquivo-wrapper"
                             class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 mt-3 hidden">
                        <h3 class="text-xs font-semibold text-emerald-700 mb-2">
                            5. DOCUMENTO ENVIADO AO CLIENTE
                        </h3>

                        <p class="text-[12px] text-emerald-800 mb-2">
                            Este é o arquivo anexado e enviado ao cliente na finalização da tarefa.
                        </p>

                        <a id="modal-arquivo-link"
                           href="#"
                           target="_blank"
                           class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-700
                                 hover:text-emerald-900 underline">
                            📎 Abrir arquivo anexado
                        </a>
                        <button
                            type="button"
                            id="btn-notificar-cliente"
                            class="mt-3 w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                               bg-emerald-600 text-white text-sm font-semibold shadow-sm
                               hover:bg-emerald-700 transition hidden">
                            Notificar cliente (WhatsApp)
                        </button>
                    </section>
                    {{-- 5b. Documentos da tarefa (ASO, PGR, PCMSO etc) --}}
                    <section id="modal-docs-wrapper"
                             class="bg-slate-50 border border-slate-200 rounded-xl p-4 mt-3 hidden">
                        <h3 class="text-xs font-semibold text-slate-500 mb-2">
                            DOCUMENTOS DA TAREFA
                        </h3>

                        <p class="text-[12px] text-slate-600 mb-2">
                            Lista de documentos anexados à tarefa (ASO, PGR, PCMSO, etc).
                        </p>

                        <ul id="modal-docs-list" class="space-y-1 text-sm">
                            {{-- preenchido via JavaScript --}}
                        </ul>
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
                        @isset($usuario)
                            @if($usuario->isMaster())
                                <button
                                    type="button"
                                    id="btn-excluir-tarefa"
                                    class="mt-2 w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg
                                   border border-red-200 bg-red-50 text-red-700 text-sm font-semibold shadow-sm
                                   hover:bg-red-100 transition">
                                    Excluir Tarefa
                                </button>
                            @endif
                        @endisset
                    </section>


                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // =========================================================
            //  MODAL DE DETALHES DA TAREFA
            // =========================================================
            const modal = document.getElementById('tarefa-modal');
            const closeBtn = document.getElementById('tarefa-modal-close');

            const spanId = document.getElementById('modal-tarefa-id');
            const spanCliente = document.getElementById('modal-cliente');
            const spanCnpj = document.getElementById('modal-cnpj');

            const spanFuncionario = document.getElementById('modal-funcionario');
            const spanFuncionarioFuncao = document.getElementById('modal-funcionario-funcao');
            const spanFuncionarioCpf = document.getElementById('modal-funcionario-cpf');
            const spanFuncionarioCelular = document.getElementById('modal-funcionario-celular');
            const spanFuncionarioNascimento = document.getElementById('modal-funcionario-nascimento');
            const spanAsoTipo = document.getElementById('modal-aso-tipo');
            const spanAsoData = document.getElementById('modal-aso-data');
            const spanAsoUnidade = document.getElementById('modal-aso-unidade');
            const spanAsoTreinamento = document.getElementById('modal-aso-treinamento');
            const spanAsoTreinamentos = document.getElementById('modal-aso-treinamentos');
            const spanAsoPacote = document.getElementById('modal-aso-pacote');
            const spanAsoEmail = document.getElementById('modal-aso-email');
            const blocoAso = document.getElementById('modal-bloco-aso');

            const spanTelefone = document.getElementById('modal-telefone');
            const spanResp = document.getElementById('modal-responsavel');
            const spanServico = document.getElementById('modal-servico');
            const spanTipoServ = document.getElementById('modal-tipo-servico');
            const spanDataHora = document.getElementById('modal-datahora');
            const spanSla = document.getElementById('modal-sla');
            const spanTempoRestante = document.getElementById('modal-tempo-restante');
            const spanPrioridade = document.getElementById('modal-prioridade');
            const spanStatusText = document.getElementById('modal-status-text');
            const badgeStatus = document.getElementById('modal-status-badge');
            const spanObs = document.getElementById('modal-observacoes');
            const textareaObsInterna = document.getElementById('modal-observacao-interna');

            const docsWrapper = document.getElementById('modal-docs-wrapper');
            const docsList = document.getElementById('modal-docs-list');


            // PGR
            const blocoPgr = document.getElementById('modal-bloco-pgr');
            const spanPgrTipo = document.getElementById('modal-pgr-tipo');
            const spanPgrArt = document.getElementById('modal-pgr-art');
            const spanPgrHomens = document.getElementById('modal-pgr-qtd-homens');
            const spanPgrMulheres = document.getElementById('modal-pgr-qtd-mulheres');
            const spanPgrTotal = document.getElementById('modal-pgr-total-trabalhadores');
            const spanPgrComPcmso = document.getElementById('modal-pgr-com-pcmso');
            const spanPgrContr = document.getElementById('modal-pgr-contratante');
            const spanPgrContrCnpj = document.getElementById('modal-pgr-contratante-cnpj');
            const spanPgrObraNome = document.getElementById('modal-pgr-obra-nome');
            const spanPgrObraEnd = document.getElementById('modal-pgr-obra-endereco');
            const spanPgrObraCej = document.getElementById('modal-pgr-obra-cej-cno');
            const spanPgrObraTurno = document.getElementById('modal-pgr-obra-turno');
            const ulPgrFuncoes = document.getElementById('modal-pgr-funcoes');

            // Treinamento NR
            const blocoTreinamento = document.getElementById('modal-bloco-treinamento');
            const spanTreinLocal = document.getElementById('modal-treinamento-local');
            const spanTreinUnidade = document.getElementById('modal-treinamento-unidade');
            const spanTreinPart = document.getElementById('modal-treinamento-participantes');
            const spanTreinFuncs = document.getElementById('modal-treinamento-funcoes');
            const spanTreinModo = document.getElementById('modal-treinamento-modo');
            const spanTreinCodigos = document.getElementById('modal-treinamento-codigos');
            const spanTreinPacote = document.getElementById('modal-treinamento-pacote');

            // Link do documento da tarefa (arquivo_cliente_path)
            const arquivoWrapper = document.getElementById('modal-arquivo-wrapper');
            const arquivoLink = document.getElementById('modal-arquivo-link');
            const btnNotificarCliente = document.getElementById('btn-notificar-cliente');
            let detalhesCurrentCard = null;

            function formatTelefone(raw) {
                const digits = String(raw || '').replace(/\D/g, '');
                if (!digits) return '—';
                if (digits.length === 11) {
                    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
                }
                if (digits.length === 10) {
                    return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
                }
                return raw || '—';
            }

            function buildWhatsappMensagem(card, arquivoUrl) {
                const telefone = (card?.dataset?.telefone || '').replace(/\D/g, '');
                if (!telefone) return null;

                const servico = card?.dataset?.servico || 'documento';
                let links = [];
                if (arquivoUrl) {
                    links.push(arquivoUrl);
                }

                try {
                    const anexos = JSON.parse(card?.dataset?.anexos || '[]');
                    anexos.forEach((anexo) => {
                        if (anexo && anexo.url) {
                            links.push(anexo.url);
                        }
                    });
                } catch (e) {
                    // ignora erro de parse
                }

                links = Array.from(new Set(links));
                const linksTexto = links.length ? `\n\nLinks:\n${links.join('\n')}` : '';
                const mensagem = `Olá! Segue abaixo o anexo do ${servico}.\n\nEnviado pela Formed.${linksTexto}`;

                return { telefone, mensagem };
            }

            function parseIsoToMs(iso) {
                if (!iso) return null;
                const ts = Date.parse(iso);
                return Number.isNaN(ts) ? null : ts;
            }

            function formatDuration(ms) {
                const total = Math.max(0, Math.floor(ms / 1000));
                const horas = Math.floor(total / 3600);
                const minutos = Math.floor((total % 3600) / 60);
                const segundos = total % 60;
                return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
            }

            function isCardFinalizada(card) {
                if (!card) return false;
                const finalizadoFlag = String(card.dataset.finalizado || '') === '1';
                const status = String(card.dataset.status || '').toLowerCase();
                const colunaSlug = String(card.closest('.kanban-column')?.dataset?.colunaSlug || '').toLowerCase();
                return finalizadoFlag || status.includes('finalizada') || colunaSlug === 'finalizada';
            }

            function updateTempoCard(card, nowMs) {
                if (!card) return;
                const fimIso = card.dataset.fimPrevisto || '';
                const fimMs = parseIsoToMs(fimIso);
                const label = card.querySelector('[data-role="card-tempo-label"]');
                const textEl = card.querySelector('[data-role="card-tempo-text"]');
                if (!label || !textEl) return;

                if (isCardFinalizada(card)) {
                    textEl.textContent = 'Finalizada';
                    label.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-600', 'border-rose-200', 'bg-rose-50', 'text-rose-700');
                    label.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
                    return;
                }

                label.classList.remove('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
                if (!fimMs) {
                    textEl.textContent = '—';
                    return;
                }

                const diff = fimMs - nowMs;
                if (diff >= 0) {
                    textEl.textContent = formatDuration(diff);
                    label.classList.remove('border-rose-200', 'bg-rose-50', 'text-rose-700');
                    label.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
                } else {
                    textEl.textContent = `Atrasado ${formatDuration(Math.abs(diff))}`;
                    label.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-600');
                    label.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-700');
                }
            }

            function updateModalTempo(card, nowMs) {
                if (!spanTempoRestante || !card) return;
                if (isCardFinalizada(card)) {
                    spanTempoRestante.textContent = 'Finalizada';
                    return;
                }
                const fimIso = card.dataset.fimPrevisto || '';
                const fimMs = parseIsoToMs(fimIso);
                if (!fimMs) {
                    spanTempoRestante.textContent = '—';
                    return;
                }
                const diff = fimMs - nowMs;
                if (diff >= 0) {
                    spanTempoRestante.textContent = formatDuration(diff);
                } else {
                    spanTempoRestante.textContent = `Atrasado ${formatDuration(Math.abs(diff))}`;
                }
            }


            function openDetalhesModal(card) {
                if (!card) return;
                detalhesCurrentCard = card;

                const isCancelada = card.dataset.cancelada === '1';
                modal.dataset.cancelada = isCancelada ? '1' : '0';
                // Campos básicos
                spanId.textContent = card.dataset.id ?? '';
                spanCliente.textContent = card.dataset.cliente ?? '';
                spanCnpj.textContent = card.dataset.cnpj || '—';
                spanTelefone.textContent = card.dataset.telefone || '—';
                spanResp.textContent = card.dataset.responsavel ?? '';
                spanServico.textContent = card.dataset.servico ?? '';
                spanTipoServ.textContent = card.dataset.servico ?? '';
                spanDataHora.textContent = card.dataset.datahora ?? '';
                spanSla.textContent = card.dataset.sla ?? '-';
                spanPrioridade.textContent = card.dataset.prioridade ?? '';
                spanStatusText.textContent = card.dataset.status ?? '';
                spanObs.textContent = card.dataset.observacoes ?? '';
                updateModalTempo(card, Date.now());

                spanFuncionario.textContent = card.dataset.funcionario || '—';
                spanFuncionarioFuncao.textContent = card.dataset.funcionarioFuncao || '—';
                if (spanFuncionarioNascimento) {
                    spanFuncionarioNascimento.textContent = card.dataset.funcionarioNascimento || '—';
                }
                if (spanFuncionarioCelular) {
                    spanFuncionarioCelular.textContent = formatTelefone(card.dataset.funcionarioCelular || '');
                }

                if (textareaObsInterna) {
                    textareaObsInterna.value = card.dataset.observacaoInterna || '';
                }
                modal.dataset.observacaoUrl = card.dataset.observacaoUrl || '';

                // Link do arquivo do cliente
                if (arquivoWrapper && arquivoLink) {
                    const urlArquivo = card.dataset.arquivoClienteUrl || '';
                    console.log(urlArquivo)
                    if (urlArquivo) {
                        arquivoLink.href = urlArquivo;
                        arquivoWrapper.classList.remove('hidden');
                        if (btnNotificarCliente) {
                            btnNotificarCliente.classList.remove('hidden');
                        }
                    } else {
                        arquivoLink.href = '#';
                        arquivoWrapper.classList.add('hidden');
                        if (btnNotificarCliente) {
                            btnNotificarCliente.classList.add('hidden');
                        }
                    }
                }
                // ===============================
                // LISTA DE ANEXOS DA TAREFA
                // ===============================
                let anexos = [];
                try {
                    anexos = card.dataset.anexos ? JSON.parse(card.dataset.anexos) : [];
                } catch (e) {
                    anexos = [];
                }
                if (docsWrapper && docsList) {
                    // limpa lista anterior
                    docsList.innerHTML = '';

                    // 1) Documento enviado ao cliente (path_documento_cliente)
                    if (card.dataset.arquivoClienteUrl) {
                        anexos.push({
                            label: 'Documento enviado ao cliente',
                            url: card.dataset.arquivoClienteUrl
                        });
                    }

                    // 2) PGR anexado ao PCMSO (se existir)
                    if (card.dataset.pcmsoPgrUrl) {
                        anexos.push({
                            label: 'PGR anexado (PCMSO)',
                            url: card.dataset.pcmsoPgrUrl
                        });
                    }

                    // 👉 Aqui no futuro você pode ir plugando mais anexos:
                    // if (card.dataset.algumaOutraCoisaUrl) { ... }

                    if (anexos.length) {
                        docsWrapper.classList.remove('hidden');
                        docsList.innerHTML = anexos.map(a => `
                                <li>
                                    <a href="${a.url}" target="_blank" class="underline text-[13px] font-medium">
                                        ${a.label || 'Documento'}
                                    </a>
                                    <span class="text-[11px] text-slate-500">
                                        (${a.tamanho || '-'} · ${a.mime || '-'}${a.data ? ' · ' + a.data : ''}${a.uploaded_by ? ' · ' + a.uploaded_by : ''})
                                    </span>
                                </li>
                            `).join('');
                    } else {
                        docsWrapper.classList.add('hidden');
                        docsList.innerHTML = '';
                    }
                }

                // === REGRAS POR TIPO DE SERVIÇO ===
                const tipoServico = (card.dataset.servico || '').toLowerCase();
                const isAso = card.dataset.isAso === '1';
                const isPgr = tipoServico.includes('pgr');

                // ASO
                // ASO
                if (blocoAso) {
                    if (isAso) {
                        blocoAso.classList.remove('hidden');

                        spanFuncionario.textContent = card.dataset.funcionario || '—';
                        spanFuncionarioFuncao.textContent = card.dataset.funcionarioFuncao || '—';

                        if (spanFuncionarioCpf) {
                            spanFuncionarioCpf.textContent = card.dataset.funcionarioCpf || '—';
                        }
                        if (spanFuncionarioNascimento) {
                            spanFuncionarioNascimento.textContent = card.dataset.funcionarioNascimento || '—';
                        }
                        if (spanFuncionarioCelular) {
                            spanFuncionarioCelular.textContent = formatTelefone(card.dataset.funcionarioCelular || '');
                        }
                        if (spanAsoTipo) {
                            spanAsoTipo.textContent = card.dataset.asoTipo || '—';
                        }
                        if (spanAsoData) {
                            spanAsoData.textContent = card.dataset.asoData || '—';
                        }
                        if (spanAsoUnidade) {
                            spanAsoUnidade.textContent = card.dataset.asoUnidade || '—';
                        }
                        if (spanAsoTreinamento) {
                            spanAsoTreinamento.textContent = card.dataset.asoTreinamento || '—';
                        }
                        if (spanAsoTreinamentos) {
                            spanAsoTreinamentos.textContent = card.dataset.asoTreinamentos || '—';
                        }
                        if (spanAsoPacote) {
                            spanAsoPacote.textContent = card.dataset.asoPacote || '—';
                        }
                        if (spanAsoEmail) {
                            spanAsoEmail.textContent = card.dataset.asoEmail || '—';
                        }
                    } else {
                        blocoAso.classList.add('hidden');
                        spanFuncionario.textContent = '—';
                        spanFuncionarioFuncao.textContent = '—';
                        if (spanFuncionarioCpf) spanFuncionarioCpf.textContent = '—';
                        if (spanFuncionarioCelular) spanFuncionarioCelular.textContent = '—';
                        if (spanAsoTipo) spanAsoTipo.textContent = '—';
                        if (spanAsoData) spanAsoData.textContent = '—';
                        if (spanAsoUnidade) spanAsoUnidade.textContent = '—';
                        if (spanAsoTreinamento) spanAsoTreinamento.textContent = '—';
                        if (spanAsoTreinamentos) spanAsoTreinamentos.textContent = '—';
                        if (spanAsoEmail) spanAsoEmail.textContent = '—';
                    }
                }


                // PGR
                if (blocoPgr) {
                    if (isPgr) {
                        blocoPgr.classList.remove('hidden');

                        spanPgrTipo.textContent = card.dataset.pgrTipo || '—';

                        spanPgrArt.textContent = card.dataset.pgrComArt === '1'
                            ? 'Com ART'
                            : (card.dataset.pgrComArt === '0' ? 'Sem ART' : '—');

                        spanPgrHomens.textContent = card.dataset.pgrQtdHomens || '0';
                        spanPgrMulheres.textContent = card.dataset.pgrQtdMulheres || '0';
                        spanPgrTotal.textContent = card.dataset.pgrTotalTrabalhadores || '0';

                        spanPgrComPcmso.textContent = card.dataset.pgrComPcmso === '1'
                            ? 'Sim, PGR + PCMSO'
                            : (card.dataset.pgrComPcmso === '0' ? 'Não, apenas PGR' : '—');

                        spanPgrContr.textContent = card.dataset.pgrContratante || '—';
                        spanPgrContrCnpj.textContent = card.dataset.pgrContratanteCnpj || '—';

                        spanPgrObraNome.textContent = card.dataset.pgrObraNome || '—';
                        spanPgrObraEnd.textContent = card.dataset.pgrObraEndereco || '—';
                        spanPgrObraCej.textContent = card.dataset.pgrObraCejCno || '—';
                        spanPgrObraTurno.textContent = card.dataset.pgrObraTurno || '—';

                        if (ulPgrFuncoes) {
                            let funcoesJson = [];
                            try {
                                funcoesJson = card.dataset.pgrFuncoesJson
                                    ? JSON.parse(card.dataset.pgrFuncoesJson)
                                    : [];
                            } catch (e) {
                                funcoesJson = [];
                            }

                            if (Array.isArray(funcoesJson) && funcoesJson.length) {
                                const html = funcoesJson.map(item => {
                                    const nome = item.nome || 'Funcao';
                                    const qtd = typeof item.quantidade === 'number' ? item.quantidade : 0;
                                    const nrs = [];
                                    if (item.nr_altura) nrs.push('NR-35');
                                    if (item.nr_eletricidade) nrs.push('NR-10');
                                    if (item.nr_espaco_confinado) nrs.push('NR-33');
                                    const nrsHtml = nrs.length
                                        ? `<span class="ml-2 text-[11px] text-slate-500">${nrs.join(' · ')}</span>`
                                        : `<span class="ml-2 text-[11px] text-slate-400">Sem NRs</span>`;
                                    const content = `<span class="font-medium">${nome} (${qtd})</span>${nrsHtml}`;
                                    return ulPgrFuncoes.tagName === 'UL' ? `<li>${content}</li>` : `<div>${content}</div>`;
                                }).join('');
                                ulPgrFuncoes.innerHTML = html;
                            } else {
                                ulPgrFuncoes.textContent = card.dataset.pgrFuncoes || '—';
                            }
                        }
                    } else {
                        blocoPgr.classList.add('hidden');
                    }
                }

                // limpa blocos especiais
                ['apr', 'ltcat', 'ltip', 'pae', 'pcmso'].forEach(slug => {
                    const el = document.getElementById(`modal-bloco-${slug}`);
                    if (el) el.classList.add('hidden');
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

                    const localTipo = card.dataset.treinamentoLocal || '—';
                    const unidade = card.dataset.treinamentoUnidade || '—';
                    let participantes = card.dataset.treinamentoParticipantes || '';
                    const funcoes = card.dataset.treinamentoFuncoes || '—';
                    const modo = card.dataset.treinamentoModo || '';
                    const pacote = card.dataset.treinamentoPacote || '';
                    const codigos = card.dataset.treinamentoCodigos || '';

                    spanTreinLocal.textContent = localTipo === 'clinica' ? 'Clínica' : 'In Company';
                    spanTreinUnidade.textContent = unidade;
                    try {
                        const lista = participantes ? JSON.parse(participantes) : [];
                        if (Array.isArray(lista) && lista.length) {
                            spanTreinPart.innerHTML = lista.map(nome => String(nome)).join('<br>');
                        } else {
                            spanTreinPart.textContent = '—';
                        }
                    } catch (e) {
                        spanTreinPart.textContent = participantes || '—';
                    }
                    spanTreinFuncs.textContent = funcoes;
                    if (spanTreinModo) {
                        spanTreinModo.textContent = modo === 'pacote' ? 'Pacote' : (modo === 'avulso' ? 'Avulso' : '—');
                    }
                    if (spanTreinCodigos) {
                        spanTreinCodigos.textContent = codigos || '—';
                    }
                    if (spanTreinPacote) {
                        spanTreinPacote.textContent = pacote || '—';
                    }

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

                    document.getElementById('modal-ltcat-tipo').textContent = tipoLabel;
                    document.getElementById('modal-ltcat-endereco').textContent = card.dataset.ltcatEndereco || '—';
                    document.getElementById('modal-ltcat-total-funcoes').textContent =
                        card.dataset.ltcatTotalFuncoes || '—';
                    document.getElementById('modal-ltcat-total-func').textContent =
                        card.dataset.ltcatTotalFuncionarios || '—';
                    document.getElementById('modal-ltcat-funcoes').textContent =
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

                    document.getElementById('modal-pcmso-tipo').textContent = tipoLabel;
                    document.getElementById('modal-pcmso-prazo').textContent = card.dataset.pcmsoPrazo || '—';
                    document.getElementById('modal-pcmso-obra-nome').textContent = card.dataset.pcmsoObraNome || '—';
                    document.getElementById('modal-pcmso-obra-cnpj').textContent = card.dataset.pcmsoObraCnpj || '—';
                    document.getElementById('modal-pcmso-obra-cei').textContent = card.dataset.pcmsoObraCei || '—';
                    document.getElementById('modal-pcmso-obra-endereco').textContent =
                        card.dataset.pcmsoObraEndereco || '—';

                    const linkWrapper = document.getElementById('modal-pcmso-pgr-wrapper');
                    const linkEl = document.getElementById('modal-pcmso-pgr-link');
                    const url = card.dataset.pcmsoPgrUrl || '';

                    if (url) {
                        linkEl.href = url;
                        linkWrapper.classList.remove('hidden');
                    } else {
                        linkEl.href = '#';
                        linkWrapper.classList.add('hidden');
                    }

                    document.getElementById('modal-bloco-pcmso').classList.remove('hidden');
                }

                // badge de status
                if (isCancelada) {
                    spanStatusText.textContent = 'Cancelada';
                    badgeStatus.className =
                        'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ' +
                        'bg-red-100 text-red-700 border border-red-200';
                } else {
                    spanStatusText.textContent = card.dataset.status || '';
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
                }

                modal.dataset.moveUrl = card.dataset.moveUrl || '';
                modal.dataset.tarefaId = card.dataset.id || '';
                modal.dataset.editUrl = card.dataset.editUrl || '';

                // === BLOQUEIO DE AÇÕES SE CANCELADA ===
                const btnEditar      = document.getElementById('btn-editar-tarefa');
                const btnSalvarObs   = document.getElementById('btn-salvar-observacao');
                const btnExcluir     = document.getElementById('btn-excluir-tarefa');
                const moverBtns      = document.querySelectorAll('.js-mover-coluna');

                // const isCancelada = card.dataset.cancelada === '1';

                function toggleBtn(btn, disabled) {
                    if (!btn) return;
                    if (disabled) {
                        btn.setAttribute('disabled', 'disabled');
                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        btn.removeAttribute('disabled');
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }

                if (isCancelada) {
                    toggleBtn(btnEditar, true);
                    toggleBtn(btnSalvarObs, true);
                    toggleBtn(btnExcluir, true);
                    moverBtns.forEach(b => toggleBtn(b, true));
                } else {
                    toggleBtn(btnEditar, false);
                    toggleBtn(btnSalvarObs, false);
                    toggleBtn(btnExcluir, false);
                    moverBtns.forEach(b => toggleBtn(b, false));
                }



                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            if (btnNotificarCliente) {
                btnNotificarCliente.addEventListener('click', function () {
                    if (!detalhesCurrentCard) return;
                    const arquivoUrl = detalhesCurrentCard.dataset.arquivoClienteUrl || '';
                    if (!arquivoUrl) {
                        window.uiAlert('Nenhum documento anexado para enviar.');
                        return;
                    }

                    const payload = buildWhatsappMensagem(detalhesCurrentCard, arquivoUrl);
                    if (!payload) {
                        window.uiAlert('Telefone do cliente não informado.');
                        return;
                    }

                    const whatsappUrl = `https://wa.me/${payload.telefone}?text=${encodeURIComponent(payload.mensagem)}`;
                    window.open(whatsappUrl, '_blank');
                });
            }

            // Clique no card -> abre APENAS o modal de detalhes
            document.addEventListener('click', function (e) {
                const card = e.target.closest('.kanban-card');
                if (!card) return;
                openDetalhesModal(card);
            });

            // =========================================================
            //  MODAL DE FINALIZAR COM ARQUIVO
            // =========================================================
            const finalizarModal = document.getElementById('tarefa-finalizar-modal');
            const finalizarCloseBtn = document.getElementById('tarefa-finalizar-close');
            const finalizarCloseXBtn = document.getElementById('tarefa-finalizar-x');
            const finalizarForm = document.getElementById('tarefa-finalizar-form');
            const finalizarArquivo = document.getElementById('tarefa-finalizar-arquivo');
            const finalizarNotificar = document.getElementById('tarefa-finalizar-notificar');
            const finalizarTituloSpan = document.getElementById('tarefa-finalizar-titulo');
            const finalizarClienteSpan = document.getElementById('tarefa-finalizar-cliente');

            let finalizarCurrentCard = null;
            let finalizarUrl = null;

            function openFinalizarModal(card, url) {
                console.log(finalizarModal)
                if (!finalizarModal) return;

                finalizarCurrentCard = card;
                finalizarUrl = url;

                if (finalizarTituloSpan) {
                    finalizarTituloSpan.textContent =
                        (card.dataset.servico || '') + ' - #' + (card.dataset.id || '');
                }
                if (finalizarClienteSpan) {
                    finalizarClienteSpan.textContent = card.dataset.cliente || '';
                }

                if (finalizarArquivo) {
                    finalizarArquivo.value = '';
                }
                if (finalizarNotificar) {
                    finalizarNotificar.checked = true;
                }

                finalizarModal.classList.remove('hidden');
                finalizarModal.classList.add('flex');
            }

            function closeFinalizarModal() {
                console.log('chamando metodo para fechar modal')
                if (!finalizarModal) return;
                finalizarModal.classList.add('hidden');
                finalizarModal.classList.remove('flex');
                finalizarCurrentCard = null;
                finalizarUrl = null;
            }

            [finalizarCloseBtn, finalizarCloseXBtn].forEach((btn) => {
                if (!btn) return;

                btn.addEventListener('click', function () {
                    console.log('fechar');
                    closeFinalizarModal();
                    // se quiser voltar o card pra coluna original:
                    window.location.reload();
                });
            });

            if (finalizarModal) {
                finalizarModal.addEventListener('click', function (e) {
                    if (e.target === finalizarModal) {
                        closeFinalizarModal();
                        window.location.reload();
                    }
                });
            }

            if (finalizarForm) {
                finalizarForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (!finalizarUrl || !finalizarCurrentCard || !finalizarArquivo.files.length) {
                        return;
                    }

                    const abrirWhatsapp = finalizarNotificar && finalizarNotificar.checked;
                    const whatsappPopup = abrirWhatsapp ? window.open('about:blank', '_blank') : null;
                    if (whatsappPopup) {
                        whatsappPopup.document.write('Aguarde, preparando o envio...');
                    }

                    const formData = new FormData();
                    formData.append('arquivo_cliente', finalizarArquivo.files[0]);
                    if (finalizarNotificar && finalizarNotificar.checked) {
                        formData.append('notificar', '1');
                    }

                    fetch(finalizarUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })
                        .then(async (r) => {
                            const contentType = r.headers.get('content-type') || '';
                            const isJson = contentType.includes('application/json');
                            const data = isJson ? await r.json() : null;

                            if (!r.ok) {
                                const error =
                                    data?.error
                                    || data?.message
                                    || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                    || 'Erro ao finalizar tarefa.';
                                throw new Error(error);
                            }

                            return data;
                        })
                        .then(data => {
                            if (!data || !data.ok) {
                                const error =
                                    data?.error
                                    || data?.message
                                    || (data?.errors ? Object.values(data.errors).flat()[0] : null)
                                    || 'Erro ao finalizar tarefa.';
                                window.uiAlert(error);
                                return;
                            }

                            // Atualiza dataset do card com a URL do arquivo, se voltou do backend
                            if (data.documento_url) {
                                finalizarCurrentCard.dataset.arquivoClienteUrl = data.documento_url;
                            }

                            // atualiza status do card
                            const statusName = data.status_label || 'Finalizada';
                            finalizarCurrentCard.dataset.status = statusName;
                            finalizarCurrentCard.dataset.finalizado = '1';

                            const statusSpan = finalizarCurrentCard.querySelector('[data-role="card-status-label"]');
                            if (statusSpan) {
                                statusSpan.textContent = statusName;
                            }

                            if (finalizarNotificar && finalizarNotificar.checked) {
                                const arquivoUrl = data.documento_url || '';
                                const payload = buildWhatsappMensagem(finalizarCurrentCard, arquivoUrl);

                                if (payload) {
                                    const whatsappUrl = `https://wa.me/${payload.telefone}?text=${encodeURIComponent(payload.mensagem)}`;
                                    if (whatsappPopup && !whatsappPopup.closed) {
                                        whatsappPopup.location.href = whatsappUrl;
                                    } else {
                                        window.location.href = whatsappUrl;
                                    }
                                } else if (whatsappPopup && !whatsappPopup.closed) {
                                    whatsappPopup.close();
                                }
                            }

                            closeFinalizarModal();
                            // Para evitar descompasso de contadores/ordem, recarrega a página
                            window.location.reload();
                        })
                        .catch((error) => {
                            if (whatsappPopup && !whatsappPopup.closed) {
                                whatsappPopup.close();
                            }
                            window.uiAlert(error?.message || 'Erro ao finalizar tarefa.');
                        });
                });
            }

            // Função global usada pelo Sortable
            window.abreModalFinalizarTarefa = openFinalizarModal;

            // =========================================================
            //  SLA / TEMPO REAL (polling)
            // =========================================================
            const PRAZOS_URL = @json(route('operacional.tarefas.prazos'));
            const CSRF_TOKEN = @json(csrf_token());
            const POLL_INTERVAL = 30000;
            const TICK_INTERVAL = 1000;

            function getKanbanCards() {
                return Array.from(document.querySelectorAll('.kanban-card'));
            }

            function updateAllTempoLabels() {
                const nowMs = Date.now();
                getKanbanCards().forEach(card => updateTempoCard(card, nowMs));
                if (detalhesCurrentCard) {
                    updateModalTempo(detalhesCurrentCard, nowMs);
                }
            }

            function moveCardToColumn(card, colunaId, colunaNome) {
                const destino = document.querySelector(`.kanban-column[data-coluna-id="${colunaId}"]`);
                if (!destino) return;
                destino.appendChild(card);

                const colunaCor = destino.dataset.colunaCor || '';
                if (colunaCor) {
                    card.style.borderLeftColor = colunaCor;
                }

                if (colunaNome) {
                    card.dataset.status = colunaNome;
                }
                card.dataset.finalizado = (String(destino.dataset.colunaSlug || '') === 'finalizada') ? '1' : '0';
            }

            async function pollPrazos() {
                const ids = getKanbanCards()
                    .map(card => card.dataset.id)
                    .filter(Boolean);

                if (!ids.length) return;

                try {
                    const res = await fetch(PRAZOS_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids }),
                    });
                    const data = await res.json();
                    if (!data || !data.ok || !Array.isArray(data.tarefas)) return;

                    data.tarefas.forEach((tarefa) => {
                        const card = document.querySelector(`.kanban-card[data-id="${tarefa.id}"]`);
                        if (!card) return;

                        if (tarefa.fim_previsto) {
                            card.dataset.fimPrevisto = tarefa.fim_previsto;
                        }

                        const colunaAtual = card.closest('.kanban-column')?.dataset?.colunaId;
                        if (tarefa.coluna_id && String(tarefa.coluna_id) !== String(colunaAtual)) {
                            moveCardToColumn(card, tarefa.coluna_id, tarefa.coluna_nome || '');
                        }
                    });
                } catch (e) {
                    // silencioso
                }
            }

            updateAllTempoLabels();
            setInterval(updateAllTempoLabels, TICK_INTERVAL);
            pollPrazos();
            setInterval(pollPrazos, POLL_INTERVAL);

            // =========================================================
            //  DRAG & DROP (Sortable)
            // =========================================================
            if (window.Sortable) {
                document.querySelectorAll('.kanban-column').forEach(function (colunaEl) {

                    new Sortable(colunaEl, {
                        group: 'kanban',
                        animation: 150,
                        handle: '.kanban-card',
                        draggable: '.kanban-card',

                        onMove: function (evt) {
                            const card = evt.dragged;
                            if (card.dataset.cancelada === '1') {
                                return false; // se a tarefa estiver cancelada, cancela o movimento
                            }
                        },
                        onEnd: function (evt) {
                            const card = evt.item;
                            const colunaId = card.closest('.kanban-column').dataset.colunaId;
                            const colunaEl = card.closest('.kanban-column');
                            const colunaCor = colunaEl?.dataset.colunaCor || '#38bdf8';
                            const colunaSlug = colunaEl?.dataset.colunaSlug || '';
                            const moveUrl = card.dataset.moveUrl;
                            const colunaOrigemEl = evt.from;
                            const colunaOrigemId = colunaOrigemEl?.dataset?.colunaId || '';

                            // segurança extra: se por algum motivo chegou aqui, não processa
                            if (card.dataset.cancelada === '1') {
                                evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                                return;
                            }

                            // ajusta cor da borda com a cor da coluna
                            if (colunaCor) {
                                card.style.borderLeftColor = colunaCor;
                            }

                            // Se soltou na coluna "finalizada": NÃO chama mover(),
                            // abre o modal de finalizar com arquivo.

                            if (colunaSlug === 'finalizada') {
                                const finalizarUrl = card.dataset.finalizarUrl;
                                if (finalizarUrl) {
                                    openFinalizarModal(card, finalizarUrl);
                                } else {
                                    window.location.reload();
                                }
                                return;
                            }

                            // demais colunas -> fluxo normal de mover
                            if (!moveUrl || !colunaId) return;

                            const idsDestino = Array.from(colunaEl.querySelectorAll('.kanban-card'))
                                .map(el => el.dataset.id)
                                .filter(Boolean);
                            const idsOrigem = colunaOrigemEl
                                ? Array.from(colunaOrigemEl.querySelectorAll('.kanban-card'))
                                    .map(el => el.dataset.id)
                                    .filter(Boolean)
                                : [];

                            fetch(moveUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    coluna_id: colunaId,
                                    ordem: idsDestino,
                                    coluna_origem_id: colunaOrigemId,
                                    ordem_origem: idsOrigem,
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
                                    card.dataset.finalizado = (colunaSlug === 'finalizada') ? '1' : '0';

                                    const respBadge = card.querySelector('[data-role="card-responsavel-badge"]');
                                    if (respBadge && colunaCor) {
                                        respBadge.style.borderColor = colunaCor;
                                        respBadge.style.color = '#0f172a';
                                        respBadge.style.backgroundColor = colunaCor + '20';
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
                                    // aqui dá pra colocar um toast se quiser
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

            const modal = document.getElementById('tarefa-modal');
            const statusText = document.getElementById('modal-status-text');


            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    const colunaId = this.dataset.colunaId;

                    const isCancelada = modal.dataset.cancelada === '1';
                    console.log(isCancelada)
                    if (isCancelada) {
                        window.uiAlert('Esta tarefa está cancelada e não pode ser movimentada.');
                        return;
                    }

                    // Rota do método mover
                    const url = modal.dataset.moveUrl;

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
                                window.uiAlert('Não foi possível mover a tarefa.');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            window.uiAlert('Erro ao mover a tarefa.');
                        });
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('tarefa-modal');
            const textareaObsInterna = document.getElementById('modal-observacao-interna');
            const btnSalvarObs = document.getElementById('btn-salvar-observacao');

            if (btnSalvarObs && textareaObsInterna) {
                btnSalvarObs.addEventListener('click', function () {
                    const url = modal.dataset.observacaoUrl;

                    if (!url) {
                        window.uiAlert('Nenhuma tarefa selecionada para salvar observação.');
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
                                window.uiAlert('Não foi possível salvar a observação.');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            window.uiAlert('Erro ao salvar a observação.');
                        });
                });
            }
        });

    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnExcluir = document.getElementById('btn-excluir-tarefa');

            if (btnExcluir) {
                btnExcluir.addEventListener('click', async () => {
                    const ok = await window.uiConfirm('Tem certeza que deseja excluir esta tarefa?');
                    if (!ok) return;

                    const idSpan = document.getElementById('modal-tarefa-id');
                    const tarefaId = idSpan ? idSpan.textContent.trim() : null;

                    if (!tarefaId) {
                        window.uiAlert('ID da tarefa não encontrado.');
                        return;
                    }

                    fetch(`{{ url('operacional/tarefas') }}/${tarefaId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    })
                        .then(r => r.json())
                        .then(json => {
                            if (!json.ok) {
                                window.uiAlert(json.message || 'Não foi possível excluir a tarefa.');
                                return;
                            }

                            // Fecha modal e faz refresh ou remove a card do Kanban via JS
                            const modal = document.getElementById('tarefa-modal');
                            if (modal) modal.classList.add('hidden');

                            // Se quiser ser simples:
                            window.location.reload();
                        })
                        .catch(() => {
                            window.uiAlert('Erro na comunicação com o servidor.');
                        });
                });
            }
        });


    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('tarefa-modal');
            const btnEditar = document.getElementById('btn-editar-tarefa');

            if (modal && btnEditar) {
                btnEditar.addEventListener('click', function () {
                    const url = modal.dataset.editUrl;

                    if (!url) {
                        window.uiAlert('Edição ainda não está disponível para este tipo de tarefa.');
                        return;
                    }

                    // Redireciona para a tela de edição
                    window.location.href = url;
                });
            }
        });
    </script>

@endpush
