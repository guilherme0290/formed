@extends('layouts.operacional')

@section('pageTitle', 'APR - Análise Preliminar de Riscos')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('operacional.painel') }}"
               class="inline-flex items-center gap-2 text-xs text-slate-600 mb-4">
                ← Voltar ao Painel
            </a>
        </div>

        <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            {{-- Cabeçalho --}}
            <div class="px-6 py-4 bg-gradient-to-r from-amber-700 to-amber-600 text-white">
                <h1 class="text-lg font-semibold">
                    APR - Análise Preliminar de Riscos
                </h1>
                <p class="text-xs text-white/80 mt-1">
                    {{ $cliente->razao_social }}
                </p>
            </div>

            <form method="POST"
                  action="{{ route('operacional.apr.store', $cliente) }}"
                  class="p-6 space-y-6">
                @csrf

                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700 mb-2">
                        <ul class="list-disc ms-4">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Endereço da Obra/Atividade --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">
                        Endereço da Obra/Atividade *
                    </label>
                    <input type="text"
                           name="endereco_atividade"
                           value="{{ old('endereco_atividade') }}"
                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2
                                  focus:outline-none focus:ring-2 focus:ring-amber-400/70 focus:border-amber-500"
                           placeholder="Local onde será realizada a atividade">
                </div>

                {{-- Funções Envolvidas --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">
                        Funções Envolvidas *
                    </label>
                    <textarea
                        name="funcoes_envolvidas"
                        rows="3"
                        class="w-full rounded-lg border-slate-200 text-sm px-3 py-2
                               focus:outline-none focus:ring-2 focus:ring-amber-400/70 focus:border-amber-500"
                        placeholder="Liste as funções que participarão da atividade (ex: Carpinteiro, Ajudante, Eletricista)"
                    >{{ old('funcoes_envolvidas') }}</textarea>
                </div>

                {{-- Etapas da Atividade --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">
                        Etapas da Atividade (Passo a Passo) *
                    </label>
                    <textarea
                        name="etapas_atividade"
                        rows="5"
                        class="w-full rounded-lg border-slate-200 text-sm px-3 py-2
                               focus:outline-none focus:ring-2 focus:ring-amber-400/70 focus:border-amber-500"
                        placeholder="Descreva passo a passo como a atividade será executada"
                    >{{ old('etapas_atividade') }}</textarea>
                </div>

                {{-- Footer --}}
                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl
                                   bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 shadow-sm">
                        Criar Tarefa APR
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
