@extends('layouts.comercial')
@section('title', 'Painel Comercial')
@section('page-container', 'w-full px-0 py-3')

@section('content')
    <div class="w-full px-4 md:px-6 lg:px-10 py-3 space-y-4">

        <header class="space-y-1">
            <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Painel Comercial</h1>
            <p class="text-slate-500 text-sm">
                Gerencie propostas, contratos, comiss&otilde;es e agenda.
            </p>
        </header>

        @if(session('erro'))
            <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
                {{ session('erro') }}
            </div>
        @endif

        <section>
            <div class="rounded-xl shadow overflow-hidden bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600">
                <div class="px-4 md:px-6 py-2 border-b border-white/10 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm md:text-base font-semibold text-white">
                        Ranking de Vendedores - {{ $ranking['mesAtual'] ?? now()->translatedFormat('F Y') }}
                    </h2>
                    <span class="hidden md:inline text-xs text-indigo-100">
                        Atualizado automaticamente conforme a comiss&atilde;o do m&ecirc;s.
                    </span>
                </div>

                <div class="px-3 md:px-4 py-3">
                    @if(!empty($ranking['itens']))
                        <div class="grid gap-4 md:grid-cols-3">
                            @foreach($ranking['itens'] as $item)
                                @php
                                    $classes = [
                                        1 => 'bg-gradient-to-br from-yellow-400 via-amber-400 to-orange-300 text-white',
                                        2 => 'bg-gradient-to-br from-slate-200 via-slate-300 to-slate-400 text-slate-800',
                                        3 => 'bg-gradient-to-br from-orange-400 via-orange-500 to-amber-500 text-white',
                                    ];
                                    $classe = $classes[$item['posicao']] ?? $classes[1];
                                @endphp
                                <article class="{{ $classe }} rounded-lg shadow-md p-3 md:p-4 flex flex-col justify-between">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide">{{ $item['posicao'] }}&ordm; Lugar</p>
                                            <p class="text-base md:text-lg font-bold mt-1">{{ $item['nome'] }}</p>
                                        </div>
                                        <span class="text-2xl font-black opacity-70 leading-none">{{ $item['posicao'] }}&ordm;</span>
                                    </div>
                                    <div class="mt-4">
                                        <p class="text-xs uppercase tracking-wide {{ $item['posicao'] === 2 ? 'text-slate-700' : 'text-white/80' }}">Comiss&atilde;o</p>
                                        <p class="text-xl font-semibold leading-tight">
                                            R$ {{ number_format($item['comissao'], 2, ',', '.') }}
                                        </p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-3">
                            @for($i=1; $i<=3; $i++)
                                <article class="rounded-xl border border-white/10 bg-white/5 backdrop-blur text-white p-4 md:p-5 flex flex-col justify-between animate-pulse">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="space-y-2">
                                            <div class="h-3 w-16 bg-white/30 rounded"></div>
                                            <div class="h-4 w-24 bg-white/20 rounded"></div>
                                        </div>
                                        <span class="text-3xl font-black opacity-40 leading-none">{{ $i }}&ordm;</span>
                                    </div>
                                    <div class="mt-4 space-y-2">
                                        <div class="h-3 w-20 bg-white/20 rounded"></div>
                                        <div class="h-5 w-28 bg-white/30 rounded"></div>
                                    </div>
                                </article>
                            @endfor
                        </div>
                        <div class="text-center text-white text-sm mt-4">
                            Nenhuma comiss&atilde;o registrada neste per&iacute;odo. O ranking ser&aacute; exibido assim que houver comiss&otilde;es no m&ecirc;s.
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <article class="rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-indigo-700 font-semibold">Total de compromissos em aberto</p>
                <p class="text-2xl font-bold text-indigo-900 mt-1">{{ $agendaKpis['aberto_total'] ?? 0 }}</p>
            </article>
            <article class="rounded-xl border border-amber-100 bg-amber-50/70 px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-amber-700 font-semibold">Pendentes do dia</p>
                <p class="text-2xl font-bold text-amber-800 mt-1">{{ $agendaKpis['pendentes_dia'] ?? 0 }}</p>
            </article>
            <article class="rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-emerald-700 font-semibold">Conclu&iacute;das do dia</p>
                <p class="text-2xl font-bold text-emerald-800 mt-1">{{ $agendaKpis['concluidas_dia'] ?? 0 }}</p>
            </article>
        </section>

        <section class="bg-white rounded-xl border border-indigo-100 shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 text-white px-3 py-2 flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-2">
                    <a href="{{ route('comercial.dashboard', ['agenda_data' => $agendaMesAnterior->toDateString(), 'agenda_dia' => $agendaMesAnterior->copy()->startOfMonth()->toDateString()]) }}"
                       class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold">
                        M&ecirc;s anterior
                    </a>
                    <form method="GET" action="{{ route('comercial.dashboard') }}" class="flex items-center gap-2">
                        <input type="month"
                               name="agenda_data"
                               value="{{ $agendaDataSelecionada->format('Y-m') }}"
                               onchange="this.form.submit()"
                               class="px-2.5 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold text-white border border-white/20">
                    </form>
                </div>

                <div class="text-center">
                    <p class="text-[11px] uppercase tracking-[0.2em] text-indigo-100">M&ecirc;s selecionado</p>
                    <p class="text-xl font-semibold leading-none mt-1">{{ $agendaDataSelecionada->locale('pt_BR')->translatedFormat('F \d\e Y') }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="btnAbrirModalAgenda"
                            class="px-3 py-2 rounded-lg bg-blue-500 hover:bg-blue-400 text-xs font-semibold">
                        Nova tarefa
                    </button>
                    <a href="{{ route('comercial.dashboard', ['agenda_data' => $agendaMesProximo->toDateString(), 'agenda_dia' => $agendaMesProximo->copy()->startOfMonth()->toDateString()]) }}"
                       class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold">
                        Pr&oacute;ximo m&ecirc;s
                    </a>
                </div>
            </div>

            <div class="p-0">
                <div class="grid gap-0 lg:grid-cols-2 items-stretch">
                    <div class="rounded-none border-0 lg:border-r lg:border-slate-200 px-2 md:px-2.5 pt-2 md:pt-2.5 pb-0 h-[390px]">
                        @php
                            $agendaSemanas = max(1, (int) ceil(count($agendaDias) / 7));
                        @endphp
                        <div class="h-full grid grid-cols-7 gap-1.5" style="grid-template-rows: repeat({{ $agendaSemanas }}, minmax(0, 1fr));">
                            @php
                                $labelsSemana = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'];
                            @endphp
                            @foreach ($agendaDias as $dia)
                                @if (!$dia)
                                    <div class="h-full min-h-[68px] rounded-lg border border-transparent bg-slate-50/70"></div>
                                    @continue
                                @endif
                                @php
                                    $dataStr = $dia->toDateString();
                                    $contagens = $agendaContagensPorData[$dataStr] ?? ['pendentes' => 0, 'concluidas' => 0];
                                    $selecionado = $agendaDiaSelecionado === $dataStr;
                                    $mesCurto = mb_strtoupper(rtrim($dia->locale('pt_BR')->translatedFormat('M'), '.'), 'UTF-8');
                                    $labelDiaSemana = $labelsSemana[$dia->dayOfWeek] ?? '';
                                @endphp
                                <button type="button"
                                        class="agenda-dia h-full min-h-[68px] rounded-lg border px-1 py-1 text-center transition {{ $selecionado ? 'bg-indigo-600 border-indigo-600 text-white shadow' : 'bg-slate-50 border-slate-200 text-slate-700 hover:border-blue-300 hover:bg-blue-50/50' }}"
                                        data-date="{{ $dataStr }}"
                                        data-label="{{ $dia->format('d/m/Y') }}">
                                    <div class="text-[9px] font-semibold tracking-wide {{ $selecionado ? 'text-indigo-100' : 'text-slate-500' }}">{{ $labelDiaSemana }}</div>
                                    <div class="text-lg md:text-xl font-bold leading-none mt-0.5">{{ $dia->day }}</div>
                                    <div class="text-[8px] mt-0.5 font-semibold {{ $selecionado ? 'text-indigo-100' : 'text-slate-500' }}">{{ $mesCurto }}</div>
                                    <div class="mt-0.5 flex items-center justify-center gap-1">
                                        @if ($contagens['pendentes'] > 0)
                                            <span class="inline-flex h-1.5 w-1.5 rounded-full {{ $selecionado ? 'bg-amber-300' : 'bg-amber-500' }}"></span>
                                        @endif
                                        @if ($contagens['concluidas'] > 0)
                                            <span class="inline-flex h-1.5 w-1.5 rounded-full {{ $selecionado ? 'bg-emerald-200' : 'bg-emerald-500' }}"></span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <aside class="rounded-none border-0 bg-slate-50/60 overflow-hidden min-h-[390px]">
                        <div class="px-4 py-3 border-b border-slate-200 bg-white/70">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Detalhes do dia</p>
                            <p class="text-lg font-semibold text-slate-800" id="agendaSideLabel">{{ \Carbon\Carbon::parse($agendaDiaSelecionado)->format('d/m/Y') }}</p>
                        </div>
                        <div class="p-3 space-y-3 h-[390px] overflow-y-auto" id="agendaSideConteudo">
                            <div class="h-full min-h-[240px] flex flex-col items-center justify-start pt-6 text-center gap-3">
                                <div class="relative w-[180px] h-[180px]">
                                    <div id="agenda-empty-lottie" class="absolute inset-0"></div>
                                    <div id="agenda-empty-fallback" class="absolute inset-0 flex items-center justify-center text-indigo-400">
                                        <i class="fa-regular fa-calendar-xmark text-6xl"></i>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Sem compromisso para hoje</p>
                                    <p class="text-xs text-slate-500 mt-1">Selecione outro dia ou crie uma nova tarefa.</p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <div class="hidden">
            @foreach ($agendaTarefasPorData as $dataStr => $tarefasDia)
                <div id="agenda-dia-{{ $dataStr }}" data-has-itens="{{ $tarefasDia->count() > 0 ? '1' : '0' }}">
                    @forelse ($tarefasDia as $tarefa)
                        @php
                            $isConcluida = $tarefa->status === 'CONCLUIDA';
                            $cardClasses = $isConcluida
                                ? 'bg-emerald-50/40 border-emerald-200'
                                : 'bg-amber-50/40 border-amber-200';
                            $accentClasses = $isConcluida
                                ? 'border-l-emerald-500'
                                : 'border-l-amber-500';
                        @endphp
                        <div class="rounded-lg border border-l-4 shadow-sm px-3 py-2.5 space-y-2 {{ $cardClasses }} {{ $accentClasses }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[15px] leading-tight font-semibold text-slate-900 truncate">{{ $tarefa->titulo }}</p>
                                    @if($tarefa->descricao)
                                        <p class="text-xs text-slate-600 mt-0.5 line-clamp-2">{{ $tarefa->descricao }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $isConcluida ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {!! $isConcluida ? 'Conclu&iacute;da' : 'Pendente' !!}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                <div><span class="font-medium text-slate-600">Hora:</span> {{ $tarefa->hora?->format('H:i') ?? '--:--' }}</div>
                                @if($tarefa->cliente)
                                    <div><span class="font-medium text-slate-600">Cliente:</span> {{ $tarefa->cliente }}</div>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-1.5 text-xs">
                                @if (!$isConcluida)
                                    <form method="POST" action="{{ route('comercial.agenda.concluir', $tarefa) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="origem" value="dashboard">
                                        <input type="hidden" name="agenda_data" value="{{ $agendaDataSelecionada->toDateString() }}">
                                        <button class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                                            Concluir
                                        </button>
                                    </form>
                                    <button type="button"
                                            class="js-editar-agenda inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-slate-900 text-white hover:bg-slate-800"
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
                                        <input type="hidden" name="origem" value="dashboard">
                                        <input type="hidden" name="agenda_data" value="{{ $agendaDataSelecionada->toDateString() }}">
                                        <button class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-white border border-rose-200 text-rose-700 hover:bg-rose-50">
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

    <div id="agendaModal" class="fixed inset-0 z-[90] hidden flex items-center justify-center bg-black/50 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900" id="agendaModalTitle">Nova tarefa</h2>
                <button type="button" id="btnFecharAgendaModal" class="h-9 w-9 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500">X</button>
            </div>
            <form method="POST" action="{{ route('comercial.agenda.store') }}" class="p-5 space-y-4" id="agendaForm">
                @csrf
                <input type="hidden" name="_method" id="agendaFormMethod" value="POST">
                <input type="hidden" name="origem" value="dashboard">
                <input type="hidden" name="agenda_data" value="{{ $agendaDataSelecionada->toDateString() }}">

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600">T&iacute;tulo *</label>
                    <input name="titulo" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Ex: Retorno com cliente XPTO">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600">Descri&ccedil;&atilde;o</label>
                    <textarea name="descricao" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Detalhes ou observa&ccedil;&otilde;es"></textarea>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Tipo *</label>
                        <select name="tipo" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option>Retorno Cliente</option>
                            <option>Reuni&atilde;o</option>
                            <option>Follow-up</option>
                            <option selected>Tarefa</option>
                            <option>Outro</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Prioridade *</label>
                        <select name="prioridade" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="Baixa">Baixa</option>
                            <option value="Media" selected>M&eacute;dia</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Data *</label>
                        <input type="date" name="data" value="{{ $agendaDiaSelecionado }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
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

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" id="btnCancelarAgenda" class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700" id="agendaSubmitBtn">Criar tarefa</button>
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
                    if (dataInput) dataInput.value = '{{ $agendaDiaSelecionado }}';
                    if (horaInput) horaInput.value = '{{ now()->format('H:i') }}';
                }
                if (form) form.action = storeAction;
                if (methodInput) methodInput.value = 'POST';
                if (title) title.textContent = 'Nova tarefa';
                if (submitBtn) submitBtn.textContent = 'Criar tarefa';
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
                if (title) title.textContent = 'Editar tarefa';
                if (submitBtn) submitBtn.textContent = 'Salvar alterações';

                const titulo = form.querySelector('[name="titulo"]');
                if (titulo) titulo.value = data.titulo || '';
                const descricao = form.querySelector('[name="descricao"]');
                if (descricao) descricao.value = data.descricao || '';
                const tipo = form.querySelector('[name="tipo"]');
                if (tipo) tipo.value = data.tipo || 'Tarefa';
                const prioridade = form.querySelector('[name="prioridade"]');
                if (prioridade) prioridade.value = data.prioridade || 'Media';
                const dataInput = form.querySelector('[name="data"]');
                if (dataInput) dataInput.value = data.data || '{{ $agendaDiaSelecionado }}';
                const horaInput = form.querySelector('[name="hora"]');
                if (horaInput) horaInput.value = data.hora || '';
                const cliente = form.querySelector('[name="cliente"]');
                if (cliente) cliente.value = data.cliente || '';

                openModal();
            });
        })();
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js"></script>

    @if(session('ok'))
        <div id="successPopup" class="fixed inset-0 z-[120] flex items-center justify-center bg-black/35 p-4">
            <div class="w-full max-w-sm rounded-2xl border border-emerald-200 bg-white shadow-xl overflow-hidden">
                <div class="px-4 py-3 bg-emerald-50 border-b border-emerald-100">
                    <p class="text-sm font-semibold text-emerald-700">Sucesso</p>
                </div>
                <div class="px-4 py-4 space-y-3">
                    <p class="text-sm text-slate-700">{{ session('ok') }}</p>
                    <div class="flex justify-end">
                        <button type="button" id="btnCloseSuccessPopup"
                                class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                            Ok
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const popup = document.getElementById('successPopup');
            const closeBtn = document.getElementById('btnCloseSuccessPopup');
            if (!popup) return;

            function closePopup() {
                popup.classList.add('hidden');
            }

            closeBtn?.addEventListener('click', closePopup);
            popup.addEventListener('click', (e) => {
                if (e.target === popup) closePopup();
            });
            setTimeout(closePopup, 2400);
        })();
    </script>

    <script>
        (function () {
            const label = document.getElementById('agendaSideLabel');
            const content = document.getElementById('agendaSideConteudo');
            let emptyLottieInstance = null;
            const emptyStateHtml = `
                <div class="h-full min-h-[240px] flex flex-col items-center justify-start pt-6 text-center gap-3">
                    <div class="relative w-[180px] h-[180px]">
                        <div id="agenda-empty-lottie" class="absolute inset-0"></div>
                        <div id="agenda-empty-fallback" class="absolute inset-0 flex items-center justify-center text-indigo-400">
                            <i class="fa-regular fa-calendar-xmark text-6xl"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Sem compromisso para hoje</p>
                        <p class="text-xs text-slate-500 mt-1">Selecione outro dia ou crie uma nova tarefa.</p>
                    </div>
                </div>
            `;

            function initEmptyLottie() {
                const el = document.getElementById('agenda-empty-lottie');
                const fallback = document.getElementById('agenda-empty-fallback');
                if (!el || typeof window.lottie === 'undefined') return;
                if (fallback) fallback.classList.remove('hidden');
                if (emptyLottieInstance) {
                    emptyLottieInstance.destroy();
                    emptyLottieInstance = null;
                }
                emptyLottieInstance = window.lottie.loadAnimation({
                    container: el,
                    renderer: 'svg',
                    loop: true,
                    autoplay: true,
                    path: 'https://assets9.lottiefiles.com/packages/lf20_t24tpvcu.json',
                });
                emptyLottieInstance.addEventListener('DOMLoaded', () => {
                    if (fallback) fallback.classList.add('hidden');
                });
                emptyLottieInstance.addEventListener('data_failed', () => {
                    if (fallback) fallback.classList.remove('hidden');
                });
            }

            function updateSidePanel(dateLabel, html, hasItems, taskCount = 0) {
                if (!content || !label) return;
                label.textContent = dateLabel;
                content.innerHTML = hasItems ? html : emptyStateHtml;

                if (!hasItems) {
                    initEmptyLottie();
                } else if (emptyLottieInstance) {
                    emptyLottieInstance.destroy();
                    emptyLottieInstance = null;
                }
            }

            document.querySelectorAll('.agenda-dia').forEach(btn => {
                btn.addEventListener('click', () => {
                    const date = btn.dataset.date;
                    const dateLabel = btn.dataset.label || date;
                    const container = document.getElementById('agenda-dia-' + date);
                    const taskCount = container ? container.querySelectorAll('.rounded-xl').length : 0;
                    const hasItems = !!container && (
                        container?.dataset.hasItens === '1' ||
                        taskCount > 0
                    );
                    updateSidePanel(dateLabel, container ? container.innerHTML : '', hasItems, taskCount);

                    document.querySelectorAll('.agenda-dia').forEach(item => {
                        item.classList.remove('bg-indigo-600', 'border-indigo-600', 'text-white', 'shadow');
                        item.classList.add('bg-slate-50', 'border-slate-200', 'text-slate-700');
                    });
                    btn.classList.remove('bg-slate-50', 'border-slate-200', 'text-slate-700');
                    btn.classList.add('bg-indigo-600', 'border-indigo-600', 'text-white', 'shadow');
                });
            });

            const initialDate = @json($agendaDiaSelecionado);
            const initialBtn = document.querySelector('.agenda-dia[data-date="' + initialDate + '"]');
            if (initialBtn instanceof HTMLElement) {
                initialBtn.click();
            } else {
                updateSidePanel(@json(\Carbon\Carbon::parse($agendaDiaSelecionado)->format('d/m/Y')), '', false, 0);
            }
        })();
    </script>
@endsection
