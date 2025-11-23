@extends('layouts.operacional')

@section('pageTitle', 'Treinamentos de NRs')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('operacional.kanban.servicos', $cliente) }}"
               class="inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50">
                ← Voltar
            </a>
        </div>

        <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            {{-- Cabeçalho --}}
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white">
                <h1 class="text-lg font-semibold">
                    Treinamentos de NRs
                </h1>
                <p class="text-xs text-white/80 mt-1">
                    {{ $cliente->razao_social }}
                </p>
            </div>

            <form method="POST"
                  action="{{ route('operacional.treinamentos-nr.store', $cliente) }}"
                  class="p-6 space-y-6">
                @csrf

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
                                <input type="text" id="nf-cpf"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                       placeholder="000.000.000-00">
                            </div>

                            <div class="col-span-3">
                                <label class="block text-xs font-medium text-slate-600 mb-1">
                                    Data de Nascimento
                                </label>
                                <input type="date" id="nf-nascimento"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            </div>

                            <div class="col-span-6">
                                <x-funcoes.select-with-create
                                    name="nf_funcao_id"
                                    field-id="nf_funcao_id"
                                    label="Função"
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
                    <div id="lista-funcionarios"
                         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto pr-1">
                        @foreach($funcionarios as $func)
                            <label class="block border rounded-xl px-3 py-3 text-xs cursor-pointer bg-slate-50 hover:bg-slate-100">
                                <div class="flex items-start gap-2">
                                    <input type="checkbox"
                                           name="funcionarios[]"
                                           value="{{ $func->id }}"
                                           class="mt-1 h-3 w-3 text-indigo-600 border-slate-300 rounded">
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

                {{-- 3. Onde será realizado? --}}
                <section class="space-y-3 pt-4 border-t border-slate-100 mt-4">
                    <h2 class="text-sm font-semibold text-slate-800">3. Onde será realizado?</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Na Clínica --}}
                        <label class="relative">
                            <input type="radio" name="local_tipo" value="clinica"
                                   class="sr-only" checked>
                            <div class="local-radio-card rounded-2xl border border-indigo-300 bg-indigo-50 px-4 py-3 cursor-pointer">
                                <p class="text-sm font-semibold text-slate-800">Na Clínica</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Treinamento na unidade FORMED
                                </p>
                            </div>
                        </label>

                        {{-- In Company --}}
                        <label class="relative">
                            <input type="radio" name="local_tipo" value="empresa" class="sr-only">
                            <div class="local-radio-card rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 cursor-pointer">
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
                            Selecione a Unidade
                        </label>
                        <select name="unidade_id"
                                class="w-full rounded-xl border-slate-200 text-sm px-3 py-2">
                            <option value="">Escolha uma unidade</option>
                            @foreach($unidades as $unidade)
                                <option value="{{ $unidade->id }}">
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
                        Criar Tarefa de Treinamento
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
                        funcao_id:  nfFuncaoSel.value || null,   // <-- agora enviamos funcao_id
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
                            nfFuncaoSel.value = '';  // <-- limpa select de função

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
            });
        </script>
    @endpush

@endsection
