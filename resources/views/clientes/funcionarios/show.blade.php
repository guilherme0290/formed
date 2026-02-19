@extends('layouts.cliente')

@section('title', 'Detalhes do Funcionário')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canUpdate = $isMaster || isset($permissionMap['cliente.funcionarios.update']);
        $canToggle = $isMaster || isset($permissionMap['cliente.funcionarios.toggle']);
        $canDelete = $canUpdate;
    @endphp
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex gap-2">
                <a href="{{ route('cliente.dashboard') }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                    ← Voltar ao início
                </a>
                <a href="{{ route('cliente.funcionarios.index') }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                    ← Voltar para lista
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $canUpdate ? route('cliente.funcionarios.edit', $funcionario) : 'javascript:void(0)' }}"
                   @if(!$canUpdate) title="Usuario sem permissao." aria-disabled="true" @endif
                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold {{ $canUpdate ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                    Editar informações
                </a>
                <form method="POST" action="{{ route('cliente.funcionarios.destroy', $funcionario) }}"
                      onsubmit="return confirm('Deseja remover este funcionário?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            @if(!$canDelete) disabled title="Usuario sem permissao." @endif
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold {{ $canDelete ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                        Deletar
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-blue-800 bg-blue-700 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-lg md:text-xl font-semibold text-white">{{ $funcionario->nome }}</h1>
                    <p class="text-sm text-blue-100 mt-0.5">{{ $funcionario->funcao->nome ?? 'Função não informada' }}</p>
                </div>

                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $funcionario->ativo ? 'bg-emerald-200 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                    {{ $funcionario->ativo ? 'Ativo' : 'Inativo' }}
                </span>
            </div>

            <div class="p-4 md:p-5 grid grid-cols-1 lg:grid-cols-12 gap-4">
                <section class="lg:col-span-4 bg-slate-50/70 border border-slate-200 rounded-xl p-4">
                    <h2 class="text-sm font-semibold text-slate-800 mb-3">Informações pessoais</h2>

                    <dl class="space-y-3 text-sm text-slate-700">
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">CPF</dt>
                            <dd class="mt-0.5">{{ $funcionario->cpf }}</dd>
                        </div>

                        @if($funcionario->celular ?? false)
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Celular</dt>
                                <dd class="mt-0.5">{{ $funcionario->celular }}</dd>
                            </div>
                        @endif

                        @if($funcionario->setor ?? false)
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Setor</dt>
                                <dd class="mt-0.5">{{ $funcionario->setor }}</dd>
                            </div>
                        @endif

                        @if($funcionario->data_nascimento ?? false)
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Data de Nascimento</dt>
                                <dd class="mt-0.5">{{ \Carbon\Carbon::parse($funcionario->data_nascimento)->format('d/m/Y') }}</dd>
                            </div>
                        @endif

                        @if($funcionario->data_admissao ?? false)
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Data de Admissão</dt>
                                <dd class="mt-0.5">{{ \Carbon\Carbon::parse($funcionario->data_admissao)->format('d/m/Y') }}</dd>
                            </div>
                        @endif

                        @if($funcionario->vaga_atual ?? false)
                            <div>
                                <dt class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Vaga atual</dt>
                                <dd class="mt-0.5">{{ $funcionario->vaga_atual }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-4 pt-4 border-t border-slate-200">
                        <form method="POST" action="{{ route('cliente.funcionarios.toggle-status', $funcionario) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    @if(!$canToggle) disabled title="Usuario sem permissao." @endif
                                    class="w-full inline-flex justify-center items-center px-4 py-2 rounded-lg text-xs font-semibold {{ $canToggle ? ($funcionario->ativo ? 'border border-red-300 text-red-700 bg-red-50 hover:bg-red-100' : 'border border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100') : 'bg-slate-200 text-slate-500 cursor-not-allowed border border-slate-300' }}">
                                {{ $funcionario->ativo ? 'Inativar funcionário' : 'Reativar funcionário' }}
                            </button>
                        </form>
                    </div>
                </section>

                <section class="lg:col-span-8 border border-slate-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-3 border-b border-blue-800 bg-blue-700 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-white">Arquivos ASO</h2>
                        <a href="{{ route('cliente.arquivos.funcionario.download', $funcionario) }}"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-blue-200 text-blue-700 bg-white hover:bg-blue-50">
                            Baixar todos (ZIP)
                        </a>
                    </div>

                    @if(($arquivosAso ?? collect())->isEmpty())
                        <div class="px-4 py-8 text-sm text-slate-500 bg-white">
                            Nenhum arquivo de ASO disponível para este funcionário.
                        </div>
                    @else
                        <form method="POST" action="{{ route('cliente.arquivos.funcionario.download-selecionados', $funcionario) }}">
                            @csrf

                            <div class="overflow-x-auto bg-white">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold w-10">
                                            <input type="checkbox" class="rounded border-slate-300 js-check-all-arquivos-funcionario">
                                        </th>
                                        <th class="px-3 py-2 text-left font-semibold">Serviço</th>
                                        <th class="px-3 py-2 text-left font-semibold">Finalizado em</th>
                                        <th class="px-3 py-2 text-left font-semibold">Status</th>
                                        <th class="px-3 py-2 text-right font-semibold">Arquivo</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                    @foreach($arquivosAso as $arquivo)
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="px-3 py-2">
                                                <input type="checkbox" name="tarefa_ids[]" value="{{ $arquivo->id }}" class="rounded border-slate-300 js-check-item-arquivo-funcionario">
                                            </td>
                                            <td class="px-3 py-2 text-slate-800">{{ $arquivo->servico->nome ?? 'ASO' }}</td>
                                            <td class="px-3 py-2 text-slate-700">{{ optional($arquivo->finalizado_em)->format('d/m/Y H:i') ?? '-' }}</td>
                                            <td class="px-3 py-2 text-slate-700">{{ $arquivo->coluna->nome ?? 'Finalizado' }}</td>
                                            <td class="px-3 py-2 text-right">
                                                @if($arquivo->documento_link)
                                                    <a href="{{ $arquivo->documento_link }}"
                                                       target="_blank" rel="noopener"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                                                        Ver / Baixar
                                                    </a>
                                                @else
                                                    <span class="text-xs text-slate-400">Indisponível</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/80 flex justify-between items-center">
                                <span class="text-xs text-slate-500">Selecione os documentos desejados para baixar em ZIP.</span>
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                                    Baixar selecionados
                                </button>
                            </div>
                        </form>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.querySelector('.js-check-all-arquivos-funcionario');
    if (!checkAll) return;

    const items = Array.from(document.querySelectorAll('.js-check-item-arquivo-funcionario'));
    checkAll.addEventListener('change', function () {
        items.forEach((item) => {
            item.checked = checkAll.checked;
        });
    });
});
</script>
@endpush
