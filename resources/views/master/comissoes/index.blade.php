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

        <div class="bg-blue-50/60 rounded-2xl border border-blue-100 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Nova regra de comissão</h2>
                    <p class="text-[11px] text-slate-500">Escolha o serviço, percentual e vigência. Você pode ter várias regras por serviço.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('master.comissoes.store') }}" class="space-y-3">
                @csrf
                <x-toggle-ativo
                    name="ativo"
                    :checked="old('ativo', '1') === '1'"
                    on-label="Ativo"
                    off-label="Inativo"
                    text-class="text-sm text-slate-700"
                />
                <div class="hidden md:grid grid-cols-5 gap-3 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
                    <div class="col-span-2 pl-3">Serviço</div>
                    <div class="pl-3">Percentual (%)</div>
                    <div class="pl-3">Vigência início</div>
                    <div class="pl-3">Vigência fim</div>
                </div>
                <div class="grid md:grid-cols-5 gap-3 text-sm">
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-600 md:sr-only">Serviço</label>
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
                        <label class="text-xs font-semibold text-slate-600 md:sr-only">Percentual (%)</label>
                        <input type="number" name="percentual" step="0.01" min="0" max="100" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                               value="{{ old('percentual') }}" placeholder="Ex: 5,00">
                        @if (!$isBulkUpdate)
                            @error('percentual')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600 md:sr-only">Vigência início</label>
                        <input type="date" name="vigencia_inicio" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                               value="{{ old('vigencia_inicio', now()->toDateString()) }}">
                        @if (!$isBulkUpdate)
                            @error('vigencia_inicio')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600 md:sr-only">Vigência fim (opcional)</label>
                        <input type="date" name="vigencia_fim" class="w-full rounded-xl border border-slate-200 px-3 py-2"
                               value="{{ old('vigencia_fim') }}">
                        @if (!$isBulkUpdate)
                            @error('vigencia_fim')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 text-sm">
                    <button class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                        Salvar regra
                    </button>
                </div>
            </form>
        </div>

        @php $totalRegras = $regrasPorServico?->flatten(1)->count() ?? 0; @endphp

        <div class="bg-blue-50/60 rounded-2xl border border-blue-100 shadow-sm p-5 space-y-4">
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
                <div class="hidden md:grid grid-cols-5 gap-3 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 rounded-lg mx-1 px-3 py-2">
                    <div class="pl-3">Serviço</div>
                    <div class="pl-3">Comissão (%)</div>
                    <div class="pl-3">Vigência início</div>
                    <div class="pl-3">Vigência fim</div>
                    <div class="text-right pr-3">Ações</div>
                </div>
                @forelse ($servicos as $servico)
                    @php $regras = $regrasPorServico[$servico->id] ?? collect(); @endphp
                    @if ($regras->isNotEmpty())
                        <div class="rounded-xl p-3 space-y-1">
                            <div class="space-y-1">
                                @foreach ($regras as $regra)
                                @php
                                    $percentual = old('regras.'.$regra->id.'.percentual', $regra->percentual);
                                    $vigenciaInicio = old('regras.'.$regra->id.'.vigencia_inicio', optional($regra->vigencia_inicio)->toDateString());
                                    $vigenciaFim = old('regras.'.$regra->id.'.vigencia_fim', optional($regra->vigencia_fim)->toDateString());
                                @endphp

                                <div class="bg-white border border-slate-100 rounded-lg p-3">
                                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 text-sm items-end">
                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600 md:sr-only">Serviço</label>
                                            <div class="h-10 flex items-center rounded-xl border border-slate-200 px-3 py-2 bg-white text-slate-700">
                                                {{ $servico->nome }}
                                            </div>
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600 md:sr-only">Comissão (%)</label>
                                            <input type="number" name="regras[{{ $regra->id }}][percentual]" step="0.01" min="0" max="100"
                                                   class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                                   value="{{ $percentual }}">
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600 md:sr-only">Vigência início</label>
                                            <input type="date" name="regras[{{ $regra->id }}][vigencia_inicio]"
                                                   class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                                   value="{{ $vigenciaInicio }}">
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600 md:sr-only">Vigência fim</label>
                                            <input type="date" name="regras[{{ $regra->id }}][vigencia_fim]"
                                                   class="w-full rounded-xl border border-slate-200 px-3 py-2"
                                                   value="{{ $vigenciaFim }}">
                                        </div>

                                        <div class="flex md:justify-end">
                                            <button type="submit"
                                                    form="delete-regra-{{ $regra->id }}"
                                                    class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                Excluir
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-sm text-slate-600">Nenhum serviço encontrado para esta empresa.</div>
                @endforelse
                <div class="flex items-center justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        Salvar
                    </button>
                </div>
            </form>
            @foreach ($servicos as $servico)
                @php $regras = $regrasPorServico[$servico->id] ?? collect(); @endphp
                @foreach ($regras as $regra)
                    <form id="delete-regra-{{ $regra->id }}"
                          method="POST"
                          action="{{ route('master.comissoes.destroy', $regra) }}"
                          class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endforeach
        </div>
    </div>
@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.flatpickr) {
                return;
            }
            if (flatpickr.l10ns && flatpickr.l10ns.pt) {
                flatpickr.localize(flatpickr.l10ns.pt);
            }
            function maskBrDate(value) {
                const digits = (value || '').replace(/\D+/g, '').slice(0, 8);
                if (digits.length <= 2) return digits;
                if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
                return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
            }
            document.querySelectorAll('input[type="date"]').forEach((input) => {
                if (input.dataset.fpBound) return;
                input.dataset.fpBound = '1';
                const fp = flatpickr(input, {
                    allowInput: true,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    altInputClass: input.className,
                });
                if (fp && fp.altInput) {
                    fp.altInput.addEventListener('input', () => {
                        fp.altInput.value = maskBrDate(fp.altInput.value);
                    });
                }
            });
        });
    </script>
@endpush@endsection

