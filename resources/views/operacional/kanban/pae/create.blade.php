@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')


@section('pageTitle', 'PAE - Plano de Atendimento a Emergências')

@section('content')
    @php
        $origem = request()->query('origem');
    @endphp
    <div class="w-full px-2 sm:px-3 md:px-4 xl:px-5 py-4 md:py-6">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ $origem === 'cliente'
                    ? route('cliente.agendamentos')
                    : route('operacional.kanban.servicos', ['cliente' => $cliente->id, 'origem' => $origem]) }}"
               class="inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50">
                ← Voltar
            </a>
        </div>

        <div class="w-full bg-white rounded-2xl shadow-lg overflow-hidden">
            {{-- Cabeçalho --}}
            <div class="px-4 sm:px-5 md:px-6 py-4 bg-gradient-to-r from-rose-700 to-rose-600 text-white">
                <h1 class="text-lg font-semibold">
                    PAE - Plano de Atendimento a Emergências
                    {{ !empty($isEdit) ? '(Editar)' : '' }}
                </h1>
                <p class="text-xs text-white/80 mt-1">
                    {{ $cliente->razao_social }}
                </p>
            </div>

            <form method="POST"
                  action="{{ !empty($isEdit) && $pae
                        ? route('operacional.pae.update', ['pae' => $pae, 'origem' => $origem])
                        : route('operacional.pae.store', ['cliente' => $cliente, 'origem' => $origem]) }}"
                  class="px-4 sm:px-5 md:px-6 py-5 md:py-6 space-y-6">
                @csrf
                @if(!empty($isEdit) && $pae)
                    @method('PUT')
                @endif
                <input type="hidden" name="origem" value="{{ $origem }}">

                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700">
                        <ul class="list-disc ms-4">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Endereço do Local --}}
                <section class="space-y-2">
                    <h2 class="text-sm font-semibold text-slate-800">Endereço do Local *</h2>

                    <input type="text"
                           name="endereco_local"
                           value="{{ old('endereco_local', $pae->endereco_local ?? '') }}"
                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                           placeholder="Endereço completo da instalação">
                </section>

                {{-- Número total de funcionários --}}
                <section class="space-y-2">
                    <h2 class="text-sm font-semibold text-slate-800">Número Total de Funcionários *</h2>

                    <input type="number"
                           name="total_funcionarios"
                           value="{{ old('total_funcionarios', $pae->total_funcionarios ?? 1) }}"
                           min="1"
                           class="w-full max-w-xs rounded-lg border-slate-200 text-sm px-3 py-2"
                           placeholder="Quantidade de pessoas no local">
                </section>

                {{-- Descrição das instalações --}}
                <section class="space-y-2">
                    <h2 class="text-sm font-semibold text-slate-800">Descrição das Instalações *</h2>

                    <textarea
                        name="descricao_instalacoes"
                        rows="4"
                        class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                        placeholder="Descreva o tipo de instalação, atividades realizadas, áreas de risco, equipamentos, etc."
                    >{{ old('descricao_instalacoes', $pae->descricao_instalacoes ?? '') }}</textarea>

                    <div class="mt-2 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-[11px] text-rose-800">
                        O PAE incluirá procedimentos para incêndio, acidentes, evacuação e primeiros socorros,
                        conforme as informações fornecidas acima.
                    </div>
                </section>

                {{-- Footer --}}
                <div class="pt-4 border-t border-slate-100 mt-4">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl
                                   bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700">
                        {{ !empty($isEdit) ? 'Salvar alterações' : 'Criar Tarefa PAE' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
