@php
    $acessoUser = auth()->user();
    $routePrefix = $routePrefix ?? 'clientes';
    $permPrefixAcesso = str_starts_with($routePrefix, 'comercial.') ? 'comercial.clientes' : 'master.clientes';
    $permissionMapAcesso = $acessoUser?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
    $isMasterAcesso = $acessoUser?->hasPapel('Master');
    $canManageAcessoTab = $isMasterAcesso
        || isset($permissionMapAcesso[$permPrefixAcesso.'.update'])
        || isset($permissionMapAcesso[$permPrefixAcesso.'.create']);

    $senhaPadraoTab = old('password', $senhaSugerida ?? \Illuminate\Support\Str::password(10));
    $emailSugeridoTab = old('email', $cliente->email ?? '');
    $documentoSugeridoTab = old('documento', $cliente->cnpj ?? '');
    $temEmailTab = trim((string) ($cliente->email ?? '')) !== '';
    $temDocumentoTab = trim((string) ($cliente->cnpj ?? '')) !== '';
    $loginTipoPadraoTab = $temDocumentoTab ? 'documento' : 'email';
    $loginTipoAtualTab = old('login_tipo', $loginTipoPadraoTab);
    if (!$temEmailTab && $loginTipoAtualTab === 'email') {
        $loginTipoAtualTab = 'documento';
    }
    if (!$temDocumentoTab && $loginTipoAtualTab === 'documento') {
        $loginTipoAtualTab = 'email';
    }
    $mostrarEscolhaLoginTab = $temEmailTab && $temDocumentoTab;
@endphp

