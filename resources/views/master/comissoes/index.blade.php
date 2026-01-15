@extends('layouts.master')
@section('title', 'Parametrização de Comissões')

@section('content')
    @php $isBulkUpdate = (bool) old('bulk_update'); @endphp

    <div class="w-full mx-auto px-4 md:px-6 xl:px-8 py-6 space-y-6">
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <a href="{{ route('master.dashboard') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                    Voltar ao Painel
                </a>
                <h1 class="text-2xl font-semibold text-slate-900">Parametrização de Comissões</h1>
                <p class="text-slate-500 text-sm mt-1">Defina percentuais por serviço e vigências para o cálculo automático.</p>
            </div>
        </div>

        @if (session('ok'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any() && !$isBulkUpdate)
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm space-y-1">
                <p class="font-semibold">Houve um problema ao salvar a regra.</p>
                <ul class="list-disc list-inside text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($errors->any() && $isBulkUpdate)
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm space-y-1">
                <p class="font-semibold">Houve um problema ao salvar as regras.</p>
                <p class="text-xs">Revise os campos destacados e tente novamente.</p>
            </div>
        @endif

        <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 space-y-3">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-semibold">?</div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Como funciona a vigência</h3>
                        <p class="text-xs text-slate-500">Aplicamos a regra com <strong>vigência início &lt;= data de conclusão</strong> e <strong>vigência fim em branco ou &gt;= data</strong>. A mais recente vence.</p>
                    </div>
                </div>
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-xs text-slate-700 leading-relaxed">
                    <p class="font-semibold text-slate-800 mb-1">Exemplo prático</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>01/01/2025 a 31/01/2025 · ASO · 5%</li>
                        <li>01/02/2025 em diante · ASO · 6%</li>
                    </ul>
                    <p class="mt-2">Se concluir em 15/01/2025, aplica 5%. Em 10/02/2025, aplica 6%.</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 space-y-3">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-semibold">i</div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Sem regra vigente = comissão zerada</h3>
                        <p class="text-xs text-slate-500">Se não houver regra para a data, geramos comissão com 0% para manter rastreabilidade. Mantenha sempre uma regra “em aberto”.</p>
                    </div>
                </div>
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-xs text-slate-700 leading-relaxed">
                    <p class="font-semibold text-slate-800 mb-1">Boas práticas</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Evite sobreposição de vigências no mesmo serviço.</li>
                        <li>Use o campo “Fim” apenas quando houver troca planejada.</li>
                        <li>Desative regras antigas para preservar histórico.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Nova regra de comissão</h2>
                    <p class="text-[11px] text-slate-500">Escolha o serviço, percentual e vigência. Você pode ter várias regras por serviço.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('master.comissoes.store') }}" class="space-y-3">
                @csrf
                <div class="grid md:grid-cols-5 gap-3 text-sm">
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Serviço</label>
                        <select name="servico_id" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                            <option value="">Selecione</option>
                            @foreach ($servicos as $servico)
                                <option value="{{ $servico->id }}" @selected(old('servico_id') == $servico->id)>
                                    {{ $servico->nome }}{{ $servico->ativo ? '' : ' (inativo)' }}
                                </option>
                            @endforeach
                        </select>
                        @if (!$isBulkUpdate)
                            @error('servico_id')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Percentual (%)</label>
                        <input type="number" name="percentual" step="0.01" min="0" max="100" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                               value="{{ old('percentual') }}" placeholder="Ex: 5,00">
                        @if (!$isBulkUpdate)
                            @error('percentual')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Vigência início</label>
                        <input type="date" name="vigencia_inicio" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                               value="{{ old('vigencia_inicio', now()->toDateString()) }}">
                        @if (!$isBulkUpdate)
                            @error('vigencia_inicio')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Vigência fim (opcional)</label>
                        <input type="date" name="vigencia_fim" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                               value="{{ old('vigencia_fim') }}">
                        @if (!$isBulkUpdate)
                            @error('vigencia_fim')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3 text-sm">
                    <div class="flex items-center gap-2 text-slate-700">
                        <input type="hidden" name="ativo" value="0">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="ativo" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   {{ old('ativo', '1') === '1' ? 'checked' : '' }}>
                            Ativo
                        </label>
                    </div>
                    <button class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                        Salvar regra
                    </button>
                </div>
            </form>
        </div>

        @php $totalRegras = $regrasPorServico?->flatten(1)->count() ?? 0; @endphp

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Regras por serviço</h2>
                    <p class="text-[11px] text-slate-500">Edite ou exclua regras já cadastradas. A lista respeita a empresa logada.</p>
                </div>
                <span class="text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-full">{{ $totalRegras }} regra(s)</span>
            </div>

            <form method="POST" action="{{ route('master.comissoes.bulk') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="bulk_update" value="1">
                @forelse ($servicos as $servico)
                    @php $regras = $regrasPorServico[$servico->id] ?? collect(); @endphp
                    <div class="border border-slate-100 rounded-xl p-4 space-y-3 bg-slate-50/60">
                        <div class="space-y-3">
                            @forelse ($regras as $regra)
                                @php
                                    $percentual = old('regras.'.$regra->id.'.percentual', $regra->percentual);
                                    $vigenciaInicio = old('regras.'.$regra->id.'.vigencia_inicio', optional($regra->vigencia_inicio)->toDateString());
                                    $vigenciaFim = old('regras.'.$regra->id.'.vigencia_fim', optional($regra->vigencia_fim)->toDateString());
                                @endphp

                                <div class="bg-white border border-slate-100 rounded-lg p-3">
                                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 text-sm items-end">
                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600">Serviço</label>
                                            <div class="h-10 flex items-center rounded-xl border border-slate-200 px-3 py-2 bg-slate-50 text-slate-700">
                                                {{ $servico->nome }}
                                            </div>
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600">Comissão (%)</label>
                                            <input type="number" name="regras[{{ $regra->id }}][percentual]" step="0.01" min="0" max="100"
                                                   class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                                   value="{{ $percentual }}">
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600">Vigência início</label>
                                            <input type="date" name="regras[{{ $regra->id }}][vigencia_inicio]"
                                                   class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                                   value="{{ $vigenciaInicio }}">
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600">Vigência fim</label>
                                            <input type="date" name="regras[{{ $regra->id }}][vigencia_fim]"
                                                   class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                                   value="{{ $vigenciaFim }}">
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-white border border-slate-100 rounded-lg p-3">
                                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 text-sm items-end">
                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600">Serviço</label>
                                            <div class="h-10 flex items-center rounded-xl border border-slate-200 px-3 py-2 bg-slate-50 text-slate-700">
                                                {{ $servico->nome }}
                                            </div>
                                        </div>
                                        <div class="md:col-span-4 flex items-center text-slate-500">
                                            Nenhuma regra cadastrada para este serviço.
                                        </div>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-600">Nenhum serviço encontrado para esta empresa.</div>
                @endforelse
                <div class="flex items-center justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
