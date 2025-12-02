
    <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Portal do Cliente') - Formed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
<div class="min-h-screen flex flex-col">

    {{-- Top bar --}}
    <header class="bg-[color:var(--color-brand-azul)] text-white shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="font-semibold text-lg tracking-tight">FORMED</span>
                <span class="text-xs sm:text-sm text-blue-100">
                    Portal do Cliente
                </span>
            </div>

            <div class="flex items-center gap-3 text-xs sm:text-sm text-blue-50">
                <span class="hidden sm:inline">
                    {{ auth()->user()->name ?? '' }}
                </span>
                {{-- aqui você pode colocar menu de perfil/logout --}}
            </div>
        </div>
    </header>

    <main class="flex-1">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
