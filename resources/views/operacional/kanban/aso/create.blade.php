@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')


@php
    /** @var \App\Models\Tarefa|null $tarefa */
    $isEdit = isset($tarefa);

    $anexos = $anexos ?? collect();

    $aso = $isEdit ? $tarefa->asoSolicitacao : null;

    // funcionário selecionado no select
    $funcionarioSelecionadoId = old('funcionario_id', $isEdit ? ($tarefa->funcionario_id ?? '') : '');

    $temFuncionario = !empty($funcionarioSelecionadoId);

    // treinamentos selecionados: old() > aso_solicitacao > []
    $treinamentosSelecionados = old(
        'treinamentos',
        $aso && is_array($aso->treinamentos) ? $aso->treinamentos : []
    );

    // Vai fazer treinamento? old() > aso_solicitacao > 0
    $vaiFazerTreinamento = (int) old(
        'vai_fazer_treinamento',
        $aso ? (int) $aso->vai_fazer_treinamento : 0
    );

    $treinamentosPermitidos = $treinamentosPermitidos ?? [];
    $temTreinamentosPermitidos = !empty($treinamentosPermitidos) || !empty($pacotesTreinamentos ?? []);
    $treinamentosDisponiveis = $treinamentosDisponiveis ?? [];
    $pacotesTreinamentos = $pacotesTreinamentos ?? [];
    $treinamentosBloqueados = array_diff(array_keys($treinamentosDisponiveis), $treinamentosPermitidos);
    $temTreinamentosBloqueados = !empty($treinamentosDisponiveis) && !empty($treinamentosBloqueados);
    $treinamentoAviso = 'Serviço não contratado, converse com seu comercial';
    $pacoteSelecionadoId = old(
        'pacote_id',
        $aso && !empty($aso->treinamento_pacote['contrato_item_id'])
            ? $aso->treinamento_pacote['contrato_item_id']
            : ''
    );
    $modoTreinamento = old('treinamento_modo', $pacoteSelecionadoId ? 'pacotes' : 'avulsos');
    if (!$temTreinamentosPermitidos) {
        $vaiFazerTreinamento = 0;
    }

    // Data do ASO (Y-m-d): old() > aso_solicitacao > $dataAso calculado no controller (fallback)
    $dataAsoValue = old(
        'data_aso',
        $aso && $aso->data_aso
            ? $aso->data_aso->format('Y-m-d')
            : ($dataAso ?? '')
    );

    // Unidade: old() > aso_solicitacao > unidadeSelecionada calculado no controller (fallback)
    $unidadeSelecionada = old(
        'unidade_id',
        $aso ? $aso->unidade_id : ($unidadeSelecionada ?? null)
    );

    // Tipo de ASO: old() > aso_solicitacao
    $tipoAsoSelected = old(
        'tipo_aso',
        $aso->tipo_aso ?? null
    );

    $tiposAsoPermitidos = $tiposAsoPermitidos ?? [];
    $temTiposAsoPermitidos = !empty($tiposAsoPermitidos);
    $tipoAsoSelecionadoPermitido = $tipoAsoSelected
        ? in_array($tipoAsoSelected, $tiposAsoPermitidos, true)
        : true;

    // Email para envio do ASO
    $emailAso = old(
        'email_aso',
        $aso->email_aso ?? ''
    );

    // Helper para pegar dados do funcionário no modo edição
    $funcionario = $isEdit ? optional($tarefa->funcionario) : null;

    // 🔹 Origem da tela (cliente ou operacional)
    $origem = request()->query('origem');
    $rotaVoltar = $origem === 'cliente'
        ? route('cliente.dashboard')
        : route('operacional.kanban.servicos', $cliente);
@endphp

@section('title', 'Agendar ASO')

