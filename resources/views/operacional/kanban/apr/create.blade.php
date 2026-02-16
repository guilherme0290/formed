@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')

@section('pageTitle', 'APR - Análise Preliminar de Riscos')

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
                        'nao_treinado' => false,
                    ];
                })
                ->filter(fn ($item) => !empty($item['nome']) || !empty($item['funcao']))
                ->values()
                ->all();
        }
        if (empty($equipeDefault)) {
            $equipeDefault = [['nome' => '', 'funcao' => '', 'nao_treinado' => false]];
        }

        $episDefault = [];
        if (is_array(old('epis'))) {
            $episDefault = old('epis');
        } elseif (!empty($apr?->epis_json) && is_array($apr?->epis_json)) {
            $episDefault = $apr?->epis_json;
        }
        if (empty($episDefault)) {
            $episDefault = [['descricao' => '', 'aplicacao' => '']];
        }

        $obraEnderecoFallback = $apr?->obra_endereco ?? $apr?->endereco_atividade ?? trim(($cliente->endereco ?? '') . ' ' . ($cliente->numero ?? ''));
        $obraCidadeFallback = $apr?->obra_cidade ?? optional($cliente->cidade)->nome;
        $obraUfFallback = $apr?->obra_uf ?? optional(optional($cliente->cidade)->estado)->uf ?? '';
    @endphp

    <div class="w-full px-2 sm:px-3 md:px-4 xl:px-5 py-4 md:py-6">
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
                <div class="grid grid-cols-3 md:grid-cols-6 gap-2 text-[11px] font-semibold text-slate-500 pb-3" id="apr-step-nav">
                    <button type="button" class="apr-step-btn text-left px-2 py-1 rounded-md bg-slate-100 text-slate-800" data-step="0">Contratante</button>
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

                <section class="apr-step" data-step="0">
                    <h2 class="text-base font-semibold text-slate-800">Dados da Contratante</h2>
                    <p class="text-xs text-slate-500 mt-1">Informações da empresa responsável pela obra.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Razão Social / Empresa</label>
                            <input type="text" name="contratante_razao_social" value="{{ $valor('contratante_razao_social', $apr?->contratante_razao_social ?? $cliente->razao_social) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">CNPJ</label>
                            <input type="text" name="contratante_cnpj" value="{{ $valor('contratante_cnpj', $apr?->contratante_cnpj ?? $cliente->cnpj) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Nome do Responsável</label>
                            <input type="text" name="contratante_responsavel_nome" value="{{ $valor('contratante_responsavel_nome', $apr?->contratante_responsavel_nome ?? $cliente->contato) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Telefone</label>
                            <input type="text" name="contratante_telefone" value="{{ $valor('contratante_telefone', $apr?->contratante_telefone ?? $cliente->telefone) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">E-mail</label>
                            <input type="email" name="contratante_email" value="{{ $valor('contratante_email', $apr?->contratante_email ?? $cliente->email) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>
                    </div>

                    <hr class="my-5 border-slate-100">

                    <h2 class="text-base font-semibold text-slate-800">Local da Atividade</h2>
                    <p class="text-xs text-slate-500 mt-1">Dados do local onde será executada a atividade.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Nome da Obra</label>
                            <input type="text" name="obra_nome" value="{{ $valor('obra_nome', $apr?->obra_nome ?? '') }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Endereço Completo</label>
                            <input type="text" name="obra_endereco" value="{{ $valor('obra_endereco', $obraEnderecoFallback) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Cidade</label>
                            <input type="text" name="obra_cidade" value="{{ $valor('obra_cidade', $obraCidadeFallback) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">UF</label>
                            <input type="text" name="obra_uf" maxlength="2" value="{{ strtoupper($valor('obra_uf', $obraUfFallback)) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2 uppercase">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">CEP</label>
                            <input type="text" name="obra_cep" value="{{ $valor('obra_cep', $apr?->obra_cep ?? $cliente->cep) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
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
                            <label class="block text-xs font-medium text-slate-600 mb-1">Descrição detalhada</label>
                            <textarea name="atividade_descricao" rows="4" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">{{ $valor('atividade_descricao', $apr?->atividade_descricao ?? '') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Data início</label>
                                <input type="date" name="atividade_data_inicio" value="{{ $valor('atividade_data_inicio', optional($apr?->atividade_data_inicio)->format('Y-m-d')) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Previsão término</label>
                                <input type="date" name="atividade_data_termino_prevista" value="{{ $valor('atividade_data_termino_prevista', optional($apr?->atividade_data_termino_prevista)->format('Y-m-d')) }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="apr-step hidden" data-step="2">
                    <h2 class="text-base font-semibold text-slate-800">Passo a Passo da Atividade</h2>
                    <p class="text-xs text-slate-500 mt-1">Liste as etapas sequenciais da atividade.</p>

                    <div id="etapas-list" class="space-y-3 mt-4">
                        @foreach($etapasDefault as $idx => $etapa)
                            <div class="etapa-item rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-xs font-semibold text-slate-600">Etapa <span class="etapa-num">{{ $idx + 1 }}</span></span>
                                    <button type="button" class="remove-etapa text-xs text-red-600 hover:text-red-700">Remover</button>
                                </div>
                                <input type="text" name="etapas[{{ $idx }}][descricao]" value="{{ $etapa['descricao'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" placeholder="Descreva a etapa">
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-etapa" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        + Adicionar etapa
                    </button>
                </section>

                <section class="apr-step hidden" data-step="3">
                    <h2 class="text-base font-semibold text-slate-800">Equipe Envolvida</h2>
                    <p class="text-xs text-slate-500 mt-1">Cadastre os trabalhadores que participarão da atividade.</p>

                    <div id="equipe-list" class="space-y-3 mt-4">
                        @foreach($equipeDefault as $idx => $trabalhador)
                            <div class="equipe-item rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-xs font-semibold text-slate-600">Trabalhador <span class="equipe-num">{{ $idx + 1 }}</span></span>
                                    <button type="button" class="remove-equipe text-xs text-red-600 hover:text-red-700">Remover</button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Nome</label>
                                        <input type="text" name="equipe[{{ $idx }}][nome]" value="{{ $trabalhador['nome'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Função</label>
                                        <input type="text" name="equipe[{{ $idx }}][funcao]" value="{{ $trabalhador['funcao'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                    </div>
                                </div>

                                <label class="mt-3 inline-flex items-center gap-2 text-xs text-slate-600">
                                    <input type="checkbox" name="equipe[{{ $idx }}][nao_treinado]" value="1" {{ !empty($trabalhador['nao_treinado']) ? 'checked' : '' }}>
                                    Trabalhador sem treinamento específico
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-equipe" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        + Adicionar trabalhador
                    </button>
                </section>

                <section class="apr-step hidden" data-step="4">
                    <h2 class="text-base font-semibold text-slate-800">EPIs e Máquinas</h2>
                    <p class="text-xs text-slate-500 mt-1">Informe os EPIs e equipamentos necessários na operação.</p>

                    <div id="epis-list" class="space-y-3 mt-4">
                        @foreach($episDefault as $idx => $epi)
                            <div class="epi-item rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-xs font-semibold text-slate-600">EPI <span class="epi-num">{{ $idx + 1 }}</span></span>
                                    <button type="button" class="remove-epi text-xs text-red-600 hover:text-red-700">Remover</button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Descrição</label>
                                        <input type="text" name="epis[{{ $idx }}][descricao]" value="{{ $epi['descricao'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" placeholder="Ex: Capacete classe B">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Aplicação (opcional)</label>
                                        <input type="text" name="epis[{{ $idx }}][aplicacao]" value="{{ $epi['aplicacao'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2" placeholder="Ex: Escavação">
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
                        <p><b>Contratante:</b> <span data-resumo="contratante_razao_social">-</span></p>
                        <p><b>CNPJ:</b> <span data-resumo="contratante_cnpj">-</span></p>
                        <p><b>Obra:</b> <span data-resumo="obra_nome">-</span></p>
                        <p><b>Endereço:</b> <span data-resumo="obra_endereco">-</span></p>
                        <p><b>Período:</b> <span data-resumo="periodo">-</span></p>
                        <p><b>Etapas:</b> <span data-resumo="total_etapas">0</span></p>
                        <p><b>Equipe:</b> <span data-resumo="total_equipe">0</span></p>
                        <p><b>EPIs:</b> <span data-resumo="total_epis">0</span></p>
                    </div>
                </section>

                <div class="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between gap-3">
                    <button type="button" id="apr-prev" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50">
                        ← Anterior
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button" id="apr-draft" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50">
                            Salvar rascunho
                        </button>
                        <button type="button" id="apr-next" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-800 text-white text-sm font-semibold hover:bg-slate-900">
                            Próximo →
                        </button>
                        <button type="button" id="apr-submit" class="hidden inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                            Aprovar APR
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
                    btn.classList.toggle('bg-slate-100', active);
                    btn.classList.toggle('text-slate-800', active);
                    btn.classList.toggle('bg-emerald-50', done);
                    btn.classList.toggle('text-emerald-700', done);
                    btn.classList.toggle('text-slate-500', !active && !done);
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

            btnDraft.addEventListener('click', function () {
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

            function validarStepAtual(stepIdx) {
                const stepEl = steps[stepIdx];
                if (!stepEl) return true;
                limparErrosStep(stepEl);

                if (stepIdx === 0) {
                    const obrigatorios = [
                        'contratante_razao_social',
                        'contratante_cnpj',
                        'contratante_responsavel_nome',
                        'contratante_telefone',
                        'obra_nome',
                        'obra_endereco',
                        'obra_cidade',
                        'obra_uf',
                        'obra_area_setor',
                    ];
                    for (const name of obrigatorios) {
                        const campo = form.querySelector(`[name="${name}"]`);
                        if (!campo || !possuiTexto(campo.value)) {
                            addErroCampo(stepEl, campo, 'Preencha os campos obrigatórios deste passo.');
                            campo?.focus();
                            return false;
                        }
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
                    if (etapas.length === 0 || etapas.every((i) => !possuiTexto(i.value))) {
                        addErroCampo(stepEl, etapas[0], 'Adicione ao menos uma etapa com descrição.');
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

                    const chk = item.querySelector('[data-field="nao_treinado"]');
                    if (chk) chk.name = `${namePrefix}[${idx}][nao_treinado]`;
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
                            <label class="block text-xs font-medium text-slate-600 mb-1">Nome</label>
                            <input type="text" data-field="nome" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Função</label>
                            <input type="text" data-field="funcao" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>
                    </div>
                    <label class="mt-3 inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" data-field="nao_treinado" value="1">
                        Trabalhador sem treinamento específico
                    </label>
                `;
                return el;
            }

            function criarItemEpi() {
                const el = document.createElement('div');
                el.className = 'epi-item rounded-xl border border-slate-200 p-3';
                el.innerHTML = `
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-slate-600">EPI <span class="epi-num">1</span></span>
                        <button type="button" class="remove-epi text-xs text-red-600 hover:text-red-700">Remover</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Descrição</label>
                            <input type="text" data-field="descricao" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Aplicação (opcional)</label>
                            <input type="text" data-field="aplicacao" class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                        </div>
                    </div>
                `;
                return el;
            }

            const etapasList = document.getElementById('etapas-list');
            const equipeList = document.getElementById('equipe-list');
            const episList = document.getElementById('epis-list');

            document.querySelectorAll('.etapa-item input[name^="etapas"]').forEach((input) => input.dataset.field = 'descricao');
            document.querySelectorAll('.equipe-item input[name*="[nome]"]').forEach((input) => input.dataset.field = 'nome');
            document.querySelectorAll('.equipe-item input[name*="[funcao]"]').forEach((input) => input.dataset.field = 'funcao');
            document.querySelectorAll('.equipe-item input[type="checkbox"]').forEach((input) => input.dataset.field = 'nao_treinado');
            document.querySelectorAll('.epi-item input[name*="[descricao]"]').forEach((input) => input.dataset.field = 'descricao');
            document.querySelectorAll('.epi-item input[name*="[aplicacao]"]').forEach((input) => input.dataset.field = 'aplicacao');

            document.getElementById('add-etapa').addEventListener('click', function () {
                etapasList.appendChild(criarItemEtapa());
                renumerarItens('#etapas-list', '.etapa-item', '.etapa-num', 'etapas', ['descricao']);
            });

            document.getElementById('add-equipe').addEventListener('click', function () {
                equipeList.appendChild(criarItemEquipe());
                renumerarItens('#equipe-list', '.equipe-item', '.equipe-num', 'equipe', ['nome', 'funcao']);
            });

            document.getElementById('add-epi').addEventListener('click', function () {
                episList.appendChild(criarItemEpi());
                renumerarItens('#epis-list', '.epi-item', '.epi-num', 'epis', ['descricao', 'aplicacao']);
            });

            document.addEventListener('click', function (ev) {
                if (ev.target.matches('.remove-etapa')) {
                    const itens = etapasList.querySelectorAll('.etapa-item');
                    if (itens.length > 1) ev.target.closest('.etapa-item')?.remove();
                    renumerarItens('#etapas-list', '.etapa-item', '.etapa-num', 'etapas', ['descricao']);
                }

                if (ev.target.matches('.remove-equipe')) {
                    const itens = equipeList.querySelectorAll('.equipe-item');
                    if (itens.length > 1) ev.target.closest('.equipe-item')?.remove();
                    renumerarItens('#equipe-list', '.equipe-item', '.equipe-num', 'equipe', ['nome', 'funcao']);
                }

                if (ev.target.matches('.remove-epi')) {
                    const itens = episList.querySelectorAll('.epi-item');
                    if (itens.length > 1) ev.target.closest('.epi-item')?.remove();
                    renumerarItens('#epis-list', '.epi-item', '.epi-num', 'epis', ['descricao', 'aplicacao']);
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

                set('contratante_razao_social', valorInput('contratante_razao_social'));
                set('contratante_cnpj', valorInput('contratante_cnpj'));
                set('obra_nome', valorInput('obra_nome'));
                set('obra_endereco', valorInput('obra_endereco'));

                const ini = valorInput('atividade_data_inicio');
                const fim = valorInput('atividade_data_termino_prevista');
                set('periodo', `${ini || '-'} a ${fim || '-'}`);

                set('total_etapas', String(etapasList.querySelectorAll('.etapa-item').length));
                set('total_equipe', String(equipeList.querySelectorAll('.equipe-item').length));
                set('total_epis', String(episList.querySelectorAll('.epi-item').length));
            }

            renumerarItens('#etapas-list', '.etapa-item', '.etapa-num', 'etapas', ['descricao']);
            renumerarItens('#equipe-list', '.equipe-item', '.equipe-num', 'equipe', ['nome', 'funcao']);
            renumerarItens('#epis-list', '.epi-item', '.epi-num', 'epis', ['descricao', 'aplicacao']);
            setStep(currentStep);
        });
    </script>
@endpush
