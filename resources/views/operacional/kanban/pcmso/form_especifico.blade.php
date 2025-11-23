@extends('layouts.operacional')

@section('pageTitle', 'PCMSO - Específico')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <a href="{{ route('operacional.pcmso.possui-pgr', [$cliente, 'especifico']) }}"
           class="inline-flex items-center gap-2 text-xs text-slate-600 mb-4">
            ← Voltar
        </a>

        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-fuchsia-500 text-white">
                <h1 class="text-lg font-semibold">PCMSO - Específico</h1>
                <p class="text-xs text-purple-100 mt-1">
                    {{ $cliente->razao_social ?? $cliente->nome }}
                </p>
            </div>

            <form method="POST"
                  action="{{ route('operacional.pcmso.store-com-pgr', [$cliente, 'especifico']) }}"
                  enctype="multipart/form-data"
                  class="p-6 space-y-4">
                @csrf

                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700 mb-3">
                        <ul class="list-disc ms-4">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Nome da Obra *
                        </label>
                        <input type="text" name="obra_nome" value="{{ old('obra_nome') }}"
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                               placeholder="Nome da obra">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            CNPJ do Contratante *
                        </label>
                        <input type="text" name="obra_cnpj_contratante" value="{{ old('obra_cnpj_contratante') }}"
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                               placeholder="00.000.000/0000-00">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            CEI/CNO *
                        </label>
                        <input type="text" name="obra_cei_cno" value="{{ old('obra_cei_cno') }}"
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                               placeholder="Número do CEI ou CNO">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Endereço da Obra *
                        </label>
                        <input type="text" name="obra_endereco" value="{{ old('obra_endereco') }}"
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                               placeholder="Endereço completo">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Inserir PGR / Inventário de Risco *
                    </label>
                    <input type="file" name="pgr_arquivo" accept="application/pdf"
                           class="w-full text-sm rounded-lg border border-slate-200 px-3 py-2
                                  file:mr-3 file:px-3 file:py-2 file:rounded-lg
                                  file:border-0 file:bg-purple-600 file:text-white
                                  hover:file:bg-purple-700">
                </div>

                <div class="rounded-xl bg-purple-50 border border-purple-100 px-4 py-3 text-xs text-purple-800">
                    Tarefa de PCMSO Específico será criada com prazo de <strong>10 dias</strong>.
                </div>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <a href="{{ route('operacional.pcmso.possui-pgr', [$cliente, 'especifico']) }}"
                       class="flex-1 text-center rounded-lg bg-slate-50 border border-slate-200 text-sm font-semibold
                              text-slate-700 py-2.5 hover:bg-slate-100 transition">
                        Voltar
                    </a>

                    <button type="submit"
                            class="flex-1 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-sm
                                   font-semibold py-2.5 transition">
                        Criar Tarefa PCMSO
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
