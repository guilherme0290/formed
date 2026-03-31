@extends('layouts.comercial')
@section('title', isset($proposta) && $proposta ? 'Editar Proposta Rápida' : 'Nova Proposta Rápida')

@section('content')
    @php
        $isEdit = isset($proposta) && $proposta;
        $propostaBase = $propostaBase ?? null;
        $fonteProposta = $isEdit ? $proposta : $propostaBase;
        $duplicandoProposta = !$isEdit && $propostaBase;
        $clienteSelecionadoId = data_get($clienteSelecionado, 'id');
        $clienteInicial = old('cliente_existente_id', $clienteSelecionadoId ?? ($isEdit ? ($proposta->cliente_id ?? null) : null));
        $modoPadrao = $clienteInicial ? 'existente' : (($clienteModoDuplicacao === 'novo') ? 'novo' : 'existente');
        $modoInicial = old('cliente_modo', $modoPadrao);
        $prazoDiasInicial = (int) old('prazo_dias', $fonteProposta->prazo_dias ?? 7);
        $dataEmissaoBase = optional($fonteProposta->created_at ?? now())->format('Y-m-d');
        $mostrarResumoFinanceiro = old('mostrar_resumo_financeiro', $fonteProposta->mostrar_resumo_financeiro ?? true);
        $telefoneComercial = preg_replace('/\D+/', '', (string) ($empresa?->vendedor?->telefone ?? ''));
        $telefoneComercialFormatado = match (strlen($telefoneComercial)) {
            11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefoneComercial),
            10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefoneComercial),
            default => ($empresa?->vendedor?->telefone ?? '—'),
        };
        $mensagemErroDesconto = 'O desconto informado ultrapassa o limite liberado para este vendedor.';
        $mostrarPopupErroDesconto = $errors->first('desconto_percentual') === $mensagemErroDesconto;
        $errosFormulario = collect($errors->all())->reject(fn ($error) => $error === $mensagemErroDesconto)->values();

        $initialItems = old('items_payload');
        if (is_string($initialItems) && $initialItems !== '') {
            $decodedItems = json_decode($initialItems, true);
            $initialItems = is_array($decodedItems) ? $decodedItems : [];
        } else {
            $initialItems = $fonteProposta
                ? $fonteProposta->itens->map(function ($item) {
                    return [
                        'categoria' => strtoupper((string) data_get($item->meta, 'categoria', $item->tipo)),
                        'origem_id' => data_get($item->meta, 'origem_id'),
                        'nome' => $item->nome,
                        'descricao' => $item->descricao,
                        'quantidade' => (int) $item->quantidade,
                        'valor_unitario' => (float) $item->valor_unitario,
                    ];
                })->values()->all()
                : [];
        }

        $clientesJson = $clientes->values()->all();
        $catalogoJson = $catalogo;
    @endphp

    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex justify-end">
            <a href="{{ route('comercial.propostas.rapidas.index') }}"
               class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <span>&larr;</span>
                <span>Voltar</span>
            </a>
        </div>

        <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
            <div class="bg-[linear-gradient(135deg,#0f172a_0%,#1d4ed8_52%,#38bdf8_100%)] px-6 py-7 text-white sm:px-8">
                <div class="grid gap-6 lg:grid-cols-[1.25fr,0.75fr] lg:items-center">
                    <div class="max-w-2xl">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-blue-100/80">Comercial</div>
                        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                            {{ $isEdit ? 'Editar proposta' : ($duplicandoProposta ? 'Duplicar proposta' : 'Montar proposta') }}
                        </h1>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-blue-50/85">
                            {{ $duplicandoProposta
                                ? 'Os itens e condições foram copiados da proposta original. Agora escolha o novo cliente para finalizar a duplicação.'
                                : 'Cadastre ou selecione o cliente, monte os itens e feche a proposta em um único fluxo.' }}
                        </p>
                    </div>

                    <div class="flex h-full items-center justify-end">
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-right text-white shadow-sm backdrop-blur-sm">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-100/80">Proposta comercial</div>
                            <div class="mt-1 text-sm font-semibold text-white">
                                Preencha os dados e finalize abaixo
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($errosFormulario->isNotEmpty())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <div class="font-semibold">Corrija os campos obrigatórios:</div>
                <ul class="mt-2 list-disc pl-5 space-y-1">
                    @foreach($errosFormulario as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($duplicandoProposta)
            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                Duplicando a proposta <strong>{{ $propostaBase->codigo ?? ('#' . $propostaBase->id) }}</strong>. O cliente original não será reaproveitado.
            </div>
        @endif

        <form method="POST"
              action="{{ $isEdit ? route('comercial.propostas.rapidas.update', $proposta) : route('comercial.propostas.rapidas.store') }}"
              class="space-y-6"
              x-data="propostaRapidaForm({
                    clientes: @js($clientesJson),
                    catalogo: @js($catalogoJson),
                    empresa: @js(['razao_social' => $empresa->nome ?? 'FORMED', 'cnpj' => $empresa->cnpj, 'endereco' => $empresa->endereco]),
                    clienteInicialId: @js((string) $clienteInicial),
                    modoInicial: @js($modoInicial),
                    itensIniciais: @js($initialItems),
                    descontoInicial: @js((string) old('desconto_percentual', $fonteProposta->desconto_percentual ?? 0)),
                    prazoDiasInicial: @js((string) $prazoDiasInicial),
                    dataEmissaoBase: @js($dataEmissaoBase),
                    descontoMaximo: @js((float) $descontoMaximo),
                    consultaExistsUrl: @js(route('comercial.clientes.cnpj-exists', ['cnpj' => '__CNPJ__'])),
                    consultaCnpjUrl: @js(route('comercial.clientes.consulta-cnpj', ['cnpj' => '__CNPJ__'])),
              })">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <input type="hidden" name="items_payload" x-model="itemsPayload">

            <div class="space-y-6">
                <section class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-blue-700 bg-blue-600 px-6 py-4 text-white">
                        <h2 class="text-sm font-bold uppercase tracking-[0.22em] text-white">Cliente</h2>
                        <p class="mt-1 text-sm text-blue-100">Escolha um cliente já cadastrado ou abra um cadastro novo diretamente daqui.</p>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex gap-2 rounded-2xl bg-slate-100 p-1">
                                <label class="cursor-pointer rounded-xl px-4 py-2 text-sm font-semibold transition" :class="clienteModo === 'novo' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">
                                    <input type="radio" name="cliente_modo" value="novo" class="hidden" x-model="clienteModo">
                                    Novo cliente
                                </label>
                                <label class="cursor-pointer rounded-xl px-4 py-2 text-sm font-semibold transition" :class="clienteModo === 'existente' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">
                                    <input type="radio" name="cliente_modo" value="existente" class="hidden" x-model="clienteModo">
                                    Cliente cadastrado
                                </label>
                            </div>
                        </div>

                        <div x-show="clienteModo === 'existente'" x-cloak class="grid gap-4">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Cliente cadastrado</label>
                                <select name="cliente_existente_id"
                                        x-model="clienteExistenteId"
                                        class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                    <option value="">Selecione...</option>
                                    <template x-for="cliente in clientesFiltrados()" :key="cliente.id">
                                        <option :value="String(cliente.id)" x-text="cliente.razao_social + ' - ' + (cliente.documento_principal || '')"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="lg:col-span-2 grid gap-4 md:grid-cols-2" x-show="clienteSelecionado()">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Endereço</label>
                                    <input type="text"
                                           :value="clienteSelecionado()?.endereco || ''"
                                           readonly
                                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Telefone</label>
                                    <input type="text"
                                           :value="clienteSelecionado()?.telefone || ''"
                                           readonly
                                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-xs font-semibold text-slate-600">E-mail</label>
                                    <input type="text"
                                           :value="clienteSelecionado()?.email || ''"
                                           readonly
                                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                </div>
                            </div>
                        </div>

                        <div x-show="clienteModo === 'novo'" x-cloak class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">CNPJ</label>
                                <input type="text"
                                       name="novo_cnpj"
                                       x-model="novoCliente.cnpj"
                                       @blur="consultarCnpj()"
                                       class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"
                                       placeholder="00.000.000/0000-00">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs font-semibold text-slate-600">Razão social</label>
                                <input type="text"
                                       name="novo_razao_social"
                                       x-model="novoCliente.razao_social"
                                       class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"
                                       placeholder="Razão social">
                            </div>
                            <div class="md:col-span-3">
                                <label class="text-xs font-semibold text-slate-600">Endereço</label>
                                <input type="text"
                                       name="novo_endereco"
                                       x-model="novoCliente.endereco"
                                       class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"
                                       placeholder="Endereço da empresa">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Telefone</label>
                                <input type="text"
                                       name="novo_telefone"
                                       x-model="novoCliente.telefone"
                                       class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"
                                       placeholder="(00) 00000-0000">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs font-semibold text-slate-600">E-mail</label>
                                <input type="email"
                                       name="novo_email"
                                       x-model="novoCliente.email"
                                       class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"
                                       placeholder="contato@empresa.com.br">
                            </div>
                            <div class="md:col-span-3" x-show="clienteLookupAviso">
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800" x-text="clienteLookupAviso"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">F</div>
                            <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Contratada</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-lg font-bold text-slate-900" x-text="empresa.razao_social || 'FORMED'"></div>
                            <div class="text-sm text-slate-600" x-text="formatDocumento(empresa.cnpj) || '—'"></div>
                            <div class="text-sm text-slate-600">
                                <span class="font-semibold text-slate-700">Comercial Responsável:</span>
                                <span>{{ $empresa?->vendedor?->name ?? '—' }}</span>
                            </div>
                            <div class="text-sm text-slate-600">
                                <span class="font-semibold text-slate-700">Telefone:</span>
                                <span>{{ $telefoneComercialFormatado }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">C</div>
                            <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Contratante</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-lg font-bold text-slate-900" x-text="contratanteAtual().razao_social || '—'"></div>
                            <div class="text-sm text-slate-600" x-text="contratanteAtual().documento || '—'"></div>
                            <div class="text-sm text-slate-600" x-text="contratanteAtual().endereco || '—'"></div>
                            <div class="text-sm text-slate-600" x-text="contratanteAtual().telefone || '—'"></div>
                            <div class="text-sm text-slate-600" x-text="contratanteAtual().email || '—'"></div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-blue-700 bg-blue-600 px-6 py-4 text-white">
                        <h2 class="text-sm font-bold uppercase tracking-[0.22em] text-white">Catálogo</h2>
                        <p class="mt-1 text-sm text-blue-100">Escolha serviços, treinamentos ou exames e monte a proposta por seleção direta.</p>
                    </div>
                    <div class="p-6">
                        <div class="rounded-[24px] border border-blue-100 bg-blue-50/40 p-4">
                            <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-4">
                                <button type="button"
                                        @click="catalogoTab = 'servicos'"
                                        class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                        :class="catalogoTab === 'servicos' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'">
                                    Serviços
                                </button>
                                <button type="button"
                                        @click="catalogoTab = 'treinamentos'"
                                        class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                        :class="catalogoTab === 'treinamentos' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'">
                                    Treinamentos
                                </button>
                                <button type="button"
                                        @click="catalogoTab = 'exames'"
                                        class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                        :class="catalogoTab === 'exames' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'">
                                    ASO
                                </button>
                            </div>

                            <div x-show="catalogoTab === 'servicos'" x-cloak class="pt-5 space-y-4">
                                <div class="text-sm font-semibold text-slate-800">Serviços</div>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                    <template x-for="item in catalogo.servicos || []" :key="'servico-' + (item.origem_id || item.nome)">
                                        <button type="button"
                                                @click="adicionarItem(item)"
                                                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm transition"
                                                :class="itemFoiAdicionado(item) ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : botaoCatalogoClasse(item.nome, 'servico')">
                                            <div class="flex items-center justify-between gap-3">
                                                <span x-text="'+ ' + item.nome"></span>
                                                <span x-show="itemFoiAdicionado(item)"
                                                      x-transition.opacity.duration.250ms
                                                      class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">
                                                    Adicionado
                                                </span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div x-show="catalogoTab === 'treinamentos'" x-cloak class="pt-5 space-y-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-slate-800">Treinamentos</div>
                                    <button type="button"
                                            @click="abrirModalPacote('treinamentos')"
                                            class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">
                                        + Pacote de Treinamentos
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                    <template x-for="item in catalogo.treinamentos || []" :key="'treinamento-' + (item.origem_id || item.nome)">
                                        <button type="button"
                                                @click="adicionarItem(item)"
                                                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition"
                                                :class="itemFoiAdicionado(item) ? 'border-emerald-300 bg-emerald-50' : 'hover:border-blue-200 hover:bg-slate-50'">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="font-semibold text-slate-800" x-text="nomePrincipalTreinamento(item.nome)"></div>
                                                    <div class="text-xs text-slate-500" x-text="descricaoSecundariaTreinamento(item)"></div>
                                                </div>
                                                <span x-show="itemFoiAdicionado(item)"
                                                      x-transition.opacity.duration.250ms
                                                      class="inline-flex shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">
                                                    Adicionado
                                                </span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div x-show="catalogoTab === 'exames'" x-cloak class="pt-5 space-y-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-slate-800">Exames</div>
                                    <button type="button"
                                            @click="abrirModalPacote('exames')"
                                            class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-100">
                                        + Grupo de Exames
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                    <template x-for="item in catalogo.exames || []" :key="'exame-' + (item.origem_id || item.nome)">
                                        <button type="button"
                                                @click="adicionarItem(item)"
                                                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition"
                                                :class="itemFoiAdicionado(item) ? 'border-emerald-300 bg-emerald-50' : 'hover:border-blue-200 hover:bg-slate-50'">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="font-semibold text-slate-800" x-text="item.nome"></div>
                                                    <div class="text-xs text-slate-500" x-text="'#' + (item.origem_id || '—') + ' — ' + formatMoney(item.valor_unitario || 0)"></div>
                                                </div>
                                                <span x-show="itemFoiAdicionado(item)"
                                                      x-transition.opacity.duration.250ms
                                                      class="inline-flex shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">
                                                    Adicionado
                                                </span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-blue-700 bg-blue-600 px-6 py-4 text-white">
                        <h2 class="text-sm font-bold uppercase tracking-[0.22em] text-white">Itens da proposta</h2>
                        <p class="mt-1 text-sm text-blue-100">Revise quantidade, valor unitário e remova o que não entrar na proposta.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-blue-50 text-blue-900">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Categoria</th>
                                <th class="px-4 py-3 text-left font-semibold">Item</th>
                                <th class="px-4 py-3 text-right font-semibold">Qtd</th>
                                <th class="px-4 py-3 text-right font-semibold">Valor unit.</th>
                                <th class="px-4 py-3 text-right font-semibold">Total</th>
                                <th class="px-4 py-3 text-right font-semibold">Ação</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">
                                        Nenhum item adicionado ainda.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in items" :key="item.uid">
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-slate-600" x-text="item.categoria"></td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-900" x-text="item.nome"></div>
                                        <div class="text-xs text-slate-500" x-text="item.descricao || '—'"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" min="1" step="1" x-model="item.quantidade" @input="syncPayload()"
                                               class="w-20 rounded-xl border border-slate-200 px-2 py-2 text-right text-sm">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" min="0" step="0.01" x-model="item.valor_unitario" @input="syncPayload()"
                                               class="w-32 rounded-xl border border-slate-200 px-2 py-2 text-right text-sm">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="font-semibold text-slate-900" x-text="formatMoney(totalItemComDesconto(item))"></div>
                                        <div x-show="percentualAplicado() > 0" class="text-[11px] text-slate-400 line-through" x-text="formatMoney(totalItem(item))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button"
                                                @click="removerItem(index)"
                                                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                            Remover
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-200 bg-slate-50/80 p-6">
                        <div class="rounded-[22px] border border-slate-200 bg-white p-5">
                            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Resumo financeiro</div>
                                    <div class="mt-1 text-sm text-slate-500">Desconto máximo: <span class="font-semibold text-slate-700" x-text="descontoMaximo.toFixed(2).replace('.', ',') + '%'"></span></div>
                                </div>
                                <div class="w-full md:w-52">
                                    <label class="text-xs font-semibold text-slate-600">Desconto (%)</label>
                                    <input type="number"
                                           name="desconto_percentual"
                                           min="0"
                                           max="100"
                                           step="0.01"
                                           x-model="descontoPercentual"
                                           class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <input type="hidden" name="mostrar_resumo_financeiro" value="0">
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input type="checkbox"
                                           name="mostrar_resumo_financeiro"
                                           value="1"
                                           @checked((bool) $mostrarResumoFinanceiro)
                                           class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>Mostrar resumo financeiro para o cliente</span>
                                </label>
                            </div>

                            <div class="mt-5 divide-y divide-slate-100">
                                <div class="grid gap-4 py-3 md:grid-cols-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600">Data de emissão</label>
                                        <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700"
                                             x-text="formatDateBr(dataEmissaoBase)"></div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600">Validade da proposta (dias)</label>
                                        <input type="number"
                                               name="prazo_dias"
                                               min="1"
                                               max="365"
                                               step="1"
                                               x-model="prazoDias"
                                               class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600">Data de vencimento</label>
                                        <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700"
                                             x-text="dataVencimentoFormatada()"></div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between py-3 text-sm">
                                    <span class="text-slate-500">Subtotal</span>
                                    <span class="font-semibold text-slate-900" x-text="formatMoney(subtotal())"></span>
                                </div>
                                <div class="flex items-center justify-between py-3 text-sm">
                                    <span class="text-amber-700">Desconto</span>
                                    <span class="font-semibold text-amber-800" x-text="formatMoney(valorDesconto())"></span>
                                </div>
                                <div class="flex items-center justify-between pt-4 text-base">
                                    <span class="font-semibold text-slate-900">Total final</span>
                                    <span class="text-2xl font-black text-slate-950" x-text="formatMoney(totalFinal())"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ route('comercial.propostas.rapidas.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit"
                        @click="syncPayload()"
                        class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                    {{ $isEdit ? 'Salvar proposta rápida' : 'Criar proposta rápida' }}
                </button>
            </div>

            <div x-cloak
                 x-show="pacoteModal.aberto"
                 x-transition.opacity
                 class="fixed inset-0 z-[90] bg-slate-950/50 px-4 py-6"
                 @click.self="fecharModalPacote()">
                <div class="mx-auto flex min-h-full max-w-3xl items-center justify-center">
                    <div class="w-full overflow-hidden rounded-[28px] bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900" x-text="pacoteModal.tipo === 'treinamentos' ? 'Pacote de Treinamentos' : 'Grupo de Exames'"></h3>
                                <p class="mt-1 text-sm text-slate-500">Selecione os itens e adicione tudo em uma única linha da proposta.</p>
                            </div>
                            <button type="button"
                                    @click="fecharModalPacote()"
                                    class="rounded-xl px-3 py-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                Fechar
                            </button>
                        </div>

                        <div class="space-y-4 px-6 py-5">
                            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Nome do grupo</label>
                                    <input type="text"
                                           x-model="pacoteModal.nome"
                                           class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"
                                           :placeholder="pacoteModal.tipo === 'treinamentos' ? 'Ex.: Pacote de NRs' : 'Ex.: Grupo admissional'">
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Valor do grupo (R$)</label>
                                    <input type="number"
                                           min="0"
                                           step="0.01"
                                           x-model="pacoteModal.valor"
                                           @input="pacoteModal.valorManual = true"
                                           class="mt-1 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-3 text-sm">
                                <div class="font-semibold text-slate-700" x-text="pacoteModal.tipo === 'treinamentos' ? 'Itens do pacote' : 'Exames do grupo'"></div>
                                <div class="text-slate-500"><span x-text="pacoteModalSelecionados().length"></span> selecionados</div>
                            </div>

                            <div class="max-h-[45vh] overflow-y-auto rounded-2xl border border-slate-200">
                                <div class="divide-y divide-slate-100">
                                    <template x-for="item in pacoteModal.itens" :key="pacoteModal.tipo + '-' + (item.origem_id || item.nome)">
                                        <label class="flex cursor-pointer items-center justify-between gap-4 px-4 py-3 hover:bg-slate-50">
                                            <div class="flex items-start gap-3">
                                                <input type="checkbox"
                                                       :value="String(item.origem_id || item.nome)"
                                                       x-model="pacoteModal.selecionados"
                                                       @change="atualizarValorPacoteAutomatico()"
                                                       class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                                <div>
                                                    <div class="font-medium text-slate-900" x-text="pacoteModal.tipo === 'treinamentos' ? nomePrincipalTreinamento(item.nome) : item.nome"></div>
                                                    <div class="text-xs text-slate-500" x-text="pacoteModal.tipo === 'treinamentos' ? descricaoSecundariaTreinamento(item) : (item.descricao || 'Sem descrição')"></div>
                                                </div>
                                            </div>
                                            <div class="shrink-0 text-sm font-semibold text-slate-600" x-text="formatMoney(item.valor_unitario || 0)"></div>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                            <button type="button"
                                    @click="fecharModalPacote()"
                                    class="rounded-2xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                                Cancelar
                            </button>
                            <button type="button"
                                    @click="confirmarPacote()"
                                    class="rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                Salvar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if($mostrarPopupErroDesconto)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.uiAlert === 'function') {
                    window.uiAlert(@json($mensagemErroDesconto), {
                        icon: 'error',
                        title: 'Limite de desconto'
                    });
                }
            });
        </script>
    @endif
