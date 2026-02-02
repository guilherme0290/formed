@extends('layouts.master')
@section('title', 'Configuração de Caixas de Email')

@section('content')
    @php
        $caixaEmEdicaoId = (int) old('caixa_id');
        $caixaTesteId = (int) old('caixa_teste_id');
        $tab = request('tab', 'email');
    @endphp

    <div class="max-w-6xl mx-auto px-4 md:px-6 py-6 space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    @if ($tab === 'tempos')
                        Configuração de tempo das tarefas
                    @elseif ($tab === 'painel')
                        Configuração do painel
                    @else
                        Configuração de Caixas de E-mail
                    @endif
                </h1>
                <p class="text-slate-500 text-sm mt-1">
                    @if ($tab === 'tempos')
                        Defina o tempo padrão por serviço para o controle de SLA.
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
                <x-toggle-ativo
                    name="ativo"
                    :checked="old('ativo', '1') === '1'"
                    on-label="Ativo"
                    off-label="Inativo"
                    text-class="text-sm text-slate-700"
                />
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

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-800">Credenciais de Acesso (U - Usuario)</h2>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">E-mail de Login / Usuario SMTP</label>
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
                                    Autenticacao obrigatoria?
                                </label>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-3 pt-6">
                                <button type="submit" formaction="{{ route('master.email-caixas.testar') }}"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                                    Testar Conexao
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
                </div>
            </form>

            @php $totalCaixas = $caixas->count(); @endphp

            <div class="bg-white border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Email cadastrado</h2>
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
                                <form method="POST" action="{{ route('master.email-caixas.destroy', $caixa) }}" data-confirm="Excluir este email?">
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
                                        <label class="text-xs font-semibold text-slate-600">E-mail de Login / Usuario SMTP</label>
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
                    @empty
                        <div class="text-sm text-slate-600">Nenhuma caixa cadastrada.</div>
                    @endforelse
                </div>
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
