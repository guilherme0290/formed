@php
    $papeisList = $papeis ?? collect();
    $permissoesList = $permissoes ?? collect();

    $scopeMeta = [
        'master' => ['label' => 'Master', 'chip' => 'text-indigo-700 bg-indigo-50 border-indigo-100'],
        'comercial' => ['label' => 'Comercial', 'chip' => 'text-orange-700 bg-orange-50 border-orange-100'],
        'financeiro' => ['label' => 'Financeiro', 'chip' => 'text-sky-700 bg-sky-50 border-sky-100'],
        'operacional' => ['label' => 'Operacional', 'chip' => 'text-emerald-700 bg-emerald-50 border-emerald-100'],
        'cliente' => ['label' => 'Cliente', 'chip' => 'text-blue-700 bg-blue-50 border-blue-100'],
        'outros' => ['label' => 'Outros', 'chip' => 'text-slate-700 bg-slate-50 border-slate-100'],
    ];

    $roleMainScope = [
        'master' => null, // master visualiza todos os módulos
        'comercial' => 'comercial',
        'financeiro' => 'financeiro',
        'operacional' => 'operacional',
        'cliente' => 'cliente',
    ];

    $knownScopes = ['master', 'comercial', 'financeiro', 'operacional', 'cliente'];
    $actionLabels = [
        'view' => 'Leitura',
        'create' => 'Criar',
        'update' => 'Editar',
        'delete' => 'Excluir',
        'manage' => 'Gerenciar',
        'edit' => 'Editar',
        'toggle' => 'Alternar',
    ];
@endphp

