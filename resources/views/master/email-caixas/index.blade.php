@extends('layouts.master')
@section('title', 'Configuracao de Caixas de Email')

@section('content')
    @php $caixaEmEdicaoId = (int) old('caixa_id'); @endphp

    <div class="max-w-6xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Configuracao de Caixas de Email</h1>
                <p class="text-slate-500 text-sm mt-1">Cadastre SMTPs por caixa para uso do suporte.</p>
            </div>
        </div>

        @if (session('ok'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                {{ session('ok') }}
            </div>
        @endif

        @if (session('smtp_ok'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                {{ session('smtp_ok') }}
            </div>
        @endif

        @if (session('smtp_error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm">
                {{ session('smtp_error') }}
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

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Nova caixa de email</h2>
                <p class="text-[11px] text-slate-500">Defina servidor, seguranca e credenciais.</p>
            </div>

            <form method="POST" action="{{ route('master.email-caixas.store') }}" class="space-y-4">
                @csrf
                <div class="grid lg:grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-800">Servidor SMTP & Autenticacao</h3>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Nome da configuracao *</label>
                                <input type="text" name="nome" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                       value="{{ old('nome') }}" placeholder="SMTP Principal Formed" required>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Servidor SMTP (Host) *</label>
                                <input type="text" name="host" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                       value="{{ old('host') }}" placeholder="smtp.dominio.com" required>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-600">Porta *</label>
                                    <input type="number" name="porta" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                           value="{{ old('porta', 587) }}" min="1" max="65535" required>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-600">Seguranca *</label>
                                    <select name="criptografia" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                                        <option value="starttls" @selected(old('criptografia', 'starttls') === 'starttls')>STARTTLS</option>
                                        <option value="ssl" @selected(old('criptografia', 'starttls') === 'ssl')>SSL</option>
                                        <option value="none" @selected(old('criptografia', 'starttls') === 'none')>Sem criptografia</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Timeout (segundos)</label>
                                <input type="number" name="timeout" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                       value="{{ old('timeout', 30) }}" min="1" max="600">
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-800">Credenciais de Acesso (Usuario)</h3>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Email de login / Usuario SMTP</label>
                                <input type="text" name="usuario" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                       value="{{ old('usuario') }}" placeholder="usuario@dominio.com">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Senha SMTP</label>
                                <input type="password" name="senha" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                       value="{{ old('senha') }}" autocomplete="new-password">
                            </div>
                            <div class="flex flex-wrap items-center gap-4 text-sm">
                                <input type="hidden" name="requer_autenticacao" value="0">
                                <label class="inline-flex items-center gap-2 text-slate-700">
                                    <input type="checkbox" name="requer_autenticacao" value="1"
                                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                           {{ old('requer_autenticacao', '1') === '1' ? 'checked' : '' }}>
                                    Autenticacao obrigatoria
                                </label>
                                <input type="hidden" name="ativo" value="0">
                                <label class="inline-flex items-center gap-2 text-slate-700">
                                    <input type="checkbox" name="ativo" value="1"
                                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                           {{ old('ativo', '1') === '1' ? 'checked' : '' }}>
                                    Ativo
                                </label>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                                <button type="submit" formaction="{{ route('master.email-caixas.testar') }}"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                                    Testar conexao
                                </button>
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                                    Salvar configuracoes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @php $totalCaixas = $caixas->count(); @endphp

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Caixas cadastradas</h2>
                    <p class="text-[11px] text-slate-500">Edite ou exclua as configuracoes existentes.</p>
                </div>
                <span class="text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-full">{{ $totalCaixas }} caixa(s)</span>
            </div>

            <div class="space-y-4">
                @forelse ($caixas as $caixa)
                    @php
                        $isEditing = $caixaEmEdicaoId === $caixa->id;
                        $valor = fn ($campo, $fallback = null) => $isEditing ? old($campo, $fallback) : $fallback;
                        $requerAuth = $isEditing
                            ? (string) old('requer_autenticacao', $caixa->requer_autenticacao ? '1' : '0') === '1'
                            : (bool) $caixa->requer_autenticacao;
                        $ativoMarcado = $isEditing
                            ? (string) old('ativo', $caixa->ativo ? '1' : '0') === '1'
                            : (bool) $caixa->ativo;
                        $criptografiaAtual = $isEditing ? old('criptografia', $caixa->criptografia) : $caixa->criptografia;
                        $criptografiaAtual = $criptografiaAtual === 'tls' ? 'starttls' : $criptografiaAtual;
                    @endphp

                    @if ($isEditing && $errors->any())
                        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-3 py-2 rounded-lg text-xs">
                            <p class="font-semibold">Revise os campos desta caixa:</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="border border-slate-100 rounded-xl p-4 space-y-3 bg-slate-50/60">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $caixa->nome }}</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full {{ $caixa->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $caixa->ativo ? 'Ativa' : 'Inativa' }}
                            </span>
                        </div>

                        <form id="update-{{ $caixa->id }}" method="POST"
                              action="{{ route('master.email-caixas.update', $caixa) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="caixa_id" value="{{ $caixa->id }}">

                            <div class="grid md:grid-cols-2 gap-3 text-sm">
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-600">Nome *</label>
                                    <input type="text" name="nome" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                           value="{{ $valor('nome', $caixa->nome) }}" required>
                                </div>
                            </div>

                            <div class="grid md:grid-cols-3 gap-3 text-sm">
                                <div class="space-y-1 md:col-span-2">
                                    <label class="text-xs font-semibold text-slate-600">Host SMTP *</label>
                                    <input type="text" name="host" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                           value="{{ $valor('host', $caixa->host) }}" required>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-600">Porta *</label>
                                    <input type="number" name="porta" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                           value="{{ $valor('porta', $caixa->porta) }}" min="1" max="65535" required>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-600">Seguranca *</label>
                                    <select name="criptografia" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                                        <option value="starttls" @selected($criptografiaAtual === 'starttls')>STARTTLS</option>
                                        <option value="ssl" @selected($criptografiaAtual === 'ssl')>SSL</option>
                                        <option value="none" @selected($criptografiaAtual === 'none')>Sem criptografia</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-600">Timeout (segundos)</label>
                                    <input type="number" name="timeout" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                           value="{{ $valor('timeout', $caixa->timeout ?? 30) }}" min="1" max="600">
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-3 text-sm">
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-600">Usuario SMTP</label>
                                    <input type="text" name="usuario" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                           value="{{ $valor('usuario', $caixa->usuario) }}">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-600">Senha SMTP</label>
                                    <input type="password" name="senha" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                           placeholder="Manter atual" autocomplete="new-password">
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                                <div class="flex items-center gap-4">
                                    <input type="hidden" name="requer_autenticacao" value="0">
                                    <label class="inline-flex items-center gap-2 text-slate-700">
                                        <input type="checkbox" name="requer_autenticacao" value="1"
                                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                               {{ $requerAuth ? 'checked' : '' }}>
                                        Requer autenticacao
                                    </label>
                                    <input type="hidden" name="ativo" value="0">
                                    <label class="inline-flex items-center gap-2 text-slate-700">
                                        <input type="checkbox" name="ativo" value="1"
                                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                               {{ $ativoMarcado ? 'checked' : '' }}>
                                        Ativo
                                    </label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit" form="update-{{ $caixa->id }}"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                                        Salvar
                                    </button>
                                    <button type="submit" form="test-{{ $caixa->id }}"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                                        Testar conexao
                                    </button>
                                    <button type="submit" form="delete-{{ $caixa->id }}"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-50 text-rose-700 text-sm font-semibold border border-rose-200 hover:bg-rose-100"
                                            onclick="return confirm('Excluir esta caixa de email?')">
                                        Excluir
                                    </button>
                                </div>
                            </div>
                        </form>
                        <form id="delete-{{ $caixa->id }}" method="POST"
                              action="{{ route('master.email-caixas.destroy', $caixa) }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="caixa_id" value="{{ $caixa->id }}">
                        </form>
                        <form id="test-{{ $caixa->id }}" method="POST"
                              action="{{ route('master.email-caixas.testar-salvo', $caixa) }}">
                            @csrf
                        </form>
                    </div>
                @empty
                    <div class="text-sm text-slate-600">Nenhuma caixa cadastrada.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
