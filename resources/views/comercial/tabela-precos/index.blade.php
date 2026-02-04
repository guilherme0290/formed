@extends('layouts.comercial')
@section('title', 'Tabela de Preços')
@section('page-container', 'w-full p-0')

@section('content')
    <div class="w-full px-2 sm:px-3 md:px-4 py-2 md:py-3 space-y-6">

        <header>
            <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Tabela de Preços</h1>
            <p class="text-slate-500 text-sm md:text-base mt-1">
                Tabela padrão utilizada como base para novas propostas.
            </p>
        </header>

        @if (session('ok'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        <section class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">{{ $padrao->nome }}</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Apenas uma tabela padrão ativa por empresa.
                    </p>
                </div>

                <span class="text-xs px-3 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">
                Ativa
            </span>
            </div>

            <div class="px-6 py-6 grid md:grid-cols-3 gap-6">
                {{-- Info --}}
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs text-slate-500">Empresa</p>
                    <p class="font-medium text-slate-800 mt-1">{{ $padrao->empresa->razao_social ?? '—' }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs text-slate-500">Itens cadastrados</p>
                    <p class="font-semibold text-slate-800 mt-1 text-xl">
                        {{ $padrao->itens->count() }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs text-slate-500">Última atualização</p>
                    <p class="font-medium text-slate-800 mt-1">
                        {{ $padrao->updated_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                <a href="{{ route('comercial.tabela-precos.itens.index') }}"
                   class="rounded-2xl bg-slate-900 hover:bg-slate-800 text-white px-5 py-2 text-sm font-semibold shadow">
                    Gerenciar Itens →
                </a>
            </div>
        </section>

    </div>
@endsection
