@extends('layouts.master')
@section('title', 'Configuração de Caixas de Email')

@section('content')
    @php
        $caixaEmEdicaoId = (int) old('caixa_id');
        $caixaTesteId = (int) old('caixa_teste_id');
        $tab = request('tab', 'email');
    @endphp

    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-6 bg-gradient-to-b from-slate-50 via-white to-indigo-50/40 min-h-[calc(100vh-6rem)]">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    @if ($tab === 'tempos')
                        Configuração de tempo das tarefas
                    @elseif ($tab === 'whatsapp')
                        Configuração de WhatsApp
                    @elseif ($tab === 'painel')
                        Configuração do painel
                    @else
                        Configuração de Caixas de E-mail
                    @endif
                </h1>
                <p class="text-slate-500 text-sm mt-1">
                    @if ($tab === 'tempos')
                        Defina o tempo padrão por serviço para o controle de SLA.
                    @elseif ($tab === 'whatsapp')
                        Configure a Evolution API, crie a instância e acompanhe a conexão em tempo real.
                    @elseif ($tab === 'painel')
                        Ajuste os textos exibidos no painel de controle.
                    @else
                        Defina servidor, remetente, respondente e credenciais.
                    @endif
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
            <a href="{{ route('master.email-caixas.index', ['tab' => 'whatsapp']) }}"
               class="px-3 py-2 rounded-xl border {{ $tab === 'whatsapp' ? 'border-indigo-200 bg-indigo-50 text-indigo-700 font-semibold' : 'border-slate-200 bg-white text-slate-600' }}">
                WhatsApp
            </a>
            <a href="{{ route('master.email-caixas.index', ['tab' => 'painel']) }}"
               class="px-3 py-2 rounded-xl border {{ $tab === 'painel' ? 'border-indigo-200 bg-indigo-50 text-indigo-700 font-semibold' : 'border-slate-200 bg-white text-slate-600' }}">
                Configurar Card
            </a>
        </div>

        @if (session('ok'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                {{ session('ok') }}
            </div>
        @endif

        @if ($tab === 'email' && $errors->any() && !$caixaEmEdicaoId)
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
                <x-toggle-ativo
                    name="ativo"
                    :checked="old('ativo', '1') === '1'"
                    on-label="Ativo"
                    off-label="Inativo"
                    text-class="text-sm text-slate-700"
                />
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl border border-indigo-100 shadow-sm shadow-indigo-100/40 overflow-hidden">
                        <div class="border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 px-4 py-3">
                            <h2 class="text-sm font-semibold text-slate-800">Servidor SMTP e Autenticação</h2>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Nome da configuração</label>
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
                                    <label class="text-xs font-semibold text-slate-600">Segurança</label>
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

                    <div class="bg-white rounded-xl border border-emerald-100 shadow-sm shadow-emerald-100/40 overflow-hidden">
                        <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-cyan-50 px-4 py-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-800">Credenciais de Acesso (U - Usuário)</h2>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">E-mail de Login / Usuário SMTP</label>
                                <input type="text" name="usuario" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                       value="{{ old('usuario') }}" placeholder="usuario@dominio.com">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Senha SMTP</label>
                                <input type="password" name="senha" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                       value="{{ old('senha') }}" autocomplete="new-password">
                            </div>
                            <div class="flex flex-wrap items-center gap-4 text-sm">
                                <input type="hidden" name="requer_autenticacao" value="0">
                                <label class="inline-flex items-center gap-2 text-slate-700">
                                    <input type="checkbox" name="requer_autenticacao" value="1"
                                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        {{ old('requer_autenticacao', '1') === '1' ? 'checked' : '' }}>
                                    Autenticação obrigatória?
                                </label>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-3 pt-6">
                                <button type="submit" formaction="{{ route('master.email-caixas.testar') }}"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                                    Testar Conexão
                                </button>
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                                    Salvar Configurações
                                </button>
                                @if (session('smtp_ok'))
                                    <span class="text-xs text-emerald-600 font-semibold">{{ session('smtp_ok') }}</span>
                                @elseif (session('smtp_error'))
                                    <span class="text-xs text-rose-600 font-semibold">{{ session('smtp_error') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-amber-100 shadow-sm shadow-amber-100/40 overflow-hidden">
                        <div class="border-b border-amber-100 bg-gradient-to-r from-amber-50 to-orange-50 px-4 py-3">
                            <h2 class="text-sm font-semibold text-slate-800">Cópia em Enviados</h2>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Pasta de enviados</label>
                                <input type="text" name="imap_sent_folder" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                       value="{{ old('imap_sent_folder', 'INBOX.Sent') }}" placeholder="INBOX.Sent">
                                <p class="text-[11px] text-slate-500">Na Hostinger, normalmente use <strong>INBOX.Sent</strong>.</p>
                            </div>

                            <input type="hidden" name="imap_host" value="{{ old('imap_host') }}">
                            <input type="hidden" name="imap_porta" value="{{ old('imap_porta') }}">
                            <input type="hidden" name="imap_criptografia" value="{{ old('imap_criptografia') }}">
                            <input type="hidden" name="imap_usuario" value="{{ old('imap_usuario') }}">
                            <input type="hidden" name="imap_senha" value="{{ old('imap_senha') }}">
                        </div>
                    </div>
                </div>
            </form>

            @php $totalCaixas = $caixas->count(); @endphp

            <div class="bg-white border border-violet-100 shadow-sm shadow-violet-100/40 p-5 space-y-4 rounded-2xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">E-mail cadastrado</h2>
                        <p class="text-[11px] text-slate-500">Selecione para editar ou remover.</p>
                    </div>
                    <span class="text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-full">{{ $totalCaixas }} caixa(s)</span>
                </div>

                <div class="space-y-3">
                    @forelse ($caixas as $caixa)
                        @php
                            $isEditing = $caixaEmEdicaoId === $caixa->id;
                            $emailLogin = $caixa->usuario ?: '-';
                        @endphp

                        <div class="rounded-lg border border-slate-200 px-3 py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $emailLogin }}</p>
                                <p class="text-[11px] text-slate-500">{{ $caixa->nome }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2" x-data>
                                <button type="button"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800"
                                        x-on:click="$dispatch('open-modal', 'email-caixa-{{ $caixa->id }}')">
                                    Editar
                                </button>
                                <button type="button"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700"
                                        x-on:click="$dispatch('open-modal', 'email-caixa-teste-{{ $caixa->id }}')">
                                    Testar envio
                                </button>
                                <form method="POST" action="{{ route('master.email-caixas.destroy', $caixa) }}" data-confirm="Excluir este e-mail?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 text-xs font-semibold border border-rose-200 hover:bg-rose-100"
                                            >
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </div>

                        <x-modal name="email-caixa-{{ $caixa->id }}" :show="$isEditing" maxWidth="2xl">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="text-sm font-semibold text-slate-800">Editar e-mail de acesso</h3>
                            </div>
                            <div class="px-6 py-4 space-y-4">
                                @if ($isEditing && $errors->any())
                                    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-3 py-2 rounded-lg text-xs">
                                        <p class="font-semibold">Revise os campos deste e-mail:</p>
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

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">SMTP</p>
                                            <div class="space-y-1 text-sm">
                                                <label class="text-xs font-semibold text-slate-600">Nome da configuração</label>
                                                <input type="text" name="nome" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                                       value="{{ $isEditing ? old('nome', $caixa->nome) : $caixa->nome }}" required>
                                            </div>
                                            <div class="space-y-1 text-sm">
                                                <label class="text-xs font-semibold text-slate-600">Servidor SMTP</label>
                                                <input type="text" name="host" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                                       value="{{ $isEditing ? old('host', $caixa->host) : $caixa->host }}" required>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="space-y-1 text-sm">
                                                    <label class="text-xs font-semibold text-slate-600">Porta</label>
                                                    <input type="number" name="porta" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                                           value="{{ $isEditing ? old('porta', $caixa->porta) : $caixa->porta }}" min="1" max="65535" required>
                                                </div>
                                                <div class="space-y-1 text-sm">
                                                    <label class="text-xs font-semibold text-slate-600">Segurança</label>
                                                    <select name="criptografia" class="w-full rounded-lg border border-slate-200 px-3 py-2">
                                                        <option value="starttls" @selected(($isEditing ? old('criptografia', $caixa->criptografia) : $caixa->criptografia) === 'starttls')>STARTTLS</option>
                                                        <option value="ssl" @selected(($isEditing ? old('criptografia', $caixa->criptografia) : $caixa->criptografia) === 'ssl')>SSL</option>
                                                        <option value="none" @selected(($isEditing ? old('criptografia', $caixa->criptografia) : $caixa->criptografia) === 'none')>Sem criptografia</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="space-y-1 text-sm">
                                                <label class="text-xs font-semibold text-slate-600">Timeout</label>
                                                <input type="number" name="timeout" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                                       value="{{ $isEditing ? old('timeout', $caixa->timeout) : $caixa->timeout }}" min="1" max="600">
                                            </div>
                                            <div class="space-y-1 text-sm">
                                                <label class="text-xs font-semibold text-slate-600">E-mail de Login / Usuário SMTP</label>
                                                <input type="text" name="usuario" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                                       value="{{ $isEditing ? old('usuario', $caixa->usuario) : $caixa->usuario }}" required>
                                            </div>
                                            <div class="space-y-1 text-sm">
                                                <label class="text-xs font-semibold text-slate-600">Senha SMTP</label>
                                                <input type="password" name="senha" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                                       value="{{ $isEditing ? old('senha') : '' }}" autocomplete="new-password">
                                            </div>
                                            <label class="inline-flex items-center gap-2 text-slate-700 text-sm">
                                                <input type="hidden" name="requer_autenticacao" value="0">
                                                <input type="checkbox" name="requer_autenticacao" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                       @checked((bool) ($isEditing ? old('requer_autenticacao', $caixa->requer_autenticacao) : $caixa->requer_autenticacao))>
                                                Autenticação obrigatória?
                                            </label>
                                            <label class="inline-flex items-center gap-2 text-slate-700 text-sm">
                                                <input type="hidden" name="ativo" value="0">
                                                <input type="checkbox" name="ativo" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                       @checked((bool) ($isEditing ? old('ativo', $caixa->ativo) : $caixa->ativo))>
                                                Caixa ativa
                                            </label>
                                        </div>

                                        <div class="space-y-3 rounded-xl border border-amber-200 bg-amber-50/60 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">IMAP / Enviados</p>
                                            <div class="space-y-1 text-sm">
                                                <label class="text-xs font-semibold text-slate-600">Pasta de enviados</label>
                                                <input type="text" name="imap_sent_folder" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                                       value="{{ $isEditing ? old('imap_sent_folder', $caixa->imap_sent_folder ?: 'INBOX.Sent') : ($caixa->imap_sent_folder ?: 'INBOX.Sent') }}">
                                            </div>
                                            <div class="rounded-xl border border-amber-200 bg-white/80 px-3 py-3 text-[11px] text-amber-900 space-y-1">
                                                <p class="font-semibold">Mesma conta da caixa</p>
                                                <p>O sistema usa automaticamente o mesmo usuário e senha do SMTP para gravar uma cópia em <strong>Enviados</strong>.</p>
                                                <p>Se o host SMTP estiver como `smtp.dominio.com`, ele tenta `imap.dominio.com` com porta `993`.</p>
                                            </div>
                                            <input type="hidden" name="imap_host" value="{{ $isEditing ? old('imap_host', $caixa->imap_host) : $caixa->imap_host }}">
                                            <input type="hidden" name="imap_porta" value="{{ $isEditing ? old('imap_porta', $caixa->imap_porta) : $caixa->imap_porta }}">
                                            <input type="hidden" name="imap_criptografia" value="{{ $isEditing ? old('imap_criptografia', $caixa->imap_criptografia) : $caixa->imap_criptografia }}">
                                            <input type="hidden" name="imap_usuario" value="{{ $isEditing ? old('imap_usuario', $caixa->imap_usuario) : $caixa->imap_usuario }}">
                                            <input type="hidden" name="imap_senha" value="">
                                        </div>
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
                                <h3 class="text-sm font-semibold text-slate-800">Enviar e-mail de teste</h3>
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
                                        <label class="text-xs font-semibold text-slate-600">E-mail de destino</label>
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
                    @empty
                        <div class="text-sm text-slate-600">Nenhuma caixa cadastrada.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="{{ $tab === 'whatsapp' ? 'space-y-6' : 'hidden' }}" data-tab-panel="whatsapp">
            @php
                $whatsappConfigErrors = collect($errors->getMessages())
                    ->filter(fn ($messages, $field) => in_array((string) $field, ['financeiro_numero', 'operacional_numero', 'ativo', 'whatsapp_config'], true))
                    ->flatten();
                $whatsappFinanceiro = $whatsappInstancias[\App\Models\WhatsappInstancia::TIPO_FINANCEIRO] ?? null;
                $whatsappOperacional = $whatsappInstancias[\App\Models\WhatsappInstancia::TIPO_OPERACIONAL] ?? null;
                $whatsappConfigRef = $whatsappFinanceiro ?: $whatsappOperacional;
                $whatsappBaseUrl = config('services.evolution.base_url');
                $whatsappApiKeyConfigured = filled(config('services.evolution.api_key'));
                $whatsappAtivo = old('ativo', ($whatsappConfigRef?->ativo ?? true) ? '1' : '0') === '1';
                $whatsappCards = [
                    \App\Models\WhatsappInstancia::TIPO_FINANCEIRO => [
                        'label' => 'Financeiro',
                        'description' => 'Vincule a instância já criada na Evolution para envio financeiro e cobranças.',
                        'iconBg' => 'bg-emerald-50',
                        'iconText' => 'text-emerald-600',
                        'numero' => old('financeiro_numero', $whatsappFinanceiro->numero ?? ''),
                        'instance_name' => 'Formed_Finaceiro',
                        'instancia' => $whatsappFinanceiro,
                    ],
                    \App\Models\WhatsappInstancia::TIPO_OPERACIONAL => [
                        'label' => 'Operacional',
                        'description' => 'Vincule a instância já criada na Evolution para avisos operacionais e notificações.',
                        'iconBg' => 'bg-sky-50',
                        'iconText' => 'text-sky-600',
                        'numero' => old('operacional_numero', $whatsappOperacional->numero ?? ''),
                        'instance_name' => 'Formed_Operacional',
                        'instancia' => $whatsappOperacional,
                    ],
                ];
                $whatsappHasConfig = filled($whatsappBaseUrl) && $whatsappApiKeyConfigured;
            @endphp

            @if($whatsappConfigErrors->isNotEmpty())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <div class="font-semibold mb-1">Não foi possível salvar a configuração de WhatsApp</div>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        @foreach($whatsappConfigErrors as $mensagem)
                            <li>{{ $mensagem }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-6">
                <form method="POST" action="{{ route('master.email-caixas.whatsapp.store') }}"
                      class="rounded-2xl border border-emerald-100 bg-white shadow-sm shadow-emerald-100/40 overflow-hidden">
                    @csrf
                    <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50 via-white to-cyan-50 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-800">Credenciais da Evolution API</h2>
                        <p class="mt-1 text-xs text-slate-500">As instâncias são criadas diretamente na Evolution. Aqui a Formed só vincula os números e opera a conexão.</p>
                    </div>
                    <div class="p-5 space-y-4 text-sm">
                        <x-toggle-ativo
                            name="ativo"
                            :checked="$whatsappAtivo"
                            on-label="Integração ativa"
                            off-label="Integração inativa"
                            text-class="text-sm text-slate-700"
                        />

                        @if(!$whatsappHasConfig)
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                                A integração da Evolution ainda não foi configurada no ambiente.
                            </div>
                        @endif

                        <div class="grid gap-4 lg:grid-cols-2">
                            @foreach($whatsappCards as $tipo => $card)
                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-2">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-2xl {{ $card['iconBg'] }} {{ $card['iconText'] }} grid place-items-center">
                                            <i class="fa-brands fa-whatsapp text-lg"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">Instância {{ $card['label'] }}</div>
                                            <div class="text-xs text-slate-500">{{ $card['description'] }}</div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs font-semibold text-slate-600">Número com DDI</label>
                                        <input type="text" name="{{ $tipo }}_numero" class="w-full rounded-lg border border-slate-200 px-3 py-2"
                                               value="{{ $card['numero'] }}" placeholder="5567999999999">
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                            A Formed trabalhará com <strong>2 instâncias por empresa</strong>, uma para <strong>financeiro</strong> e outra para <strong>operacional</strong>. As instâncias devem existir previamente na Evolution. Nesta tela, você apenas vincula os números e usa os botões de conectar, atualizar status, reiniciar e logout.
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                Salvar configuração
                            </button>
                        </div>
                    </div>
                </form>

                <div class="grid gap-6 xl:grid-cols-2">
                    @foreach($whatsappCards as $tipo => $card)
                        @php
                            $instancia = $card['instancia'];
                            $state = strtolower((string) ($instancia->last_state ?? 'closed'));
                            $instanceNameAtual = $instancia?->instance_name ?: $card['instance_name'];
                            $hasInstance = filled($instanceNameAtual);
                            $canUseCard = $whatsappHasConfig
                                && ($hasInstance || filled($card['numero']))
                                && ($instancia?->ativo ?? $whatsappAtivo);
                        @endphp

                        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden" data-whatsapp-card="{{ $tipo }}">
                            <div class="px-5 py-5 border-b border-slate-100">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <div class="h-14 w-14 rounded-2xl {{ $card['iconBg'] }} {{ $card['iconText'] }} grid place-items-center shadow-inner">
                                            <i class="fa-brands fa-whatsapp text-2xl"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-2xl font-semibold text-slate-900 truncate">
                                                WhatsApp - {{ $card['label'] }}
                                                <span class="text-slate-500 text-lg" data-instance-name>{{ $instanceNameAtual ? '#'.$instanceNameAtual : 'Não criada' }}</span>
                                            </div>
                                            <div class="text-sm text-slate-500">{{ $card['description'] }}</div>
                                        </div>
                                    </div>

                                    <div class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold
                                        {{ $state === 'open' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($state === 'connecting' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-slate-50 text-slate-600') }}"
                                        data-status-badge>
                                        <span class="h-2.5 w-2.5 rounded-full {{ $state === 'open' ? 'bg-emerald-500' : ($state === 'connecting' ? 'bg-amber-400' : 'bg-slate-300') }}"></span>
                                        <span data-status-label>{{ strtoupper($state ?: 'closed') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="px-5 py-5 space-y-5">
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button"
                                            class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 {{ $hasInstance ? '' : 'opacity-60 cursor-not-allowed' }}"
                                            data-action="restart"
                                            @if(!$hasInstance) data-lock-disabled="1" @endif
                                            @disabled(!$hasInstance)>
                                        Reiniciar
                                    </button>
                                    <button type="button"
                                            class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 {{ $hasInstance ? '' : 'opacity-60 cursor-not-allowed' }}"
                                            data-action="logout"
                                            @if(!$hasInstance) data-lock-disabled="1" @endif
                                            @disabled(!$hasInstance)>
                                        Logout
                                    </button>
                                    <button type="button"
                                            class="rounded-2xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-600 {{ $hasInstance ? '' : 'opacity-60 cursor-not-allowed' }}"
                                            data-action="connect"
                                            @if(!$hasInstance) data-lock-disabled="1" @endif
                                            @disabled(!$hasInstance)>
                                        Conectar e gerar QR
                                    </button>
                                    <button type="button"
                                            class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 {{ $hasInstance ? '' : 'opacity-60 cursor-not-allowed' }}"
                                            data-action="status"
                                            @if(!$hasInstance) data-lock-disabled="1" @endif
                                            @disabled(!$hasInstance)>
                                        Atualizar status
                                    </button>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <div class="text-[11px] uppercase tracking-wide font-semibold text-slate-500">Número</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-800">{{ $card['numero'] ?: 'Não informado' }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <div class="text-[11px] uppercase tracking-wide font-semibold text-slate-500">Última atualização</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-800" data-last-status-at>{{ optional($instancia?->last_status_at)->format('d/m/Y H:i') ?? '—' }}</div>
                                    </div>
                                </div>

                                <div class="text-sm text-slate-600">
                                    Estado atual:
                                    <span class="font-semibold text-slate-900" data-state-text>{{ strtoupper($state ?: 'closed') }}</span>
                                </div>

                                <div class="@if(blank($instancia?->last_error)) hidden @endif rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" data-error-box>
                                    {{ $instancia?->last_error }}
                                </div>

                                <div class="hidden items-center gap-3 text-slate-500" data-loading-row>
                                    <svg class="w-5 h-5 animate-spin text-emerald-600" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".25" stroke-width="4"></circle>
                                        <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4"></path>
                                    </svg>
                                    Processando a instância...
                                </div>

                                <div class="hidden text-center" data-qr-wrap>
                                    <img class="w-64 h-64 border rounded-2xl mx-auto shadow-sm" alt="QR Code" data-qr-img>
                                    <div class="text-xs text-slate-500 mt-2">Abra o WhatsApp no celular e escaneie o QR.</div>
                                </div>

                                <div class="hidden space-y-2" data-pairing-wrap>
                                    <div class="text-sm font-medium text-slate-800">Pairing Code</div>
                                    <div class="flex items-center gap-2">
                                        <code class="px-3 py-2 bg-slate-100 rounded-xl text-slate-800 text-base tracking-widest" data-pairing-code></code>
                                        <button type="button" class="px-3 h-10 rounded-xl bg-slate-800 text-white text-sm hover:bg-black" data-action="copy-pairing">
                                            Copiar
                                        </button>
                                    </div>
                                    <div class="text-xs text-slate-500">Digite este código no WhatsApp, se o provider devolver pareamento manual.</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="{{ $tab === 'tempos' ? 'space-y-6' : 'hidden' }}" data-tab-panel="tempos">
            <form method="POST" action="{{ route('master.tempo-tarefas.store') }}" class="space-y-4">
                @csrf
                @php
                    $tempoErrors = collect($errors->getMessages())
                        ->filter(fn ($messages, $field) => str_starts_with((string) $field, 'tempos'))
                        ->flatten();
                @endphp
                @if($tempoErrors->isNotEmpty())
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <div class="font-semibold mb-1">Não foi possível salvar alguns tempos</div>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($tempoErrors->take(4) as $mensagem)
                                <li>{{ $mensagem }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-800">Tempo padrão por serviço</h2>
                        <span class="text-xs text-slate-500">Duração: minutos ou HH:MM</span>
                    </div>
                    <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 text-xs text-slate-600">
                        Defina a duração da tarefa. Exemplo: <span class="font-semibold">90</span> (minutos) ou <span class="font-semibold">01:30</span> (1h30).
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($servicos as $servico)
                            @php
                                $isExcluido = in_array((int) $servico->id, $excluirServicoIds ?? [], true);
                                $tempoAtual = $tempos[$servico->id]->tempo_minutos ?? 0;
                                $tempoAtualHoras = str_pad((string) floor($tempoAtual / 60), 2, '0', STR_PAD_LEFT).':'.str_pad((string) ($tempoAtual % 60), 2, '0', STR_PAD_LEFT);
                                $tempoValor = old('tempos.'.$servico->id, (string) $tempoAtual);
                            @endphp
                            <div class="px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">{{ $servico->nome }}</div>
                                    @if($isExcluido)
                                        <div class="text-xs text-slate-500">Serviço não aplicável para SLA.</div>
                                    @else
                                        <div class="text-xs text-slate-500">Defina a duração máxima desta tarefa.</div>
                                    @endif
                                </div>
                                <div class="w-full md:w-44">
                                    <input type="text"
                                           inputmode="numeric"
                                           name="tempos[{{ $servico->id }}]"
                                           value="{{ $tempoValor }}"
                                           {{ $isExcluido ? 'disabled' : '' }}
                                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm {{ $isExcluido ? 'bg-slate-100 text-slate-400' : '' }}"
                                           data-tempo-input
                                           placeholder="Ex: 90 ou 01:30">
                                    @if(!$isExcluido)
                                        <div class="mt-1 text-[11px] text-slate-500" data-tempo-preview>
                                            Atual: {{ $tempoAtual }} min ({{ $tempoAtualHoras }})
                                        </div>
                                    @endif
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

        <div class="{{ $tab === 'painel' ? 'space-y-6' : 'hidden' }}" data-tab-panel="painel">
            <div class="bg-slate-50 rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
                <div>
                    <div class="text-xl font-semibold text-slate-900">Configurações do Painel Master</div>
                    <div class="text-sm text-slate-500">Personalize o que será exibido no seu dashboard</div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 grid place-items-center text-lg">📊</div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Resumo Geral</div>
                                <div class="h-px bg-indigo-100 mt-1"></div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="flex items-start justify-between gap-4 text-sm text-slate-700">
                                <span>
                                    <span class="font-medium">Mostrar resumo de faturamento</span>
                                    <span class="block text-xs text-slate-500">Mostra o faturamento total do período selecionado</span>
                                </span>
                                <span class="relative inline-flex items-center">
                                    <input type="checkbox" class="sr-only peer" data-dashboard-toggle="faturamento-global">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-indigo-600 transition"></span>
                                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                            <div class="h-px bg-slate-100"></div>
                            <label class="flex items-start justify-between gap-4 text-sm text-slate-700">
                                <span>
                                    <span class="font-medium">Mostrar resumo de serviços consumidos</span>
                                    <span class="block text-xs text-slate-500">Mostra o total de itens de serviços utilizados</span>
                                </span>
                                <span class="relative inline-flex items-center">
                                    <input type="checkbox" class="sr-only peer" data-dashboard-toggle="servicos-consumidos">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-indigo-600 transition"></span>
                                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 grid place-items-center text-lg">💰</div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Financeiro</div>
                                <div class="h-px bg-amber-100 mt-1"></div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="flex items-start justify-between gap-4 text-sm text-slate-700">
                                <span>
                                    <span class="font-medium">Faturamento pendente</span>
                                    <span class="block text-xs text-slate-500">Mostra o total em aberto no per&iacute;odo</span>
                                </span>
                                <span class="relative inline-flex items-center">
                                    <input type="checkbox" class="sr-only peer" data-dashboard-toggle="financeiro-pendente">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-indigo-600 transition"></span>
                                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                            <div class="h-px bg-slate-100"></div>
                            <label class="flex items-start justify-between gap-4 text-sm text-slate-700">
                                <span>
                                    <span class="font-medium">Faturamento recebido</span>
                                    <span class="block text-xs text-slate-500">Mostra o total recebido no per&iacute;odo</span>
                                </span>
                                <span class="relative inline-flex items-center">
                                    <input type="checkbox" class="sr-only peer" data-dashboard-toggle="financeiro-recebido">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-indigo-600 transition"></span>
                                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 grid place-items-center text-lg">⚙️</div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Indicadores Operacionais</div>
                                <div class="h-px bg-indigo-100 mt-1"></div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="flex items-start justify-between gap-4 text-sm text-slate-700">
                                <span>
                                    <span class="font-medium">Card de clientes ativos</span>
                                    <span class="block text-xs text-slate-500">Mostra o número de clientes em atendimento</span>
                                </span>
                                <span class="relative inline-flex items-center">
                                    <input type="checkbox" class="sr-only peer" data-dashboard-toggle="clientes-ativos">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-indigo-600 transition"></span>
                                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                            <div class="h-px bg-slate-100"></div>
                            <label class="flex items-start justify-between gap-4 text-sm text-slate-700">
                                <span>
                                    <span class="font-medium">Card de tempo médio</span>
                                    <span class="block text-xs text-slate-500">Mostra o tempo médio operacional</span>
                                </span>
                                <span class="relative inline-flex items-center">
                                    <input type="checkbox" class="sr-only peer" data-dashboard-toggle="tempo-medio">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-indigo-600 transition"></span>
                                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                            <div class="h-px bg-slate-100"></div>
                            <label class="flex items-start justify-between gap-4 text-sm text-slate-700">
                                <span>
                                    <span class="font-medium">Card de agendamentos do dia</span>
                                    <span class="block text-xs text-slate-500">Mostra o total de agendas abertas e fechadas hoje</span>
                                </span>
                                <span class="relative inline-flex items-center">
                                    <input type="checkbox" class="sr-only peer" data-dashboard-toggle="agendamentos-dia">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-indigo-600 transition"></span>
                                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 grid place-items-center text-lg">📄</div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Relatórios</div>
                                <div class="h-px bg-indigo-100 mt-1"></div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="flex items-start justify-between gap-4 text-sm text-slate-700">
                                <span>
                                    <span class="font-medium">Card de relatórios master</span>
                                    <span class="block text-xs text-slate-500">Atalho para relatórios</span>
                                </span>
                                <span class="relative inline-flex items-center">
                                    <input type="checkbox" class="sr-only peer" data-dashboard-toggle="relatorios-master">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-indigo-600 transition"></span>
                                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                            <div class="h-px bg-slate-100"></div>
                            <label class="flex items-start justify-between gap-4 text-sm text-slate-700">
                                <span>
                                    <span class="font-medium">Relatórios avançados</span>
                                    <span class="block text-xs text-slate-500">Bloco de métricas avançadas</span>
                                </span>
                                <span class="relative inline-flex items-center">
                                    <input type="checkbox" class="sr-only peer" data-dashboard-toggle="relatorios-avancados">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-indigo-600 transition"></span>
                                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                            <span class="text-xs text-slate-500" data-dashboard-save-status></span>
                            <button type="button"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700"
                                    data-dashboard-save>
                                <span class="text-base">💾</span>
                                Salvar configurações do painel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const whatsappTab = @json($tab === 'whatsapp');

                if (whatsappTab) {
                    const csrf = @json(csrf_token());
                    const routeTemplates = {
                        create: @json(route('master.email-caixas.whatsapp.instance', ['tipo' => '__TIPO__'])),
                        connect: @json(route('master.email-caixas.whatsapp.connect', ['tipo' => '__TIPO__'])),
                        status: @json(route('master.email-caixas.whatsapp.status', ['tipo' => '__TIPO__'])),
                        restart: @json(route('master.email-caixas.whatsapp.restart', ['tipo' => '__TIPO__'])),
                        logout: @json(route('master.email-caixas.whatsapp.logout', ['tipo' => '__TIPO__'])),
                    };

                    const formatNow = () => new Date().toLocaleString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const show = (el, display = 'block') => {
                        if (!el) return;
                        el.classList.remove('hidden');
                        el.style.display = display;
                    };

                    const hide = (el) => {
                        if (!el) return;
                        el.classList.add('hidden');
                        el.style.display = 'none';
                    };

                    document.querySelectorAll('[data-whatsapp-card]').forEach((card) => {
                        const tipo = card.getAttribute('data-whatsapp-card');
                        const elements = {
                            statusBadge: card.querySelector('[data-status-badge]'),
                            statusLabel: card.querySelector('[data-status-label]'),
                            instanceName: card.querySelector('[data-instance-name]'),
                            lastStatusAt: card.querySelector('[data-last-status-at]'),
                            stateText: card.querySelector('[data-state-text]'),
                            errorBox: card.querySelector('[data-error-box]'),
                            loadingRow: card.querySelector('[data-loading-row]'),
                            qrWrap: card.querySelector('[data-qr-wrap]'),
                            qrImg: card.querySelector('[data-qr-img]'),
                            pairingWrap: card.querySelector('[data-pairing-wrap]'),
                            pairingCode: card.querySelector('[data-pairing-code]'),
                            buttons: Array.from(card.querySelectorAll('[data-action]')),
                        };

                        let pollTimer = null;

                        const routeFor = (action) => routeTemplates[action].replace('__TIPO__', tipo);

                        const setError = (message = '') => {
                            if (!elements.errorBox) return;
                            if (message) {
                                elements.errorBox.textContent = message;
                                show(elements.errorBox);
                            } else {
                                elements.errorBox.textContent = '';
                                hide(elements.errorBox);
                            }
                        };

                        const setStatus = (state, label) => {
                            if (!elements.statusBadge || !elements.statusLabel) return;

                            const dot = elements.statusBadge.querySelector('span:first-child');
                            elements.statusBadge.className = 'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold';

                            if (state === 'open') {
                                dot.className = 'h-2.5 w-2.5 rounded-full bg-emerald-500';
                                elements.statusBadge.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
                                elements.statusLabel.textContent = label || 'ONLINE';
                            } else if (state === 'connecting' || state === 'qr' || state === 'pairing') {
                                dot.className = 'h-2.5 w-2.5 rounded-full bg-amber-400';
                                elements.statusBadge.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-700');
                                elements.statusLabel.textContent = label || 'CONNECTING';
                            } else {
                                dot.className = 'h-2.5 w-2.5 rounded-full bg-slate-300';
                                elements.statusBadge.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
                                elements.statusLabel.textContent = label || 'CLOSED';
                            }

                            if (elements.stateText) {
                                elements.stateText.textContent = (label || state || 'closed').toUpperCase();
                            }
                            if (elements.lastStatusAt) {
                                elements.lastStatusAt.textContent = formatNow();
                            }
                        };

                        const setBusy = (busy) => {
                            elements.buttons.forEach((button) => {
                                const locked = button.getAttribute('data-lock-disabled') === '1';
                                button.disabled = busy || locked;
                            });
                            if (busy) {
                                show(elements.loadingRow, 'flex');
                            } else {
                                hide(elements.loadingRow);
                            }
                        };

                        const requestJson = async (url, method = 'POST') => {
                            const response = await fetch(url, {
                                method,
                                headers: {
                                    'X-CSRF-TOKEN': csrf,
                                    'Accept': 'application/json',
                                },
                            });
                            const data = await response.json().catch(() => ({}));
                            if (!response.ok || data.ok === false) {
                                throw new Error(data.message || 'Falha ao processar a instância.');
                            }
                            return data;
                        };

                        const updateButtonsAfterCreate = () => {
                            const createButton = card.querySelector('[data-action="create"]');
                            if (createButton) {
                                createButton.disabled = true;
                                createButton.setAttribute('data-lock-disabled', '1');
                                createButton.classList.add('opacity-60', 'cursor-not-allowed');
                            }

                            ['connect', 'status', 'restart', 'logout'].forEach((action) => {
                                const button = card.querySelector(`[data-action="${action}"]`);
                                if (!button) return;
                                button.removeAttribute('data-lock-disabled');
                                button.disabled = false;
                                button.classList.remove('opacity-60', 'cursor-not-allowed');
                            });
                        };

                        const startPolling = () => {
                            if (pollTimer) clearInterval(pollTimer);

                            pollTimer = setInterval(async () => {
                                try {
                                    const data = await requestJson(routeFor('status'), 'GET');
                                    const state = data.state || 'closed';
                                    setStatus(state, state === 'open' ? 'ONLINE' : String(state).toUpperCase());

                                    if (state === 'open' && pollTimer) {
                                        clearInterval(pollTimer);
                                        pollTimer = null;
                                        hide(elements.qrWrap);
                                        hide(elements.pairingWrap);
                                    }
                                } catch (error) {
                                    setError(error.message || 'Não foi possível atualizar o status.');
                                }
                            }, 3000);
                        };

                        const handleAction = async (action) => {
                            setBusy(true);
                            setError('');

                            try {
                                if (action === 'connect') {
                                    hide(elements.qrWrap);
                                    hide(elements.pairingWrap);
                                }

                                const method = action === 'status' ? 'GET' : 'POST';
                                const data = await requestJson(routeFor(action), method);

                                if (action === 'create') {
                                    if (elements.instanceName) {
                                        elements.instanceName.textContent = `#${data.instance?.name || 'Instância criada'}`;
                                    }
                                    updateButtonsAfterCreate();
                                    setStatus(data.instance?.state || 'closed', String(data.instance?.state || 'closed').toUpperCase());
                                }

                                if (action === 'connect') {
                                    if (data.base64) {
                                        elements.qrImg.src = String(data.base64).startsWith('data:image')
                                            ? data.base64
                                            : `data:image/png;base64,${data.base64}`;
                                        show(elements.qrWrap);
                                        setStatus('qr', 'QR');
                                    }

                                    const code = data.pairingCode || data.code || '';
                                    if (code) {
                                        elements.pairingCode.textContent = code;
                                        show(elements.pairingWrap);
                                        setStatus('pairing', 'PAIRING');
                                    }

                                    startPolling();
                                }

                                if (action === 'status') {
                                    const state = data.state || 'closed';
                                    setStatus(state, state === 'open' ? 'ONLINE' : String(state).toUpperCase());
                                }

                                if (action === 'restart') {
                                    setStatus('connecting', 'RESTART');
                                    startPolling();
                                }

                                if (action === 'logout') {
                                    hide(elements.qrWrap);
                                    hide(elements.pairingWrap);
                                    setStatus('closed', 'CLOSED');
                                    if (pollTimer) {
                                        clearInterval(pollTimer);
                                        pollTimer = null;
                                    }
                                }
                            } catch (error) {
                                setError(error.message || 'Falha ao processar a instância.');
                            } finally {
                                setBusy(false);
                            }
                        };

                        elements.buttons.forEach((button) => {
                            const action = button.getAttribute('data-action');

                            if (action === 'copy-pairing') {
                                button.addEventListener('click', async () => {
                                    const code = elements.pairingCode?.textContent?.trim() || '';
                                    if (!code) return;
                                    try {
                                        await navigator.clipboard.writeText(code);
                                        button.textContent = 'Copiado!';
                                        setTimeout(() => {
                                            button.textContent = 'Copiar';
                                        }, 1200);
                                    } catch (error) {
                                        setError('Não foi possível copiar o código.');
                                    }
                                });
                                return;
                            }

                            button.addEventListener('click', async () => {
                                if (action === 'restart') {
                                    const canProceed = typeof window.swalConfirm === 'function'
                                        ? await window.swalConfirm({ title: `Reiniciar ${tipo}?`, text: 'Deseja reiniciar a conexão agora?' })
                                        : window.confirm('Deseja reiniciar a conexão agora?');
                                    if (!canProceed) return;
                                }

                                if (action === 'logout') {
                                    const canProceed = typeof window.swalConfirm === 'function'
                                        ? await window.swalConfirm({ title: `Fazer logout de ${tipo}?`, text: 'Será necessário reconectar depois.' })
                                        : window.confirm('Será necessário reconectar depois. Deseja continuar?');
                                    if (!canProceed) return;
                                }

                                await handleAction(action);
                            });
                        });

                        if (card.querySelector('[data-action="status"]')?.getAttribute('data-lock-disabled') !== '1') {
                            startPolling();
                        }
                    });
                }

                const parseTempo = (value) => {
                    const raw = (value || '').trim();
                    if (!raw) return 0;

                    if (/^\d+$/.test(raw)) {
                        return parseInt(raw, 10);
                    }

                    const match = raw.match(/^(\d{1,3}):([0-5]\d)$/);
                    if (!match) return null;

                    return (parseInt(match[1], 10) * 60) + parseInt(match[2], 10);
                };

                const formatHHMM = (minutes) => {
                    const h = Math.floor(minutes / 60);
                    const m = minutes % 60;
                    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                };

                document.querySelectorAll('[data-tempo-input]').forEach((input) => {
                    const preview = input.parentElement?.querySelector('[data-tempo-preview]');
                    if (!preview) return;

                    const render = () => {
                        const minutos = parseTempo(input.value);
                        if (minutos === null) {
                            preview.textContent = 'Formato inválido. Use 90 ou 01:30.';
                            preview.classList.remove('text-slate-500');
                            preview.classList.add('text-rose-600');
                            return;
                        }

                        if (minutos > 10080) {
                            preview.textContent = 'Máximo permitido: 10080 min (168:00).';
                            preview.classList.remove('text-slate-500');
                            preview.classList.add('text-rose-600');
                            return;
                        }

                        preview.textContent = minutos === 0
                            ? 'Tarefa sem SLA (0 min).'
                            : `${minutos} min (${formatHHMM(minutos)}).`;
                        preview.classList.remove('text-rose-600');
                        preview.classList.add('text-slate-500');
                    };

                    input.addEventListener('input', render);
                    render();
                });
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const saveBtn = document.querySelector('[data-dashboard-save]');
                if (!saveBtn) return;

                const toggles = Array.from(document.querySelectorAll('[data-dashboard-toggle]'));
                const statusEl = document.querySelector('[data-dashboard-save-status]');
                const preferencesUrl = '{{ route('master.dashboard-preferences.show') }}';
                const saveUrl = '{{ route('master.dashboard-preferences.update') }}';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                let currentState = {};

                const loadState = async () => {
                    try {
                        const response = await fetch(preferencesUrl, {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!response.ok) {
                            return {};
                        }
                        const payload = await response.json();
                        return payload.visibility || {};
                    } catch (e) {
                        return {};
                    }
                };

                const applyState = (state) => {
                    toggles.forEach((toggle) => {
                        const key = toggle.getAttribute('data-dashboard-toggle');
                        if (Object.prototype.hasOwnProperty.call(state, key)) {
                            toggle.checked = state[key] !== false;
                        }
                    });
                };

                loadState().then((state) => {
                    currentState = state || {};
                    applyState(currentState);
                });

                saveBtn.addEventListener('click', () => {
                    const next = { ...currentState };
                    toggles.forEach((toggle) => {
                        const key = toggle.getAttribute('data-dashboard-toggle');
                        next[key] = toggle.checked;
                    });
                    currentState = next;
                    fetch(saveUrl, {
                        method: 'PUT',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                        },
                        body: JSON.stringify({ visibility: next }),
                    }).catch(() => null);
                    if (statusEl) {
                        statusEl.textContent = 'Configurações salvas.';
                        setTimeout(() => {
                            statusEl.textContent = '';
                        }, 2000);
                    }
                });
            });
        </script>
    @endpush
@endsection
