@extends('layouts.landing')

@section('content')
    <div class="min-h-screen bg-gradient-to-b from-slate-950 via-slate-900 to-sky-900 flex items-center">
        <div class="max-w-6xl mx-auto w-full px-4 md:px-6">

            {{-- Cabeçalho / Logo --}}
            <div class="flex flex-col items-center text-center mb-12 md:mb-16">
                <div class="h-20 w-20 md:h-24 md:w-24 rounded-3xl bg-white shadow-xl shadow-sky-500/30 flex items-center justify-center mb-6">
                    {{-- ajuste o caminho do logo se precisar --}}
                    <img src="{{ asset('storage/iconFormed.png') }}" alt="FORMED" class="h-10 md:h-12">

                </div>

                <h1 class="text-3xl md:text-4xl font-semibold tracking-[0.25em] text-white">
                    FORMED
                </h1>
                <p class="mt-3 text-sm md:text-base text-sky-100">
                    Medicina e Segurança do Trabalho
                </p>
                <p class="mt-1 text-xs md:text-sm text-sky-300/80">
                    Selecione o tipo de acesso para visualizar o sistema
                </p>
            </div>

            {{-- Cards dos módulos --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">

                {{-- Comercial (desabilitado) --}}
                <div class="rounded-3xl bg-gradient-to-br from-slate-900/90 to-slate-800/90
            border border-white/5 shadow-xl shadow-slate-950/50 p-6
            flex flex-col justify-between
            transition-transform transition-shadow duration-200 ease-out
            hover:-translate-y-1 hover:shadow-2xl hover:shadow-sky-900/60 hover:border-sky-400/40">
                    <div>
                        <div class="inline-flex items-center justify-center h-10 w-10 rounded-2xl bg-sky-500/15 text-sky-300 mb-4 text-xl">
                            👜
                        </div>
                        <h2 class="text-lg md:text-xl font-semibold text-white mb-1">Comercial</h2>
                        <p class="text-sm text-sky-100/80">
                            Propostas, Contratos e Comissões
                        </p>
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('comercial.dashboard') }}"
                           class="inline-flex items-center gap-1 text-xs md:text-sm font-medium text-sky-300 hover:text-sky-200 transition">
                            <span>Acessar painel</span>
                            <span class="text-base md:text-lg">›</span>
                        </a>
                    </div>
                </div>

                {{-- Cliente (Habilitado) --}}
                <div class="rounded-3xl bg-gradient-to-br from-slate-900/90 to-slate-800/90
            border border-white/5 shadow-xl shadow-slate-950/50 p-6
            flex flex-col justify-between
            transition-transform transition-shadow duration-200 ease-out
            hover:-translate-y-1 hover:shadow-2xl hover:shadow-sky-900/60 hover:border-sky-400/40">
                    <div>
                        <div class="inline-flex items-center justify-center h-10 w-10 rounded-2xl bg-emerald-500/15 text-emerald-300 mb-4 text-xl">
                            👥
                        </div>
                        <h2 class="text-lg md:text-xl font-semibold text-white mb-1">Cliente</h2>
                        <p class="text-sm text-sky-100/80">
                            Agendamentos e Solicitações
                        </p>
                    </div>


                    <div class="mt-6">
                        @guest
                            <a href="{{ route('login', ['redirect' => 'cliente']) }}"
                               class="inline-flex items-center gap-1 text-xs md:text-sm font-medium text-sky-300 hover:text-sky-200 transition">
                                <span>Acessar painel</span>
                                <span class="text-base md:text-lg">›</span>
                            </a>
                        @else
                            <a href="{{ route('cliente.dashboard') }}"
                               class="inline-flex items-center gap-1 text-xs md:text-sm font-medium text-sky-300 hover:text-sky-200 transition">
                                <span>Acessar painel</span>
                                <span class="text-base md:text-lg">›</span>
                            </a>
                        @endguest


                    </div>
                </div>

                {{-- Operacional (habilitado) --}}
                <div class="rounded-3xl bg-gradient-to-br from-slate-900/90 to-slate-800/90
            border border-white/5 shadow-xl shadow-slate-950/50 p-6
            flex flex-col justify-between
            transition-transform transition-shadow duration-200 ease-out
            hover:-translate-y-1 hover:shadow-2xl hover:shadow-sky-900/60 hover:border-sky-400/40">
                    <div>
                        <div class="inline-flex items-center justify-center h-10 w-10 rounded-2xl bg-orange-500/15 text-orange-300 mb-4 text-xl">
                            ⚙️
                        </div>
                        <h2 class="text-lg md:text-xl font-semibold text-white mb-1">Operacional</h2>
                        <p class="text-sm text-sky-100/80">
                            Tarefas e Checklist
                        </p>
                    </div>

                    <div class="mt-6">
                        @guest
                            <a href="{{ route('login', ['redirect' => 'operacional']) }}"
                               class="inline-flex items-center gap-1 text-xs md:text-sm font-medium text-sky-300 hover:text-sky-200 transition">
                                <span>Acessar painel</span>
                                <span class="text-base md:text-lg">›</span>
                            </a>
                        @else
                            <a href="{{ route('operacional.kanban') }}"
                               class="inline-flex items-center gap-1 text-xs md:text-sm font-medium text-sky-300 hover:text-sky-200 transition">
                                <span>Acessar painel</span>
                                <span class="text-base md:text-lg">›</span>
                            </a>
                        @endguest
                    </div>
                </div>

                {{-- Financeiro --}}
                <div class="rounded-3xl bg-gradient-to-br from-slate-900/90 to-slate-800/90
            border border-white/5 shadow-xl shadow-slate-950/50 p-6
            flex flex-col justify-between
            transition-transform transition-shadow duration-200 ease-out
            hover:-translate-y-1 hover:shadow-2xl hover:shadow-sky-900/60 hover:border-sky-400/40">
                    <div>
                        <div class="inline-flex items-center justify-center h-10 w-10 rounded-2xl bg-gradient-to-br from-fuchsia-500/40 to-indigo-400/30 text-pink-100 mb-4 text-xl">
                            💰
                        </div>
                        <h2 class="text-lg md:text-xl font-semibold text-white mb-1">Financeiro</h2>
                        <p class="text-sm text-sky-100/80">
                            Faturamento e Documentos
                        </p>
                    </div>

                    <div class="mt-6">
                        @php
                            $user = auth()->user();
                            $podeFinanceiro = $user && ($user->hasPapel('Master') || $user->hasPapel('Financeiro'));
                        @endphp
                        @if($podeFinanceiro)
                            <a href="{{ route('financeiro.dashboard') }}"
                               class="inline-flex items-center gap-1 text-xs md:text-sm font-medium text-pink-100 hover:text-white transition">
                                <span>Acessar painel</span>
                                <span class="text-base md:text-lg">›</span>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs md:text-sm font-medium text-sky-300/70 opacity-60 cursor-not-allowed">
                                Acesso restrito
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Master (habilitado) --}}
                <div class="rounded-3xl bg-gradient-to-br from-slate-900/90 to-slate-800/90
            border border-white/5 shadow-xl shadow-slate-950/50 p-6
            flex flex-col justify-between
            transition-transform transition-shadow duration-200 ease-out
            hover:-translate-y-1 hover:shadow-2xl hover:shadow-sky-900/60 hover:border-sky-400/40">
                    <div>
                        <div class="inline-flex items-center justify-center h-10 w-10 rounded-2xl bg-red-500/15 text-red-300 mb-4 text-xl">
                            🧭
                        </div>
                        <h2 class="text-lg md:text-xl font-semibold text-white mb-1">Master</h2>
                        <p class="text-sm text-sky-100/80">
                            Dashboard Executivo
                        </p>
                    </div>

                    <div class="mt-6">
                        @guest
                            <a href="{{ route('login', ['redirect' => 'master']) }}"
                               class="inline-flex items-center gap-1 text-xs md:text-sm font-medium text-sky-300 hover:text-sky-200 transition">
                                <span>Acessar painel</span>
                                <span class="text-base md:text-lg">›</span>
                            </a>
                        @else
                            @php
                                $user = auth()->user();
                                $papelNome = optional($user->papel)->nome;   // "Master", "Operacional", etc.
                            @endphp

                            @if ($papelNome === 'Operacional')
                                <span class="inline-flex items-center gap-1 text-xs md:text-sm font-medium text-sky-300/70 opacity-60 cursor-not-allowed">
                                    Acesso restrito ao Master
                                </span>
                            @else
                                <a href="{{ route('master.dashboard') }}"
                                   class="inline-flex items-center gap-1 text-xs md:text-sm font-medium text-sky-300 hover:text-sky-200 transition">
                                    <span>Acessar painel</span>
                                    <span class="text-base md:text-lg">›</span>
                                </a>
                            @endif
                        @endguest
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
