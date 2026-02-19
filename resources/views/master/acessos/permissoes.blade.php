@php
    $papeisList = ($papeis ?? collect())->values();
    $usuariosPermissoesList = ($usuariosPermissoes ?? collect())->values();

    $papeisEditaveis = $papeisList->filter(function ($papel) {
        return mb_strtolower((string) ($papel->nome ?? '')) !== 'cliente';
    })->values();

    $usuariosEditaveis = $usuariosPermissoesList->filter(function ($usuario) {
        return mb_strtolower((string) optional($usuario->papel)->nome) !== 'cliente';
    })->values();

    $permissoesList = ($permissoes ?? collect())->flatten(1)->sortBy('chave')->values();
    $defaultRoleId = (int) ($papeisEditaveis->first()->id ?? 0);
    $defaultUserId = (int) ($usuariosEditaveis->first()->id ?? 0);
    $defaultScope = 'papel';

    $roleScopeMap = [
        'master' => null,
        'comercial' => 'comercial',
        'operacional' => 'operacional',
        'financeiro' => 'financeiro',
        'cliente' => 'cliente',
    ];

    $actionOrder = ['view', 'create', 'update', 'delete'];
    $actionLabels = [
        'view' => 'VER',
        'create' => 'CRIAR',
        'update' => 'EDITAR',
        'delete' => 'DELETAR',
    ];

    $resourceLabelOverrides = [
        'operacional.apr' => 'APR',
        'operacional.aso' => 'ASO',
        'operacional.ltcat' => 'LTCAT',
        'operacional.ltip' => 'LTIP',
        'operacional.pae' => 'PAE',
        'operacional.pcmso' => 'PCMSO',
        'operacional.pgr' => 'PGR',
        'operacional.treinamentos' => 'Treinamentos',
        'operacional.tarefas' => 'Tarefas',
        'operacional.dashboard' => 'Dashboard',
        'operacional.anexos' => 'Anexos',
    ];

    $resourcePriorityByScope = [
        'operacional' => [
            'operacional.dashboard' => 0,
            'operacional.aso' => 10,
            'operacional.pgr' => 20,
            'operacional.pcmso' => 30,
            'operacional.ltcat' => 40,
            'operacional.ltip' => 50,
            'operacional.apr' => 60,
            'operacional.pae' => 70,
            'operacional.treinamentos' => 80,
            'operacional.tarefas' => 90,
            'operacional.anexos' => 100,
        ],
    ];

    $operacionalServiceKeys = [
        'operacional.aso',
        'operacional.pgr',
        'operacional.pcmso',
        'operacional.ltcat',
        'operacional.ltip',
        'operacional.apr',
        'operacional.pae',
        'operacional.treinamentos',
    ];

    $resourceOrderResolver = function (string $resourceKey, ?string $scope) use ($resourcePriorityByScope): string {
        $scopeKey = $scope ?? (string) \Illuminate\Support\Str::before($resourceKey, '.');
        $priority = $resourcePriorityByScope[$scopeKey][$resourceKey] ?? 999;
        return sprintf('%04d_%s', $priority, $resourceKey);
    };

    $resourceLabelResolver = function (string $resourceKey) use ($resourceLabelOverrides): string {
        if (isset($resourceLabelOverrides[$resourceKey])) {
            return $resourceLabelOverrides[$resourceKey];
        }

        $resourceName = $resourceKey;
        $scopeName = (string) \Illuminate\Support\Str::before($resourceKey, '.');
        if ($scopeName !== '' && str_starts_with($resourceKey, $scopeName . '.')) {
            $resourceName = (string) \Illuminate\Support\Str::after($resourceKey, $scopeName . '.');
        }
        return \Illuminate\Support\Str::title(str_replace(['.', '_', '-'], ' ', $resourceName));
    };
