@php
    $user = auth()->user();

    // default
    $layout = 'layouts.app';

    if ($user && optional($user->papel)->nome === 'Operacional') {
        $layout = 'layouts.operacional';
    }
@endphp

@extends($layout)

@section('content')

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

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">
                {{ $cliente->exists ? 'Editar Cliente' : 'Cadastrar Cliente' }}
            </h1>
        </div>

        <form method="POST"
              action="{{ $cliente->exists ? route('clientes.update', $cliente) : route('clientes.store') }}"
              class="bg-white rounded-xl shadow border p-6 space-y-6">

            @csrf
            @if($cliente->exists)
                @method('PUT')
            @endif

            {{-- LINHA 1 --}}
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm">CNPJ</label>
                    <input name="cnpj" value="{{ old('cnpj', $cliente->cnpj) }}"
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
                    <label class="text-sm">Ativo</label>
                    <div class="flex items-center gap-2 mt-1">
                        <input type="hidden" name="ativo" value="0">
                        <input type="checkbox" name="ativo" value="1"
                               {{ old('ativo', $cliente->ativo) ? 'checked' : '' }}
                               class="h-5 w-5 text-blue-600">
                    </div>
                </div>

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
            <div class="flex justify-end gap-3">
                <a href="{{ route('clientes.index') }}"
                   class="px-4 py-2 bg-gray-200 rounded-lg">Cancelar</a>

                <button class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    {{ $cliente->exists ? 'Salvar Alterações' : 'Cadastrar' }}
                </button>
            </div>
        </form>
    </div>

    {{-- jQuery + MÁSCARAS (CNPJ, CEP, Telefone) --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(function () {
            // CNPJ
            $('input[name="cnpj"]').mask('00.000.000/0000-00');

            // CEP
            $('#cep').mask('00000-000');

            // Telefone (celular/padrão BR)
            $('input[name="telefone"]').mask('(00) 00000-0000');
        });
    </script>

    {{-- SCRIPT UF → CIDADES (IBGE via sua API) --}}
    <script>
        document.querySelector('#estado').addEventListener('change', async (e) => {
            let uf = e.target.value;
            let cidadeSelect = document.querySelector('#cidade_id');

            cidadeSelect.innerHTML = '<option>Carregando...</option>';

            if (!uf) {
                cidadeSelect.innerHTML = '<option value="">Selecione...</option>';
                return;
            }

            let urlBase = "{{ url('master/estados') }}";
            let resp = await fetch(`${urlBase}/${uf}/cidades`);
            let json = await resp.json();

            cidadeSelect.innerHTML = '<option value="">Selecione...</option>';

            json.forEach(c => {
                cidadeSelect.innerHTML += `<option value="${c.id}">${c.nome}</option>`;
            });
        });
    </script>

    {{-- SCRIPT CEP → ViaCEP + IBGE --}}
    <script>
        document.querySelector('#cep').addEventListener('blur', async function () {
            let cep = this.value.replace(/\D/g, '');

            if (cep.length !== 8) return;

            try {
                let resp = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                let json = await resp.json();

                if (json.erro) return;

                document.querySelector('#endereco').value = json.logradouro || '';
                document.querySelector('#bairro').value   = json.bairro || '';

                let ufApi     = json.uf;
                let cidadeApi = (json.localidade || '').trim().toLowerCase();

                let estadoSelect = document.querySelector('#estado');
                let cidadeSelect = document.querySelector('#cidade_id');

                // Seleciona/cria UF
                if (ufApi) {
                    let found = false;
                    Array.from(estadoSelect.options).forEach(opt => {
                        if (opt.value === ufApi) {
                            found = true;
                        }
                    });

                    if (!found) {
                        let opt = document.createElement('option');
                        opt.value = ufApi;
                        opt.textContent = ufApi;
                        estadoSelect.appendChild(opt);
                    }

                    estadoSelect.value = ufApi;
                }

                // Carrega cidades e tenta marcar a correta
                if (ufApi) {
                    let urlBase = "{{ url('master/estados') }}";
                    let respCidades = await fetch(`${urlBase}/${ufApi}/cidades`);
                    let cidadesJson = await respCidades.json();

                    cidadeSelect.innerHTML = '<option value="">Selecione...</option>';

                    cidadesJson.forEach(c => {
                        let selected =
                            (c.nome || '').trim().toLowerCase() === cidadeApi ? 'selected' : '';
                        cidadeSelect.innerHTML +=
                            `<option value="${c.id}" ${selected}>${c.nome}</option>`;
                    });
                }

            } catch (e) {
                console.error('Erro ao buscar CEP', e);
            }
        });
    </script>

@endsection
