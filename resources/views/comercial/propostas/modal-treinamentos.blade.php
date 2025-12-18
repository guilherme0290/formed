{{-- MODAL PACOTE TREINAMENTOS --}}
<div id="modalPacoteTreinamentos" class="fixed inset-0 z-50 hidden bg-black/40">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden">
            <div class="px-5 py-4 border-b bg-emerald-700 text-white flex items-center justify-between">
                <h3 class="font-semibold">Criar Pacote de Treinamentos</h3>
                <button type="button" class="h-9 w-9 rounded-xl hover:bg-white/10" onclick="closePacoteTreinamentosModal()">✕</button>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-slate-700">Nome do Pacote *</label>
                    <input id="pkgTreinNome" type="text"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                           placeholder="Ex: Pacote Construção Civil">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-700 block mb-2">
                        Selecione os treinamentos (<span id="pkgTreinCount">0</span> selecionados)
                    </label>

                    <div id="pkgTreinList" class="space-y-2 max-h-[45vh] overflow-auto pr-1">
                        @foreach($treinamentos as $t)
                            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <input type="checkbox"
                                       class="rounded border-slate-300"
                                       value="{{ $t->id }}"
                                       data-codigo="{{ e($t->codigo) }}"
                                       data-titulo="{{ e($t->titulo) }}">
                                <span class="font-semibold w-16">{{ $t->codigo }}</span>
                                <span class="text-slate-700 flex-1">{{ $t->titulo }}</span>
                                <span class="text-xs text-slate-400">#{{ $t->id }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-700">Valor do Pacote (R$) *</label>
                    <input id="pkgTreinValorView" type="text"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2"
                           value="R$ 0,00">
                    <input id="pkgTreinValorHidden" type="hidden" value="0.00">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm hover:bg-slate-100"
                            onclick="closePacoteTreinamentosModal()">Cancelar</button>

                    <button type="button"
                            class="rounded-xl px-4 py-2 text-sm bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"
                            onclick="confirmPacoteTreinamentos()">
                        Adicionar Pacote
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
