@extends('layouts.comercial')
@section('title', 'Propostas Rápidas')

@section('content')
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
         x-data="duplicarPropostaRapidaModal()">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Propostas Rápidas</h1>
                <p class="text-sm text-slate-500">Propostas diretas para abordagem inicial do comercial.</p>
            </div>

            <a href="{{ route('comercial.propostas.rapidas.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                <span class="text-base leading-none">+</span>
                <span>Nova proposta</span>
            </a>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 p-4">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-10">
                        <label class="text-xs font-semibold text-slate-600">Buscar</label>
                        <input name="q"
                               value="{{ request('q') }}"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                               placeholder="Buscar por nome do cliente...">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit"
                                class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <div class="max-h-[70vh] overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Código</th>
                        <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                        <th class="px-4 py-3 text-left font-semibold">Vendedor</th>
                        <th class="px-4 py-3 text-left font-semibold">Valor final</th>
                        <th class="px-4 py-3 text-left font-semibold">Criada em</th>
                        <th class="px-4 py-3 text-right font-semibold">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($propostas as $proposta)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $proposta->codigo ?? ('#' . $proposta->id) }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $proposta->cliente?->razao_social ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $proposta->cliente?->documento_principal ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $proposta->vendedor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">R$ {{ number_format((float) $proposta->valor_total, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ optional($proposta->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('comercial.propostas.rapidas.show', $proposta) }}"
                                       class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Abrir
                                    </a>
                                    <a href="{{ route('comercial.propostas.rapidas.edit', $proposta) }}"
                                       class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                        Editar
                                    </a>
                                    <button type="button"
                                            @click="abrir({ codigo: @js($proposta->codigo ?? ('#' . $proposta->id)), urlBase: @js(route('comercial.propostas.rapidas.create', ['duplicar_de' => $proposta->id])) })"
                                            class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                        Duplicar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                Nenhuma proposta rápida encontrada.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 p-4">
                {{ $propostas->links() }}
            </div>
        </section>

        <div x-cloak
             x-show="aberto"
             x-transition.opacity
             class="fixed inset-0 z-[90] bg-slate-950/50 p-4"
             data-overlay-root="true"
             @click.self="fechar()">
            <div class="mx-auto flex min-h-full max-w-lg items-center justify-center">
                <div class="w-full rounded-[28px] border border-slate-200 bg-white shadow-2xl">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Duplicar proposta</div>
                        <h2 class="mt-2 text-xl font-black text-slate-900">Nova proposta a partir de <span x-text="propostaCodigo"></span></h2>
                        <p class="mt-2 text-sm text-slate-500">A estrutura da proposta será mantida. Escolha apenas como o novo cliente será informado.</p>
                    </div>

                    <div class="space-y-4 px-6 py-5">
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 px-4 py-4 hover:border-blue-200 hover:bg-blue-50/50">
                            <input type="radio" name="duplicar_cliente_modo" value="existente" x-model="clienteModo"
                                   class="mt-1 h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <div class="font-semibold text-slate-900">Cliente cadastrado</div>
                                <div class="text-sm text-slate-500">Abre a proposta duplicada para você selecionar um cliente já existente.</div>
                            </div>
                        </label>

                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 px-4 py-4 hover:border-blue-200 hover:bg-blue-50/50">
                            <input type="radio" name="duplicar_cliente_modo" value="novo" x-model="clienteModo"
                                   class="mt-1 h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <div class="font-semibold text-slate-900">Novo cliente</div>
                                <div class="text-sm text-slate-500">Abre a proposta duplicada com o cadastro de novo cliente já preparado.</div>
                            </div>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button type="button"
                                @click="fechar()"
                                class="rounded-2xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                            Cancelar
                        </button>
                        <button type="button"
                                @click="confirmar()"
                                class="rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                            Continuar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function duplicarPropostaRapidaModal() {
            return {
                aberto: false,
                propostaCodigo: '',
                urlBase: '',
                clienteModo: 'existente',
                abrir(payload) {
                    this.propostaCodigo = payload?.codigo || '';
                    this.urlBase = payload?.urlBase || '';
                    this.clienteModo = 'existente';
                    this.aberto = true;
                },
                fechar() {
                    this.aberto = false;
                },
                confirmar() {
                    if (!this.urlBase) {
                        this.fechar();
                        return;
                    }

                    const separador = this.urlBase.includes('?') ? '&' : '?';
                    window.location.href = `${this.urlBase}${separador}cliente_modo=${encodeURIComponent(this.clienteModo)}`;
                },
            };
        }
    </script>
@endpush