@endsection

@push('scripts')
    <script>
        function propostaRapidaForm(config) {
            return {
                clientes: config.clientes || [],
                empresa: config.empresa || {},
                catalogo: config.catalogo || {},
                clienteExistenteId: config.clienteInicialId || '',
                clienteModo: config.modoInicial || 'existente',
                clienteBusca: '',
                novoCliente: {
                    cnpj: @js(old('novo_cnpj', '')),
                    razao_social: @js(old('novo_razao_social', '')),
                    endereco: @js(old('novo_endereco', '')),
                    telefone: @js(old('novo_telefone', '')),
                    email: @js(old('novo_email', '')),
                },
                descontoPercentual: String(config.descontoInicial || '0'),
                prazoDias: String(config.prazoDiasInicial || '7'),
                dataEmissaoBase: String(config.dataEmissaoBase || ''),
                descontoMaximo: Number(config.descontoMaximo || 0),
                catalogoTab: 'servicos',
                items: (config.itensIniciais || []).map((item, index) => ({
                    uid: `initial-${index}-${Date.now()}`,
                    categoria: String(item.categoria || 'SERVICO').toUpperCase(),
                    origem_id: item.origem_id || null,
                    nome: item.nome || '',
                    descricao: item.descricao || '',
                    quantidade: Number(item.quantidade || 1),
                    valor_unitario: Number(item.valor_unitario || 0),
                })),
                itemsPayload: '[]',
                clienteLookupAviso: '',
                addedItemKey: '',
                pacoteModal: {
                    aberto: false,
                    tipo: 'treinamentos',
                    nome: '',
                    valor: '',
                    valorManual: false,
                    itens: [],
                    selecionados: [],
                },
                init() {
                    this.syncPayload();
                },
                clientesFiltrados() {
                    const busca = (this.clienteBusca || '').toLowerCase().trim();
                    if (!busca) {
                        return this.clientes;
                    }

                    return this.clientes.filter((cliente) => {
                        const nome = String(cliente.razao_social || '').toLowerCase();
                        const documento = String(cliente.documento_principal || '').toLowerCase();
                        return nome.includes(busca) || documento.includes(busca);
                    });
                },
                clienteSelecionado() {
                    return this.clientes.find((cliente) => String(cliente.id) === String(this.clienteExistenteId)) || null;
                },
                contratanteAtual() {
                    if (this.clienteModo === 'existente') {
                        const cliente = this.clienteSelecionado();
                        return {
                            razao_social: cliente?.razao_social || '',
                            documento: cliente?.documento_principal || '',
                            endereco: cliente?.endereco || '',
                            telefone: cliente?.telefone || '',
                            email: cliente?.email || '',
                        };
                    }

                    return {
                        razao_social: this.novoCliente.razao_social || '',
                        documento: this.formatDocumento(this.novoCliente.cnpj || ''),
                        endereco: this.novoCliente.endereco || '',
                        telefone: this.novoCliente.telefone || '',
                        email: this.novoCliente.email || '',
                    };
                },
                adicionarItem(base) {
                    const itemKey = `${String(base.categoria || 'SERVICO').toUpperCase()}-${base.origem_id || 'sem-origem'}-${base.nome || ''}`;
                    this.items.push({
                        uid: `item-${Date.now()}-${Math.random()}`,
                        categoria: String(base.categoria || 'SERVICO').toUpperCase(),
                        origem_id: base.origem_id || null,
                        nome: base.nome || '',
                        descricao: base.descricao || '',
                        quantidade: 1,
                        valor_unitario: Number(base.valor_unitario || 0),
                    });
                    this.addedItemKey = itemKey;
                    setTimeout(() => {
                        if (this.addedItemKey === itemKey) {
                            this.addedItemKey = '';
                        }
                    }, 1400);
                    this.syncPayload();
                },
                itemFoiAdicionado(base) {
                    const itemKey = `${String(base.categoria || 'SERVICO').toUpperCase()}-${base.origem_id || 'sem-origem'}-${base.nome || ''}`;
                    return this.addedItemKey === itemKey;
                },
                botaoCatalogoClasse(nome, fallback = 'servico') {
                    const base = String(nome || '').toLowerCase();
                    if (base.includes('apr')) return 'border-l-4 border-l-amber-500 text-amber-800 hover:bg-amber-50';
                    if (base.includes('art')) return 'border-l-4 border-l-slate-500 text-slate-700 hover:bg-slate-50';
                    if (base.includes('ltcat')) return 'border-l-4 border-l-orange-500 text-orange-800 hover:bg-orange-50';
                    if (base.includes('ltip')) return 'border-l-4 border-l-rose-500 text-rose-800 hover:bg-rose-50';
                    if (base.includes('pae')) return 'border-l-4 border-l-red-500 text-red-800 hover:bg-red-50';
                    if (base.includes('pcmso')) return 'border-l-4 border-l-purple-500 text-purple-800 hover:bg-purple-50';
                    if (base.includes('pgr')) return 'border-l-4 border-l-emerald-500 text-emerald-800 hover:bg-emerald-50';
                    if (base.includes('esocial')) return 'border-l-4 border-l-violet-500 text-violet-800 hover:bg-violet-50';
                    return fallback === 'servico'
                        ? 'border-l-4 border-l-blue-500 text-slate-700 hover:bg-blue-50'
                        : 'hover:bg-slate-50';
                },
                nomePrincipalTreinamento(nome) {
                    const valor = String(nome || '');
                    const partes = valor.split(' - ');
                    return partes[0] || valor;
                },
                descricaoSecundariaTreinamento(item) {
                    const nome = String(item?.nome || '');
                    const partes = nome.split(' - ');
                    if (partes.length > 1) {
                        return `${item?.origem_id ? '#' + item.origem_id + ' — ' : ''}${partes.slice(1).join(' - ')}`;
                    }
                    return item?.descricao || '';
                },
                abrirModalPacote(tipo) {
                    const itens = Array.isArray(this.catalogo?.[tipo]) ? this.catalogo[tipo] : [];
                    this.pacoteModal = {
                        aberto: true,
                        tipo,
                        nome: tipo === 'treinamentos' ? 'Pacote de Treinamentos' : 'Grupo de Exames',
                        valor: '',
                        valorManual: false,
                        itens,
                        selecionados: [],
                    };
                },
                fecharModalPacote() {
                    this.pacoteModal.aberto = false;
                },
                pacoteModalSelecionados() {
                    const selecionados = new Set((this.pacoteModal.selecionados || []).map(String));
                    return (this.pacoteModal.itens || []).filter((item) => selecionados.has(String(item.origem_id || item.nome)));
                },
                atualizarValorPacoteAutomatico() {
                    if (this.pacoteModal.valorManual) {
                        return;
                    }

                    const total = this.pacoteModalSelecionados()
                        .reduce((carry, item) => carry + Number(item.valor_unitario || 0), 0);

                    this.pacoteModal.valor = total ? total.toFixed(2) : '';
                },
                confirmarPacote() {
                    const selecionados = this.pacoteModalSelecionados();
                    if (!selecionados.length) {
                        return;
                    }

                    const categoria = this.pacoteModal.tipo === 'treinamentos' ? 'TREINAMENTO' : 'EXAME';
                    const nomePadrao = this.pacoteModal.tipo === 'treinamentos' ? 'Pacote de Treinamentos' : 'Grupo de Exames';
                    const valorInformado = Number(this.pacoteModal.valor || 0);
                    const valorUnitario = valorInformado > 0
                        ? valorInformado
                        : selecionados.reduce((carry, item) => carry + Number(item.valor_unitario || 0), 0);

                    this.adicionarItem({
                        categoria,
                        origem_id: null,
                        nome: String(this.pacoteModal.nome || '').trim() || nomePadrao,
                        descricao: selecionados.map((item) => item.nome).join(', '),
                        valor_unitario: valorUnitario,
                    });

                    this.fecharModalPacote();
                },
                removerItem(index) {
                    this.items.splice(index, 1);
                    this.syncPayload();
                },
                totalItem(item) {
                    return Number(item.quantidade || 0) * Number(item.valor_unitario || 0);
                },
                subtotal() {
                    return this.items.reduce((carry, item) => carry + this.totalItem(item), 0);
                },
                percentualAplicado() {
                    const raw = Number(this.descontoPercentual || 0);
                    if (raw < 0) return 0;
                    return raw;
                },
                valorDesconto() {
                    return this.subtotal() * (this.percentualAplicado() / 100);
                },
                totalItemComDesconto(item) {
                    return Math.max(0, this.totalItem(item) * (1 - (this.percentualAplicado() / 100)));
                },
                valorMaximoDesconto() {
                    return this.subtotal() * (Number(this.descontoMaximo || 0) / 100);
                },
                totalFinal() {
                    return Math.max(0, this.subtotal() - this.valorDesconto());
                },
                syncPayload() {
                    this.itemsPayload = JSON.stringify(this.items.map((item) => ({
                        categoria: item.categoria,
                        origem_id: item.origem_id || null,
                        nome: item.nome,
                        descricao: item.descricao || null,
                        quantidade: Number(item.quantidade || 1),
                        valor_unitario: Number(item.valor_unitario || 0),
                    })));
                },
                formatMoney(value) {
                    const number = Number(value || 0);
                    return number.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                },
                formatDateBr(value) {
                    if (!value) return '—';
                    const date = new Date(`${value}T12:00:00`);
                    if (Number.isNaN(date.getTime())) return '—';
                    return date.toLocaleDateString('pt-BR');
                },
                dataVencimentoFormatada() {
                    if (!this.dataEmissaoBase) return '—';
                    const dias = Math.max(1, parseInt(this.prazoDias || '1', 10) || 1);
                    const date = new Date(`${this.dataEmissaoBase}T12:00:00`);
                    if (Number.isNaN(date.getTime())) return '—';
                    date.setDate(date.getDate() + dias);
                    return date.toLocaleDateString('pt-BR');
                },
                formatDocumento(value) {
                    const digits = String(value || '').replace(/\D+/g, '');
                    if (digits.length !== 14) {
                        return value || '';
                    }
                    return digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
                },
                async consultarCnpj() {
                    const digits = String(this.novoCliente.cnpj || '').replace(/\D+/g, '');
                    this.clienteLookupAviso = '';

                    if (digits.length !== 14) {
                        return;
                    }

                    try {
                        const existsResponse = await fetch(config.consultaExistsUrl.replace('__CNPJ__', encodeURIComponent(digits)) + '?tipo_pessoa=PJ');
                        const existsData = await existsResponse.json();

                        if (existsData?.exists) {
                            const existente = this.clientes.find((cliente) => String(cliente.cnpj || '').replace(/\D+/g, '') === digits);
                            if (existente) {
                                this.clienteModo = 'existente';
                                this.clienteExistenteId = String(existente.id);
                                this.clienteLookupAviso = 'Este CNPJ já existe no sistema. O cliente cadastrado foi selecionado.';
                            }
                            return;
                        }

                        const consultaResponse = await fetch(config.consultaCnpjUrl.replace('__CNPJ__', encodeURIComponent(digits)));
                        const consultaData = await consultaResponse.json();
                        if (consultaData?.error) {
                            return;
                        }

                        if (consultaData?.razao_social) {
                            this.novoCliente.razao_social = consultaData.razao_social;
                        }

                        if (consultaData?.endereco) {
                            const endereco = [consultaData.endereco, consultaData.bairro].filter(Boolean).join(' - ');
                            this.novoCliente.endereco = endereco || consultaData.endereco;
                        }

                        if (consultaData?.telefone) {
                            this.novoCliente.telefone = consultaData.telefone;
                        }

                        if (consultaData?.email) {
                            this.novoCliente.email = consultaData.email;
                        }
                    } catch (error) {
                        console.error('Erro ao consultar CNPJ da proposta rápida', error);
                    }
                },
            }
        }
    </script>
@endpush
