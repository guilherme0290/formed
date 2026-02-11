@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')


@section('pageTitle', 'Treinamentos de NRs')

@section('content')
    @php
        $origem = request()->query('origem');
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

                {{-- Erros --}}
                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700">
                        <ul class="list-disc ms-4">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- 1. Selecione os participantes --}}
                <section class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-800">1. Selecione os Participantes</h2>

                        <button type="button"
                                id="btn-novo-funcionario-toggle"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600">
                            + Cadastrar Novo
                        </button>
                    </div>

                    {{-- Card de novo funcionário --}}
                    <div id="card-novo-funcionario"
                         class="hidden rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 mb-3">
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
                                            aria-label="Abrir calendÃ¡rio">
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
                                    :allowCreate="false"
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
                    @endphp

                    <div id="lista-funcionarios"
                         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto pr-1">
                        @foreach($funcionarios as $func)
                            <label class="block border rounded-xl px-3 py-3 text-xs cursor-pointer bg-slate-50 hover:bg-slate-100">
                                <div class="flex items-start gap-2">
                                    <input type="checkbox"
                                           name="funcionarios[]"
                                           value="{{ $func->id }}"
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

                    <p id="contador-selecionados" class="text-[11px] text-slate-500 mt-1">
                        Nenhum participante selecionado.
                    </p>
                </section>

                {{-- 2. Selecione os Treinamentos --}}
                @php
                    $treinamentosSelecionados = old('treinamentos', $detalhes->treinamentos ?? []);
                    $treinamentosFinalizados = $treinamentosFinalizados ?? [];
                @endphp

                <section class="space-y-3 pt-4 border-t border-slate-100 mt-4">
                    <h2 class="text-sm font-semibold text-slate-800">2. Selecione os Treinamentos</h2>

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
                                    $isFinalizado = in_array(strtoupper(trim($codigoTreinamento)), $treinamentosFinalizados, true);
                                @endphp
                                <label class="block border rounded-xl px-3 py-3 text-xs cursor-pointer bg-slate-50 hover:bg-slate-100">
                                    <div class="flex items-start gap-2 {{ $isFinalizado && !$isSelecionado ? 'opacity-60' : '' }}">
                                        <input type="checkbox"
                                               name="treinamentos[]"
                                               value="{{ $treinamento->codigo }}"
                                               class="mt-1 h-3 w-3 text-indigo-600 border-slate-300 rounded"
                                               @disabled($isFinalizado && !$isSelecionado)
                                            @checked(in_array($treinamento->codigo, $treinamentosSelecionados))>
                                        <div>
                                            <p class="font-semibold text-slate-800 text-sm">
                                                {{ $treinamento->codigo }}
                                            </p>
                                            <p class="text-[11px] text-slate-500">
                                                {{ $treinamento->descricao ?? 'Treinamento NR' }}
                                            </p>
                                            @if($isFinalizado && !$isSelecionado)
                                                <p class="mt-1 text-[11px] font-semibold text-amber-700">
                                                    Serviço finalizado
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
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

                    {{-- Na Clínica: select unidade --}}
                    <div id="bloco-clinica" class="space-y-2">
                        <label class="block text-xs font-medium text-slate-600">
                            Unidade Credenciada
                        </label>
                        <select name="unidade_id"
                                class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                            <option value="">Escolha uma unidade</option>
                            @foreach($unidades as $unidade)
                                <option value="{{ $unidade->id }}"
                                    @selected($unidadeAtual == $unidade->id)>
                                    {{ $unidade->nome }}
                                </option>
                            @endforeach
                        </select>
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
                        {{ !empty($isEdit) ? 'Salvar alterações' : 'Criar Tarefa de Treinamento' }}
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

                function adicionarFuncionarioNaLista(func) {
                    const label = document.createElement('label');
                    label.className = 'block border rounded-xl px-3 py-3 text-xs cursor-pointer bg-slate-50 hover:bg-slate-100';
                    label.innerHTML = `
                    <div class="flex items-start gap-2">
                        <input type="checkbox"
                               name="funcionarios[]"
                               value="${func.id}"
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
                }

                function atualizarContador() {
                    const selecionados = lista.querySelectorAll('input[type="checkbox"]:checked').length;
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
                    }
                });

                atualizarContador();

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

                });
                });
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


