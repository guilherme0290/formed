@extends('layouts.comercial')
@section('title', $clausula->exists ? 'Editar Cláusula' : 'Nova Cláusula')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">{{ $clausula->exists ? 'Editar cláusula' : 'Nova cláusula' }}</h1>
            <p class="text-xs text-slate-500">Placeholders disponíveis: <code>@{{CONTRATANTE_RAZAO}}</code>, <code>@{{CONTRATADA_RAZAO}}</code>, <code>@{{DATA_HOJE}}</code>.</p>
            <p class="text-xs text-slate-500 mt-1">O título da cláusula será exibido automaticamente no contrato. Não use título no conteúdo.</p>
        </div>

        <form method="POST" action="{{ $clausula->exists ? route('comercial.contratos.clausulas.update', $clausula) : route('comercial.contratos.clausulas.store') }}"
              class="bg-white border border-slate-200 rounded-xl p-4 space-y-4">
            @csrf
            @if($clausula->exists)
                @method('PUT')
            @endif

            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo serviço</label>
                    <select name="servico_tipo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                        @foreach(($serviceTypes ?? []) as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" @selected(old('servico_tipo', $clausula->servico_tipo ?? 'GERAL') === $typeKey)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $clausula->slug) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Título</label>
                    <input type="text" name="titulo" value="{{ old('titulo', $clausula->titulo) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Ordem</label>
                    <input type="number" min="0" name="ordem" value="{{ old('ordem', $clausula->ordem ?? 0) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input id="ativo" type="checkbox" name="ativo" value="1" {{ old('ativo', $clausula->ativo ?? true) ? 'checked' : '' }}>
                    <label for="ativo" class="text-sm text-slate-700">Cláusula ativa</label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Conteúdo da cláusula</label>
                <textarea name="content_text" rows="8" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Escreva o conteúdo em texto simples (um parágrafo por linha).">{{ old('content_text', $contentText ?? '') }}</textarea>
                @error('content_text')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input id="editar_html" type="checkbox" name="editar_html" value="1" @checked(old('editar_html'))>
                    Mostrar editor HTML (avançado)
                </label>
                <div id="html_advanced_panel" class="{{ old('editar_html') ? '' : 'hidden' }} mt-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">HTML da cláusula</label>
                    <textarea name="html_template" rows="12" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">{{ old('html_template', $clausula->html_template) }}</textarea>
                </div>
                @error('html_template')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Salvar</button>
                <a href="{{ route('comercial.contratos.clausulas.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50">Voltar</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkbox = document.getElementById('editar_html');
            const panel = document.getElementById('html_advanced_panel');
            if (!checkbox || !panel) return;

            checkbox.addEventListener('change', function () {
                panel.classList.toggle('hidden', !checkbox.checked);
            });
        });
    </script>
@endsection
