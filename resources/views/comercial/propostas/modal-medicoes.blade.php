<div id="modalMedicoes" class="fixed inset-0 z-[90] hidden bg-black/50 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-5 py-4 border-b bg-amber-700 text-white flex items-center justify-between">
                <div>
                    <h3 class="font-semibold">Medições <span id="medicoesTipoLabel">LTCAT/LTIP</span></h3>
                    <p class="text-xs opacity-90">Selecione ao menos 1 item.</p>
                </div>
                <button type="button" class="h-9 w-9 rounded-xl hover:bg-white/10" onclick="closeMedicoesModal()">✕</button>
            </div>

            <div class="p-5 space-y-4">
                <div id="medicoesModalAlert" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-700"></div>

                <div>
                    <label class="text-xs font-semibold text-slate-700 block mb-2">
                        Itens de medição (<span id="medicoesCount">0</span> selecionados)
                    </label>

                    <div id="medicoesList" class="space-y-2 max-h-[45vh] overflow-auto pr-1">
                        <div class="text-sm text-slate-500">Carregando medições...</div>
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-3 py-2">
                    <span class="text-sm font-semibold text-amber-800">Total selecionado</span>
                    <span id="medicoesTotal" class="text-sm font-semibold text-amber-900">R$ 0,00</span>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm hover:bg-slate-100"
                            onclick="closeMedicoesModal()">Cancelar</button>

                    <button type="button"
                            id="medicoesConfirmBtn"
                            class="rounded-xl px-4 py-2 text-sm bg-amber-600 hover:bg-amber-700 text-white font-semibold"
                            onclick="confirmMedicoes()">
                        Adicionar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
