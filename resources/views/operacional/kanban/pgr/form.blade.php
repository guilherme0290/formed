@extends(request()->query('origem') === 'cliente' ? 'layouts.cliente' : 'layouts.operacional')


@section('title', 'PGR - ' . $tipoLabel)

@section('content')
    @php

            use App\Helpers\S3Helper;
            $modo = $modo ?? (isset($tarefa) ? 'edit' : 'create');
            $tipoLabel = $tipoLabel ?? 'Matriz';

            // coleção de anexos desta tarefa (ou vazio)
            $anexos = $anexos ?? collect();
            $origem = request()->query('origem'); // 'cliente' ou null

            // rota para o PASSO 1 (selecionar tipo), preservando origem
            $rotaVoltarTipo = route('operacional.kanban.pgr.tipo', [
                'cliente' => $cliente->id,
                'origem'  => $origem,
            ]);

            /** @var string $modo */ // 'create' ou 'edit'
            $modo = $modo ?? 'create';
    @endphp

    <div class="w-full px-2 sm:px-3 md:px-4 xl:px-5 py-4 md:py-6">

        {{-- BOTÃO VOLTAR CORRETO --}}
        <div class="mb-4">
            <a href="{{ $rotaVoltarTipo }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>←</span>
                <span>Voltar</span>
            </a>
        </div>



            <form method="POST"
              enctype="multipart/form-data"
              action="{{ $modo === 'edit'
                    ? route('operacional.kanban.pgr.update', ['tarefa' => $tarefa, 'origem' => $origem])
                    : route('operacional.kanban.pgr.store', ['cliente' => $cliente, 'origem' => $origem]) }}">
            @csrf
            @if($modo === 'edit')
                @method('PUT')
            @endif
            <input type="hidden" name="origem" value="{{ $origem }}">

            <input type="hidden" name="tipo" value="{{ old('tipo', $tipo ?? ($pgr->tipo ?? 'matriz')) }}">


            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                {{-- Cabeçalho --}}
                <div class="bg-emerald-700 px-4 sm:px-5 md:px-6 py-4">
                    <h1 class="text-lg md:text-xl font-semibold text-white mb-1">
                        PGR - {{ $tipoLabel }}
                    </h1>
                    <p class="text-xs md:text-sm text-emerald-100">
                        {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                    </p>
                </div>

                <div class="px-4 sm:px-5 md:px-6 py-5 md:py-6">
                    {{-- ERROS --}}
                    @if($errors->any())
                        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700">
                            <p class="font-medium mb-1">Ocorreram alguns erros ao salvar:</p>
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- NAV DAS ABAS --}}
                    <div class="border-b border-slate-200 mb-4">
                        <nav class="flex gap-6 text-sm">
                            <button type="button"
                                    class="tab-btn border-b-2 border-emerald-500 text-emerald-600 font-semibold pb-2"
                                    data-tab="dados">
                                Dados do PGR
                            </button>

                            <button type="button"
                                    class="tab-btn text-slate-500 hover:text-slate-700 pb-2"
                                    data-tab="anexos">
                                Anexos
                            </button>
                        </nav>
                    </div>

                    {{-- ABA 1: DADOS DO PGR --}}
                    <div id="tab-dados" class="space-y-6">
                        {{-- 1. ART --}}
                        <section>
                            <h2 class="text-sm font-semibold text-slate-800 mb-3">1. ART</h2>

                            @php
                                $comArtOld = old('com_art', isset($pgr) ? (string) (int) $pgr->com_art : null);
                            @endphp
                            <div class="grid grid-cols-1 gap-3 mb-3">
                                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <input type="radio"
                                           id="com_art_sim"
                                           name="com_art"
                                           value="1"
                                           class="h-4 w-4 text-slate-900"
                                           @if($comArtOld === '1') checked @endif
                                           @if(!($artDisponivel ?? true)) disabled @endif
                                           required>
                                    <span>Com ART</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <input type="radio"
                                           id="com_art_nao"
                                           name="com_art"
                                           value="0"
                                           class="h-4 w-4 text-slate-900"
                                           @if($comArtOld === '0') checked @endif>
                                    <span>Sem ART</span>
                                </label>
                            </div>

                            <div id="alert-art"
                                 class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                ⚠ Custo adicional de R$ {{ number_format($valorArt ?? 500, 2, ',', '.') }}
                            </div>

                            @if(!($artDisponivel ?? true))
                                <div class="mt-2 text-xs text-slate-500">
                                    ART não disponível no contrato atual do cliente.
                                </div>
                            @endif
                        </section>

                        {{-- 2. CONTRATANTE / OBRA (somente PGR específico) --}}
                        @if(($tipo ?? ($pgr->tipo ?? 'matriz')) === 'especifico')
                            <section class="mb-6 bg-white border border-slate-200 rounded-2xl p-5">
                                <h2 class="text-sm font-semibold text-slate-800 mb-4">2. Contratante</h2>

                            <div class="grid md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Nome/Razão
                                        Social</label>
                                    <input type="text" name="contratante_nome"
                                           value="{{ old('contratante_nome', $pgr->contratante_nome ?? '') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">CNPJ</label>
                                    <input type="text" name="contratante_cnpj"
                                           value="{{ old('contratante_cnpj', $pgr->contratante_cnpj ?? '') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                            </div>

                            <h2 class="text-sm font-semibold text-slate-800 mb-3 mt-2">3. Obra</h2>

                            <div class="grid md:grid-cols-2 gap-4 mb-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Nome da Obra</label>
                                    <input type="text" name="obra_nome"
                                           value="{{ old('obra_nome', $pgr->obra_nome ?? '') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Endereço da
                                        Obra</label>
                                    <input type="text" name="obra_endereco"
                                           value="{{ old('obra_endereco', $pgr->obra_endereco ?? '') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">CEJ/CNO</label>
                                    <input type="text" name="obra_cej_cno"
                                           value="{{ old('obra_cej_cno', $pgr->obra_cej_cno ?? '') }}"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Turno(s) de
                                        Trabalho</label>
                                    <input type="text" name="obra_turno_trabalho"
                                           value="{{ old('obra_turno_trabalho', $pgr->obra_turno_trabalho ?? '') }}"
                                           placeholder="Ex: Diurno (7h às 17h)"
                                           class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                                </div>
                            </div>
                        </section>
                    @endif

                    {{-- 2. Trabalhadores --}}
                    <section>
                        <h2 class="text-sm font-semibold text-slate-800 mb-3">2. Trabalhadores</h2>

                        <div class="grid grid-cols-1 md:grid-cols-[1.2fr,1.2fr,auto] gap-3 items-end">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Funcionários Homens
                                </label>
                                <input type="number" name="qtd_homens"
                                       value="{{ old('qtd_homens', $pgr->qtd_homens ?? 0) }}"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Funcionárias Mulheres
                                </label>
                                <input type="number" name="qtd_mulheres"
                                       value="{{ old('qtd_mulheres', $pgr->qtd_mulheres ?? 0) }}"
                                       class="w-full rounded-lg border-slate-200 text-sm px-3 py-2">
                            </div>

                            <div class="flex flex-col items-center justify-center">
                                <span class="text-xs font-medium text-slate-500 mb-1">Total</span>
                                <div id="total-trabalhadores"
                                     class="inline-flex items-center justify-center px-4 py-2 rounded-full bg-sky-500 text-white text-sm font-semibold">
                                    0
                                </div>
                            </div>
                        </div>
                    </section>

                        {{-- 4. FUNÇÕES E CARGOS --}}
                        <section>
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <h2 class="text-sm font-semibold text-slate-800">
                                    5. Funções e Cargos
                                </h2>

                                <x-funcoes.create-button label="Cadastrar nova função" variant="emerald" :allowCreate="true" />
                            </div>

                            @php
                                $funcoesForm = old('funcoes');

                                if ($funcoesForm === null) {
                                    if (isset($pgr) && is_array($pgr->funcoes)) {
                                        $funcoesForm = $pgr->funcoes;
                                    } else {
                                        $funcoesForm = [
                                            [
                                                'funcao_id' => null,
                                                'quantidade' => 1,
                                                'cbo' => null,
                                                'descricao' => null,
                                                'nr_altura' => 0,
                                                'nr_eletricidade' => 0,
                                                'nr_espaco_confinado' => 0,
                                                'nr_definido' => 0,
                                            ],
                                        ];
                                    }
                                }
                            @endphp

                            <div id="funcoes-wrapper" class="space-y-3">
                                @foreach($funcoesForm as $idx => $f)
                                    <div class="funcao-item rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
                                         data-funcao-index="{{ $idx }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <span
                                                class="badge-funcao text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-semibold">
                                                Função {{ $idx + 1 }}
                                            </span>

                                            <button type="button"
                                                    class="btn-remove-funcao inline-flex items-center gap-1 text-[11px] text-red-600 hover:text-red-800">
                                                ✕ Remover
                                            </button>
                                        </div>

                                    <div class="grid grid-cols-12 gap-3">
                                        <div class="col-span-5 funcao-select-wrapper">
                                            <x-funcoes.select-with-create
                                                name="funcoes[{{ $idx }}][funcao_id]"
                                                field-id="funcoes_{{ $idx }}_funcao_id"
                                                label="Cargo"
                                                help-text="Funções listadas por GHE, pré-configuradas pelo vendedor/comercial."
                                                :funcoes="$funcoes"
                                                :selected="old('funcoes.'.$idx.'.funcao_id', $f['funcao_id'] ?? null)"
                                                :show-create="true"
                                                :allowCreate="true"
                                            />
                                        </div>

                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-slate-500 mb-1">Qtd</label>
                                            <input type="number"
                                                   name="funcoes[{{ $idx }}][quantidade]"
                                                   class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                                   value="{{ old('funcoes.'.$idx.'.quantidade', $f['quantidade'] ?? 1) }}"
                                                   min="1">
                                        </div>

                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-slate-500 mb-1">CBO</label>
                                            <input type="text"
                                                   name="funcoes[{{ $idx }}][cbo]"
                                                   class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                                   value="{{ old('funcoes.'.$idx.'.cbo', $f['cbo'] ?? '') }}"
                                                   placeholder="0000-00">
                                        </div>

                                        <div class="col-span-3">
                                            <label class="block text-xs font-medium text-slate-500 mb-1">
                                                Descrição (opcional)
                                            </label>
                                            <input type="text"
                                                   name="funcoes[{{ $idx }}][descricao]"
                                                   class="w-full rounded-lg border-slate-200 text-sm px-3 py-2"
                                                   value="{{ old('funcoes.'.$idx.'.descricao', $f['descricao'] ?? '') }}"
                                                   placeholder="Atividades...">
                                        </div>

                                        <div class="col-span-12">
                                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                                <span class="text-slate-500">NRs:</span>
                                                <div class="flex flex-wrap gap-1" data-nr-tags></div>
                                                <span class="text-slate-400" data-nr-empty>Nenhuma definida</span>
                                                <button type="button"
                                                        class="btn-definir-nr inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                                                    Definir NRs
                                                </button>
                                            </div>
                                            <input type="hidden"
                                                   name="funcoes[{{ $idx }}][nr_altura]"
                                                   value="{{ old('funcoes.'.$idx.'.nr_altura', $f['nr_altura'] ?? 0) }}">
                                            <input type="hidden"
                                                   name="funcoes[{{ $idx }}][nr_eletricidade]"
                                                   value="{{ old('funcoes.'.$idx.'.nr_eletricidade', $f['nr_eletricidade'] ?? 0) }}">
                                            <input type="hidden"
                                                   name="funcoes[{{ $idx }}][nr_espaco_confinado]"
                                                   value="{{ old('funcoes.'.$idx.'.nr_espaco_confinado', $f['nr_espaco_confinado'] ?? 0) }}">
                                            <input type="hidden"
                                                   name="funcoes[{{ $idx }}][nr_definido]"
                                                   value="{{ old('funcoes.'.$idx.'.nr_definido', $f['nr_definido'] ?? 0) }}">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-end mt-3">
                            <button type="button" id="btn-add-funcao"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600">
                                <span>+</span>
                                <span>Adicionar</span>
                            </button>
                        </div>

                        @error('funcoes')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </section>

                    {{-- Rodapé --}}
                    <div class="flex flex-col md:flex-row gap-3 mt-4">
                        <a href="{{ $rotaVoltarTipo }}"
                           class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                            Voltar
                        </a>

                            <button type="submit"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                                {{ $modo === 'edit' ? 'Atualizar PGR' : 'Criar PGR' }}
                            </button>
                        </div>
                    </div>

                    {{-- ABA 2: ANEXOS --}}
                    <div id="tab-anexos" class="space-y-4 hidden">
                        <p class="text-xs text-slate-600">
                            Anexe aqui documentos relacionados ao PGR (PDF, DOC, DOCX, imagens).
                            Você pode arrastar e soltar ou clicar na área abaixo.
                        </p>

                        {{-- Dropzone --}}
                        <div id="pgr-dropzone-anexos"
                             class="flex flex-col items-center justify-center px-6 py-10 border-2 border-dashed rounded-2xl
                        border-slate-300 bg-slate-50 text-center cursor-pointer
                        hover:border-emerald-400 hover:bg-emerald-50 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M3 15.75V18a3 3 0 003 3h12a3 3 0 003-3v-2.25M16.5 9.75L12 5.25m0 0L7.5 9.75M12 5.25V15"/>
                            </svg>
                            <p class="text-sm text-slate-700">
                                Arraste arquivos aqui
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1">
                                ou clique para selecionar
                            </p>

                            <input id="pgr-input-anexos"
                                   type="file"
                                   name="anexos[]"
                                   multiple
                                   class="hidden">
                        </div>

                        {{-- Lista de arquivos selecionados (novos) --}}
                        <ul id="pgr-lista-anexos" class="mt-3 text-xs text-slate-600 space-y-1"></ul>

                        {{-- Anexos já salvos --}}
                        @if($modo === 'edit')
                            <div class="mt-6">
                                <h3 class="text-sm font-semibold text-slate-800 mb-3">
                                    Anexos desta tarefa
                                </h3>

                                @if($anexos->isEmpty())
                                    <p class="text-xs text-slate-400">
                                        Nenhum anexo cadastrado ainda.
                                    </p>
                                @else
                                    <ul class="divide-y divide-slate-100 border border-slate-200 rounded-2xl overflow-hidden">
                                        @foreach($anexos as $anexo)
                                            @php
                                                $ext = strtolower(pathinfo($anexo->nome_original, PATHINFO_EXTENSION));

                                                $iconClasses = match($ext) {
                                                    'pdf'          => 'bg-red-100 text-red-600',
                                                    'doc', 'docx'  => 'bg-blue-100 text-blue-600',
                                                    'png', 'jpg', 'jpeg' => 'bg-amber-100 text-amber-600',
                                                    default        => 'bg-slate-100 text-slate-600',
                                                };

                                                $sizeKb = $anexo->tamanho
                                                    ? round($anexo->tamanho / 1024, 1)
                                                    : null;
                                            @endphp

                                            <li class="flex items-center justify-between px-4 py-3">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div
                                                        class="h-9 w-9 rounded-xl flex items-center justify-center text-[11px] font-semibold {{ $iconClasses }}">
                                                        {{ strtoupper($ext ?: 'ARQ') }}
                                                    </div>

                                                    <div class="min-w-0">
                                                        <p class="text-sm text-slate-800 truncate max-w-xs">
                                                            {{ $anexo->nome_original }}
                                                        </p>
                                                        <p class="text-[11px] text-slate-400">
                                                            @if($sizeKb)
                                                                {{ number_format($sizeKb, 1, ',', '.') }} KB ·
                                                            @endif
                                                            Enviado em {{ $anexo->created_at?->format('d/m/Y H:i') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('operacional.anexos.view', $anexo) }}"
                                                       target="_blank"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium
                                      border border-slate-200 text-slate-700 hover:bg-slate-50">
                                                        Ver
                                                    </a>

                                                    <a href="{{ route('operacional.anexos.download', $anexo) }}"
                                                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium
                                      bg-emerald-500 text-white hover:bg-emerald-600">
                                                        Download
                                                    </a>

                                                    <button type="button"
                                                            class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-red-100
               text-red-500 hover:bg-red-50 text-xs"
                                                            title="Excluir anexo"
                                                            data-delete-anexo="{{ route('operacional.anexos.destroy', $anexo) }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                             viewBox="0 0 24 24"
                                                             fill="none"
                                                             stroke="currentColor"
                                                             stroke-width="1.7"
                                                             class="w-4 h-4">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                  d="M9.75 9.75v6.75M14.25 9.75v6.75M4.5 6.75h15M18.75 6.75
                 l-.861 12.067A2.25 2.25 0 0 1 15.648 21H8.352a2.25 2.25 0 0 1-2.241-2.183L5.25 6.75M9 6.75V4.5
                 A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5v2.25"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif

                        {{-- Botão de salvar na aba Anexos (mesmo do Dados) --}}
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <button type="submit"
                                    class="w-full px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium shadow-sm">
                                {{ $modo === 'edit' ? 'Atualizar PGR' : 'Criar PGR' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if($modo === 'edit')
            <form id="pgr-form-delete-anexo" method="POST" style="display:none;">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>

    <div id="nrModal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/50 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-900 text-white flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold">Atividades especiais (NR)</h2>
                    <p class="text-xs text-slate-300">Selecione se esta funcao exige alguma NR.</p>
                </div>
                <button type="button" id="btnFecharNrModal"
                        class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-slate-800 text-white">X</button>
            </div>
            <div class="p-5 space-y-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="nr-option" data-nr="altura">
                    <span>Trabalho em altura (NR-35)</span>
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="nr-option" data-nr="eletricidade">
                    <span>Eletricidade (NR-10)</span>
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="nr-option" data-nr="espaco_confinado">
                    <span>Espaço confinado (NR-33)</span>
                </label>
                <label class="flex items-center gap-2 text-sm pt-2 border-t border-slate-100">
                    <input type="checkbox" class="nr-option" data-nr="nenhuma">
                    <span>Nenhuma</span>
                </label>
            </div>
            <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" id="btnCancelarNrModal"
                        class="px-3 py-2 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="button" id="btnSalvarNrModal"
                        class="px-4 py-2 text-xs rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700">
                    Salvar
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // ========= ABA TABS =========
                const tabButtons = document.querySelectorAll('.tab-btn');
                const tabDados = document.getElementById('tab-dados');
                const tabAnexos = document.getElementById('tab-anexos');

                tabButtons.forEach(btn => {
                    btn.addEventListener('click', function () {
                        const tab = this.dataset.tab;

                        if (tab === 'dados') {
                            tabDados.classList.remove('hidden');
                            tabAnexos.classList.add('hidden');
                        } else {
                            tabAnexos.classList.remove('hidden');
                            tabDados.classList.add('hidden');
                        }

                        tabButtons.forEach(b => {
                            b.classList.remove('border-b-2', 'border-emerald-500', 'text-emerald-600', 'font-semibold');
                            b.classList.add('text-slate-500');
                        });

                        this.classList.remove('text-slate-500');
                        this.classList.add('border-b-2', 'border-emerald-500', 'text-emerald-600', 'font-semibold');
                    });
                });

                // ========= ART =========
                const radiosArt = document.querySelectorAll('input[name="com_art"]');
                const alertArt = document.getElementById('alert-art');

                function aplicarEstadoArt(valor) {
                    if (alertArt) {
                        alertArt.style.display = (String(valor) === '1') ? 'block' : 'none';
                    }
                }

                radiosArt.forEach(radio => {
                    radio.addEventListener('change', () => {
                        aplicarEstadoArt(radio.value);
                    });
                });

                const checkedArt = document.querySelector('input[name="com_art"]:checked');
                aplicarEstadoArt(checkedArt ? checkedArt.value : '');


                // ========= TOTAL TRABALHADORES =========
                const inputHomens = document.querySelector('input[name="qtd_homens"]');
                const inputMulheres = document.querySelector('input[name="qtd_mulheres"]');
                const totalEl = document.getElementById('total-trabalhadores');

                function atualizarTotal() {
                    const h = parseInt(inputHomens?.value || '0', 10);
                    const m = parseInt(inputMulheres?.value || '0', 10);
                    totalEl.textContent = String(h + m);
                }

                if (inputHomens && inputMulheres && totalEl) {
                    inputHomens.addEventListener('input', atualizarTotal);
                    inputMulheres.addEventListener('input', atualizarTotal);
                    atualizarTotal();
                }

                // ========= FUNÇÕES DINÂMICAS =========
                const wrapper = document.getElementById('funcoes-wrapper');
                const btnAdd = document.getElementById('btn-add-funcao');
                const nrModal = document.getElementById('nrModal');
                const btnFecharNrModal = document.getElementById('btnFecharNrModal');
                const btnCancelarNrModal = document.getElementById('btnCancelarNrModal');
                const btnSalvarNrModal = document.getElementById('btnSalvarNrModal');
                const nrOptions = nrModal ? nrModal.querySelectorAll('.nr-option') : [];
                let nrItemAtual = null;

                function getNrInputs(item) {
                    return {
                        altura: item.querySelector('input[name$="[nr_altura]"]'),
                        eletricidade: item.querySelector('input[name$="[nr_eletricidade]"]'),
                        espacoConfinado: item.querySelector('input[name$="[nr_espaco_confinado]"]'),
                        definido: item.querySelector('input[name$="[nr_definido]"]'),
                    };
                }

                function renderNrTags(item) {
                    const tags = item.querySelector('[data-nr-tags]');
                    const empty = item.querySelector('[data-nr-empty]');
                    const inputs = getNrInputs(item);
                    if (!tags || !inputs.altura || !inputs.eletricidade || !inputs.espacoConfinado) {
                        return;
                    }

                    const ativos = [];
                    if (inputs.altura.value === '1') ativos.push('NR-35');
                    if (inputs.eletricidade.value === '1') ativos.push('NR-10');
                    if (inputs.espacoConfinado.value === '1') ativos.push('NR-33');

                    tags.innerHTML = '';
                    if (!ativos.length) {
                        const definido = inputs.definido?.value === '1';
                        if (empty) {
                            empty.textContent = definido ? 'Nenhuma' : 'Nenhuma definida';
                            empty.classList.remove('hidden');
                        }
                        return;
                    }

                    if (empty) empty.classList.add('hidden');
                    ativos.forEach(label => {
                        const span = document.createElement('span');
                        span.className = 'inline-flex items-center rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-700';
                        span.textContent = label;
                        tags.appendChild(span);
                    });
                }

                function resetNr(item) {
                    const inputs = getNrInputs(item);
                    if (inputs.altura) inputs.altura.value = '0';
                    if (inputs.eletricidade) inputs.eletricidade.value = '0';
                    if (inputs.espacoConfinado) inputs.espacoConfinado.value = '0';
                    if (inputs.definido) inputs.definido.value = '0';
                    renderNrTags(item);
                }

                function abrirNrModal(item) {
                    if (!nrModal || !item) return;
                    nrItemAtual = item;
                    const inputs = getNrInputs(item);
                    nrOptions.forEach(opt => {
                        const nr = opt.getAttribute('data-nr');
                        if (nr === 'altura') opt.checked = inputs.altura?.value === '1';
                        if (nr === 'eletricidade') opt.checked = inputs.eletricidade?.value === '1';
                        if (nr === 'espaco_confinado') opt.checked = inputs.espacoConfinado?.value === '1';
                        if (nr === 'nenhuma') {
                            const nenhum = (inputs.altura?.value !== '1' && inputs.eletricidade?.value !== '1' && inputs.espacoConfinado?.value !== '1');
                            opt.checked = nenhum;
                        }
                    });
                    nrModal.classList.remove('hidden');
                    nrModal.classList.add('flex');
                }

                function fecharNrModal() {
                    if (!nrModal) return;
                    nrModal.classList.add('hidden');
                    nrModal.classList.remove('flex');
                    nrItemAtual = null;
                }

                if (wrapper && btnAdd) {
                    btnAdd.addEventListener('click', function () {
                        const itens = wrapper.querySelectorAll('.funcao-item');
                        const novoIndex = itens.length;

                        const base = itens[itens.length - 1];
                        const clone = base.cloneNode(true);

                        clone.dataset.funcaoIndex = String(novoIndex);
                        clone.removeAttribute('data-funcao-ultimo');

                        clone.querySelectorAll('input, select').forEach(function (el) {
                            if (el.name && el.name.includes('funcoes[')) {
                                el.name = el.name.replace(/\[\d+]/, '[' + novoIndex + ']');
                            }

                            if (el.tagName === 'SELECT') {
                                el.value = '';
                            } else if (el.name.includes('[quantidade]')) {
                                el.value = '1';
                            } else if (el.name.includes('[nr_')) {
                                el.value = '0';
                            } else {
                                el.value = '';
                            }

                            if (el.id && el.id.startsWith('funcoes_')) {
                                el.id = el.id.replace(/_\d+_funcao_id$/, '_' + novoIndex + '_funcao_id');
                            }
                        });

                        const badge = clone.querySelector('.badge-funcao');
                        if (badge) {
                            badge.textContent = 'Função ' + (novoIndex + 1);
                        }

                        wrapper.appendChild(clone);
                        renderNrTags(clone);
                    });

                    // 🔹 NOVO: remover função com delegação de evento
                    wrapper.addEventListener('click', function (e) {
                        const btn = e.target.closest('.btn-remove-funcao');
                        if (!btn) return;

                        const itens = wrapper.querySelectorAll('.funcao-item');
                        if (itens.length <= 1) {
                            window.uiAlert('É necessário pelo menos uma função.');
                            return;
                        }

                        const item = btn.closest('.funcao-item');
                        if (item) {
                            item.remove();
                            reindexFuncoes(wrapper);
                        }
                    });
                }

                if (wrapper) {
                    wrapper.querySelectorAll('.funcao-item').forEach(renderNrTags);

                    wrapper.addEventListener('change', function (e) {
                        const target = e.target;
                        if (!(target instanceof HTMLSelectElement)) return;
                        if (!target.name || !target.name.includes('[funcao_id]')) return;

                        const item = target.closest('.funcao-item');
                        if (!item) return;

                        const valor = target.value || '';
                        const ultimo = item.getAttribute('data-funcao-ultimo') || '';
                        if (!valor) {
                            resetNr(item);
                            item.setAttribute('data-funcao-ultimo', '');
                            return;
                        }

                        if (ultimo !== valor) {
                            resetNr(item);
                            item.setAttribute('data-funcao-ultimo', valor);
                        }

                        const definido = item.querySelector('input[name$="[nr_definido]"]')?.value;
                        if (definido !== '1') {
                            abrirNrModal(item);
                        }
                    });

                    wrapper.addEventListener('click', function (e) {
                        const btn = e.target.closest('.btn-definir-nr');
                        if (!btn) return;
                        const item = btn.closest('.funcao-item');
                        if (!item) return;
                        abrirNrModal(item);
                    });
                }

                if (nrModal) {
                    nrModal.addEventListener('click', function (e) {
                        if (e.target === nrModal) {
                            fecharNrModal();
                        }
                    });
                }

                btnFecharNrModal?.addEventListener('click', fecharNrModal);
                btnCancelarNrModal?.addEventListener('click', fecharNrModal);

                nrOptions.forEach(opt => {
                    opt.addEventListener('change', function () {
                        const nr = this.getAttribute('data-nr');
                        if (nr === 'nenhuma' && this.checked) {
                            nrOptions.forEach(o => {
                                if (o.getAttribute('data-nr') !== 'nenhuma') {
                                    o.checked = false;
                                }
                            });
                        }
                        if (nr !== 'nenhuma' && this.checked) {
                            const none = nrModal.querySelector('.nr-option[data-nr="nenhuma"]');
                            if (none) none.checked = false;
                        }
                    });
                });

                btnSalvarNrModal?.addEventListener('click', function () {
                    if (!nrItemAtual) return;
                    const inputs = getNrInputs(nrItemAtual);
                    if (!inputs.altura || !inputs.eletricidade || !inputs.espacoConfinado || !inputs.definido) {
                        fecharNrModal();
                        return;
                    }

                    const selecionados = {
                        altura: nrModal.querySelector('.nr-option[data-nr="altura"]')?.checked,
                        eletricidade: nrModal.querySelector('.nr-option[data-nr="eletricidade"]')?.checked,
                        espacoConfinado: nrModal.querySelector('.nr-option[data-nr="espaco_confinado"]')?.checked,
                        nenhuma: nrModal.querySelector('.nr-option[data-nr="nenhuma"]')?.checked,
                    };

                    if (selecionados.nenhuma) {
                        inputs.altura.value = '0';
                        inputs.eletricidade.value = '0';
                        inputs.espacoConfinado.value = '0';
                    } else {
                        inputs.altura.value = selecionados.altura ? '1' : '0';
                        inputs.eletricidade.value = selecionados.eletricidade ? '1' : '0';
                        inputs.espacoConfinado.value = selecionados.espacoConfinado ? '1' : '0';
                    }

                    inputs.definido.value = '1';
                    renderNrTags(nrItemAtual);
                    fecharNrModal();
                });

                // função auxiliar para reindexar os índices/names/labels
                function reindexFuncoes(wrapper) {
                    const itens = wrapper.querySelectorAll('.funcao-item');

                    itens.forEach((item, idx) => {
                        item.dataset.funcaoIndex = String(idx);

                        const badge = item.querySelector('.badge-funcao');
                        if (badge) {
                            badge.textContent = 'Função ' + (idx + 1);
                        }

                        item.querySelectorAll('input, select').forEach(function (el) {
                            if (el.name && el.name.includes('funcoes[')) {
                                el.name = el.name.replace(/funcoes\[\d+]/, 'funcoes[' + idx + ']');
                            }

                            if (el.id && /^funcoes_\d+_funcao_id$/.test(el.id)) {
                                el.id = 'funcoes_' + idx + '_funcao_id';
                            }
                        });
                    });
                }

                // ========= DROPZONE ANEXOS PGR =========
                const dropzone = document.getElementById('pgr-dropzone-anexos');
                const inputFiles = document.getElementById('pgr-input-anexos');
                const lista = document.getElementById('pgr-lista-anexos');

                if (dropzone && inputFiles && lista) {
                    function atualizarLista() {
                        lista.innerHTML = '';
                        if (!inputFiles.files.length) {
                            const li = document.createElement('li');
                            li.textContent = 'Nenhum arquivo selecionado.';
                            lista.appendChild(li);
                            return;
                        }

                        Array.from(inputFiles.files).forEach(file => {
                            const li = document.createElement('li');
                            li.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                            lista.appendChild(li);
                        });
                    }

                    dropzone.addEventListener('click', () => inputFiles.click());
                    inputFiles.addEventListener('change', atualizarLista);

                    dropzone.addEventListener('dragover', e => {
                        e.preventDefault();
                        dropzone.classList.add('border-emerald-400', 'bg-emerald-50');
                    });

                    dropzone.addEventListener('dragleave', e => {
                        e.preventDefault();
                        dropzone.classList.remove('border-emerald-400', 'bg-emerald-50');
                    });

                    dropzone.addEventListener('drop', e => {
                        e.preventDefault();
                        dropzone.classList.remove('border-emerald-400', 'bg-emerald-50');
                        if (!e.dataTransfer.files.length) return;
                        inputFiles.files = e.dataTransfer.files;
                        atualizarLista();
                    });

                    atualizarLista();
                }

                // ========= DELETE ANEXO =========
                const deleteButtons = document.querySelectorAll('[data-delete-anexo]');
                const formDelete = document.getElementById('pgr-form-delete-anexo');

                if (formDelete && deleteButtons.length) {
                    deleteButtons.forEach(btn => {
                        btn.addEventListener('click', async function () {
                            const url = this.dataset.deleteAnexo;
                            if (!url) return;

                            const ok = await window.uiConfirm('Deseja realmente excluir este anexo?');
                            if (!ok) return;

                            formDelete.action = url;
                            formDelete.submit();
                        });
                    });
                }

                // ========= MÁSCARA / VALIDAÇÃO CNPJ CONTRATANTE =========
                const cnpjInput = document.querySelector('input[name="contratante_cnpj"]');
                if (cnpjInput) {
                    cnpjInput.addEventListener('input', function () {
                        var v = cnpjInput.value.replace(/\D/g, '');
                        v = v.slice(0, 14);

                        if (v.length > 12) {
                            cnpjInput.value = v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})/, "$1.$2.$3/$4-$5");
                        } else if (v.length > 8) {
                            cnpjInput.value = v.replace(/(\d{2})(\d{3})(\d{3})(\d{1,4})/, "$1.$2.$3/$4");
                        } else if (v.length > 5) {
                            cnpjInput.value = v.replace(/(\d{2})(\d{3})(\d{1,3})/, "$1.$2.$3");
                        } else if (v.length > 2) {
                            cnpjInput.value = v.replace(/(\d{2})(\d{1,3})/, "$1.$2");
                        } else {
                            cnpjInput.value = v;
                        }
                    });

                    cnpjInput.addEventListener('blur', function () {
                        var cnpjLimpo = cnpjInput.value.replace(/\D/g, '');

                        if (cnpjLimpo === '') {
                            limparErroCNPJ(cnpjInput);
                            return;
                        }

                        if (!cnpjValido(cnpjLimpo)) {
                            mostrarErroCNPJ(cnpjInput, 'CNPJ inválido');
                        } else {
                            limparErroCNPJ(cnpjInput);
                        }
                    });
                }

                function cnpjValido(cnpj) {
                    if (!cnpj || cnpj.length !== 14) return false;
                    if (/^(\d)\1{13}$/.test(cnpj)) return false;

                    var tamanho = 12;
                    var numeros = cnpj.substring(0, tamanho);
                    var digitos = cnpj.substring(tamanho);
                    var soma = 0;
                    var pos = tamanho - 7;

                    for (var i = tamanho; i >= 1; i--) {
                        soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
                        if (pos < 2) pos = 9;
                    }

                    var resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
                    if (resultado !== parseInt(digitos.charAt(0))) return false;

                    tamanho = 13;
                    numeros = cnpj.substring(0, tamanho);
                    soma = 0;
                    pos = tamanho - 7;

                    for (var j = tamanho; j >= 1; j--) {
                        soma += parseInt(numeros.charAt(tamanho - j)) * pos--;
                        if (pos < 2) pos = 9;
                    }

                    resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
                    if (resultado !== parseInt(digitos.charAt(1))) return false;

                    return true;
                }

                function mostrarErroCNPJ(input, mensagem) {
                    limparErroCNPJ(input);

                    input.style.borderColor = '#dc2626';
                    var p = document.createElement('p');
                    p.className = 'cnpj-error';
                    p.style.color = '#dc2626';
                    p.style.fontSize = '12px';
                    p.style.marginTop = '4px';
                    p.textContent = mensagem;

                    if (input.parentNode) {
                        input.parentNode.appendChild(p);
                    }
                }

                function limparErroCNPJ(input) {
                    input.style.borderColor = '';
                    if (!input.parentNode) return;
                    var erro = input.parentNode.querySelector('.cnpj-error');
                    if (erro) erro.remove();
                }
            });
        </script>

    @endpush
@endsection
