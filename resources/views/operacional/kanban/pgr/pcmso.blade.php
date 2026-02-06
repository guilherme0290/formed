@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')


@section('title', 'PGR - PCMSO')

@section('content')
    @php
        $origem = request()->query('origem');
        $rotaVoltar = $origem === 'cliente' ? route('cliente.dashboard') : route('operacional.kanban');
    @endphp
    <div class="w-full px-2 sm:px-3 md:px-4 xl:px-5 py-4 md:py-6">

        <div class="mb-4">
            <a href="{{ $rotaVoltar }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>←</span>
                <span>Voltar ao Painel</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-emerald-700 px-4 sm:px-5 md:px-6 py-4">
                <h1 class="text-lg md:text-xl font-semibold text-white mb-1">
                    Precisa de PCMSO?
                </h1>
                <p class="text-xs md:text-sm text-emerald-100">
                    Vai precisar do PCMSO para usar as mesmas informações do PGR?
                </p>
            </div>

            <div class="px-4 sm:px-5 md:px-6 py-5 md:py-6">
                <form method="POST" action="{{ route('operacional.kanban.pgr.pcmso.store', ['tarefa' => $tarefa, 'origem' => $origem]) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="origem" value="{{ $origem }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <button type="submit" name="com_pcms0" value="1"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                            Sim, criar PGR + PCMSO
                        </button>

                        <button type="submit" name="com_pcms0" value="0"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Não, apenas PGR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