@endphp

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 px-5 py-4 text-white">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold">Permissões</h2>
                <p class="text-xs text-slate-200">Defina os acessos por papel ou por usuário.</p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="rounded-full bg-white/10 px-3 py-1">Papeis: {{ $papeisEditaveis->count() }}</span>
                <span class="rounded-full bg-white/10 px-3 py-1">Usuarios: {{ $usuariosEditaveis->count() }}</span>
            </div>
        </div>
    </div>

    <div class="p-5 space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex flex-wrap gap-2 rounded-xl bg-slate-100 p-1">
                <button
                    type="button"
                    data-scope-btn="papel"
                    class="px-4 py-2 rounded-lg border text-xs font-semibold bg-slate-900 text-white border-slate-900"
                >
                    Por Perfil
                </button>
                <button
                    type="button"
                    data-scope-btn="usuario"
                    class="px-4 py-2 rounded-lg border text-xs font-semibold bg-white text-slate-700 border-slate-200 hover:bg-slate-50"
                >
                    Por Usuário
                </button>
            </div>
        </div>

    <div data-scope-panel="papel" class="{{ $defaultScope === 'papel' ? '' : 'hidden' }}">
        @if($papeisEditaveis->isNotEmpty())
            <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3 mb-3">
                @foreach($papeisEditaveis as $papel)
                    @php $isActive = (int) $papel->id === $defaultRoleId; @endphp
                    <button
                        type="button"
                        data-role-tab-btn="{{ $papel->id }}"
                        class="px-4 py-2 rounded-xl border text-xs font-semibold {{ $isActive ? 'bg-slate-900 text-white border-slate-900 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}"
                    >
                        {{ $papel->nome }}
                    </button>
                @endforeach
            </div>

            @foreach($papeisEditaveis as $papel)
                @php
                    $panelActive = (int) $papel->id === $defaultRoleId;
                    $papelKey = mb_strtolower((string) ($papel->nome ?? ''));
                    $scope = $roleScopeMap[$papelKey] ?? null;
                    $permissoesDoPapel = $scope
                        ? $permissoesList->where('escopo', $scope)->values()
                        : $permissoesList;

                    $groupedResources = $permissoesDoPapel
                        ->groupBy(function ($perm) {
                            return \Illuminate\Support\Str::beforeLast($perm->chave, '.');
                        })
                        ->sortBy(fn ($resourcePerms, $resourceKey) => $resourceOrderResolver((string) $resourceKey, $scope));

                    $operacionalServicePermIdsByAction = collect($actionOrder)->mapWithKeys(fn ($action) => [$action => []])->all();
                    if ($scope === 'operacional') {
                        foreach ($operacionalServiceKeys as $serviceKey) {
                            $servicePerms = $groupedResources->get($serviceKey, collect());
                            foreach ($servicePerms as $rp) {
                                $action = strtolower((string) \Illuminate\Support\Str::afterLast($rp->chave, '.'));
                                if (array_key_exists($action, $operacionalServicePermIdsByAction)) {
                                    $operacionalServicePermIdsByAction[$action][] = (int) $rp->id;
                                }
                            }
                        }
                    }
                @endphp

                <div data-role-panel="{{ $papel->id }}" class="{{ $panelActive ? '' : 'hidden' }}">
                    <form method="POST" action="{{ route('master.papeis.permissoes.sync', $papel) }}" class="space-y-3">
                        @csrf

                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ $papel->nome }}</div>
                                <div class="text-xs text-slate-500">{{ $papel->descricao ?: 'Sem descricao' }}</div>
                            </div>
                            <button class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm">
                                Salvar
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold min-w-[320px]">Recurso</th>
                                    @foreach($actionOrder as $actionCol)
                                        <th class="px-3 py-3 text-center font-semibold min-w-[90px]">{{ $actionLabels[$actionCol] ?? strtoupper($actionCol) }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                @if($scope === 'operacional')
                                    <tr class="hover:bg-slate-50/70 permissao-row odd:bg-white even:bg-slate-50/40" data-search="servicos operacionais operacional">
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium text-slate-800">Servicos Operacionais</div>
                                            <div class="text-xs text-slate-500">operacional.servicos</div>
                                        </td>
                                        @foreach($actionOrder as $actionCol)
                                            @php
                                                $ids = $operacionalServicePermIdsByAction[$actionCol] ?? [];
                                                $masterChecked = !empty($ids)
                                                    && collect($ids)->every(fn ($id) => $papel->permissoes->contains('id', $id));
                                                $groupName = 'operacional-servicos-papel-'.$papel->id.'-'.$actionCol;
                                            @endphp
                                            <td class="px-3 py-3 text-center align-middle">
                                                @if(!empty($ids))
                                                    @foreach($ids as $permId)
                                                        <input
                                                            type="checkbox"
                                                            name="permissoes[]"
                                                            value="{{ $permId }}"
                                                            class="hidden permissao-group-hidden"
                                                            data-group="{{ $groupName }}"
                                                            @checked($papel->permissoes->contains('id', $permId))
                                                        >
                                                    @endforeach
                                                    <label class="inline-flex cursor-pointer items-center" title="Aplicar para todos os servicos operacionais ({{ strtoupper($actionCol) }})">
                                                        <input
                                                            type="checkbox"
                                                            class="sr-only permissao-group-master"
                                                            data-group="{{ $groupName }}"
                                                            @checked($masterChecked)
                                                        >
                                                        <span class="h-5 w-10 rounded-full bg-slate-300 transition relative toggle-track">
                                                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition toggle-dot"></span>
                                                        </span>
                                                    </label>
                                                @else
                                                    <span class="text-slate-300">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                                @forelse($groupedResources as $resourceKey => $resourcePerms)
                                    @if($scope === 'operacional' && in_array((string) $resourceKey, $operacionalServiceKeys, true))
                                        @continue
                                    @endif
                                    @php
                                        $resourcePermsByAction = [];
                                        foreach ($resourcePerms as $rp) {
                                            $action = strtolower((string) \Illuminate\Support\Str::afterLast($rp->chave, '.'));
                                            $resourcePermsByAction[$action] = $rp;
                                        }
                                        $resourceLabel = $resourceLabelResolver($resourceKey);
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 permissao-row odd:bg-white even:bg-slate-50/40" data-search="{{ mb_strtolower($resourceLabel.' '.$resourceKey) }}">
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium text-slate-800">{{ $resourceLabel }}</div>
                                            <div class="text-xs text-slate-500">{{ $resourceKey }}</div>
                                        </td>
                                        @foreach($actionOrder as $actionCol)
                                            @php
                                                $perm = $resourcePermsByAction[$actionCol] ?? null;
                                                $checked = $perm ? $papel->permissoes->contains('id', $perm->id) : false;
                                            @endphp
                                            <td class="px-3 py-3 text-center align-middle">
                                                @if($perm)
                                                    <label class="inline-flex cursor-pointer items-center" title="{{ $perm->nome }}">
                                                        <input
                                                            type="checkbox"
                                                            name="permissoes[]"
                                                            value="{{ $perm->id }}"
                                                            class="sr-only permissao-toggle"
                                                            @checked($checked)
                                                        >
                                                        <span class="h-5 w-10 rounded-full bg-slate-300 transition relative toggle-track">
                                                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition toggle-dot"></span>
                                                        </span>
                                                    </label>
                                                @else
                                                    <span class="text-slate-300">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 1 + count($actionOrder) }}" class="px-4 py-6 text-center text-slate-500">
                                            Nenhuma permissao encontrada para este papel.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            @endforeach
        @else
            <div class="text-sm text-slate-500">Nao ha papeis editaveis.</div>
        @endif
    </div>

    <div data-scope-panel="usuario" class="hidden space-y-3">
        @if($usuariosEditaveis->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 flex flex-wrap items-center gap-3">
                <label for="permissoes-usuario-select" class="text-xs font-semibold text-slate-700">Usuario</label>
                <select
                    id="permissoes-usuario-select"
                    class="min-w-[260px] rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400"
                >
                    @foreach($usuariosEditaveis as $usuario)
                        <option value="{{ $usuario->id }}" @selected((int) $usuario->id === $defaultUserId)>
                            {{ $usuario->name }} ({{ optional($usuario->papel)->nome ?? 'Sem papel' }})
                        </option>
                    @endforeach
                </select>
                <span class="text-xs text-slate-500">Configuração individual do usuário selecionado (substitui as permissões do papel quando houver seleção).</span>
            </div>

            @foreach($usuariosEditaveis as $usuario)
                @php
                    $panelActive = (int) $usuario->id === $defaultUserId;
                    $papelNome = mb_strtolower((string) optional($usuario->papel)->nome);
                    $scope = $roleScopeMap[$papelNome] ?? null;
                    $permissoesDoUsuario = $scope
                        ? $permissoesList->where('escopo', $scope)->values()
                        : $permissoesList;

                    $groupedResources = $permissoesDoUsuario
                        ->groupBy(function ($perm) {
                            return \Illuminate\Support\Str::beforeLast($perm->chave, '.');
                        })
                        ->sortBy(fn ($resourcePerms, $resourceKey) => $resourceOrderResolver((string) $resourceKey, $scope));

                    $diretasIds = $usuario->permissoesDiretas->pluck('id')->map(fn ($id) => (int) $id)->all();
                    $operacionalServicePermIdsByAction = collect($actionOrder)->mapWithKeys(fn ($action) => [$action => []])->all();
                    if ($scope === 'operacional') {
                        foreach ($operacionalServiceKeys as $serviceKey) {
                            $servicePerms = $groupedResources->get($serviceKey, collect());
                            foreach ($servicePerms as $rp) {
                                $action = strtolower((string) \Illuminate\Support\Str::afterLast($rp->chave, '.'));
                                if (array_key_exists($action, $operacionalServicePermIdsByAction)) {
                                    $operacionalServicePermIdsByAction[$action][] = (int) $rp->id;
                                }
                            }
                        }
                    }
                @endphp

                <div data-user-panel="{{ $usuario->id }}" class="{{ $panelActive ? '' : 'hidden' }}">
                    <form method="POST" action="{{ route('master.usuarios.permissoes.sync', $usuario) }}" class="space-y-3">
                        @csrf

                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ $usuario->name }}</div>
                                <div class="text-xs text-slate-500">{{ $usuario->email }} - {{ optional($usuario->papel)->nome ?? 'Sem papel' }}</div>
                            </div>
                            <button class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shadow-sm">
                                Salvar
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold min-w-[320px]">Recurso</th>
                                    @foreach($actionOrder as $actionCol)
                                        <th class="px-3 py-3 text-center font-semibold min-w-[90px]">{{ $actionLabels[$actionCol] ?? strtoupper($actionCol) }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                @if($scope === 'operacional')
                                    <tr class="hover:bg-slate-50/70 permissao-row odd:bg-white even:bg-slate-50/40" data-search="servicos operacionais operacional">
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium text-slate-800">Servicos Operacionais</div>
                                            <div class="text-xs text-slate-500">operacional.servicos</div>
                                        </td>
                                        @foreach($actionOrder as $actionCol)
                                            @php
                                                $ids = $operacionalServicePermIdsByAction[$actionCol] ?? [];
                                                $masterChecked = !empty($ids)
                                                    && collect($ids)->every(fn ($id) => in_array((int) $id, $diretasIds, true));
                                                $groupName = 'operacional-servicos-usuario-'.$usuario->id.'-'.$actionCol;
                                            @endphp
                                            <td class="px-3 py-3 text-center align-middle">
                                                @if(!empty($ids))
                                                    @foreach($ids as $permId)
                                                        <input
                                                            type="checkbox"
                                                            name="permissoes[]"
                                                            value="{{ $permId }}"
                                                            class="hidden permissao-group-hidden"
                                                            data-group="{{ $groupName }}"
                                                            @checked(in_array((int) $permId, $diretasIds, true))
                                                        >
                                                    @endforeach
                                                    <label class="inline-flex cursor-pointer items-center" title="Aplicar para todos os servicos operacionais ({{ strtoupper($actionCol) }})">
                                                        <input
                                                            type="checkbox"
                                                            class="sr-only permissao-group-master"
                                                            data-group="{{ $groupName }}"
                                                            @checked($masterChecked)
                                                        >
                                                        <span class="h-5 w-10 rounded-full bg-slate-300 transition relative toggle-track">
                                                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition toggle-dot"></span>
                                                        </span>
                                                    </label>
                                                @else
                                                    <span class="text-slate-300">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                                @forelse($groupedResources as $resourceKey => $resourcePerms)
                                    @if($scope === 'operacional' && in_array((string) $resourceKey, $operacionalServiceKeys, true))
                                        @continue
                                    @endif
                                    @php
                                        $resourcePermsByAction = [];
                                        foreach ($resourcePerms as $rp) {
                                            $action = strtolower((string) \Illuminate\Support\Str::afterLast($rp->chave, '.'));
                                            $resourcePermsByAction[$action] = $rp;
                                        }
                                        $resourceLabel = $resourceLabelResolver($resourceKey);
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 permissao-row odd:bg-white even:bg-slate-50/40" data-search="{{ mb_strtolower($resourceLabel.' '.$resourceKey) }}">
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium text-slate-800">{{ $resourceLabel }}</div>
                                            <div class="text-xs text-slate-500">{{ $resourceKey }}</div>
                                        </td>
                                        @foreach($actionOrder as $actionCol)
                                            @php
                                                $perm = $resourcePermsByAction[$actionCol] ?? null;
                                                $checked = $perm ? in_array((int) $perm->id, $diretasIds, true) : false;
                                            @endphp
                                            <td class="px-3 py-3 text-center align-middle">
                                                @if($perm)
                                                    <label class="inline-flex cursor-pointer items-center" title="{{ $perm->nome }}">
                                                        <input
                                                            type="checkbox"
                                                            name="permissoes[]"
                                                            value="{{ $perm->id }}"
                                                            class="sr-only permissao-toggle"
                                                            @checked($checked)
                                                        >
                                                        <span class="h-5 w-10 rounded-full bg-slate-300 transition relative toggle-track">
                                                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition toggle-dot"></span>
                                                        </span>
                                                    </label>
                                                @else
                                                    <span class="text-slate-300">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 1 + count($actionOrder) }}" class="px-4 py-6 text-center text-slate-500">
                                            Nenhuma permissao encontrada para este usuario.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            @endforeach
        @else
            <div class="text-sm text-slate-500">Nao ha usuarios elegiveis para configuracao por usuario.</div>
        @endif
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const scopeButtons = Array.from(document.querySelectorAll('[data-scope-btn]'));
    const scopePanels = Array.from(document.querySelectorAll('[data-scope-panel]'));
    const roleTabs = Array.from(document.querySelectorAll('[data-role-tab-btn]'));
    const rolePanels = Array.from(document.querySelectorAll('[data-role-panel]'));
    const userSelect = document.getElementById('permissoes-usuario-select');
    const userPanels = Array.from(document.querySelectorAll('[data-user-panel]'));

    function syncToggleVisual(input) {
        const label = input.closest('label');
        if (!label) return;
        const track = label.querySelector('.toggle-track');
        const dot = label.querySelector('.toggle-dot');
        if (!track || !dot) return;

        if (input.checked) {
            track.classList.remove('bg-slate-300');
            track.classList.add('bg-sky-500');
            dot.style.transform = 'translateX(20px)';
        } else {
            track.classList.remove('bg-sky-500');
            track.classList.add('bg-slate-300');
            dot.style.transform = 'translateX(0)';
        }
    }

    function activeScope() {
        const active = scopeButtons.find((btn) => btn.classList.contains('bg-slate-900'));
        return active ? active.getAttribute('data-scope-btn') : 'papel';
    }

    function refreshVisibleRows() {
        const scope = activeScope();

        if (scope === 'papel') {
            rolePanels.forEach((panel) => {
                const isActive = !panel.classList.contains('hidden');
                panel.querySelectorAll('.permissao-row').forEach((row) => {
                    row.classList.toggle('hidden', !isActive);
                });
            });
            return;
        }

        userPanels.forEach((panel) => {
            const isActive = !panel.classList.contains('hidden');
            panel.querySelectorAll('.permissao-row').forEach((row) => {
                row.classList.toggle('hidden', !isActive);
            });
        });
    }

    function setScope(scope) {
        scopePanels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.getAttribute('data-scope-panel') !== scope);
        });

        scopeButtons.forEach((btn) => {
            const active = btn.getAttribute('data-scope-btn') === scope;
            btn.classList.toggle('bg-slate-900', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('border-slate-900', active);
            btn.classList.toggle('bg-white', !active);
            btn.classList.toggle('text-slate-700', !active);
            btn.classList.toggle('border-slate-200', !active);
        });

        refreshVisibleRows();
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

        refreshVisibleRows();
    }

    function activateUser(userId) {
        userPanels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.getAttribute('data-user-panel') !== userId);
        });
        refreshVisibleRows();
    }

    document.querySelectorAll('.permissao-toggle').forEach((toggle) => {
        syncToggleVisual(toggle);
        toggle.addEventListener('change', function () {
            syncToggleVisual(toggle);
        });
    });

    document.querySelectorAll('.permissao-group-master').forEach((masterToggle) => {
        syncToggleVisual(masterToggle);
        masterToggle.addEventListener('change', function () {
            syncToggleVisual(masterToggle);
            const group = masterToggle.getAttribute('data-group');
            if (!group) return;
            document.querySelectorAll('.permissao-group-hidden[data-group=\"' + group + '\"]').forEach((input) => {
                input.checked = masterToggle.checked;
            });
        });
    });

    scopeButtons.forEach((btn) => {
        btn.addEventListener('click', function () {
            setScope(btn.getAttribute('data-scope-btn'));
        });
    });

    roleTabs.forEach((tab) => {
        tab.addEventListener('click', function () {
            activateRole(tab.getAttribute('data-role-tab-btn'));
        });
    });

    if (userSelect) {
        userSelect.addEventListener('change', function () {
            activateUser(userSelect.value);
        });
    }

    if (roleTabs.length) {
        activateRole(roleTabs[0].getAttribute('data-role-tab-btn'));
    }

    if (userSelect) {
        activateUser(userSelect.value);
    }

    setScope('{{ $defaultScope }}');
});
</script>
