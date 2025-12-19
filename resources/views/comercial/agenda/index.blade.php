@extends('layouts.comercial')
@section('title', 'Agenda Comercial')

@php
    $tipoBadges = [
        'Retorno Cliente' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'icon' => '↩'],
        'Reunião' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'icon' => '🗓'],
        'Follow-up' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'icon' => '⟳'],
        'Tarefa' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'icon' => '•'],
        'Outro' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'icon' => '•'],
    ];

    $prioridadeBadges = [
        'Baixa' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'label' => '🟢 Baixa'],
        'Media' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'label' => '🟡 Média'],
        'Alta'  => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'label' => '🔴 Alta'],
    ];
@endphp

@section('content')
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <a href="{{ route('comercial.dashboard') }}" class="text-sm text-slate-600 hover:text-slate-800 inline-flex items-center gap-2">
                ← Voltar ao Painel
            </a>
            <button type="button"
                    id="btnAbrirModalAgenda"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-500 text-white text-sm font-semibold shadow hover:bg-orange-600">
                ➕ Nova Tarefa
            </button>
        </div>

        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Agenda Comercial</h1>
                <p class="text-sm text-slate-500 mt-1">Foco diário nas atividades comerciais.</p>
            </div>

            <div class="flex items-center gap-2">
                @php
                    $ontem = $dataSelecionada->copy()->subDay()->toDateString();
                    $amanha = $dataSelecionada->copy()->addDay()->toDateString();
                @endphp
                <a href="{{ route('comercial.agenda.index', ['data' => $ontem]) }}"
                   class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 text-sm">◀</a>

                <div class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">
                    {{ $dataSelecionada->translatedFormat('d \\d\\e F \\d\\e Y') }}
                </div>

                <a href="{{ route('comercial.agenda.index', ['data' => $amanha]) }}"
                   class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 text-sm">▶</a>

                <a href="{{ route('comercial.agenda.index', ['data' => now()->toDateString()]) }}"
                   class="px-3 py-2 rounded-xl border border-orange-200 text-orange-700 bg-orange-50 hover:bg-orange-100 text-sm">
                    Hoje
                </a>

                <span class="px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 bg-white">📅 Dia</span>
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

        {{-- KPIs --}}
        <div class="grid md:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-slate-100 bg-white shadow-sm p-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</p>
                <p class="text-3xl font-bold text-slate-900 mt-2">{{ $kpis['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50/70 shadow-sm p-4">
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide">Pendentes</p>
                <p class="text-3xl font-bold text-amber-700 mt-2">{{ $kpis['pendentes'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 shadow-sm p-4">
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Concluídas</p>
                <p class="text-3xl font-bold text-emerald-700 mt-2">{{ $kpis['concluidas'] }}</p>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            {{-- Pendentes --}}
            <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-amber-800">Pendentes ({{ $pendentes->count() }})</h2>
                </div>

                <div class="space-y-3">
                    @forelse($pendentes as $tarefa)
                        <div class="rounded-xl bg-white border border-slate-100 shadow-sm p-3 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $tarefa->titulo }}</p>
                                    @if($tarefa->cliente)
                                        <p class="text-xs text-slate-500">Cliente: {{ $tarefa->cliente }}</p>
                                    @endif
                                    <p class="text-xs text-slate-500">
                                        {{ $tarefa->data?->format('d/m') }} {{ $tarefa->hora?->format('H:i') }}
                                    </p>
                                </div>
                                <div class="flex flex-col gap-1 items-end">
                                    @php $tp = $tipoBadges[$tarefa->tipo] ?? ['bg'=>'bg-slate-100','text'=>'text-slate-700','icon'=>'•']; @endphp
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full {{ $tp['bg'] }} {{ $tp['text'] }}">
                                        <span>{{ $tp['icon'] }}</span> {{ $tarefa->tipo }}
                                    </span>
                                    @php $pr = $prioridadeBadges[$tarefa->prioridade] ?? $prioridadeBadges['Media']; @endphp
                                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded-full {{ $pr['bg'] }} {{ $pr['text'] }}">
                                        {{ $pr['label'] }}
                                    </span>
                                </div>
                            </div>
                            @if($tarefa->descricao)
                                <p class="text-xs text-slate-600">{{ $tarefa->descricao }}</p>
                            @endif
                            <div class="flex items-center justify-end gap-2 text-sm">
                                <form method="POST" action="{{ route('comercial.agenda.concluir', $tarefa) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                        ✓ Concluir
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('comercial.agenda.destroy', $tarefa) }}" onsubmit="return confirm('Remover esta tarefa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white border border-rose-200 text-rose-700 hover:bg-rose-50">
                                        ❌ Remover
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Nenhuma tarefa pendente.</p>
                    @endforelse
                </div>
            </div>

            {{-- Concluídas --}}
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-emerald-800">Concluídas ({{ $concluidas->count() }})</h2>
                </div>

                <div class="space-y-3">
                    @forelse($concluidas as $tarefa)
                        <div class="rounded-xl bg-white border border-slate-100 shadow-sm p-3 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $tarefa->titulo }}</p>
                                    @if($tarefa->cliente)
                                        <p class="text-xs text-slate-500">Cliente: {{ $tarefa->cliente }}</p>
                                    @endif
                                    <p class="text-xs text-slate-500">
                                        {{ $tarefa->data?->format('d/m') }} {{ $tarefa->hora?->format('H:i') }}
                                    </p>
                                </div>
                                <div class="flex flex-col gap-1 items-end">
                                    @php $tp = $tipoBadges[$tarefa->tipo] ?? ['bg'=>'bg-slate-100','text'=>'text-slate-700','icon'=>'•']; @endphp
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full {{ $tp['bg'] }} {{ $tp['text'] }}">
                                        <span>{{ $tp['icon'] }}</span> {{ $tarefa->tipo }}
                                    </span>
                                    @php $pr = $prioridadeBadges[$tarefa->prioridade] ?? $prioridadeBadges['Media']; @endphp
                                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded-full {{ $pr['bg'] }} {{ $pr['text'] }}">
                                        {{ $pr['label'] }}
                                    </span>
                                </div>
                            </div>
                            @if($tarefa->descricao)
                                <p class="text-xs text-slate-600">{{ $tarefa->descricao }}</p>
                            @endif
                            <p class="text-[11px] text-slate-500">Concluída em {{ optional($tarefa->concluida_em)->format('d/m H:i') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Nenhuma tarefa concluída.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Nova Tarefa --}}
    <div id="agendaModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Nova Tarefa</h2>
                <button type="button" id="btnFecharAgendaModal" class="h-9 w-9 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500">✕</button>
            </div>
            <form method="POST" action="{{ route('comercial.agenda.store') }}" class="p-5 space-y-4" id="agendaForm">
                @csrf
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600">Título *</label>
                    <input name="titulo" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Ex: Retorno com cliente XPTO">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600">Descrição</label>
                    <textarea name="descricao" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Detalhes ou observações"></textarea>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Tipo *</label>
                        <select name="tipo" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option>Retorno Cliente</option>
                            <option>Reunião</option>
                            <option>Follow-up</option>
                            <option selected>Tarefa</option>
                            <option>Outro</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Prioridade *</label>
                        <select name="prioridade" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="Baixa">Baixa</option>
                            <option value="Media" selected>Média</option>
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
                    <button class="px-4 py-2 rounded-xl bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600">Criar Tarefa</button>
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
        })();
    </script>
@endsection
