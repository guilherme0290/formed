@extends('layouts.master')

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Acessos & Usuários</h1>
        <div class="text-sm text-gray-500">Gerencie perfis, permissões e usuários</div>
    </div>
@endsection

@section('content')
    <div class="max-w-5xl mx-auto pt-4 md:pt-6">
        {{-- Abas simples por query ?tab= --}}
        @php $tab = request('tab','usuarios'); @endphp

        <div class="mb-4 flex flex-wrap gap-2 justify-center">
            <a href="{{ route('master.acessos',['tab'=>'usuarios']) }}"
               class="px-3 py-1.5 rounded-lg border {{ $tab==='usuarios' ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-50' }}">
                Usuários
            </a>
            <a href="{{ route('master.acessos',['tab'=>'papeis']) }}"
               class="px-3 py-1.5 rounded-lg border {{ $tab==='papeis' ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-50' }}">
                Perfis
            </a>
            <a href="{{ route('master.acessos',['tab'=>'permissoes']) }}"
               class="px-3 py-1.5 rounded-lg border {{ $tab==='permissoes' ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-50' }}">
                Permissões
            </a>
            <a href="{{ route('master.acessos',['tab'=>'senhas']) }}"
               class="px-3 py-1.5 rounded-lg border {{ $tab==='senhas' ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-50' }}">
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
        @else
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-center">
                Aba inválida.
            </div>
        @endif
    </div>
@endsection
