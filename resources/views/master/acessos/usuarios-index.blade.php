{{-- USUÁRIOS (visual moderno) --}}
<div x-data="{
        open: false,
        editing: false,
        resetOpen: false,
        formAction: '{{ route('master.usuarios.store') }}',
        formMethod: 'POST',
        resetAction: '',
        resetNome: '',
        form: {
            name: '',
            email: '',
            telefone: '',
            papel_id: '',
            ativo: true,
        },
        openCreate() {
            this.editing = false;
            this.formAction = '{{ route('master.usuarios.store') }}';
            this.formMethod = 'POST';
            this.form = { name: '', email: '', telefone: '', papel_id: '', ativo: true };
            this.open = true;
        },
        openEdit(payload) {
            this.editing = true;
            this.formAction = payload.action;
            this.formMethod = 'PATCH';
            this.form = {
                name: payload.name || '',
                email: payload.email || '',
                telefone: payload.telefone || '',
                papel_id: payload.papel_id || '',
                ativo: payload.ativo === true || payload.ativo === 1 || payload.ativo === '1',
            };
            this.open = true;
        },
        openReset(payload) {
            this.resetAction = payload.action;
            this.resetNome = payload.name || '';
            this.resetOpen = true;
        },
    }" class="rounded-3xl border border-slate-200 bg-gradient-to-b from-white to-slate-50/70 p-4 shadow-sm md:p-6">

    @php
        $usuariosCollection = $usuarios->getCollection();
        $ativosCount = $usuariosCollection->where('ativo', true)->count();
        $inativosCount = $usuariosCollection->where('ativo', false)->count();
    @endphp

    {{-- Cabeçalho --}}
    <div class="mb-5 rounded-2xl border border-slate-200/80 bg-white/90 p-4 md:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1">
                <div class="text-xl font-semibold text-slate-900">Usuários</div>
                <p class="text-xs text-slate-500">Gerencie contas, perfis e status com segurança.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 font-semibold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>Ativos: {{ $ativosCount }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-100 px-3 py-1.5 font-semibold text-slate-700">
                    <span class="h-2 w-2 rounded-full bg-slate-500"></span>Inativos: {{ $inativosCount }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 font-semibold text-indigo-700">
                    Página: {{ $usuariosCollection->count() }}
                </span>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('master.acessos') }}" class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-12" id="usuarios-filter-form">
            <input type="hidden" name="tab" value="usuarios">
            <div class="relative lg:col-span-4">
                <input name="q" id="usuarios-autocomplete-input" value="{{ request('q') }}"
                       autocomplete="off"
                       placeholder="Buscar por nome ou e-mail..."
                       class="w-full rounded-xl border-slate-200 bg-slate-50/80 px-3 py-2 text-sm focus:bg-white focus:border-indigo-400 focus:ring-indigo-400">
                <div id="usuarios-autocomplete-list"
                     class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg"></div>
            </div>
            <select name="papel_id" class="w-full rounded-xl border-slate-200 bg-slate-50/80 px-3 py-2 pr-10 text-sm lg:col-span-2">
                <option value="">Todos os perfis</option>
                @foreach($papeis as $p)
                    @continue(in_array(strtolower($p->nome), ['parceiros'], true))
                    <option value="{{ $p->id }}" @selected(request('papel_id') == $p->id)>{{ $p->nome }}</option>
                @endforeach
            </select>
            <select name="status" class="w-full rounded-xl border-slate-200 bg-slate-50/80 px-3 py-2 pr-10 text-sm lg:col-span-2">
                <option value="">Todos</option>
                <option value="ativos" @selected(request('status') === 'ativos')>Ativos</option>
                <option value="inativos" @selected(request('status') === 'inativos')>Inativos</option>
            </select>
            <button class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 lg:col-span-1">Filtrar</button>
            <button type="button" @click="openCreate()"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 lg:col-span-2">
                + Novo Usuário
            </button>
            <a href="{{ route('master.acessos', ['tab' => 'usuarios']) }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 lg:col-span-1">
                Limpar
            </a>
        </form>
    </div>

    {{-- Tabela --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="px-4 py-3 text-left font-semibold">Nome</th>
                <th class="px-4 py-3 text-left font-semibold">E-mail</th>
                <th class="px-4 py-3 text-left font-semibold">Perfil</th>
                <th class="px-4 py-3 text-left font-semibold">Status</th>
                <th class="px-4 py-3 text-left font-semibold">&Uacute;ltimo acesso</th>
                <th class="w-44 px-4 py-3 text-right font-semibold">A&ccedil;&otilde;es</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($usuarios as $u)
                @php
                    $emailExibicao = $u->email ?: ($u->cliente->email ?? null);
                @endphp
                <tr class="transition hover:bg-slate-50/70">
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $u->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $emailExibicao ?: '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            {{ optional($u->papel)->nome ?? '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $u->ativo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                            {{ $u->ativo ? 'ativo' : 'inativo' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $u->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>

                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-1.5">
                            <button type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border transition text-blue-700 bg-blue-50 border-blue-200 hover:bg-blue-100"
                                    title="Editar"
                                    x-on:click="openEdit({
                                        action: '{{ route('master.usuarios.update', $u) }}',
                                        name: @js($u->name),
                                        email: @js($u->email),
                                        telefone: @js($u->telefone),
                                        papel_id: {{ $u->papel_id ?? 'null' }},
                                        ativo: {{ $u->ativo ? 'true' : 'false' }},
                                    })">
                                <i class="fa-regular fa-pen-to-square text-sm"></i>
                            </button>

                            <button type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border transition text-indigo-700 bg-indigo-50 border-indigo-200 hover:bg-indigo-100"
                                    title="Criar acesso"
                                    x-on:click="openReset({
                                        action: '{{ route('master.usuarios.password', $u) }}',
                                        name: @js($u->name),
                                    })">
                                <i class="fa-solid fa-key text-sm"></i>
                            </button>

                            <form method="POST" action="{{ route('master.usuarios.destroy', $u) }}" class="js-delete-user">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border transition text-rose-700 bg-rose-50 border-rose-200 hover:bg-rose-100"
                                        title="Excluir usuário"
                                        aria-label="Excluir usuário">
                                    <i class="fa-regular fa-trash-can text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-slate-500">Nenhum usuário cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    <div class="mt-4">
        {{ $usuarios->links() }}
    </div>

    {{-- Modal novo usuário --}}
    <div x-cloak x-show="open" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
         @keydown.escape.window="open=false">
        <div class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900" x-text="editing ? 'Editar usuário' : 'Criar novo usuário'"></h3>
                <button class="text-slate-500 hover:text-slate-700" @click="open=false">&times;</button>
            </div>
            <form method="POST" :action="formAction" class="space-y-3">
                @csrf
                <template x-if="editing">
                    <input type="hidden" name="_method" value="PATCH">
                </template>
                <x-toggle-ativo
                    name="ativo"
                    x-model="form.ativo"
                    on-label="Usuário ativo"
                    off-label="Usuário inativo"
                    text-class="text-sm text-slate-600"
                />
                <input name="name" x-model="form.name" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400" placeholder="Nome completo *" required>
                <input name="email" x-model="form.email" type="email" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400" placeholder="E-mail corporativo *" required>
                <div class="relative" x-data="{ showPassword: false }" x-show="!editing">
                    <input name="password"
                           :type="showPassword ? 'text' : 'password'"
                           :required="!editing"
                           class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 pr-11 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400"
                           placeholder="Senha">
                    <button type="button"
                            class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-slate-500 hover:text-slate-700"
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
                <input name="telefone" x-model="form.telefone" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400" placeholder="Telefone (opcional)">
                <select name="papel_id" x-model="form.papel_id" class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400" required>
                    <option value="">Selecione o perfil</option>
                    @foreach($papeis as $p)
                        @continue(in_array(strtolower($p->nome), ['cliente', 'parceiros'], true))
                        <option value="{{ $p->id }}">{{ $p->nome }}</option>
                    @endforeach
                </select>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="open=false">Cancelar</button>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                            x-text="editing ? 'Salvar alterações' : 'Salvar e criar'"></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal redefinir senha --}}
    <div x-cloak x-show="resetOpen" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
         @keydown.escape.window="resetOpen=false">
        <div class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Redefinir senha</h3>
                <button class="text-slate-500 hover:text-slate-700" @click="resetOpen=false">&times;</button>
            </div>
            <p class="mb-4 text-sm text-slate-600">
                Defina uma nova senha para <span class="font-semibold" x-text="resetNome || 'usuário'"></span>.
            </p>
            <form method="POST" :action="resetAction" class="space-y-3">
                @csrf
                <div class="relative" x-data="{ showPassword: false }">
                    <input type="password"
                           name="password"
                           required
                           :type="showPassword ? 'text' : 'password'"
                           class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 pr-11 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400"
                           placeholder="Nova senha *">
                    <button type="button"
                            class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-slate-500 hover:text-slate-700"
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
                           class="w-full rounded-xl border-slate-200 bg-slate-50 px-3 py-2 pr-11 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400"
                           placeholder="Confirmar senha *">
                    <button type="button"
                            class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-slate-500 hover:text-slate-700"
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
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="resetOpen=false">Cancelar</button>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Salvar senha</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('usuarios-filter-form');
                const input = document.getElementById('usuarios-autocomplete-input');

                window.initTailwindAutocomplete?.(
                    'usuarios-autocomplete-input',
                    'usuarios-autocomplete-list',
                    @json($usuariosAutocomplete ?? [])
                );

                input?.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();

                    if (typeof form?.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form?.submit();
                    }
                });
            });
        </script>
    @endpush
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const flashErro = @json(session('erro'));
            const flashOk = @json(session('ok'));

            if (flashErro) {
                window.uiAlert?.(flashErro, {
                    icon: 'error',
                    title: 'Não foi possível excluir',
                    confirmText: 'Ok',
                });
            } else if (flashOk) {
                window.uiAlert?.(flashOk, {
                    icon: 'success',
                    title: 'Concluído',
                    confirmText: 'Ok',
                });
            }

            document.querySelectorAll('.js-delete-user').forEach(function (form) {
                form.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    const confirmed = await window.uiConfirm?.(
                        'Se houver vínculos, recomendamos inativar em vez de excluir.',
                        {
                            title: 'Excluir usuário?',
                            icon: 'warning',
                            confirmText: 'Excluir',
                            cancelText: 'Cancelar',
                        }
                    );

                    if (confirmed) {
                        HTMLFormElement.prototype.submit.call(form);
                    }
                });
            });
        });
    </script>
@endpush
