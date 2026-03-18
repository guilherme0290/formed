@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')

@php
    $origem = request()->query('origem');
    $rotaVoltar = $origem === 'cliente'
        ? route('cliente.dashboard')
        : route('operacional.kanban.servicos', $cliente);
    $solicitacaoParaPadrao = !empty($solicitacao?->funcionario_id)
        ? 'funcionario'
        : (($cliente->tipo_pessoa ?? null) === 'PJ' ? 'funcionario' : 'independente');
    $solicitacaoPara = old('solicitacao_para', $solicitacaoParaPadrao);
    $funcionarioSelecionadoId = old('funcionario_id', $solicitacao->funcionario_id ?? '');
    $nomeCompletoValue = old('nome_completo', $solicitacao->nome_completo ?? ($cliente->razao_social ?? $cliente->nome_fantasia ?? ''));
    $cpfValue = old('cpf', $solicitacao->cpf ?? ($cliente->tipo_pessoa === 'PF' ? ($cliente->cpf ?? '') : ''));
    $rgValue = old('rg', $solicitacao->rg ?? '');
    $dataNascimentoValue = old('data_nascimento', optional($solicitacao?->data_nascimento)->format('Y-m-d'));
    $telefoneValue = old('telefone', $solicitacao->telefone ?? ($cliente->telefone ?? ''));
    $emailValue = old('email_envio', $solicitacao->email_envio ?? ($cliente->email ?? ''));
@endphp

@section('pageTitle', 'Exame toxicológico')

