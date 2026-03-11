@extends('layouts.comercial')
@section('title', 'Configurador do Modelo Comercial')

@php
    $layoutDraft = old('layout', $layout ?? []);
    $navigationGroups = [
        'Base' => [
            'dados-modelo' => 'Dados do modelo',
            'textos-apresentacao' => 'Textos da apresentação',
            'servicos-essenciais' => 'Serviços essenciais',
        ],
        'Estrutura visual' => [
            'layout-apresentacao' => 'Blocos ativos',
            'hero-config' => 'Hero',
            'desafios-config' => 'Desafios',
            'solucoes-config' => 'Nossas soluções',
            'palestras-config' => 'Palestras anuais',
            'processo-config' => 'Processo simplificado',
        ],
        'Fechamento' => [
            'investimento-config' => 'Investimento',
            'unidade-config' => 'Unidade',
            'contato-config' => 'Contato final',
        ],
    ];

    $manualTablesOld = old('manual_tables');
    if (is_array($manualTablesOld)) {
        $manualTables = $manualTablesOld;
        $manualTablesOrder = old('manual_tables_order', array_keys($manualTables));
    } else {
        $manualTables = ($tabelasManuais ?? collect())
            ->mapWithKeys(function ($tabela) {
                $rows = $tabela->linhas
                    ->sortBy('ordem')
                    ->mapWithKeys(function ($linha) {
                        return [(string) $linha->id => $linha->valores ?? []];
                    })
                    ->all();

                return [(string) $tabela->id => [
                    'titulo' => $tabela->titulo,
                    'subtitulo' => $tabela->subtitulo,
                    'columns' => $tabela->colunas ?? [],
                    'rows' => $rows,
                ]];
            })
            ->all();
        $manualTablesOrder = ($tabelasManuais ?? collect())
            ->sortBy('ordem')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    $heroSubtitle = old('layout.hero.subtitle', $heroDraft['subtitle'] ?? ($heroDraft['description'] ?? ''));
    $heroTitle = old('layout.hero.title', $heroDraft['title'] ?? $tituloSegmento);
@endphp

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/comercial/apresentacao-modelo.css'])
@endpush

@section('content')
    <div class="cfg-page py-4 py-xl-5">
        <div class="container-fluid px-3 px-xl-4">
            <div class="cfg-shell mx-auto">
                <div class="cfg-topbar d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <a href="{{ route('comercial.apresentacao.show', $segmento) }}"
                           class="btn btn-sm btn-light border mb-3">
                            <i class="bi bi-arrow-left"></i>
                            Voltar para apresentação
                        </a>
                        <h1 class="display-6 fw-bold text-dark mb-2">Modelo comercial da apresentação de {{ $segmentoNome }}</h1>
                        <p class="text-secondary mb-0">
                            Organize o modelo por blocos. O foco aqui é editar sem se perder entre conteúdo, preços e layout.
                        </p>
                    </div>
                    <div class="cfg-topbar__actions d-flex flex-wrap gap-2">
                        <a href="{{ route('comercial.apresentacao.show', $segmento) }}" class="btn btn-outline-secondary">
                            Visualizar apresentação
                        </a>
                    </div>
                </div>

                @if (session('ok'))
                    <div class="alert alert-success border-0 shadow-sm">{{ session('ok') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm">
                        Revise os campos do configurador e tente novamente.
                    </div>
                @endif

                <div class="row g-4 align-items-start">
                    <div class="col-12">
                        <form method="POST" action="{{ route('comercial.apresentacao.modelo.store', $segmento) }}" class="d-grid gap-4">
                            @csrf
                            <input type="hidden" name="comissao_vendedor" value="{{ old('comissao_vendedor', $modelo?->comissao_vendedor) }}">
                            <input type="hidden" name="contato_email" value="{{ old('contato_email', $modelo?->contato_email ?? '') }}">
                            <input type="hidden" name="contato_telefone" value="{{ old('contato_telefone', $modelo?->contato_telefone ?? '') }}">
                            <input type="hidden" name="rodape" value="{{ old('rodape', $modelo?->rodape ?? '') }}">

                            <x-comercial.config-section
                                id="textos-apresentacao"
                                title="Textos da apresentação"
                                data-section="textos-apresentacao"
                                description="Configure a narrativa inicial da proposta comercial.">
                                <div class="row g-4">
                                    <div class="col-lg-4">
                                        <label class="form-label">Introdução linha 1</label>
                                        <textarea name="intro_1" rows="5" class="form-control" placeholder="Primeira linha da apresentação">{{ old('intro_1', $modelo?->intro_1 ?? ($conteudo['intro'][0] ?? '')) }}</textarea>
                                    </div>
                                    <div class="col-lg-4">
                                        <label class="form-label">Introdução linha 2</label>
                                        <textarea name="intro_2" rows="5" class="form-control" placeholder="Segunda linha da apresentação">{{ old('intro_2', $modelo?->intro_2 ?? ($conteudo['intro'][1] ?? '')) }}</textarea>
                                    </div>
                                    <div class="col-lg-4">
                                        <label class="form-label">Mensagem principal</label>
                                        <textarea name="mensagem_principal" rows="5" class="form-control" placeholder="Mensagem principal do modelo">{{ old('mensagem_principal', $modelo?->mensagem_principal ?? '') }}</textarea>
                                    </div>
                                </div>
                            </x-comercial.config-section>

                            <x-comercial.config-section
                                id="servicos-essenciais"
                                title="Serviços essenciais"
                                data-section="servicos-essenciais"
                                description="Cada linha será convertida em um item exibido na apresentação.">
                                <label class="form-label">Lista de serviços</label>
                                <textarea name="servicos" rows="8" class="form-control cfg-codearea" placeholder="PGR e inventário de riscos&#10;PCMSO e gestão de exames ocupacionais&#10;ASO admissional">{{ old('servicos', $servicos) }}</textarea>
                            </x-comercial.config-section>

                            <x-comercial.config-section
                                id="layout-apresentacao"
                                title="Blocos ativos"
                                data-section="layout-apresentacao"
                                description="Escolha quais blocos estruturais serão exibidos no PDF e na apresentação.">
                                <div class="row g-3">
                                    @foreach([
                                        'hero' => 'Hero',
                                        'desafios' => 'Desafios',
                                        'solucoes' => 'Nossas soluções',
                                        'palestras' => 'Palestras anuais',
                                        'processo' => 'Processo simplificado',
                                        'investimento' => 'Investimento',
                                        'unidade' => 'Unidade',
                                        'contato_final' => 'Contato final',
                                    ] as $key => $label)
                                        <div class="col-md-6 col-xl-4">
                                            <label class="card border-0 shadow-sm h-100 cfg-toggle-card">
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <span class="fw-semibold">{{ $label }}</span>
                                                    <span class="form-check m-0 cfg-check-control">
                                                        <input type="hidden" name="layout[{{ $key }}][enabled]" value="0">
                                                        <input class="form-check-input" type="checkbox" value="1"
                                                               name="layout[{{ $key }}][enabled]"
                                                               @checked(old("layout.$key.enabled", $layoutDraft[$key]['enabled'] ?? true))>
                                                    </span>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </x-comercial.config-section>

                            <x-comercial.config-section
                                id="hero-config"
                                title="Hero"
                                data-section="hero-config"
                                description="Título e subtítulo principal da capa da apresentação.">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label">Etiqueta</label>
                                        <input type="text"
                                               class="form-control"
                                               name="layout[hero][badge]"
                                               value="{{ old('layout.hero.badge', $heroDraft['badge'] ?? 'Apresentação comercial') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Título</label>
                                        <input type="text"
                                              class="form-control form-control-lg"
                                              name="layout[hero][title]"
                                               value="{{ $heroTitle }}"
                                               data-sync-titulo
                                               placeholder="Construção Civil">
                                        <input type="hidden" name="titulo" value="{{ old('titulo', $tituloSegmento) }}" data-sync-titulo-target>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Subtítulo</label>
                                        <input type="text"
                                               class="form-control form-control-lg"
                                               name="layout[hero][subtitle]"
                                               value="{{ $heroSubtitle }}"
                                               data-sync-hero-subtitle
                                               placeholder="Sua obra não pode parar">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Texto auxiliar da capa</label>
                                        <textarea class="form-control" rows="3" name="layout[hero][description]">{{ old('layout.hero.description', $heroDraft['description'] ?? '') }}</textarea>
                                    </div>
                                </div>
                            </x-comercial.config-section>

                            <x-comercial.config-section
                                id="desafios-config"
                                title="Desafios"
                                data-section="desafios-config"
                                description="Configure os cards de dores e obstáculos do segmento.">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <label class="form-label mb-1">Etiqueta do bloco</label>
                                        <input type="text" class="form-control mb-3" name="layout[desafios][badge]" value="{{ old('layout.desafios.badge', $desafiosDraft['badge'] ?? 'Desafio do setor') }}">
                                        <label class="form-label mb-1">Título do bloco</label>
                                        <input type="text" class="form-control" name="layout[desafios][title]" value="{{ old('layout.desafios.title', $desafiosDraft['title'] ?? 'Os principais desafios das empresas da construção civil') }}">
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm ms-3 mt-4" data-repeater-add="desafios">
                                        <i class="bi bi-plus-lg"></i>
                                        Adicionar desafio
                                    </button>
                                </div>
                                <div class="cfg-repeater-grid" data-repeater="desafios" data-next-index="{{ count(old('layout.desafios.items', $desafiosDraft['items'] ?? [])) }}">
                                    @foreach(old('layout.desafios.items', $desafiosDraft['items'] ?? []) as $index => $item)
                                        <div class="card border-0 shadow-sm" data-repeater-item>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-end mb-2">
                                                    <button type="button" class="btn btn-sm btn-outline-success" data-repeater-remove><i class="bi bi-check-lg"></i></button>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Título do desafio</label>
                                                    <input type="text" class="form-control" name="layout[desafios][items][{{ $index }}][title]" value="{{ $item['title'] ?? '' }}">
                                                </div>
                                                <div>
                                                    <label class="form-label">Descrição</label>
                                                    <textarea class="form-control" rows="3" name="layout[desafios][items][{{ $index }}][description]">{{ $item['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </x-comercial.config-section>

                            <x-comercial.config-section
                                id="solucoes-config"
                                title="Nossas soluções"
                                data-section="solucoes-config"
                                description="Cadastre os cards de solução que aparecerão no material comercial.">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="flex-grow-1">
                                        <label class="form-label mb-1">Etiqueta do bloco</label>
                                        <input type="text" class="form-control" name="layout[solucoes][badge]" value="{{ old('layout.solucoes.badge', $solucoesDraft['badge'] ?? 'Nossas soluções') }}">
                                        <label class="form-label mb-1">Título do bloco</label>
                                        <input type="text" class="form-control" name="layout[solucoes][title]" value="{{ old('layout.solucoes.title', $solucoesDraft['title'] ?? 'Como resolvemos isso') }}">
                                        <label class="form-label mt-3 mb-1">Texto introdutório</label>
                                        <textarea class="form-control" rows="4" name="layout[solucoes][description]">{{ old('layout.solucoes.description', $solucoesDraft['description'] ?? '') }}</textarea>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm ms-3 mt-4" data-repeater-add="solucoes">
                                        <i class="bi bi-plus-lg"></i>
                                        Adicionar solução
                                    </button>
                                </div>
                                <div class="cfg-repeater-grid" data-repeater="solucoes" data-next-index="{{ count(old('layout.solucoes.cards', $solucoesDraft['cards'] ?? [])) }}">
                                    @foreach(old('layout.solucoes.cards', $solucoesDraft['cards'] ?? []) as $index => $card)
                                        <div class="card border-0 shadow-sm" data-repeater-item>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-end mb-2">
                                                    <button type="button" class="btn btn-sm btn-outline-success" data-repeater-remove><i class="bi bi-check-lg"></i></button>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Título</label>
                                                    <input type="text" class="form-control" name="layout[solucoes][cards][{{ $index }}][title]" value="{{ $card['title'] ?? '' }}">
                                                </div>
                                                <div>
                                                    <label class="form-label">Descrição</label>
                                                    <textarea class="form-control" rows="3" name="layout[solucoes][cards][{{ $index }}][description]">{{ $card['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </x-comercial.config-section>

                            <x-comercial.config-section
                                id="palestras-config"
                                title="Palestras anuais"
                                data-section="palestras-config"
                                description="Monte a grade mensal de palestras do calendário anual.">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Etiqueta do bloco</label>
                                        <input type="text" class="form-control" name="layout[palestras][badge]" value="{{ old('layout.palestras.badge', $palestrasDraft['badge'] ?? 'Calendário anual') }}">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Título do bloco</label>
                                        <input type="text" class="form-control" name="layout[palestras][title]" value="{{ old('layout.palestras.title', $palestrasDraft['title'] ?? 'Palestras conforme o calendário anual') }}">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mb-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-repeater-add="palestras">
                                        <i class="bi bi-plus-lg"></i>
                                        Adicionar palestra
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle cfg-table">
                                        <thead>
                                        <tr>
                                            <th>Mês</th>
                                            <th>Tema</th>
                                            <th style="width: 72px;"></th>
                                        </tr>
                                        </thead>
                                        <tbody data-repeater="palestras" data-next-index="{{ count(old('layout.palestras.items', $palestrasDraft['items'] ?? [])) }}">
                                        @foreach(old('layout.palestras.items', $palestrasDraft['items'] ?? []) as $index => $item)
                                            <tr data-repeater-item>
                                                <td><input type="text" class="form-control" name="layout[palestras][items][{{ $index }}][title]" value="{{ $item['title'] ?? '' }}" placeholder="Janeiro"></td>
                                                <td><input type="text" class="form-control" name="layout[palestras][items][{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Segurança no trabalho"></td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-success" data-repeater-remove><i class="bi bi-check-lg"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </x-comercial.config-section>

                            <x-comercial.config-section
                                id="processo-config"
                                title="Processo simplificado"
                                data-section="processo-config"
                                description="Cadastre as etapas da jornada comercial/operacional do atendimento.">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <label class="form-label mb-1">Etiqueta do bloco</label>
                                        <input type="text" class="form-control mb-3" name="layout[processo][badge]" value="{{ old('layout.processo.badge', $processoDraft['badge'] ?? 'Fluxo de atendimento') }}">
                                        <label class="form-label mb-1">Título do bloco</label>
                                        <input type="text" class="form-control" name="layout[processo][title]" value="{{ old('layout.processo.title', $processoDraft['title'] ?? 'Processo simplificado') }}">
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm ms-3 mt-4" data-repeater-add="processo">
                                        <i class="bi bi-plus-lg"></i>
                                        Adicionar etapa
                                    </button>
                                </div>
                                <div class="cfg-repeater-grid" data-repeater="processo" data-next-index="{{ count(old('layout.processo.items', $processoDraft['items'] ?? [])) }}">
                                    @foreach(old('layout.processo.items', $processoDraft['items'] ?? []) as $index => $step)
                                        <div class="card border-0 shadow-sm" data-repeater-item>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-end mb-2">
                                                    <button type="button" class="btn btn-sm btn-outline-success" data-repeater-remove><i class="bi bi-check-lg"></i></button>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Título da etapa</label>
                                                    <input type="text" class="form-control" name="layout[processo][items][{{ $index }}][title]" value="{{ $step['title'] ?? '' }}" placeholder="Etapa 1">
                                                </div>
                                                <div>
                                                    <label class="form-label">Descrição</label>
                                                    <textarea class="form-control" rows="3" name="layout[processo][items][{{ $index }}][description]">{{ $step['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </x-comercial.config-section>

                            <x-comercial.config-section
                                id="investimento-config"
                                title="Investimento"
                                data-section="investimento-config"
                                description="Cadastre os cards de preço e o bloco principal de ASO que serão exibidos no slide de investimento.">
                                <div class="form-check mb-4 cfg-check-control">
                                    <input type="hidden" name="layout[investimento][enabled]" value="0">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           name="layout[investimento][enabled]"
                                           @checked(old('layout.investimento.enabled', $layoutDraft['investimento']['enabled'] ?? true))>
                                    <label class="form-check-label">Mostrar bloco na apresentação</label>
                                </div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label">Etiqueta do bloco</label>
                                        <input type="text" class="form-control" name="layout[investimento][badge]" value="{{ old('layout.investimento.badge', $layoutDraft['investimento']['badge'] ?? 'Capacitação') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Título do bloco</label>
                                        <input type="text" class="form-control" name="layout[investimento][title]" value="{{ old('layout.investimento.title', $layoutDraft['investimento']['title'] ?? 'Investimento') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Preço principal do ASO</label>
                                        <input type="text" class="form-control" name="layout[investimento][aso_price]" value="{{ old('layout.investimento.aso_price', $layoutDraft['investimento']['aso_price'] ?? '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Título principal do ASO</label>
                                        <input type="text" class="form-control" name="layout[investimento][aso_title]" value="{{ old('layout.investimento.aso_title', $layoutDraft['investimento']['aso_title'] ?? '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Itens do ASO (um por linha)</label>
                                        <textarea class="form-control cfg-codearea" rows="8" name="layout[investimento][aso_items_text]">{{ old('layout.investimento.aso_items_text', $layoutDraft['investimento']['aso_items_text'] ?? '') }}</textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mb-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-repeater-add="investimento">
                                        <i class="bi bi-plus-lg"></i>
                                        Adicionar card de investimento
                                    </button>
                                </div>
                                <div class="cfg-repeater-grid" data-repeater="investimento" data-next-index="{{ count(old('layout.investimento.cards', $layoutDraft['investimento']['cards'] ?? [])) }}">
                                    @foreach(old('layout.investimento.cards', $layoutDraft['investimento']['cards'] ?? []) as $index => $card)
                                        <div class="card border-0 shadow-sm" data-repeater-item>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-end mb-2">
                                                    <button type="button" class="btn btn-sm btn-outline-success" data-repeater-remove><i class="bi bi-check-lg"></i></button>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Título</label>
                                                    <input type="text" class="form-control" name="layout[investimento][cards][{{ $index }}][title]" value="{{ $card['title'] ?? '' }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Valor</label>
                                                    <input type="text" class="form-control" name="layout[investimento][cards][{{ $index }}][value]" value="{{ $card['value'] ?? '' }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Descrição</label>
                                                    <textarea class="form-control" rows="3" name="layout[investimento][cards][{{ $index }}][description]">{{ $card['description'] ?? '' }}</textarea>
                                                </div>
                                                <div>
                                                    <label class="form-label">Itens internos (um por linha)</label>
                                                    <textarea class="form-control" rows="5" name="layout[investimento][cards][{{ $index }}][items]">{{ $card['items'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </x-comercial.config-section>

                            <x-comercial.config-section
                                id="unidade-config"
                                title="Unidade"
                                data-section="unidade-config"
                                description="Defina os dados da unidade e o destaque de atendimento in company.">
                                <div class="form-check mb-4 cfg-check-control">
                                    <input type="hidden" name="layout[unidade][enabled]" value="0">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           name="layout[unidade][enabled]"
                                           @checked(old('layout.unidade.enabled', $layoutDraft['unidade']['enabled'] ?? true))>
                                    <label class="form-check-label">Mostrar bloco na apresentação</label>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Etiqueta do bloco</label>
                                        <input type="text" class="form-control" name="layout[unidade][badge]" value="{{ old('layout.unidade.badge', $layoutDraft['unidade']['badge'] ?? 'Atendimento') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Título do bloco</label>
                                        <input type="text" class="form-control" name="layout[unidade][title]" value="{{ old('layout.unidade.title', $layoutDraft['unidade']['title'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nome da unidade</label>
                                        <input type="text" class="form-control" name="layout[unidade][name]" value="{{ old('layout.unidade.name', $layoutDraft['unidade']['name'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Endereço</label>
                                        <textarea class="form-control" rows="4" name="layout[unidade][address]">{{ old('layout.unidade.address', $layoutDraft['unidade']['address'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Horário</label>
                                        <textarea class="form-control" rows="4" name="layout[unidade][schedule]">{{ old('layout.unidade.schedule', $layoutDraft['unidade']['schedule'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Título do card azul</label>
                                        <input type="text" class="form-control" name="layout[unidade][highlight_title]" value="{{ old('layout.unidade.highlight_title', $layoutDraft['unidade']['highlight_title'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Texto do card azul</label>
                                        <textarea class="form-control" rows="4" name="layout[unidade][highlight_description]">{{ old('layout.unidade.highlight_description', $layoutDraft['unidade']['highlight_description'] ?? '') }}</textarea>
                                    </div>
                                </div>
                            </x-comercial.config-section>

                            <x-comercial.config-section
                                id="contato-config"
                                title="Contato final"
                                data-section="contato-config"
                                description="Configure o último slide com contato, horário, site e CTA final.">
                                <div class="form-check mb-4 cfg-check-control">
                                    <input type="hidden" name="layout[contato_final][enabled]" value="0">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           name="layout[contato_final][enabled]"
                                           @checked(old('layout.contato_final.enabled', $layoutDraft['contato_final']['enabled'] ?? true))>
                                    <label class="form-check-label">Mostrar bloco na apresentação</label>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Etiqueta do bloco</label>
                                        <input type="text" class="form-control" name="layout[contato_final][badge]" value="{{ old('layout.contato_final.badge', $layoutDraft['contato_final']['badge'] ?? 'Contato e próximos passos') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Título</label>
                                        <input type="text" class="form-control" name="layout[contato_final][title]" value="{{ old('layout.contato_final.title', $layoutDraft['contato_final']['title'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Texto do CTA</label>
                                        <input type="text" class="form-control" name="layout[contato_final][cta_label]" value="{{ old('layout.contato_final.cta_label', $layoutDraft['contato_final']['cta_label'] ?? '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Mensagem principal</label>
                                        <textarea class="form-control" rows="4" name="layout[contato_final][description]">{{ old('layout.contato_final.description', $layoutDraft['contato_final']['description'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Telefone</label>
                                        <input type="text" class="form-control" name="layout[contato_final][phone]" value="{{ old('layout.contato_final.phone', $layoutDraft['contato_final']['phone'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">E-mail</label>
                                        <input type="text" class="form-control" name="layout[contato_final][email]" value="{{ old('layout.contato_final.email', $layoutDraft['contato_final']['email'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Endereço principal</label>
                                        <textarea class="form-control" rows="4" name="layout[contato_final][address]">{{ old('layout.contato_final.address', $layoutDraft['contato_final']['address'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Horário de funcionamento</label>
                                        <textarea class="form-control" rows="4" name="layout[contato_final][schedule]">{{ old('layout.contato_final.schedule', $layoutDraft['contato_final']['schedule'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Site</label>
                                        <input type="text" class="form-control" name="layout[contato_final][site]" value="{{ old('layout.contato_final.site', $layoutDraft['contato_final']['site'] ?? '') }}">
                                    </div>
                                </div>
                            </x-comercial.config-section>

                            <div class="cfg-submitbar card border-0 shadow-sm">
                                <div class="card-body d-flex flex-wrap justify-content-end gap-2">
                                    <a href="{{ route('comercial.apresentacao.show', $segmento) }}" class="btn btn-light border">
                                        Cancelar
                                    </a>
                                    <button type="submit" name="submit_action" value="save" class="btn btn-outline-primary">
                                        Salvar modelo
                                    </button>
                                    <button type="submit" name="submit_action" value="generate" class="btn btn-primary">
                                        Gerar apresentação
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template id="repeater-card-template">
        <div class="card border-0 shadow-sm" data-repeater-item>
            <div class="card-body">
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-sm btn-outline-success" data-repeater-remove><i class="bi bi-check-lg"></i></button>
                </div>
                <div class="mb-3">
                    <label class="form-label" data-field-label="title">Título</label>
                    <input type="text" class="form-control" data-field-input="title">
                </div>
                <div>
                    <label class="form-label" data-field-label="description">Descrição</label>
                    <textarea class="form-control" rows="3" data-field-input="description"></textarea>
                </div>
            </div>
        </div>
    </template>

    <template id="repeater-row-template">
        <tr data-repeater-item>
            <td><input type="text" class="form-control" data-field-input="title"></td>
            <td><input type="text" class="form-control" data-field-input="description"></td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-success" data-repeater-remove><i class="bi bi-check-lg"></i></button>
            </td>
        </tr>
    </template>

    <template id="repeater-exam-template">
        <tr data-repeater-item>
            <td><input type="text" class="form-control" data-field-input="title"></td>
            <td><input type="text" class="form-control" data-field-input="value"></td>
            <td>
                <div class="form-check cfg-check-control">
                    <input type="hidden" data-field-hidden="active" value="0">
                    <input class="form-check-input" type="checkbox" value="1" data-field-input="active">
                </div>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-success" data-repeater-remove><i class="bi bi-check-lg"></i></button>
            </td>
        </tr>
    </template>

    <template id="repeater-offer-template">
        <div class="card border-0 shadow-sm" data-repeater-item>
            <div class="card-body">
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-sm btn-outline-success" data-repeater-remove><i class="bi bi-check-lg"></i></button>
                </div>
                <div class="mb-3">
                    <label class="form-label" data-field-label="title">Título</label>
                    <input type="text" class="form-control" data-field-input="title">
                </div>
                <div class="mb-3">
                    <label class="form-label" data-field-label="value">Valor</label>
                    <input type="text" class="form-control" data-field-input="value">
                </div>
                <div class="mb-3">
                    <label class="form-label" data-field-label="description">Descrição</label>
                    <textarea class="form-control" rows="3" data-field-input="description"></textarea>
                </div>
                <div>
                    <label class="form-label" data-field-label="items">Itens internos</label>
                    <textarea class="form-control" rows="5" data-field-input="items"></textarea>
                </div>
            </div>
        </div>
    </template>

    <template id="manual-table-template">
        <div class="manual-table card border-0 shadow-sm" draggable="true" data-table-id="__ID__">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge text-bg-light border manual-table-handle">Arrastar</span>
                        <span class="fw-semibold">Tabela manual</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-table">Remover tabela</button>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Título da tabela</label>
                        <input type="text" class="form-control" name="manual_tables[__ID__][titulo]">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subtítulo</label>
                        <input type="text" class="form-control" name="manual_tables[__ID__][subtitulo]">
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Colunas</label>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-add-col">Adicionar coluna</button>
                    </div>
                    <div class="manual-columns row g-2"></div>
                </div>
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Linhas</label>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-add-row">Adicionar linha</button>
                    </div>
                    <div class="manual-rows d-grid gap-2"></div>
                    <div class="manual-rows-order"></div>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    @vite(['resources/js/comercial/apresentacao-modelo.js'])
@endpush
