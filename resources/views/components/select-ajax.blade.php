@props([
    'name',
    'label' => null,
    'endpoint' => '#',
    'placeholder' => 'Digite para buscar...'
])

<div x-data="selectAjax('{{ $endpoint }}')" class="space-y-1">
    @if($label)
        <label class="text-sm text-gray-600">{{ $label }}</label>
    @endif

    {{-- ESTE hidden é o que vai no POST (ex.: servico_id) --}}
    <input type="hidden" name="{{ $name }}" x-model="selectedId">

    <input type="text"
           x-model.debounce.300ms="term"
           x-on:focus="open = true"
           autocomplete="off"
           class="w-full rounded-lg border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-400 focus:ring-indigo-400 px-3 py-2"
           placeholder="{{ $placeholder }}">

    <div x-show="open && results.length"
         x-cloak
         class="bg-white border rounded-lg mt-1 max-h-56 overflow-auto z-50 relative">
        <template x-for="item in results" :key="item.id">
            <button type="button"
                    class="block w-full text-left px-3 py-2 hover:bg-gray-50"
                    x-on:click="select(item)">
                {{-- tenta usar nome (serviços), depois razao_social, depois text --}}
                <span class="text-sm font-medium"
                      x-text="item.nome || item.razao_social || item.text || ('ID ' + item.id)">
                </span>

                {{-- subtítulo opcional: tipo, cnpj, etc --}}
                <span class="text-xs text-gray-500"
                      x-text="item.tipo || item.cnpj || ''">
                </span>
            </button>
        </template>
    </div>
</div>

@push('scripts')
    <script>
        function selectAjax(endpoint){
            return {
                term: '',
                results: [],
                selectedId: null,
                open: false,

                fetch(){
                    if(!this.term || this.term.length < 2){
                        this.results = [];
                        return;
                    }

                    fetch(endpoint + '?q=' + encodeURIComponent(this.term), {
                        headers: { 'X-Requested-With':'XMLHttpRequest' }
                    })
                        .then(r => r.json())
                        .then(d => {
                            // se sua API devolver {data:[...]} ou só [...]
                            this.results = Array.isArray(d) ? d : (d.data || []);
                            this.open = true;
                        })
                        .catch(() => this.results = []);
                },

                select(item){
                    const label = item.nome || item.razao_social || item.text || ('ID ' + item.id);

                    this.selectedId = item.id;
                    this.term = label;
                    this.results = [];
                    this.open = false;

                    // avisa quem estiver ouvindo (se precisar)
                    window.dispatchEvent(new CustomEvent('select-ajax:selected', {
                        detail: { id: item.id, text: label }
                    }));
                },

                init(){
                    this.$watch('term', () => this.fetch());
                }
            }
        }
    </script>
@endpush
