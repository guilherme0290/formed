<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Painel Operacional') - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900">
<div class="min-h-screen flex">

    {{-- Sidebar esquerda --}}
    <aside class="hidden md:flex flex-col w-56 bg-slate-950 text-slate-100">
        <div class="h-16 flex items-center px-6 text-lg font-semibold">
            Operacional
        </div>

        <nav class="flex-1 px-3 mt-4 space-y-1">
            <a href="{{ route('operacional.kanban') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-800 text-slate-50 text-sm font-medium">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-700">
                    🗂️
                </span>
                <span>Painel Operacional</span>
            </a>
        </nav>

        <div class="px-4 py-4 border-t border-slate-800 space-y-2 text-sm">
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-slate-300 hover:text-white">
                <span>⏪</span> <span>Voltar ao Início</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex items-center gap-2 text-rose-400 hover:text-rose-300">
                    <span>🚪</span> Sair
                </button>
            </form>
        </div>
    </aside>

    {{-- Área principal à direita --}}
    <div class="flex-1 flex flex-col bg-slate-50">

        <header class="bg-blue-900 text-white shadow-sm">
            <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between">
                <div class="flex items-baseline gap-3">
                    <span class="font-semibold text-lg tracking-tight">FORMED</span>
                    <span class="text-xs md:text-sm text-blue-100">
                    Medicina e Segurança do Trabalho
                </span>
                </div>

                <div class="flex items-center gap-3 text-xs md:text-sm text-blue-50">
                <span class="hidden md:inline">
                    {{ auth()->user()->name ?? '' }}
                </span>
                </div>
            </div>
        </header>

        <main class="flex-1">
            @yield('content')
        </main>
    </div>
</div>

{{-- SortableJS para drag & drop do Kanban --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

{{-- Scripts específicos das views --}}
@stack('scripts')
</body>
</html>
