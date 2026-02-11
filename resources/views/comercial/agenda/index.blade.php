@extends('layouts.comercial')
@section('title', 'Agenda Comercial')

@php
    $tipoBadges = [
        'Retorno Cliente' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'icon' => 'R'],
        'Reuniao' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'icon' => 'M'],
        'Follow-up' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'icon' => 'F'],
        'Tarefa' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'icon' => 'T'],
        'Outro' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'icon' => 'O'],
    ];

    $prioridadeBadges = [
        'Baixa' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'label' => 'Baixa'],
        'Media' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'label' => 'Media'],
        'Alta'  => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'label' => 'Alta'],
    ];

    $rotuloPendentes = $periodo === 'semana'
        ? 'Pendentes da semana'
        : ($periodo === 'mes'
            ? 'Pendentes do mes'
            : ($periodo === 'ano' ? 'Pendentes do ano' : 'Pendentes do dia'));
    $rotuloConcluidas = $periodo === 'semana'
        ? 'Concluidas da semana'
        : ($periodo === 'mes'
            ? 'Concluidas do mes'
            : ($periodo === 'ano' ? 'Concluidas do ano' : 'Concluidas do dia'));
@endphp

@section('content')
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <a href="{{ route('comercial.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                Voltar ao Painel
            </a>
            @if(auth()->user()?->isMaster())
                <a href="{{ route('master.agenda-vendedores') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                    Agenda de Vendedores
                </a>
            @endif
            <button type="button"
                    id="btnAbrirModalAgenda"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-500 text-white text-sm font-semibold shadow hover:bg-orange-600">
                Nova Tarefa
            </button>
        </div>

        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Agenda Comercial</h1>
                <p class="text-sm text-slate-500 mt-1">Foco diario nas atividades comerciais.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @php
                    $rotuloPeriodo = $periodo === 'ano'
                        ? $dataSelecionada->locale('pt_BR')->translatedFormat('Y')
                        : ($periodo === 'mes'
                            ? $dataSelecionada->locale('pt_BR')->translatedFormat('F Y')
                            : ($periodo === 'semana'
                                ? ($inicio->format('d/m').' a '.$fim->format('d/m'))
                                : $dataSelecionada->locale('pt_BR')->translatedFormat('d \\d\\e F \\d\\e Y')));
                @endphp

                <form method="GET" action="{{ route('comercial.agenda.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="date" name="data" value="{{ $dataSelecionada->toDateString() }}"
                           class="px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-700 bg-white">
                    <select name="periodo" class="h-11 min-w-[140px] px-4 rounded-xl border border-slate-200 text-sm leading-tight text-slate-600 bg-white">
                        <option value="mes" @selected($periodo === 'mes')>Mes</option>
                        <option value="ano" @selected($periodo === 'ano')>Ano</option>
                    </select>
                    <button class="px-3 py-2 rounded-xl bg-slate-900 text-white text-sm">Aplicar</button>
                </form>

                <div class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">
                    {{ $rotuloPeriodo }}
                </div>
            </div>
        </header>

        @if(session('ok'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif
        @if(session('erro'))
            <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
                {{ session('erro') }}
            </div>
        @endif

        <div class="grid md:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-slate-100 bg-white shadow-sm p-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total de compromissos em aberto</p>
                <p class="text-3xl font-bold text-slate-900 mt-2">{{ $kpis['aberto_total'] }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50/70 shadow-sm p-4">
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide">{{ $rotuloPendentes }}</p>
                <p class="text-3xl font-bold text-amber-700 mt-2">{{ $kpis['pendentes_periodo'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 shadow-sm p-4">
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">{{ $rotuloConcluidas }}</p>
                <p class="text-3xl font-bold text-emerald-700 mt-2">{{ $kpis['concluidas_periodo'] }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between bg-emerald-600 text-white px-4 py-3">
                <div>
                    <h2 class="text-sm font-semibold">Calendario</h2>
                    <p class="text-xs text-emerald-50">Clique em um dia para ver os compromissos.</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <div class="flex items-center gap-1 text-emerald-50">
                        <span class="inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                        Pendente
                    </div>
                    <div class="flex items-center gap-1 text-emerald-50">
                        <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        Concluida
                    </div>
                </div>
            </div>

            <div class="p-5 space-y-4">
            @if ($periodo === 'ano')
                <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($calendariosAno as $calendario)
                        @php
                            $cardCores = [
                                ['bg' => 'bg-indigo-50/70', 'border' => 'border-indigo-100', 'text' => 'text-indigo-900'],
                                ['bg' => 'bg-emerald-50/70', 'border' => 'border-emerald-100', 'text' => 'text-emerald-900'],
                                ['bg' => 'bg-amber-50/70', 'border' => 'border-amber-100', 'text' => 'text-amber-900'],
                                ['bg' => 'bg-sky-50/70', 'border' => 'border-sky-100', 'text' => 'text-sky-900'],
                                ['bg' => 'bg-rose-50/70', 'border' => 'border-rose-100', 'text' => 'text-rose-900'],
                                ['bg' => 'bg-teal-50/70', 'border' => 'border-teal-100', 'text' => 'text-teal-900'],
                            ];
                            $cor = $cardCores[$loop->index % count($cardCores)];
                        @endphp
                        <div class="rounded-2xl border p-4 space-y-3 {{ $cor['bg'] }} {{ $cor['border'] }}">
                            <div class="text-sm font-semibold {{ $cor['text'] }}">{{ $calendario['titulo'] }}</div>
                            <div class="grid grid-cols-7 text-[10px] uppercase tracking-wide font-semibold {{ $cor['text'] }}">
                                <div class="text-center">D</div>
                                <div class="text-center">S</div>
                                <div class="text-center">T</div>
                                <div class="text-center">Q</div>
                                <div class="text-center">Q</div>
                                <div class="text-center">S</div>
                                <div class="text-center">S</div>
                            </div>
                            <div class="grid grid-cols-7 gap-1">
                                @foreach ($calendario['datas'] as $dia)
                                    @if (!$dia)
                                        <div class="h-10 rounded-lg border border-transparent"></div>
                                        @continue
                                    @endif
                                    @php
                                        $dataStr = $dia->toDateString();
                                        $contagens = $contagensPorData[$dataStr] ?? ['pendentes' => 0, 'concluidas' => 0];
                                    @endphp
                                    <button type="button"
                                            class="agenda-dia flex flex-col items-center justify-center rounded-lg border border-slate-100 px-1 py-1 text-[11px] bg-white text-slate-900 hover:border-slate-300"
                                            data-date="{{ $dataStr }}"
                                            data-label="{{ $dia->format('d/m/Y') }}">
                                        <span class="font-semibold">{{ $dia->day }}</span>
                                        <div class="mt-0.5 flex items-center justify-center gap-0.5">
                                            @if ($contagens['pendentes'] > 0)
                                                <span class="inline-flex h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            @endif
                                            @if ($contagens['concluidas'] > 0)
                                                <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-7 text-[11px] uppercase tracking-wide font-semibold text-slate-900">
                    <div class="text-center">D</div>
                    <div class="text-center">S</div>
                    <div class="text-center">T</div>
                    <div class="text-center">Q</div>
                    <div class="text-center">Q</div>
                    <div class="text-center">S</div>
                    <div class="text-center">S</div>
                </div>

                <div class="grid grid-cols-7 gap-2">
                    @foreach ($datasCalendario as $dia)
                        @if (!$dia)
                            <div class="h-16 rounded-xl border border-transparent"></div>
                            @continue
                        @endif
                        @php
                            $dataStr = $dia->toDateString();
                            $contagens = $contagensPorData[$dataStr] ?? ['pendentes' => 0, 'concluidas' => 0];
                        @endphp
                        <button type="button"
                                class="agenda-dia flex flex-col items-center justify-center rounded-xl border border-slate-100 px-2 py-2 text-sm bg-white text-slate-900 hover:border-slate-300"
                                data-date="{{ $dataStr }}"
                                data-label="{{ $dia->format('d/m/Y') }}">
                            <span class="font-semibold">{{ $dia->day }}</span>
                            <div class="mt-1 flex items-center justify-center gap-1">
                                @if ($contagens['pendentes'] > 0)
                                    <span class="inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                                @endif
                                @if ($contagens['concluidas'] > 0)
                                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
            </div>
        </div>

        <div class="hidden">
            @foreach ($tarefasPorData as $dataStr => $tarefasDia)
                <div id="agenda-dia-{{ $dataStr }}">
                    @forelse ($tarefasDia as $tarefa)
                        @php
                            $isConcluida = $tarefa->status === 'CONCLUIDA';
                            $status = $isConcluida ? 'Concluida' : 'Pendente';
                            $statusClasses = $isConcluida
                                ? 'bg-emerald-50 text-emerald-700'
                                : 'bg-amber-50 text-amber-700';
                            $cardClasses = $isConcluida
                                ? 'bg-emerald-50/40 border-emerald-100'
                                : 'bg-amber-50/40 border-amber-100';
                        @endphp
                        @php
                            $prioridadeBadge = match ($tarefa->prioridade) {
                                'Alta' => 'bg-rose-600 text-white',
                                'Baixa' => 'bg-blue-600 text-white',
                                default => 'bg-amber-400 text-amber-900',
                            };
                        @endphp
                        <div class="rounded-xl border shadow-sm p-3 space-y-2 {{ $cardClasses }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $tarefa->titulo }}</p>
                                    @if($tarefa->descricao)
                                        <p class="text-xs text-slate-600 mt-1">{{ $tarefa->descricao }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-semibold {{ $prioridadeBadge }}">
                                    {{ $tarefa->prioridade }}
                                </span>
                            </div>
                            <div class="mt-2 space-y-1 text-xs text-slate-500">
                                <div>Hora: {{ $tarefa->hora?->format('H:i') ?? '--:--' }}</div>
                                @if($tarefa->cliente)
                                    <div>Cliente: {{ $tarefa->cliente }}</div>
                                @endif
                            </div>
                            <div class="flex items-center justify-end gap-2 text-sm">
                                @if (!$isConcluida)
                                    <form method="POST" action="{{ route('comercial.agenda.concluir', $tarefa) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                            Concluir
                                        </button>
                                    </form>
                                    <button type="button"
                                            class="js-editar-agenda inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-900 text-white hover:bg-slate-800"
                                            data-id="{{ $tarefa->id }}"
                                            data-titulo="{{ $tarefa->titulo }}"
                                            data-descricao="{{ $tarefa->descricao }}"
                                            data-tipo="{{ $tarefa->tipo }}"
                                            data-prioridade="{{ $tarefa->prioridade }}"
                                            data-data="{{ $tarefa->data?->toDateString() }}"
                                            data-hora="{{ $tarefa->hora?->format('H:i') }}"
                                            data-cliente="{{ $tarefa->cliente }}">
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('comercial.agenda.destroy', $tarefa) }}" data-confirm="Remover esta tarefa?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white border border-rose-200 text-rose-700 hover:bg-rose-50">
                                            Excluir
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">Sem compromissos.</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>

    <div id="agendaDiaModal" class="fixed inset-0 z-[90] hidden flex items-center justify-center bg-black/50 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-5 py-4 border-b border-slate-100 bg-emerald-600 text-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Compromissos</h2>
                    <p class="text-xs text-emerald-50" id="agendaDiaLabel"></p>
                </div>
                <button type="button" id="btnFecharAgendaDia" class="h-9 w-9 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500">X</button>
            </div>
            <div class="p-5 space-y-3" id="agendaDiaConteudo"></div>
        </div>
    </div>

    <div id="agendaModal" class="fixed inset-0 z-[90] hidden flex items-center justify-center bg-black/50 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900" id="agendaModalTitle">Nova Tarefa</h2>
                <button type="button" id="btnFecharAgendaModal" class="h-9 w-9 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500">X</button>
            </div>
            <form method="POST" action="{{ route('comercial.agenda.store') }}" class="p-5 space-y-4" id="agendaForm">
                @csrf
                <input type="hidden" name="_method" id="agendaFormMethod" value="POST">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600">Titulo *</label>
                    <input name="titulo" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Ex: Retorno com cliente XPTO">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600">Descricao</label>
                    <textarea name="descricao" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Detalhes ou observacoes"></textarea>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Tipo *</label>
                        <select name="tipo" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option>Retorno Cliente</option>
                            <option>Reuniao</option>
                            <option>Follow-up</option>
                            <option selected>Tarefa</option>
                            <option>Outro</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Prioridade *</label>
                        <select name="prioridade" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="Baixa">Baixa</option>
                            <option value="Media" selected>Media</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Data *</label>
                        <input type="date" name="data" value="{{ $dataSelecionada->toDateString() }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Hora</label>
                        <input type="time" name="hora" value="{{ now()->format('H:i') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600">Cliente (opcional)</label>
                    <input name="cliente" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Nome do cliente">
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" id="btnCancelarAgenda" class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="px-4 py-2 rounded-xl bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600" id="agendaSubmitBtn">Criar Tarefa</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('agendaModal');
            const form = document.getElementById('agendaForm');
            const btnAbrir = document.getElementById('btnAbrirModalAgenda');
            const btnFechar = document.getElementById('btnFecharAgendaModal');
            const btnCancelar = document.getElementById('btnCancelarAgenda');
            const title = document.getElementById('agendaModalTitle');
            const submitBtn = document.getElementById('agendaSubmitBtn');
            const methodInput = document.getElementById('agendaFormMethod');
            const storeAction = @json(route('comercial.agenda.store'));
            const updateActionTemplate = @json(route('comercial.agenda.update', ['tarefa' => '__ID__']));

            function openModal() {
                if (!modal) return;
                modal.classList.remove('hidden');
            }

            function closeModal() {
                if (!modal) return;
                modal.classList.add('hidden');
                if (form) {
                    form.reset();
                    const dataInput = form.querySelector('[name="data"]');
                    const horaInput = form.querySelector('[name="hora"]');
                    if (dataInput) dataInput.value = '{{ $dataSelecionada->toDateString() }}';
                    if (horaInput) horaInput.value = '{{ now()->format('H:i') }}';
                }
                if (form) form.action = storeAction;
                if (methodInput) methodInput.value = 'POST';
                if (title) title.textContent = 'Nova Tarefa';
                if (submitBtn) submitBtn.textContent = 'Criar Tarefa';
            }

            btnAbrir?.addEventListener('click', openModal);
            btnFechar?.addEventListener('click', closeModal);
            btnCancelar?.addEventListener('click', closeModal);
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            document.addEventListener('click', (e) => {
                const target = e.target;
                if (!(target instanceof HTMLElement)) return;
                const btn = target.closest('.js-editar-agenda');
                if (!btn) return;
                const data = btn.dataset;
                if (!form) return;

                form.action = updateActionTemplate.replace('__ID__', data.id || '');
                if (methodInput) methodInput.value = 'PUT';
                if (title) title.textContent = 'Editar Tarefa';
                if (submitBtn) submitBtn.textContent = 'Salvar Alteracoes';

                form.querySelector('[name="titulo"]')?.setAttribute('value', data.titulo || '');
                const titulo = form.querySelector('[name="titulo"]');
                if (titulo) titulo.value = data.titulo || '';
                const descricao = form.querySelector('[name="descricao"]');
                if (descricao) descricao.value = data.descricao || '';
                const tipo = form.querySelector('[name="tipo"]');
                if (tipo) tipo.value = data.tipo || 'Tarefa';
                const prioridade = form.querySelector('[name="prioridade"]');
                if (prioridade) prioridade.value = data.prioridade || 'Media';
                const dataInput = form.querySelector('[name="data"]');
                if (dataInput) dataInput.value = data.data || '{{ $dataSelecionada->toDateString() }}';
                const horaInput = form.querySelector('[name="hora"]');
                if (horaInput) horaInput.value = data.hora || '';
                const cliente = form.querySelector('[name="cliente"]');
                if (cliente) cliente.value = data.cliente || '';

                openModal();
            });
        })();
    </script>

    <script>
        (function () {
            const modal = document.getElementById('agendaDiaModal');
            const btnClose = document.getElementById('btnFecharAgendaDia');
            const label = document.getElementById('agendaDiaLabel');
            const content = document.getElementById('agendaDiaConteudo');

            function openModal(dateLabel, html) {
                if (!modal || !content || !label) return;
                label.textContent = dateLabel;
                content.innerHTML = html || '<div class="text-sm text-slate-500">Sem compromissos.</div>';
                modal.classList.remove('hidden');
            }

            function closeModal() {
                modal?.classList.add('hidden');
            }

            document.querySelectorAll('.agenda-dia').forEach(btn => {
                btn.addEventListener('click', () => {
                    const date = btn.dataset.date;
                    const dateLabel = btn.dataset.label || date;
                    const container = document.getElementById('agenda-dia-' + date);
                    openModal(dateLabel, container ? container.innerHTML : '');
                });
            });

            btnClose?.addEventListener('click', closeModal);
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
        })();
    </script>
@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.flatpickr) {
                return;
            }
            if (flatpickr.l10ns && flatpickr.l10ns.pt) {
                flatpickr.localize(flatpickr.l10ns.pt);
            }
            function maskBrDate(value) {
                const digits = (value || '').replace(/\D+/g, '').slice(0, 8);
                if (digits.length <= 2) return digits;
                if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
                return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
            }
            document.querySelectorAll('input[type="date"]').forEach((input) => {
                if (input.dataset.fpBound) return;
                input.dataset.fpBound = '1';
                const fp = flatpickr(input, {
                    allowInput: true,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    altInputClass: input.className,
                });
                if (fp && fp.altInput) {
                    fp.altInput.addEventListener('input', () => {
                        fp.altInput.value = maskBrDate(fp.altInput.value);
                    });
                }
            });
        });
    </script>
@endpush@endsection

