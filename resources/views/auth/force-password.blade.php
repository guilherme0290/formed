<x-guest-layout>
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow border border-slate-200 p-6 space-y-4">
        <div class="text-center space-y-1">
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
                <input type="password" name="password" required autocomplete="new-password" class="w-full rounded-xl border border-slate-200 px-3 py-2">
            </div>
            <div class="space-y-1">
                <label class="text-sm font-semibold text-slate-700">Confirmar senha</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-xl border border-slate-200 px-3 py-2">
            </div>
            <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Salvar e entrar</button>
        </form>
    </div>
</x-guest-layout>
