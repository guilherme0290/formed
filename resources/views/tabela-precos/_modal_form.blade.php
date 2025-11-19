<x-modal name="preco-form">
    <div
        x-data="{
            // edição
            id: null,
            update_url: null,

            // dados do serviço (sempre novo_servico)
            novo_nome: '',
            novo_tipo: '',
            novo_esocial: '',
            novo_valor_base: '',

            // dados do item de tabela de preço
            codigo: '',
            descricao: '',
            preco: '',
            ativo: 1,

            fill(p = {}) {
                this.id             = p.id ?? null
                this.update_url     = p.update_url ?? null

                this.novo_nome      = p.novo_nome ?? ''
                this.novo_tipo      = p.novo_tipo ?? ''
                this.novo_esocial   = p.novo_esocial ?? ''
                this.novo_valor_base= p.novo_valor_base ?? ''

                this.codigo         = p.codigo ?? ''
                this.descricao      = p.descricao ?? ''
                this.preco          = p.preco ?? ''
                this.ativo          = (p.ativo ?? 1) ? 1 : 0
            }
        }"
        x-on:open-modal.window="
            if ($event.detail?.name !== 'preco-form') return;
            fill($event.detail?.params || {});
        "
        class="p-6 min-w-[700px]"
    >
        <h3 class="text-lg font-semibold mb-1" x-text="id ? 'Editar preço' : 'Cadastrar preço'"></h3>
        <p class="text-sm text-slate-500 mb-4">Preço global usado pelo Operacional.</p>

        {{-- ========= FORM ÚNICO (sempre NOVO SERVIÇO) ========= --}}
        <form
            x-bind:action="id ? update_url : '{{ route('tabela-precos.items.store') }}'"
            method="POST"
            x-on:submit.prevent="$el.submit()"
        >
            @csrf
            <template x-if="id">
                <input type="hidden" name="_method" value="PUT">
            </template>

            {{-- DADOS DO SERVIÇO --}}
            <div class="grid md:grid-cols-3 gap-3">
                <div class="md:col-span-3">
                    <label class="block text-sm mb-1">Nome do serviço *</label>
                    <input name="novo_servico[nome]" x-model="novo_nome"
                           class="input" placeholder="Ex.: Exame Admissional">
                </div>

                <div>
                    <label class="block text-sm mb-1">Tipo</label>
                    <input name="novo_servico[tipo]" x-model="novo_tipo"
                           class="input" placeholder="Ex.: Exame">
                </div>

                <div>
                    <label class="block text-sm mb-1">eSocial</label>
                    <input name="novo_servico[esocial]" x-model="novo_esocial"
                           class="input" placeholder="Ex.: S-2220, S-2240">
                </div>

                <div>
                    <label class="block text-sm mb-1">Valor base (opcional)</label>
                    <input name="novo_servico[valor_base]" x-model="novo_valor_base"
                           class="input preco-mask" placeholder="R$ 0,00" inputmode="decimal">
                </div>
            </div>

            {{-- CAMPOS DO ITEM --}}
            <div class="grid md:grid-cols-2 gap-3 mt-4">
                <div>
                    <label class="block text-sm mb-1">Código</label>
                    <input name="codigo" x-model="codigo" class="input" placeholder="SRV001/CMB001">
                </div>

                <div>
                    <label class="block text-sm mb-1">Preço (R$) *</label>
                    <input name="preco" x-model="preco"
                           class="input preco-mask" placeholder="R$ 0,00" inputmode="decimal">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Descrição (opcional)</label>
                    <textarea name="descricao" x-model="descricao"
                              class="input" rows="3" placeholder="Observações..."></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm mb-2">Status</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="ativo" value="0" :checked="!ativo"> Inativo
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="ativo" value="1" :checked="!!ativo"> Ativo
                        </label>
                    </div>
                </div>
            </div>

            {{-- sempre modo NOVO --}}
            <input type="hidden" name="modo" value="novo">

            <div class="mt-6">
                <button type="submit"
                        class="w-full px-4 py-3 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">
                    Salvar
                </button>
            </div>
        </form>
    </div>

    <script>
        // MÁSCARA BR DE PREÇO
        document.addEventListener('input', function (e) {
            if (!e.target.classList.contains('preco-mask')) return;
            let v = e.target.value.replace(/[^\d]/g, '');
            v = (parseInt(v || '0', 10) / 100).toFixed(2).replace('.', ',');
            e.target.value = 'R$ ' + v.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    </script>
</x-modal>
