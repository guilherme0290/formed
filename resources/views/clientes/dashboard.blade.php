@extends('layouts.cliente')

@section('title', 'Serviços Disponíveis')

@section('content')
    <div class="mb-6">
        <h1 class="text-lg md:text-xl font-semibold text-slate-800">
            Serviços Disponíveis
        </h1>
        <p class="text-xs md:text-sm text-slate-500">
            Selecione um serviço para solicitar atendimentos e laudos para o seu quadro de colaboradores.
        </p>
    </div>

    {{-- Grid de serviços --}}
    <div class="grid gap-4 md:gap-5 md:grid-cols-2 lg:grid-cols-4">

        {{-- Meus Funcionários --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
            <div>
                <div class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-emerald-500/10 text-emerald-600 mb-3 text-lg">
                    👥
                </div>
                <h2 class="text-sm font-semibold text-slate-800">
                    Meus Funcionários
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Gerencie seus colaboradores e documentação.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    Gestão
                </span>

                <a href="{{ route('cliente.funcionarios.index') }}"
                   class="text-[color:var(--color-brand-azul)] font-medium hover:underline">
                    Acessar
                </a>
            </div>
        </div>

        {{-- Agendar ASO (placeholder de rota) --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
            <div>
                <div class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-sky-500/10 text-sky-600 mb-3 text-lg">
                    📅
                </div>
                <h2 class="text-sm font-semibold text-slate-800">
                    Agendar ASO
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Agende exames ocupacionais para seus colaboradores.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    R$ 250,00
                </span>

                <button type="button"
                        class="text-[color:var(--color-brand-azul)] font-medium opacity-60 cursor-not-allowed"
                        title="Em desenvolvimento">
                    Solicitar
                </button>
            </div>
        </div>

        {{-- Solicitar PGR --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
            <div>
                <div class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-teal-500/10 text-teal-600 mb-3 text-lg">
                    📋
                </div>
                <h2 class="text-sm font-semibold text-slate-800">
                    Solicitar PGR
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Programa de Gerenciamento de Riscos.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    R$ 600,00
                </span>

                <button type="button"
                        class="text-[color:var(--color-brand-azul)] font-medium opacity-60 cursor-not-allowed"
                        title="Em desenvolvimento">
                    Solicitar
                </button>
            </div>
        </div>

        {{-- Solicitar PCMSO --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
            <div>
                <div class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-violet-500/10 text-violet-600 mb-3 text-lg">
                    📑
                </div>
                <h2 class="text-sm font-semibold text-slate-800">
                    Solicitar PCMSO
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Programa de Controle Médico de Saúde Ocupacional.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    R$ 350,00
                </span>

                <button type="button"
                        class="text-[color:var(--color-brand-azul)] font-medium opacity-60 cursor-not-allowed"
                        title="Em desenvolvimento">
                    Solicitar
                </button>
            </div>
        </div>

        {{-- Solicitar LTCAT --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
            <div>
                <div class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-orange-500/10 text-orange-600 mb-3 text-lg">
                    📄
                </div>
                <h2 class="text-sm font-semibold text-slate-800">
                    Solicitar LTCAT
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Laudo Técnico das Condições Ambientais do Trabalho.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    A partir de R$ 2.000,00
                </span>

                <button type="button"
                        class="text-[color:var(--color-brand-azul)] font-medium opacity-60 cursor-not-allowed"
                        title="Em desenvolvimento">
                    Solicitar
                </button>
            </div>
        </div>

        {{-- Solicitar APR --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
            <div>
                <div class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-red-500/10 text-red-600 mb-3 text-lg">
                    ⚠️
                </div>
                <h2 class="text-sm font-semibold text-slate-800">
                    Solicitar APR
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Análise Preliminar de Riscos.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-100">
                    Sob consulta
                </span>

                <button type="button"
                        class="text-[color:var(--color-brand-azul)] font-medium opacity-60 cursor-not-allowed"
                        title="Em desenvolvimento">
                    Solicitar
                </button>
            </div>
        </div>

        {{-- Solicitar Treinamentos --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
            <div>
                <div class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-indigo-500/10 text-indigo-600 mb-3 text-lg">
                    🎓
                </div>
                <h2 class="text-sm font-semibold text-slate-800">
                    Solicitar Treinamentos
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Treinamentos de Normas Regulamentadoras.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    A partir de R$ 40,00
                </span>

                <button type="button"
                        class="text-[color:var(--color-brand-azul)] font-medium opacity-60 cursor-not-allowed"
                        title="Em desenvolvimento">
                    Solicitar
                </button>
            </div>
        </div>

        {{-- Meus Arquivos --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
            <div>
                <div class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-teal-500/10 text-teal-600 mb-3 text-lg">
                    📁
                </div>
                <h2 class="text-sm font-semibold text-slate-800">
                    Meus Arquivos
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Acesse todos os documentos e certificados liberados.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    Downloads
                </span>

                <button type="button"
                        class="text-[color:var(--color-brand-azul)] font-medium opacity-60 cursor-not-allowed"
                        title="Em desenvolvimento">
                    Acessar
                </button>
            </div>
        </div>

    </div>
@endsection
