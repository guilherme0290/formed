@php
    $user = auth()->user();
    $vendedores = $vendedores ?? collect();

    // default
    $layout = 'layouts.app';

    if ($user && optional($user->papel)->nome === 'Operacional') {
        $layout = 'layouts.operacional';
    }else if ($user && optional($user->papel)->nome === 'Master') {
         $layout = 'layouts.master';
    }else if ($user && optional($user->papel)->nome === 'Comercial') {
         $layout = 'layouts.comercial';
    }
@endphp

@extends($layout)

@section('content')
    @php
        $routePrefix = $routePrefix ?? 'clientes';
        $permPrefix = str_starts_with($routePrefix, 'comercial.') ? 'comercial.clientes' : 'master.clientes';
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canCreate = $isMaster || isset($permissionMap[$permPrefix.'.create']);
        $canUpdate = $isMaster || isset($permissionMap[$permPrefix.'.update']);
        $canSave = $cliente->exists ? $canUpdate : $canCreate;
        $tipoPessoaAtual = old('tipo_pessoa', $cliente->tipo_pessoa ?? 'PJ');
        $documentoAtual = old('documento_principal', $tipoPessoaAtual === 'PF' ? ($cliente->cpf ?? '') : ($cliente->cnpj ?? ''));
        $parametroAtual = $parametro ?? null;
        $formaPagamentoAtual = old('forma_pagamento', $parametroAtual?->forma_pagamento ?? '');
        $vencimentoServicosAtual = old('vencimento_servicos', $parametroAtual?->vencimento_servicos ?? '');
        $emailEnvioFaturaAtual = old('email_envio_fatura', $parametroAtual?->email_envio_fatura ?? '');
    @endphp

    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="mb-2">
            <a href="{{ route($routePrefix.'.index') }}"
               class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                <span class="text-base">&larr;</span>
                Voltar
            </a>
            <div class="mt-1">

