@extends('layouts.master')

@section('header')
    <div class="flex items-center justify-between" data-acessos-header>
        <h1 class="text-2xl font-semibold">Acessos & Usuários</h1>
        <div class="text-sm text-gray-500">Gerencie perfis e usuários</div>
    </div>
@endsection

@section('content')
    <div class="w-full mx-auto px-4 md:px-6 xl:px-8 pt-4 md:pt-6">
        <div class="mb-4">
            <a href="{{ route('master.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                Voltar ao Painel
            </a>
        </div>

        @php
            $tab = request('tab', 'usuarios');
            if (!in_array($tab, ['usuarios', 'papeis', 'permissoes', 'senhas'], true)) {
                $tab = 'usuarios';
            }
        @endphp

        <div class="mb-4 flex flex-wrap gap-2 justify-center" data-acessos-tabs>
            <a href="{{ route('master.acessos',['tab'=>'usuarios']) }}"
               class="px-4 py-2 rounded-lg border text-sm md:text-base {{ $tab==='usuarios' ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-50' }}">
                Usuários
            </a>
            <a href="{{ route('master.acessos',['tab'=>'papeis']) }}"
               class="px-4 py-2 rounded-lg border text-sm md:text-base {{ $tab==='papeis' ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-50' }}">
                Perfis
            </a>
            <a href="{{ route('master.acessos',['tab'=>'permissoes']) }}"
               class="px-4 py-2 rounded-lg border text-sm md:text-base {{ $tab==='permissoes' ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-50' }}">
                Permissões
            </a>
            <a href="{{ route('master.acessos',['tab'=>'senhas']) }}"
               class="px-4 py-2 rounded-lg border text-sm md:text-base {{ $tab==='senhas' ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-50' }}">
                Senhas
            </a>
        </div>

        @if($tab==='usuarios')
            @includeIf('master.acessos.usuarios-index')
        @elseif($tab==='papeis')
            @includeIf('master.acessos.papeis-index')
        @elseif($tab==='permissoes')
            @includeIf('master.acessos.permissoes')
        @elseif($tab==='senhas')
            @includeIf('master.acessos.senhas', [])
        @endif
    </div>
@endsection
