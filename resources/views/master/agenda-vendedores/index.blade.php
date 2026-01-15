@extends('layouts.master')
@section('title', 'Agenda de Vendedores')

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

    $mostrarVendedor = $vendedorSelecionado === 'todos';
@endphp

@section('content')
    <div class="w-full mx-auto px-4 md:px-6 xl:px-8 py-6 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <a href="{{ route('master.dashboard') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                Voltar ao Painel
            </a>
            <button type="button"
                    id="btnAbrirModalAgenda"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-500 text-white text-sm font-semibold shadow hover:bg-orange-600">
                Nova Tarefa
            </button>
        </div>

        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Agenda de Vendedores</h1>
                <p class="text-sm text-slate-500 mt-1">Visão ampla da agenda comercial por data.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @php
                    $rotuloPeriodo = $periodo === 'ano'
                        ? $dataSelecionada->locale('pt_BR')->translatedFormat('Y')
                        : $dataSelecionada->locale('pt_BR')->translatedFormat('F Y');
                @endphp
                <form method="GET" action="{{ route('master.agenda-vendedores.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="date" name="data" value="{{ $dataSelecionada->toDateString() }}"
                           class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                    <select name="periodo" class="rounded-xl border border-slate-200 bg-white px-3 py-2 pr-9 text-sm">
                        <option value="mes" @selected($periodo === 'mes')>Mes</option>
                        <option value="ano" @selected($periodo === 'ano')>Ano</option>
                    </select>
                    <select name="vendedor" class="rounded-xl border border-slate-200 bg-white px-3 py-2 pr-9 text-sm">
                        <option value="todos" @selected($vendedorSelecionado === 'todos')>Todos os vendedores</option>
                        @foreach ($vendedores as $vendedor)
                            <option value="{{ $vendedor->id }}" @selected($vendedorSelecionado == $vendedor->id)>
                                {{ $vendedor->name }}
                            </option>
                        @endforeach
                    </select>
                    <button class="px-3 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">
                        Aplicar
                    </button>
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
            @php
                $tituloTotal = $vendedorSelecionado === 'todos'
                    ? 'Total de tarefas dos comerciais'
                    : ('Total de tarefas - '.($vendedores->firstWhere('id', (int) $vendedorSelecionado)?->name ?? 'Vendedor'));
            @endphp
            <div class="rounded-2xl border border-slate-100 bg-white shadow-sm p-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $tituloTotal }}</p>
                <p class="text-3xl font-bold text-slate-900 mt-2">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50/70 shadow-sm p-4">
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide">Pendentes</p>
                <p class="text-3xl font-bold text-amber-700 mt-2">{{ $kpis['pendentes'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 shadow-sm p-4">
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Concluidas</p>
                <p class="text-3xl font-bold text-emerald-700 mt-2">{{ $kpis['concluidas'] }}</p>
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
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 space-y-3">
                            <div class="text-sm font-semibold text-slate-800">{{ $calendario['titulo'] }}</div>
                            <div class="grid grid-cols-7 text-[10px] uppercase tracking-wide font-semibold text-slate-900">
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
                            $cardClasses = $isConcluida
                                ? 'bg-emerald-50/40 border-emerald-100'
                                : 'bg-amber-50/40 border-amber-100';
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
                                @if($mostrarVendedor)
                                    <div>Vendedor: {{ $tarefa->usuario?->name ?? 'Nao informado' }}</div>
                                @endif
                            </div>
                            <div class="flex items-center justify-end gap-2 text-sm">
                                @if (!$isConcluida)
                                    <form method="POST" action="{{ route('master.agenda-vendedores.concluir', $tarefa) }}">
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
                                            data-cliente="{{ $tarefa->cliente }}"
                                            data-vendedor="{{ $tarefa->user_id }}">
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('master.agenda-vendedores.destroy', $tarefa) }}" onsubmit="return confirm('Remover esta tarefa?')">
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

    <div id="agendaDiaModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-emerald-600 text-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Compromissos</h2>
                    <p class="text-xs text-emerald-50" id="agendaDiaLabel"></p>
                </div>
                <button type="button" id="btnFecharAgendaDia" class="h-9 w-9 flex items-center justify-center rounded-xl hover:bg-emerald-500 text-white">X</button>
            </div>
            <div class="p-5 space-y-3" id="agendaDiaConteudo"></div>
        </div>
    </div>

    <div id="agendaModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900" id="agendaModalTitle">Nova Tarefa</h2>
                <button type="button" id="btnFecharAgendaModal" class="h-9 w-9 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500">X</button>
            </div>
            <form method="POST" action="{{ route('master.agenda-vendedores.store') }}" class="p-5 space-y-4" id="agendaForm">
                @csrf
                <input type="hidden" name="_method" id="agendaFormMethod" value="POST">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600">Vendedor *</label>
                    <select name="user_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                        <option value="">Selecione um vendedor</option>
                        @foreach ($vendedores as $vendedor)
                            <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                        @endforeach
                    </select>
                </div>
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
            const storeAction = @json(route('master.agenda-vendedores.store'));
            const updateActionTemplate = @json(route('master.agenda-vendedores.update', ['tarefa' => '__ID__']));
            const vendedorSelecionado = @json($vendedorSelecionado);

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
                    const vendedorInput = form.querySelector('[name="user_id"]');
                    if (dataInput) dataInput.value = '{{ $dataSelecionada->toDateString() }}';
                    if (horaInput) horaInput.value = '{{ now()->format('H:i') }}';
                    if (vendedorInput && vendedorSelecionado && vendedorSelecionado !== 'todos') {
                        vendedorInput.value = vendedorSelecionado;
                    }
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

                const vendedorInput = form.querySelector('[name="user_id"]');
                if (vendedorInput) vendedorInput.value = data.vendedor || '';
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

            closeModal();
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
@endsection
