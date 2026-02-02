@extends('layouts.comercial')
@section('title', 'Propostas')

@section('content')
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <div>
            <a href="{{ route('comercial.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                ← Voltar ao Painel
            </a>
        </div>

        <header class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Propostas</h1>
                <p class="text-slate-500 text-sm mt-1">Listagem de propostas comerciais.</p>
            </div>

            <a href="{{ route('comercial.propostas.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-2xl
                      bg-blue-600 hover:bg-blue-700 active:bg-blue-800
                      text-white px-5 py-2.5 text-sm font-semibold shadow-sm
                      ring-1 ring-blue-600/20 hover:ring-blue-700/30 transition">
                <span class="text-base leading-none">＋</span>
                <span>Nova Proposta</span>
            </a>
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
            <div class="p-4 md:p-5 border-b border-slate-100">
                <form method="GET" action="{{ route('comercial.propostas.index') }}"
                      class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-7">
                        <label class="text-xs font-semibold text-slate-600">Buscar</label>
                        <div class="relative">
                            <input name="q" id="propostas-autocomplete-input" value="{{ request('q') }}"
                                   autocomplete="off"
                                   class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                   placeholder="Buscar por ID, cliente ou status...">
                            <div id="propostas-autocomplete-list"
                                 class="absolute z-20 mt-1 w-full max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg hidden"></div>
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="text-xs font-semibold text-slate-600">Status</label>
                        <select name="status"
                                class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                            @php
                                $statusAtual = strtoupper((string) request('status', 'TODOS'));
                            @endphp
                            <option value="TODOS" @selected($statusAtual === 'TODOS')>Todos</option>
                            <option value="PENDENTE" @selected($statusAtual === 'PENDENTE')>Pendente</option>
                            <option value="ENVIADA" @selected($statusAtual === 'ENVIADA')>Enviada</option>
                            <option value="FECHADA" @selected($statusAtual === 'FECHADA')>Fechada</option>
                            <option value="CANCELADA" @selected($statusAtual === 'CANCELADA')>Cancelada</option>
                        </select>
                    </div>

                    <div class="md:col-span-2 flex gap-2">
                        <button type="submit"
                                class="w-full rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 text-sm font-semibold">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-5 py-3 font-semibold">ID</th>
                        <th class="px-5 py-3 font-semibold">Empresa / Cliente</th>
                        <th class="px-5 py-3 font-semibold">Título / Referência</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 font-semibold">Valor Total</th>
                        <th class="px-5 py-3 font-semibold">Criada em</th>
                        <th class="px-5 py-3 font-semibold w-[300px]">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($propostas as $proposta)
                        @php
                            $cliente = $proposta->cliente;
                            $empresaTxt = $proposta->empresa?->nome ?? '—';
                            $clienteTxt = $cliente?->razao_social ?? '—';
                            $cnpjRaw = $cliente?->cnpj ?? '';
                            $cnpjDigits = preg_replace('/\D+/', '', (string) $cnpjRaw);
                            if (strlen($cnpjDigits) === 14) {
                                $cnpjClienteTxt = substr($cnpjDigits, 0, 2) . '.' . substr($cnpjDigits, 2, 3) . '.' . substr($cnpjDigits, 5, 3) . '/' . substr($cnpjDigits, 8, 4) . '-' . substr($cnpjDigits, 12, 2);
                            } else {
                                $cnpjClienteTxt = $cnpjRaw !== '' ? $cnpjRaw : '—';
                            }
                            $ref = str_pad((int) $proposta->id, 2, '0', STR_PAD_LEFT);
                            $status = strtoupper((string) ($proposta->status ?? ''));

                            $badgeByStatus = [
                                'PENDENTE' => 'bg-amber-50 text-amber-800 border-amber-200',
                                'ENVIADA'  => 'bg-blue-50 text-blue-700 border-blue-200',
                                'FECHADA'  => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'CANCELADA' => 'bg-red-50 text-red-700 border-red-200',
                            ];
                            $badge = $badgeByStatus[$status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                        @endphp

                        <tr>
                            <td class="px-5 py-3 font-semibold text-slate-800">#{{ $proposta->id }}</td>

                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $clienteTxt }}</div>
                                <div class="text-xs text-slate-500">{{ $cnpjClienteTxt }}</div>
                            </td>

                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $ref }}</div>
                                <div class="text-xs text-slate-500">—</div>
                            </td>

                            <td class="px-5 py-3">
                                <button type="button"
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border {{ $badge }} hover:shadow-sm transition"
                                        data-act="status"
                                        data-action="{{ route('comercial.propostas.status', $proposta) }}"
                                        data-id="{{ $proposta->id }}"
                                        data-cliente="{{ e($clienteTxt) }}"
                                        data-status="{{ $status ?: '—' }}"
                                        title="Alterar status">
                                    {{ $status ?: '—' }}
                                </button>
                            </td>

                            <td class="px-5 py-3 font-semibold text-slate-800">
                                R$ {{ number_format((float) $proposta->valor_total, 2, ',', '.') }}
                            </td>

                            <td class="px-5 py-3 text-slate-700">
                                {{ optional($proposta->created_at)->format('d/m/Y H:i') ?? '—' }}
                            </td>

                            <td class="px-5 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2 flex-nowrap">
                                    <button type="button"
                                            class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100"
                                            title="Enviar por WhatsApp"
                                            aria-label="Enviar por WhatsApp"
                                            data-act="whatsapp"
                                            data-action="{{ route('comercial.propostas.enviar-whatsapp', $proposta) }}"
                                            data-telefone="{{ e($cliente?->telefone ?? '') }}"
                                            data-ref="{{ e($ref) }}">
                                        <i class="fa-brands fa-whatsapp text-base"></i>
                                        <span class="sr-only">Enviar por WhatsApp</span>
                                    </button>

                                    <button type="button"
                                            class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-blue-200 bg-blue-50 text-blue-800 hover:bg-blue-100"
                                            title="Enviar por e-mail"
                                            aria-label="Enviar por e-mail"
                                            data-act="email"
                                            data-action="{{ route('comercial.propostas.enviar-email', $proposta) }}"
                                            data-email="{{ e($cliente?->email ?? '') }}"
                                            data-ref="{{ e($ref) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16v12H4V6zm0 1l8 6 8-6"/>
                                        </svg>
                                        <span class="sr-only">Enviar por e-mail</span>
                                    </button>

                                    <a href="{{ route('comercial.propostas.print', $proposta) }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
                                       title="Imprimir"
                                       aria-label="Imprimir">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v7H6v-7z"/>
                                        </svg>
                                        <span class="sr-only">Imprimir</span>
                                    </a>

                                    <button type="button"
                                            class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100"
                                            title="Duplicar proposta"
                                            aria-label="Duplicar proposta"
                                            data-act="duplicar"
                                            data-action="{{ route('comercial.propostas.duplicar', $proposta) }}"
                                            data-ref="{{ e($ref) }}"
                                            data-cliente="{{ e($clienteTxt) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h11v11H9zM5 5h11v2H7v9H5z"/>
                                        </svg>
                                        <span class="sr-only">Duplicar proposta</span>
                                    </button>

                                    <a href="{{ route('comercial.propostas.edit', $proposta) }}"
                                       class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                       title="Editar"
                                       aria-label="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 3.5l4 4L8 20H4v-4L16.5 3.5z"/>
                                        </svg>
                                        <span class="sr-only">Editar</span>
                                    </a>

                                    <form method="POST"
                                          action="{{ route('comercial.propostas.destroy', $proposta) }}"
                                          data-confirm="Deseja excluir esta proposta?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-red-200 bg-red-50 text-red-700 hover:bg-red-100"
                                                title="Excluir"
                                                aria-label="Excluir">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5h6v2m-8 0l1 14h8l1-14"/>
                                            </svg>
                                            <span class="sr-only">Excluir</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                Nenhuma proposta encontrada.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 md:p-5 border-t border-slate-100">
                {{ $propostas->links() }}
            </div>
        </section>

        {{-- Modal WhatsApp --}}
        <div id="modalWhatsapp" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-800">Enviar por WhatsApp</h3>
                        <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                                onclick="closeWhatsappModal()">✕</button>
                    </div>

                    <form id="formWhatsapp" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Telefone *</label>
                            <input id="whatsappTelefone" name="telefone" type="text"
                                   class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                   placeholder="(11) 99999-9999">
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-600">Mensagem *</label>
                            <textarea id="whatsappMensagem" name="mensagem" rows="5"
                                      class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                      placeholder="Mensagem..."></textarea>
                        </div>

                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button"
                                    class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                    onclick="closeWhatsappModal()">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 text-sm font-semibold">
                                Enviar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal E-mail --}}
        <div id="modalEmail" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-800">Enviar por E-mail</h3>
                        <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                                onclick="closeEmailModal()">✕</button>
                    </div>

                    <form id="formEmail" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="text-xs font-semibold text-slate-600">E-mail *</label>
                            <input id="emailTo" name="email" type="email"
                                   class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                   placeholder="cliente@email.com">
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-600">Assunto *</label>
                            <input id="emailAssunto" name="assunto" type="text"
                                   class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                   placeholder="Assunto">
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-600">Mensagem *</label>
                            <textarea id="emailMensagem" name="mensagem" rows="6"
                                      class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                      placeholder="Mensagem..."></textarea>
                        </div>

                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button"
                                    class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                    onclick="closeEmailModal()">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold">
                                Enviar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Duplicar --}}
        <div id="modalDuplicar" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-800">Duplicar proposta</h3>
                        <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                                onclick="closeDuplicarModal()">✕</button>
                    </div>

                    <form id="formDuplicar" method="POST" class="p-6 space-y-4">
                        @csrf

                        <div class="text-sm text-slate-600">
                            Duplicando a proposta <span class="font-semibold text-slate-800">#<span id="duplicarPropostaRef">—</span></span>
                            <span class="text-slate-500">(<span id="duplicarPropostaCliente">—</span>)</span>
                        </div>

                        <div id="duplicarClienteWrap">
                            <x-select-ajax name="cliente_id"
                                           label="Cliente"
                                           endpoint="{{ route('api.clientes.index') }}"
                                           placeholder="Digite o nome do cliente..." />
                        </div>

                        <p class="text-xs text-slate-500">Selecione o cliente para criar uma nova proposta a partir desta.</p>

                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button"
                                    class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                    onclick="closeDuplicarModal()">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="rounded-xl bg-amber-600 hover:bg-amber-700 text-white px-5 py-2 text-sm font-semibold">
                                Duplicar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Status --}}
        <div id="modalStatus" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-800">Alterar status da proposta</h3>
                        <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                                onclick="closeStatusModal()">✕</button>
                    </div>

                    <form id="statusForm" method="POST" class="p-6 space-y-4">
                        @csrf

                        <div class="text-sm text-slate-700 space-y-1">
                            <div><span class="text-slate-500">Proposta:</span> <span class="font-semibold text-slate-800">#<span id="statusPropostaId">—</span></span></div>
                            <div><span class="text-slate-500">Cliente:</span> <span id="statusCliente" class="font-medium text-slate-800">—</span></div>
                            <div><span class="text-slate-500">Status atual:</span> <span id="statusAtual" class="font-medium text-slate-800">—</span></div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-600">Novo status</label>
                            <select id="statusSelect" name="status"
                                    class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"></select>
                            <p id="statusHelp" class="text-xs text-slate-500 mt-1">Selecione o novo status permitido.</p>
                        </div>

                        <div id="statusError" class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button"
                                    class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                    onclick="closeStatusModal()">
                                Cancelar
                            </button>
                            <button id="statusSubmit" type="submit"
                                    class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            (function () {
                const modalWhatsapp = document.getElementById('modalWhatsapp');
                const modalEmail = document.getElementById('modalEmail');
                const modalDuplicar = document.getElementById('modalDuplicar');

                const formWhatsapp = document.getElementById('formWhatsapp');
                const whatsappTelefone = document.getElementById('whatsappTelefone');
                const whatsappMensagem = document.getElementById('whatsappMensagem');

                const formEmail = document.getElementById('formEmail');
                const emailTo = document.getElementById('emailTo');
                const emailAssunto = document.getElementById('emailAssunto');
                const emailMensagem = document.getElementById('emailMensagem');

                const formDuplicar = document.getElementById('formDuplicar');
                const duplicarPropostaRef = document.getElementById('duplicarPropostaRef');
                const duplicarPropostaCliente = document.getElementById('duplicarPropostaCliente');
                const duplicarClienteWrap = document.getElementById('duplicarClienteWrap');

                function openWhatsappModal(action, telefone, ref) {
                    if (!modalWhatsapp || !formWhatsapp) return;
                    formWhatsapp.action = action;
                    whatsappTelefone.value = telefone || '';
                    whatsappMensagem.value = `Olá! Segue a proposta ${ref}.`;
                    modalWhatsapp.classList.remove('hidden');
                    setTimeout(() => whatsappTelefone?.focus(), 0);
                }

                function closeWhatsappModal() {
                    modalWhatsapp?.classList.add('hidden');
                }

                function openEmailModal(action, email, ref) {
                    if (!modalEmail || !formEmail) return;
                    formEmail.action = action;
                    emailTo.value = email || '';
                    emailAssunto.value = `Proposta ${ref}`;
                    emailMensagem.value = `Olá! Segue a proposta ${ref}.`;
                    modalEmail.classList.remove('hidden');
                    setTimeout(() => emailTo?.focus(), 0);
                }

                function closeEmailModal() {
                    modalEmail?.classList.add('hidden');
                }

                function resetDuplicarCliente() {
                    if (!duplicarClienteWrap) return;
                    const textInput = duplicarClienteWrap.querySelector('input[type="text"]');
                    const hiddenInput = duplicarClienteWrap.querySelector('input[type="hidden"]');
                    if (textInput) {
                        textInput.value = '';
                        textInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (hiddenInput) {
                        hiddenInput.value = '';
                        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }

                function openDuplicarModal(action, ref, cliente) {
                    if (!modalDuplicar || !formDuplicar) return;
                    formDuplicar.action = action;
                    if (duplicarPropostaRef) duplicarPropostaRef.textContent = ref || '—';
                    if (duplicarPropostaCliente) duplicarPropostaCliente.textContent = cliente || '—';
                    resetDuplicarCliente();
                    modalDuplicar.classList.remove('hidden');
                    const textInput = duplicarClienteWrap?.querySelector('input[type="text"]');
                    setTimeout(() => textInput?.focus(), 0);
                }

                function closeDuplicarModal() {
                    modalDuplicar?.classList.add('hidden');
                }

                // Status
                const modalStatus = document.getElementById('modalStatus');
                const statusForm = document.getElementById('statusForm');
                const statusSelect = document.getElementById('statusSelect');
                const statusPropostaId = document.getElementById('statusPropostaId');
                const statusCliente = document.getElementById('statusCliente');
                const statusAtual = document.getElementById('statusAtual');
                const statusError = document.getElementById('statusError');
                const statusHelp = document.getElementById('statusHelp');
                const statusSubmit = document.getElementById('statusSubmit');
                const statusTransitions = {
                    'PENDENTE': ['PENDENTE', 'ENVIADA', 'CANCELADA'],
                    'ENVIADA': ['ENVIADA', 'FECHADA', 'CANCELADA'],
                };
                const badgeClassByStatus = {
                    'PENDENTE': 'bg-amber-50 text-amber-800 border-amber-200',
                    'ENVIADA': 'bg-blue-50 text-blue-700 border-blue-200',
                    'FECHADA': 'bg-emerald-50 text-emerald-800 border-emerald-200',
                    'CANCELADA': 'bg-red-50 text-red-700 border-red-200',
                };
                const badgeBaseClass = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border hover:shadow-sm transition';
                let statusTrigger = null;

                function formatStatusLabel(status) {
                    const map = {
                        'PENDENTE': 'Pendente',
                        'ENVIADA': 'Enviada',
                        'FECHADA': 'Fechada',
                        'CANCELADA': 'Cancelada',
                    };
                    return map[status] || status || '—';
                }

                function buildStatusOptions(atual) {
                    const transitions = statusTransitions[atual] || [];
                    statusSelect.innerHTML = '';

                    transitions.forEach(status => {
                        const opt = document.createElement('option');
                        opt.value = status;
                        opt.textContent = formatStatusLabel(status);
                        statusSelect.appendChild(opt);
                    });

                    const hasOptions = transitions.length > 0;
                    statusSelect.disabled = !hasOptions;
                    statusSubmit.disabled = !hasOptions;
                    statusHelp.textContent = hasOptions
                        ? 'Selecione o novo status permitido.'
                        : 'Transição não permitida para este status.';
                }

                function openStatusModal(btn) {
                    if (!modalStatus || !statusForm) return;
                    statusTrigger = btn;
                    const id = btn?.dataset?.id || '—';
                    const cliente = btn?.dataset?.cliente || '—';
                    const atual = (btn?.dataset?.status || '').toUpperCase();

                    statusForm.action = btn?.dataset?.action || '#';
                    statusPropostaId.textContent = id;
                    statusCliente.textContent = cliente;
                    statusAtual.textContent = formatStatusLabel(atual);
                    statusError?.classList.add('hidden');
                    statusError.textContent = '';

                    buildStatusOptions(atual);
                    if (statusSelect.options.length > 0) {
                        statusSelect.value = statusSelect.options[0].value;
                    }

                    modalStatus.classList.remove('hidden');
                    setTimeout(() => statusSelect?.focus(), 0);
                }

                function closeStatusModal() {
                    modalStatus?.classList.add('hidden');
                    statusTrigger = null;
                    if (statusError) {
                        statusError.classList.add('hidden');
                        statusError.textContent = '';
                    }
                }

                async function submitStatus(e) {
                    e.preventDefault();
                    if (!statusForm || !statusSelect) return;

                    const action = statusForm.action;
                    if (!action) return;

                    statusSubmit.disabled = true;
                    statusSubmit.textContent = 'Salvando...';
                    statusError?.classList.add('hidden');
                    statusError.textContent = '';

                    try {
                        const formData = new FormData(statusForm);
                        const response = await fetch(action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        });

                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data?.message || 'Erro ao atualizar status.');
                        }

                        const novoStatus = (data?.status || statusSelect.value || '').toUpperCase();
                        if (statusTrigger) {
                            statusTrigger.dataset.status = novoStatus;
                            statusTrigger.textContent = novoStatus || '—';
                            const cls = badgeClassByStatus[novoStatus] || badgeClassByStatus['PENDENTE'];
                            statusTrigger.className = `${badgeBaseClass} ${cls}`;
                        }

                        closeStatusModal();
                    } catch (err) {
                        if (statusError) {
                            statusError.textContent = err.message || 'Erro ao atualizar status.';
                            statusError.classList.remove('hidden');
                        }
                    } finally {
                        statusSubmit.disabled = false;
                        statusSubmit.textContent = 'Salvar';
                    }
                }

                window.closeWhatsappModal = closeWhatsappModal;
                window.closeEmailModal = closeEmailModal;
                window.closeDuplicarModal = closeDuplicarModal;
                window.closeStatusModal = closeStatusModal;

                document.querySelectorAll('[data-act="whatsapp"]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        openWhatsappModal(btn.dataset.action, btn.dataset.telefone, btn.dataset.ref);
                    });
                });

                document.querySelectorAll('[data-act="email"]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        openEmailModal(btn.dataset.action, btn.dataset.email, btn.dataset.ref);
                    });
                });

                document.querySelectorAll('[data-act="duplicar"]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        openDuplicarModal(btn.dataset.action, btn.dataset.ref, btn.dataset.cliente);
                    });
                });

                document.querySelectorAll('[data-act="status"]').forEach(btn => {
                    btn.addEventListener('click', () => openStatusModal(btn));
                });

                statusForm?.addEventListener('submit', submitStatus);

                document.addEventListener('click', (e) => {
                    if (modalWhatsapp && !modalWhatsapp.classList.contains('hidden') && e.target === modalWhatsapp) closeWhatsappModal();
                    if (modalEmail && !modalEmail.classList.contains('hidden') && e.target === modalEmail) closeEmailModal();
                    if (modalDuplicar && !modalDuplicar.classList.contains('hidden') && e.target === modalDuplicar) closeDuplicarModal();
                    if (modalStatus && !modalStatus.classList.contains('hidden') && e.target === modalStatus) closeStatusModal();
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key !== 'Escape') return;
                    if (modalWhatsapp && !modalWhatsapp.classList.contains('hidden')) return closeWhatsappModal();
                    if (modalEmail && !modalEmail.classList.contains('hidden')) return closeEmailModal();
                    if (modalDuplicar && !modalDuplicar.classList.contains('hidden')) return closeDuplicarModal();
                    if (modalStatus && !modalStatus.classList.contains('hidden')) return closeStatusModal();
                });
            })();
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.initTailwindAutocomplete?.(
                    'propostas-autocomplete-input',
                    'propostas-autocomplete-list',
                    @json($propostasAutocomplete ?? [])
                );
            });
        </script>
    @endpush
@endsection
