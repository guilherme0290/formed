@extends('layouts.master')
@section('title', $isEdit ? 'Editar Unidade Clínica' : 'Nova Unidade Clínica')

@section('content')
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div>
            <a href="{{ route('master.empresa.edit', ['tab' => 'unidades']) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                ← Voltar para unidades
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b bg-indigo-700 text-white">
                <h1 class="text-lg font-semibold">
                    {{ $isEdit ? 'Editar Unidade Clínica' : 'Cadastrar Unidade Clínica' }}
                </h1>
                <p class="text-xs text-indigo-100 mt-1">Informações básicas da unidade vinculada à empresa.</p>
            </div>

            <div class="p-6 space-y-6">
                @if ($errors->any())
                    <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
                        <div class="font-semibold mb-1">Verifique os campos</div>
                        <ul class="list-disc ml-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST"
                      action="{{ $isEdit ? route('master.empresa.unidades.update', $unidade) : route('master.empresa.unidades.store') }}"
                      class="space-y-5">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <x-toggle-ativo
                        name="ativo"
                        :checked="(bool) old('ativo', $unidade->ativo ?? true)"
                        on-label="Unidade ativa"
                        off-label="Unidade inativa"
                        text-class="text-sm font-medium text-slate-700"
                    />

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Nome *</label>
                            <input type="text" name="nome" value="{{ old('nome', $unidade->nome) }}" required
                                   class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Telefone</label>
                            <input type="text" name="telefone" value="{{ old('telefone', $unidade->telefone) }}"
                                   class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 js-telefone">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Endereço</label>
                        <input type="text" name="endereco" value="{{ old('endereco', $unidade->endereco) }}"
                               class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                    </div>



                    <div class="pt-4 flex flex-wrap items-center justify-end gap-2">
                        <a href="{{ route('master.empresa.edit', ['tab' => 'unidades']) }}"
                           class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm">
                            {{ $isEdit ? 'Salvar alterações' : 'Cadastrar unidade' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(function () {
            $('.js-telefone').mask('(00) 00000-0000');
        });
    </script>
@endsection
