@extends('layouts.comercial')
@section('title', 'Gerar Apresentação')

@section('content')
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">

        <div>
            <a href="{{ route('comercial.dashboard') }}"
               class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800">
                ← Voltar ao Painel
            </a>
        </div>

        <header class="space-y-1">
            <h1 class="text-2xl font-semibold text-slate-900">Gerar Apresentação</h1>
            <p class="text-sm text-slate-500">Informe os dados do cliente para montar a apresentação.</p>
        </header>

        @if (session('ok'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        @if (session('erro'))
            <div class="rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('erro') }}
            </div>
        @endif

        <section class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b bg-slate-50">
                <h2 class="text-sm font-semibold text-slate-800">1. Dados do Cliente</h2>
                <p class="text-xs text-slate-500 mt-1">Selecione uma proposta ou informe manualmente.</p>
            </div>

            <form method="POST" action="{{ route('comercial.apresentacao.cliente.store') }}" class="p-6 space-y-5" id="formApresentacaoCliente">
                @csrf

                <div>
                    <label class="text-xs font-semibold text-slate-600">Selecionar proposta (opcional)</label>
                    <select id="proposta_id" name="proposta_id"
                            class="w-full mt-1 rounded-xl border border-slate-200 text-sm px-3 py-2">
                        <option value="">— Informar manualmente —</option>
                        @foreach($propostas as $p)
                            <option value="{{ $p->id }}"
                                    @selected((string) old('proposta_id', $draft['proposta_id'] ?? '') === (string) $p->id)
                                    data-razao="{{ e($p->cliente?->razao_social ?? '') }}"
                                    data-cnpj="{{ e($p->cliente?->cnpj ?? '') }}"
                                    data-telefone="{{ e($p->cliente?->telefone ?? '') }}">
                                #{{ $p->id }} — {{ $p->codigo ?? 'Proposta' }} — {{ $p->cliente?->razao_social ?? '—' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Ao selecionar, os campos abaixo serão preenchidos automaticamente (você pode editar).</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">CNPJ *</label>
                        <div class="mt-1 flex gap-2">
                            <input id="cnpj" name="cnpj" type="text" required
                                   value="{{ old('cnpj', $draft['cnpj'] ?? '') }}"
                                   class="flex-1 rounded-xl border border-slate-200 text-sm px-3 py-2"
                                   placeholder="00.000.000/0000-00">
                            <button type="button" id="btnBuscarCnpj"
                                    class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 text-sm font-semibold">
                                Buscar
                            </button>
                        </div>
                        <p id="cnpjMsg" class="text-[11px] text-slate-500 mt-1 hidden"></p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Razão Social *</label>
                        <input id="razao_social" name="razao_social" type="text" required
                               value="{{ old('razao_social', $draft['razao_social'] ?? '') }}"
                               class="w-full mt-1 rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="Ex: Empresa LTDA">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Nome do Contato *</label>
                        <input id="contato" name="contato" type="text" required
                               value="{{ old('contato', $draft['contato'] ?? '') }}"
                               class="w-full mt-1 rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="Ex: João Silva">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Telefone *</label>
                        <input id="telefone" name="telefone" type="text" required
                               value="{{ old('telefone', $draft['telefone'] ?? '') }}"
                               class="w-full mt-1 rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="(11) 99999-9999">
                    </div>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <a href="{{ route('comercial.apresentacao.cancelar') }}"
                       class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold">
                        Próximo
                    </button>
                </div>
            </form>
        </section>
    </div>

    @push('scripts')
        <script>
            (function () {
                const propostaSelect = document.getElementById('proposta_id');
                const cnpj = document.getElementById('cnpj');
                const razao = document.getElementById('razao_social');
                const telefone = document.getElementById('telefone');

                const btnBuscar = document.getElementById('btnBuscarCnpj');
                const cnpjMsg = document.getElementById('cnpjMsg');

                function setMsg(type, text) {
                    if (!cnpjMsg) return;
                    cnpjMsg.classList.remove('hidden');
                    cnpjMsg.className = 'text-[11px] mt-1';
                    cnpjMsg.classList.add(type === 'err' ? 'text-red-600' : 'text-slate-500');
                    cnpjMsg.textContent = text;
                }

                function clearMsg() {
                    cnpjMsg?.classList.add('hidden');
                }

                propostaSelect?.addEventListener('change', () => {
                    const opt = propostaSelect.selectedOptions?.[0];
                    if (!opt || !opt.value) return;

                    if (cnpj) cnpj.value = opt.dataset.cnpj || '';
                    if (razao) razao.value = opt.dataset.razao || '';
                    if (telefone) telefone.value = opt.dataset.telefone || '';
                });

                btnBuscar?.addEventListener('click', async () => {
                    clearMsg();
                    const raw = (cnpj?.value || '').trim();
                    const digits = raw.replace(/\D+/g, '');
                    if (!digits) return setMsg('err', 'Informe um CNPJ.');

                    btnBuscar.disabled = true;
                    btnBuscar.textContent = 'Buscando...';

                    try {
                        const url = @json(route('clientes.consulta-cnpj', ['cnpj' => '__CNPJ__'])).replace('__CNPJ__', encodeURIComponent(digits));
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
                        const json = await res.json().catch(() => ({}));

                        if (!res.ok) {
                            return setMsg('err', json?.error || 'Falha ao consultar CNPJ.');
                        }

                        if (razao && json?.razao_social) razao.value = json.razao_social;
                        setMsg('ok', 'Dados preenchidos com sucesso.');
                    } catch (e) {
                        console.error(e);
                        setMsg('err', 'Falha ao consultar CNPJ.');
                    } finally {
                        btnBuscar.disabled = false;
                        btnBuscar.textContent = 'Buscar';
                    }
                });
            })();
        </script>
    @endpush
@endsection

