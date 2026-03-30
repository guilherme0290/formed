@extends('layouts.comercial')
@section('title', 'Proposta Rápida')

@section('content')
    <div class="max-w-6xl mx-auto px-4 md:px-6 py-6 space-y-6">
        @if(session('ok'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Proposta rápida</div>
                <h1 class="text-2xl font-semibold text-slate-900">{{ $proposta->codigo ?? ('#' . $proposta->id) }}</h1>
                <p class="text-sm text-slate-500">Vendedor: {{ $proposta->vendedor?->name ?? '—' }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('comercial.propostas.rapidas.index') }}"
                   class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Voltar
                </a>
                <a href="{{ route('comercial.propostas.rapidas.edit', $proposta) }}"
                   class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                    Editar
                </a>
                <a href="{{ route('comercial.propostas.rapidas.pdf', $proposta) }}"
                   class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                    Baixar PDF
                </a>
                <form method="POST" action="{{ route('comercial.propostas.rapidas.destroy', $proposta) }}" onsubmit="return confirm('Deseja excluir esta proposta rápida?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                        Excluir
                    </button>
                </form>
            </div>
        </div>

        @include('comercial.propostas-rapidas._documento', ['proposta' => $proposta, 'printMode' => false])
    </div>
@endsection
