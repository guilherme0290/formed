@extends('layouts.master')
@section('title', 'Dados da Empresa')

@section('content')
    <div class="max-w-4xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div>
            <a href="{{ route('master.dashboard') }}"
               class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800">
                ← Voltar ao Painel
            </a>
        </div>

        @php($activeTab = request()->query('tab', 'dados'))

        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b bg-indigo-700 text-white">
                <h1 class="text-lg font-semibold">Dados da Empresa</h1>
                <p class="text-xs text-indigo-100 mt-1">Atualize as informações cadastrais da empresa.</p>
            </div>

            <div class="p-6 space-y-6">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('master.empresa.edit', ['tab' => 'dados']) }}"
                       class="px-4 py-2 rounded-xl text-sm font-semibold border {{ $activeTab === 'dados' ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                        Dados da empresa
                    </a>
                    <a href="{{ route('master.empresa.edit', ['tab' => 'unidades']) }}"
                       class="px-4 py-2 rounded-xl text-sm font-semibold border {{ $activeTab === 'unidades' ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                        Cl&iacute;nicas credenciadas</a>
                </div>

                @if (session('ok'))
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                        {{ session('ok') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
                        <div class="font-semibold mb-1">Verifique os campos</div>
                        <ul class="list-disc ml-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($activeTab === 'dados')
                    <form method="POST" action="{{ route('master.empresa.update') }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <label class="inline-flex flex-col items-start gap-2 cursor-pointer">
                            <input type="checkbox" name="ativo" value="1" class="sr-only peer"
                                @checked(old('ativo', $empresa->ativo))>
                            <div class="relative w-9 h-5 bg-slate-300 rounded-full transition-colors peer-checked:bg-emerald-600
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                    after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-transform
                                    peer-checked:after:translate-x-4"></div>
                            <span class="select-none text-sm font-medium text-slate-700">Empresa ativa</span>
                        </label>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Nome *</label>
                                <input type="text" name="nome" value="{{ old('nome', $empresa->nome) }}"
                                       class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                       required>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">CNPJ</label>
                                <input type="text" name="cnpj" value="{{ old('cnpj', $empresa->cnpj) }}"
                                       class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 js-cnpj"
                                       placeholder="00.000.000/0000-00">
                                <p id="cnpjMsg" class="text-[11px] text-slate-500 mt-1 hidden"></p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">E-mail</label>
                                <input type="email" name="email" value="{{ old('email', $empresa->email) }}"
                                       class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Telefone</label>
                                <input type="text" name="telefone" value="{{ old('telefone', $empresa->telefone) }}"
                                       class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 js-telefone"
                                       placeholder="(00) 00000-0000">
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-600">Endereço</label>
                            <input type="text" name="endereco" value="{{ old('endereco', $empresa->endereco) }}"
                                   class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                   placeholder="Rua, número, bairro">
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Estado (UF)</label>
                                <select id="estado"
                                        class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                                    <option value="">Selecione...</option>
                                    @foreach($estados as $estado)
                                        <option
                                            value="{{ $estado->uf }}" @selected(old('estado', $ufSelecionada) === $estado->uf)>
                                            {{ $estado->uf }} - {{ $estado->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Cidade</label>
                                <select id="cidade_id" name="cidade_id"
                                        class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                                    <option value="">Selecione...</option>
                                    @foreach($cidadesDoEstado as $cid)
                                        <option
                                            value="{{ $cid->id }}" @selected(old('cidade_id', $empresa->cidade_id) == $cid->id)>
                                            {{ $cid->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        <div class="pt-4 flex flex-wrap items-center justify-end gap-2">
                            <a href="{{ route('master.dashboard') }}"
                               class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                Cancelar
                            </a>
                            <button type="submit"
                                    class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm">
                                Salvar alterações
                            </button>
                        </div>
                    </form>
                @endif

                @if ($activeTab === 'unidades')
                    <div class="space-y-6">
                        <div class="rounded-2xl border border-slate-200 overflow-hidden">
                            <div
                                class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Cl&iacute;nicas credenciadas</h2>
                                    <span class="text-xs text-slate-500">{{ $unidades->count() }} unidade(s)</span>
                                </div>
                                <a href="{{ route('master.empresa.unidades.create') }}"
                                   class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm">
                                    + Nova unidade
                                </a>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @forelse($unidades as $unidade)
                                    <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <div class="font-semibold text-slate-800">{{ $unidade->nome }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $unidade->endereco ?? 'Endereço não informado' }}
                                            </div>
                                            <div class="text-xs text-slate-500 mt-1">
                                                {{ $unidade->telefone ?? 'Telefone não informado' }}
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs px-2 py-1 rounded-full {{ $unidade->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $unidade->ativo ? 'Ativa' : 'Inativa' }}
                                            </span>
                                            <a href="{{ route('master.empresa.unidades.edit', $unidade) }}"
                                               class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Editar
                                            </a>
                                            <form method="POST"
                                                  action="{{ route('master.empresa.unidades.destroy', $unidade) }}"
                                                  onsubmit="return confirm('Deseja remover esta unidade cl&iacute;nica?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-5 py-6 text-sm text-slate-500">Nenhuma unidade cadastrada.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(function () {
            $('.js-cnpj').mask('00.000.000/0000-00');
            $('.js-telefone').mask('(00) 00000-0000');
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
                const nomeNorm = normalizarTexto(nomeOriginal);
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

        document.querySelector('#estado')?.addEventListener('change', async (e) => {
            const uf = e.target.value;
            await carregarCidadesPorUf(uf);
        });

        const cnpjInput = document.querySelector('input[name="cnpj"]');
        const enderecoInput = document.querySelector('input[name="endereco"]');
        const estadoSelect = document.getElementById('estado');
        const cnpjMsg = document.getElementById('cnpjMsg');

        function setCnpjMsg(type, text) {
            if (!cnpjMsg) return;
            cnpjMsg.classList.remove('hidden');
            cnpjMsg.className = 'text-[11px] mt-1';
            if (type === 'err') {
                cnpjMsg.classList.add('text-rose-600');
            } else if (type === 'ok') {
                cnpjMsg.classList.add('text-emerald-600');
            } else {
                cnpjMsg.classList.add('text-slate-500');
            }
            cnpjMsg.textContent = text;
        }

        async function consultarCnpjEmpresa(cnpj) {
            const baseUrl = @json(route('clientes.consulta-cnpj', ['cnpj' => '__CNPJ__']));
            const resp = await fetch(baseUrl.replace('__CNPJ__', encodeURIComponent(cnpj)));
            let json = null;
            try {
                json = await resp.json();
            } catch (_) {
                json = null;
            }
            return json;
        }

        cnpjInput?.addEventListener('blur', async () => {
            const digits = (cnpjInput.value || '').replace(/\D/g, '');
            if (digits.length !== 14) return;

            setCnpjMsg('info', 'Buscando CNPJ...');

            try {
                const data = await consultarCnpjEmpresa(digits);
                if (!data || data.error) {
                    setCnpjMsg('err', (data && data.error) ? data.error : 'Falha ao consultar CNPJ.');
                    return;
                }

                if (enderecoInput) {
                    const partes = [data.endereco, data.bairro, data.complemento].filter(Boolean);
                    if (partes.length) enderecoInput.value = partes.join(' - ');
                }

                if (estadoSelect && data.uf) {
                    estadoSelect.value = data.uf;
                    await carregarCidadesPorUf(data.uf, data.municipio || null);
                }

                setCnpjMsg('ok', 'CNPJ encontrado.');
            } catch (e) {
                setCnpjMsg('err', 'Falha ao consultar CNPJ.');
            }
        });
    </script>
@endsection
