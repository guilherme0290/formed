@extends('layouts.landing')

@section('content')
    <div class="min-h-screen bg-gradient-to-b from-sky-50 to-indigo-50 flex items-center">
        <div class="max-w-6xl mx-auto px-4 w-full">

            {{-- Título principal --}}
            <div class="text-center mb-12">
                <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">
                    Plataforma de Gestão Corporativa
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Gestão de Saúde e Segurança do Trabalho
                </p>
            </div>

            {{-- Cards dos módulos --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 justify-items-center">

                {{-- Comercial --}}
                <div class="w-full max-w-xs bg-white rounded-3xl shadow-lg border border-slate-100 p-6 flex flex-col justify-between">
                    <div>
                        <div class="inline-flex items-center justify-center h-12 w-12 rounded-2xl bg-blue-100 text-blue-600 mb-4 text-xl">
                            📊
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900 mb-1">Comercial</h2>
                        <p class="text-sm text-slate-500">Propostas, Contratos e Comissões</p>
                    </div>

                    <div class="mt-6">
                        @guest
                            <a href="{{ route('login', ['redirect' => 'comercial']) }}"
                               class="inline-flex w-full items-center justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-medium shadow-md">
                                Acessar Painel
                            </a>
                        @else
                            {{-- por enquanto, manda pro master --}}
                            <a href="{{ route('master.dashboard') }}"
                               class="inline-flex w-full items-center justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-medium shadow-md">
                                Acessar Painel
                            </a>
                        @endguest
                    </div>
                </div>

                {{-- Cliente --}}
                <div class="w-full max-w-xs bg-white rounded-3xl shadow-lg border border-slate-100 p-6 flex flex-col justify-between">
                    <div>
                        <div class="inline-flex items-center justify-center h-12 w-12 rounded-2xl bg-emerald-100 text-emerald-600 mb-4 text-xl">
                            👥
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900 mb-1">Cliente</h2>
                        <p class="text-sm text-slate-500">Agendamentos e Solicitações</p>
                    </div>

                    <div class="mt-6">
                        @guest
                            <a href="{{ route('login', ['redirect' => 'cliente']) }}"
                               class="inline-flex w-full items-center justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-medium shadow-md">
                                Acessar Painel
                            </a>
                        @else
                            {{-- por enquanto, manda pro master --}}
                            <a href="{{ route('master.dashboard') }}"
                               class="inline-flex w-full items-center justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-medium shadow-md">
                                Acessar Painel
                            </a>
                        @endguest
                    </div>
                </div>

                {{-- Operacional --}}
                <div class="w-full max-w-xs bg-white rounded-3xl shadow-lg border border-slate-100 p-6 flex flex-col justify-between">
                    <div>
                        <div class="inline-flex items-center justify-center h-12 w-12 rounded-2xl bg-purple-100 text-purple-600 mb-4 text-xl">
                            📋
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900 mb-1">Operacional</h2>
                        <p class="text-sm text-slate-500">Tarefas e Checklist</p>
                    </div>

                    <div class="mt-6">
                        @guest
                            <a href="{{ route('login', ['redirect' => 'operacional']) }}"
                               class="inline-flex w-full items-center justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-medium shadow-md">
                                Acessar Painel
                            </a>
                        @else
                            <a href="{{ route('operacional.kanban') }}"
                               class="inline-flex w-full items-center justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-medium shadow-md">
                                Acessar Painel
                            </a>
                        @endguest
                    </div>
                </div>

                {{-- Financeiro --}}
                <div class="w-full max-w-xs bg-white rounded-3xl shadow-lg border border-slate-100 p-6 flex flex-col justify-between">
                    <div>
                        <div class="inline-flex items-center justify-center h-12 w-12 rounded-2xl bg-amber-100 text-amber-500 mb-4 text-xl">
                            💰
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900 mb-1">Financeiro</h2>
                        <p class="text-sm text-slate-500">Faturamento e Documentos</p>
                    </div>

                    <div class="mt-6">
                        @guest
                            <a href="{{ route('login', ['redirect' => 'financeiro']) }}"
                               class="inline-flex w-full items-center justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-medium shadow-md">
                                Acessar Painel
                            </a>
                        @else
                            {{-- por enquanto, manda pro master --}}
                            <a href="{{ route('master.dashboard') }}"
                               class="inline-flex w-full items-center justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-medium shadow-md">
                                Acessar Painel
                            </a>
                        @endguest
                    </div>
                </div>

                {{-- Master --}}
                <div class="w-full max-w-xs bg-white rounded-3xl shadow-lg border border-slate-100 p-6 flex flex-col justify-between">
                    <div>
                        <div class="inline-flex items-center justify-center h-12 w-12 rounded-2xl bg-indigo-100 text-indigo-600 mb-4 text-xl">
                            🧭
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900 mb-1">Master</h2>
                        <p class="text-sm text-slate-500">Dashboard Executivo</p>
                    </div>

                    <div class="mt-6">
                        @guest
                            <a href="{{ route('login', ['redirect' => 'master']) }}"
                               class="inline-flex w-full items-center justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-medium shadow-md">
                                Acessar Painel
                            </a>
                        @else
                            <a href="{{ route('master.dashboard') }}"
                               class="inline-flex w-full items-center justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-medium shadow-md">
                                Acessar Painel
                            </a>
                        @endguest
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
