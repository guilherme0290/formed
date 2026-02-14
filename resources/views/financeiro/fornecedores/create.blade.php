@extends('layouts.financeiro')
@section('title', 'Novo Fornecedor')
@section('page-container', 'w-full p-0')

@section('content')
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-[0.2em] text-indigo-400">Cadastro</div>
                <h1 class="text-3xl font-semibold text-slate-900">Novo fornecedor</h1>
            </div>
            <a href="{{ route('financeiro.fornecedores.index') }}"
               class="px-3 py-2 rounded-lg bg-slate-200 text-slate-800 text-xs font-semibold hover:bg-slate-300">Voltar</a>
        </div>

        @include('financeiro.partials.tabs')

        @if($errors->any())
            <div class="rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('financeiro.fornecedores.store') }}" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 space-y-5">
            @csrf
            @include('financeiro.fornecedores._form')

            <div class="flex justify-end">
                <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Salvar fornecedor</button>
            </div>
        </form>
    </div>
@endsection
