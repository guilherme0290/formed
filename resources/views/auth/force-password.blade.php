<x-guest-layout>
    <div class="relative w-full flex items-center justify-center min-h-screen px-4">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-28 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-indigo-200/40 blur-3xl"></div>
            <div class="absolute -bottom-24 right-10 h-64 w-64 rounded-full bg-sky-200/40 blur-3xl"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-[120px] sm:text-[160px] font-semibold tracking-[0.3em] text-slate-900/5">FORMED</span>
            </div>
        </div>

        <div class="relative w-full max-w-md">
            <div class="bg-white/90 backdrop-blur rounded-2xl shadow-xl border border-slate-200 p-6 space-y-4">
                <div class="text-center space-y-1">
                    <p class="text-xs font-semibold tracking-[0.25em] text-slate-400">FORMED</p>
                    <h1 class="text-xl font-semibold text-slate-900">Defina uma nova senha</h1>
                    <p class="text-sm text-slate-600">Senha temporária detectada. Atualize para continuar.</p>
                </div>

                @if ($errors->any())
                    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.force.update') }}" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label class="text-sm font-semibold text-slate-700">Nova senha</label>
                        <div class="relative" x-data="{ showPassword: false }">
                            <input type="password"
                                   name="password"
                                   required
                                   autocomplete="new-password"
                                   :type="showPassword ? 'text' : 'password'"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 pr-11">
                            <button type="button"
                                    class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-slate-500 hover:text-slate-700"
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
                    <div class="space-y-1">
                        <label class="text-sm font-semibold text-slate-700">Confirmar senha</label>
                        <div class="relative" x-data="{ showPassword: false }">
                            <input type="password"
                                   name="password_confirmation"
                                   required
                                   autocomplete="new-password"
                                   :type="showPassword ? 'text' : 'password'"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 pr-11">
                            <button type="button"
                                    class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-slate-500 hover:text-slate-700"
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
                    <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Salvar e entrar</button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