@section('content')
    <div class="w-full px-2 sm:px-3 md:px-4 xl:px-5 py-4 md:py-6">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ $rotaVoltar }}"
               class="inline-flex items-center gap-2 mb-4 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                ← Voltar
            </a>
        </div>

        <div class="w-full bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-4 sm:px-5 md:px-6 py-4 bg-gradient-to-r from-teal-700 to-cyan-600 text-white">
                <h1 class="text-lg font-semibold">
                    Exame toxicológico {{ !empty($isEdit) ? '(Editar)' : '' }}
                </h1>
                <p class="text-xs text-white/80 mt-1">
                    {{ $cliente->razao_social }}
                </p>
            </div>

            <form method="POST"
                  action="{{ !empty($isEdit) && $solicitacao
                        ? route('operacional.toxicologico.update', ['exameToxicologico' => $solicitacao, 'origem' => $origem])
                        : route('operacional.toxicologico.store', ['cliente' => $cliente, 'origem' => $origem]) }}"
                  class="px-4 sm:px-5 md:px-6 py-5 md:py-6 space-y-6">
                @csrf
                @if(!empty($isEdit) && $solicitacao)
                    @method('PUT')
                @endif
                <input type="hidden" name="origem" value="{{ $origem }}">

                <section class="space-y-3">
                    <h2 class="text-sm font-semibold text-slate-800">Solicitação para *</h2>
                    <div class="grid gap-2 md:grid-cols-2">
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 hover:bg-slate-100">
                            <input type="radio"
                                   name="solicitacao_para"
                                   value="funcionario"
                                   class="border-slate-300 text-teal-600 focus:ring-teal-500"
                                   @checked($solicitacaoPara === 'funcionario')>
                            <span class="font-medium">Colaborador da empresa</span>
                        </label>
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 hover:bg-slate-100">
                            <input type="radio"
                                   name="solicitacao_para"
                                   value="independente"
                                   class="border-slate-300 text-teal-600 focus:ring-teal-500"
                                   @checked($solicitacaoPara === 'independente')>
                            <span class="font-medium">Independente</span>
                        </label>
                    </div>
                </section>

                <section id="funcionario-box" class="space-y-2 {{ $solicitacaoPara === 'funcionario' ? '' : 'hidden' }}">
                    <h2 class="text-sm font-semibold text-slate-800">Colaborador da empresa *</h2>
                    <select name="funcionario_id"
                            id="funcionario_id"
                            data-funcionario-url="{{ route('operacional.kanban.aso.funcionario', ['cliente' => $cliente, 'funcionario' => 'FUNCIONARIO_ID']) }}"
                            class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 bg-white">
                        <option value="">Selecione o colaborador</option>
                        @foreach($funcionarios as $funcionario)
                            <option value="{{ $funcionario->id }}" @selected((string) $funcionarioSelecionadoId === (string) $funcionario->id)>
                                {{ $funcionario->nome }}
                            </option>
                        @endforeach
                    </select>
                </section>

                <section class="space-y-3">
                    <h2 class="text-sm font-semibold text-slate-800">Tipo de Exame Toxicológico *</h2>
                    <div class="grid gap-2 md:grid-cols-3">
                        @foreach($tiposExame as $key => $label)
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 hover:bg-slate-100">
                                <input type="radio"
                                       name="tipo_exame"
                                       value="{{ $key }}"
                                       class="border-slate-300 text-teal-600 focus:ring-teal-500"
                                       @checked(old('tipo_exame', $solicitacao->tipo_exame ?? '') === $key)>
                                <span class="font-medium">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section id="dados-base" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div id="nome_wrap" class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Nome completo *</label>
                        <input type="text"
                               id="nome_completo"
                               name="nome_completo"
                               class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                               value="{{ $nomeCompletoValue }}">
                    </div>

                    <div id="cpf_wrap">
                        <label class="block text-xs font-medium text-slate-600 mb-1">CPF *</label>
                        <input type="text"
                               id="cpf"
                               name="cpf"
                               class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                               value="{{ $cpfValue }}">
                    </div>

                    <div id="rg_wrap">
                        <label class="block text-xs font-medium text-slate-600 mb-1">RG *</label>
                        <input type="text"
                               id="rg"
                               name="rg"
                               class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                               value="{{ $rgValue }}">
                    </div>

                    <div id="nascimento_wrap">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Data de nascimento *</label>
                        <input type="date"
                               id="data_nascimento"
                               name="data_nascimento"
                               class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                               value="{{ $dataNascimentoValue }}">
                    </div>

                    <div id="telefone_wrap">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Telefone *</label>
                        <input type="text"
                               id="telefone"
                               name="telefone"
                               class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                               value="{{ $telefoneValue }}">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">E-mail para envio do exame *</label>
                        <input type="email"
                               name="email_envio"
                               class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                               value="{{ $emailValue }}">
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-semibold text-slate-800 mb-4">Data e local de realização</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Data de Realização *</label>
                            <input type="date"
                                   name="data_realizacao"
                                   class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 bg-white"
                                   value="{{ old('data_realizacao', optional($solicitacao?->data_realizacao)->format('Y-m-d')) }}">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Unidade *</label>
                            <select name="unidade_id"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 bg-white">
                                <option value="">Selecione a unidade</option>
                                @foreach($unidades as $unidade)
                                    <option value="{{ $unidade->id }}"
                                        @selected((string) old('unidade_id', $solicitacao->unidade_id ?? '') === (string) $unidade->id)>
                                        {{ $unidade->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <div class="pt-4 border-t border-slate-100 mt-4">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700">
                        {{ !empty($isEdit) ? 'Salvar alterações' : 'Solicitar Exame toxicológico' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.uiAlert === 'function') {
                    window.uiAlert(@json($errors->first()), {
                        icon: 'error',
                        title: 'Atenção'
                    });
                }
            });
        </script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const radios = document.querySelectorAll('input[name="solicitacao_para"]');
            const funcionarioBox = document.getElementById('funcionario-box');
            const funcionarioSelect = document.getElementById('funcionario_id');
            const fields = {
                nome: document.getElementById('nome_completo'),
                cpf: document.getElementById('cpf'),
                rg: document.getElementById('rg'),
                nascimento: document.getElementById('data_nascimento'),
                telefone: document.getElementById('telefone'),
            };

            function modoAtual() {
                return document.querySelector('input[name="solicitacao_para"]:checked')?.value || 'independente';
            }

            function setReadonly(readonly) {
                Object.values(fields).forEach((field) => {
                    if (!field) return;
                    field.readOnly = readonly;
                    field.classList.toggle('bg-slate-100', readonly);
                });
            }

            function syncModo() {
                const modo = modoAtual();
                const isFuncionario = modo === 'funcionario';
                if (funcionarioBox) {
                    funcionarioBox.classList.toggle('hidden', !isFuncionario);
                }
                if (funcionarioSelect) {
                    funcionarioSelect.disabled = !isFuncionario;
                    if (!isFuncionario) {
                        funcionarioSelect.value = '';
                    }
                }
                setReadonly(isFuncionario);
            }

            async function carregarFuncionario() {
                if (!funcionarioSelect || !funcionarioSelect.value) {
                    return;
                }

                const urlTemplate = funcionarioSelect.dataset.funcionarioUrl || '';
                if (!urlTemplate) {
                    return;
                }

                const url = urlTemplate.replace('FUNCIONARIO_ID', funcionarioSelect.value);

                try {
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const json = await response.json();
                    const funcionario = json?.funcionario || {};

                    if (fields.nome) fields.nome.value = funcionario.nome || '';
                    if (fields.cpf) fields.cpf.value = funcionario.cpf || '';
                    if (fields.rg) fields.rg.value = funcionario.rg || '';
                    if (fields.nascimento) fields.nascimento.value = funcionario.data_nascimento || '';
                    if (fields.telefone) fields.telefone.value = funcionario.celular || '';
                } catch (error) {
                    if (typeof window.uiAlert === 'function') {
                        window.uiAlert('Não foi possível carregar os dados do colaborador.', {
                            icon: 'error',
                            title: 'Atenção'
                        });
                    }
                }
            }

            radios.forEach((radio) => {
                radio.addEventListener('change', function () {
                    syncModo();
                    if (modoAtual() === 'funcionario') {
                        carregarFuncionario();
                    }
                });
            });

            funcionarioSelect?.addEventListener('change', carregarFuncionario);

            syncModo();
            if (modoAtual() === 'funcionario' && funcionarioSelect?.value) {
                carregarFuncionario();
            }
        });
    </script>
@endpush
