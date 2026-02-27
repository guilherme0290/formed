@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')

@section('pageTitle', 'APR - Análise Preliminar de Riscos')

@push('styles')
    <style>
        .apr-theme {
            --apr-primary: #0f3d5e;
            --apr-primary-hover: #0b2f48;
            --apr-success: #0f8a5f;
            --apr-success-hover: #0b6a49;
            --apr-neutral-bg: #f8fafc;
            --apr-neutral-border: #cbd5e1;
            --apr-text: #0f172a;
            --apr-text-muted: #475569;
        }

        .apr-theme .apr-step-btn {
            border: 1px solid var(--apr-neutral-border);
            color: var(--apr-text-muted);
            background: #fff;
        }

        .apr-theme .apr-step-btn.is-current {
            color: #fff;
            background: var(--apr-primary);
            border-color: var(--apr-primary);
            box-shadow: 0 4px 10px rgba(15, 61, 94, 0.2);
        }

        .apr-theme .apr-step-btn.is-done {
            color: #065f46;
            background: #ecfdf5;
            border-color: #6ee7b7;
        }

        .apr-theme input:focus,
        .apr-theme select:focus,
        .apr-theme textarea:focus {
            border-color: var(--apr-primary) !important;
            box-shadow: 0 0 0 3px rgba(15, 61, 94, 0.18) !important;
            outline: none !important;
        }

        .apr-theme .apr-actions {
            position: sticky;
            bottom: 0;
            z-index: 20;
            background: linear-gradient(to top, #fff 65%, rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(4px);
        }

        .apr-theme .apr-btn-primary {
            background: var(--apr-primary);
            color: #fff;
        }

        .apr-theme .apr-btn-primary:hover {
            background: var(--apr-primary-hover);
        }

        .apr-theme .apr-btn-success {
            background: var(--apr-success);
            color: #fff;
        }

        .apr-theme .apr-btn-success:hover {
            background: var(--apr-success-hover);
        }

        .apr-theme .apr-required-label {
            position: relative;
        }

        .apr-theme .apr-required-label::after {
            content: '';
            display: inline-block;
            width: 0.3rem;
            height: 0.3rem;
            margin-left: 0.35rem;
            border-radius: 9999px;
            background: #475569;
            vertical-align: middle;
            opacity: 0.9;
        }

        .apr-theme .apr-required-legend {
            color: #64748b;
            font-size: 11px;
        }

        .apr-theme .apr-inline-error {
            margin-top: 0.25rem;
            color: #dc2626;
            font-size: 12px;
            line-height: 1.2;
        }
    </style>
@endpush

@section('content')
    @php
        $origem = request()->query('origem');

        $isEdit = !empty($isEdit);
        $aprStatus = old('status', $apr?->status ?? 'rascunho');
        $wizardStep = (int) old('wizard_step', 0);

        if ($errors->any()) {
            if ($errors->hasAny([
                'contratante_razao_social',
                'contratante_cnpj',
                'contratante_responsavel_nome',
                'contratante_telefone',
                'contratante_email',
                'obra_nome',
                'obra_endereco',
                'obra_cidade',
                'obra_uf',
                'obra_cep',
                'obra_area_setor',
            ])) {
                $wizardStep = 0;
            } elseif ($errors->hasAny([
                'atividade_descricao',
                'atividade_data_inicio',
                'atividade_data_termino_prevista',
            ])) {
                $wizardStep = 1;
            } elseif ($errors->hasAny(['etapas', 'etapas.*.descricao'])) {
                $wizardStep = 2;
            } elseif ($errors->hasAny(['equipe', 'equipe.*.nome', 'equipe.*.funcao'])) {
                $wizardStep = 3;
            } elseif ($errors->hasAny(['epis', 'epis.*.descricao'])) {
                $wizardStep = 4;
            } else {
                $wizardStep = 5;
            }
        }

        $valor = fn (string $key, $fallback = '') => old($key, $fallback);

        $etapasDefault = [];
        if (is_array(old('etapas'))) {
            $etapasDefault = old('etapas');
        } elseif (!empty($apr?->etapas_json) && is_array($apr?->etapas_json)) {
            $etapasDefault = $apr?->etapas_json;
        } elseif (!empty($apr?->etapas_atividade)) {
            $etapasDefault = collect(preg_split('/\r\n|\r|\n/', (string) $apr?->etapas_atividade))
                ->map(fn ($linha) => ['descricao' => trim((string) $linha)])
                ->filter(fn ($item) => !empty($item['descricao']))
                ->values()
                ->all();
        }
        if (empty($etapasDefault)) {
            $etapasDefault = [['descricao' => '']];
        }

        $equipeDefault = [];
        if (is_array(old('equipe'))) {
            $equipeDefault = old('equipe');
        } elseif (!empty($apr?->equipe_json) && is_array($apr?->equipe_json)) {
            $equipeDefault = $apr?->equipe_json;
        } elseif (!empty($apr?->funcoes_envolvidas)) {
            $equipeDefault = collect(explode(';', (string) $apr?->funcoes_envolvidas))
                ->map(function ($item) {
                    $partes = explode('-', (string) $item, 2);
                    return [
                        'nome' => trim((string) ($partes[0] ?? '')),
                        'funcao' => trim((string) ($partes[1] ?? '')),
                    ];
                })
                ->filter(fn ($item) => !empty($item['nome']) || !empty($item['funcao']))
                ->values()
                ->all();
        }
        if (empty($equipeDefault)) {
            $equipeDefault = [['nome' => '', 'funcao' => '']];
        }

        $episDefault = [];
        if (is_array(old('epis'))) {
            $episDefault = old('epis');
        } elseif (!empty($apr?->epis_json) && is_array($apr?->epis_json)) {
            $episDefault = $apr?->epis_json;
        }
        $episDefault = collect($episDefault)
            ->map(function ($epi) {
                $tipo = $epi['tipo'] ?? 'epi';
                if (!in_array($tipo, ['epi', 'maquina'], true)) {
                    $tipo = 'epi';
                }
                return [
                    'tipo' => $tipo,
                    'descricao' => $epi['descricao'] ?? '',
                ];
            })
            ->values()
            ->all();
        if (empty($episDefault)) {
            $episDefault = [['tipo' => 'epi', 'descricao' => '']];
        }

        $funcoesColaboradores = collect($colaboradores ?? [])
            ->pluck('funcao')
            ->map(fn ($f) => trim((string) $f))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $obraEnderecoFallback = $apr?->obra_endereco ?? '';
        $obraCidadeFallback = $apr?->obra_cidade ?? '';
        $obraUfFallback = $apr?->obra_uf ?? '';
    @endphp

    <div class="apr-theme w-full px-2 sm:px-3 md:px-4 xl:px-5 py-4 md:py-6">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ $origem === 'cliente' ? route('cliente.dashboard') : route('operacional.kanban.servicos', $cliente) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                ← Voltar
            </a>
        </div>

        <div class="w-full bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-4 sm:px-5 md:px-6 py-4 bg-gradient-to-r from-slate-900 to-slate-700 text-white">
                <h1 class="text-lg font-semibold">Nova APR {{ $isEdit ? '(Editar)' : '' }}</h1>
                <p class="text-xs text-white/80 mt-1">Análise Preliminar de Riscos</p>
            </div>

            <div class="px-4 sm:px-5 md:px-6 pt-4 border-b border-slate-100">
                <div class="grid grid-cols-3 md:grid-cols-6 gap-2 text-[11px] font-semibold pb-3" id="apr-step-nav">
                    <button type="button" class="apr-step-btn text-left px-2 py-1 rounded-md is-current" data-step="0">Contratante</button>
                    <button type="button" class="apr-step-btn text-left px-2 py-1 rounded-md" data-step="1">Atividade</button>
                    <button type="button" class="apr-step-btn text-left px-2 py-1 rounded-md" data-step="2">Etapas</button>
                    <button type="button" class="apr-step-btn text-left px-2 py-1 rounded-md" data-step="3">Equipe</button>
                    <button type="button" class="apr-step-btn text-left px-2 py-1 rounded-md" data-step="4">EPIs</button>
                    <button type="button" class="apr-step-btn text-left px-2 py-1 rounded-md" data-step="5">Aprovação</button>
                </div>
                <div class="pb-4">
                    <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1">
                        <span id="apr-step-label">Passo 1 de 6</span>
                        <span id="apr-step-percent">17%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div id="apr-step-progress" class="h-full bg-slate-700 transition-all duration-300" style="width: 17%"></div>
                    </div>
                </div>
            </div>

            <form method="POST"
                  action="{{ $isEdit && $apr ? route('operacional.apr.update', ['apr' => $apr, 'origem' => $origem]) : route('operacional.apr.store', ['cliente' => $cliente, 'origem' => $origem]) }}"
                  class="px-4 sm:px-5 md:px-6 py-5 md:py-6 space-y-6" id="apr-form">
                @csrf
                @if($isEdit && $apr)
                    @method('PUT')
                @endif

                <input type="hidden" name="acao" id="apr-acao" value="aprovar">
                <input type="hidden" name="wizard_step" id="wizard-step" value="{{ $wizardStep }}">

                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700 mb-2">
                        <ul class="list-disc ms-4">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="apr-required-legend">Campos com • são obrigatórios para aprovação da APR.</p>

                <section class="apr-step" data-step="0">
                    <h2 class="text-base font-semibold text-slate-800">Dados da Contratante</h2>
                    <p class="text-xs text-slate-500 mt-1">Informações da empresa responsável pela obra.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="md:col-span-2">
                            <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Razão Social / Empresa</label>
                            <input type="text" name="contratante_razao_social" value="{{ $valor('contratante_razao_social', $apr?->contratante_razao_social ?? '') }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div>
                            <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">CNPJ</label>
                            <input type="text" id="contratante_cnpj" name="contratante_cnpj" value="{{ $valor('contratante_cnpj', $apr?->contratante_cnpj ?? '') }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" inputmode="numeric" maxlength="18">
                            <p id="contratante_cnpj_error" class="apr-inline-error hidden"></p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Nome do Responsável</label>
                            <input type="text" name="contratante_responsavel_nome" value="{{ $valor('contratante_responsavel_nome', $apr?->contratante_responsavel_nome ?? '') }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Telefone</label>
                            <input type="text" id="contratante_telefone" name="contratante_telefone" value="{{ $valor('contratante_telefone', $apr?->contratante_telefone ?? '') }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" inputmode="numeric" maxlength="15">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">E-mail</label>
                            <input type="email" id="contratante_email" name="contratante_email" value="{{ $valor('contratante_email', $apr?->contratante_email ?? '') }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" maxlength="255">
                        </div>
                    </div>

                    <hr class="my-5 border-slate-100">

                    <h2 class="text-base font-semibold text-slate-800">Local da Atividade</h2>
                    <p class="text-xs text-slate-500 mt-1">Dados do local onde será executada a atividade.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="md:col-span-2">
                            <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Nome da Obra</label>
                            <input type="text" name="obra_nome" value="{{ $valor('obra_nome', $apr?->obra_nome ?? '') }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">CEP</label>
                            <input type="text" id="obra_cep" name="obra_cep" value="{{ $valor('obra_cep', $apr?->obra_cep ?? '') }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" inputmode="numeric" maxlength="9">
                        </div>

                        <div class="md:col-span-2">
                            <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Endereço Completo</label>
                            <input type="text" id="obra_endereco" name="obra_endereco" value="{{ $valor('obra_endereco', $obraEnderecoFallback) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div>
                            <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">UF</label>
                            <select name="obra_uf" id="obra_uf"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                <option value="">Selecione...</option>
                                @foreach(($estados ?? collect()) as $estado)
                                    <option value="{{ $estado->uf }}"
                                        {{ strtoupper($valor('obra_uf', $obraUfFallback)) === $estado->uf ? 'selected' : '' }}>
                                        {{ $estado->uf }} - {{ $estado->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Cidade</label>
                            <select name="obra_cidade" id="obra_cidade"
                                    class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                <option value="">Selecione...</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Área/Setor</label>
                            <input type="text" name="obra_area_setor" value="{{ $valor('obra_area_setor', $apr?->obra_area_setor ?? '') }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>
                    </div>
                </section>

                <section class="apr-step hidden" data-step="1">
                    <h2 class="text-base font-semibold text-slate-800">Dados da Atividade</h2>
                    <p class="text-xs text-slate-500 mt-1">Descreva a atividade principal.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Descrição detalhada</label>
                            <textarea name="atividade_descricao" rows="4" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">{{ $valor('atividade_descricao', $apr?->atividade_descricao ?? '') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Data início</label>
                                <div class="relative">
                                    <input type="text"
                                           inputmode="numeric"
                                           placeholder="dd/mm/aaaa"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 pr-10 js-date-text"
                                           data-date-target="atividade_data_inicio">
                                    <button type="button"
                                            class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                            data-date-target="atividade_data_inicio"
                                            aria-label="Abrir calendário">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
                                        </svg>
                                    </button>
                                    <input type="date"
                                           id="atividade_data_inicio"
                                           name="atividade_data_inicio"
                                           value="{{ $valor('atividade_data_inicio', optional($apr?->atividade_data_inicio)->format('Y-m-d')) }}"
                                           class="absolute right-0 top-0 h-full w-10 opacity-0 pointer-events-none js-date-hidden">
                                </div>
                            </div>

                            <div>
                                <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Previsão término</label>
                                <div class="relative">
                                    <input type="text"
                                           inputmode="numeric"
                                           placeholder="dd/mm/aaaa"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 pr-10 js-date-text"
                                           data-date-target="atividade_data_termino_prevista">
                                    <button type="button"
                                            class="absolute right-0 top-0 h-full w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 date-picker-btn z-10"
                                            data-date-target="atividade_data_termino_prevista"
                                            aria-label="Abrir calendário">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 2 0v1zm15 8H2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V10z"/>
                                        </svg>
                                    </button>
                                    <input type="date"
                                           id="atividade_data_termino_prevista"
                                           name="atividade_data_termino_prevista"
                                           value="{{ $valor('atividade_data_termino_prevista', optional($apr?->atividade_data_termino_prevista)->format('Y-m-d')) }}"
                                           class="absolute right-0 top-0 h-full w-10 opacity-0 pointer-events-none js-date-hidden">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="apr-step hidden" data-step="2">
                    <h2 class="text-base font-semibold text-slate-800">Passo a Passo da Atividade</h2>
                    <p class="text-xs text-slate-500 mt-1">Liste as etapas sequenciais da atividade.</p>

                    <div class="grid grid-cols-1 md:grid-cols-[1fr,auto] gap-3 mt-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Template de etapas</label>
                            <select id="apr-etapas-template" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                <option value="">Selecione um modelo...</option>
                                <option value="escavacao">Escavação / Fundação</option>
                                <option value="altura">Trabalho em Altura (NR-35)</option>
                                <option value="eletricidade">Serviço em Eletricidade (NR-10)</option>
                                <option value="confinado">Espaço Confinado (NR-33)</option>
                                <option value="demolicao">Demolição / Reforma</option>
                                <option value="icamento">Movimentação de Carga / Içamento</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="button" id="apr-apply-etapas-template"
                                    class="inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                Aplicar template
                            </button>
                        </div>
                    </div>

                    <div id="etapas-list" class="space-y-3 mt-4">
                        @foreach($etapasDefault as $idx => $etapa)
                            <div class="etapa-item rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-xs font-semibold text-slate-600">Etapa <span class="etapa-num">{{ $idx + 1 }}</span></span>
                                    <button type="button" class="remove-etapa text-xs text-red-600 hover:text-red-700">Remover</button>
                                </div>
                                <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Descrição</label>
                                <input type="text" name="etapas[{{ $idx }}][descricao]" value="{{ $etapa['descricao'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" placeholder="Descreva a etapa">
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-etapa" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        + Adicionar etapa
                    </button>
                    <p class="mt-2 text-[11px] text-slate-500">Regra: para aprovar APR, informe pelo menos 3 etapas.</p>
                </section>

                <section class="apr-step hidden" data-step="3">
                    <h2 class="text-base font-semibold text-slate-800">Equipe Envolvida</h2>
                    <p class="text-xs text-slate-500 mt-1">Cadastre os trabalhadores que participarão da atividade.</p>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Adicionar equipe por função</label>
                        <div class="grid grid-cols-1 md:grid-cols-[1fr,auto] gap-2">
                            <select id="apr-equipe-funcao-select" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                <option value="">Selecione a função...</option>
                                @foreach($funcoesColaboradores as $funcaoColaborador)
                                    <option value="{{ $funcaoColaborador }}">{{ $funcaoColaborador }}</option>
                                @endforeach
                            </select>
                            <button type="button"
                                    id="apr-equipe-funcao-aplicar"
                                    class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-900">
                                Preencher equipe
                            </button>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Ao escolher a função, os colaboradores dessa função serão adicionados automaticamente e organizados por grupo.</p>
                    </div>

                    <div id="equipe-list" class="space-y-3 mt-4">
                        @foreach($equipeDefault as $idx => $trabalhador)
                            <div class="equipe-item rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-xs font-semibold text-slate-600">Trabalhador <span class="equipe-num">{{ $idx + 1 }}</span></span>
                                    <button type="button" class="remove-equipe text-xs text-red-600 hover:text-red-700">Remover</button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Nome</label>
                                        <input type="text"
                                               name="equipe[{{ $idx }}][nome]"
                                               value="{{ $trabalhador['nome'] ?? '' }}"
                                               class="js-equipe-nome w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                               list="apr-colaboradores-list"
                                               autocomplete="off">
                                    </div>

                                    <div>
                                        <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Função</label>
                                        <input type="text"
                                               name="equipe[{{ $idx }}][funcao]"
                                               value="{{ $trabalhador['funcao'] ?? '' }}"
                                               class="js-equipe-funcao w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-equipe" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        + Adicionar trabalhador
                    </button>

                    <datalist id="apr-colaboradores-list">
                        @foreach(($colaboradores ?? []) as $colaborador)
                            <option value="{{ $colaborador['nome'] }}"></option>
                        @endforeach
                    </datalist>
                </section>

                <section class="apr-step hidden" data-step="4">
                    <h2 class="text-base font-semibold text-slate-800">EPIs e Máquinas</h2>
                    <p class="text-xs text-slate-500 mt-1">Informe os EPIs e equipamentos necessários na operação.</p>

                    <div class="grid grid-cols-1 md:grid-cols-[1fr,auto,auto] gap-2 mt-4">
                        <select id="apr-epis-template" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            <option value="">Template por atividade...</option>
                            <option value="escavacao">Escavação / Fundação</option>
                            <option value="altura">Trabalho em Altura (NR-35)</option>
                            <option value="eletricidade">Serviço em Eletricidade (NR-10)</option>
                            <option value="confinado">Espaço Confinado (NR-33)</option>
                            <option value="demolicao">Demolição / Reforma</option>
                            <option value="icamento">Movimentação de Carga / Içamento</option>
                        </select>
                        <button type="button" id="apr-apply-epis-template"
                                class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-900">
                            Aplicar template
                        </button>

                    </div>

                    <div id="epis-list" class="space-y-3 mt-4">
                        @foreach($episDefault as $idx => $epi)
                            <div class="epi-item rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-xs font-semibold text-slate-600">EPI <span class="epi-num">{{ $idx + 1 }}</span></span>
                                    <button type="button" class="remove-epi text-xs text-red-600 hover:text-red-700">Remover</button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Tipo</label>
                                        <select name="epis[{{ $idx }}][tipo]" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                            <option value="epi" {{ ($epi['tipo'] ?? 'epi') === 'epi' ? 'selected' : '' }}>EPI</option>
                                            <option value="maquina" {{ ($epi['tipo'] ?? '') === 'maquina' ? 'selected' : '' }}>Máquina</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Descrição</label>
                                        <input type="text" name="epis[{{ $idx }}][descricao]" value="{{ $epi['descricao'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" placeholder="Ex: Capacete classe B">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-epi" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        + Adicionar EPI
                    </button>
                </section>

                <section class="apr-step hidden" data-step="5">
                    <h2 class="text-base font-semibold text-slate-800">Resumo e Aprovação</h2>
                    <p class="text-xs text-slate-500 mt-1">Revise as informações antes de concluir.</p>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 space-y-2" id="apr-resumo">
                        <p><b>Status atual:</b> {{ strtoupper((string) $aprStatus) }}</p>
                        <div class="pt-2">
                            <p class="font-semibold text-slate-800">Contratante e Local</p>
                            <p><b>Razão social:</b> <span data-resumo="contratante_razao_social">-</span></p>
                            <p><b>CNPJ:</b> <span data-resumo="contratante_cnpj">-</span></p>
                            <p><b>Responsável:</b> <span data-resumo="contratante_responsavel_nome">-</span></p>
                            <p><b>Telefone:</b> <span data-resumo="contratante_telefone">-</span></p>
                            <p><b>Email:</b> <span data-resumo="contratante_email">-</span></p>
                            <p><b>Obra:</b> <span data-resumo="obra_nome">-</span></p>
                            <p><b>Endereço:</b> <span data-resumo="obra_endereco">-</span></p>
                            <p><b>Cidade/UF:</b> <span data-resumo="obra_cidade_uf">-</span></p>
                            <p><b>CEP:</b> <span data-resumo="obra_cep">-</span></p>
                            <p><b>Área/Setor:</b> <span data-resumo="obra_area_setor">-</span></p>
                        </div>
                        <div class="pt-2">
                            <p class="font-semibold text-slate-800">Atividade</p>
                            <p><b>Descrição:</b> <span data-resumo="atividade_descricao">-</span></p>
                            <p><b>Período:</b> <span data-resumo="periodo">-</span></p>
                        </div>
                        <div class="pt-2">
                            <p class="font-semibold text-slate-800">Etapas da Atividade (<span data-resumo="total_etapas">0</span>)</p>
                            <ul class="list-disc ms-5 text-sm" data-resumo-list="etapas"><li>-</li></ul>
                        </div>
                        <div class="pt-2">
                            <p class="font-semibold text-slate-800">Equipe Envolvida (<span data-resumo="total_equipe">0</span>)</p>
                            <ul class="list-disc ms-5 text-sm" data-resumo-list="equipe"><li>-</li></ul>
                        </div>
                        <div class="pt-2">
                            <p class="font-semibold text-slate-800">EPIs e Máquinas (<span data-resumo="total_epis">0</span>)</p>
                            <ul class="list-disc ms-5 text-sm" data-resumo-list="epis"><li>-</li></ul>
                        </div>
                    </div>
                </section>

                <div class="apr-actions pt-4 border-t border-slate-200 mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <button type="button" id="apr-prev" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-500 text-xs font-medium hover:bg-slate-50 hover:text-slate-700">
                        ← Anterior
                    </button>

                    <div class="w-full md:w-auto md:flex-1 md:max-w-xl">
                        <button type="button" id="apr-next" class="inline-flex w-full items-center justify-center px-5 py-3 rounded-xl bg-slate-800 text-white text-sm font-bold shadow-md hover:bg-slate-900">
                            Próximo →
                        </button>
                        <button type="button" id="apr-submit" class="apr-btn-success hidden inline-flex w-full items-center justify-center px-5 py-3 rounded-xl text-sm font-bold shadow-md transition-colors">
                            Solicitar APR
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('apr-form');
            if (!form) return;

            const steps = Array.from(document.querySelectorAll('.apr-step'));
            const navButtons = Array.from(document.querySelectorAll('.apr-step-btn'));
            const btnPrev = document.getElementById('apr-prev');
            const btnNext = document.getElementById('apr-next');
            const btnSubmit = document.getElementById('apr-submit');
            const btnDraft = document.getElementById('apr-draft');
            const inputAcao = document.getElementById('apr-acao');
            const inputWizardStep = document.getElementById('wizard-step');
            const progressLabel = document.getElementById('apr-step-label');
            const progressPercent = document.getElementById('apr-step-percent');
            const progressBar = document.getElementById('apr-step-progress');
            const obraUfSelect = document.getElementById('obra_uf');
            const obraCidadeSelect = document.getElementById('obra_cidade');
            const cidadeInicial = @json($valor('obra_cidade', $obraCidadeFallback));
            const inputCnpj = document.getElementById('contratante_cnpj');
            const inputCnpjError = document.getElementById('contratante_cnpj_error');
            const inputTelefone = document.getElementById('contratante_telefone');
            const inputCep = document.getElementById('obra_cep');
            const inputEmail = document.getElementById('contratante_email');
            const inputObraEndereco = document.getElementById('obra_endereco');
            const btnClearEpis = document.getElementById('clear-epis');
            const etapasTemplateSelect = document.getElementById('apr-etapas-template');
            const btnApplyEtapasTemplate = document.getElementById('apr-apply-etapas-template');
            const episTemplateSelect = document.getElementById('apr-epis-template');
            const btnApplyEpisTemplate = document.getElementById('apr-apply-epis-template');
            const btnSuggestEpisEquipe = document.getElementById('apr-suggest-epis-equipe');
            const colaboradores = @json($colaboradores ?? []);
            const equipeFuncaoSelect = document.getElementById('apr-equipe-funcao-select');
            const equipeFuncaoAplicarBtn = document.getElementById('apr-equipe-funcao-aplicar');

            let currentStep = Number(inputWizardStep?.value || 0);
            let maxVisitedStep = currentStep;

            function setStep(step) {
                currentStep = Math.max(0, Math.min(step, steps.length - 1));

                steps.forEach((el, idx) => {
                    el.classList.toggle('hidden', idx !== currentStep);
                });

                navButtons.forEach((btn, idx) => {
                    const active = idx === currentStep;
                    const done = idx < currentStep;
                    btn.classList.toggle('is-current', active);
                    btn.classList.toggle('is-done', done);
                });

                btnPrev.style.visibility = currentStep === 0 ? 'hidden' : 'visible';
                btnNext.classList.toggle('hidden', currentStep === steps.length - 1);
                btnSubmit.classList.toggle('hidden', currentStep !== steps.length - 1);
                inputWizardStep.value = String(currentStep);

                const percentual = Math.round(((currentStep + 1) / steps.length) * 100);
                if (progressLabel) progressLabel.textContent = `Passo ${currentStep + 1} de ${steps.length}`;
                if (progressPercent) progressPercent.textContent = `${percentual}%`;
                if (progressBar) progressBar.style.width = `${percentual}%`;

                if (currentStep === steps.length - 1) {
                    preencherResumo();
                }
            }

            navButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const target = Number(btn.dataset.step || 0);
                    if (target <= maxVisitedStep) {
                        setStep(target);
                    }
                });
            });

            btnPrev.addEventListener('click', () => setStep(currentStep - 1));
            btnNext.addEventListener('click', () => {
                if (!validarStepAtual(currentStep)) {
                    return;
                }

                maxVisitedStep = Math.max(maxVisitedStep, currentStep + 1);
                setStep(currentStep + 1);
            });

            btnDraft?.addEventListener('click', function () {
                inputAcao.value = 'rascunho';
                form.submit();
            });

            btnSubmit.addEventListener('click', function () {
                if (!validarTudoParaAprovar()) {
                    return;
                }
                inputAcao.value = 'aprovar';
                form.submit();
            });

            function limparErrosStep(stepEl) {
                stepEl.querySelectorAll('.apr-erro-step').forEach((el) => el.remove());
                stepEl.querySelectorAll('.border-red-400').forEach((el) => el.classList.remove('border-red-400'));
            }

            function addErroCampo(stepEl, campo, mensagem) {
                if (campo) {
                    campo.classList.add('border-red-400');
                }
                const aviso = document.createElement('p');
                aviso.className = 'apr-erro-step mt-2 text-xs text-red-600';
                aviso.textContent = mensagem;
                stepEl.appendChild(aviso);
            }

            function possuiTexto(value) {
                return String(value || '').trim() !== '';
            }

            function normalizarTexto(txt) {
                return String(txt || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toUpperCase();
            }

            const mapaColaboradorFuncao = new Map(
                (Array.isArray(colaboradores) ? colaboradores : []).map((item) => [
                    normalizarTexto(item?.nome || ''),
                    String(item?.funcao || '').trim(),
                ])
            );

            function preencherFuncaoPorNome(nomeInput) {
                if (!nomeInput) return;
                const linha = nomeInput.closest('.equipe-item');
                if (!linha) return;

                const inputFuncao = linha.querySelector('input[name*="[funcao]"], input[data-field="funcao"]');
                if (!inputFuncao) return;

                const nomeNormalizado = normalizarTexto(nomeInput.value || '');
                const funcao = mapaColaboradorFuncao.get(nomeNormalizado);
                if (funcao) {
                    inputFuncao.value = funcao;
                }
            }

            function notify(message) {
                if (typeof window.uiAlert === 'function') {
                    window.uiAlert(message);
                    return;
                }
                window.alert(message);
            }

            function somenteDigitos(valor) {
                return String(valor || '').replace(/\D/g, '');
            }

            function mascararCnpj(valor) {
                const v = somenteDigitos(valor).slice(0, 14);
                if (v.length <= 2) return v;
                if (v.length <= 5) return `${v.slice(0, 2)}.${v.slice(2)}`;
                if (v.length <= 8) return `${v.slice(0, 2)}.${v.slice(2, 5)}.${v.slice(5)}`;
                if (v.length <= 12) return `${v.slice(0, 2)}.${v.slice(2, 5)}.${v.slice(5, 8)}/${v.slice(8)}`;
                return `${v.slice(0, 2)}.${v.slice(2, 5)}.${v.slice(5, 8)}/${v.slice(8, 12)}-${v.slice(12)}`;
            }

            function cnpjValido(cnpj) {
                const valor = somenteDigitos(cnpj);
                if (valor.length !== 14) return false;
                if (/^(\d)\1{13}$/.test(valor)) return false;

                let tamanho = 12;
                let numeros = valor.substring(0, tamanho);
                const digitos = valor.substring(tamanho);
                let soma = 0;
                let pos = tamanho - 7;

                for (let i = tamanho; i >= 1; i--) {
                    soma += Number(numeros.charAt(tamanho - i)) * pos--;
                    if (pos < 2) pos = 9;
                }

                let resultado = (soma % 11) < 2 ? 0 : 11 - (soma % 11);
                if (resultado !== Number(digitos.charAt(0))) return false;

                tamanho = 13;
                numeros = valor.substring(0, tamanho);
                soma = 0;
                pos = tamanho - 7;

                for (let i = tamanho; i >= 1; i--) {
                    soma += Number(numeros.charAt(tamanho - i)) * pos--;
                    if (pos < 2) pos = 9;
                }

                resultado = (soma % 11) < 2 ? 0 : 11 - (soma % 11);
                return resultado === Number(digitos.charAt(1));
            }

            function limparErroCnpj() {
                if (inputCnpj) {
                    inputCnpj.classList.remove('border-red-400', 'focus:border-red-500');
                    inputCnpj.setCustomValidity('');
                }
                if (inputCnpjError) {
                    inputCnpjError.textContent = '';
                    inputCnpjError.classList.add('hidden');
                }
            }

            function mostrarErroCnpj(mensagem) {
                if (inputCnpj) {
                    inputCnpj.classList.add('border-red-400');
                    inputCnpj.setCustomValidity(mensagem || 'CNPJ inválido');
                }
                if (inputCnpjError) {
                    inputCnpjError.textContent = mensagem || 'CNPJ inválido';
                    inputCnpjError.classList.remove('hidden');
                }
            }

            function validarCampoCnpj({ exigirPreenchimento = false } = {}) {
                if (!inputCnpj) return true;

                const cnpjLimpo = somenteDigitos(inputCnpj.value);

                if (cnpjLimpo === '') {
                    limparErroCnpj();
                    if (exigirPreenchimento) {
                        mostrarErroCnpj('Informe o CNPJ.');
                        return false;
                    }
                    return true;
                }

                if (cnpjLimpo.length !== 14 || !cnpjValido(cnpjLimpo)) {
                    mostrarErroCnpj('CNPJ inválido');
                    return false;
                }

                limparErroCnpj();
                return true;
            }

            function mascararTelefone(valor) {
                const v = somenteDigitos(valor).slice(0, 11);
                if (v.length <= 2) return v;
                if (v.length <= 6) return `(${v.slice(0, 2)}) ${v.slice(2)}`;
                if (v.length <= 10) return `(${v.slice(0, 2)}) ${v.slice(2, 6)}-${v.slice(6)}`;
                return `(${v.slice(0, 2)}) ${v.slice(2, 7)}-${v.slice(7)}`;
            }

            function mascararCep(valor) {
                const v = somenteDigitos(valor).slice(0, 8);
                if (v.length <= 5) return v;
                return `${v.slice(0, 5)}-${v.slice(5)}`;
            }

            function maskBrDate(value) {
                const digits = somenteDigitos(value).slice(0, 8);
                if (digits.length <= 2) return digits;
                if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
                return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
            }

            function isoToBr(isoDate) {
                if (!isoDate || !/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) return '';
                const [y, m, d] = isoDate.split('-');
                return `${d}/${m}/${y}`;
            }

            function brToIso(brDate) {
                const m = String(brDate || '').match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                if (!m) return '';
                const d = m[1];
                const mo = m[2];
                const y = m[3];
                const iso = `${y}-${mo}-${d}`;
                const dt = new Date(`${iso}T00:00:00`);
                if (Number.isNaN(dt.getTime())) return '';
                return iso;
            }

            const etapasTemplates = {
                escavacao: [
                    'Isolamento da área e sinalização perimetral.',
                    'Verificação de interferências (rede elétrica, água, gás).',
                    'Posicionamento da escavadeira e checklist do equipamento.',
                    'Escavação mecanizada conforme profundidade prevista.',
                    'Retirada e transporte do material para caçamba.',
                    'Inspeção final, limpeza e liberação da área.',
                ],
                altura: [
                    'Análise do local e definição dos pontos de ancoragem.',
                    'Isolamento da área inferior e sinalização.',
                    'Inspeção dos EPIs e sistemas de proteção contra queda.',
                    'Acesso seguro à estrutura (escada/plataforma).',
                    'Execução da atividade em altura com linha de vida ativa.',
                    'Desmobilização, inspeção final e liberação da área.',
                ],
                eletricidade: [
                    'Identificação do circuito e planejamento da intervenção.',
                    'Desenergização e bloqueio/etiquetagem (LOTO).',
                    'Teste de ausência de tensão com instrumento adequado.',
                    'Execução do serviço elétrico (manutenção/instalação).',
                    'Reenergização controlada e teste funcional.',
                    'Registro da intervenção e encerramento da atividade.',
                ],
                confinado: [
                    'Emissão da PET e definição da equipe autorizada.',
                    'Isolamento da área e controle de acesso.',
                    'Monitoramento atmosférico inicial e ventilação.',
                    'Entrada do trabalhador com vigia externo.',
                    'Execução da atividade com monitoramento contínuo.',
                    'Saída segura, fechamento da PET e liberação do local.',
                ],
                demolicao: [
                    'Vistoria técnica inicial e plano de demolição.',
                    'Isolamento da área e desligamento de utilidades.',
                    'Demolição controlada por sequência definida.',
                    'Coleta e segregação de resíduos.',
                    'Remoção de entulho e limpeza operacional.',
                    'Inspeção final de segurança da área.',
                ],
                icamento: [
                    'Avaliação da carga e rota de movimentação.',
                    'Inspeção de cintas, cabos, ganchos e equipamento.',
                    'Isolamento da área de içamento.',
                    'Amarração correta da carga e teste de elevação.',
                    'Movimentação controlada com sinalizador.',
                    'Posicionamento final, desacoplamento e inspeção.',
                ],
            };

            const episMaquinasTemplates = {
                escavacao: [
                    { tipo: 'epi', descricao: 'Capacete de segurança classe B', aplicacao: 'Todas as etapas' },
                    { tipo: 'epi', descricao: 'Botina de segurança com biqueira', aplicacao: 'Todas as etapas' },
                    { tipo: 'epi', descricao: 'Protetor auricular', aplicacao: 'Escavação mecanizada' },
                    { tipo: 'maquina', descricao: 'Escavadeira hidráulica', aplicacao: 'Execução principal' },
                    { tipo: 'maquina', descricao: 'Caminhão caçamba', aplicacao: 'Transporte de material' },
                ],
                altura: [
                    { tipo: 'epi', descricao: 'Cinto de segurança tipo paraquedista', aplicacao: 'Execução em altura' },
                    { tipo: 'epi', descricao: 'Talabarte com absorvedor de energia', aplicacao: 'Execução em altura' },
                    { tipo: 'epi', descricao: 'Capacete com jugular', aplicacao: 'Todas as etapas' },
                    { tipo: 'maquina', descricao: 'Plataforma elevatória', aplicacao: 'Acesso/execução' },
                ],
                eletricidade: [
                    { tipo: 'epi', descricao: 'Luva isolante classe adequada', aplicacao: 'Intervenção elétrica' },
                    { tipo: 'epi', descricao: 'Protetor facial/óculos de segurança', aplicacao: 'Intervenção elétrica' },
                    { tipo: 'epi', descricao: 'Calçado de segurança isolante', aplicacao: 'Todas as etapas' },
                    { tipo: 'maquina', descricao: 'Multímetro / detector de tensão', aplicacao: 'Teste e validação' },
                ],
                confinado: [
                    { tipo: 'epi', descricao: 'Respirador adequado ao risco', aplicacao: 'Entrada e execução' },
                    { tipo: 'epi', descricao: 'Cinturão com linha de resgate', aplicacao: 'Entrada e permanência' },
                    { tipo: 'epi', descricao: 'Capacete de segurança', aplicacao: 'Todas as etapas' },
                    { tipo: 'maquina', descricao: 'Detector multigases', aplicacao: 'Monitoramento atmosférico' },
                    { tipo: 'maquina', descricao: 'Ventilador/exaustor', aplicacao: 'Ventilação do espaço' },
                ],
                demolicao: [
                    { tipo: 'epi', descricao: 'Óculos de proteção', aplicacao: 'Demolição e limpeza' },
                    { tipo: 'epi', descricao: 'Luva de raspa', aplicacao: 'Demolição e manuseio' },
                    { tipo: 'epi', descricao: 'Máscara PFF2', aplicacao: 'Ambiente com poeira' },
                    { tipo: 'maquina', descricao: 'Martelete / rompedor', aplicacao: 'Demolição controlada' },
                ],
                icamento: [
                    { tipo: 'epi', descricao: 'Capacete de segurança', aplicacao: 'Toda operação' },
                    { tipo: 'epi', descricao: 'Luva de vaqueta', aplicacao: 'Amarração e desacoplamento' },
                    { tipo: 'epi', descricao: 'Botina de segurança', aplicacao: 'Toda operação' },
                    { tipo: 'maquina', descricao: 'Guindaste / munck', aplicacao: 'Içamento' },
                    { tipo: 'maquina', descricao: 'Cintas e acessórios certificados', aplicacao: 'Amarração' },
                ],
            };

            const sugestoesPorFuncao = {
                'OPERADOR DE ESCAVADEIRA': [
                    { tipo: 'epi', descricao: 'Protetor auricular', aplicacao: 'Operação de escavadeira' },
                    { tipo: 'epi', descricao: 'Óculos de segurança', aplicacao: 'Operação de escavadeira' },
                    { tipo: 'maquina', descricao: 'Escavadeira hidráulica', aplicacao: 'Execução principal' },
                ],
                ELETRICISTA: [
                    { tipo: 'epi', descricao: 'Luva isolante', aplicacao: 'Serviço elétrico' },
                    { tipo: 'epi', descricao: 'Protetor facial', aplicacao: 'Serviço elétrico' },
                    { tipo: 'maquina', descricao: 'Multímetro', aplicacao: 'Teste elétrico' },
                ],
                SOLDADOR: [
                    { tipo: 'epi', descricao: 'Máscara de solda', aplicacao: 'Soldagem' },
                    { tipo: 'epi', descricao: 'Avental de raspa', aplicacao: 'Soldagem' },
                ],
                SERVENTE: [
                    { tipo: 'epi', descricao: 'Luva de proteção', aplicacao: 'Apoio operacional' },
                    { tipo: 'epi', descricao: 'Botina de segurança', aplicacao: 'Apoio operacional' },
                ],
            };

            function emailValido(valor) {
                if (!possuiTexto(valor)) return true;
                const email = String(valor).trim();
                return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
            }

            async function carregarCidadesPorUf(uf, cidadeSelecionar = null) {
                if (!obraCidadeSelect) return;

                if (!uf) {
                    obraCidadeSelect.innerHTML = '<option value="">Selecione...</option>';
                    return;
                }

                obraCidadeSelect.innerHTML = '<option value="">Carregando...</option>';

                try {
                    const urlBase = @json(route('estados.cidades', ['uf' => '__UF__']));
                    const resp = await fetch(urlBase.replace('__UF__', uf));
                    const json = await resp.json();

                    obraCidadeSelect.innerHTML = '<option value="">Selecione...</option>';

                    const alvoNorm = cidadeSelecionar ? normalizarTexto(cidadeSelecionar) : null;

                    json.forEach((c) => {
                        const nomeOriginal = String(c.nome || '').trim();
                        if (!nomeOriginal) return;

                        const opt = document.createElement('option');
                        opt.value = nomeOriginal;
                        opt.textContent = nomeOriginal;

                        if (alvoNorm && normalizarTexto(nomeOriginal) === alvoNorm) {
                            opt.selected = true;
                        }

                        obraCidadeSelect.appendChild(opt);
                    });
                } catch (e) {
                    obraCidadeSelect.innerHTML = '<option value="">Selecione...</option>';
                }
            }

            let ultimoCepConsultado = '';

            async function consultarCepViaCep() {
                if (!inputCep) return;

                const cep = somenteDigitos(inputCep.value);
                if (cep.length !== 8) return;
                if (cep === ultimoCepConsultado) return;

                try {
                    const resp = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const json = await resp.json();

                    if (json?.erro) return;

                    ultimoCepConsultado = cep;

                    const logradouro = String(json.logradouro || '').trim();
                    const bairro = String(json.bairro || '').trim();
                    const enderecoAuto = [logradouro, bairro].filter(Boolean).join(' - ');

                    if (inputObraEndereco && enderecoAuto) {
                        inputObraEndereco.value = enderecoAuto;
                    }

                    const ufApi = String(json.uf || '').trim().toUpperCase();
                    const cidadeApi = String(json.localidade || '').trim();

                    if (ufApi && obraUfSelect) {
                        obraUfSelect.value = ufApi;
                        await carregarCidadesPorUf(ufApi, cidadeApi || null);
                    }
                } catch (e) {
                    console.error('Erro ao buscar CEP no ViaCEP', e);
                }
            }

            function validarStepAtual(stepIdx) {
                const stepEl = steps[stepIdx];
                if (!stepEl) return true;
                limparErrosStep(stepEl);

                if (stepIdx === 0) {
                    const obrigatorios = [
                        'contratante_razao_social',
                        'contratante_cnpj',
                        'obra_nome',
                        'obra_endereco',
                        'obra_cidade',
                        'obra_uf',
                    ];
                    for (const name of obrigatorios) {
                        const campo = form.querySelector(`[name="${name}"]`);
                        if (!campo || !possuiTexto(campo.value)) {
                            addErroCampo(stepEl, campo, 'Preencha os campos obrigatórios deste passo.');
                            campo?.focus();
                            return false;
                        }
                    }

                    if (!validarCampoCnpj({ exigirPreenchimento: true })) {
                        addErroCampo(stepEl, inputCnpj, 'Informe um CNPJ válido.');
                        inputCnpj?.focus();
                        return false;
                    }

                    const emailCampo = form.querySelector('[name="contratante_email"]');
                    if (emailCampo && !emailValido(emailCampo.value)) {
                        addErroCampo(stepEl, emailCampo, 'Informe um e-mail válido.');
                        emailCampo.focus();
                        return false;
                    }
                }

                if (stepIdx === 1) {
                    const descricao = form.querySelector('[name="atividade_descricao"]');
                    const inicio = form.querySelector('[name="atividade_data_inicio"]');
                    const fim = form.querySelector('[name="atividade_data_termino_prevista"]');
                    if (!possuiTexto(descricao?.value) || !possuiTexto(inicio?.value) || !possuiTexto(fim?.value)) {
                        addErroCampo(stepEl, descricao, 'Informe descrição e período da atividade.');
                        descricao?.focus();
                        return false;
                    }
                    if (inicio.value && fim.value && fim.value < inicio.value) {
                        addErroCampo(stepEl, fim, 'A data de término deve ser maior ou igual à data de início.');
                        fim.focus();
                        return false;
                    }
                }

                if (stepIdx === 2) {
                    const etapas = Array.from(stepEl.querySelectorAll('input[name^="etapas"]'));
                    const preenchidas = etapas.filter((i) => possuiTexto(i.value));
                    if (etapas.length === 0 || preenchidas.length < 3) {
                        addErroCampo(stepEl, etapas[0], 'Adicione pelo menos 3 etapas com descrição.');
                        etapas[0]?.focus();
                        return false;
                    }
                }

                if (stepIdx === 3) {
                    const linhas = Array.from(stepEl.querySelectorAll('.equipe-item'));
                    const ok = linhas.some((linha) => {
                        const nome = linha.querySelector('input[name*="[nome]"]');
                        const funcao = linha.querySelector('input[name*="[funcao]"]');
                        return possuiTexto(nome?.value) && possuiTexto(funcao?.value);
                    });
                    if (!ok) {
                        addErroCampo(stepEl, linhas[0]?.querySelector('input[name*="[nome]"]'), 'Adicione ao menos um trabalhador com nome e função.');
                        linhas[0]?.querySelector('input[name*="[nome]"]')?.focus();
                        return false;
                    }
                }

                if (stepIdx === 4) {
                    const epis = Array.from(stepEl.querySelectorAll('input[name*="[descricao]"]'));
                    if (epis.length === 0 || epis.every((i) => !possuiTexto(i.value))) {
                        addErroCampo(stepEl, epis[0], 'Adicione ao menos um EPI com descrição.');
                        epis[0]?.focus();
                        return false;
                    }
                }

                return true;
            }

            function validarTudoParaAprovar() {
                for (let step = 0; step <= 4; step++) {
                    const ok = validarStepAtual(step);
                    if (!ok) {
                        setStep(step);
                        return false;
                    }
                }
                return true;
            }

            function renumerarItens(containerSelector, itemSelector, numberSelector, namePrefix, fields) {
                const container = document.querySelector(containerSelector);
                if (!container) return;

                const itens = Array.from(container.querySelectorAll(itemSelector));
                itens.forEach((item, idx) => {
                    const numEl = item.querySelector(numberSelector);
                    if (numEl) numEl.textContent = String(idx + 1);

                    fields.forEach((field) => {
                        const input = item.querySelector(`[data-field="${field}"]`);
                        if (!input) return;
                        input.name = `${namePrefix}[${idx}][${field}]`;
                    });
                });
            }

            function criarItemEtapa() {
                const el = document.createElement('div');
                el.className = 'etapa-item rounded-xl border border-slate-200 p-3';
                el.innerHTML = `
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-slate-600">Etapa <span class="etapa-num">1</span></span>
                        <button type="button" class="remove-etapa text-xs text-red-600 hover:text-red-700">Remover</button>
                    </div>
                    <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Descrição</label>
                    <input type="text" data-field="descricao" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" placeholder="Descreva a etapa">
                `;
                return el;
            }

            function criarItemEquipe() {
                const el = document.createElement('div');
                el.className = 'equipe-item rounded-xl border border-slate-200 p-3';
                el.innerHTML = `
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-slate-600">Trabalhador <span class="equipe-num">1</span></span>
                        <button type="button" class="remove-equipe text-xs text-red-600 hover:text-red-700">Remover</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Nome</label>
                            <input type="text"
                                   data-field="nome"
                                   class="js-equipe-nome w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                   list="apr-colaboradores-list"
                                   autocomplete="off">
                        </div>
                        <div>
                            <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Função</label>
                            <input type="text" data-field="funcao" class="js-equipe-funcao w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>
                    </div>
                `;
                return el;
            }

            function obterDadosEquipe() {
                return Array.from(equipeList.querySelectorAll('.equipe-item'))
                    .map((linha) => ({
                        nome: String(linha.querySelector('input[name*="[nome]"], input[data-field="nome"]')?.value || '').trim(),
                        funcao: String(linha.querySelector('input[name*="[funcao]"], input[data-field="funcao"]')?.value || '').trim(),
                    }));
            }

            function chaveFuncaoGrupo(funcao) {
                const chave = normalizarTexto(funcao || '');
                return chave !== '' ? chave : '__SEM_FUNCAO__';
            }

            const equipeGrupoPaleta = [
                { container: 'border-sky-200 bg-sky-50', badge: 'bg-sky-100 text-sky-800' },
                { container: 'border-emerald-200 bg-emerald-50', badge: 'bg-emerald-100 text-emerald-800' },
                { container: 'border-amber-200 bg-amber-50', badge: 'bg-amber-100 text-amber-800' },
                { container: 'border-fuchsia-200 bg-fuchsia-50', badge: 'bg-fuchsia-100 text-fuchsia-800' },
                { container: 'border-indigo-200 bg-indigo-50', badge: 'bg-indigo-100 text-indigo-800' },
                { container: 'border-rose-200 bg-rose-50', badge: 'bg-rose-100 text-rose-800' },
                { container: 'border-teal-200 bg-teal-50', badge: 'bg-teal-100 text-teal-800' },
                { container: 'border-orange-200 bg-orange-50', badge: 'bg-orange-100 text-orange-800' },
            ];

            function indiceCorGrupoPorChave(chaveGrupo) {
                const texto = String(chaveGrupo || '');
                let hash = 0;
                for (let i = 0; i < texto.length; i++) {
                    hash = ((hash << 5) - hash) + texto.charCodeAt(i);
                    hash |= 0;
                }
                return Math.abs(hash) % equipeGrupoPaleta.length;
            }

            function criarGrupoEquipe(labelFuncao, chaveGrupo) {
                const tema = equipeGrupoPaleta[indiceCorGrupoPorChave(chaveGrupo)];
                const grupo = document.createElement('div');
                grupo.className = `equipe-grupo rounded-xl border p-3 ${tema.container}`;
                grupo.dataset.funcaoKey = chaveGrupo;
                grupo.innerHTML = `
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-[11px] font-semibold ${tema.badge}">
                            Função: ${labelFuncao}
                        </span>
                        <button type="button" class="remove-equipe-grupo text-xs font-semibold text-red-600 hover:text-red-700">
                            Remover grupo
                        </button>
                    </div>
                    <div class="equipe-grupo-itens space-y-3"></div>
                `;
                return grupo;
            }

            function montarEquipeAgrupada(dados) {
                const lista = (Array.isArray(dados) ? dados : [])
                    .map((item) => ({
                        nome: String(item?.nome || '').trim(),
                        funcao: String(item?.funcao || '').trim(),
                    }));

                if (!lista.length) {
                    lista.push({ nome: '', funcao: '' });
                }

                equipeList.innerHTML = '';
                const grupos = new Map();

                lista.forEach((pessoa) => {
                    const key = chaveFuncaoGrupo(pessoa.funcao);
                    const label = pessoa.funcao || 'Sem função informada';

                    if (!grupos.has(key)) {
                        const grupo = criarGrupoEquipe(label, key);
                        grupos.set(key, grupo);
                        equipeList.appendChild(grupo);
                    }

                    const item = criarItemEquipe();
                    const nomeInput = item.querySelector('input[data-field="nome"]');
                    const funcaoInput = item.querySelector('input[data-field="funcao"]');
                    if (nomeInput) nomeInput.value = pessoa.nome;
                    if (funcaoInput) funcaoInput.value = pessoa.funcao;

                    grupos.get(key).querySelector('.equipe-grupo-itens')?.appendChild(item);
                });

                renumerarItens('#equipe-list', '.equipe-item', '.equipe-num', 'equipe', ['nome', 'funcao']);
            }

            function criarItemEpi() {
                const el = document.createElement('div');
                el.className = 'epi-item rounded-xl border border-slate-200 p-3';
                el.innerHTML = `
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-slate-600">EPI <span class="epi-num">1</span></span>
                        <button type="button" class="remove-epi text-xs text-red-600 hover:text-red-700">Remover</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Tipo</label>
                            <select data-field="tipo" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                <option value="epi">EPI</option>
                                <option value="maquina">Máquina</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="apr-required-label block text-xs font-medium text-slate-600 mb-1">Descrição</label>
                            <input type="text" data-field="descricao" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>
                    </div>
                `;
                return el;
            }

            function limparEtapas() {
                etapasList.innerHTML = '';
                etapasList.appendChild(criarItemEtapa());
                renumerarItens('#etapas-list', '.etapa-item', '.etapa-num', 'etapas', ['descricao']);
            }

            function limparEquipe() {
                montarEquipeAgrupada([{ nome: '', funcao: '' }]);
            }

            function limparEpis() {
                episList.innerHTML = '';
                episList.appendChild(criarItemEpi());
                renumerarItens('#epis-list', '.epi-item', '.epi-num', 'epis', ['tipo', 'descricao']);
            }

            function chaveItemEpi(item) {
                const tipo = (item?.tipo === 'maquina') ? 'maquina' : 'epi';
                const descricao = normalizarTexto(item?.descricao || '');
                return `${tipo}|${descricao}`;
            }

            function coletarChavesEpisAtuais() {
                return new Set(
                    Array.from(episList.querySelectorAll('.epi-item'))
                        .map((linha) => {
                            const tipo = linha.querySelector('select[name*="[tipo]"], select[data-field="tipo"]')?.value || 'epi';
                            const descricao = linha.querySelector('input[name*="[descricao]"], input[data-field="descricao"]')?.value || '';
                            return chaveItemEpi({ tipo, descricao });
                        })
                        .filter((chave) => !chave.endsWith('|'))
                );
            }

            function appendItensEpis(itens) {
                const lista = (Array.isArray(itens) ? itens : [])
                    .map((item) => ({
                        tipo: item?.tipo === 'maquina' ? 'maquina' : 'epi',
                        descricao: String(item?.descricao || '').trim(),
                    }))
                    .filter((item) => item.descricao !== '');

                if (!lista.length) return 0;

                const existentes = coletarChavesEpisAtuais();
                let adicionados = 0;

                const primeiraLinha = episList.querySelector('.epi-item');
                const primeiraDescricao = primeiraLinha?.querySelector('input[name*="[descricao]"], input[data-field="descricao"]');
                const primeiraTipo = primeiraLinha?.querySelector('select[name*="[tipo]"], select[data-field="tipo"]');
                const primeiraVazia = primeiraDescricao && !possuiTexto(primeiraDescricao.value);

                lista.forEach((item) => {
                    const chave = chaveItemEpi(item);
                    if (!chave || existentes.has(chave)) return;

                    if (primeiraVazia && adicionados === 0 && primeiraDescricao && primeiraTipo) {
                        primeiraTipo.value = item.tipo;
                        primeiraDescricao.value = item.descricao;
                    } else {
                        const novo = criarItemEpi();
                        const tipoInput = novo.querySelector('select[data-field="tipo"]');
                        const descricaoInput = novo.querySelector('input[data-field="descricao"]');
                        if (tipoInput) tipoInput.value = item.tipo;
                        if (descricaoInput) descricaoInput.value = item.descricao;
                        episList.appendChild(novo);
                    }

                    existentes.add(chave);
                    adicionados++;
                });

                if (adicionados > 0) {
                    renumerarItens('#epis-list', '.epi-item', '.epi-num', 'epis', ['tipo', 'descricao']);
                }

                return adicionados;
            }

            function aplicarTemplateEpisPorAtividade() {
                const key = episTemplateSelect?.value || '';
                if (!key) {
                    limparEpis();
                    return;
                }

                const itens = episMaquinasTemplates[key];
                if (!Array.isArray(itens) || !itens.length) {
                    notify('Nenhum item encontrado para o template selecionado.');
                    return;
                }

                const adicionados = appendItensEpis(itens);
                if (adicionados === 0) {
                    notify('Os itens desse template já foram adicionados.');
                    return;
                }

                notify(`Template aplicado: ${adicionados} item(ns) incluído(s).`);
            }

            function sugerirEpisPorEquipe() {
                const funcoes = new Set(
                    Array.from(equipeList.querySelectorAll('input[name*="[funcao]"], input[data-field="funcao"]'))
                        .map((el) => normalizarTexto(el.value || ''))
                        .filter((v) => v !== '')
                );

                if (funcoes.size === 0) {
                    notify('Preencha ao menos uma função na equipe para gerar sugestões.');
                    return;
                }

                const sugestoes = [];
                funcoes.forEach((funcao) => {
                    const itens = sugestoesPorFuncao[funcao];
                    if (Array.isArray(itens) && itens.length) {
                        sugestoes.push(...itens);
                    }
                });

                if (!sugestoes.length) {
                    notify('Não há sugestões configuradas para as funções informadas.');
                    return;
                }

                const adicionados = appendItensEpis(sugestoes);
                if (adicionados === 0) {
                    notify('As sugestões por função já estão no formulário.');
                    return;
                }

                notify(`Sugestões aplicadas: ${adicionados} item(ns) incluído(s).`);
            }

            const etapasList = document.getElementById('etapas-list');
            const equipeList = document.getElementById('equipe-list');
            const episList = document.getElementById('epis-list');

            document.querySelectorAll('.etapa-item input[name^="etapas"]').forEach((input) => input.dataset.field = 'descricao');
            document.querySelectorAll('.equipe-item input[name*="[nome]"]').forEach((input) => input.dataset.field = 'nome');
            document.querySelectorAll('.equipe-item input[name*="[funcao]"]').forEach((input) => input.dataset.field = 'funcao');
            document.querySelectorAll('.epi-item select[name*="[tipo]"]').forEach((input) => input.dataset.field = 'tipo');
            document.querySelectorAll('.epi-item input[name*="[descricao]"]').forEach((input) => input.dataset.field = 'descricao');

            document.getElementById('add-etapa').addEventListener('click', function () {
                etapasList.appendChild(criarItemEtapa());
                renumerarItens('#etapas-list', '.etapa-item', '.etapa-num', 'etapas', ['descricao']);
            });

            btnApplyEtapasTemplate?.addEventListener('click', function () {
                const key = etapasTemplateSelect?.value || '';
                const etapas = etapasTemplates[key];
                if (!key) {
                    limparEtapas();
                    return;
                }
                if (!Array.isArray(etapas) || !etapas.length) {
                    return;
                }

                etapasList.innerHTML = '';
                etapas.forEach((descricao) => {
                    const item = criarItemEtapa();
                    const input = item.querySelector('input[data-field="descricao"]');
                    if (input) input.value = descricao;
                    etapasList.appendChild(item);
                });
                renumerarItens('#etapas-list', '.etapa-item', '.etapa-num', 'etapas', ['descricao']);
            });

            etapasTemplateSelect?.addEventListener('change', function () {
                if (!this.value) {
                    limparEtapas();
                    return;
                }
                btnApplyEtapasTemplate?.click();
            });

            function nomesJaNoFormulario() {
                return new Set(
                    Array.from(equipeList.querySelectorAll('input[name*="[nome]"]'))
                        .map((el) => normalizarTexto(el.value || ''))
                        .filter((v) => v !== '')
                );
            }

            function adicionarEquipePorFuncao(funcaoSelecionada) {
                const funcaoNorm = normalizarTexto(funcaoSelecionada || '');
                if (!funcaoNorm) {
                    notify('Selecione uma função para autopreencher a equipe.');
                    return;
                }

                const candidatos = (Array.isArray(colaboradores) ? colaboradores : [])
                    .filter((c) => normalizarTexto(c?.funcao || '') === funcaoNorm)
                    .map((c) => ({
                        nome: String(c?.nome || '').trim(),
                        funcao: String(c?.funcao || '').trim(),
                    }))
                    .filter((c) => c.nome !== '');

                if (!candidatos.length) {
                    notify('Nenhum colaborador encontrado para a função selecionada.');
                    return;
                }

                const existentes = nomesJaNoFormulario();
                const novos = candidatos.filter((c) => !existentes.has(normalizarTexto(c.nome)));

                if (!novos.length) {
                    notify('Todos os colaboradores dessa função já estão na equipe.');
                    return;
                }

                const equipeAtual = obterDadosEquipe();
                const idxPrimeiraVazia = equipeAtual.findIndex((item) => !possuiTexto(item.nome) && !possuiTexto(item.funcao));

                if (idxPrimeiraVazia >= 0) {
                    const primeiro = novos.shift();
                    equipeAtual[idxPrimeiraVazia] = primeiro;
                }

                equipeAtual.push(...novos);

                montarEquipeAgrupada(equipeAtual);
            }

            document.getElementById('add-equipe').addEventListener('click', function () {
                const equipeAtual = obterDadosEquipe();
                equipeAtual.push({ nome: '', funcao: '' });
                montarEquipeAgrupada(equipeAtual);
            });

            equipeFuncaoAplicarBtn?.addEventListener('click', function () {
                const valor = equipeFuncaoSelect?.value || '';
                if (!valor) {
                    limparEquipe();
                    return;
                }
                adicionarEquipePorFuncao(valor);
            });

            equipeFuncaoSelect?.addEventListener('change', function () {
                if (!this.value) {
                    limparEquipe();
                    return;
                }
                adicionarEquipePorFuncao(this.value);
            });

            document.getElementById('add-epi').addEventListener('click', function () {
                episList.appendChild(criarItemEpi());
                renumerarItens('#epis-list', '.epi-item', '.epi-num', 'epis', ['tipo', 'descricao']);
            });

            btnApplyEpisTemplate?.addEventListener('click', function () {
                aplicarTemplateEpisPorAtividade();
            });

            episTemplateSelect?.addEventListener('change', function () {
                if (!this.value) {
                    limparEpis();
                    return;
                }
                btnApplyEpisTemplate?.click();
            });

            btnSuggestEpisEquipe?.addEventListener('click', function () {
                sugerirEpisPorEquipe();
            });

            btnClearEpis?.addEventListener('click', function () {
                if (window.confirm('Deseja remover todos os EPIs e Máquinas desta seção?')) {
                    limparEpis();
                    if (episTemplateSelect) {
                        episTemplateSelect.value = '';
                    }
                }
            });

            document.addEventListener('click', function (ev) {
                if (ev.target.matches('.remove-etapa')) {
                    const itens = etapasList.querySelectorAll('.etapa-item');
                    if (itens.length > 1) ev.target.closest('.etapa-item')?.remove();
                    renumerarItens('#etapas-list', '.etapa-item', '.etapa-num', 'etapas', ['descricao']);
                }

                if (ev.target.matches('.remove-equipe')) {
                    const linha = ev.target.closest('.equipe-item');
                    linha?.remove();
                    const grupo = ev.target.closest('.equipe-grupo');
                    if (grupo && !grupo.querySelector('.equipe-item')) {
                        grupo.remove();
                    }
                    if (!equipeList.querySelector('.equipe-item')) {
                        limparEquipe();
                    } else {
                        renumerarItens('#equipe-list', '.equipe-item', '.equipe-num', 'equipe', ['nome', 'funcao']);
                    }
                }

                if (ev.target.matches('.remove-equipe-grupo')) {
                    ev.target.closest('.equipe-grupo')?.remove();
                    if (!equipeList.querySelector('.equipe-item')) {
                        limparEquipe();
                    } else {
                        renumerarItens('#equipe-list', '.equipe-item', '.equipe-num', 'equipe', ['nome', 'funcao']);
                    }
                }

                if (ev.target.matches('.remove-epi')) {
                    const itens = episList.querySelectorAll('.epi-item');
                    if (itens.length > 1) ev.target.closest('.epi-item')?.remove();
                    renumerarItens('#epis-list', '.epi-item', '.epi-num', 'epis', ['tipo', 'descricao']);
                }

            });

            document.addEventListener('input', function (ev) {
                if (ev.target.matches('.js-equipe-nome')) {
                    preencherFuncaoPorNome(ev.target);
                }
            });

            document.addEventListener('change', function (ev) {
                if (ev.target.matches('.js-equipe-nome')) {
                    preencherFuncaoPorNome(ev.target);
                    montarEquipeAgrupada(obterDadosEquipe());
                }
                if (ev.target.matches('.js-equipe-funcao')) {
                    montarEquipeAgrupada(obterDadosEquipe());
                }
            });

            function valorInput(name) {
                const input = form.querySelector(`[name="${name}"]`);
                return input ? input.value.trim() : '-';
            }

            function preencherResumo() {
                const set = (key, value) => {
                    const el = form.querySelector(`[data-resumo="${key}"]`);
                    if (el) el.textContent = value || '-';
                };
                const preencherLista = (key, itens) => {
                    const lista = form.querySelector(`[data-resumo-list="${key}"]`);
                    if (!lista) return;
                    lista.innerHTML = '';
                    if (!Array.isArray(itens) || !itens.length) {
                        const li = document.createElement('li');
                        li.textContent = '-';
                        lista.appendChild(li);
                        return;
                    }
                    itens.forEach((txt) => {
                        const li = document.createElement('li');
                        li.textContent = txt;
                        lista.appendChild(li);
                    });
                };

                set('contratante_razao_social', valorInput('contratante_razao_social'));
                set('contratante_cnpj', valorInput('contratante_cnpj'));
                set('contratante_responsavel_nome', valorInput('contratante_responsavel_nome'));
                set('contratante_telefone', valorInput('contratante_telefone'));
                set('contratante_email', valorInput('contratante_email'));
                set('obra_nome', valorInput('obra_nome'));
                set('obra_endereco', valorInput('obra_endereco'));
                set('obra_cep', valorInput('obra_cep'));
                set('obra_area_setor', valorInput('obra_area_setor'));
                const cidade = valorInput('obra_cidade');
                const uf = valorInput('obra_uf');
                set('obra_cidade_uf', (cidade && cidade !== '-' ? cidade : '-') + (uf && uf !== '-' ? ` / ${uf}` : ''));
                set('atividade_descricao', valorInput('atividade_descricao'));

                const ini = valorInput('atividade_data_inicio');
                const fim = valorInput('atividade_data_termino_prevista');
                set('periodo', `${ini || '-'} a ${fim || '-'}`);

                const etapasResumo = Array.from(etapasList.querySelectorAll('.etapa-item input[name^="etapas"]'))
                    .map((el) => String(el.value || '').trim())
                    .filter((v) => v !== '');
                const equipeResumo = Array.from(equipeList.querySelectorAll('.equipe-item'))
                    .map((linha) => {
                        const nome = String(linha.querySelector('input[name*="[nome]"]')?.value || '').trim();
                        const funcao = String(linha.querySelector('input[name*="[funcao]"]')?.value || '').trim();
                        if (!nome && !funcao) return '';
                        return funcao ? `${nome} - ${funcao}` : nome;
                    })
                    .filter((v) => v !== '');
                const episResumo = Array.from(episList.querySelectorAll('.epi-item'))
                    .map((linha) => {
                        const tipo = linha.querySelector('select[name*="[tipo]"]')?.value === 'maquina' ? 'Máquina' : 'EPI';
                        const descricao = String(linha.querySelector('input[name*="[descricao]"]')?.value || '').trim();
                        return descricao ? `${tipo}: ${descricao}` : '';
                    })
                    .filter((v) => v !== '');

                set('total_etapas', String(etapasResumo.length));
                set('total_equipe', String(equipeResumo.length));
                set('total_epis', String(episResumo.length));
                preencherLista('etapas', etapasResumo);
                preencherLista('equipe', equipeResumo);
                preencherLista('epis', episResumo);
            }

            renumerarItens('#etapas-list', '.etapa-item', '.etapa-num', 'etapas', ['descricao']);
            montarEquipeAgrupada(obterDadosEquipe());
            renumerarItens('#epis-list', '.epi-item', '.epi-num', 'epis', ['tipo', 'descricao']);

            if (obraUfSelect) {
                obraUfSelect.addEventListener('change', async (e) => {
                    await carregarCidadesPorUf(e.target.value, null);
                });
            }

            if (obraUfSelect?.value) {
                carregarCidadesPorUf(obraUfSelect.value, cidadeInicial);
            }

            if (inputCnpj) {
                inputCnpj.addEventListener('input', (e) => {
                    e.target.value = mascararCnpj(e.target.value);
                    if (!somenteDigitos(e.target.value)) {
                        limparErroCnpj();
                        return;
                    }
                    if (somenteDigitos(e.target.value).length < 14) {
                        limparErroCnpj();
                    }
                });
                inputCnpj.addEventListener('blur', () => validarCampoCnpj());
                inputCnpj.value = mascararCnpj(inputCnpj.value);
                validarCampoCnpj();
            }

            if (inputTelefone) {
                inputTelefone.addEventListener('input', (e) => {
                    e.target.value = mascararTelefone(e.target.value);
                });
                inputTelefone.value = mascararTelefone(inputTelefone.value);
            }

            if (inputCep) {
                inputCep.addEventListener('input', (e) => {
                    e.target.value = mascararCep(e.target.value);
                    ultimoCepConsultado = '';
                });
                inputCep.addEventListener('blur', consultarCepViaCep);
                inputCep.value = mascararCep(inputCep.value);
            }

            if (inputEmail) {
                inputEmail.addEventListener('input', (e) => {
                    if (!emailValido(e.target.value)) {
                        e.target.setCustomValidity('Informe um e-mail válido.');
                    } else {
                        e.target.setCustomValidity('');
                    }
                });
            }

            document.querySelectorAll('.js-date-text').forEach((textInput) => {
                const hiddenId = textInput.dataset.dateTarget;
                const hiddenInput = hiddenId ? document.getElementById(hiddenId) : null;
                if (!hiddenInput) return;

                textInput.value = isoToBr(hiddenInput.value);

                textInput.addEventListener('input', () => {
                    textInput.value = maskBrDate(textInput.value);
                    if (textInput.value.length === 10) {
                        hiddenInput.value = brToIso(textInput.value);
                    } else if (textInput.value.length === 0) {
                        hiddenInput.value = '';
                    }
                });

                textInput.addEventListener('blur', () => {
                    const iso = brToIso(textInput.value);
                    hiddenInput.value = iso;
                    textInput.value = iso ? isoToBr(iso) : textInput.value;
                });

                hiddenInput.addEventListener('change', () => {
                    textInput.value = isoToBr(hiddenInput.value);
                });
            });

            document.querySelectorAll('.date-picker-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const targetId = btn.dataset.dateTarget;
                    const hiddenInput = targetId ? document.getElementById(targetId) : null;
                    if (!hiddenInput) return;

                    if (typeof hiddenInput.showPicker === 'function') {
                        hiddenInput.showPicker();
                    } else {
                        hiddenInput.focus();
                    }
                });
            });

            setStep(currentStep);
        });
    </script>
@endpush
