@extends('layouts.comercial')
@section('title', 'Gerar Apresentação')
@section('page-container', 'w-full p-0')

@section('content')
    <div class="w-full px-2 sm:px-3 md:px-4 py-2 md:py-3 space-y-4 md:space-y-6">

        <div>
            <a href="{{ route('comercial.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                &larr; Voltar ao Painel
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

        <section class="bg-emerald-600 rounded-2xl shadow border border-emerald-700 overflow-hidden">
            <div class="px-4 md:px-6 py-4 border-b border-emerald-700 bg-emerald-600">
                <h2 class="text-sm font-semibold text-white">1. Dados do Cliente</h2>
                <p class="text-xs text-emerald-100 mt-1">Preencha os dados do cliente manualmente.</p>
            </div>

            <form method="POST" action="{{ route('comercial.apresentacao.cliente.store') }}" class="p-4 md:p-6 space-y-5 bg-white" id="formApresentacaoCliente">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">CNPJ *</label>
                        <div class="mt-1 flex flex-col sm:flex-row gap-2">
                            <input id="cnpj" name="cnpj" type="text" required
                                   value="{{ old('cnpj', $draft['cnpj'] ?? '') }}"
                                   class="w-full sm:flex-1 rounded-xl border border-slate-200 text-sm px-3 py-2"
                                   placeholder="00.000.000/0000-00">
                            <button type="button" id="btnBuscarCnpj"
                                    class="w-full sm:w-auto rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 text-sm font-semibold whitespace-nowrap">
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

                <div class="pt-2 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <a href="{{ route('comercial.apresentacao.cancelar') }}"
                       class="w-full sm:w-auto text-center rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="w-full sm:w-auto rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold">
                        Próximo
                    </button>
                </div>
            </form>
        </section>
    </div>

    @push('scripts')
        <script>
            (function () {
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

                btnBuscar?.addEventListener('click', async () => {
                    clearMsg();
                    const raw = (cnpj?.value || '').trim();
                    const digits = raw.replace(/\D+/g, '');
                    if (!digits) return setMsg('err', 'Informe um CNPJ.');

                    btnBuscar.disabled = true;
                    btnBuscar.textContent = 'Buscando...';

                    try {
                        const url = @json(route('comercial.clientes.consulta-cnpj', ['cnpj' => '__CNPJ__'])).replace('__CNPJ__', encodeURIComponent(digits));
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
                        const json = await res.json().catch(() => ({}));

                        if (!res.ok) {
                            return setMsg('err', json?.error || 'Falha ao consultar CNPJ.');
                        }

                        if (razao && json?.razao_social) razao.value = json.razao_social;
                        if (telefone && json?.telefone) telefone.value = json.telefone;
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