@section('content')
    <div class="w-full px-2 sm:px-3 md:px-4 xl:px-5 py-4 md:py-6">

        {{-- Voltar --}}
        <div class="mb-4">
            <a href="{{ $rotaVoltar }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>←</span>
                <span>Voltar</span>
            </a>
        </div>

        {{-- Card principal --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            {{-- Cabeçalho azul --}}
            <div class="px-4 sm:px-5 md:px-6 py-4 bg-gradient-to-r from-[#0A3A80] to-[#1E68D9]">
                <h1 class="text-lg md:text-xl font-semibold text-white mb-1">
                    Agendar ASO
                </h1>
                <p class="text-xs md:text-sm text-blue-100">
                    {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                </p>
            </div>


            {{-- Conteúdo / Form --}}
            <div class="px-4 sm:px-5 md:px-6 py-5 md:py-6">
                @if($errors->any())
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700">
                        <p class="font-medium mb-1">Ocorreram alguns erros ao salvar:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if(!$temTiposAsoPermitidos || !$tipoAsoSelecionadoPermitido)
                    <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800">
                        ASO não disponível, fale com seu comercial.
                    </div>
                @endif

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ $isEdit
                        ? route('operacional.kanban.aso.update', ['tarefa' => $tarefa, 'origem' => $origem])
                        : route('operacional.kanban.aso.store', ['cliente' => $cliente, 'origem' => $origem]) }}"
                >
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    {{-- NAV DAS ABAS --}}
                    <div class="border-b border-slate-200 mb-4">
                        <nav class="flex gap-6 text-sm">
                            <button type="button"
                                    class="tab-btn border-b-2 border-sky-500 text-sky-600 font-semibold pb-2"
                                    data-tab="dados">
                                Dados do ASO
                            </button>

                            <button type="button"
                                    class="tab-btn text-slate-500 hover:text-slate-700 pb-2"
                                    data-tab="anexos">
                                Anexos
                            </button>
                        </nav>
                    </div>

                    <div id="tab-dados" class="space-y-6">
                        {{-- Tipo de ASO --}}
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-slate-600">
                                Tipo de ASO *
                            </label>

                            <select name="tipo_aso"
                                    @disabled(!$temTiposAsoPermitidos)
                                    class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3 bg-white
                                       focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                                <option value="">Selecione o tipo de ASO</option>
                                @foreach($tiposAso as $key => $label)
                                    @php
                                        $tipoPermitido = in_array($key, $tiposAsoPermitidos, true);
                                        $tipoSelecionado = $tipoAsoSelected === $key;
                                    @endphp
                                    <option value="{{ $key }}"
                                            @selected($tipoSelecionado)
                                            @disabled(!$tipoPermitido && !$tipoSelecionado)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Colaborador + dados do funcionário --}}
                        <div class="space-y-6 mt-4">

                            {{-- Colaborador --}}
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-slate-600">
                                    Colaborador *
                                </label>

                                <select name="funcionario_id"
                                        id="funcionario_id"
                                        data-funcionario-url="{{ route('operacional.kanban.aso.funcionario', ['cliente' => $cliente, 'funcionario' => 'FUNCIONARIO_ID']) }}"
                                        class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3">
                                    <option value="">Novo colaborador</option>
                                    @foreach($funcionarios as $func)
                                        <option value="{{ $func->id }}"
                                            {{ (string) $funcionarioSelecionadoId === (string) $func->id ? 'selected' : '' }}>
                                            {{ $func->nome }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="text-[11px] text-slate-400">
                                    Se for um colaborador novo, deixe o campo acima em branco e preencha os dados
                                    abaixo.
                                </p>
                            </div>

                            {{-- Nome completo / Função --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">
                                        Nome Completo *
                                    </label>
                                    <input type="text"
                                           id="campo_nome"
                                           name="nome"
                                           value="{{ old('nome', $funcionario->nome ?? '') }}"
                                           placeholder="Nome completo"
                                           class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                                              focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                                              {{ $temFuncionario ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' }}"
                                        {{ $temFuncionario ? 'disabled' : '' }}>
                                </div>

                                <x-funcoes.select-with-create
                                    name="funcao_id"
                                    label="Função"
                                    field-id="campo_funcao"
                                    help-text="Funções listadas por GHE, pré-configuradas pelo vendedor/comercial."
                                    :allowCreate="false"
                                    :funcoes="$funcoes"
                                    :selected="old('funcao_id', $funcionario->funcao_id ?? null)"
                                />
                            </div>

                            <div id="asoResumo"
                                 data-aso-resumo-url="{{ $asoResumoUrl ?? '' }}"
                                 class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-semibold text-slate-700">Resumo do ASO</h4>
                                    <span class="text-[10px] text-slate-400">GHE + Grupo de Exames</span>
                                </div>
                                <p id="asoResumoStatus" class="mt-2 text-xs text-slate-500">
                                    Selecione o tipo de ASO e a função para visualizar os exames.
                                </p>
                                <div class="mt-3 flex items-center justify-between">
                                    <button type="button"
                                            id="asoResumoToggle"
                                            class="text-xs font-medium text-sky-600 hover:text-sky-700 hidden">
                                        Ver exames
                                    </button>
                                    <span id="asoResumoCount" class="text-[11px] text-slate-400 hidden"></span>
                                </div>

                                <div id="asoResumoBadges" class="mt-3 flex flex-wrap gap-2 hidden"></div>
                                <div id="asoResumoTotal" class="mt-3 flex items-center justify-between text-xs font-semibold text-slate-700 hidden">
                                    <span>Total estimado</span>
                                    <span id="asoResumoTotalValor">R$ 0,00</span>
                                </div>
                            </div>

                            {{-- CPF / RG / Data Nascimento --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">
                                        CPF *
                                    </label>
                                    <input type="text"
                                           id="campo_cpf"
                                           name="cpf"
                                           value="{{ old('cpf', $funcionario->cpf ?? '') }}"
                                           placeholder="000.000.000-00"
                                           class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                                              focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                                              {{ $temFuncionario ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' }}"
                                        {{ $temFuncionario ? 'disabled' : '' }}>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">
                                        RG *
                                    </label>
                                    <input type="text"
                                           id="campo_rg"
                                           name="rg"
                                           value="{{ old('rg', $funcionario->rg ?? '') }}"
                                           placeholder="00.000.000-0"
                                           class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                                              focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                                              {{ $temFuncionario ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' }}"
                                        {{ $temFuncionario ? 'disabled' : '' }}>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">
                                        Data de Nascimento *
                                    </label>
                                    <input type="date"
                                           id="campo_data_nascimento"
                                           name="data_nascimento"
                                           value="{{ old(
                                                    'data_nascimento',
                                                    isset($tarefa) && $tarefa?->funcionario
                                                        ? $tarefa->funcionario->data_nascimento?->format('Y-m-d')
                                                        : ''
                                                ) }}"
                                           class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                                              focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400
                                              {{ $temFuncionario ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' }}"
                                        {{ $temFuncionario ? 'disabled' : '' }}>
                                </div>
                            </div>
                        </div>

                        {{-- E-mail para envio do ASO --}}
                        <div class="mt-6">
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                E-mail para envio de ASO
                            </label>
                            <input type="email"
                                   name="email_aso"
                                   value="{{ $emailAso }}"
                                   placeholder="email@exemplo.com"
                                   class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                                      focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                        </div>

                        {{-- Telefone/Celular para contato --}}
                        <div class="mt-6">
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Telefone do colaborador
                            </label>
                            <input type="text"
                                   name="celular"
                                   value="{{ old('celular', $funcionario->celular ?? '') }}"
                                   placeholder="(00) 00000-0000"
                                   class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                                      focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                        </div>

                        {{-- Vai fazer treinamento conosco? --}}
                        <div class="space-y-3 mt-6">
                            <p class="text-xs font-medium text-slate-600">
                                Vai fazer treinamento conosco?
                            </p>

                            {{-- campo real enviado pro backend --}}
                            <input type="hidden"
                                   id="vai_fazer_treinamento"
                                   name="vai_fazer_treinamento"
                                   value="{{ $vaiFazerTreinamento ? 1 : 0 }}">

                            <div class="grid grid-cols-2 gap-3 text-sm">
                                {{-- SIM --}}
                                <button type="button"
                                        id="btn_treina_sim"
                                        class="px-4 py-2 rounded-xl border text-center text-xs font-medium
                                        {{ $vaiFazerTreinamento ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300' }}
                                        {{ $temTreinamentosPermitidos ? '' : 'opacity-60 cursor-not-allowed' }}"
                                        {{ $temTreinamentosPermitidos ? '' : 'disabled' }}
                                        title="{{ $temTreinamentosPermitidos ? '' : $treinamentoAviso }}">
                                    Sim
                                </button>

                                {{-- NÃO --}}
                                <button type="button"
                                        id="btn_treina_nao"
                                        class="px-4 py-2 rounded-xl border text-center text-xs font-medium
                                        {{ !$vaiFazerTreinamento ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300' }}">
                                    Não
                                </button>
                            </div>

                            <div id="treinamentosWrap" class="{{ $vaiFazerTreinamento ? '' : 'hidden' }}">
                                <input type="hidden" name="treinamento_modo" id="treinamentoModoAso" value="{{ $modoTreinamento }}">

                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button"
                                            id="btnModoPacotes"
                                            class="px-3 py-2 rounded-xl border text-xs font-semibold bg-white text-slate-700 border-slate-300">
                                        Pacotes
                                    </button>
                                    <button type="button"
                                            id="btnModoAvulsos"
                                            class="px-3 py-2 rounded-xl border text-xs font-semibold bg-slate-900 text-white border-slate-900">
                                        Avulsos
                                    </button>
                                </div>

                                {{-- Pacotes --}}
                                @if(!empty($pacotesTreinamentos))
                                    <div id="pacotesTreinamentosAso" data-treinamento-mode="pacotes"
                                         class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-3 hidden">
                                        <div class="text-xs font-semibold text-slate-700">Pacotes de Treinamentos</div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            @foreach($pacotesTreinamentos as $pacote)
                                                @php
                                                    $pacoteCodigos = $pacote['codigos'] ?? [];
                                                @endphp
                                                <label class="block border rounded-2xl px-3 py-3 text-xs cursor-pointer bg-white hover:bg-slate-50">
                                                    <div class="flex items-start gap-2">
                                                        <input type="radio"
                                                               name="pacote_id"
                                                               value="{{ $pacote['contrato_item_id'] ?? '' }}"
                                                               @checked((string) ($pacote['contrato_item_id'] ?? '') === (string) $pacoteSelecionadoId)
                                                               class="mt-1 border-slate-300 text-sky-500 focus:ring-sky-400"
                                                               data-treinamento-pacote
                                                               data-codigos='@json($pacoteCodigos)'>
                                                        <div>
                                                            <p class="font-semibold text-slate-800 text-sm">
                                                                {{ $pacote['nome'] ?? 'Pacote de Treinamentos' }}
                                                            </p>
                                                            @if(!empty($pacote['descricao']))
                                                                <p class="text-[11px] text-slate-500">
                                                                    {{ $pacote['descricao'] }}
                                                                </p>
                                                            @endif
                                                            @if(!empty($pacoteCodigos))
                                                                <p class="text-[11px] text-slate-500 mt-1">
                                                                    Inclui: {{ implode(', ', $pacoteCodigos) }}
                                                                </p>
                                                            @endif
                                                            <p class="text-[11px] text-emerald-700 mt-1 font-semibold">
                                                                Valor do pacote: R$ {{ number_format((float) ($pacote['valor'] ?? 0), 2, ',', '.') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Avulsos --}}
                                <div id="listaTreinamentos" data-treinamento-mode="avulsos"
                                     class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @if(empty($treinamentosDisponiveis))
                                        <p class="text-[11px] text-slate-400 md:col-span-2">
                                            Nenhum treinamento disponível para seleção.
                                        </p>
                                    @else
                                        @foreach($treinamentosDisponiveis as $key => $label)
                                            @php
                                                $permitido = in_array($key, $treinamentosPermitidos, true);
                                                $selecionado = in_array($key, (array) $treinamentosSelecionados, true);
                                            @endphp
                                            <label class="inline-flex items-center gap-2 text-sm {{ $permitido ? 'text-slate-700' : 'text-slate-400' }}"
                                                   title="{{ $permitido ? '' : $treinamentoAviso }}">
                                                <input type="checkbox"
                                                       name="treinamentos[]"
                                                       value="{{ $key }}"
                                                       @checked($selecionado)
                                                       {{ $permitido ? '' : 'disabled' }}
                                                       class="rounded border-slate-300 text-sky-500 focus:ring-sky-400">
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach

                                        <p class="mt-1 text-[11px] text-slate-400 md:col-span-2">
                                            Você pode selecionar mais de um treinamento.
                                        </p>

                                        @if(empty($pacotesTreinamentos) && (!$temTreinamentosPermitidos || $temTreinamentosBloqueados))
                                            <p class="text-[11px] text-slate-400 md:col-span-2">
                                                {{ $treinamentoAviso }}
                                            </p>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Data e Local de Realização --}}
                        <div class="border-t border-slate-100 pt-4 mt-6 space-y-4">
                            <h2 class="text-sm font-semibold text-slate-800">
                                Data e Local de Realização
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">
                                        Data de Realização *
                                    </label>
                                    <input type="date"
                                           name="data_aso"
                                           value="{{ $dataAsoValue }}"
                                           class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3
                                              focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">
                                        Unidade *
                                    </label>
                                    <select name="unidade_id"
                                            class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3 bg-white
                                               focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                                        <option value="">Selecione a unidade</option>
                                        @foreach($unidades as $unidade)
                                            <option value="{{ $unidade->id }}"
                                                @selected((string) $unidadeSelecionada === (string) $unidade->id)>
                                                {{ $unidade->nome }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Botão final --}}
                        <div class="mt-4">
                            <button type="submit"
                                    id="btnSubmitAso"
                                    @disabled(!$temTiposAsoPermitidos || !$tipoAsoSelecionadoPermitido)
                                    class="w-full px-6 py-3 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium shadow-sm {{ (!$temTiposAsoPermitidos || !$tipoAsoSelecionadoPermitido) ? 'opacity-60 cursor-not-allowed hover:bg-sky-500' : '' }}">
                                {{ $isEdit ? 'Atualizar Tarefa ASO' : 'Criar Tarefa ASO' }}
                            </button>
                        </div>
                    </div>
                    {{-- ABA 2: ANEXOS --}}
                    <div id="tab-anexos" class="space-y-4 hidden">
                        <p class="text-xs text-slate-600">
                            Anexe aqui documentos relacionados ao ASO (PDF, DOC, DOCX).
                            Você pode arrastar e soltar ou clicar na área abaixo.
                        </p>

                        {{-- Dropzone --}}
                        <div id="dropzone-anexos"
                             class="flex flex-col items-center justify-center px-6 py-10 border-2 border-dashed rounded-2xl
                        border-slate-300 bg-slate-50 text-center cursor-pointer
                        hover:border-sky-400 hover:bg-sky-50 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M3 15.75V18a3 3 0 003 3h12a3 3 0 003-3v-2.25M16.5 9.75L12 5.25m0 0L7.5 9.75M12 5.25V15"/>
                            </svg>
                            <p class="text-sm text-slate-700">
                                Arraste arquivos aqui
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1">
                                ou clique para selecionar
                            </p>

                            <input id="input-anexos"
                                   type="file"
                                   name="anexos[]"
                                   multiple
                                   accept=".pdf,.doc,.docx"
                                   class="hidden">
                        </div>

                        {{-- Lista de arquivos selecionados --}}
                        <ul id="lista-anexos" class="mt-3 text-xs text-slate-600 space-y-1"></ul>
                        @if($isEdit)
                            <div class="mt-6">
                                <h3 class="text-sm font-semibold text-slate-800 mb-3">
                                    Anexos desta tarefa
                                </h3>

                                @if($anexos->isEmpty())
                                    <p class="text-xs text-slate-400">
                                        Nenhum anexo cadastrado ainda.
                                    </p>
                                @else
                                    <ul class="divide-y divide-slate-100 border border-slate-200 rounded-2xl overflow-hidden">
                                        @foreach($anexos as $anexo)
                                            @php
                                                $ext = strtolower(pathinfo($anexo->nome_original, PATHINFO_EXTENSION));

                                                $iconClasses = match($ext) {
                                                    'pdf'      => 'bg-red-100 text-red-600',
                                                    'doc', 'docx' => 'bg-blue-100 text-blue-600',
                                                    default    => 'bg-slate-100 text-slate-600',
                                                };

                                                $sizeKb = $anexo->tamanho
                                                    ? round($anexo->tamanho / 1024, 1)
                                                    : null;
                                            @endphp

                                            <li class="flex items-center justify-between px-4 py-3">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div
                                                        class="h-9 w-9 rounded-xl flex items-center justify-center text-[11px] font-semibold {{ $iconClasses }}">
                                                        {{ strtoupper($ext ?: 'ARQ') }}
                                                    </div>

                                                    <div class="min-w-0">
                                                        <p class="text-sm text-slate-800 truncate max-w-xs">
                                                            {{ $anexo->nome_original }}
                                                        </p>
                                                        <p class="text-[11px] text-slate-400">
                                                            @if($sizeKb)
                                                                {{ number_format($sizeKb, 1, ',', '.') }} KB ·
                                                            @endif
                                                            Enviado em {{ $anexo->created_at?->format('d/m/Y H:i') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('operacional.anexos.view', $anexo) }}"
                                                       target="_blank"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium
                                      border border-slate-200 text-slate-700 hover:bg-slate-50">
                                                        Ver
                                                    </a>

                                                    <a href="{{ route('operacional.anexos.download', $anexo) }}"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium
                                      bg-sky-500 text-white hover:bg-sky-600">
                                                        Download
                                                    </a>

                                                    {{-- Botão lixeira (excluir) --}}
                                                    <button type="button"
                                                            class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-red-100
               text-red-500 hover:bg-red-50 text-xs"
                                                            title="Excluir anexo"
                                                            data-delete-anexo="{{ route('operacional.anexos.destroy', $anexo) }}">
                                                        {{-- Ícone trash em SVG (Heroicons) --}}
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                             viewBox="0 0 24 24"
                                                             fill="none"
                                                             stroke="currentColor"
                                                             stroke-width="1.7"
                                                             class="w-4 h-4">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                  d="M9.75 9.75v6.75M14.25 9.75v6.75M4.5 6.75h15M18.75 6.75
                 l-.861 12.067A2.25 2.25 0 0 1 15.648 21H8.352a2.25 2.25 0 0 1-2.241-2.183L5.25 6.75M9 6.75V4.5
                 A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5v2.25"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif

                        {{-- Botão de salvar reaproveita o mesmo da aba Dados --}}
                        <div class="mt-4">
                            <button type="submit"
                                    @disabled(!$temTiposAsoPermitidos || !$tipoAsoSelecionadoPermitido)
                                    class="w-full px-6 py-3 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium shadow-sm {{ (!$temTiposAsoPermitidos || !$tipoAsoSelecionadoPermitido) ? 'opacity-60 cursor-not-allowed hover:bg-sky-500' : '' }}">
                                {{ $isEdit ? 'Atualizar Tarefa ASO' : 'Criar Tarefa ASO' }}
                            </button>
                        </div>
                    </div>
                </form>
                @if($isEdit)
                    <form id="form-delete-anexo" method="POST" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        {{-- Máscara + validação de CPF --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var cpfInput = document.querySelector('input[name="cpf"]');
                if (!cpfInput) return;

                cpfInput.addEventListener('input', function () {
                    var v = cpfInput.value.replace(/\D/g, '');
                    v = v.slice(0, 11);

                    if (v.length > 9) {
                        cpfInput.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, "$1.$2.$3-$4");
                    } else if (v.length > 6) {
                        cpfInput.value = v.replace(/(\d{3})(\d{3})(\d{1,3})/, "$1.$2.$3");
                    } else if (v.length > 3) {
                        cpfInput.value = v.replace(/(\d{3})(\d{1,3})/, "$1.$2");
                    } else {
                        cpfInput.value = v;
                    }
                });

                cpfInput.addEventListener('blur', function () {
                    var cpfLimpo = cpfInput.value.replace(/\D/g, '');

                    if (cpfLimpo === '') {
                        limparErroCPF(cpfInput);
                        return;
                    }

                    if (!cpfValido(cpfLimpo)) {
                        mostrarErroCPF(cpfInput, 'CPF inválido');
                    } else {
                        limparErroCPF(cpfInput);
                    }
                });
            });

            function cpfValido(cpf) {
                if (!cpf || cpf.length !== 11) return false;
                if (/^(\d)\1{10}$/.test(cpf)) return false;

                var soma = 0;
                for (var i = 0; i < 9; i++) {
                    soma += parseInt(cpf.charAt(i)) * (10 - i);
                }
                var resto = (soma * 10) % 11;
                if (resto === 10 || resto === 11) resto = 0;
                if (resto !== parseInt(cpf.charAt(9))) return false;

                soma = 0;
                for (var j = 0; j < 10; j++) {
                    soma += parseInt(cpf.charAt(j)) * (11 - j);
                }
                resto = (soma * 10) % 11;
                if (resto === 10 || resto === 11) resto = 0;
                if (resto !== parseInt(cpf.charAt(10))) return false;

                return true;
            }

            function mostrarErroCPF(input, mensagem) {
                limparErroCPF(input);

                input.style.borderColor = '#dc2626';
                var p = document.createElement('p');
                p.className = 'cpf-error';
                p.style.color = '#dc2626';
                p.style.fontSize = '12px';
                p.style.marginTop = '4px';
                p.textContent = mensagem;

                if (input.parentNode) {
                    input.parentNode.appendChild(p);
                }
            }

            function limparErroCPF(input) {
                input.style.borderColor = '';

                if (!input.parentNode) return;
                var erro = input.parentNode.querySelector('.cpf-error');
                if (erro) erro.remove();
            }
        </script>

        {{-- Habilita / desabilita campos de funcionário conforme select --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const selectFuncionario = document.getElementById('funcionario_id');
                const selectTipoAso = document.querySelector('select[name="tipo_aso"]');
                const selectFuncao = document.getElementById('campo_funcao');
                const resumoEl = document.getElementById('asoResumo');
                const btnSubmitAso = document.getElementById('btnSubmitAso');

                if (!selectFuncionario) return;

                const campos = [
                    document.getElementById('campo_nome'),
                    document.getElementById('campo_cpf'),
                    document.getElementById('campo_rg'),
                    document.getElementById('campo_data_nascimento'),
                ];

                function toggleCamposFuncionario() {
                    const temFuncionario = selectFuncionario.value !== '';
                    const tipoAso = selectTipoAso ? selectTipoAso.value : '';

                    // Campos de cadastro do funcionário (sempre travam quando tem funcionário)
                    campos.forEach(function (campo) {
                        if (!campo) return;

                        campo.disabled = temFuncionario;

                        campo.classList.toggle('bg-slate-100', temFuncionario);
                        campo.classList.toggle('cursor-not-allowed', temFuncionario);
                        campo.classList.toggle('bg-white', !temFuncionario);
                    });

                    // Regra específica para FUNÇÃO:
                    // - Se tem funcionário selecionado E NÃO for mudança de função => trava o select
                    // - Se for mudança de função (mudanca_funcao) => libera o select mesmo com funcionário
                    if (selectFuncao) {
                        const deveDesabilitarFuncao = temFuncionario && tipoAso !== 'mudanca_funcao';

                        selectFuncao.disabled = deveDesabilitarFuncao;

                        selectFuncao.classList.toggle('bg-slate-100', deveDesabilitarFuncao);
                        selectFuncao.classList.toggle('cursor-not-allowed', deveDesabilitarFuncao);
                        selectFuncao.classList.toggle('bg-white', !deveDesabilitarFuncao);
                    }
                }

                // Inicializa estado na carga
                toggleCamposFuncionario();

                // Reavalia quando mudar colaborador ou tipo de ASO
                selectFuncionario.addEventListener('change', toggleCamposFuncionario);
                if (selectTipoAso) {
                    selectTipoAso.addEventListener('change', toggleCamposFuncionario);
                }

                if (selectFuncionario) {
                    const funcionarioUrlTemplate = selectFuncionario.dataset.funcionarioUrl || '';
                    const campoNome = document.getElementById('campo_nome');
                    const campoCpf = document.getElementById('campo_cpf');
                    const campoRg = document.getElementById('campo_rg');
                    const campoDataNascimento = document.getElementById('campo_data_nascimento');
                    const campoCelular = document.querySelector('input[name="celular"]');

                    const preencherCamposFuncionario = (dados) => {
                        if (!dados) return;
                        if (campoNome) campoNome.value = dados.nome || '';
                        if (campoCpf) campoCpf.value = dados.cpf || '';
                        if (campoRg) campoRg.value = dados.rg || '';
                        if (campoDataNascimento) campoDataNascimento.value = dados.data_nascimento || '';
                        if (campoCelular) campoCelular.value = dados.celular || '';
                        if (selectFuncao && dados.funcao_id) {
                            selectFuncao.value = String(dados.funcao_id);
                            selectFuncao.dispatchEvent(new Event('change'));
                        }
                    };

                    selectFuncionario.addEventListener('change', function () {
                        const funcionarioId = selectFuncionario.value;
                        if (!funcionarioId || !funcionarioUrlTemplate) {
                            return;
                        }
                        const url = funcionarioUrlTemplate.replace('FUNCIONARIO_ID', funcionarioId);
                        fetch(url, { headers: { 'Accept': 'application/json' } })
                            .then((response) => response.json().then((json) => ({ ok: response.ok, json })))
                            .then(({ ok, json }) => {
                                if (!ok || !json.ok) {
                                    return;
                                }
                                preencherCamposFuncionario(json.funcionario || {});
                            })
                            .catch(() => {});
                    });
                }

                if (resumoEl) {
                    const resumoUrl = resumoEl.dataset.asoResumoUrl;
                    const statusEl = document.getElementById('asoResumoStatus');
                    const toggleEl = document.getElementById('asoResumoToggle');
                    const countEl = document.getElementById('asoResumoCount');
                    const badgesEl = document.getElementById('asoResumoBadges');
                    const rateadoEl = document.getElementById('asoResumoRateado');
                    const totalEl = document.getElementById('asoResumoTotal');
                    const totalValorEl = document.getElementById('asoResumoTotalValor');
                    let controller = null;
                    let resumoValido = true;

                    const setSubmitBlocked = (blocked) => {
                        resumoValido = !blocked;
                        if (!btnSubmitAso) {
                            return;
                        }
                        const baseDisabled = btnSubmitAso.hasAttribute('data-base-disabled');
                        if (blocked || baseDisabled) {
                            btnSubmitAso.setAttribute('disabled', 'disabled');
                            btnSubmitAso.classList.add('opacity-60', 'cursor-not-allowed');
                            btnSubmitAso.classList.remove('hover:bg-sky-600');
                        } else {
                            btnSubmitAso.removeAttribute('disabled');
                            btnSubmitAso.classList.remove('opacity-60', 'cursor-not-allowed');
                            btnSubmitAso.classList.add('hover:bg-sky-600');
                        }
                    };

                    if (btnSubmitAso && btnSubmitAso.hasAttribute('disabled')) {
                        btnSubmitAso.setAttribute('data-base-disabled', '1');
                    }

                    const formatBRL = (value) => {
                        if (value === null || value === undefined || isNaN(value)) {
                            return 'R$ 0,00';
                        }
                        return value.toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL',
                            minimumFractionDigits: 2,
                        });
                    };

                    const setStatus = (text, block = false) => {
                        if (statusEl) {
                            statusEl.textContent = text;
                            statusEl.classList.remove('hidden');
                        }
                        if (badgesEl) {
                            badgesEl.classList.add('hidden');
                            badgesEl.innerHTML = '';
                        }
                        if (rateadoEl) {
                            rateadoEl.classList.add('hidden');
                        }
                        if (toggleEl) {
                            toggleEl.classList.add('hidden');
                        }
                        if (countEl) {
                            countEl.classList.add('hidden');
                            countEl.textContent = '';
                        }
                        if (totalEl) {
                            totalEl.classList.add('hidden');
                        }
                        setSubmitBlocked(block);
                    };

                    const renderResumo = (data) => {
                        if (!badgesEl || !totalEl || !totalValorEl || !toggleEl || !countEl) {
                            return;
                        }
                        const exames = Array.isArray(data.exames) ? data.exames : [];
                        badgesEl.innerHTML = '';
                        if (exames.length === 0) {
                            setStatus('Nenhum exame configurado para esta combinação. Entre em contato com o comercial.', true);
                            return;
                        }

                        exames.forEach((exame) => {
                            const badge = document.createElement('span');
                            badge.className = 'inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] text-slate-700';
                            const titulo = document.createElement('span');
                            titulo.textContent = exame.titulo || 'Exame';
                            const preco = document.createElement('span');
                            preco.className = 'text-slate-400';
                            preco.textContent = formatBRL(Number(exame.preco || 0));
                            badge.appendChild(titulo);
                            badge.appendChild(preco);
                            badgesEl.appendChild(badge);
                        });

                        if (statusEl) {
                            statusEl.classList.add('hidden');
                        }
                        badgesEl.classList.add('hidden');
                        toggleEl.classList.remove('hidden');
                        countEl.classList.remove('hidden');
                        countEl.textContent = `${exames.length} exame(s)`;
                        totalEl.classList.remove('hidden');
                        totalValorEl.textContent = formatBRL(Number(data.total || 0));
                        if (rateadoEl && data.rateado) {
                            rateadoEl.classList.remove('hidden');
                        }
                        setSubmitBlocked(false);
                    };

                    const carregarResumo = () => {
                        if (!resumoUrl) {
                            return;
                        }

                        const tipoAso = selectTipoAso ? selectTipoAso.value : '';
                        const funcaoId = selectFuncao ? selectFuncao.value : '';

                        if (!tipoAso || !funcaoId) {
                            setStatus('Selecione o tipo de ASO e a função para visualizar os exames.');
                            return;
                        }

                        setStatus('Carregando exames...');

                        if (controller) {
                            controller.abort();
                        }
                        controller = new AbortController();

                        const params = new URLSearchParams({
                            tipo_aso: tipoAso,
                            funcao_id: funcaoId,
                        });

                        fetch(`${resumoUrl}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                            },
                            signal: controller.signal,
                        })
                            .then((response) => response.json().then((json) => ({ ok: response.ok, json })))
                            .then(({ ok, json }) => {
                                if (!ok || !json.ok) {
                                    const msg = json && json.message ? json.message : 'Não foi possível carregar o resumo.';
                                    setStatus(msg, true);
                                    return;
                                }
                                renderResumo(json);
                            })
                            .catch((err) => {
                                if (err && err.name === 'AbortError') {
                                    return;
                                }
                                setStatus('Não foi possível carregar o resumo.', true);
                            });
                    };

                    if (selectTipoAso) {
                        selectTipoAso.addEventListener('change', carregarResumo);
                    }
                    if (selectFuncao) {
                        selectFuncao.addEventListener('change', carregarResumo);
                    }

                    if (toggleEl && badgesEl) {
                        toggleEl.addEventListener('click', function () {
                            const isHidden = badgesEl.classList.contains('hidden');
                            badgesEl.classList.toggle('hidden', !isHidden);
                            toggleEl.textContent = isHidden ? 'Ocultar exames' : 'Ver exames';
                        });
                    }

                    carregarResumo();
                }
            });
        </script>

        {{-- Toggle de treinamentos (Sim / Não) --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const campo = document.getElementById('vai_fazer_treinamento');
                const btnSim = document.getElementById('btn_treina_sim');
                const btnNao = document.getElementById('btn_treina_nao');
                const lista = document.getElementById('listaTreinamentos');
                const pacotes = document.getElementById('pacotesTreinamentosAso');
                const wrapTreinos = document.getElementById('treinamentosWrap');
                const pacotesInputs = document.querySelectorAll('[data-treinamento-pacote]');

                if (!campo || !btnSim || !btnNao || !lista) return;

                function limparPacotesSelecionados() {
                    if (!pacotesInputs.length) return;
                    pacotesInputs.forEach(input => {
                        input.checked = false;
                        try {
                            const codigos = JSON.parse(input.dataset.codigos || '[]');
                            if (Array.isArray(codigos)) {
                                codigos.forEach(codigo => {
                                    const checkbox = document.querySelector(`input[name="treinamentos[]"][value="${codigo}"]`);
                                    if (checkbox && !checkbox.disabled) {
                                        checkbox.checked = false;
                                    }
                                });
                            }
                        } catch (e) {
                            // ignora erro de parse
                        }
                    });
                }

                function atualizarTreinamento() {
                    if (btnSim.disabled) {
                        campo.value = '0';
                    }

                    const ativo = campo.value === '1';

                    if (ativo) {
                        if (wrapTreinos) wrapTreinos.classList.remove('hidden');
                    } else {
                        if (wrapTreinos) wrapTreinos.classList.add('hidden');
                        limparPacotesSelecionados();
                    }

                    if (ativo) {
                        btnSim.classList.add('bg-slate-900', 'text-white', 'border-slate-900');
                        btnSim.classList.remove('bg-white', 'text-slate-700', 'border-slate-300');

                        btnNao.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
                        btnNao.classList.remove('bg-slate-900', 'text-white', 'border-slate-900');
                    } else {
                        btnNao.classList.add('bg-slate-900', 'text-white', 'border-slate-900');
                        btnNao.classList.remove('bg-white', 'text-slate-700', 'border-slate-300');

                        btnSim.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
                        btnSim.classList.remove('bg-slate-900', 'text-white', 'border-slate-900');
                    }
                }

                btnSim.addEventListener('click', function () {
                    if (btnSim.disabled) {
                        return;
                    }
                    campo.value = '1';
                    atualizarTreinamento();
                });

                btnNao.addEventListener('click', function () {
                    campo.value = '0';
                    atualizarTreinamento();
                });

                atualizarTreinamento();
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const btnPacotes = document.getElementById('btnModoPacotes');
                const btnAvulsos = document.getElementById('btnModoAvulsos');
                const modoInput = document.getElementById('treinamentoModoAso');
                const blocoPacotes = document.querySelector('[data-treinamento-mode="pacotes"]');
                const blocoAvulsos = document.querySelector('[data-treinamento-mode="avulsos"]');
                const pacotes = document.querySelectorAll('[data-treinamento-pacote]');

                function setModoTreinamento(modo) {
                    if (modoInput) modoInput.value = modo;
                    if (blocoPacotes) blocoPacotes.classList.toggle('hidden', modo !== 'pacotes');
                    if (blocoAvulsos) blocoAvulsos.classList.toggle('hidden', modo !== 'avulsos');

                    if (btnPacotes && btnAvulsos) {
                        if (modo === 'pacotes') {
                            btnPacotes.classList.add('bg-slate-900', 'text-white', 'border-slate-900');
                            btnPacotes.classList.remove('bg-white', 'text-slate-700', 'border-slate-300');
                            btnAvulsos.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
                            btnAvulsos.classList.remove('bg-slate-900', 'text-white', 'border-slate-900');
                        } else {
                            btnAvulsos.classList.add('bg-slate-900', 'text-white', 'border-slate-900');
                            btnAvulsos.classList.remove('bg-white', 'text-slate-700', 'border-slate-300');
                            btnPacotes.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
                            btnPacotes.classList.remove('bg-slate-900', 'text-white', 'border-slate-900');
                        }
                    }
                }

                if (btnPacotes) btnPacotes.addEventListener('click', () => setModoTreinamento('pacotes'));
                if (btnAvulsos) btnAvulsos.addEventListener('click', () => setModoTreinamento('avulsos'));

                const initialMode = modoInput?.value || 'avulsos';
                setModoTreinamento(initialMode);

                if (!pacotes.length) return;

                function toggleCodigos(codigos, checked) {
                    if (!Array.isArray(codigos)) return;
                    codigos.forEach(codigo => {
                        const input = document.querySelector(`input[name="treinamentos[]"][value="${codigo}"]`);
                        if (input && !input.disabled) {
                            input.checked = checked;
                        }
                    });
                }

                function limparPacotes() {
                    pacotes.forEach(pacote => {
                        pacote.checked = false;
                    });
                    const todosCodigos = [];
                    pacotes.forEach(pacote => {
                        try {
                            const codigos = JSON.parse(pacote.dataset.codigos || '[]');
                            if (Array.isArray(codigos)) {
                                codigos.forEach(codigo => todosCodigos.push(codigo));
                            }
                        } catch (e) {
                            // ignora erro de parse
                        }
                    });
                    toggleCodigos(Array.from(new Set(todosCodigos)), false);
                }

                if (btnAvulsos) {
                    btnAvulsos.addEventListener('click', () => limparPacotes());
                }

                pacotes.forEach(pacote => {
                    pacote.addEventListener('change', () => {
                        if (!pacote.checked) {
                            return;
                        }
                        limparPacotes();
                        pacote.checked = true;
                        let codigos = [];
                        try {
                            codigos = JSON.parse(pacote.dataset.codigos || '[]');
                        } catch (e) {
                            codigos = [];
                        }
                        toggleCodigos(codigos, pacote.checked);
                    });
                });
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // ====== TABS ======
                const tabButtons = document.querySelectorAll('.tab-btn');
                const tabDados = document.getElementById('tab-dados');
                const tabAnexos = document.getElementById('tab-anexos');

                tabButtons.forEach(btn => {
                    btn.addEventListener('click', function () {
                        const tab = this.dataset.tab;

                        // alterna conteúdo
                        if (tab === 'dados') {
                            tabDados.classList.remove('hidden');
                            tabAnexos.classList.add('hidden');
                        } else {
                            tabAnexos.classList.remove('hidden');
                            tabDados.classList.add('hidden');
                        }

                        // estilo ativo/inativo
                        tabButtons.forEach(b => {
                            b.classList.remove('border-b-2', 'border-sky-500', 'text-sky-600', 'font-semibold');
                            b.classList.add('text-slate-500');
                        });

                        this.classList.remove('text-slate-500');
                        this.classList.add('border-b-2', 'border-sky-500', 'text-sky-600', 'font-semibold');
                    });
                });

                // ====== DROPZONE ======
                const dropzone = document.getElementById('dropzone-anexos');
                const inputFiles = document.getElementById('input-anexos');
                const lista = document.getElementById('lista-anexos');

                if (!dropzone || !inputFiles || !lista) return;

                function atualizarLista() {
                    lista.innerHTML = '';
                    if (!inputFiles.files.length) {
                        const li = document.createElement('li');
                        li.textContent = 'Nenhum arquivo selecionado.';
                        lista.appendChild(li);
                        return;
                    }

                    Array.from(inputFiles.files).forEach(file => {
                        const li = document.createElement('li');
                        li.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                        lista.appendChild(li);
                    });
                }

                // abre seletor ao clicar na área
                dropzone.addEventListener('click', function () {
                    inputFiles.click();
                });

                // arquivos escolhidos pelo seletor
                inputFiles.addEventListener('change', function () {
                    atualizarLista();
                });

                // drag over
                dropzone.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    dropzone.classList.add('border-sky-400', 'bg-sky-50');
                });

                dropzone.addEventListener('dragleave', function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('border-sky-400', 'bg-sky-50');
                });

                dropzone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('border-sky-400', 'bg-sky-50');

                    if (!e.dataTransfer.files.length) return;

                    // joga os arquivos arrastados direto pro input
                    inputFiles.files = e.dataTransfer.files;
                    atualizarLista();
                });

                // inicia lista vazia
                atualizarLista();
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const deleteButtons = document.querySelectorAll('[data-delete-anexo]');
                const formDelete = document.getElementById('form-delete-anexo');

                if (!formDelete || !deleteButtons.length) return;

                deleteButtons.forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const url = this.dataset.deleteAnexo;
                        if (!url) return;

                        const ok = await window.uiConfirm('Deseja realmente excluir este anexo?');
                        if (!ok) return;

                        formDelete.action = url;
                        formDelete.submit();
                    });
                });
            });
        </script>
    @endpush
@endsection
