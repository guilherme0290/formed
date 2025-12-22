@php
    $user = auth()->user();
    $layout = 'layouts.app';

    if ($user && optional($user->papel)->nome === 'Operacional') {
        $layout = 'layouts.operacional';
    } else if ($user && optional($user->papel)->nome === 'Master') {
        $layout = 'layouts.master';
    } else if ($user && optional($user->papel)->nome === 'Comercial') {
        $layout = 'layouts.comercial';
    }
@endphp

@extends($layout)
@section('title', 'Criar acesso do cliente')

@php
    $telefoneLimpo = preg_replace('/\\D+/', '', $cliente->telefone ?? '');
    $senhaPadrao = $senhaSugerida ?? \Illuminate\Support\Str::password(10);
    $emailSugerido = $cliente->email ?? '';
@endphp

@section('content')
    @php($routePrefix = $routePrefix ?? 'clientes')
    <div class="max-w-2xl mx-auto px-4 py-6 space-y-6">
        <div class="bg-white rounded-2xl shadow border p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Criar acesso para {{ $cliente->razao_social }}</h1>
                    <p class="text-sm text-slate-500">O usuário deverá trocar a senha no primeiro login.</p>
                </div>
                <a href="{{ route($routePrefix.'.show', $cliente) }}" class="text-sm text-slate-600 hover:text-slate-800">← Voltar</a>
            </div>

            @if($userExistente)
                <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                    Já existe um usuário para este cliente ({{ $userExistente->email }}). Você pode criar outro apenas se usar um e-mail diferente.
                </div>
            @endif

            <form id="acessoForm" method="POST" action="{{ route($routePrefix.'.acesso', $cliente) }}" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label class="text-sm font-semibold text-slate-700">E-mail (login)</label>
                    <input type="email" name="email" value="{{ old('email', $emailSugerido) }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-semibold text-slate-700">Senha temporária</label>
                        <button type="button" id="btnGerarSenha" class="text-xs text-indigo-600 hover:underline">Gerar outra</button>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" name="password" id="senhaInput" value="{{ old('password', $senhaPadrao) }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        <button type="button" id="btnCopiarSenha" class="px-3 py-2 rounded-xl border border-slate-200 text-sm hover:bg-slate-50">Copiar</button>
                    </div>
                    <p class="text-xs text-slate-500">O usuário deverá definir nova senha no primeiro login.</p>
                </div>

                <div class="grid sm:grid-cols-3 gap-2">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 w-full">
                        Criar acesso
                    </button>
                    <a id="btnWhatsapp" href="#" target="_blank" class="px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-sm font-semibold text-center hover:bg-emerald-100 w-full">
                        Enviar no WhatsApp
                    </a>
                    <a id="btnEmail" href="#" class="px-4 py-2 rounded-xl bg-slate-50 text-slate-700 border border-slate-200 text-sm font-semibold text-center hover:bg-slate-100 w-full">
                        Enviar por e-mail
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const senhaInput = document.getElementById('senhaInput');
            const btnGerar = document.getElementById('btnGerarSenha');
            const btnCopiar = document.getElementById('btnCopiarSenha');
            const btnWhatsapp = document.getElementById('btnWhatsapp');
            const btnEmail = document.getElementById('btnEmail');
            const emailInput = document.querySelector('input[name=\"email\"]');
            const telefone = '{{ $telefoneLimpo }}';

            function gerarSenha() {
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@$!';
                let s = '';
                for (let i = 0; i < 10; i++) {
                    s += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                senhaInput.value = s;
                atualizarLinks();
            }

            function copiarSenha() {
                navigator.clipboard.writeText(senhaInput.value || '').then(() => {
                    btnCopiar.textContent = 'Copiado!';
                    setTimeout(() => btnCopiar.textContent = 'Copiar', 1500);
                });
            }

            function atualizarLinks() {
                const email = encodeURIComponent(emailInput.value || '');
                const senha = encodeURIComponent(senhaInput.value || '');
                const texto = encodeURIComponent(`Olá! Aqui estão seus acessos ao portal:\nLogin: ${emailInput.value}\nSenha temporária: ${senhaInput.value}\nAcesse e altere a senha no primeiro login.`);

                if (telefone) {
                    btnWhatsapp.href = `https://wa.me/${telefone}?text=${texto}`;
                } else {
                    btnWhatsapp.href = '#';
                }
                btnEmail.href = `mailto:${emailInput.value || ''}?subject=Acesso%20ao%20portal&body=${texto}`;
            }

            btnGerar?.addEventListener('click', gerarSenha);
            btnCopiar?.addEventListener('click', copiarSenha);
            emailInput?.addEventListener('input', atualizarLinks);
            senhaInput?.addEventListener('input', atualizarLinks);

            atualizarLinks();
        })();
    </script>
@endsection
BLADE
