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
    @php($routePrefix = $routePrefix ?? 'clientes')

    {{-- MENSAGENS --}}
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

    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="mb-6">
            <a href="{{ route($routePrefix.'.index') }}"
               class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                <span class="text-base">←</span>
                Voltar
            </a>
            <div class="mt-4">

{{--                <h1 class="mt-3 text-3xl font-semibold text-slate-900">--}}
{{--                    {{ $cliente->exists ? 'Editar Cliente' : 'Cadastrar Cliente' }}--}}
{{--                </h1>--}}

            </div>
        </div>

        @if($cliente->exists)
            <div class="mb-6 w-full mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap gap-2" data-tabs="cliente">
                    <button type="button"
                            class="px-4 py-2 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-100"
                            data-tab="dados">
                        Dados do Cliente
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-100"
                            data-tab="parametros">
                        Parâmetros
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-100"
                            data-tab="esocial">
                        eSocial
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-100"
                            data-tab="forma-pagamento">
                        Forma de Pagamento
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-100"
                            data-tab="vendedor">
                        Vendedor
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
                              class="p-6 space-y-6">

                @csrf
                @if($cliente->exists)
                    @method('PUT')
                @endif

            {{-- ATIVO --}}
            <div class="flex items-center gap-3">
                <x-toggle-ativo
                    name="ativo"
                    :checked="(bool) old('ativo', $cliente->exists ? $cliente->ativo : 1)"
                    on-label="Ativo"
                    off-label="Inativo"
                />
            </div>

            {{-- LINHA 1 --}}
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm">CNPJ</label>
                    <input name="cnpj" value="{{ old('cnpj', $cliente->cnpj) }}"
                           data-cliente-id="{{ $cliente->exists ? $cliente->id : '' }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2">
                    @error('cnpj')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm">Razão Social</label>
                    <input name="razao_social" value="{{ old('razao_social', $cliente->razao_social) }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2">
                    @error('razao_social')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm">Nome Fantasia</label>
                    <input name="nome_fantasia" value="{{ old('nome_fantasia', $cliente->nome_fantasia) }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2">
                    @error('nome_fantasia')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- LINHA 2 --}}
            <div class="grid md:grid-cols-3 gap-4 items-center">
                <div>
                    <label class="text-sm">E-mail</label>
                    <input name="email" value="{{ old('email', $cliente->email) }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2">
                    @error('email')
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
                    <label class="text-sm">Contato</label>
                    <input name="contato" value="{{ old('contato', $cliente->contato) }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2">
                    @error('contato')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- BLOCO ENDEREÇO --}}
            <div class="border rounded-xl p-4 bg-gray-50">
                <h2 class="font-medium mb-4 text-gray-800">Endereço</h2>

                <div class="grid md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm">CEP</label>
                        <input id="cep" name="cep" value="{{ old('cep', $cliente->cep) }}"
                               class="w-full border-gray-300 rounded-lg px-3 py-2"
                               placeholder="Somente números">
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
                        <label class="text-sm">Endereço</label>
                        <input id="endereco" name="endereco" value="{{ old('endereco', $cliente->endereco) }}"
                               class="w-full border-gray-300 rounded-lg px-3 py-2">
                        @error('endereco')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm">Número</label>
                        <input name="numero" value="{{ old('numero', $cliente->numero) }}"
                               class="w-full border-gray-300 rounded-lg px-3 py-2">
                        @error('numero')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm">Bairro</label>
                        <input id="bairro" name="bairro" value="{{ old('bairro', $cliente->bairro) }}"
                               class="w-full border-gray-300 rounded-lg px-3 py-2">
                        @error('bairro')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-sm">Complemento</label>
                    <input name="complemento" value="{{ old('complemento', $cliente->complemento) }}"
                           class="w-full border-gray-300 rounded-lg px-3 py-2">
                    @error('complemento')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- BOTÕES --}}
            <div class="flex flex-wrap justify-end gap-3"><button class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-base font-semibold shadow-md shadow-blue-200">
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
                <div data-tab-panel="vendedor" data-tab-panel-root="cliente" class="hidden">
                    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
                        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b bg-slate-900 text-white">
                                <h1 class="text-lg font-semibold">Vendedor</h1>
                            </div>
                            <form method="POST"
                                  action="{{ route($routePrefix.'.update', $cliente) }}"
                                  class="p-6 space-y-6">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="update_vendedor" value="1">

                                <div>
                                    <label class="text-sm font-medium text-slate-700">Vendedor responsável</label>
                                    <select name="vendedor_id"
                                            class="w-full mt-2 border-slate-300 rounded-lg px-3 py-2">
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

                                <div class="flex justify-end">
                                    <button class="px-5 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                                        Salvar vendedor
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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
                const activeClasses = {
                    'dados': ['bg-blue-600', 'text-white'],
                    'parametros': ['bg-emerald-600', 'text-white'],
                    'esocial': ['bg-amber-600', 'text-white'],
                    'forma-pagamento': ['bg-indigo-600', 'text-white'],
                    'vendedor': ['bg-slate-900', 'text-white'],
                };

                tabs.forEach(btn => {
                    const active = btn.dataset.tab === tabName;
                    const classes = activeClasses[btn.dataset.tab] || ['bg-blue-600', 'text-white'];
                    btn.classList.remove('bg-blue-600', 'bg-emerald-600', 'bg-amber-600', 'bg-indigo-600', 'bg-slate-900', 'text-white');
                    if (active) {
                        btn.classList.add(...classes);
                    }
                    btn.classList.toggle('text-slate-600', !active);
                });
                panels.forEach(panel => {
                    if (!panel.dataset.tabPanel) return;
                    panel.classList.toggle('hidden', panel.dataset.tabPanel !== tabName);
                });
            }

            tabs.forEach(btn => {
                btn.addEventListener('click', () => activate(btn.dataset.tab));
            });

            const urlTab = new URLSearchParams(window.location.search).get('tab');
            const initialTab = tabs.some(btn => btn.dataset.tab === urlTab) ? urlTab : 'dados';
            activate(initialTab);
        })();
    </script>

    <script>
        $(function () {
            // CNPJ
            // $('input[name="cnpj"]').mask('00.000.000/0000-00');

            // CEP
            $('#cep').mask('00000-000');

            // Telefone (celular/padrão BR)
            $('input[name="telefone"]').mask('(00) 00000-0000');
        });
    </script>

    <script>

        function normalizarTexto(str) {
            if (!str) return '';
            return str
                .toString()
                .normalize('NFD')                     // quebra acentos
                .replace(/[\u0300-\u036f]/g, '')     // remove marcas de acento
                .replace(/\s+/g, ' ')                // colapsa espaços
                .trim()
                .toUpperCase();
        }

        async function carregarCidadesPorUf(uf, cidadeNomeSelecionar = null) {
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

            const alvoNorm = cidadeNomeSelecionar
                ? normalizarTexto(cidadeNomeSelecionar)
                : null;

            json.forEach(c => {
                const nomeOriginal = (c.nome || '').toString().trim();
                const nomeNorm     = normalizarTexto(nomeOriginal);

                const isSelected = alvoNorm && nomeNorm === alvoNorm;

                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = nomeOriginal;
                if (isSelected) {
                    opt.selected = true;
                }
                cidadeSelect.appendChild(opt);
            });
        }
        document.querySelector('#estado').addEventListener('change', async (e) => {
            const uf = e.target.value;
            await carregarCidadesPorUf(uf);
        });
        document.querySelector('#cep').addEventListener('blur', async function () {
            let cep = this.value.replace(/\D/g, '');

            if (cep.length !== 8) return;

            try {
                const resp = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const json = await resp.json();

                if (json.erro) return;

                document.querySelector('#endereco').value     = json.logradouro || '';
                document.querySelector('#bairro').value       = json.bairro || '';
                const complementoInput = document.querySelector('input[name="complemento"]');
                if (complementoInput) {
                    complementoInput.value = json.complemento || '';
                }

                const ufApi     = json.uf;
                const cidadeApi = (json.localidade || '').trim();

                const estadoSelect = document.querySelector('#estado');

                if (ufApi && estadoSelect) {
                    // garante que a UF exista no select
                    let found = Array.from(estadoSelect.options).some(opt => opt.value === ufApi);
                    if (!found) {
                        let opt = document.createElement('option');
                        opt.value = ufApi;
                        opt.textContent = ufApi;
                        estadoSelect.appendChild(opt);
                    }

                    estadoSelect.value = ufApi;
                    await carregarCidadesPorUf(ufApi, cidadeApi);
                }
            } catch (e) {
                console.error('Erro ao buscar CEP', e);
            }
        });
        document.querySelector('input[name="cnpj"]').addEventListener('blur', async function () {
            let cnpjLimpo = this.value.replace(/\D/g, '');

            // CNPJ vazio ou incompleto: não faz nada
            if (cnpjLimpo.length !== 14) {
                return;
            }

            // Monta URL da rota (substitui o placeholder pelo CNPJ limpo)
            let url = "{{ route($routePrefix.'.consulta-cnpj', ['cnpj' => 'CNPJ_PLACEHOLDER']) }}";
            url = url.replace('CNPJ_PLACEHOLDER', cnpjLimpo);

            const razaoInput      = document.querySelector('input[name="razao_social"]');
            const fantasiaInput   = document.querySelector('input[name="nome_fantasia"]');
            const cepInput        = document.querySelector('#cep');
            const enderecoInput   = document.querySelector('#endereco');
            const bairroInput     = document.querySelector('#bairro');
            const complInput      = document.querySelector('input[name="complemento"]');
            const estadoSelect    = document.querySelector('#estado');

            try {
                // opcional: poderia colocar um "carregando..." em algum lugar aqui

                const resp = await fetch(url);
                const json = await resp.json();

                if (json.error) {
                    console.warn('Erro na consulta CNPJ:', json.error);
                    return;
                }

                // Preenche campos básicos
                if (razaoInput && json.razao_social) {
                    razaoInput.value = json.razao_social;
                }
                if (fantasiaInput && json.nome_fantasia) {
                    fantasiaInput.value = json.nome_fantasia;
                }
                if (cepInput && json.cep) {
                    // normaliza CEP para 00000-000
                    let cepLimpo = json.cep.replace(/\D/g, '');
                    if (cepLimpo.length === 8) {
                        cepInput.value = cepLimpo.replace(/^(\d{5})(\d{3})$/, '$1-$2');
                    } else {
                        cepInput.value = json.cep;
                    }
                }
                if (enderecoInput && json.endereco) {
                    enderecoInput.value = json.endereco;
                }
                if (bairroInput && json.bairro) {
                    bairroInput.value = json.bairro;
                }
                if (complInput && json.complemento) {
                    complInput.value = json.complemento;
                }

                // UF + Cidade
                const ufApi     = json.uf || null;
                const cidadeApi = json.municipio || null;

                if (ufApi && estadoSelect) {
                    // garante option da UF
                    let found = Array.from(estadoSelect.options).some(opt => opt.value === ufApi);
                    if (!found) {
                        let opt = document.createElement('option');
                        opt.value = ufApi;
                        opt.textContent = ufApi;
                        estadoSelect.appendChild(opt);
                    }

                    estadoSelect.value = ufApi;

                    // Carrega cidades da UF e tenta selecionar pelo nome retornado
                    if (cidadeApi) {
                        await carregarCidadesPorUf(ufApi, cidadeApi);
                    } else {
                        await carregarCidadesPorUf(ufApi);
                    }
                }

                // OBS: se você quiser, aqui ainda pode disparar manualmente o blur do CEP
                // para “refinar” o endereço via ViaCEP:
                // if (cepInput && cepInput.value) {
                //     cepInput.dispatchEvent(new Event('blur'));
                // }

            } catch (e) {
                console.error('Erro ao consultar CNPJ', e);
            }
        });


    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // pega o primeiro input com name="cnpj" da página
            var cnpjInput = document.querySelector('input[name="cnpj"]');
            var formCliente = cnpjInput?.closest('form');
            var cnpjDuplicado = false;
            if (!cnpjInput) return;

            // máscara enquanto digita
            cnpjInput.addEventListener('input', function () {
                var v = cnpjInput.value.replace(/\D/g, '');   // só números
                v = v.slice(0, 14);                           // máximo 14 dígitos

                if (v.length > 12) {
                    // 00.000.000/0000-00
                    cnpjInput.value = v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})/, "$1.$2.$3/$4-$5");
                } else if (v.length > 8) {
                    // 00.000.000/0000
                    cnpjInput.value = v.replace(/(\d{2})(\d{3})(\d{3})(\d{1,4})/, "$1.$2.$3/$4");
                } else if (v.length > 5) {
                    // 00.000.000
                    cnpjInput.value = v.replace(/(\d{2})(\d{3})(\d{1,3})/, "$1.$2.$3");
                } else if (v.length > 2) {
                    // 00.000
                    cnpjInput.value = v.replace(/(\d{2})(\d{1,3})/, "$1.$2");
                } else {
                    cnpjInput.value = v;
                }
            });

            // validação ao sair do campo
            cnpjInput.addEventListener('blur', async function () {
                var cnpjLimpo = cnpjInput.value.replace(/\D/g, '');

                if (cnpjLimpo === '') {
                    limparErroCNPJ(cnpjInput);
                    return;
                }

                if (!cnpjValido(cnpjLimpo)) {
                    mostrarErroCNPJ(cnpjInput, 'CNPJ inválido');
                    cnpjDuplicado = false;
                } else {
                    limparErroCNPJ(cnpjInput);
                    const clienteId = cnpjInput.dataset.clienteId || '';
                    const baseUrl = @json(route($routePrefix.'.cnpj-exists', ['cnpj' => '__CNPJ__']));
                    const url = baseUrl
                        .replace('__CNPJ__', encodeURIComponent(cnpjLimpo))
                        + (clienteId ? `?ignore=${encodeURIComponent(clienteId)}` : '');
                    try {
                        const resp = await fetch(url);
                        const json = await resp.json();
                        if (json?.exists) {
                            cnpjDuplicado = true;
                            mostrarErroCNPJ(cnpjInput, 'Já existe um cliente cadastrado com este CNPJ.');
                        } else {
                            cnpjDuplicado = false;
                            limparErroCNPJ(cnpjInput);
                        }
                    } catch (e) {
                        console.error('Erro ao validar CNPJ duplicado', e);
                    }
                }
            });

            formCliente?.addEventListener('submit', (e) => {
                if (cnpjDuplicado) {
                    e.preventDefault();
                    mostrarErroCNPJ(cnpjInput, 'Já existe um cliente cadastrado com este CNPJ.');
                }
            });
        });

        // valida CNPJ (algoritmo padrão)
        function cnpjValido(cnpj) {
            if (!cnpj || cnpj.length !== 14) return false;

            // elimina sequências como 00.000.000/0000-00, 11..., etc.
            if (/^(\d)\1{13}$/.test(cnpj)) return false;

            var tamanho = 12;
            var numeros = cnpj.substring(0, tamanho);
            var digitos = cnpj.substring(tamanho);
            var soma = 0;
            var pos = tamanho - 7;

            for (var i = tamanho; i >= 1; i--) {
                soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
                if (pos < 2) pos = 9;
            }

            var resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            if (resultado !== parseInt(digitos.charAt(0))) return false;

            tamanho = 13;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;

            for (var j = tamanho; j >= 1; j--) {
                soma += parseInt(numeros.charAt(tamanho - j)) * pos--;
                if (pos < 2) pos = 9;
            }

            resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            if (resultado !== parseInt(digitos.charAt(1))) return false;

            return true;
        }

        // mostra mensagem de erro logo abaixo do input
        function mostrarErroCNPJ(input, mensagem) {
            limparErroCNPJ(input);

            input.style.borderColor = '#dc2626'; // vermelho
            var p = document.createElement('p');
            p.className = 'cnpj-error';
            p.style.color = '#dc2626';
            p.style.fontSize = '12px';
            p.style.marginTop = '4px';
            p.textContent = mensagem;

            if (input.parentNode) {
                input.parentNode.appendChild(p);
            }
        }

        // remove mensagem de erro e estilo
        function limparErroCNPJ(input) {
            input.style.borderColor = '';

            if (!input.parentNode) return;
            var erro = input.parentNode.querySelector('.cnpj-error');
            if (erro) {
                erro.remove();
            }
        }
    </script>

@endsection

