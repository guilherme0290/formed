@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')


@section('pageTitle', 'Treinamentos de NRs')

@section('content')
    @php
        $origem = request()->query('origem');
        $allowCreateFuncao = $origem === 'cliente';
    @endphp

    <div class="w-full px-2 sm:px-3 md:px-4 xl:px-5 py-4 md:py-6">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ $origem === 'cliente'
                    ? route('cliente.dashboard')
                    : route('operacional.kanban.servicos', $cliente) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                ← Voltar
            </a>
        </div>

        <div class="w-full bg-white rounded-2xl shadow-lg overflow-hidden">
            {{-- Cabeçalho --}}
            <div class="px-4 sm:px-5 md:px-6 py-4 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white">
                <h1 class="text-lg font-semibold">
                    Treinamentos de NRs {{ !empty($isEdit) ? '(Editar)' : '' }}
                </h1>
                <p class="text-xs text-white/80 mt-1">
                    {{ $cliente->razao_social }}
                </p>
            </div>

            <form method="POST"
                  action="{{ !empty($isEdit) && $tarefa
                        ? route('operacional.treinamentos-nr.update', ['tarefa' => $tarefa, 'origem' => $origem])
                        : route('operacional.treinamentos-nr.store', ['cliente' => $cliente, 'origem' => $origem]) }}"
                  class="px-4 sm:px-5 md:px-6 py-5 md:py-6 space-y-6">
                @csrf
                @if(!empty($isEdit) && $tarefa)
                    @method('PUT')
                @endif
                <input type="hidden" name="origem" value="{{ $origem }}">

                {{-- 1. Selecione os participantes --}}
                <section class="space-y-3">
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/40 shadow-inner overflow-hidden">
                    <div class="px-4 py-3 border-b border-indigo-200 bg-indigo-100/60 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-indigo-900">1. Selecione os Participantes</h2>

                        <button type="button"
                                id="btn-novo-funcionario-toggle"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600">
                            + Cadastrar Novo
                        </button>
                    </div>
                    <div class="p-4 md:p-5 space-y-4">

                    {{-- Card de novo funcionário --}}
                    <div id="card-novo-funcionario"
                         class="hidden rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-semibold text-emerald-800">
                                Cadastrar Novo Colaborador
                            </h3>
                            <button type="button"
                                    id="btn-novo-funcionario-close"
                                    class="text-xs text-emerald-900/70 hover:text-emerald-900">
                                ✕
                            </button>
                        </div>

                        <div class="grid grid-cols-12 gap-3 items-end">
                            <div class="col-span-6">
                                <label class="block text-xs font-medium text-slate-600 mb-1">
                                    Nome Completo
                                </label>
                                <input type="text" id="nf-nome"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                       placeholder="Nome completo">
                            </div>

                            <div class="col-span-3">
                                <label class="block text-xs font-medium text-slate-600 mb-1">
                                    CPF
                                </label>
                                <input type="text" id="nf-cpf" name="cpf"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                       placeholder="000.000.000-00">
                            </div>

                            <div class="col-span-3">
                                <label class="block text-xs font-medium text-slate-600 mb-1">
                                    Data de Nascimento
                                </label>
                                <div class="relative">
                                    <input type="text"
                                           id="nf-nascimento-br"
                                           inputmode="numeric"
                                           placeholder="dd/mm/aaaa"
                                           class="w-full rounded-lg border-slate-200 text-sm pl-3 pr-10 py-2 js-date-text"
                                           data-date-target="nf-nascimento">
                                    <button type="button"
                                            class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                            data-date-target="nf-nascimento"
                                            aria-label="Abrir calendário">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
                                        </svg>
                                    </button>
                                    <input type="date"
                                           id="nf-nascimento"
                                           class="absolute right-0 top-0 h-full w-10 opacity-0 pointer-events-none js-date-hidden">
                                </div>
                            </div>

                            <div class="col-span-6">
                                <x-funcoes.select-with-create
                                    name="nf_funcao_id"
                                    field-id="nf_funcao_id"
                                    label="Função"
                                    help-text="Funções listadas por GHE, pré-configuradas pelo vendedor/comercial."
                                    :allowCreate="$allowCreateFuncao"
                                    :funcoes="$funcoes"
                                    :selected="null"
                                />
                            </div>

                            <div class="col-span-12">
                                <button type="button"
                                        id="btn-novo-funcionario-salvar"
                                        class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600">
                                    Adicionar à Lista
                                </button>
                            </div>
                        </div>

                        <p id="nf-erro"
                           class="mt-2 text-[11px] text-red-700 hidden">
                        </p>

                    </div>

                    {{-- Lista de funcionários --}}
                    @php
                        $selecionados = old('funcionarios', $selecionados ?? []);
                        $funcoesFiltroParticipantes = collect($funcionarios ?? [])
                            ->map(fn ($f) => trim((string) optional($f->funcao)->nome))
                            ->filter()
                            ->unique()
                            ->sort()
                            ->values();
                    @endphp

                    <div class="rounded-xl border border-indigo-200/80 bg-white/95 p-3 md:p-4 shadow-sm">
                    <div class="mb-3 grid grid-cols-1 md:grid-cols-[1fr,auto] gap-2 items-end">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Filtrar por fun&ccedil;&atilde;o</label>
                            <select id="filtro-funcao-participante"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                <option value="">Selecione a fun&ccedil;&atilde;o...</option>
                                @foreach($funcoesFiltroParticipantes as $funcaoFiltro)
                                    <option value="{{ $funcaoFiltro }}">{{ $funcaoFiltro }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-slate-500">Filtre os participantes pela fun&ccedil;&atilde;o.</p>
                        </div>
                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                            <input type="checkbox"
                                   id="check-selecionar-todos-participantes"
                                   class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                            Selecionar todos
                        </label>
                    </div>
                    <div id="lista-funcionarios"
                         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto pr-1">
                        @foreach($funcionarios as $func)
                            @php
                                $funcaoNome = trim((string) optional($func->funcao)->nome);
                            @endphp
                            <label class="block border border-slate-200 rounded-xl px-3 py-3 text-xs cursor-pointer bg-white hover:bg-indigo-50/40"
                                   data-funcao="{{ $funcaoNome }}">
                                <div class="flex items-start gap-2">
                                    <input type="checkbox"
                                           name="funcionarios[]"
                                           value="{{ $func->id }}"
                                           data-funcao="{{ $funcaoNome }}"
                                           data-nome="{{ $func->nome }}"
                                           class="mt-1 h-3 w-3 text-indigo-600 border-slate-300 rounded"
                                        @checked(in_array($func->id, $selecionados))>
                                    <div>
                                        <p class="font-semibold text-slate-800 text-sm">
                                            {{ $func->nome }}
                                        </p>
                                        <p class="text-[11px] text-slate-500">
                                            {{ optional($func->funcao)->nome ?? 'Função não informada' }}
                                        </p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            CPF: {{ $func->cpf }}
                                        </p>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    </div>

                    <div class="rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-white px-4 py-3 shadow-sm">
                        <p class="text-[11px] uppercase tracking-wide text-indigo-700 font-semibold">Resumo da seleção</p>
                        <div class="mt-1 flex flex-wrap items-center gap-3">
                            <span class="inline-flex items-center gap-2 rounded-xl bg-white border border-indigo-100 px-3 py-1.5 text-sm text-slate-700">
                                <span id="contador-selecionados-badge" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[11px] font-bold text-white">0</span>
                                participantes selecionados
                            </span>
                            <span id="contador-selecionados" class="hidden"></span>
                        </div>
                        <div id="resumo-funcoes-cards" class="mt-3 space-y-3"></div>
                    </div>
                    @error('funcionarios')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                    </div>
                    </div>
                </section>

                {{-- 2. Selecione os Treinamentos --}}
                @php
                    $treinamentosData = $detalhes->treinamentos ?? null;
                    $treinamentoModo = old('treinamento_modo', data_get($treinamentosData, 'modo', 'avulso'));
                    $treinamentosSelecionados = old(
                        'treinamentos',
                        data_get($treinamentosData, 'codigos', is_array($treinamentosData) ? $treinamentosData : [])
                    );
                    $pacoteSelecionado = old('pacote_id', data_get($treinamentosData, 'pacote.contrato_item_id'));
                    $treinamentosFinalizados = $treinamentosFinalizados ?? [];
                    $pacotesTreinamentos = $pacotesTreinamentos ?? [];
                @endphp

                <section class="space-y-3 pt-4 border-t border-slate-100 mt-4">
                    <h2 class="text-sm font-semibold text-slate-800">2. Selecione os Treinamentos</h2>

                    <div class="flex flex-wrap items-center gap-4 text-xs">
                        <label class="inline-flex items-center gap-2 font-semibold text-slate-700">
                            <input type="radio"
                                   name="treinamento_modo"
                                   value="avulso"
                                   class="h-4 w-4 text-indigo-600"
                                   @checked($treinamentoModo === 'avulso')>
                            Avulso (NRs individuais)
                        </label>
                        <label class="inline-flex items-center gap-2 font-semibold text-slate-700">
                            <input type="radio"
                                   name="treinamento_modo"
                                   value="pacote"
                                   class="h-4 w-4 text-indigo-600"
                                   @checked($treinamentoModo === 'pacote')>
                            Pacote de Treinamentos
                        </label>
                    </div>

                    <div id="pacotesTreinamentosWrap" class="{{ $treinamentoModo === 'pacote' ? '' : 'hidden' }}">
                        @if(empty($pacotesTreinamentos))
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-800">
                                Nenhum pacote de treinamentos contratado para este cliente.
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($pacotesTreinamentos as $pacote)
                                    @php
                                        $pacoteId = (int) ($pacote['contrato_item_id'] ?? 0);
                                        $pacoteCodigos = $pacote['codigos'] ?? [];
                                    @endphp
                                    <label class="block border rounded-xl px-3 py-3 text-xs cursor-pointer bg-slate-50 hover:bg-slate-100">
                                        <div class="flex items-start gap-2">
                                            <input type="radio"
                                                   name="pacote_id"
                                                   value="{{ $pacoteId }}"
                                                   class="mt-1 h-3 w-3 text-indigo-600 border-slate-300"
                                                   @checked((string) $pacoteSelecionado === (string) $pacoteId)>
                                            <div>
                                                <p class="font-semibold text-slate-800 text-sm">
                                                    {{ $pacote['nome'] ?? 'Pacote de Treinamentos' }}
                                                </p>
                                                <p class="text-[11px] text-slate-500">
                                                    {{ $pacote['descricao'] ?? '' }}
                                                </p>
                                                @if(!empty($pacoteCodigos))
                                                    <p class="text-[11px] text-slate-400 mt-1">
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
                        @endif
                    </div>

                    <div id="treinamentosAvulsosWrap" class="{{ $treinamentoModo === 'avulso' ? '' : 'hidden' }}">
                    @if($treinamentosDisponiveis->isEmpty())
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-800">
                            Não há treinamentos contratados para este cliente.
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto pr-1">
                            @foreach($treinamentosDisponiveis as $treinamento)
                                @php
                                    $codigoTreinamento = (string) $treinamento->codigo;
                                    $isSelecionado = in_array($codigoTreinamento, $treinamentosSelecionados, true);
                                @endphp
                                <label class="block border rounded-xl px-3 py-3 text-xs cursor-pointer bg-slate-50 hover:bg-slate-100">
                                    <div class="flex items-start gap-2">
                                        <input type="checkbox"
                                               name="treinamentos[]"
                                               value="{{ $treinamento->codigo }}"
                                               class="mt-1 h-3 w-3 text-indigo-600 border-slate-300 rounded"
                                            @checked(in_array($treinamento->codigo, $treinamentosSelecionados))>
                                        <div>
                                            <p class="font-semibold text-slate-800 text-sm">
                                                {{ $treinamento->codigo }}
                                            </p>
                                            <p class="text-[11px] text-slate-500">
                                                {{ $treinamento->descricao ?? 'Treinamento NR' }}
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    </div>
                </section>

                {{-- 3. Onde será realizado? --}}
                @php
                    $localAtual = old('local_tipo', $detalhes->local_tipo ?? 'clinica');
                    $unidadeAtual = old('unidade_id', $detalhes->unidade_id ?? '');
                @endphp

                <section class="space-y-3 pt-4 border-t border-slate-100 mt-4">
                    <h2 class="text-sm font-semibold text-slate-800">3. Onde será realizado?</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Na Clínica --}}
                        <label class="relative">
                            <input type="radio" name="local_tipo" value="clinica"
                                   class="sr-only" @checked($localAtual === 'clinica')>
                            <div class="local-radio-card rounded-2xl border px-4 py-3 cursor-pointer">
                                <p class="text-sm font-semibold text-slate-800">Na Clínica</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Treinamento na unidade FORMED
                                </p>
                            </div>
                        </label>

                        {{-- In Company --}}
                        <label class="relative">
                            <input type="radio" name="local_tipo" value="empresa"
                                   class="sr-only" @checked($localAtual === 'empresa')>
                            <div class="local-radio-card rounded-2xl border px-4 py-3 cursor-pointer">
                                <p class="text-sm font-semibold text-slate-800">In Company</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Treinamento na empresa do cliente
                                </p>
                            </div>
                        </label>
                    </div>
                    @error('local_tipo')
                        <p class="mt-1 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    {{-- Na Clínica: select unidade --}}
                    <div id="bloco-clinica" class="space-y-2">
                        <label class="block text-xs font-medium text-slate-600">
                            Unidade Credenciada
                        </label>
                        <select name="unidade_id"
                                class="w-full rounded-xl text-sm px-3 py-2 {{ $errors->has('unidade_id') ? 'border-red-300 focus:border-red-400 focus:ring-red-200' : 'border-slate-200' }}">
                            <option value="">Escolha uma unidade</option>
                            @foreach($unidades as $unidade)
                                <option value="{{ $unidade->id }}"
                                    @selected($unidadeAtual == $unidade->id)>
                                    {{ $unidade->nome }}
                                </option>
                            @endforeach
                        </select>
                        @error('unidade_id')
                            <p class="mt-1 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- In Company: CTA WhatsApp --}}
                    <div id="bloco-empresa" class="hidden space-y-2">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-800">
                            Para treinamentos In Company, nossa equipe comercial
                            entrará em contato para alinhar valores, datas e estrutura necessária.
                        </div>

                        @php
                            // Substituir pelo número real da FORMED
                            $waPhone = '55XXXXXXXXXXX';
                            $waMsg   = rawurlencode("Olá, gostaria de negociar um treinamento de NRs In Company para o cliente {$cliente->razao_social}.");
                        @endphp

                        <a href="https://wa.me/{{ $waPhone }}?text={{ $waMsg }}"
                           target="_blank"
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-semibold hover:bg-emerald-600">
                            💬 Chamar a FORMED no WhatsApp
                        </a>
                    </div>
                </section>

                {{-- Footer --}}
                <div class="pt-4 border-t border-slate-100 mt-4">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        {{ !empty($isEdit) ? 'Salvar alterações' : 'Solicitar Treinamento' }}
                    </button>
                </div>

            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const csrf   = '{{ csrf_token() }}';
                const ajaxUrl = '{{ route('operacional.treinamentos-nr.funcionarios.store', $cliente) }}';

                // ----- Novo funcionário -----
                const cardNovo  = document.getElementById('card-novo-funcionario');
                const btnToggle = document.getElementById('btn-novo-funcionario-toggle');
                const btnClose  = document.getElementById('btn-novo-funcionario-close');
                const btnSalvar = document.getElementById('btn-novo-funcionario-salvar');
                const erroEl    = document.getElementById('nf-erro');

                const nfNome      = document.getElementById('nf-nome');
                const nfCpf       = document.getElementById('nf-cpf');
                const nfNasc      = document.getElementById('nf-nascimento');
                const nfNascBr    = document.getElementById('nf-nascimento-br');
                const nfFuncaoSel = document.getElementById('nf_funcao_id'); // <-- SELECT de função

                btnToggle.addEventListener('click', () => {
                    cardNovo.classList.remove('hidden');
                });

                btnClose.addEventListener('click', () => {
                    cardNovo.classList.add('hidden');
                });

                btnSalvar.addEventListener('click', () => {
                    erroEl.classList.add('hidden');
                    erroEl.textContent = '';

                    const payload = {
                        nome:       nfNome.value.trim(),
                        cpf:        nfCpf.value.trim(),
                        nascimento: nfNasc.value || null,
                        funcao_id:  nfFuncaoSel.value || null,
                    };

                    if (!payload.nome || !payload.cpf || !payload.funcao_id) {
                        erroEl.textContent = 'Preencha Nome, CPF e Função.';
                        erroEl.classList.remove('hidden');
                        return;
                    }

                    btnSalvar.disabled = true;

                    fetch(ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (!data.ok) {
                                throw new Error('Erro ao salvar colaborador.');
                            }

                            adicionarFuncionarioNaLista(data.funcionario);

                            nfNome.value      = '';
                            nfCpf.value       = '';
                            nfNasc.value      = '';
                            if (nfNascBr) nfNascBr.value = '';
                            nfFuncaoSel.value = '';

                            cardNovo.classList.add('hidden');
                            atualizarContador();
                        })
                        .catch(() => {
                            erroEl.textContent = 'Não foi possível salvar. Tente novamente.';
                            erroEl.classList.remove('hidden');
                        })
                        .finally(() => {
                            btnSalvar.disabled = false;
                        });
                });

                // ----- Lista de funcionários / contador -----
                const lista       = document.getElementById('lista-funcionarios');
                const contadorEl  = document.getElementById('contador-selecionados');
                const contadorBadgeEl = document.getElementById('contador-selecionados-badge');
                const filtroFuncaoEl = document.getElementById('filtro-funcao-participante');
                const checkSelecionarTodosEl = document.getElementById('check-selecionar-todos-participantes');
                const resumoFuncoesCardsEl = document.getElementById('resumo-funcoes-cards');
                const paletaFuncoes = [
                    {
                        card: ['bg-blue-50', 'border-blue-200'],
                        chip: 'bg-blue-100 border-blue-200 text-blue-800'
                    },
                    {
                        card: ['bg-emerald-50', 'border-emerald-200'],
                        chip: 'bg-emerald-100 border-emerald-200 text-emerald-800'
                    },
                    {
                        card: ['bg-amber-50', 'border-amber-200'],
                        chip: 'bg-amber-100 border-amber-200 text-amber-800'
                    },
                    {
                        card: ['bg-cyan-50', 'border-cyan-200'],
                        chip: 'bg-cyan-100 border-cyan-200 text-cyan-800'
                    },
                ];

                function normalizarTexto(valor) {
                    return String(valor || '')
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .trim();
                }

                function checkboxesDaFuncao(chaveFuncao) {
                    if (!chaveFuncao || !lista) return [];
                    return Array.from(lista.querySelectorAll('input[type="checkbox"][name="funcionarios[]"]'))
                        .filter((checkbox) => normalizarTexto(checkbox.dataset.funcao) === chaveFuncao);
                }

                function chaveFuncaoSelecionadaAtual() {
                    if (!filtroFuncaoEl) return '';
                    return normalizarTexto(String(filtroFuncaoEl.value || '').trim());
                }

                function aplicarDestaqueFuncoes() {
                    if (!lista) return;

                    const cards = Array.from(lista.querySelectorAll('label[data-funcao]'));
                    const classesCardPaleta = paletaFuncoes.flatMap((item) => item.card);
                    const chaveSelecionada = chaveFuncaoSelecionadaAtual();

                    cards.forEach((card) => {
                        card.classList.remove(...classesCardPaleta);
                    });
                    if (!chaveSelecionada) return;

                    const estilo = paletaFuncoes[0];
                    cards
                        .filter((card) => normalizarTexto(card.dataset.funcao) === chaveSelecionada)
                        .forEach((card) => card.classList.add(...estilo.card));
                }

                function renderizarResumoFuncoes() {
                    if (!resumoFuncoesCardsEl) return;
                    const selecionadosPorFuncao = new Map();
                    const checkboxesSelecionados = Array.from(
                        lista.querySelectorAll('input[type="checkbox"][name="funcionarios[]"]:checked')
                    );

                    checkboxesSelecionados.forEach((checkbox) => {
                        const chave = normalizarTexto(checkbox.dataset.funcao);
                        if (!chave) return;

                        const label = String(checkbox.dataset.funcao || '').trim() || chave;

                        if (!selecionadosPorFuncao.has(chave)) {
                            selecionadosPorFuncao.set(chave, { label, total: 0 });
                        }
                        const item = selecionadosPorFuncao.get(chave);
                        item.total += 1;
                        selecionadosPorFuncao.set(chave, item);
                    });

                    if (!selecionadosPorFuncao.size) {
                        resumoFuncoesCardsEl.innerHTML = '';
                        return;
                    }

                    const ordem = Array.from(selecionadosPorFuncao.keys());

                    resumoFuncoesCardsEl.innerHTML = ordem
                        .map((chave, idx) => {
                            const estilo = paletaFuncoes[idx % paletaFuncoes.length];
                            const dados = selecionadosPorFuncao.get(chave) || {};
                            const label = dados.label || chave;
                            const participantes = checkboxesDaFuncao(chave)
                                .filter((checkbox) => checkbox.checked)
                                .map((checkbox) => ({
                                    id: String(checkbox.value || ''),
                                    nome: String(checkbox.dataset.nome || '').trim() || 'Colaborador',
                                    funcao: String(checkbox.dataset.funcao || '').trim() || label,
                                }));

                            const linhasParticipantes = participantes
                                .map((item, indexParticipante) => `
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-[11px] font-semibold text-slate-600">Trabalhador ${indexParticipante + 1}</p>
                                            <button type="button"
                                                    data-remove-funcionario="${item.id}"
                                                    class="text-[11px] font-semibold text-rose-500 hover:text-rose-600">
                                                Remover
                                            </button>
                                        </div>
                                        <div class="mt-1 grid grid-cols-1 md:grid-cols-2 gap-2">
                                            <div>
                                                <p class="text-[11px] text-slate-500">Nome</p>
                                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-sm text-slate-700">${item.nome}</div>
                                            </div>
                                            <div>
                                                <p class="text-[11px] text-slate-500">Fun&ccedil;&atilde;o</p>
                                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-sm text-slate-700">${item.funcao}</div>
                                            </div>
                                        </div>
                                    </div>
                                `)
                                .join('');

                            return `
                                <div class="rounded-2xl border p-3 ${estilo.chip}">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center rounded-lg border px-2 py-1 text-[11px] font-semibold ${estilo.chip}">
                                            Fun&ccedil;&atilde;o: ${label}
                                        </span>
                                        <button type="button"
                                                data-remove-grupo-funcao="${chave}"
                                                class="text-[11px] font-semibold text-rose-500 hover:text-rose-600">
                                            Remover grupo
                                        </button>
                                    </div>
                                    <div class="mt-2 space-y-2">${linhasParticipantes}</div>
                                </div>
                            `;
                        })
                        .join('');
                }

                function aplicarVisibilidadePorFiltroAtual() {
                    if (!lista) return;
                    const chaveSelecionada = chaveFuncaoSelecionadaAtual();
                    const cards = Array.from(lista.querySelectorAll('label[data-funcao]'));

                    cards.forEach((card) => {
                        const chaveCard = normalizarTexto(card.dataset.funcao);
                        const mostrar = !chaveSelecionada || chaveCard === chaveSelecionada;
                        card.classList.toggle('hidden', !mostrar);
                    });
                }

                function checkboxesDoFiltroAtual() {
                    const chaveSelecionada = chaveFuncaoSelecionadaAtual();
                    const todos = Array.from(lista.querySelectorAll('input[type="checkbox"][name="funcionarios[]"]'));
                    if (!chaveSelecionada) return todos;
                    return todos.filter((checkbox) => normalizarTexto(checkbox.dataset.funcao) === chaveSelecionada);
                }

                function sincronizarCheckSelecionarTodos() {
                    if (!checkSelecionarTodosEl) return;
                    const grupoAtual = checkboxesDoFiltroAtual();
                    if (!grupoAtual.length) {
                        checkSelecionarTodosEl.checked = false;
                        checkSelecionarTodosEl.indeterminate = false;
                        return;
                    }
                    const marcados = grupoAtual.filter((checkbox) => checkbox.checked).length;
                    checkSelecionarTodosEl.checked = marcados === grupoAtual.length;
                    checkSelecionarTodosEl.indeterminate = marcados > 0 && marcados < grupoAtual.length;
                }

                function adicionarFuncionarioNaLista(func) {
                    const funcaoNome = func.funcao_nome || '';
                    const label = document.createElement('label');
                    label.className = 'block border border-slate-200 rounded-xl px-3 py-3 text-xs cursor-pointer bg-white hover:bg-indigo-50/40';
                    label.dataset.funcao = funcaoNome;
                    label.innerHTML = `
                    <div class="flex items-start gap-2">
                        <input type="checkbox"
                               name="funcionarios[]"
                               value="${func.id}"
                               data-funcao="${funcaoNome}"
                               data-nome="${func.nome}"
                               class="mt-1 h-3 w-3 text-indigo-600 border-slate-300 rounded"
                               checked>
                        <div>
                            <p class="font-semibold text-slate-800 text-sm">
                                ${func.nome}
                            </p>
                            <p class="text-[11px] text-slate-500">
                                ${func.funcao_nome || 'Função não informada'}
                            </p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                CPF: ${func.cpf}
                            </p>
                        </div>
                    </div>
                    `;
                    lista.prepend(label);
                    aplicarDestaqueFuncoes();
                    renderizarResumoFuncoes();
                    aplicarVisibilidadePorFiltroAtual();
                    sincronizarCheckSelecionarTodos();
                }

                function atualizarContador() {
                    const selecionados = lista.querySelectorAll('input[type="checkbox"]:checked').length;
                    if (contadorBadgeEl) contadorBadgeEl.textContent = String(selecionados);
                    if (!selecionados) {
                        contadorEl.textContent = 'Nenhum participante selecionado.';
                    } else if (selecionados === 1) {
                        contadorEl.textContent = '1 participante selecionado.';
                    } else {
                        contadorEl.textContent = selecionados + ' participantes selecionados.';
                    }
                }

                lista.addEventListener('change', function (e) {
                    if (e.target.matches('input[type="checkbox"]')) {
                        atualizarContador();
                        renderizarResumoFuncoes();
                        sincronizarCheckSelecionarTodos();
                    }
                });

                atualizarContador();
                aplicarDestaqueFuncoes();
                renderizarResumoFuncoes();
                aplicarVisibilidadePorFiltroAtual();
                sincronizarCheckSelecionarTodos();

                if (filtroFuncaoEl) {
                    filtroFuncaoEl.addEventListener('change', function () {
                        aplicarVisibilidadePorFiltroAtual();
                        aplicarDestaqueFuncoes();
                        sincronizarCheckSelecionarTodos();
                    });
                }

                if (checkSelecionarTodosEl) {
                    checkSelecionarTodosEl.addEventListener('change', function () {
                        const checked = !!checkSelecionarTodosEl.checked;
                        checkboxesDoFiltroAtual()
                            .forEach((checkbox) => {
                                checkbox.checked = checked;
                            });

                        atualizarContador();
                        renderizarResumoFuncoes();
                        aplicarDestaqueFuncoes();
                        aplicarVisibilidadePorFiltroAtual();
                        sincronizarCheckSelecionarTodos();
                    });
                }

                if (resumoFuncoesCardsEl) {
                    resumoFuncoesCardsEl.addEventListener('click', function (event) {
                        const btnGrupo = event.target.closest('[data-remove-grupo-funcao]');
                        if (btnGrupo) {
                            const chave = normalizarTexto(btnGrupo.getAttribute('data-remove-grupo-funcao'));
                            if (chave) {
                                checkboxesDaFuncao(chave).forEach((checkbox) => { checkbox.checked = false; });
                                atualizarContador();
                                aplicarDestaqueFuncoes();
                                renderizarResumoFuncoes();
                                aplicarVisibilidadePorFiltroAtual();
                                sincronizarCheckSelecionarTodos();
                            }
                            return;
                        }

                        const btnFuncionario = event.target.closest('[data-remove-funcionario]');
                        if (btnFuncionario) {
                            const id = String(btnFuncionario.getAttribute('data-remove-funcionario') || '').trim();
                            if (!id) return;

                            const checkbox = lista.querySelector(`input[type="checkbox"][name="funcionarios[]"][value="${id}"]`);
                            if (checkbox) {
                                checkbox.checked = false;
                                atualizarContador();
                                renderizarResumoFuncoes();
                                sincronizarCheckSelecionarTodos();
                            }
                        }
                    });
                }

                // ----- Modo: pacote x avulso -----
                const modoRadios = document.querySelectorAll('input[name="treinamento_modo"]');
                const wrapPacotes = document.getElementById('pacotesTreinamentosWrap');
                const wrapAvulsos = document.getElementById('treinamentosAvulsosWrap');

                function atualizarModoTreinamento() {
                    let modo = 'avulso';
                    modoRadios.forEach(r => { if (r.checked) modo = r.value; });

                    if (wrapPacotes) wrapPacotes.classList.toggle('hidden', modo !== 'pacote');
                    if (wrapAvulsos) wrapAvulsos.classList.toggle('hidden', modo !== 'avulso');

                    if (modo === 'pacote' && wrapAvulsos) {
                        wrapAvulsos.querySelectorAll('input[type="checkbox"][name="treinamentos[]"]')
                            .forEach(cb => { cb.checked = false; });
                    }
                    if (modo === 'avulso' && wrapPacotes) {
                        wrapPacotes.querySelectorAll('input[type="radio"][name="pacote_id"]')
                            .forEach(rb => { rb.checked = false; });
                    }
                }

                modoRadios.forEach(r => r.addEventListener('change', atualizarModoTreinamento));
                atualizarModoTreinamento();

                // ----- Local: Na clínica x In Company -----
                const localCards   = document.querySelectorAll('.local-radio-card');
                const radios       = document.querySelectorAll('input[name="local_tipo"]');
                const blocoClinica = document.getElementById('bloco-clinica');
                const blocoEmpresa = document.getElementById('bloco-empresa');

                function atualizarLocalUI() {
                    let valor = 'clinica';
                    radios.forEach(r => { if (r.checked) valor = r.value; });

                    localCards.forEach((card, idx) => {
                        const r = radios[idx];
                        if (r.checked) {
                            card.classList.remove('border-slate-200', 'bg-slate-50');
                            card.classList.add('border-indigo-300', 'bg-indigo-50');
                        } else {
                            card.classList.add('border-slate-200', 'bg-slate-50');
                            card.classList.remove('border-indigo-300', 'bg-indigo-50');
                        }
                    });

                    if (valor === 'clinica') {
                        blocoClinica.classList.remove('hidden');
                        blocoEmpresa.classList.add('hidden');
                    } else {
                        blocoClinica.classList.add('hidden');
                        blocoEmpresa.classList.remove('hidden');
                    }
                }

                localCards.forEach((card, idx) => {
                    card.addEventListener('click', () => {
                        radios[idx].checked = true;
                        atualizarLocalUI();
                    });
                });

                atualizarLocalUI();

                // Máscara e validação CPF do modal
                var cpfInput = document.querySelector('input[name="cpf"]');
                if (cpfInput) {
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
                }

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

            });
        </script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.flatpickr) {
            return;
        }

        if (flatpickr.l10ns && flatpickr.l10ns.pt) {
            flatpickr.localize(flatpickr.l10ns.pt);
        }

        function maskBrDate(value) {
            const digits = (value || '').replace(/\D+/g, '').slice(0, 8);
            if (digits.length <= 2) return digits;
            if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
            return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
        }

        document.querySelectorAll('.js-date-text').forEach((textInput) => {
            const hiddenId = textInput.dataset.dateTarget;
            const hiddenInput = hiddenId ? document.getElementById(hiddenId) : null;
            const defaultDate = hiddenInput && hiddenInput.value ? hiddenInput.value : null;

            const fp = flatpickr(textInput, {
                allowInput: true,
                dateFormat: 'd/m/Y',
                defaultDate: defaultDate,
                onChange: function (selectedDates) {
                    if (!hiddenInput) return;
                    hiddenInput.value = selectedDates.length
                        ? flatpickr.formatDate(selectedDates[0], 'Y-m-d')
                        : '';
                },
                onClose: function (selectedDates) {
                    if (!hiddenInput) return;
                    hiddenInput.value = selectedDates.length
                        ? flatpickr.formatDate(selectedDates[0], 'Y-m-d')
                        : '';
                },
            });

            textInput.addEventListener('input', () => {
                textInput.value = maskBrDate(textInput.value);
                if (!hiddenInput) return;
                if (textInput.value.length === 10) {
                    const parsed = fp.parseDate(textInput.value, 'd/m/Y');
                    hiddenInput.value = parsed ? fp.formatDate(parsed, 'Y-m-d') : '';
                }
            });

            textInput.addEventListener('blur', () => {
                if (!hiddenInput) return;
                const parsed = fp.parseDate(textInput.value, 'd/m/Y');
                hiddenInput.value = parsed ? fp.formatDate(parsed, 'Y-m-d') : '';
            });
        });

        document.querySelectorAll('.date-picker-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.dateTarget;
                const textInput = targetId
                    ? document.querySelector(`.js-date-text[data-date-target="${targetId}"]`)
                    : null;
                if (textInput && textInput._flatpickr) {
                    textInput.focus();
                    textInput._flatpickr.open();
                }
            });
        });
    });
</script>
@endpush
@endsection