<div class="bg-white rounded-2xl shadow p-5 space-y-5">
    <div class="space-y-1">
        <h2 class="text-xl font-semibold text-slate-900">Permissões por Perfil</h2>
        <p class="text-sm text-slate-600">Marque exatamente o que cada perfil pode fazer em cada módulo.</p>
    </div>

    <div class="rounded-2xl border border-blue-300 bg-gradient-to-r from-blue-50 via-indigo-50 to-blue-50 p-4 md:p-5 shadow-sm">
        <div class="text-sm font-semibold text-blue-900 mb-2">Guia rápido de permissões</div>
        <div class="text-xs text-slate-700 grid gap-2 md:grid-cols-3">
            <div><span class="font-semibold text-slate-900">Leitura:</span> permite ver telas/listas.</div>
            <div><span class="font-semibold text-slate-900">Criar/Editar/Excluir:</span> libera alteração de dados.</div>
            <div><span class="font-semibold text-slate-900">Master:</span> por padrão gerencia todos os módulos.</div>
        </div>
    </div>

    @php
        $defaultRoleId = $papeisList->first()?->id;
    @endphp

    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        @foreach($papeisList as $papelTab)
            @php
                $activeRole = (int) $papelTab->id === (int) $defaultRoleId;
            @endphp
            <button type="button"
                    data-role-tab-btn="{{ $papelTab->id }}"
                    class="px-3 py-1.5 rounded-lg border text-xs font-semibold {{ $activeRole ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
                {{ $papelTab->nome }}
            </button>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse($papeisList as $papel)
            @php
                $papelKey = mb_strtolower($papel->nome ?? '');
                $mainScope = $roleMainScope[$papelKey] ?? null;

                $grupos = $permissoesList
                    ->filter(function ($grupo, $escopo) use ($mainScope) {
                        if ($mainScope !== null) {
                            return (string) $escopo === $mainScope;
                        }
                        return true;
                    })
                    ->mapWithKeys(function ($grupo, $escopo) use ($knownScopes) {
                        $scopeKey = in_array((string) $escopo, $knownScopes, true) ? (string) $escopo : 'outros';
                        return [$scopeKey => $grupo];
                    });

                $scopeTabs = $grupos->keys()->values();
                $defaultScope = $scopeTabs->first() ?? 'outros';
                $formId = 'form-permissoes-' . $papel->id;
            @endphp

            <div data-role-panel="{{ $papel->id }}" class="rounded-2xl border border-blue-200 bg-blue-50/30 overflow-hidden {{ (int) $papel->id === (int) $defaultRoleId ? '' : 'hidden' }}">
                <form id="{{ $formId }}" method="POST" action="{{ route('master.papeis.permissoes.sync', $papel) }}" data-confirm="Salvar permissões para {{ $papel->nome }}?">
                    @csrf

                    <div class="px-4 py-3 bg-blue-50 border-b border-blue-200 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">{{ $papel->nome }}</div>
                            <div class="text-xs text-slate-500">{{ $papel->descricao ?: 'Sem descrição' }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-600 rounded-lg border border-slate-200 bg-white px-2 py-1">
                                Marcadas: <strong data-count-selected="{{ $papel->id }}">0</strong> / <span data-count-total="{{ $papel->id }}">0</span>
                            </span>
                            <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                                Salvar alterações
                            </button>
                        </div>
                    </div>

                    <div class="p-4 space-y-4">
                        @if($scopeTabs->count() > 1)
                            <div class="flex flex-wrap gap-2" data-scope-tabs="{{ $papel->id }}">
                                @foreach($scopeTabs as $scopeKey)
                                    @php
                                        $meta = $scopeMeta[$scopeKey] ?? $scopeMeta['outros'];
                                        $isActive = $scopeKey === $defaultScope;
                                    @endphp
                                    <button
                                        type="button"
                                        data-role-id="{{ $papel->id }}"
                                        data-scope-tab="{{ $scopeKey }}"
                                        class="px-3 py-1.5 rounded-lg border text-xs font-semibold {{ $isActive ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}"
                                    >
                                        {{ $meta['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @forelse($scopeTabs as $scopeKey)
                            @php
                                $grupo = $grupos->get($scopeKey, collect());
                                $meta = $scopeMeta[$scopeKey] ?? $scopeMeta['outros'];
                            @endphp

                            <div
                                data-role-id="{{ $papel->id }}"
                                data-scope-panel="{{ $scopeKey }}"
                                class="space-y-3 {{ $scopeKey === $defaultScope ? '' : 'hidden' }}"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-semibold uppercase tracking-wide px-2 py-1 rounded-lg border {{ $meta['chip'] }}">
                                        {{ $meta['label'] }}
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button type="button" class="text-[11px] font-semibold text-indigo-600 hover:underline"
                                                data-role-id="{{ $papel->id }}"
                                                data-select-module="{{ $scopeKey }}">
                                            Marcar módulo
                                        </button>
                                        <button type="button" class="text-[11px] font-semibold text-slate-500 hover:underline"
                                                data-role-id="{{ $papel->id }}"
                                                data-clear-module="{{ $scopeKey }}">
                                            Limpar módulo
                                        </button>
                                    </div>
                                </div>

                                @php
                                    $actionOrder = ['view' => 1, 'create' => 2, 'update' => 3, 'delete' => 4, 'manage' => 5, 'edit' => 6, 'toggle' => 7];
                                    $groupedByResource = $grupo->groupBy(function ($perm) {
                                        return \Illuminate\Support\Str::beforeLast($perm->chave, '.');
                                    });
                                @endphp

                                <div class="space-y-2">
                                    @foreach($groupedByResource as $resourceKey => $resourcePerms)
                                        @php
                                            $resourceName = \Illuminate\Support\Str::after((string) $resourceKey, $scopeKey . '.');
                                            $resourceLabel = trim((string) preg_replace('/\s+/', ' ', str_replace(['.', '_', '-'], ' ', $resourceName)));
                                            $resourceLabel = $resourceLabel !== '' ? \Illuminate\Support\Str::title($resourceLabel) : 'Recurso';
                                            $resourceLabelMap = [
                                                'cliente.dashboard' => 'Painel do Cliente',
                                                'cliente.funcionarios' => 'Funcionários',
                                                'cliente.servicos' => 'Serviços do Cliente',
                                            ];
                                            $resourceLabel = $resourceLabelMap[$resourceKey] ?? $resourceLabel;
                                            $resourcePerms = $resourcePerms->sortBy(function ($perm) use ($actionOrder) {
                                                $actionKey = strtolower((string) \Illuminate\Support\Str::afterLast($perm->chave, '.'));
                                                return $actionOrder[$actionKey] ?? 99;
                                            });
                                            $isClienteServicos = $scopeKey === 'cliente' && $resourceKey === 'cliente.servicos';
                                        @endphp

                                        <div class="rounded-xl border border-blue-200 bg-white px-3 py-2 hover:bg-blue-50">
                                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                                <div class="min-w-0">
                                                    <div class="text-sm font-semibold text-slate-800">{{ $resourceLabel }}</div>
                                                    <div class="text-xs text-slate-500 break-all">{{ $resourceKey }}</div>
                                                    @if($isClienteServicos)
                                                        <div class="text-xs text-slate-500 mt-0.5">Marque somente os serviços que o perfil pode solicitar no portal.</div>
                                                    @endif
                                                </div>

                                                <div class="flex flex-wrap items-center gap-3">
                                                    @foreach($resourcePerms as $perm)
                                                        @php
                                                            $actionKey = strtolower((string) \Illuminate\Support\Str::afterLast($perm->chave, '.'));
                                                            $actionLabel = $actionLabels[$actionKey] ?? strtoupper($actionKey);
                                                        @endphp
                                                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-700">
                                                            <input type="checkbox"
                                                                   name="permissoes[]"
                                                                   value="{{ $perm->id }}"
                                                                   data-role-id="{{ $papel->id }}"
                                                                   data-scope-check="{{ $scopeKey }}"
                                                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                                {{ $papel->permissoes->contains('id', $perm->id) ? 'checked' : '' }}>
                                                            <span class="px-2 py-0.5 rounded-full border {{ $isClienteServicos ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-slate-50 text-slate-700' }} font-semibold">
                                                                {{ $isClienteServicos ? ($perm->nome ?? $actionLabel) : $actionLabel }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhuma permissão cadastrada para este perfil.</p>
                        @endforelse
                    </div>
                </form>
            </div>
        @empty
            <div class="text-sm text-slate-500">Cadastre perfis antes de configurar permissões.</div>
        @endforelse
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const forms = document.querySelectorAll('form[id^="form-permissoes-"]');
        const roleTabs = document.querySelectorAll('[data-role-tab-btn]');
        const rolePanels = document.querySelectorAll('[data-role-panel]');

        function updateCounters(roleId) {
            const checks = document.querySelectorAll(`input[name="permissoes[]"][data-role-id="${roleId}"]`);
            const selected = Array.from(checks).filter((c) => c.checked).length;
            const total = checks.length;
            const selectedEl = document.querySelector(`[data-count-selected="${roleId}"]`);
            const totalEl = document.querySelector(`[data-count-total="${roleId}"]`);
            if (selectedEl) selectedEl.textContent = String(selected);
            if (totalEl) totalEl.textContent = String(total);
        }

        function activateRole(roleId) {
            rolePanels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.getAttribute('data-role-panel') !== roleId);
            });

            roleTabs.forEach((tab) => {
                const active = tab.getAttribute('data-role-tab-btn') === roleId;
                tab.classList.toggle('bg-slate-900', active);
                tab.classList.toggle('text-white', active);
                tab.classList.toggle('border-slate-900', active);
                tab.classList.toggle('bg-white', !active);
                tab.classList.toggle('text-slate-700', !active);
                tab.classList.toggle('border-slate-200', !active);
            });
        }

        function activateScope(roleId, scope) {
            document.querySelectorAll(`[data-role-id="${roleId}"][data-scope-panel]`).forEach((panel) => {
                panel.classList.toggle('hidden', panel.getAttribute('data-scope-panel') !== scope);
            });

            document.querySelectorAll(`[data-role-id="${roleId}"][data-scope-tab]`).forEach((btn) => {
                const active = btn.getAttribute('data-scope-tab') === scope;
                btn.classList.toggle('bg-indigo-600', active);
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('border-indigo-600', active);
                btn.classList.toggle('bg-white', !active);
                btn.classList.toggle('text-slate-700', !active);
                btn.classList.toggle('border-slate-200', !active);
            });
        }

        document.querySelectorAll('[data-scope-tab]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const roleId = btn.getAttribute('data-role-id');
                const scope = btn.getAttribute('data-scope-tab');
                activateScope(roleId, scope);
            });
        });

        roleTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                activateRole(tab.getAttribute('data-role-tab-btn'));
            });
        });

        document.querySelectorAll('[data-select-module]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const roleId = btn.getAttribute('data-role-id');
                const scope = btn.getAttribute('data-select-module');
                document.querySelectorAll(`input[name="permissoes[]"][data-role-id="${roleId}"][data-scope-check="${scope}"]`).forEach((chk) => {
                    chk.checked = true;
                });
                updateCounters(roleId);
            });
        });

        document.querySelectorAll('[data-clear-module]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const roleId = btn.getAttribute('data-role-id');
                const scope = btn.getAttribute('data-clear-module');
                document.querySelectorAll(`input[name="permissoes[]"][data-role-id="${roleId}"][data-scope-check="${scope}"]`).forEach((chk) => {
                    chk.checked = false;
                });
                updateCounters(roleId);
            });
        });

        forms.forEach((form) => {
            const roleId = (form.id || '').replace('form-permissoes-', '');
            if (!roleId) return;
            form.querySelectorAll('input[name="permissoes[]"]').forEach((chk) => {
                chk.addEventListener('change', () => updateCounters(roleId));
            });
            updateCounters(roleId);
        });

        if (roleTabs.length) {
            activateRole(roleTabs[0].getAttribute('data-role-tab-btn'));
        }
    });
</script>
