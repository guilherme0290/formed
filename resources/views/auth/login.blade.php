<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="relative w-full max-w-5xl">
        {{-- “brilhos” de fundo --}}
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute -top-24 -left-10 h-56 w-56 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-10 h-64 w-64 rounded-full bg-sky-400/20 blur-3xl"></div>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1.15fr,1fr] items-stretch">
            {{-- Lado esquerdo: branding / texto --}}
            <div class="hidden lg:flex flex-col justify-between rounded-3xl bg-gradient-to-b from-slate-900/80 via-slate-900/90 to-slate-950/90 border border-slate-800/80 px-9 py-9 shadow-2xl shadow-slate-950/60">
                <div>
                    {{-- Logo Formed --}}
                    <div class="mb-8">
                        <div
                            class="inline-flex items-center gap-3
               rounded-3xl bg-white/95 px-4 py-2
               shadow-xl shadow-slate-900/50
               border border-slate-200/80"
                        >
                            <div class="flex items-center justify-center h-14 w-14 rounded-3xl bg-white">
                                <img
                                    src="{{ asset('storage/logo.svg') }}"
                                    alt="Formed"
                                    class="h-12 w-auto max-h-full"
                                >
                            </div>

                            <div class="flex flex-col">
            <span class="text-xs font-semibold tracking-wide text-slate-900">
                Formed
            </span>
                                <span class="text-[11px] uppercase tracking-[0.18em] text-slate-500">
                Saúde &amp; Segurança Ocupacional
            </span>
                            </div>
                        </div>
                    </div>


                    <div class="inline-flex items-center gap-2 rounded-full bg-slate-800/80 border border-slate-700/80 px-3 py-1 text-[11px] text-slate-300 mb-4">
                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Plataforma de Saúde e Segurança Ocupacional
                    </div>

                    <h1 class="text-3xl font-semibold tracking-tight text-slate-50 mb-3">
                        Bem-vindo a <span class="text-indigo-400">Formed</span>
                    </h1>
                    <p class="text-sm text-slate-300 max-w-md">
                        Centralize a gestão de ASO, PGR, PCMSO, treinamentos e documentos em um único painel,
                        com organização visual por tarefas e status.
                    </p>
                </div>

                <div class="space-y-3 mt-6">
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <div class="h-8 w-8 rounded-2xl bg-slate-800/90 flex items-center justify-center">
                            <span>📋</span>
                        </div>
                        <div>
                            <p class="font-medium text-slate-100">Painel operacional em tempo real</p>
                            <p class="text-[11px] text-slate-400">
                                Visualize tarefas por status, responsável e tipo de serviço.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <div class="h-8 w-8 rounded-2xl bg-slate-800/90 flex items-center justify-center">
                            <span>🧩</span>
                        </div>
                        <div>
                            <p class="font-medium text-slate-100">Fluxos personalizados</p>
                            <p class="text-[11px] text-slate-400">
                                ASO, PGR, PCMSO e outros serviços com checklists guiados.
                            </p>
                        </div>
                    </div>
                </div>

                <p class="mt-6 text-[11px] text-slate-500">
                    © {{ date('Y') }} Formed. Todos os direitos reservados.
                </p>
            </div>

            {{-- Lado direito: card de login --}}
            <div class="bg-slate-950/80 border border-slate-800/80 rounded-3xl shadow-2xl shadow-slate-950/70 px-7 py-8 sm:px-9 sm:py-10 backdrop-blur">
                {{-- Logo / título (mobile + desktop) --}}
                @php
                    $redirect = old('redirect', request('redirect', 'master'));
                @endphp

                <div class="flex items-center gap-3 mb-6">
                    {{-- Logo compacta pra mobile / card --}}
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-700 flex items-center justify-center shadow-lg shadow-slate-900/60 overflow-hidden">
                        <img
                            src="{{ asset('favicon.png') }}"
                            alt="Formed"
                            class="w-10 h-auto"
                        >
                    </div>

                    <div>
                        <h2 class="text-xl font-semibold text-slate-50 tracking-tight">
                            Entrar
                        </h2>
                        <p class="text-[13px] text-slate-400">
                            Adicione sua credencial de acesso.
                        </p>
                    </div>
                </div>

                {{-- Erros --}}
                @if ($errors->any())
                    <div class="mb-4 rounded-2xl bg-rose-500/10 border border-rose-500/40 px-4 py-3 text-xs text-rose-100">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5">⚠️</span>
                            <div>
                                <p class="font-medium mb-1">Não foi possível entrar:</p>
                                <ul class="list-disc ms-4 space-y-0.5">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- redirect vindo da query string /login?redirect=xxx --}}
                    <input type="hidden" name="redirect" id="redirectInput" value="{{ $redirect }}">

                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-300">E-mail ou CNPJ</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-500 text-sm">
                                @
                            </span>
                            <input
                                type="text"
                                name="login"
                                id="loginInput"
                                value="{{ old('login') }}"
                                required autofocus
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/60 px-8 py-2.5 text-sm text-slate-100
                                       placeholder:text-slate-500
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Digite seu e-mail ou CNPJ">
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Use e-mail ou CNPJ.</p>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-medium text-slate-300">
                                Senha
                            </label>
                            <a href="#"
                               class="text-[11px] text-indigo-300 hover:text-indigo-200 hover:underline">
                                Esqueceu a senha?
                            </a>
                        </div>
                        <div class="relative" x-data="{ showPassword: false }">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-500 text-sm">
                                •••
                            </span>
                            <input
                                type="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                name="password"
                                required
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/60 px-8 py-2.5 pr-11 text-sm text-slate-100
                                       placeholder:text-slate-500
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Digite sua senha">
                            <button type="button"
                                    class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-slate-400 hover:text-slate-200"
                                    :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                                    @click="showPassword = !showPassword">
                                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 3c-4.5 0-8.1 3.2-9 7 0 0 2.9 7 9 7s9-7 9-7c-.9-3.8-4.5-7-9-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/>
                                </svg>
                                <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M4.03 3.97a.75.75 0 0 0-1.06 1.06l1.6 1.6C2.83 8.1 1.66 9.9 1 10c.9 3.8 4.5 7 9 7 1.7 0 3.3-.5 4.6-1.3l1.4 1.4a.75.75 0 1 0 1.06-1.06l-12-12zm5.97 10.53a4 4 0 0 1-4-4c0-.5.1-1 .3-1.5l5.2 5.2c-.5.2-1 .3-1.5.3zm4.9-1.4-1.1-1.1a4 4 0 0 0-5.2-5.2L7.5 5.7c.8-.4 1.6-.7 2.5-.7 4.5 0 8.1 3.2 9 7-.3 1.1-1 2.3-2.1 3.6z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="inline-flex items-center gap-2 text-[12px] text-slate-400">
                            <input type="checkbox"
                                   class="rounded border-slate-600 bg-slate-900 text-indigo-500
                                          focus:ring-indigo-500 focus:ring-offset-slate-950">
                            <span>Manter-me conectado</span>
                        </label>
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl text-sm font-semibold py-2.5 mt-3
                                   bg-gradient-to-r from-indigo-500 via-indigo-400 to-sky-400
                                   text-slate-950 shadow-lg shadow-indigo-500/40
                                   hover:from-indigo-400 hover:via-indigo-300 hover:to-sky-300
                                   transition-transform transform hover:-translate-y-0.5">
                        Entrar
                    </button>
                </form>

                <div class="mt-6 border-t border-slate-800 pt-4">
                    <p class="text-[11px] text-slate-500">
                        Acesso restrito a usuários autorizados. Em caso de dúvida, contate o administrador do sistema.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const input = document.getElementById('loginInput');
        if (!input) return;

        function formatCnpj(value) {
            const digits = (value || '').replace(/\D+/g, '').slice(0, 14);
            if (digits.length <= 2) return digits;
            if (digits.length <= 5) return `${digits.slice(0, 2)}.${digits.slice(2)}`;
            if (digits.length <= 8) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
            if (digits.length <= 12) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
            return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
        }

        function countDigits(str, endIndex) {
            const slice = str.slice(0, Math.max(0, endIndex));
            return (slice.match(/\d/g) || []).length;
        }

        function positionFromDigits(formatted, digitCount) {
            if (digitCount <= 0) return 0;
            let count = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (/\d/.test(formatted[i])) {
                    count++;
                    if (count === digitCount) return i + 1;
                }
            }
            return formatted.length;
        }

        input.addEventListener('input', () => {
            const value = input.value || '';
            if (value.includes('@') || /[a-zA-Z]/.test(value)) {
                return;
            }
            const cursor = input.selectionStart ?? value.length;
            const digitsBefore = countDigits(value, cursor);
            const formatted = formatCnpj(value);
            input.value = formatted;
            const newPos = positionFromDigits(formatted, digitsBefore);
            input.setSelectionRange(newPos, newPos);
        });
    })();
</script>

</body>
</html>
