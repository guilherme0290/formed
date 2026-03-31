@extends('layouts.comercial')
@section('title', 'Propostas')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canCreate = $isMaster || isset($permissionMap['comercial.propostas.create']);
        $canUpdate = $isMaster || isset($permissionMap['comercial.propostas.update']);
        $canDelete = $isMaster || isset($permissionMap['comercial.propostas.delete']);
        $pipelineLabels = [
            'CONTATO_INICIAL' => 'Contato Inicial',
            'PROPOSTA_ENVIADA' => 'Proposta Enviada',
            'EM_NEGOCIACAO' => 'Em Negociação',
            'FECHAMENTO' => 'Fechamento',
            'PERDIDO' => 'Perdido',
        ];
        $pipelineBadgeByStatus = [
            'CONTATO_INICIAL' => 'bg-sky-50 text-sky-700 border-sky-200',
            'PROPOSTA_ENVIADA' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'EM_NEGOCIACAO' => 'bg-amber-50 text-amber-800 border-amber-200',
            'FECHAMENTO' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'PERDIDO' => 'bg-rose-50 text-rose-700 border-rose-200',
        ];
    @endphp
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <div>
            <a href="{{ route('comercial.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                &larr; Voltar ao Painel
            </a>
        </div>

        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Propostas</h1>
                <p class="text-slate-500 text-sm mt-1">Listagem de propostas comerciais.</p>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <a href="{{ $canCreate ? route('comercial.propostas.rapidas.index') : 'javascript:void(0)' }}"
                   @if(!$canCreate) title="Usuário sem permissão" aria-disabled="true" @endif
                   class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-2xl
                      {{ $canCreate ? 'bg-white hover:bg-slate-50 text-slate-700 ring-1 ring-slate-200' : 'bg-slate-200 text-slate-500 cursor-not-allowed ring-1 ring-slate-300' }}
                      px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                    <span>Propostas Rápidas</span>
                </a>

                <a href="{{ $canCreate ? route('comercial.propostas.create') : 'javascript:void(0)' }}"
                   @if(!$canCreate) title="Usuário sem permissão" aria-disabled="true" @endif
                   class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-2xl
                      {{ $canCreate ? 'bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white ring-1 ring-blue-600/20 hover:ring-blue-700/30' : 'bg-slate-200 text-slate-500 cursor-not-allowed ring-1 ring-slate-300' }}
                      px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                    <span class="text-base leading-none">＋</span>
                    <span>Nova Proposta</span>
                </a>
            </div>
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
                <div class="mb-3 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    O andamento comercial agora deve ser atualizado em
                    <a href="{{ route('comercial.pipeline.index') }}" class="font-semibold underline underline-offset-2">Comercial / Pipeline</a>.
                </div>
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

            <div class="md:hidden divide-y divide-slate-100">
                @forelse($propostas as $proposta)
                    @php
                        $cliente = $proposta->cliente;
                        $clienteTxt = $cliente?->razao_social ?? 'â€”';
                        $cnpjClienteTxt = $cliente?->documento_principal ?? 'â€”';
                        $ref = str_pad((int) $proposta->id, 2, '0', STR_PAD_LEFT);
                        $status = strtoupper((string) ($proposta->status ?? ''));
                        $pipelineStatus = strtoupper((string) ($proposta->pipeline_status ?? 'CONTATO_INICIAL'));
                        $badgeByStatus = [
                            'PENDENTE' => 'bg-amber-50 text-amber-800 border-amber-200',
                            'ENVIADA'  => 'bg-blue-50 text-blue-700 border-blue-200',
                            'FECHADA'  => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                            'CANCELADA' => 'bg-red-50 text-red-700 border-red-200',
                        ];
                        $badge = $badgeByStatus[$status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                        $pipelineBadge = $pipelineBadgeByStatus[$pipelineStatus] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                        $pipelineUrl = route('comercial.pipeline.index', ['q' => $proposta->id]);
                    @endphp

                    <article class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900 truncate">{{ $clienteTxt }}</div>
                                <div class="text-xs text-slate-500">{{ $cnpjClienteTxt }}</div>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border {{ $badge }}">
                                {{ $status ?: 'â€”' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <div class="text-slate-500">ID/Ref</div>
                                <div class="font-semibold text-slate-800">#{{ $proposta->id }} / {{ $ref }}</div>
                            </div>
                            <div>
                                <div class="text-slate-500">Valor</div>
                                <div class="font-semibold text-slate-800">R$ {{ number_format((float) $proposta->valor_total, 2, ',', '.') }}</div>
                            </div>
                            <div class="col-span-2">
                                <div class="text-slate-500">Criada em</div>
                                <div class="font-medium text-slate-700">{{ optional($proposta->created_at)->format('d/m/Y H:i') ?? 'â€”' }}</div>
                            </div>
                            <div class="col-span-2">
                                <div class="text-slate-500">Etapa no pipeline</div>
                                <a href="{{ $pipelineUrl }}"
                                   class="mt-1 inline-flex items-center rounded-full border px-2 py-1 text-xs font-semibold {{ $pipelineBadge }}">
                                    {{ $pipelineLabels[$pipelineStatus] ?? $pipelineStatus }}
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-5 gap-2 pt-1">
                            <a href="{{ $pipelineUrl }}"
                               class="inline-flex items-center justify-center h-9 rounded-xl border border-sky-200 bg-sky-50 text-sky-800"
                               title="Abrir no pipeline">
                                <i class="fa-solid fa-table-columns text-sm"></i>
                            </a>
                            <button type="button"
                                    class="inline-flex items-center justify-center h-9 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800"
                                    data-act="whatsapp"
                                    data-action="{{ route('comercial.propostas.enviar-whatsapp', $proposta) }}"
                                    data-telefone="{{ e($cliente?->telefone ?? '') }}"
                                    data-ref="{{ e($ref) }}">
                                <i class="fa-brands fa-whatsapp text-base"></i>
                            </button>
                            <button type="button"
                                    class="inline-flex items-center justify-center h-9 rounded-xl border border-blue-200 bg-blue-50 text-blue-800"
                                    data-act="email"
                                    data-action="{{ route('comercial.propostas.enviar-email', $proposta) }}"
                                    data-email="{{ e($cliente?->email ?? '') }}"
                                    data-ref="{{ e($ref) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16v12H4V6zm0 1l8 6 8-6"/>
                                </svg>
                            </button>
                            <a href="{{ $canUpdate ? route('comercial.propostas.edit', $proposta) : 'javascript:void(0)' }}"
                               class="inline-flex items-center justify-center h-9 rounded-xl border {{ $canUpdate ? 'border-slate-200 bg-white text-slate-700' : 'border-slate-300 bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 3.5l4 4L8 20H4v-4L16.5 3.5z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('comercial.propostas.destroy', $proposta) }}" data-confirm="Deseja excluir esta proposta?">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center h-9 rounded-xl border {{ $canDelete ? 'border-red-200 bg-red-50 text-red-700' : 'border-slate-300 bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                        @if(!$canDelete) disabled @endif>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5h6v2m-8 0l1 14h8l1-14"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="px-4 py-10 text-center text-slate-500 text-sm">
                        Nenhuma proposta encontrada.
                    </div>
                @endforelse
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="comercial-table min-w-full text-sm">
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
                            $cnpjClienteTxt = $cliente?->documento_principal ?? '—';
                            $ref = str_pad((int) $proposta->id, 2, '0', STR_PAD_LEFT);
                            $status = strtoupper((string) ($proposta->status ?? ''));
                            $pipelineStatus = strtoupper((string) ($proposta->pipeline_status ?? 'CONTATO_INICIAL'));
                            $pipelineUrl = route('comercial.pipeline.index', ['q' => $proposta->id]);

                            $badgeByStatus = [
                                'PENDENTE' => 'bg-amber-50 text-amber-800 border-amber-200',
                                'ENVIADA'  => 'bg-blue-50 text-blue-700 border-blue-200',
                                'FECHADA'  => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'CANCELADA' => 'bg-red-50 text-red-700 border-red-200',
                            ];
                            $badge = $badgeByStatus[$status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            $pipelineBadge = $pipelineBadgeByStatus[$pipelineStatus] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                        @endphp

                        <tr>
                            <td class="px-5 py-3 font-semibold text-slate-800">#{{ $proposta->id }}</td>

                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800 cell-wrap">{{ $clienteTxt }}</div>
                                <div class="text-xs text-slate-500">{{ $cnpjClienteTxt }}</div>
                            </td>

                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800 cell-wrap">{{ $ref }}</div>
                                <div class="text-xs text-slate-500">—</div>
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex flex-col gap-2">
                                    <span class="inline-flex w-fit items-center px-2 py-1 rounded-full text-xs font-semibold border {{ $badge }}">
                                        {{ $status ?: '—' }}
                                    </span>
                                    <a href="{{ $pipelineUrl }}"
                                       class="inline-flex w-fit items-center px-2 py-1 rounded-full text-xs font-semibold border {{ $pipelineBadge }}">
                                        {{ $pipelineLabels[$pipelineStatus] ?? $pipelineStatus }}
                                    </a>
                                </div>
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
                                            class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-sky-200 bg-sky-50 text-sky-800 hover:bg-sky-100"
                                            title="Abrir no pipeline"
                                            aria-label="Abrir no pipeline"
                                            onclick="window.location.href='{{ $pipelineUrl }}'">
                                        <i class="fa-solid fa-table-columns text-sm"></i>
                                        <span class="sr-only">Abrir no pipeline</span>
                                    </button>

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
                                            class="inline-flex items-center justify-center h-9 w-9 rounded-xl border {{ $canCreate ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-slate-300 bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                            title="Duplicar proposta"
                                            aria-label="Duplicar proposta"
                                            data-act="duplicar"
                                            data-action="{{ route('comercial.propostas.duplicar', $proposta) }}"
                                            data-ref="{{ e($ref) }}"
                                            data-cliente="{{ e($clienteTxt) }}"
                                            @if(!$canCreate) disabled title="Usuário sem permissão" @endif>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h11v11H9zM5 5h11v2H7v9H5z"/>
                                        </svg>
                                        <span class="sr-only">Duplicar proposta</span>
                                    </button>

                                    <a href="{{ $canUpdate ? route('comercial.propostas.edit', $proposta) : 'javascript:void(0)' }}"
                                       class="inline-flex items-center justify-center h-9 w-9 rounded-xl border {{ $canUpdate ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'border-slate-300 bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                       title="{{ $canUpdate ? 'Editar' : 'Usuário sem permissão' }}"
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
                                                class="inline-flex items-center justify-center h-9 w-9 rounded-xl border {{ $canDelete ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' : 'border-slate-300 bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                                title="{{ $canDelete ? 'Excluir' : 'Usuário sem permissão' }}"
                                                aria-label="Excluir"
                                                @if(!$canDelete) disabled @endif>
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

                window.closeWhatsappModal = closeWhatsappModal;
                window.closeEmailModal = closeEmailModal;
                window.closeDuplicarModal = closeDuplicarModal;

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

                document.addEventListener('click', (e) => {
                    if (modalWhatsapp && !modalWhatsapp.classList.contains('hidden') && e.target === modalWhatsapp) closeWhatsappModal();
                    if (modalEmail && !modalEmail.classList.contains('hidden') && e.target === modalEmail) closeEmailModal();
                    if (modalDuplicar && !modalDuplicar.classList.contains('hidden') && e.target === modalDuplicar) closeDuplicarModal();
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key !== 'Escape') return;
                    if (modalWhatsapp && !modalWhatsapp.classList.contains('hidden')) return closeWhatsappModal();
                    if (modalEmail && !modalEmail.classList.contains('hidden')) return closeEmailModal();
                    if (modalDuplicar && !modalDuplicar.classList.contains('hidden')) return closeDuplicarModal();
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
