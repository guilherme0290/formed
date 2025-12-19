@php
    $papeisList = $papeis ?? collect();
    $permissoesList = $permissoes ?? collect();
@endphp

<div class="bg-white rounded-2xl shadow p-5 space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Permissões por Papel</h2>
            <p class="text-sm text-slate-500">Marque as permissões que cada papel pode acessar. Salva imediatamente.</p>
        </div>
    </div>

    @php
        $escopoCores = [
            'master' => 'text-indigo-700 bg-indigo-50 border-indigo-100',
            'comercial' => 'text-orange-700 bg-orange-50 border-orange-100',
            'operacional' => 'text-emerald-700 bg-emerald-50 border-emerald-100',
            'cliente' => 'text-blue-700 bg-blue-50 border-blue-100',
            '' => 'text-slate-700 bg-slate-50 border-slate-100',
        ];
        $mapPapelEscopo = [
            'master' => 'master',
            'comercial' => 'comercial',
            'operacional' => 'operacional',
            'cliente' => 'cliente',
        ];
    @endphp

    <div class="space-y-4">
        @forelse($papeisList as $papel)
            @php
                $papelKey = mb_strtolower($papel->nome ?? '');
                $escopoAlvo = $mapPapelEscopo[$papelKey] ?? null;
            @endphp
            <div class="rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">{{ $papel->nome }}</h3>
                        <p class="text-xs text-slate-500">{{ $papel->descricao ?: 'Sem descrição' }}</p>
                    </div>
                    <form method="POST" action="{{ route('master.papeis.permissoes.sync', $papel) }}"
                          onsubmit="return confirm('Salvar permissões para {{ $papel->nome }}?')">
                        @csrf
                        <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                            Salvar alterações
                        </button>
                </div>
                <div class="p-4 space-y-4">
                    @forelse($permissoesList->filter(function ($grupo, $escopo) use ($escopoAlvo) {
                        return $escopoAlvo ? $escopo === $escopoAlvo : true;
                    }) as $escopo => $grupo)
                        @php
                            $escopoKey = $escopo ?: 'geral';
                            $style = $escopoCores[$escopo] ?? $escopoCores[''];
                        @endphp
                        <div class="space-y-2" data-escopo-wrap="{{ $escopoKey }}">
                            <div class="flex items-center justify-between">
                                <div class="text-xs font-semibold uppercase tracking-wide px-2 py-1 rounded-lg border {{ $style }}">
                                    {{ $escopo ?: 'Geral' }}
                                </div>
                                <button type="button"
                                        class="text-[11px] font-semibold text-indigo-600 hover:underline"
                                        data-select-escopo="{{ $escopoKey }}">
                                    Selecionar tudo
                                </button>
                            </div>
                            <div class="grid md:grid-cols-2 gap-2">
                                @foreach($grupo as $perm)
                                    <label class="flex items-start gap-2 rounded-xl border border-slate-200 px-3 py-2 hover:bg-slate-50 text-sm">
                                        <input type="checkbox"
                                               name="permissoes[]"
                                               value="{{ $perm->id }}"
                                               data-escopo-check="{{ $escopoKey }}"
                                               class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                               {{ $papel->permissoes->contains('id', $perm->id) ? 'checked' : '' }}>
                                        <div>
                                            <div class="font-semibold text-slate-800">{{ $perm->nome }}</div>
                                            <div class="text-xs text-slate-500">{{ $perm->chave }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Nenhuma permissão cadastrada.</p>
                    @endforelse
                </div>
                    </form>
            </div>
        @empty
            <div class="text-sm text-slate-500">Cadastre papéis antes de configurar permissões.</div>
        @endforelse
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-select-escopo]').forEach(btn => {
            btn.addEventListener('click', () => {
                const escopo = btn.getAttribute('data-select-escopo');
                document.querySelectorAll(`[data-escopo-check=\"${escopo}\"]`).forEach(chk => {
                    chk.checked = true;
                });
            });
        });
    });
</script>