{{--                <h1 class="mt-3 text-3xl font-semibold text-slate-900">--}}
{{--                    {{ $cliente->exists ? 'Editar Cliente' : 'Cadastrar Cliente' }}--}}
{{--                </h1>--}}

            </div>
        </div>

        @if($cliente->exists)
            <div class="mb-1 w-full mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap gap-2" data-tabs="cliente">
                    <button type="button"
                            class="px-4 py-2 rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors"
                            data-tab="dados">
                        Dados do Cliente
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors"
                            data-tab="parametros">
                        Par&acirc;metros
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors"
                            data-tab="unidades-permitidas">
                        Unidades Permitidas
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors"
                            data-tab="arquivos">
                        Arquivos
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors"
                            data-tab="acesso">
                        Acesso
                    </button>
                </div>
            </div>
        @endif

        <div data-tabs-scope="cliente">
            <div data-tab-panel="dados" data-tab-panel-root="cliente">
                <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b bg-blue-600 text-white">
                            <h1 class="text-lg font-semibold">Dados do Cliente</h1>
                        </div>
                        <form method="POST"
                              action="{{ $cliente->exists ? route($routePrefix.'.update', $cliente) : route($routePrefix.'.store') }}"
                              id="cliente-dados-form"
                              class="p-6 space-y-6"
                              novalidate>

                @csrf
                @if($cliente->exists)
                    @method('PUT')
                @endif

            {{-- ATIVO --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-toggle-ativo
                    name="ativo"
                    :checked="(bool) old('ativo', $cliente->exists ? $cliente->ativo : 1)"
                    on-label="Ativo"
                    off-label="Inativo"
                />
                @if($cliente->exists)
                    @php
                        $temServicoConfigurado = (($contratoAtivo?->itens_ativos_count ?? 0) > 0)
                            || (($parametro?->itens?->count() ?? 0) > 0);
                    @endphp
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('operacional.kanban.servicos', [
                            'cliente' => $cliente->id,
                            'origem' => 'cliente-edit',
                            'return_url' => route($routePrefix.'.edit', ['cliente' => $cliente->id, 'tab' => 'dados']),
                        ]) }}"
                           class="inline-flex min-w-[170px] items-center justify-center rounded-lg border border-sky-200 bg-sky-50 px-6 py-2 text-sm font-semibold text-sky-700 shadow-sm transition-all hover:-translate-y-0.5 hover:border-sky-300 hover:bg-sky-100 hover:shadow">
                            Nova Tarefa
                        </a>
                        @if($temServicoConfigurado)
                            <a href="{{ route($routePrefix.'.contrato-dinamico', ['cliente' => $cliente->id]) }}"
                               class="inline-flex min-w-[170px] items-center justify-center rounded-lg border border-emerald-300 bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:from-emerald-600 hover:to-teal-600 hover:shadow-md">
                                Criar Contrato
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- LINHA 1 --}}
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label id="tipo-pessoa-label" class="text-base font-medium">Tipo de Pessoa <span class="text-red-600">*</span></label>
                    <div class="mt-2 space-y-2" role="radiogroup" aria-labelledby="tipo-pessoa-label">
                        <label class="flex items-center gap-3 text-base text-slate-700">
                            <input type="radio" name="tipo_pessoa" value="PJ" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" @checked($tipoPessoaAtual === 'PJ') required>
                            <span>Pessoa Jurídica</span>
                        </label>
                        <label class="flex items-center gap-3 text-base text-slate-700">
                            <input type="radio" name="tipo_pessoa" value="PF" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" @checked($tipoPessoaAtual === 'PF') required>
                            <span>Pessoa Física</span>
                        </label>
                    </div>
                    @error('tipo_pessoa')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm" for="cliente-documento" id="cliente-documento-label">
                        {{ $tipoPessoaAtual === 'PF' ? 'CPF' : 'CNPJ' }}
                    </label>
                    <input id="cliente-documento"
                           name="documento_principal"
                           value="{{ $documentoAtual }}"
                           data-cliente-id="{{ $cliente->exists ? $cliente->id : '' }}"
                           data-tipo-pessoa="{{ $tipoPessoaAtual }}"
                           required
                           class="w-full border-gray-300 rounded-lg px-3 py-2">
                    <input type="hidden" name="cpf" id="cliente-cpf-hidden" value="{{ old('cpf', $cliente->cpf) }}">
                    <input type="hidden" name="cnpj" id="cliente-cnpj-hidden" value="{{ old('cnpj', $cliente->cnpj) }}">
                    @error('cpf')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    @error('cnpj')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm" id="cliente-razao-social-label">
                        {{ $tipoPessoaAtual === 'PF' ? 'Nome Completo' : 'Raz&atilde;o Social' }}
                    </label>
                    <input name="razao_social" value="{{ old('razao_social', $cliente->razao_social) }}"
                           required
                           class="w-full border-gray-300 rounded-lg px-3 py-2 uppercase">
                    @error('razao_social')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div id="cliente-nome-fantasia-wrapper">
                <label class="text-sm">Nome Fantasia</label>
                <input name="nome_fantasia" value="{{ old('nome_fantasia', $cliente->nome_fantasia) }}"
                       class="w-full border-gray-300 rounded-lg px-3 py-2 uppercase">
                @error('nome_fantasia')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- LINHA 2 --}}
            <div class="grid md:grid-cols-4 gap-4 items-center">
                <div>
                    <label class="text-sm">E-mail</label>
                    <input name="email" value="{{ old('email', $cliente->email) }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2">
                    @error('email')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm">Contato</label>
                    <input name="contato" value="{{ old('contato', $cliente->contato) }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2 uppercase">
                    @error('contato')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm">Telefone</label>
                    <input name="telefone" value="{{ old('telefone', $cliente->telefone) }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2">
                    @error('telefone')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm">Telefone 2</label>
                    <input name="telefone_2" value="{{ old('telefone_2', $cliente->telefone_2) }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2">
                    @error('telefone_2')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-4 items-start">
                <div>
                    <label id="tipo-cliente-label" class="text-base font-medium">Tipo de Cliente <span class="text-red-600">*</span></label>
                    <div class="mt-2 space-y-2" role="radiogroup" aria-labelledby="tipo-cliente-label">
                        <label class="flex items-center gap-3 text-base text-slate-700">
                            <input type="radio" name="tipo_cliente" value="parceiro" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" @checked(old('tipo_cliente', $cliente->tipo_cliente ?? 'final') === 'parceiro') required>
                            <span>Cliente Parceiro</span>
                        </label>
                        <label class="flex items-center gap-3 text-base text-slate-700">
                            <input type="radio" name="tipo_cliente" value="final" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" @checked(old('tipo_cliente', $cliente->tipo_cliente ?? 'final') === 'final') required>
                            <span>Cliente Final</span>
                        </label>
                    </div>
                    @error('tipo_cliente')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm">Observa&ccedil;&atilde;o <span id="obs-required-star" class="text-red-600 hidden">*</span></label>
                    <textarea id="observacao" name="observacao" rows="3" class="w-full border-gray-300 rounded-lg px-3 py-2 uppercase">{{ old('observacao', $cliente->observacao) }}</textarea>
                    @error('observacao')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- BLOCO ENDEREÇO --}}
            <div class="border rounded-xl p-4 bg-gray-50">
                <h2 class="font-medium mb-4 text-gray-800">Endere&ccedil;o</h2>

                <div class="grid md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm">CEP</label>
                        <input id="cep" name="cep" value="{{ old('cep', $cliente->cep) }}"
                               class="w-full border-gray-300 rounded-lg px-3 py-2"
                               placeholder="Somente n&uacute;meros">
                        @error('cep')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm">Estado (UF)</label>
                        <select id="estado" name="uf"
                                class="w-full border-gray-300 rounded-lg px-3 py-2">
                            <option value="">Selecione...</option>

                            @foreach($estados as $estado)
                                <option value="{{ $estado->uf }}"
                                    {{ old('uf', $ufSelecionada) === $estado->uf ? 'selected' : '' }}>
                                    {{ $estado->uf }} - {{ $estado->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm">Cidade</label>
                        <select name="cidade_id" id="cidade_id"
                                data-selected-id="{{ old('cidade_id', $cliente->cidade_id) }}"
                                required
                                class="w-full border-gray-300 rounded-lg px-3 py-2">
                            <option value="">Selecione...</option>
                            @foreach($cidadesDoEstado as $cid)
                                <option value="{{ $cid->id }}"
                                    {{ old('cidade_id', $cliente->cidade_id) == $cid->id ? 'selected' : '' }}>
                                    {{ $cid->nome }}
                                </option>
                            @endforeach
                        </select>
                        @error('cidade_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="text-sm">Endere&ccedil;o</label>
                        <input id="endereco" name="endereco" value="{{ old('endereco', $cliente->endereco) }}"
                               class="w-full border-gray-300 rounded-lg px-3 py-2 uppercase">
                        @error('endereco')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm">N&uacute;mero</label>
                        <input name="numero" value="{{ old('numero', $cliente->numero) }}"
                               class="w-full border-gray-300 rounded-lg px-3 py-2">
                        @error('numero')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm">Bairro</label>
                        <input id="bairro" name="bairro" value="{{ old('bairro', $cliente->bairro) }}"
                               class="w-full border-gray-300 rounded-lg px-3 py-2 uppercase">
                        @error('bairro')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-sm">Complemento</label>
                    <input name="complemento" value="{{ old('complemento', $cliente->complemento) }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2 uppercase">
                    @error('complemento')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

                        <div class="space-y-6 border-t border-slate-200 pt-6">
                            <div class="border rounded-xl p-4 bg-gray-50">
                                <h2 class="font-medium mb-4 text-gray-800">Pagamento</h2>

                                <div class="grid md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="text-sm">Forma de Pagamento <span class="text-red-600">*</span></label>
                                        <select name="forma_pagamento" required class="w-full border-gray-300 rounded-lg px-3 py-2">
                                            <option value="">Selecione...</option>
                                            @foreach($formasPagamento as $fp)
                                                <option value="{{ $fp }}" @selected($formaPagamentoAtual === $fp)>{{ $fp }}</option>
                                            @endforeach
                                        </select>
                                        @error('forma_pagamento')
                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="text-sm">Dia de Vencimento <span class="text-red-600">*</span></label>
                                        <input type="number"
                                               min="1"
                                               max="31"
                                               name="vencimento_servicos"
                                               required
                                               value="{{ $vencimentoServicosAtual }}"
                                               class="w-full border-gray-300 rounded-lg px-3 py-2">
                                        @error('vencimento_servicos')
                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="text-sm">Email para envio da fatura</label>
                                        <input type="email"
                                               name="email_envio_fatura"
                                               value="{{ $emailEnvioFaturaAtual }}"
                                               placeholder="financeiro@cliente.com.br"
                                               class="w-full border-gray-300 rounded-lg px-3 py-2">
                                        <p class="text-xs text-slate-500 mt-1">Opcional.</p>
                                        @error('email_envio_fatura')
                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="border rounded-xl p-4 bg-gray-50">
                                <h2 class="font-medium mb-4 text-gray-800">Vendedor</h2>

                                <div>
                                    <label class="text-sm">Vendedor respons&aacute;vel <span class="text-red-600">*</span></label>
                                    <select name="vendedor_id" required class="w-full border-gray-300 rounded-lg px-3 py-2 mt-1">
                                        <option value="">Selecione...</option>
                                        @foreach($vendedores as $vendedor)
                                            <option value="{{ $vendedor->id }}"
                                                @selected((string) old('vendedor_id', $cliente->vendedor_id) === (string) $vendedor->id)>
                                                {{ $vendedor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vendedor_id')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

            {{-- BOTÕES --}}
            <div class="flex flex-wrap justify-end gap-3"><button type="submit" id="cliente-dados-submit" @if(!$canSave) disabled title="Usuário sem permissão" @endif class="w-full px-6 py-3 rounded-2xl text-base font-semibold shadow-md {{ $canSave ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-200' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                    {{ $cliente->exists ? 'Salvar Alterações' : 'Cadastrar' }}
                </button>
            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if($cliente->exists)
                @include('clientes.partials.parametros')
            @endif

            @if($cliente->exists)
                @include('clientes.partials.arquivos')
            @endif

            @if($cliente->exists)
                @include('clientes.partials.acesso-tab')
            @endif

        </div>
    </div>

    {{-- jQuery + MÁSCARAS (CNPJ, CEP, Telefone) --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        (function () {
            const tabsWrap = document.querySelector('[data-tabs="cliente"]');
            if (!tabsWrap) return;

            const tabs = Array.from(tabsWrap.querySelectorAll('[data-tab]'));
            const scope = tabsWrap.closest('[data-tabs-scope="cliente"]') || document;
            const panels = Array.from(scope.querySelectorAll('[data-tab-panel-root="cliente"]'));

            function activate(tabName) {
                const activeColors = {
                    'dados': '#2563eb',
                    'parametros': '#059669',
                    'unidades-permitidas': '#0f766e',
                    'arquivos': '#4338ca',
                    'acesso': '#7c3aed',
                };

                tabs.forEach(btn => {
                    const active = btn.dataset.tab === tabName;
                    if (active) {
                        const color = activeColors[btn.dataset.tab] || '#2563eb';
                        btn.style.backgroundColor = color;
                        btn.style.color = '#ffffff';
                        btn.style.borderColor = color;
                        btn.style.boxShadow = '0 4px 12px rgba(15, 23, 42, 0.18)';
                    } else {
                        btn.style.backgroundColor = '';
                        btn.style.color = '';
                        btn.style.borderColor = '';
                        btn.style.boxShadow = '';
                    }
                });
                panels.forEach(panel => {
                    if (!panel.dataset.tabPanel) return;
                    panel.classList.toggle('hidden', panel.dataset.tabPanel !== tabName);
                });
            }

            tabs.forEach(btn => {
                btn.addEventListener('click', () => {
                    const redirectUrl = btn.dataset.tabUrl;
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                        return;
                    }
                    activate(btn.dataset.tab);
                });
            });

            const urlTab = new URLSearchParams(window.location.search).get('tab');
            const initialTab = tabs.some(btn => btn.dataset.tab === urlTab) ? urlTab : 'dados';
            activate(initialTab);
        })();
    </script>

    <script>
        $(function () {
            $('#cep').mask('00000-000');
            $('input[name="telefone"]').mask('(00) 00000-0000');
        });
    </script>

    <script>
        function normalizarTexto(str) {
            if (!str) return '';
            return str
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, ' ')
                .trim()
                .toUpperCase();
        }

        function formatCpf(value) {
            const digits = (value || '').replace(/\D+/g, '').slice(0, 11);
            if (digits.length <= 3) return digits;
            if (digits.length <= 6) return `${digits.slice(0, 3)}.${digits.slice(3)}`;
            if (digits.length <= 9) return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
            return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
        }

        function formatCnpj(value) {
            const digits = (value || '').replace(/\D+/g, '').slice(0, 14);
            if (digits.length <= 2) return digits;
            if (digits.length <= 5) return `${digits.slice(0, 2)}.${digits.slice(2)}`;
            if (digits.length <= 8) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
            if (digits.length <= 12) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
            return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
        }

        function cpfValido(cpf) {
            const digits = (cpf || '').replace(/\D+/g, '');
            if (digits.length !== 11 || /^(\d)\1{10}$/.test(digits)) return false;

            let soma = 0;
            for (let i = 0; i < 9; i++) {
                soma += parseInt(digits[i], 10) * (10 - i);
            }
            let resto = (soma * 10) % 11;
            if (resto === 10) resto = 0;
            if (resto !== parseInt(digits[9], 10)) return false;

            soma = 0;
            for (let i = 0; i < 10; i++) {
                soma += parseInt(digits[i], 10) * (11 - i);
            }
            resto = (soma * 10) % 11;
            if (resto === 10) resto = 0;

            return resto === parseInt(digits[10], 10);
        }

        function cnpjValido(cnpj) {
            const digits = (cnpj || '').replace(/\D+/g, '');
            if (digits.length !== 14 || /^(\d)\1{13}$/.test(digits)) return false;

            let tamanho = 12;
            let numeros = digits.substring(0, tamanho);
            const digitos = digits.substring(tamanho);
            let soma = 0;
            let pos = tamanho - 7;

            for (let i = tamanho; i >= 1; i--) {
                soma += parseInt(numeros.charAt(tamanho - i), 10) * pos--;
                if (pos < 2) pos = 9;
            }

            let resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            if (resultado !== parseInt(digitos.charAt(0), 10)) return false;

            tamanho = 13;
            numeros = digits.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;

            for (let i = tamanho; i >= 1; i--) {
                soma += parseInt(numeros.charAt(tamanho - i), 10) * pos--;
                if (pos < 2) pos = 9;
            }

            resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);

            return resultado === parseInt(digitos.charAt(1), 10);
        }

        async function carregarCidadesPorUf(uf, cidadeNomeSelecionar = null, cidadeIdSelecionar = null) {
            const cidadeSelect = document.querySelector('#cidade_id');
            if (!cidadeSelect) return;

            if (!uf) {
                cidadeSelect.innerHTML = '<option value="">Selecione...</option>';
                return;
            }

            cidadeSelect.innerHTML = '<option value="">Carregando...</option>';

            const urlBase = @json(route('estados.cidades', ['uf' => '__UF__']));
            const resp = await fetch(urlBase.replace('__UF__', uf));
            const json = await resp.json();

            cidadeSelect.innerHTML = '<option value="">Selecione...</option>';

            const alvoNorm = cidadeNomeSelecionar ? normalizarTexto(cidadeNomeSelecionar) : null;
            const alvoId = cidadeIdSelecionar ? String(cidadeIdSelecionar) : null;

            json.forEach((cidade) => {
                const nomeOriginal = (cidade.nome || '').toString().trim();
                const nomeNorm = normalizarTexto(nomeOriginal);
                const isSelected = (alvoId && String(cidade.id) === alvoId) || (alvoNorm && nomeNorm === alvoNorm);

                const option = document.createElement('option');
                option.value = cidade.id;
                option.textContent = nomeOriginal;
                if (isSelected) {
                    option.selected = true;
                }
                cidadeSelect.appendChild(option);
            });
        }

        function mostrarErroDocumento(input, mensagem) {
            limparErroDocumento(input);
            input.style.borderColor = '#dc2626';

            const message = document.createElement('p');
            message.className = 'documento-error';
            message.style.color = '#dc2626';
            message.style.fontSize = '12px';
            message.style.marginTop = '4px';
            message.textContent = mensagem;
            input.parentNode?.appendChild(message);
        }

        function limparErroDocumento(input) {
            input.style.borderColor = '';
            input.parentNode?.querySelector('.documento-error')?.remove();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const estadoSelect = document.querySelector('#estado');
            const cidadeSelect = document.querySelector('#cidade_id');
            const cepInput = document.querySelector('#cep');
            const documentoInput = document.querySelector('#cliente-documento');
            const cpfHidden = document.querySelector('#cliente-cpf-hidden');
            const cnpjHidden = document.querySelector('#cliente-cnpj-hidden');
            const tipoPessoaInputs = document.querySelectorAll('input[name="tipo_pessoa"]');
            const nomeFantasiaWrapper = document.querySelector('#cliente-nome-fantasia-wrapper');
            const nomeFantasiaInput = document.querySelector('input[name="nome_fantasia"]');
            const razaoSocialLabel = document.querySelector('#cliente-razao-social-label');
            const documentoLabel = document.querySelector('#cliente-documento-label');
            const formCliente = documentoInput?.closest('form');
            let documentoDuplicado = false;

            if (!documentoInput || !cpfHidden || !cnpjHidden) {
                return;
            }

            function getTipoPessoa() {
                return document.querySelector('input[name="tipo_pessoa"]:checked')?.value || 'PJ';
            }

            function syncDocumentoHiddenFields() {
                const digits = (documentoInput.value || '').replace(/\D+/g, '');
                if (getTipoPessoa() === 'PF') {
                    cpfHidden.value = digits;
                    cnpjHidden.value = '';
                } else {
                    cnpjHidden.value = digits;
                    cpfHidden.value = '';
                }
            }

            function updateTipoPessoaUI() {
                const tipoPessoa = getTipoPessoa();
                const isPf = tipoPessoa === 'PF';

                documentoLabel.textContent = isPf ? 'CPF' : 'CNPJ';
                razaoSocialLabel.innerHTML = isPf ? 'Nome Completo' : 'Raz&atilde;o Social';
                nomeFantasiaWrapper?.classList.toggle('hidden', isPf);

                if (isPf && nomeFantasiaInput) {
                    nomeFantasiaInput.value = '';
                }

                documentoInput.placeholder = isPf ? '000.000.000-00' : '00.000.000/0000-00';
                documentoInput.value = isPf ? formatCpf(documentoInput.value) : formatCnpj(documentoInput.value);
                documentoInput.dataset.tipoPessoa = tipoPessoa;

                documentoDuplicado = false;
                limparErroDocumento(documentoInput);
                syncDocumentoHiddenFields();
            }

            estadoSelect?.addEventListener('change', async (event) => {
                await carregarCidadesPorUf(event.target.value);
            });

            (async function inicializarCidadePorUf() {
                if (!estadoSelect || !cidadeSelect) return;

                const ufInicial = estadoSelect.value;
                const cidadeInicialId = cidadeSelect.dataset.selectedId;
                const cidadeJaExisteNaLista = cidadeInicialId
                    ? Array.from(cidadeSelect.options).some((option) => option.value === String(cidadeInicialId))
                    : false;

                if (ufInicial && (cidadeSelect.options.length <= 1 || !cidadeJaExisteNaLista)) {
                    await carregarCidadesPorUf(ufInicial, null, cidadeInicialId);
                }
            })();

            cepInput?.addEventListener('blur', async function () {
                const cep = this.value.replace(/\D/g, '');
                if (cep.length !== 8) return;

                try {
                    const resp = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const json = await resp.json();

                    if (json.erro) return;

                    document.querySelector('#endereco').value = json.logradouro || '';
                    document.querySelector('#bairro').value = json.bairro || '';
                    const complementoInput = document.querySelector('input[name="complemento"]');
                    if (complementoInput) {
                        complementoInput.value = json.complemento || '';
                    }

                    const ufApi = json.uf;
                    const cidadeApi = (json.localidade || '').trim();

                    if (ufApi && estadoSelect) {
                        const exists = Array.from(estadoSelect.options).some((option) => option.value === ufApi);
                        if (!exists) {
                            const option = document.createElement('option');
                            option.value = ufApi;
                            option.textContent = ufApi;
                            estadoSelect.appendChild(option);
                        }

                        estadoSelect.value = ufApi;
                        await carregarCidadesPorUf(ufApi, cidadeApi);
                    }
                } catch (error) {
                    console.error('Erro ao buscar CEP', error);
                }
            });

            documentoInput.addEventListener('input', () => {
                documentoInput.value = getTipoPessoa() === 'PF'
                    ? formatCpf(documentoInput.value)
                    : formatCnpj(documentoInput.value);

                documentoDuplicado = false;
                limparErroDocumento(documentoInput);
                syncDocumentoHiddenFields();
            });

            documentoInput.addEventListener('blur', async () => {
                const digits = (documentoInput.value || '').replace(/\D+/g, '');
                const tipoPessoa = getTipoPessoa();

                syncDocumentoHiddenFields();

                if (digits === '') {
                    documentoDuplicado = false;
                    limparErroDocumento(documentoInput);
                    return;
                }

                if (tipoPessoa === 'PF') {
                    if (!cpfValido(digits)) {
                        documentoDuplicado = false;
                        mostrarErroDocumento(documentoInput, 'CPF inválido');
                        return;
                    }
                } else {
                    if (!cnpjValido(digits)) {
                        documentoDuplicado = false;
                        mostrarErroDocumento(documentoInput, 'CNPJ inválido');
                        return;
                    }
                }

                limparErroDocumento(documentoInput);

                const clienteId = documentoInput.dataset.clienteId || '';
                const baseUrl = @json(route($routePrefix.'.cnpj-exists', ['cnpj' => '__DOCUMENTO__']));
                const params = new URLSearchParams();
                params.set('tipo_pessoa', tipoPessoa);
                if (clienteId) {
                    params.set('ignore', clienteId);
                }

                try {
                    const resp = await fetch(`${baseUrl.replace('__DOCUMENTO__', encodeURIComponent(digits))}?${params.toString()}`);
                    const json = await resp.json();

                    if (json?.exists) {
                        documentoDuplicado = true;
                        mostrarErroDocumento(documentoInput, tipoPessoa === 'PF'
                            ? 'Já existe um cliente cadastrado com este CPF.'
                            : 'Já existe um cliente cadastrado com este CNPJ.');
                        return;
                    }

                    documentoDuplicado = false;
                    limparErroDocumento(documentoInput);
                } catch (error) {
                    console.error('Erro ao validar documento duplicado', error);
                }

                if (tipoPessoa !== 'PJ' || digits.length !== 14) {
                    return;
                }

                const consultaUrl = @json(route($routePrefix.'.consulta-cnpj', ['cnpj' => '__CNPJ__']))
                    .replace('__CNPJ__', encodeURIComponent(digits));

                try {
                    const resp = await fetch(consultaUrl);
                    const json = await resp.json();

                    if (json.error) {
                        return;
                    }

                    const razaoInput = document.querySelector('input[name="razao_social"]');
                    const fantasiaInput = document.querySelector('input[name="nome_fantasia"]');
                    const enderecoInput = document.querySelector('#endereco');
                    const bairroInput = document.querySelector('#bairro');
                    const complementoInput = document.querySelector('input[name="complemento"]');

                    if (razaoInput && json.razao_social) {
                        razaoInput.value = json.razao_social;
                    }
                    if (fantasiaInput && json.nome_fantasia) {
                        fantasiaInput.value = json.nome_fantasia;
                    }
                    if (cepInput && json.cep) {
                        const cepLimpo = json.cep.replace(/\D/g, '');
                        cepInput.value = cepLimpo.length === 8
                            ? cepLimpo.replace(/^(\d{5})(\d{3})$/, '$1-$2')
                            : json.cep;
                    }
                    if (enderecoInput && json.endereco) {
                        enderecoInput.value = json.endereco;
                    }
                    if (bairroInput && json.bairro) {
                        bairroInput.value = json.bairro;
                    }
                    if (complementoInput && json.complemento) {
                        complementoInput.value = json.complemento;
                    }

                    const ufApi = json.uf || null;
                    const cidadeApi = json.municipio || null;

                    if (ufApi && estadoSelect) {
                        const exists = Array.from(estadoSelect.options).some((option) => option.value === ufApi);
                        if (!exists) {
                            const option = document.createElement('option');
                            option.value = ufApi;
                            option.textContent = ufApi;
                            estadoSelect.appendChild(option);
                        }

                        estadoSelect.value = ufApi;
                        await carregarCidadesPorUf(ufApi, cidadeApi);
                    }
                } catch (error) {
                    console.error('Erro ao consultar CNPJ', error);
                }
            });

            tipoPessoaInputs.forEach((input) => {
                input.addEventListener('change', updateTipoPessoaUI);
            });

            formCliente?.addEventListener('submit', (event) => {
                syncDocumentoHiddenFields();

                if (!documentoDuplicado) {
                    return;
                }

                event.preventDefault();
                mostrarErroDocumento(documentoInput, getTipoPessoa() === 'PF'
                    ? 'Já existe um cliente cadastrado com este CPF.'
                    : 'Já existe um cliente cadastrado com este CNPJ.');
            });

            updateTipoPessoaUI();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const radios = Array.from(document.querySelectorAll('input[name="tipo_cliente"]'));
            const observacao = document.getElementById('observacao');
            const star = document.getElementById('obs-required-star');

            if (!radios.length || !observacao) {
                return;
            }

            const syncRequired = () => {
                const selecionado = radios.find((r) => r.checked)?.value;
                const required = selecionado === 'parceiro';
                observacao.required = required;
                if (star) {
                    star.classList.toggle('hidden', !required);
                }
            };

            radios.forEach((radio) => radio.addEventListener('change', syncRequired));
            syncRequired();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tipoInput = document.querySelector('input[name="tipo_cliente"]');
            const formDados = tipoInput ? tipoInput.closest('form') : null;
            const submitButton = document.getElementById('cliente-dados-submit');
            const observacao = document.getElementById('observacao');
            const radios = Array.from(document.querySelectorAll('input[name="tipo_cliente"]'));
            const tabsWrap = document.querySelector('[data-tabs="cliente"]');
            let validationAlertOpen = false;

            if (!formDados || !observacao || !radios.length) {
                return;
            }

            const ensureLocalSwal = () => {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    return Promise.resolve(window.Swal);
                }

                return new Promise((resolve) => {
                    let settled = false;
                    const finish = () => {
                        if (settled) return;
                        settled = true;
                        resolve(window.Swal && typeof window.Swal.fire === 'function' ? window.Swal : null);
                    };

                    let script = document.getElementById('swal-cdn-script');
                    if (!script) {
                        script = document.createElement('script');
                        script.id = 'swal-cdn-script';
                        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                        script.async = true;
                        document.head.appendChild(script);
                    }

                    script.addEventListener('load', finish, { once: true });
                    script.addEventListener('error', finish, { once: true });
                    window.setTimeout(finish, 2500);
                });
            };

            const getFieldMessage = (field) => {
                const name = String(field?.name || '');

                if (name === 'cidade_id') return 'Selecione a cidade do cliente.';
                if (name === 'documento_principal') return 'Informe o CPF ou CNPJ do cliente.';
                if (name === 'razao_social') return 'Informe o nome do cliente.';
                if (name === 'forma_pagamento') return 'Selecione a forma de pagamento.';
                if (name === 'vencimento_servicos') return 'Informe o vencimento dos serviços.';
                if (name === 'vendedor_id') return 'Selecione o vendedor responsável.';
                if (name === 'observacao') return 'Preencha o campo Observação para Cliente Parceiro.';
                if (name === 'tipo_pessoa') return 'Selecione se o cliente é PF ou PJ.';
                if (name === 'tipo_cliente') return 'Selecione se o cliente é Parceiro ou Final.';

                const id = field?.id ? String(field.id) : '';
                if (id) {
                    const byFor = document.querySelector(`label[for="${id}"]`);
                    const label = byFor?.textContent?.trim();
                    if (label) return `Preencha o campo ${label}.`;
                }

                const parentLabel = field?.closest('label')?.textContent?.trim();
                if (parentLabel) return `Preencha o campo ${parentLabel}.`;

                return 'Preencha todos os campos obrigatórios para continuar.';
            };

            const findMissingRequiredField = () => {
                const requiredFields = Array.from(formDados.querySelectorAll('[required]'));

                for (const field of requiredFields) {
                    if (!field || field.disabled) continue;

                    const type = String(field.type || '').toLowerCase();
                    const name = String(field.name || '');

                    if (type === 'radio' || type === 'checkbox') {
                        if (!name) continue;
                        const group = Array.from(formDados.querySelectorAll(`[name="${CSS.escape(name)}"]`));
                        const hasChecked = group.some((input) => !input.disabled && input.checked);
                        if (!hasChecked) return field;
                        continue;
                    }

                    const value = String(field.value ?? '').trim();
                    if (value === '') return field;
                }

                return null;
            };

            const showValidationError = async (field) => {
                if (validationAlertOpen) {
                    return;
                }

                const message = getFieldMessage(field);
                const swal = await ensureLocalSwal();

                if (swal) {
                    validationAlertOpen = true;
                    swal.fire({
                        title: 'Campo obrigatório',
                        icon: 'warning',
                        text: message,
                        confirmButtonText: 'OK',
                        returnFocus: false,
                        focusConfirm: false,
                        allowOutsideClick: true,
                        allowEscapeKey: true,
                        didClose: () => {
                            validationAlertOpen = false;
                            if (document.activeElement instanceof HTMLElement) {
                                document.activeElement.blur();
                            }
                        },
                        didDestroy: () => {
                            validationAlertOpen = false;
                            if (document.activeElement instanceof HTMLElement) {
                                document.activeElement.blur();
                            }
                        },
                    }).then(() => {
                        if (typeof field?.scrollIntoView === 'function') {
                            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }).catch(() => {
                        validationAlertOpen = false;
                    });
                    return;
                }

                validationAlertOpen = true;
                alert(message);
                validationAlertOpen = false;
                if (typeof field?.focus === 'function') {
                    field.focus();
                }
            };

            const validateDadosForm = () => {
                const missingRequired = findMissingRequiredField();
                if (missingRequired) {
                    showValidationError(missingRequired);
                    return false;
                }

                const tipoSelecionado = radios.find((r) => r.checked)?.value;
                const obsVazia = !observacao.value || observacao.value.trim() === '';

                if (tipoSelecionado === 'parceiro' && obsVazia) {
                    showValidationError(observacao);
                    return false;
                }

                return true;
            };

            formDados.addEventListener('submit', function (event) {
                if (!validateDadosForm()) {
                    event.preventDefault();
                }
            });

            tabsWrap?.querySelectorAll('[data-tab]').forEach((tabButton) => {
                tabButton.addEventListener('click', function (event) {
                    const targetTab = tabButton.dataset.tab;
                    const targetUrl = tabButton.dataset.tabUrl;

                    if (!targetTab || targetTab === 'dados' || targetUrl) {
                        return;
                    }

                    if (!validateDadosForm()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                }, true);
            });
        });
    </script>
@endsection
