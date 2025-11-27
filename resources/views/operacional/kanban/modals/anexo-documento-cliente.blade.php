{{-- Modal Finalizar Tarefa --}}
<div
    id="tarefa-finalizar-modal"
    class="fixed inset-0 z-40 hidden items-center justify-center bg-black/30"
>
    <div class="bg-white rounded-2xl shadow-xl w-[360px] max-w-full overflow-hidden">
        {{-- Cabeçalho verde --}}
        <div class="bg-emerald-600 text-white px-4 py-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold">Finalizar Tarefa - Anexar Arquivo</h2>

            {{-- Botão fechar --}}
            <button type="button"
                    id="tarefa-finalizar-x"
                    class="text-white/80 hover:text-white text-lg leading-none">
                &times;
            </button>
        </div>

        {{-- Corpo --}}
        <form
            id="tarefa-finalizar-form"
            class="px-4 py-4 space-y-4"
        >
            <div class="text-xs text-slate-600">
                <p class="font-semibold" id="tarefa-finalizar-titulo"></p>
                <p class="mt-1" id="tarefa-finalizar-cliente"></p>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-medium text-slate-700">
                    Arquivo para envio ao cliente *
                </label>
                <input type="file"
                       id="tarefa-finalizar-arquivo"
                       name="arquivo_cliente"
                       class="block w-full text-xs text-slate-700
                              file:mr-2 file:px-3 file:py-1.5 file:text-xs
                              file:rounded-lg file:border-0
                              file:bg-slate-100 file:text-slate-700
                              border border-slate-200 rounded-lg"
                       required>
                <p class="text-[11px] text-slate-400">
                    Obrigatório anexar arquivo para finalizar a tarefa.
                </p>
            </div>

            <label class="flex items-start gap-2 text-[11px] text-slate-600 border rounded-lg px-3 py-2 bg-slate-50">
                <input type="checkbox"
                       id="tarefa-finalizar-notificar"
                       class="mt-0.5"
                       checked>
                <span>
                    Ao finalizar, o cliente será notificado automaticamente por E-mail e WhatsApp.
                </span>
            </label>

            {{-- Rodapé --}}
            <div class="flex justify-between items-center pt-2 border-t border-slate-100 mt-2">
                <button type="button"
                        id="tarefa-finalizar-close" {{-- usa o mesmo id do botão X --}}
                        class="px-3 py-2 rounded-lg text-xs font-medium border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Cancelar
                </button>

                <button type="submit"
                        class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-xs font-semibold
                               bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-60">
                    <span>Finalizar e Notificar Cliente</span>
                </button>
            </div>
        </form>
    </div>
</div>
