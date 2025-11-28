@extends('layouts.operacional')

@php
    /** @var \App\Models\PcmsoSolicitacoes|null $pcmso */
    $isEdit = isset($pcmso);
@endphp
@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('pageTitle', 'PCMSO - Matriz')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <a href="{{ route('operacional.pcmso.possui-pgr', [$cliente, 'matriz']) }}"
           class="inline-flex items-center gap-2 text-xs text-slate-600 mb-4">
            ← Voltar
        </a>

        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-fuchsia-500 text-white">
                <h1 class="text-lg font-semibold">PCMSO - Matriz</h1>
                <p class="text-xs text-purple-100 mt-1">
                    {{ $cliente->razao_social ?? $cliente->nome }}
                </p>
            </div>

            <form method="POST"
                  action="{{ $isEdit
                        ? route('operacional.kanban.pcmso.update', $pcmso->tarefa_id)
                        : route('operacional.pcmso.store-com-pgr', [$cliente, 'matriz']) }}"
                  enctype="multipart/form-data"
                  class="p-6 space-y-4">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700 mb-3">
                        <ul class="list-disc ms-4">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Inserir PGR / Inventário de Risco {{ $isEdit ? '' : '*' }}
                    </label>

                    <input type="file" name="pgr_arquivo" accept="application/pdf"
                           class="w-full text-sm rounded-lg border border-slate-200 px-3 py-2
                  file:mr-3 file:px-3 file:py-2 file:rounded-lg
                  file:border-0 file:bg-purple-600 file:text-white
                  hover:file:bg-purple-700">

                    @if($isEdit && !empty($pcmso->pgr_arquivo_path))

                        <div class="mt-2 text-xs text-slate-600">
                            <p>
                                Arquivo atual:
                                <a href="{{ asset('storage/'.$pcmso->pgr_arquivo_path) }}"
                                   target="_blank"
                                   class="text-purple-600 underline">
                                    Abrir PGR atual
                                </a>
                            </p>

                            <label class="inline-flex items-center gap-2 mt-1 text-[11px] text-red-600">
                                <input type="checkbox" name="remover_arquivo" value="1"
                                       class="rounded border-slate-300">
                                <span>Remover arquivo atual</span>
                            </label>
                        </div>
                    @endif
                </div>


                <div class="rounded-xl bg-purple-50 border border-purple-100 px-4 py-3 text-xs text-purple-800">
                    Tarefa de PCMSO Matriz será criada com prazo de <strong>10 dias</strong>.
                </div>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <a href="{{ route('operacional.pcmso.possui-pgr', [$cliente, 'matriz']) }}"
                       class="flex-1 text-center rounded-lg bg-slate-50 border border-slate-200 text-sm font-semibold
                              text-slate-700 py-2.5 hover:bg-slate-100 transition">
                        Voltar
                    </a>

                    <button type="submit"
                            class="flex-1 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-sm
               font-semibold py-2.5 transition">
                        {{ $isEdit ? 'Salvar alterações' : 'Criar Tarefa PCMSO' }}
                    </button>

                </div>
            </form>
        </div>
    </div>
@endsection
