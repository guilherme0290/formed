<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background: #eef4ff; /* azul clarinho */
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">

<div class="w-full max-w-md bg-white rounded-3xl shadow-xl px-10 py-10">

    {{-- Logo --}}
    <div class="flex flex-col items-center mb-8">
        <div class="w-16 h-16 rounded-full bg-indigo-600 flex items-center justify-center mb-3">
            <span class="text-white text-3xl font-bold">F</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">
            Formed
        </h1>
        <p class="text-sm text-slate-600 mt-1">
            Acesse o painel com seu e-mail e senha
        </p>
    </div>

    {{-- Erros --}}
    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc ms-4">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- redirect vindo da URL /login?redirect=xxx --}}
        <input type="hidden" name="redirect" value="{{ $redirect ?? 'master' }}">

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                E-mail
            </label>
            <input type="email" name="email" value="{{ old('email') }}"
                   required autofocus
                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="seuemail@formed.com.br">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                Senha
            </label>
            <input type="password" name="password"
                   required
                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="Digite sua senha">
        </div>

        <button type="submit"
                class="w-full rounded-xl text-white font-semibold py-2.5 mt-4
                       bg-indigo-600 hover:bg-indigo-700 transition">
            Entrar
        </button>
    </form>

</div>

</body>
</html>
