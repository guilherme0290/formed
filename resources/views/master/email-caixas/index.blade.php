@extends('layouts.master')
@section('title', 'Configuracao de Caixas de Email')

@section('content')
    @php
        $caixaEmEdicaoId = (int) old('caixa_id');
        $caixaTesteId = (int) old('caixa_teste_id');
        $tab = request('tab', 'email');
    @endphp

    <div class="max-w-6xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div class="text-[11px] text-slate-500">
            Configurações &gt; {{ $tab === 'tempos' ? 'Tempo das tarefas' : 'E-mail SMTP (CRUR)' }}
        </div>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    {{ $tab === 'tempos' ? 'Configuração de tempo das tarefas' : 'Configuracao de Caixas de E-mail SMTP (CRUR)' }}
                </h1>
                <p class="text-slate-500 text-sm mt-1">
                    {{ $tab === 'tempos'
                        ? 'Defina o tempo padrão por serviço para o controle de SLA.'
                        : 'Defina servidor, remetente, respondente e credenciais.' }}
                </p>
            </div>
            <button type="submit" form="email-caixa-form"
                    class="hidden">
                Salvar Configurações
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('master.email-caixas.index', ['tab' => 'email']) }}"
               class="px-3 py-2 rounded-xl border {{ $tab === 'email' ? 'border-indigo-200 bg-indigo-50 text-indigo-700 font-semibold' : 'border-slate-200 bg-white text-slate-600' }}">
                E-mail
            </a>
            <a href="{{ route('master.email-caixas.index', ['tab' => 'tempos']) }}"
               class="px-3 py-2 rounded-xl border {{ $tab === 'tempos' ? 'border-indigo-200 bg-indigo-50 text-indigo-700 font-semibold' : 'border-slate-200 bg-white text-slate-600' }}">
                Tempo das tarefas
            </a>
        </div>

        @if (session('ok'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any() && !$caixaEmEdicaoId)
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm space-y-1">
                <p class="font-semibold">Houve um problema ao salvar a caixa.</p>
                <ul class="list-disc list-inside text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="{{ $tab === 'email' ? 'space-y-6' : 'hidden' }}" data-tab-panel="email">
            <form id="email-caixa-form" method="POST" action="{{ route('master.email-caixas.store') }}" class="space-y-4">
                @csrf
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <h2 class="text-sm font-semibold text-slate-800">Servidor SMTP & Autenticacao</h2>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Nome da configuracao</label>
                                <input type="text" name="nome" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                       value="{{ old('nome') }}" placeholder="Ex.: SMTP Principal Formed" required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Servidor SMTP (Host)</label>
                                <input type="text" name="host" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                       value="{{ old('host') }}" placeholder="smtp.dominio.com" required>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-600">Porta</label>
                                    <input type="number" name="porta" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                           value="{{ old('porta', 587) }}" min="1" max="65535" required>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-600">Seguranca</label>
                                    <select name="criptografia" class="w-full rounded-lg border border-slate-200 px-3 py-2">
                                        <option value="starttls" @selected(old('criptografia', 'starttls') === 'starttls')>STARTTLS</option>
                                        <option value="ssl" @selected(old('criptografia', 'starttls') === 'ssl')>SSL</option>
                                        <option value="none" @selected(old('criptografia', 'starttls') === 'none')>Sem criptografia</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Timeout (segundos)</label>
                                <input type="number" name="timeout" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                       value="{{ old('timeout', 30) }}" min="1" max="600">
                            </div>
                        </div>
                    </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Credenciais de Acesso</div>
                        <div class="text-xs text-slate-500">Usuario, senha e autenticacao</div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <label class="text-xs font-semibold text-slate-600">E-mail de login / usuario SMTP</label>
                        <input type="text" name="usuario" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                               value="{{ old('usuario') }}" placeholder="usuario@dominio.com">
                    </div>

                    <div class="space-y-2 text-sm">
                        <label class="text-xs font-semibold text-slate-600">Senha SMTP</label>
                        <div class="flex items-center gap-2">
                            <input type="password" name="senha" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                   value="{{ old('senha') }}" autocomplete="new-password" data-password-field>
                            <button type="button" class="px-3 py-2 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50" data-password-toggle>
                                Mostrar
                            </button>
                        </div>
                        <div class="text-[11px] text-slate-500">Sua senha e armazenada de forma segura</div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <label class="text-slate-700">Autenticacao obrigatoria</label>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="requer_autenticacao" value="0">
                                <input type="checkbox" name="requer_autenticacao" value="1"
                                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                       {{ old('requer_autenticacao', '1') === '1' ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="text-slate-700">Caixa ativa</label>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="ativo" value="0">
                                <input type="checkbox" name="ativo" value="1"
                                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                       {{ old('ativo', '1') === '1' ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-4">
                        <button type="submit" formaction="{{ route('master.email-caixas.testar') }}"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50">
                            Testar conexao
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-500">
                            Salvar configuracoes
                        </button>
                        @if (session('smtp_ok'))
                            <span class="text-xs text-emerald-600 font-semibold">{{ session('smtp_ok') }}</span>
                        @elseif (session('smtp_error'))
                            <span class="text-xs text-rose-600 font-semibold">{{ session('smtp_error') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        @php $totalCaixas = $caixas->count(); @endphp

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Caixas de e-mail cadastradas</h2>
                    <p class="text-xs text-slate-500">Gerencie configuracoes existentes</p>
                </div>
                <span class="text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-full">{{ $totalCaixas }} caixa(s)</span>
            </div>

                <div class="space-y-3">
                    @foreach ($caixas as $caixa)
                        @php
                            $isEditing = $caixaEmEdicaoId === $caixa->id;
                            $emailLogin = $caixa->usuario ?: '-';
                            $statusAtivo = $caixa->ativo ? 'Ativa' : 'Inativa';
                        @endphp

                        <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $caixa->nome }}</div>
                                    <div class="text-xs text-slate-500">{{ $emailLogin }}</div>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full {{ $caixa->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusAtivo }}
                                </span>
                            </div>
                            <div class="text-xs text-slate-600">
                                Host: {{ $caixa->host }} &middot; Porta: {{ $caixa->porta }} &middot; {{ strtoupper($caixa->criptografia ?? 'none') }}
                            </div>
                            <div class="flex flex-wrap items-center gap-2" x-data>
                                <button type="button"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        x-on:click="$dispatch('open-modal', 'email-caixa-{{ $caixa->id }}')">
                                    Editar
                                </button>
                                <button type="button"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700"
                                        x-on:click="$dispatch('open-modal', 'email-caixa-teste-{{ $caixa->id }}')">
                                    Testar
                                </button>
                                <form method="POST" action="{{ route('master.email-caixas.destroy', $caixa) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 text-xs font-semibold border border-rose-200 hover:bg-rose-100"
                                            onclick="return confirm('Excluir este email?')">
                                        Remover
                                    </button>
                                </form>
                            </div>
                        </div>

                        <x-modal name="email-caixa-{{ $caixa->id }}" :show="$isEditing" maxWidth="2xl">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="text-sm font-semibold text-slate-800">Editar email de acesso</h3>
                            </div>
                            <div class="px-6 py-4 space-y-4">
                                @if ($isEditing && $errors->any())
                                    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-3 py-2 rounded-lg text-xs">
                                        <p class="font-semibold">Revise os campos deste email:</p>
                                        <ul class="list-disc list-inside space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form id="update-{{ $caixa->id }}" method="POST"
                                      action="{{ route('master.email-caixas.update', $caixa) }}" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="caixa_id" value="{{ $caixa->id }}">

                                    <div class="space-y-1 text-sm">
                                        <label class="text-xs font-semibold text-slate-600">E-mail de login / usuario SMTP</label>
                                        <input type="text" name="usuario" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                               value="{{ $isEditing ? old('usuario', $caixa->usuario) : $caixa->usuario }}" required>
                                    </div>
                                    <div class="space-y-1 text-sm">
                                        <label class="text-xs font-semibold text-slate-600">Senha SMTP</label>
                                        <input type="password" name="senha" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                               value="{{ $isEditing ? old('senha') : '' }}" autocomplete="new-password">
                                    </div>

                                    <div class="flex flex-wrap items-center justify-end gap-2 text-sm">
                                        <button type="button"
                                                class="inline-flex items-center px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold"
                                                x-on:click="$dispatch('close-modal', 'email-caixa-{{ $caixa->id }}')">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                                            Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </x-modal>

                        <x-modal name="email-caixa-teste-{{ $caixa->id }}" :show="(int) ($caixaTesteId ?: session('smtp_send_id')) === $caixa->id" maxWidth="lg">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="text-sm font-semibold text-slate-800">Enviar email de teste</h3>
                            </div>
                            <div class="px-6 py-4 space-y-4">
                                @if ((int) session('smtp_send_id') === $caixa->id && session('smtp_send_error'))
                                    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-3 py-2 rounded-lg text-xs">
                                        {{ session('smtp_send_error') }}
                                    </div>
                                @endif
                                @if ((int) session('smtp_send_id') === $caixa->id && session('smtp_send_ok'))
                                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-3 py-2 rounded-lg text-xs">
                                        {{ session('smtp_send_ok') }}
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('master.email-caixas.enviar-teste', $caixa) }}" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="caixa_teste_id" value="{{ $caixa->id }}">

                                    <div class="space-y-1 text-sm">
                                        <label class="text-xs font-semibold text-slate-600">Email destino</label>
                                        <input type="email" name="destino" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                               value="{{ $caixaTesteId === $caixa->id ? old('destino') : '' }}" placeholder="destino@dominio.com" required>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-end gap-2 text-sm">
                                        <button type="button"
                                                class="inline-flex items-center px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold"
                                                x-on:click="$dispatch('close-modal', 'email-caixa-teste-{{ $caixa->id }}')">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700">
                                            Enviar teste
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </x-modal>
                    @endforeach
                </div>
        </div>

        <div class="{{ $tab === 'tempos' ? 'space-y-6' : 'hidden' }}" data-tab-panel="tempos">
            <form method="POST" action="{{ route('master.tempo-tarefas.store') }}" class="space-y-4">
                @csrf
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-800">Tempo padrão por serviço</h2>
                        <span class="text-xs text-slate-500">Em minutos</span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($servicos as $servico)
                            @php
                                $isExcluido = in_array((int) $servico->id, $excluirServicoIds ?? [], true);
                                $tempoAtual = $tempos[$servico->id]->tempo_minutos ?? 0;
                            @endphp
                            <div class="px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">{{ $servico->nome }}</div>
                                    @if($isExcluido)
                                        <div class="text-xs text-slate-500">Serviço não aplicável para SLA.</div>
                                    @else
                                        <div class="text-xs text-slate-500">Defina o tempo máximo para esta tarefa.</div>
                                    @endif
                                </div>
                                <div class="w-full md:w-44">
                                    <input type="number"
                                           min="0"
                                           max="10080"
                                           step="1"
                                           name="tempos[{{ $servico->id }}]"
                                           value="{{ old('tempos.'.$servico->id, $tempoAtual) }}"
                                           {{ $isExcluido ? 'disabled' : '' }}
                                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm {{ $isExcluido ? 'bg-slate-100 text-slate-400' : '' }}"
                                           placeholder="Ex: 60">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end">
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        Salvar tempos
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.querySelector('[data-password-toggle]');
            const field = document.querySelector('[data-password-field]');
            if (toggle && field) {
                toggle.addEventListener('click', () => {
                    const isPassword = field.getAttribute('type') === 'password';
                    field.setAttribute('type', isPassword ? 'text' : 'password');
                    toggle.textContent = isPassword ? 'Ocultar' : 'Mostrar';
                });
            }
        });
    </script>
@endpush
