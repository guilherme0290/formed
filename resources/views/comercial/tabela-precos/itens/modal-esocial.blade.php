{{-- Modal eSocial --}}
<div id="modalEsocial" class="fixed inset-0 z-50 hidden bg-black/40">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-4xl rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[85vh]">

            {{-- Header --}}
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Faixas de eSocial</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Defina faixas por quantidade de colaboradores.</p>
                </div>
                <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                        onclick="closeEsocialModal()">✕</button>
            </div>

            {{-- Body --}}
            <div class="p-6 overflow-y-auto">
                {{-- alert --}}
                <div id="esocialAlert" class="hidden mb-4 rounded-xl border px-4 py-3 text-sm"></div>

                {{-- grid header --}}
                <div class="grid grid-cols-12 gap-2 text-xs font-semibold text-slate-500 mb-2">
                    <div class="col-span-3">Faixa</div>
                    <div class="col-span-5">Descrição</div>
                    <div class="col-span-2 text-right">Preço</div>
                    <div class="col-span-1 text-center">Ativo</div>
                    <div class="col-span-1 text-right">Ações</div>
                </div>

                {{-- rows --}}
                <div id="esocialFaixas" class="space-y-2"></div>

                <button id="btnNovaFaixa" type="button"
                        class="mt-5 w-full rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 text-sm font-semibold">
                    + Nova Faixa
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal interno: criar/editar faixa --}}
<div id="modalEsocialForm" class="fixed inset-0 z-[60] hidden bg-black/40">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 id="esocialFormTitle" class="text-base font-semibold">Nova Faixa</h3>
                <button type="button" class="h-9 w-9 rounded-xl hover:bg-slate-100 text-slate-500"
                        onclick="closeEsocialForm()">✕</button>
            </div>

            <form id="formEsocialFaixa" class="p-6 space-y-4">
                <input type="hidden" id="esocial_faixa_id" value="">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Início *</label>
                        <input id="esocial_inicio" type="number" min="1"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Fim *</label>
                        <input id="esocial_fim" type="number" min="1"
                               class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">
                        <p class="text-[11px] text-slate-500 mt-1">Use 999999 para “acima de”.</p>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Descrição</label>
                    <input id="esocial_descricao" type="text"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2"
                           placeholder="Ex: 01 até 10 colaboradores">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Preço *</label>
                    <input id="esocial_preco_view" type="text" inputmode="decimal" autocomplete="off"
                           placeholder="R$ 0,00"
                           class="w-full mt-1 rounded-xl border-slate-200 text-sm px-3 py-2">
                    <input id="esocial_preco" type="hidden" value="0.00">
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative inline-block w-11 h-5">
                        <input id="esocial_ativo" type="checkbox" value="1"
                               class="peer appearance-none w-11 h-5 rounded-full cursor-pointer transition-colors duration-300
                                      bg-red-600 checked:bg-green-600" checked>
                        <label for="esocial_ativo"
                               class="absolute top-0 left-0 w-5 h-5 bg-white rounded-full border shadow-sm cursor-pointer
                                      transition-transform duration-300
                                      border-red-600 peer-checked:border-green-600 peer-checked:translate-x-6"></label>
                    </div>
                    <span id="esocial_ativo_label" class="text-sm font-medium text-slate-700">Ativo</span>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button"
                            class="rounded-xl px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                            onclick="closeEsocialForm()">
                        Cancelar
                    </button>

                    <button id="btnSalvarFaixa" type="submit"
                            class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold">
                        Salvar
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
