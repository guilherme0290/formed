@extends('layouts.operacional')

@php
    /** @var \App\Models\Tarefa|null $tarefa */
    $isEdit = isset($tarefa);

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
    <div class="max-w-4xl mx-auto px-6 py-8">

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
            <div class="px-6 py-4 bg-gradient-to-r from-[#0A3A80] to-[#1E68D9]">
                <h1 class="text-lg md:text-xl font-semibold text-white mb-1">
                    Agendar ASO
                </h1>
                <p class="text-xs md:text-sm text-blue-100">
                    {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                </p>
            </div>

            {{-- Conteúdo / Form --}}
            <div class="px-6 py-6">
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

                    <form
                        method="POST"
                        action="{{ $isEdit
                            ? route('operacional.kanban.aso.update', $tarefa)
                            : route('operacional.kanban.aso.store', $cliente) }}"
                    >
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    {{-- Tipo de ASO --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-medium text-slate-600">
                            Tipo de ASO *
                        </label>

                        <select name="tipo_aso"
                                class="w-full rounded-xl border border-slate-200 text-sm py-2.5 px-3 bg-white
                                       focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400">
                            <option value="">Selecione o tipo de ASO</option>
                            @foreach($tiposAso as $key => $label)
                                <option value="{{ $key }}" @selected($tipoAsoSelected === $key)>
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
                                Se for um colaborador novo, deixe o campo acima em branco e preencha os dados abaixo.
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
                                :funcoes="$funcoes"
                                :selected="old('funcao_id', $funcionario->funcao_id ?? null)"
                            />
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
                                        {{ $vaiFazerTreinamento ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300' }}">
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

                        {{-- Lista de treinamentos --}}
                        <div id="listaTreinamentos"
                             class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2 {{ $vaiFazerTreinamento ? '' : 'hidden' }}">
                            @foreach($treinamentosDisponiveis as $key => $label)
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox"
                                           name="treinamentos[]"
                                           value="{{ $key }}"
                                           @checked(in_array($key, (array) $treinamentosSelecionados))
                                           class="rounded border-slate-300 text-sky-500 focus:ring-sky-400">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach

                            <p class="mt-1 text-[11px] text-slate-400 md:col-span-2">
                                Você pode selecionar mais de um treinamento.
                            </p>
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
                                class="w-full px-6 py-3 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium shadow-sm">
                            {{ $isEdit ? 'Atualizar Tarefa ASO' : 'Criar Tarefa ASO' }}
                        </button>
                    </div>
                </form>
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
                const selectTipoAso     = document.querySelector('select[name="tipo_aso"]');
                const selectFuncao      = document.getElementById('campo_funcao');

                if (!selectFuncionario) return;

                const campos = [
                    document.getElementById('campo_nome'),
                    document.getElementById('campo_cpf'),
                    document.getElementById('campo_rg'),
                    document.getElementById('campo_data_nascimento'),
                ];

                function toggleCamposFuncionario() {
                    const temFuncionario = selectFuncionario.value !== '';
                    const tipoAso        = selectTipoAso ? selectTipoAso.value : '';

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
            });
        </script>

        {{-- Toggle de treinamentos (Sim / Não) --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const campo = document.getElementById('vai_fazer_treinamento');
                const btnSim = document.getElementById('btn_treina_sim');
                const btnNao = document.getElementById('btn_treina_nao');
                const lista = document.getElementById('listaTreinamentos');

                if (!campo || !btnSim || !btnNao || !lista) return;

                function atualizarTreinamento() {
                    const ativo = campo.value === '1';

                    if (ativo) {
                        lista.classList.remove('hidden');
                    } else {
                        lista.classList.add('hidden');
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
    @endpush
@endsection
