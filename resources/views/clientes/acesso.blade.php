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
                <a href="{{ route($routePrefix.'.index') }}" class="text-sm text-slate-600 hover:text-slate-800">← Voltar</a>
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
{{--                    <a id="btnWhatsapp" href="#" target="_blank" class="px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-sm font-semibold text-center hover:bg-emerald-100 w-full">--}}
{{--                        Enviar no WhatsApp--}}
{{--                    </a>--}}
{{--                    <button type="button" id="btnEmail" class="px-4 py-2 rounded-xl bg-slate-50 text-slate-700 border border-slate-200 text-sm font-semibold text-center hover:bg-slate-100 w-full">--}}
{{--                        Enviar por e-mail--}}
{{--                    </button>--}}
                </div>
            </form>
        </div>
    </div>

    <div id="modalEmailAcesso" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Enviar acesso por e-mail</h3>
                    <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                            onclick="closeEmailModal()">✕</button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">E-mail</label>
                        <input id="emailModalTo" type="email"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               placeholder="email@cliente.com">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Assunto</label>
                        <input id="emailModalSubject"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                               value="Acesso ao portal do cliente">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Mensagem</label>
                        <textarea id="emailModalBody" rows="5"
                                  class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                                  placeholder="Mensagem..."></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button"
                                class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                onclick="closeEmailModal()">
                            Cancelar
                        </button>
                        <a id="emailModalLink"
                           class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold"
                           href="#">
                            Abrir e-mail
                        </a>
                    </div>
                </div>
            </div>
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
            const sistemaUrl = @json(route('login'));
            const modalEmail = document.getElementById('modalEmailAcesso');
            const emailModalTo = document.getElementById('emailModalTo');
            const emailModalSubject = document.getElementById('emailModalSubject');
            const emailModalBody = document.getElementById('emailModalBody');
            const emailModalLink = document.getElementById('emailModalLink');

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

            function montarMensagem(email, senha) {
                return `Olá! Aqui estão seus acessos ao portal:\nLogin: ${email}\nSenha temporária: ${senha}\nAcesse: ${sistemaUrl}\nAltere a senha no primeiro login.`;
            }

            function atualizarLinks() {
                const email = emailInput.value || '';
                const senha = senhaInput.value || '';
                const texto = encodeURIComponent(montarMensagem(email, senha));

                if (telefone) {
                    btnWhatsapp.href = `https://wa.me/${telefone}?text=${texto}`;
                } else {
                    btnWhatsapp.href = '#';
                }
            }

            function abrirEmailModal() {
                if (!modalEmail) return;
                const email = emailInput.value || '';
                const senha = senhaInput.value || '';
                const mensagem = montarMensagem(email, senha);

                if (emailModalTo) emailModalTo.value = email;
                if (emailModalSubject && !emailModalSubject.value) {
                    emailModalSubject.value = 'Acesso ao portal do cliente';
                }
                if (emailModalBody) emailModalBody.value = mensagem;

                atualizarEmailModalLink();
                modalEmail.classList.remove('hidden');
            }

            function atualizarEmailModalLink() {
                if (!emailModalLink) return;
                const to = encodeURIComponent(emailModalTo?.value || '');
                const subject = encodeURIComponent(emailModalSubject?.value || 'Acesso ao portal do cliente');
                const body = encodeURIComponent(emailModalBody?.value || '');
                emailModalLink.href = `mailto:${to}?subject=${subject}&body=${body}`;
            }

            window.closeEmailModal = () => modalEmail?.classList.add('hidden');

            btnGerar?.addEventListener('click', gerarSenha);
            btnCopiar?.addEventListener('click', copiarSenha);
            emailInput?.addEventListener('input', atualizarLinks);
            senhaInput?.addEventListener('input', atualizarLinks);
            btnEmail?.addEventListener('click', abrirEmailModal);
            emailModalTo?.addEventListener('input', atualizarEmailModalLink);
            emailModalSubject?.addEventListener('input', atualizarEmailModalLink);
            emailModalBody?.addEventListener('input', atualizarEmailModalLink);

            atualizarLinks();
        })();
    </script>
@endsection
BLADE
