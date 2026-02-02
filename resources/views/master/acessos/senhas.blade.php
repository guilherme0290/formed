{{-- ALERTAS --}}
@if(session('ok') || session('status'))
    <div x-data="{show:true}" x-show="show" class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 flex items-start justify-between">
        <div class="mr-3">
            <div class="font-semibold">Tudo certo</div>
            <div class="text-sm">
                {{ session('ok') ?? (session('status') === 'password-updated' ? 'Senha alterada com sucesso.' : __(session('status'))) }}
            </div>
        </div>
        <button x-on:click="show=false" class="text-emerald-700/70 hover:text-emerald-900">✕</button>
    </div>
@endif

@if(session('err'))
    <div x-data="{show:true}" x-show="show" class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 flex items-start justify-between">
        <div class="mr-3">
            <div class="font-semibold">Ops, algo falhou</div>
            <div class="text-sm">{{ session('err') }}</div>
        </div>
        <button x-on:click="show=false" class="text-rose-700/70 hover:text-rose-900">✕</button>
    </div>
@endif

@if ($errors->any())
    <div x-data="{show:true}" x-show="show" class="mb-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3">
        <div class="font-semibold mb-1">Verifique os campos</div>
        <ul class="text-sm list-disc ml-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <div class="text-right mt-2">
            <button x-on:click="show=false" class="text-amber-800/80 hover:text-amber-900">Fechar</button>
        </div>
    </div>
@endif

{{-- SENHAS (visual moderno) --}}
<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <h3 class="text-lg font-semibold mb-4">Alterar Minha Senha</h3>
        <form method="POST" action="{{ route('password.update') }}" class="space-y-3">
            @csrf
            @method('PUT') {{-- necessário para password.update --}}
            <div class="relative" x-data="{ showPassword: false }">
                <input type="password"
                       name="current_password"
                       required
                       :type="showPassword ? 'text' : 'password'"
                       class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2 pr-11"
                       placeholder="Senha Atual *">
                <button type="button"
                        class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-gray-500 hover:text-gray-700"
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
            <div class="relative" x-data="{ showPassword: false }">
                <input type="password"
                       name="password"
                       required
                       :type="showPassword ? 'text' : 'password'"
                       class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2 pr-11"
                       placeholder="Nova Senha * (mín. 8)">
                <button type="button"
                        class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-gray-500 hover:text-gray-700"
                        :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                        @click="showPassword = !showPassword">
                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 3c-4.5 0-8.1 3.2-9 7 0 0 2.9 7 9 7s9-7 9-7c-.9-3.8-4.5-7-9-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/>
                    </svg>
                    <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4.03 3.97a.75.75 0 0 0-1.06 1.06l1.6 1.6C2.83 8.1 1.66 9.9 1 10c.9 3.8 4.5 7 9 7 1.7 0 3.3-.5 4.6-1.3l1.4 1.4a.75.75 0 1 0 1.06-1.06l-12-12zm5.97 10.53a4 4 0 0 1-4-4c0-.5.1-1 .3-1.5l5.2 5.2c-.5.2-1 .3-1.5.3zm4.9-1.4-1.1-1.1a4 4 0 0 0-5.2-5.2L7.5 5.7c.8-.4 1.6-.7 2.5-.7 4.5 0 8.1 3.2 9 7-.3 1.1-1  2.3-2.1 3.6z"/>
                    </svg>
                </button>
            </div>
            <div class="relative" x-data="{ showPassword: false }">
                <input type="password"
                       name="password_confirmation"
                       required
                       :type="showPassword ? 'text' : 'password'"
                       class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2 pr-11"
                       placeholder="Confirmar Nova Senha *">
                <button type="button"
                        class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-gray-500 hover:text-gray-700"
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
            <button class="w-full px-4 py-2 rounded-xl bg-indigo-600 text-white">Alterar Senha</button>
        </form>
        <ul class="mt-4 text-sm text-gray-500 list-disc ms-5 space-y-1">
            <li>Mínimo de 8 caracteres</li>
            <li>Use letras maiúsculas, minúsculas, números e símbolos</li>
            <li>Evite senhas muito simples</li>
        </ul>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <h3 class="text-lg font-semibold mb-4">Redefinir Senha de Usuário</h3>
        {{-- mantém layout, apenas liga os botões às rotas --}}
        <form id="senhaOutrosForm" method="POST" action="#" class="space-y-3">
            @csrf
            <select id="userSelect" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2" required>
                <option value="">Escolha um usuário</option>
                @foreach($usuarios as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                @endforeach
            </select>

            <div class="relative" x-data="{ showPassword: false }">
                <input type="password"
                       name="password"
                       required
                       :type="showPassword ? 'text' : 'password'"
                       class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2 pr-11"
                       placeholder="Nova Senha *">
                <button type="button"
                        class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-gray-500 hover:text-gray-700"
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
            <div class="relative" x-data="{ showPassword: false }">
                <input type="password"
                       name="password_confirmation"
                       required
                       :type="showPassword ? 'text' : 'password'"
                       class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2 pr-11"
                       placeholder="Confirmar Senha *">
                <button type="button"
                        class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-gray-500 hover:text-gray-700"
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

            <div class="flex items-center justify-between gap-2">
                <button type="button" class="px-4 py-2 rounded-xl bg-indigo-600 text-white" onclick="definirNovaSenha()">
                    Definir Nova Senha
                </button>
                <button type="button" class="px-4 py-2 rounded-xl border" onclick="gerarLinkReset()">
                    Gerar Link
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JS mínimo: aponta o form para as rotas corretas --}}
<script>
    function getUserId(){
        const sel = document.getElementById('userSelect');
        return sel && sel.value ? sel.value : null;
    }
    function gerarLinkReset() {
        const id = getUserId();
        if (!id) { window.uiAlert('Selecione um usuário.'); return; }
        const form = document.getElementById('senhaOutrosForm');
        form.action = "{{ route('master.usuarios.reset', ':id') }}".replace(':id', id);
        form.method = 'POST';
        form.submit();
    }
    function definirNovaSenha() {
        const id = getUserId();
        if (!id) { window.uiAlert('Selecione um usuário.'); return; }
        const form = document.getElementById('senhaOutrosForm');
        form.action = "{{ route('master.usuarios.password', ':id') }}".replace(':id', id);
        form.method = 'POST';
        form.submit();
    }
</script>