<div data-tab-panel="acesso" data-tab-panel-root="cliente" class="hidden">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b bg-indigo-600 text-white">
                <h1 class="text-lg font-semibold">Acesso do Cliente</h1>
                <p class="text-sm text-indigo-100 mt-1">Crie o acesso aqui e, depois, redefina a senha na aba Senhas.</p>
            </div>

            <div class="p-6 space-y-5">
                @if (session('acesso_cliente'))
                    @php
                        $acessoCriado = session('acesso_cliente');
                    @endphp
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                        <div class="font-semibold">Acesso criado com sucesso</div>
                        <div>Login: <span class="font-mono">{{ $acessoCriado['email'] ?? '-' }}</span></div>
                        <div>Senha temporária: <span class="font-mono">{{ $acessoCriado['senha'] ?? '-' }}</span></div>
                    </div>
                @endif

                @if($userExistente)
                    <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                        <div class="font-semibold">Cliente já possui acesso</div>
                        <div class="mt-1">Usuário: {{ $userExistente->name }}</div>
                        <div>Login: {{ $userExistente->email ?: ($userExistente->documento ?? '-') }}</div>
                        <div>Status: {{ $userExistente->ativo ? 'Ativo' : 'Inativo' }}</div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('master.acessos', ['tab' => 'senhas', 'user_id' => $userExistente->id]) }}"
                           class="inline-flex items-center rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold">
                            Redefinir senha
                        </a>

                    </div>
                @else
                    @if(session('error') || session('erro'))
                        <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                            {{ session('error') ?? session('erro') }}
                        </div>
                    @endif

                    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">Criar acesso para {{ $cliente->razao_social }}</h2>
                                <p class="text-sm text-slate-500">O usuário deverá trocar a senha no primeiro login.</p>
                            </div>
                        </div>

                    <form method="POST" action="{{ route($routePrefix.'.acesso', $cliente) }}" class="space-y-4" id="clienteAcessoTabForm">
                        @csrf

                        @if($mostrarEscolhaLoginTab)
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700">Tipo de login</label>
                                <div class="grid grid-cols-1 gap-2">
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="login_tipo" value="documento" class="h-4 w-4"
                                               {{ $loginTipoAtualTab === 'documento' ? 'checked' : '' }}>
                                        <span>CNPJ</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="login_tipo" value="email" class="h-4 w-4"
                                               {{ $loginTipoAtualTab === 'email' ? 'checked' : '' }}>
                                        <span>E-mail</span>
                                    </label>
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="login_tipo" value="{{ $temDocumentoTab ? 'documento' : 'email' }}">
                        @endif

                        <div id="acessoTabDocumentoGroup" class="space-y-1">
                                <label class="text-sm font-semibold text-slate-700">CNPJ (login)</label>
                                <input type="text"
                                       id="acessoTabDocumentoInput"
                                       name="documento"
                                       value="{{ $documentoSugeridoTab }}"
                                       class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                       placeholder="00.000.000/0000-00">
                        </div>

                        <div id="acessoTabEmailGroup" class="space-y-1">
                                <label class="text-sm font-semibold text-slate-700">E-mail (login)</label>
                                <input type="email"
                                       id="acessoTabEmailInput"
                                       name="email"
                                       value="{{ $emailSugeridoTab }}"
                                       class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                       placeholder="email@cliente.com">
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <label class="text-sm font-semibold text-slate-700">Senha temporária</label>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="acessoTabGerarSenha" class="text-xs text-indigo-600 hover:underline">Gerar outra</button>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <input type="text"
                                       id="acessoTabSenhaInput"
                                       name="password"
                                       value="{{ $senhaPadraoTab }}"
                                       required
                                       class="w-full rounded-xl border border-slate-200 px-3 py-2">
                                <button type="button" id="acessoTabCopiarSenha" class="px-3 py-2 rounded-xl border border-slate-200 text-sm hover:bg-slate-50">Copiar</button>
                            </div>
                            <p class="text-xs text-slate-500">O usuário deverá definir nova senha no primeiro login.</p>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-2">
                            <button type="submit"
                                    @if(!$canManageAcessoTab) disabled title="Usuário sem permissão" @endif
                                    class="px-4 py-2 rounded-xl text-sm font-semibold w-full {{ $canManageAcessoTab ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                                Criar acesso
                            </button>

                        </div>
                    </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('clienteAcessoTabForm');
        if (!form) return;

        const senhaInput = document.getElementById('acessoTabSenhaInput');
        const btnGerar = document.getElementById('acessoTabGerarSenha');
        const btnCopiar = document.getElementById('acessoTabCopiarSenha');
        const documentoInput = document.getElementById('acessoTabDocumentoInput');
        const emailInput = document.getElementById('acessoTabEmailInput');
        const radios = form.querySelectorAll('input[name="login_tipo"]');
        const documentoGroup = document.getElementById('acessoTabDocumentoGroup');
        const emailGroup = document.getElementById('acessoTabEmailGroup');

        function getLoginTipo() {
            const checked = form.querySelector('input[name="login_tipo"]:checked');
            if (checked && checked.value) return checked.value;
            const hidden = form.querySelector('input[name="login_tipo"]');
            return hidden?.value || 'documento';
        }

        function toggleLoginInputs() {
            const tipo = getLoginTipo();
            const usarEmail = tipo === 'email';

            if (documentoInput) {
                documentoInput.disabled = usarEmail;
                documentoInput.required = !usarEmail;
            }
            if (emailInput) {
                emailInput.disabled = !usarEmail;
                emailInput.required = usarEmail;
            }
            documentoGroup?.classList.toggle('hidden', usarEmail);
            emailGroup?.classList.toggle('hidden', !usarEmail);
        }

        function gerarSenha() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@$!';
            let s = '';
            for (let i = 0; i < 10; i++) {
                s += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            if (senhaInput) senhaInput.value = s;
        }

        function copiarSenha() {
            if (!senhaInput) return;
            navigator.clipboard?.writeText(senhaInput.value || '').then(() => {
                if (!btnCopiar) return;
                const oldText = btnCopiar.textContent;
                btnCopiar.textContent = 'Copiado!';
                setTimeout(() => btnCopiar.textContent = oldText, 1200);
            }).catch(() => {});
        }

        function formatCnpj(value) {
            const digits = (value || '').replace(/\D+/g, '').slice(0, 14);
            if (digits.length <= 2) return digits;
            if (digits.length <= 5) return `${digits.slice(0, 2)}.${digits.slice(2)}`;
            if (digits.length <= 8) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
            if (digits.length <= 12) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
            return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
        }

        documentoInput?.addEventListener('input', () => {
            documentoInput.value = formatCnpj(documentoInput.value);
        });
        btnGerar?.addEventListener('click', gerarSenha);
        btnCopiar?.addEventListener('click', copiarSenha);
        radios.forEach((radio) => radio.addEventListener('change', toggleLoginInputs));

        toggleLoginInputs();
    })();
</script>
