<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perfil não reconhecido - Formed</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <div class="max-w-lg w-full bg-white rounded-2xl shadow-lg border border-slate-200 p-6 space-y-4 text-center">
        <div class="mx-auto h-12 w-12 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-2xl">
            !
        </div>
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Perfil não reconhecido</h1>
            <p class="text-sm text-slate-600 mt-2">
                Não foi possível identificar um perfil válido para seu usuário.
                Perfil informado: <span class="font-semibold text-orange-600">{{ $papel ?? 'desconhecido' }}</span>.
            </p>
            <p class="text-sm text-slate-600 mt-1">Acesse novamente com um perfil Master, Comercial, Operacional ou Cliente.</p>
        </div>
        <div class="flex flex-col sm:flex-row sm:justify-center gap-3">
            <a href="{{ route('login') }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600 shadow-sm">
                Voltar ao login
            </a>
            <a href="{{ url('/') }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Ir para a página inicial
            </a>
        </div>
    </div>
</body>
</html>
