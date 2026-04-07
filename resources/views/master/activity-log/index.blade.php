@extends('layouts.master')

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Activity Log</h1>
        <div class="text-sm text-gray-500">Auditoria de alterações do sistema</div>
    </div>
@endsection

@section('content')
    @php use Illuminate\Support\Str; @endphp

    <div class="w-full mx-auto px-4 md:px-6 xl:px-8 pt-4 md:pt-6 space-y-4">
        <div class="mb-2">
            <a href="{{ route('master.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                Voltar ao Painel
            </a>
        </div>

        <form method="GET" class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-7 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Data início</label>
                    <input type="datetime-local"
                           name="date_start"
                           value="{{ request('date_start') }}"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Data fim</label>
                    <input type="datetime-local"
                           name="date_end"
                           value="{{ request('date_end') }}"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Usuário</label>
                    <select name="user_id"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">ID</label>
                    <input type="number"
                           name="id"
                           value="{{ request('id') }}"
                           placeholder="ID do log"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tipo</label>
                    <select name="event"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        <option value="created" @selected(request('event') === 'created')>criação</option>
                        <option value="updated" @selected(request('event') === 'updated')>edição</option>
                        <option value="deleted" @selected(request('event') === 'deleted')>deleção</option>
                        <option value="restored" @selected(request('event') === 'restored')>restauração</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Registro</label>
                    <select name="subject_type"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        @foreach($subjectTypes as $subjectType)
                            <option value="{{ $subjectType }}" @selected(request('subject_type') === $subjectType)>
                                {{ Str::of($subjectType)->afterLast('\\') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </button>
                    <a href="{{ route('master.activity-log.index') }}"
                       class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 hover:bg-slate-50">
                        Limpar
                    </a>
                </div>
            </div>
        </form>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                    <tr class="text-left text-slate-700">
                        <th class="px-3 py-2">ID</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2">Registro</th>
                        <th class="px-3 py-2">Usuário</th>
                        <th class="px-3 py-2">Descrição</th>
                        <th class="px-3 py-2">Log Name</th>
                        <th class="px-3 py-2">Registro_ID</th>
                        <th class="px-3 py-2">Data</th>
                        <th class="px-3 py-2">Ação</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($activities as $activity)
                        @php
                            $event = strtolower((string) ($activity->event ?? ''));
                            $rowClass = match ($event) {
                                'created' => 'bg-emerald-100/80',
                                'updated' => 'bg-amber-200/80',
                                'deleted' => 'bg-rose-100/80',
                                'restored' => 'bg-sky-100/80',
                                default => 'bg-white',
                            };

                            $tipo = match ($event) {
                                'created' => 'criação',
                                'updated' => 'edição',
                                'deleted' => 'deleção',
                                'restored' => 'restauração',
                                default => $activity->event ?? '-',
                            };
                        @endphp
                        <tr class="border-t border-slate-200 {{ $rowClass }}">
                            <td class="px-3 py-2 font-medium text-slate-800">{{ $activity->id }}</td>
                            <td class="px-3 py-2">{{ $tipo }}</td>
                            <td class="px-3 py-2">
                                {{ Str::of((string) $activity->subject_type)->afterLast('\\') ?: '-' }}
                            </td>
                            <td class="px-3 py-2">{{ optional($activity->causer)->name ?? 'Sistema' }}</td>
                            <td class="px-3 py-2">{{ $activity->description }}</td>
                            <td class="px-3 py-2">{{ $activity->log_name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $activity->subject_id ?? '-' }}</td>
                            <td class="px-3 py-2">
                                {{ optional($activity->created_at)->format('d-m-Y H:i:s') }}
                            </td>
                            <td class="px-3 py-2">
                                <button type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200"
                                        data-log-view
                                        data-url="{{ route('master.activity-log.show', $activity->id) }}"
                                        title="Visualizar detalhes">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-8 text-center text-slate-500">
                                Nenhum log encontrado com os filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-3 py-3 border-t border-slate-200 bg-slate-50">
                {{ $activities->links() }}
            </div>
        </div>
    </div>

    <div id="log-modal"
         class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-5xl rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <h3 class="text-base font-semibold text-slate-900">Detalhes do Log</h3>
                <button type="button"
                        data-log-close
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="px-4 py-3 border-b border-slate-200 text-sm text-slate-600" id="log-modal-meta"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4">
                <div class="rounded-xl border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-slate-600">Antigo</div>
                    <pre id="log-modal-old" class="text-xs p-3 overflow-auto max-h-[420px] bg-white"></pre>
                </div>
                <div class="rounded-xl border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-slate-600">Novo</div>
                    <pre id="log-modal-new" class="text-xs p-3 overflow-auto max-h-[420px] bg-white"></pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('log-modal');
            const oldEl = document.getElementById('log-modal-old');
            const newEl = document.getElementById('log-modal-new');
            const metaEl = document.getElementById('log-modal-meta');
            const closeButtons = modal.querySelectorAll('[data-log-close]');
            const openButtons = document.querySelectorAll('[data-log-view]');

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            openButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    const url = button.getAttribute('data-url');
                    if (!url) return;

                    oldEl.textContent = 'Carregando...';
                    newEl.textContent = 'Carregando...';
                    metaEl.textContent = '';
                    openModal();

                    try {
                        const response = await fetch(url, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        });

                        if (!response.ok) {
                            throw new Error('Falha ao carregar');
                        }

                        const data = await response.json();
                        oldEl.textContent = JSON.stringify(data.old || {}, null, 2);
                        newEl.textContent = JSON.stringify(data.attributes || {}, null, 2);
                        metaEl.textContent = `ID ${data.id} | ${data.subject_label}#${data.subject_id ?? '-'} | ${data.causer_name} | ${data.created_at ?? '-'}`;
                    } catch (error) {
                        oldEl.textContent = 'Não foi possível carregar os detalhes.';
                        newEl.textContent = '';
                        metaEl.textContent = '';
                    }
                });
            });
        })();
    </script>
@endsection
