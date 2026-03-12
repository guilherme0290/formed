<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ModeloComercial;
use App\Models\ModeloComercialExame;
use App\Models\ModeloComercialItem;
use App\Models\ModeloComercialPreco;
use App\Models\ModeloComercialTabela;
use App\Models\ModeloComercialTabelaLinha;
use App\Models\ModeloComercialTreinamento;
use App\Models\ExamesTabPreco;
use App\Models\EsocialTabPreco;
use App\Models\Proposta;
use App\Models\Servico;
use App\Models\TabelaPrecoPadrao;
use App\Models\TreinamentoNrsTabPreco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApresentacaoController extends Controller
{
    private const SESSION_KEY = 'apresentacao_proposta';

    private const SEGMENTOS = [
        'construcao-civil' => 'Construção Civil',
        'industria' => 'Indústria',
        'comercio' => 'Comércio / Varejo',
        'restaurante' => 'Restaurante / Alimentação',
    ];
    private const SEGMENTO_TITULOS = [
        'construcao-civil' => 'CONSTRUÇÃO CIVIL',
        'industria' => 'INDÚSTRIA',
        'comercio' => 'COMÉRCIO / VAREJO / SUPERMERCADOS',
        'restaurante' => 'RESTAURANTE / ALIMENTAÇÃO',
    ];

    private const TREINAMENTOS_NR_PADRAO = [
        'NR-01',
        'NR-05',
        'NR-06',
        'NR-10',
        'NR-11',
        'NR-12',
        'NR-18',
        'NR-20',
        'NR-33',
        'NR-35',
    ];

    public function cliente(Request $request)
    {
        if (!$request->boolean('preserve')) {
            $request->session()->forget(self::SESSION_KEY);
        }

        $user = $request->user();
        $empresaId = $user->empresa_id;
        $isMaster = $user->hasPapel('Master');

        $propostas = Proposta::query()
            ->with('cliente')
            ->where('empresa_id', $empresaId)
            ->when(!$isMaster, fn ($q) => $q->where('vendedor_id', $user->id))
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'cliente_id', 'codigo', 'status', 'created_at', 'valor_total']);

        $draft = $request->session()->get(self::SESSION_KEY . '.cliente', []);

        return view('comercial.apresentacao.cliente', [
            'propostas' => $propostas,
            'draft' => $draft,
        ]);
    }

    public function clienteStore(Request $request)
    {
        $data = $request->validate([
            'proposta_id' => ['nullable', 'integer'],
            'cnpj' => ['required', 'string', 'max:30'],
            'razao_social' => ['required', 'string', 'max:255'],
            'contato' => ['required', 'string', 'max:120'],
            'telefone' => ['required', 'string', 'max:30'],
        ]);

        $user = $request->user();
        $empresaId = $user->empresa_id;
        $isMaster = $user->hasPapel('Master');
        if (!empty($data['proposta_id'])) {
            $ok = Proposta::where('id', $data['proposta_id'])
                ->where('empresa_id', $empresaId)
                ->when(!$isMaster, fn ($q) => $q->where('vendedor_id', $user->id))
                ->exists();
            abort_if(!$ok, 403);
        }

        $documento = preg_replace('/\D+/', '', (string) ($data['cnpj'] ?? ''));
        if (!in_array(strlen($documento), [11, 14], true)) {
            return back()
                ->withErrors(['cnpj' => 'Informe um CPF ou CNPJ válido.'])
                ->withInput();
        }

        $request->session()->put(self::SESSION_KEY . '.cliente', [
            'proposta_id' => $data['proposta_id'] ?? null,
            'cnpj' => $data['cnpj'],
            'razao_social' => $data['razao_social'],
            'contato' => $data['contato'],
            'telefone' => $data['telefone'],
        ]);

        return redirect()->route('comercial.apresentacao.segmento');
    }

    public function segmento(Request $request)
    {
        $cliente = $request->session()->get(self::SESSION_KEY . '.cliente');
        if (!$cliente) {
            return redirect()->route('comercial.apresentacao.cliente');
        }

        return view('comercial.apresentacao.segmento', [
            'cliente' => $cliente,
            'segmentos' => self::SEGMENTOS,
        ]);
    }

    public function show(Request $request, string $segmento)
    {
        $cliente = $request->session()->get(self::SESSION_KEY . '.cliente');
        if (!$cliente) {
            return redirect()->route('comercial.apresentacao.cliente');
        }

        abort_unless(array_key_exists($segmento, self::SEGMENTOS), 404);

        $request->session()->put(self::SESSION_KEY . '.segmento', $segmento);

        return view('comercial.apresentacao.show', $this->presentationViewData($request, $segmento));
    }

    public function pdf(Request $request, string $segmento)
    {
        $cliente = $request->session()->get(self::SESSION_KEY . '.cliente');
        if (!$cliente) {
            return redirect()->route('comercial.apresentacao.cliente');
        }

        abort_unless(array_key_exists($segmento, self::SEGMENTOS), 404);

        return view('comercial.apresentacao.pdf', $this->presentationViewData($request, $segmento));
    }

    public function clienteLogoStore(Request $request)
    {
        $data = $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $segmento = $this->segmentoPersistencia($request);
        $path = $this->storePresentationAsset($request, $segmento, $data['logo'], 'cliente-logo', true, true);
        $this->persistLayoutAssetPath($request, $segmento, 'cliente_logo_path', $path);

        return response()->json(['ok' => true]);
    }

    public function clienteLogoDestroy(Request $request)
    {
        $segmento = $this->segmentoPersistencia($request);
        $this->deleteLayoutAssetPath($request, $segmento, 'cliente_logo_path');

        return response()->json(['ok' => true]);
    }

    public function formedLogoStore(Request $request)
    {
        $data = $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $segmento = $this->segmentoPersistencia($request);
        $path = $this->storePresentationAsset($request, $segmento, $data['logo'], 'formed-logo', true);
        $this->persistLayoutAssetPath($request, $segmento, 'formed_logo_path', $path);

        return response()->json(['ok' => true]);
    }

    public function formedLogoDestroy(Request $request)
    {
        $segmento = $this->segmentoPersistencia($request);
        $this->deleteLayoutAssetPath($request, $segmento, 'formed_logo_path');

        return response()->json(['ok' => true]);
    }

    public function coverImageStore(Request $request)
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'max:4096'],
        ]);

        $segmento = $this->segmentoPersistencia($request);
        $path = $this->storePresentationAsset($request, $segmento, $data['image'], 'cover-image');
        $this->persistLayoutAssetPath($request, $segmento, 'cover_image_path', $path);

        return response()->json(['ok' => true]);
    }

    public function coverImageDestroy(Request $request)
    {
        $segmento = $this->segmentoPersistencia($request);
        $this->deleteLayoutAssetPath($request, $segmento, 'cover_image_path');

        return response()->json(['ok' => true]);
    }

    public function cancelar(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);
        return redirect()->route('comercial.dashboard');
    }

    public function modelo(Request $request, string $segmento)
    {
        abort_unless(array_key_exists($segmento, self::SEGMENTOS), 404);

        $modelo = $this->findModelo($request, $segmento);
        $conteudo = $this->conteudoParaSegmento($request, $segmento);

        $servicos = implode("\n", $conteudo['servicos'] ?? []);
        $tabelaItens = $this->tabelaPrecoItens($request);
        $precos = $this->precosParaSegmento($request, $segmento);
        $precoSelecionados = $precos->pluck('tabela_preco_item_id')->all();
        $precoQuantidades = $precos->pluck('quantidade', 'tabela_preco_item_id')->all();
        $exames = $this->examesParaSegmento($request, $segmento);
        $examesSelecionados = $exames->pluck('exame_tab_preco_id')->all();
        $examesQuantidades = $exames->pluck('quantidade', 'exame_tab_preco_id')->all();
        $treinamentos = $this->treinamentosParaSegmento($request, $segmento);
        $treinamentosSelecionados = $treinamentos->pluck('treinamento_nr_tab_preco_id')->all();
        $treinamentosQuantidades = $treinamentos->pluck('quantidade', 'treinamento_nr_tab_preco_id')->all();
        $tabelasManuais = $this->tabelasManuaisParaSegmento($request, $segmento);
        $layout = $this->layoutParaSegmento($request, $segmento);
        $heroDraft = $layout['hero'] ?? [];
        $desafiosDraft = $layout['desafios'] ?? [];
        $solucoesDraft = $layout['solucoes'] ?? [];
        $diferenciaisDraft = $layout['diferenciais'] ?? [];
        $palestrasDraft = $layout['palestras'] ?? [];
        $processoDraft = $layout['processo'] ?? [];
        $examesDraft = $this->customExamesDraft($layout, $exames);
        $treinamentosNrDraft = $this->customTreinamentosNrDraft($layout);

        return view('comercial.apresentacao.modelo', [
            'segmento' => $segmento,
            'segmentos' => self::SEGMENTOS,
            'segmentoNome' => self::SEGMENTOS[$segmento],
            'modelo' => $modelo,
            'conteudo' => $conteudo,
            'servicos' => $servicos,
            'tituloSegmento' => $this->tituloParaSegmento($request, $segmento),
            'tabelaItens' => $tabelaItens,
            'precoSelecionados' => $precoSelecionados,
            'precoQuantidades' => $precoQuantidades,
            'examesList' => $this->examesTabela($request),
            'examesSelecionados' => $examesSelecionados,
            'examesQuantidades' => $examesQuantidades,
            'usarTodosExames' => $modelo?->usar_todos_exames ?? false,
            'treinamentosList' => $this->treinamentosTabela($request),
            'treinamentosSelecionados' => $treinamentosSelecionados,
            'treinamentosQuantidades' => $treinamentosQuantidades,
            'esocialDescricao' => $modelo?->esocial_descricao,
            'esocialFaixas' => $this->esocialFaixas($request),
            'tabelasManuais' => $tabelasManuais,
            'layout' => $layout,
            'heroDraft' => $heroDraft,
            'desafiosDraft' => $desafiosDraft,
            'solucoesDraft' => $solucoesDraft,
            'diferenciaisDraft' => $diferenciaisDraft,
            'palestrasDraft' => $palestrasDraft,
            'processoDraft' => $processoDraft,
            'examesDraft' => $examesDraft,
            'treinamentosNrDraft' => $treinamentosNrDraft,
            'catalogoPrecoOptions' => [
                'padrao' => 'Tabela padrão da empresa',
                'personalizada' => 'Modelo personalizado',
            ],
        ]);
    }

    public function modeloStore(Request $request, string $segmento)
    {
        abort_unless(array_key_exists($segmento, self::SEGMENTOS), 404);

        $data = $request->validate([
            'nome_modelo' => ['nullable', 'string', 'max:150'],
            'segmento_modelo' => ['required', 'string', 'max:60'],
            'catalogo_preco' => ['nullable', 'string', 'max:40'],
            'titulo' => ['nullable', 'string', 'max:150'],
            'intro_1' => ['nullable', 'string', 'max:1000'],
            'intro_2' => ['nullable', 'string', 'max:1000'],
            'mensagem_principal' => ['nullable', 'string', 'max:2000'],
            'comissao_vendedor' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'contato_email' => ['nullable', 'string', 'max:255'],
            'contato_telefone' => ['nullable', 'string', 'max:50'],
            'beneficios' => ['nullable', 'string', 'max:1000'],
            'rodape' => ['nullable', 'string', 'max:1000'],
            'servicos' => ['nullable', 'string', 'max:4000'],
            'preco_itens' => ['nullable', 'array'],
            'preco_itens.*' => ['integer'],
            'preco_qtd' => ['nullable', 'array'],
            'preco_qtd.*' => ['nullable', 'numeric', 'min:0'],
            'exames' => ['nullable', 'array'],
            'exames.*' => ['integer'],
            'exames_qtd' => ['nullable', 'array'],
            'exames_qtd.*' => ['nullable', 'numeric', 'min:0'],
            'usar_todos_exames' => ['nullable', 'boolean'],
            'treinamentos' => ['nullable', 'array'],
            'treinamentos.*' => ['integer'],
            'treinamentos_qtd' => ['nullable', 'array'],
            'treinamentos_qtd.*' => ['nullable', 'numeric', 'min:0'],
            'esocial_descricao' => ['nullable', 'string'],
            'manual_tables' => ['nullable', 'array'],
            'manual_tables_order' => ['nullable', 'array'],
            'layout' => ['nullable', 'array'],
            'submit_action' => ['nullable', 'string', 'max:20'],
        ]);

        $segmentoDestino = $data['segmento_modelo'];
        abort_unless(array_key_exists($segmentoDestino, self::SEGMENTOS), 404);

        $empresaId = $request->user()->empresa_id;
        $modeloAtual = $this->findModelo($request, $segmentoDestino);
        $layoutInput = $data['layout'] ?? [];
        $layout = $this->normalizeLayout($segmentoDestino, $layoutInput);
        $layout = $this->mergeLayout($layout, $this->normalizeExtraLayout($layoutInput));
        $assetsPersistidos = is_array($modeloAtual?->layout ?? null) ? (($modeloAtual->layout['assets'] ?? null) ?: []) : [];
        if (is_array($assetsPersistidos) && !empty($assetsPersistidos)) {
            $layout['assets'] = $assetsPersistidos;
        }

        $linhas = collect(preg_split("/\r\n|\n|\r/", (string) ($data['servicos'] ?? '')))
            ->map(fn ($linha) => trim($linha))
            ->filter();

        $rodapeComposto = collect([
            trim((string) ($data['contato_email'] ?? '')),
            trim((string) ($data['contato_telefone'] ?? '')),
        ])->filter()->implode(' • ');

        $tabelaItens = $this->tabelaPrecoItens($request);
        $allowedIds = $tabelaItens->pluck('id')->all();
        $examesAllowedIds = $this->examesTabela($request)->pluck('id')->all();
        $treinamentosAllowedIds = $this->treinamentosTabela($request)->pluck('id')->all();

        $selecionados = collect($data['preco_itens'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $allowedIds, true))
            ->values();

        $examesSelecionados = collect($data['exames'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $examesAllowedIds, true))
            ->values();

        $treinamentosSelecionados = collect($data['treinamentos'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $treinamentosAllowedIds, true))
            ->values();

        $tablesPayload = $request->input('manual_tables', []);
        $tablesOrder = $request->input('manual_tables_order', array_keys($tablesPayload));
        $shouldSyncExamesLegado = $request->has('exames') || $request->has('usar_todos_exames');
        $shouldSyncTreinamentosLegado = $request->has('treinamentos');

        DB::transaction(function () use (
            $empresaId,
            $segmentoDestino,
            $data,
            $linhas,
            $selecionados,
            $examesSelecionados,
            $treinamentosSelecionados,
            $tablesPayload,
            $tablesOrder,
            $layout,
            $rodapeComposto,
            $shouldSyncExamesLegado,
            $shouldSyncTreinamentosLegado
        ) {
            $modelo = ModeloComercial::updateOrCreate(
                ['empresa_id' => $empresaId, 'segmento' => $segmentoDestino],
                [
                    'nome_modelo' => $data['nome_modelo'] ?? null,
                    'titulo' => $data['titulo'] ?? null,
                    'intro_1' => $data['intro_1'] ?? null,
                    'intro_2' => $data['intro_2'] ?? null,
                    'mensagem_principal' => $data['mensagem_principal'] ?? null,
                    'comissao_vendedor' => $data['comissao_vendedor'] ?? null,
                    'contato_email' => $data['contato_email'] ?? null,
                    'contato_telefone' => $data['contato_telefone'] ?? null,
                    'catalogo_preco' => $data['catalogo_preco'] ?? null,
                    'beneficios' => $data['beneficios'] ?? null,
                    'rodape' => $rodapeComposto ?: ($data['rodape'] ?? null),
                    'usar_todos_exames' => !empty($data['usar_todos_exames']),
                    'esocial_descricao' => $data['esocial_descricao'] ?? null,
                    'layout' => $layout,
                    'ativo' => true,
                ]
            );

            $modelo->itens()
                ->where('tipo', 'servico')
                ->delete();

            $linhas->values()->each(function (string $descricao, int $index) use ($modelo) {
                ModeloComercialItem::create([
                    'modelo_comercial_id' => $modelo->id,
                    'tipo' => 'servico',
                    'descricao' => $descricao,
                    'ordem' => $index + 1,
                    'ativo' => true,
                ]);
            });

            $modelo->precos()->delete();

            $selecionados->values()->each(function (int $itemId, int $index) use ($modelo, $data) {
                $quantidade = 1;
                if (!empty($data['preco_qtd'][$itemId])) {
                    $quantidade = (float) $data['preco_qtd'][$itemId];
                }

                ModeloComercialPreco::create([
                    'modelo_comercial_id' => $modelo->id,
                    'tabela_preco_item_id' => $itemId,
                    'quantidade' => $quantidade > 0 ? $quantidade : 1,
                    'ordem' => $index + 1,
                    'ativo' => true,
                ]);
            });

            if ($shouldSyncExamesLegado) {
                $modelo->exames()->delete();
                $examesSelecionados->values()->each(function (int $exameId, int $index) use ($modelo, $data) {
                    $quantidade = 1;
                    if (!empty($data['exames_qtd'][$exameId])) {
                        $quantidade = (float) $data['exames_qtd'][$exameId];
                    }

                    ModeloComercialExame::create([
                        'modelo_comercial_id' => $modelo->id,
                        'exame_tab_preco_id' => $exameId,
                        'quantidade' => $quantidade > 0 ? $quantidade : 1,
                        'ordem' => $index + 1,
                        'ativo' => true,
                    ]);
                });
            }

            if ($shouldSyncTreinamentosLegado) {
                $modelo->treinamentos()->delete();
                $treinamentosSelecionados->values()->each(function (int $treinamentoId, int $index) use ($modelo, $data) {
                    $quantidade = 1;
                    if (!empty($data['treinamentos_qtd'][$treinamentoId])) {
                        $quantidade = (float) $data['treinamentos_qtd'][$treinamentoId];
                    }

                    ModeloComercialTreinamento::create([
                        'modelo_comercial_id' => $modelo->id,
                        'treinamento_nr_tab_preco_id' => $treinamentoId,
                        'quantidade' => $quantidade > 0 ? $quantidade : 1,
                        'ordem' => $index + 1,
                        'ativo' => true,
                    ]);
                });
            }

            $modelo->tabelas()->delete();
            $orderIds = collect($tablesOrder)
                ->filter(fn ($id) => isset($tablesPayload[$id]))
                ->values();

            if ($orderIds->isEmpty()) {
                $orderIds = collect(array_keys($tablesPayload));
            }

            $orderIds->each(function ($tableKey, int $index) use ($modelo, $tablesPayload) {
                $tableData = $tablesPayload[$tableKey] ?? [];
                $titulo = trim((string) ($tableData['titulo'] ?? ''));
                $subtitulo = trim((string) ($tableData['subtitulo'] ?? ''));
                $colunas = collect($tableData['columns'] ?? [])
                    ->map(fn ($c) => trim((string) $c))
                    ->filter()
                    ->values()
                    ->all();

                if (empty($colunas)) {
                    return;
                }

                $tabela = ModeloComercialTabela::create([
                    'modelo_comercial_id' => $modelo->id,
                    'titulo' => $titulo ?: null,
                    'subtitulo' => $subtitulo ?: null,
                    'colunas' => $colunas,
                    'ordem' => $index + 1,
                    'ativo' => true,
                ]);

                $rows = $tableData['rows'] ?? [];
                $rowsOrder = $tableData['rows_order'] ?? array_keys($rows);
                $rowIndex = 0;

                foreach ($rowsOrder as $rowKey) {
                    $row = $rows[$rowKey] ?? null;
                    if ($row === null) {
                        continue;
                    }

                    $values = collect($row)
                        ->map(fn ($v) => trim((string) $v))
                        ->values()
                        ->all();

                    $values = array_slice(array_pad($values, count($colunas), ''), 0, count($colunas));
                    $hasContent = collect($values)->filter(fn ($v) => $v !== '')->isNotEmpty();
                    if (!$hasContent) {
                        continue;
                    }

                    $rowIndex++;
                    ModeloComercialTabelaLinha::create([
                        'modelo_comercial_tabela_id' => $tabela->id,
                        'valores' => $values,
                        'ordem' => $rowIndex,
                        'ativo' => true,
                    ]);
                }
            });
        });

        return redirect()
            ->route(
                !empty($data['submit_action']) && $data['submit_action'] === 'generate'
                    ? 'comercial.apresentacao.show'
                    : 'comercial.apresentacao.modelo',
                $segmentoDestino
            )
            ->with('ok', 'Modelo atualizado com sucesso.');
    }

    private function findModelo(Request $request, string $segmento): ?ModeloComercial
    {
        $empresaId = $request->user()->empresa_id;

        return ModeloComercial::query()
            ->with([
                'itens' => fn ($q) => $q->where('ativo', true),
                'precos' => fn ($q) => $q->where('ativo', true),
                'exames' => fn ($q) => $q->where('ativo', true),
                'treinamentos' => fn ($q) => $q->where('ativo', true),
                'tabelas' => fn ($q) => $q->where('ativo', true)->orderBy('ordem'),
                'tabelas.linhas' => fn ($q) => $q->where('ativo', true)->orderBy('ordem'),
            ])
            ->where('empresa_id', $empresaId)
            ->where('segmento', $segmento)
            ->where('ativo', true)
            ->first();
    }

    private function presentationViewData(Request $request, string $segmento): array
    {
        $modelo = $this->findModelo($request, $segmento);
        $layout = $this->layoutParaSegmento($request, $segmento);
        $assets = $layout['assets'] ?? [];

        $clienteLogoData = $this->storageFileToDataUrl($assets['cliente_logo_path'] ?? null);

        $logoFormedData = $this->storageFileToDataUrl($assets['formed_logo_path'] ?? null);
        $coverImageData = $this->storageFileToDataUrl($assets['cover_image_path'] ?? null);
        if ($logoFormedData === null) {
            $logoFormedPath = storage_path('app/public/logo-formed.png');
            if (!is_file($logoFormedPath)) {
                $logoFormedPath = storage_path('app/public/logo (1)-transparente.png');
            }

            $logoFormedData = is_file($logoFormedPath)
                ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFormedPath))
                : null;
        }

        return [
            'modelo' => $modelo,
            'cliente' => $request->session()->get(self::SESSION_KEY . '.cliente', []),
            'segmento' => $segmento,
            'segmentoNome' => self::SEGMENTOS[$segmento],
            'clienteLogoData' => $clienteLogoData,
            'conteudo' => $this->conteudoParaSegmento($request, $segmento),
            'tituloSegmento' => $this->tituloParaSegmento($request, $segmento),
            'precos' => $this->precosParaSegmento($request, $segmento),
            'exames' => $this->examesParaSegmento($request, $segmento),
            'treinamentos' => $this->treinamentosParaSegmento($request, $segmento),
            'esocialFaixas' => $this->esocialFaixas($request),
            'esocialDescricao' => $this->esocialDescricao($request, $segmento),
            'tabelasManuais' => $this->tabelasManuaisParaSegmento($request, $segmento),
            'layout' => $layout,
            'logoFormedData' => $logoFormedData,
            'coverImageData' => $coverImageData,
        ];
    }

    private function tituloParaSegmento(Request $request, string $segmento): string
    {
        $modelo = $this->findModelo($request, $segmento);
        return $modelo?->titulo ?: self::SEGMENTO_TITULOS[$segmento];
    }

    private function conteudoParaSegmento(Request $request, string $segmento): array
    {
        $padrao = $this->conteudoPadrao($segmento);
        $modelo = $this->findModelo($request, $segmento);

        if (!$modelo) {
            return $padrao;
        }

        $servicos = $modelo->itens
            ->where('tipo', 'servico')
            ->sortBy('ordem')
            ->pluck('descricao')
            ->values()
            ->all();

        return [
            'intro' => [
                $modelo->intro_1 ?: ($padrao['intro'][0] ?? ''),
                $modelo->intro_2 ?: ($padrao['intro'][1] ?? ''),
            ],
            'servicos' => !empty($servicos) ? $servicos : ($padrao['servicos'] ?? []),
            'beneficios' => $modelo->beneficios ?: ($padrao['beneficios'] ?? ''),
            'rodape' => $modelo->rodape ?: ($padrao['rodape'] ?? ''),
        ];
    }

    private function layoutPadrao(string $segmento): array
    {
        $defaults = [
            'construcao-civil' => [
                'hero' => [
                    'enabled' => true,
                    'badge' => 'Apresentação comercial',
                    'title' => 'CONSTRUÇÃO CIVIL',
                    'subtitle' => 'Sua obra não pode parar!',
                    'description' => null,
                ],
                'assets' => [],
                'desafios' => [
                    'enabled' => true,
                    'badge' => 'Desafio do setor',
                    'title' => 'Desafio do Setor',
                    'items' => [
                        [
                            'title' => 'Atraso na Entrega do ASO',
                            'description' => 'Mais de 5 dias para a obtenção do Atestado de Saúde Ocupacional, impactando o início das atividades e impedindo a entrada dos colaboradores na obra.',
                        ],
                        [
                            'title' => 'Demora em PGR e PCMSO',
                            'description' => 'Lentidão na emissão e entrega dos Programas de Gerenciamento de Riscos e de Controle Médico de Saúde Ocupacional.',
                        ],
                        [
                            'title' => 'Dificuldade para Agendar Exames',
                            'description' => 'Processos complicados e demorados para o agendamento de exames médicos ocupacionais.',
                        ],
                        [
                            'title' => 'Deslocamento entre Unidades',
                            'description' => 'Deslocamento do colaborador para várias unidades para realização de exames.',
                        ],
                        [
                            'title' => 'Burocracia no Atendimento',
                            'description' => 'Etapas administrativas que atrasam o atendimento e geram retrabalho.',
                        ],
                    ],
                ],
                'solucoes' => [
                    'enabled' => true,
                    'badge' => 'Nossas soluções',
                    'title' => 'Nossas soluções',
                    'description' => 'Na Formed, entendemos os obstáculos que sua empresa de construção civil enfrenta diariamente. Questões burocráticas e atrasos podem comprometer projetos e a segurança dos colaboradores.',
                    'cards' => [
                        [
                            'title' => 'ASO Trabalho em Altura - 24hs',
                            'description' => 'Na Formed, entendemos os obstáculos que sua empresa de construção civil enfrenta diariamente. Questões burocráticas e atrasos podem comprometer projetos e a segurança dos colaboradores.' . "\n" . "\n" . 'ASO para trabalho em altura entregue no dia seguinte, garantindo o início imediato das atividades.' . "\n" . 'OBS: Todos os exames são feitos em nossas unidades.',
                        ],
                        [
                            'title' => 'PGR, Inventário de Risco e PCMSO em até 2 dias',
                            'description' => 'Programas de Gerenciamento de Riscos e de Controle Médico de Saúde Ocupacional entregues em 1 dia.',
                        ],
                        [
                            'title' => 'Treinamentos das NRs',
                            'description' => 'Principais NRs atendidas:' . "\n" . 'NR-01 - Disposições Gerais' . "\n" . 'NR-06 - EPI' . "\n" . 'NR-11 - Transporte, Movimentação, Armazenagem e Manuseio de Materiais' . "\n" . 'NR-12 - Segurança no Trabalho em Máquinas e Equipamentos' . "\n" . 'NR-18 - Segurança na Indústria da Construção' . "\n" . 'NR-33 - Espaços Confinados' . "\n" . 'NR-35 - Trabalho em Altura',
                        ],
                        [
                            'title' => 'Integração com o e-Social',
                            'description' => 'Envio ao e-Social dos seguintes eventos:' . "\n" . 'S-2210 - Comunicação de Acidente de Trabalho (CAT)' . "\n" . 'S-2220 - Monitoramento da Saúde do Trabalhador (ASO)' . "\n" . 'S-2240 - Condições Ambientais do Trabalho (com base no LTCAT)',
                        ],
                        [
                            'title' => 'LTIP - Laudo Técnico de Insalubridade e Periculosidade',
                            'description' => 'Elaboração do LTIP para caracterização de insalubridade e periculosidade, conforme a legislação trabalhista, com base em avaliação técnica dos riscos e atividades desenvolvidas.',
                        ],
                        [
                            'title' => 'LTCAT - Laudo Técnico das Condições Ambientais do Trabalho',
                            'description' => 'Elaboração do LTCAT conforme legislação previdenciária, com avaliação dos agentes nocivos e caracterização das condições ambientais de trabalho, servindo de base para o eSocial (S-2240) e aposentadoria especial.',
                        ],
                        [
                            'title' => 'Sistema intuitivo',
                            'description' => 'Agendamento realizado diretamente no sistema, por meio de acesso com login e senha, garantindo segurança, autonomia e agilidade no processo.' . "\n" . 'Os documentos são disponibilizados diretamente no sistema, eliminando burocracias e a necessidade de solicitações por outras vias.',
                        ],
                        [
                            'title' => 'Atendimento personalizado',
                            'description' => 'Atendimento personalizado, com foco nas necessidades específicas de cada empresa e colaborador, garantindo agilidade, clareza e eficiência em todo o processo.' . "\n" . 'Contato humanizado e sem burocracia, com suporte dedicado sempre que precisar.',
                        ],
                        [
                            'title' => 'Relatório anual',
                            'description' => 'Emissão do relatório anual do PCMSO, conforme a NR-07, contendo a análise dos resultados dos exames clínicos e complementares, indicadores de saúde e ações de acompanhamento.',
                        ],
                        [
                            'title' => 'Atendimento Incompany',
                            'description' => 'Realização de atendimentos e exames ocupacionais diretamente nas dependências da empresa, proporcionando praticidade, redução de deslocamentos e maior produtividade para os colaboradores.',
                        ],
                    ],
                ],
                'diferenciais' => [
                    'enabled' => false,
                    'title' => 'Diferenciais FORMED',
                    'cards' => [],
                ],
                'palestras' => [
                    'enabled' => true,
                    'badge' => 'Calendário anual',
                    'title' => 'Palestras conforme o calendário anual',
                    'items' => [
                        ['title' => 'Janeiro Branco', 'description' => 'Saúde mental e emocional'],
                        ['title' => 'Fevereiro Roxo/Laranja', 'description' => 'Conscientização sobre doenças crônicas e leucemia'],
                        ['title' => 'Março Azul', 'description' => 'Prevenção do câncer colorretal'],
                        ['title' => 'Abril Verde', 'description' => 'Saúde e segurança no trabalho'],
                        ['title' => 'Maio Amarelo', 'description' => 'Prevenção de acidentes de trânsito'],
                        ['title' => 'Junho Vermelho', 'description' => 'Doação de sangue'],
                        ['title' => 'Julho Amarelo', 'description' => 'Prevenção das hepatites virais'],
                        ['title' => 'Agosto Dourado', 'description' => 'Incentivo ao aleitamento materno'],
                        ['title' => 'Setembro Amarelo', 'description' => 'Prevenção ao suicídio (abordagem educativa e preventiva)'],
                        ['title' => 'Outubro Rosa', 'description' => 'Prevenção do câncer de mama'],
                        ['title' => 'Novembro Azul', 'description' => 'Prevenção do câncer de próstata'],
                        ['title' => 'Dezembro Vermelho', 'description' => 'Prevenção ao HIV/Aids'],
                    ],
                ],
                'processo' => [
                    'enabled' => true,
                    'badge' => 'Fluxo de atendimento',
                    'title' => 'Processo Simplificado',
                    'items' => [
                        [
                            'title' => 'Contrato Assinado',
                            'description' => 'Formalização da parceria, com definição do escopo dos serviços.',
                        ],
                        [
                            'title' => 'Acesso ao Sistema',
                            'description' => 'Liberação de acesso ao sistema com login e senha, permitindo agendamentos, acompanhamento e gestão dos atendimentos.',
                        ],
                        [
                            'title' => 'Agendamento ou Solicitação',
                            'description' => 'Agendamento dos exames ocupacionais ou solicitações de documentações diretamente no sistema.',
                        ],
                        [
                            'title' => 'Notificações',
                            'description' => 'Envio automático de notificações via WhatsApp ao colaborador, contendo unidade, endereço, data e horário do atendimento, além de notificação interna para a unidade responsável.',
                        ],
                        [
                            'title' => 'Execução dos exames',
                            'description' => 'Realização dos exames ocupacionais conforme PCMSO.',
                        ],
                        [
                            'title' => 'Entrega do ASO em 24hs',
                            'description' => 'Disponibilização do ASO diretamente no sistema, garantindo agilidade, segurança e eliminação de burocracias.',
                        ],
                        [
                            'title' => 'Envio Automático para o E-social',
                            'description' => 'Garantimos o envio pontual e automático de todos os eventos obrigatórios para o e-Social, sem preocupações.',
                        ],
                        [
                            'title' => 'Fechamento mensal',
                            'description' => 'Fechamento mensal dos atendimentos, com conferência dos serviços realizados e emissão da Nota Fiscal e do boleto na data previamente acordada.',
                        ],
                    ],
                ],
                'investimento' => [
                    'enabled' => true,
                    'badge' => 'Capacitação',
                    'title' => 'Investimento',
                    'aso_title' => 'ASO - TRABALHO EM ALTURA / ESPAÇO CONFINADO',
                    'aso_price' => 'R$ 240,00',
                    'aso_items_text' => "Exame Clínico\nAcuidade visual\nAudiometria\nAvaliação Psicossocial\nEletrocardiograma\nEletroencefalograma\nEspirometria\nGlicemia de Jejum\nHemograma\nRaio X Tórax",
                    'cards' => [
                        [
                            'title' => 'E-SOCIAL - MENSALIDADE VALOR FIXO - Contrato de 12 meses',
                            'description' => 'até 100 Colaboradores',
                            'value' => 'R$ 350,00',
                            'items' => "S-2210 - CAT - Comunicação de Acidente de Trabalho enviada em até 24h após o evento\nS-2220 - ASO - Atestado de Saúde Ocupacional enviado até dia 15 do mês seguinte\nS-2240 - LTCAT - Laudo Técnico das Condições Ambientais com envio imediato",
                        ],
                        [
                            'title' => 'PGR - Programa de Gerenciamento de Risco com ART',
                            'description' => null,
                            'value' => 'R$ 700,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'PCMSO - Programa de Controle Médico de Saúde Ocupacional',
                            'description' => null,
                            'value' => 'R$ 400,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'LTCAT - Laudo Técnico das Condições de Ambiente do trabalho',
                            'description' => null,
                            'value' => 'A partir de R$ 3.000,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'LTIP - Laudo técnico de Insalubridade e Periculosidade',
                            'description' => null,
                            'value' => 'A partir de R$ 3.000,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'NR-35 + NR-18 + NR-01',
                            'description' => 'Ordem de Serviço completa',
                            'value' => 'R$ 180,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'NR-12 Máquinas',
                            'description' => 'Segurança em equipamentos',
                            'value' => 'R$ 220,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'NR-35 Trabalho em Altura',
                            'description' => 'Trabalho em altura',
                            'value' => 'R$ 120,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'NR-33 Espaço Confinado',
                            'description' => 'Espaço confinado',
                            'value' => 'R$ 200,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'NR-06 EPI',
                            'description' => 'Equipamentos de Proteção Individual',
                            'value' => 'R$ 180,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'NR-20 Combustíveis',
                            'description' => 'Segurança com inflamáveis e combustíveis',
                            'value' => 'R$ 180,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'Ordem de Serviço',
                            'description' => null,
                            'value' => 'R$ 60,00',
                            'items' => null,
                        ],
                        [
                            'title' => 'NR-11 TRANSPORTE E MOVIMENTAÇÃO DE CARGAS',
                            'description' => null,
                            'value' => 'R$ 220,00',
                            'items' => null,
                        ],
                    ],
                ],
                'unidade' => [
                    'enabled' => true,
                    'badge' => 'Atendimento',
                    'title' => 'Unidade de Fácil Acesso',
                    'name' => 'Vila Mariana',
                    'address' => "Rua Vergueiro, 1922\nMetrô Ana Rosa\nSão Paulo/SP",
                    'schedule' => 'Segunda a sexta das 7h às 14h e aos sábados sob agendamento',
                    'highlight_title' => 'Atendimento In Company',
                    'highlight_description' => 'Levamos toda a equipe até sua empresa para maior comodidade e agilidade! Fale com nosso comercial.',
                ],
                'contato_final' => [
                    'enabled' => true,
                    'badge' => 'Contato e próximos passos',
                    'title' => 'Contato e Próximos Passos',
                    'description' => 'Estamos prontos para otimizar a saúde ocupacional da sua empresa. Entre em contato hoje mesmo para agendar uma consulta e descobrir como podemos ser seu parceiro estratégico.',
                    'phone' => '(11) 99228-3886 (WhatsApp)',
                    'email' => 'gestao@formedseg.com.br',
                    'address' => "Rua Vergueiro, 1922 - Vila Mariana\nSão Paulo/SP",
                    'schedule' => "Segunda a Sexta: 7h - 14h\nSábados: 9h - 11h",
                    'site' => 'www.formedseg.com.br',
                    'cta_label' => 'Fale conosco via WhatsApp',
                ],
            ],
        ];

        return $defaults[$segmento] ?? [
            'hero' => [
                'enabled' => true,
                'title' => self::SEGMENTO_TITULOS[$segmento] ?? 'Apresentação',
                'description' => 'Apresentação personalizada da FORMED.',
            ],
            'assets' => [],
        ];
    }

    private function layoutParaSegmento(Request $request, string $segmento): array
    {
        $padrao = $this->layoutPadrao($segmento);
        $modelo = $this->findModelo($request, $segmento);
        if (!$modelo || empty($modelo->layout)) {
            return $padrao;
        }

        return $this->mergeLayout($padrao, (array) $modelo->layout);
    }

    private function normalizeLayout(string $segmento, array $input): array
    {
        $padrao = $this->layoutPadrao($segmento);
        foreach ($input as $section => $value) {
            if (!isset($padrao[$section]) || !is_array($value)) {
                continue;
            }

            $padrao[$section] = $this->sanitizeLayoutSection($section, $value, $padrao[$section]);
        }

        return $padrao;
    }

    private function segmentoPersistencia(Request $request): string
    {
        $segmento = (string) $request->session()->get(self::SESSION_KEY . '.segmento', '');
        abort_unless(array_key_exists($segmento, self::SEGMENTOS), 422);

        return $segmento;
    }

    private function presentationAssetDirectory(Request $request, string $segmento): string
    {
        return 'apresentacao/' . $request->user()->empresa_id . '/' . $segmento;
    }

    private function storePresentationAsset(
        Request $request,
        string $segmento,
        $file,
        string $basename,
        bool $forcePng = false,
        bool $removeWhiteBackground = false
    ): string
    {
        $disk = Storage::disk('public');
        $directory = $this->presentationAssetDirectory($request, $segmento);
        $extension = $forcePng
            ? 'png'
            : strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        $filename = $basename . '.' . $extension;
        $path = $directory . '/' . $filename;

        foreach ($disk->files($directory) as $existingPath) {
            if (Str::startsWith(basename($existingPath), $basename . '.')) {
                $disk->delete($existingPath);
            }
        }

        if ($forcePng) {
            $disk->put($path, $this->convertImageToPng($file, $removeWhiteBackground));
        } else {
            $disk->putFileAs($directory, $file, $filename);
        }

        return $path;
    }

    private function convertImageToPng($file, bool $removeWhiteBackground = false): string
    {
        $contents = file_get_contents($file->getRealPath());
        abort_if($contents === false, 422, 'Falha ao ler a imagem enviada.');

        $image = imagecreatefromstring($contents);
        abort_if($image === false, 422, 'Falha ao processar a imagem enviada.');

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        if ($removeWhiteBackground) {
            $this->makeNearWhitePixelsTransparent($image);
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        abort_if($png === false, 422, 'Falha ao converter a imagem para PNG.');

        return $png;
    }

    private function makeNearWhitePixelsTransparent(\GdImage $image): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;

                if ($alpha === 127) {
                    continue;
                }

                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;

                if ($red >= 245 && $green >= 245 && $blue >= 245) {
                    $transparent = imagecolorallocatealpha($image, $red, $green, $blue, 127);
                    imagesetpixel($image, $x, $y, $transparent);
                }
            }
        }
    }

    private function persistLayoutAssetPath(Request $request, string $segmento, string $key, string $path): void
    {
        $modelo = ModeloComercial::firstOrCreate(
            ['empresa_id' => $request->user()->empresa_id, 'segmento' => $segmento],
            ['ativo' => true]
        );

        $layout = is_array($modelo->layout) ? $modelo->layout : [];
        $assets = is_array($layout['assets'] ?? null) ? $layout['assets'] : [];
        $assets[$key] = $path;
        $layout['assets'] = $assets;

        $modelo->layout = $layout;
        $modelo->ativo = true;
        $modelo->save();
    }

    private function deleteLayoutAssetPath(Request $request, string $segmento, string $key): void
    {
        $modelo = $this->findModelo($request, $segmento);
        if (!$modelo) {
            return;
        }

        $layout = is_array($modelo->layout) ? $modelo->layout : [];
        $assets = is_array($layout['assets'] ?? null) ? $layout['assets'] : [];
        $path = $assets[$key] ?? null;

        if (is_string($path) && $path !== '') {
            Storage::disk('public')->delete($path);
        }

        unset($assets[$key]);
        $layout['assets'] = $assets;
        $modelo->layout = $layout;
        $modelo->save();
    }

    private function storageFileToDataUrl(?string $path): ?string
    {
        if (!is_string($path) || $path === '' || !Storage::disk('public')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('public')->get($path);
        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function sanitizeLayoutSection(string $section, array $input, array $default): array
    {
        $result = $default;
        if (array_key_exists('enabled', $input)) {
            $enabled = filter_var($input['enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $result['enabled'] = $enabled ?? ($default['enabled'] ?? true);
        }

        if (isset($input['title'])) {
            $result['title'] = $this->sanitizeText($input['title'], 200);
        }

        if (isset($input['description'])) {
            $result['description'] = $this->sanitizeText($input['description'], 2000);
        }

        if (isset($input['subtitle'])) {
            $result['subtitle'] = $this->sanitizeText($input['subtitle'], 255);
        }

        if (isset($input['badge'])) {
            $result['badge'] = $this->sanitizeText($input['badge'], 120);
        }

        foreach (['name', 'address', 'schedule', 'highlight_title', 'highlight_description', 'aso_title', 'aso_price', 'aso_items_text', 'phone', 'email', 'site', 'cta_label', 'value', 'items'] as $field) {
            if (isset($input[$field])) {
                $result[$field] = $this->sanitizeText($input[$field], 4000);
            }
        }

        if (isset($input['items']) && is_array($input['items'])) {
            $result['items'] = $this->sanitizeLayoutList($input['items']);
        }

        if (isset($input['cards']) && is_array($input['cards'])) {
            $result['cards'] = $this->sanitizeLayoutList($input['cards']);
        }

        return $result;
    }

    private function sanitizeLayoutList(array $list): array
    {
        $items = [];
        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $title = $this->sanitizeText($entry['title'] ?? null, 180);
            $description = $this->sanitizeText($entry['description'] ?? null, 2000);
            if ($title === null && $description === null) {
                continue;
            }

            $items[] = [
                'title' => $title,
                'description' => $description,
                'value' => $this->sanitizeText($entry['value'] ?? null, 200),
                'items' => $this->sanitizeText($entry['items'] ?? null, 4000),
                'active' => filter_var($entry['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];

            if (count($items) >= 20) {
                break;
            }
        }

        return $items;
    }

    private function sanitizeText($value, int $limit = 1000): ?string
    {
        if (is_array($value)) {
            $value = collect($value)
                ->flatten()
                ->filter(fn ($item) => !is_array($item) && $item !== null)
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->implode("\n");
        }

        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $limit);
    }

    private function mergeLayout(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = $this->mergeLayout($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    private function normalizeExtraLayout(array $input): array
    {
        $layout = [];

        if (isset($input['exames_ocupacionais']) && is_array($input['exames_ocupacionais'])) {
            $layout['exames_ocupacionais'] = [
                'enabled' => filter_var($input['exames_ocupacionais']['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'items' => collect($input['exames_ocupacionais']['items'] ?? [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(function (array $item) {
                        return [
                            'title' => $this->sanitizeText($item['title'] ?? null, 180),
                            'value' => $this->sanitizeText($item['value'] ?? null, 80),
                            'active' => filter_var($item['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        ];
                    })
                    ->filter(fn ($item) => $item['title'])
                    ->values()
                    ->all(),
            ];
        }

        if (isset($input['treinamentos_nr']) && is_array($input['treinamentos_nr'])) {
            $layout['treinamentos_nr'] = [
                'enabled' => filter_var($input['treinamentos_nr']['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'items' => collect($input['treinamentos_nr']['items'] ?? [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(function (array $item) {
                        return [
                            'title' => $this->sanitizeText($item['title'] ?? null, 80),
                            'quantity' => max(0, (int) ($item['quantity'] ?? 0)),
                            'active' => filter_var($item['active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ];
                    })
                    ->filter(fn ($item) => $item['title'])
                    ->values()
                    ->all(),
            ];
        }

        return $layout;
    }

    private function customExamesDraft(array $layout, $selectedExames)
    {
        $draft = collect($layout['exames_ocupacionais']['items'] ?? []);
        if ($draft->isNotEmpty()) {
            return $draft->map(fn ($item) => [
                'title' => $item['title'] ?? '',
                'value' => $item['value'] ?? '',
                'active' => !empty($item['active']),
            ])->values()->all();
        }

        return collect($selectedExames)
            ->map(function ($row) {
                $exame = $row->exame ?? $row;

                return [
                    'title' => $exame->titulo ?? '',
                    'value' => isset($exame->preco) ? number_format((float) $exame->preco, 2, ',', '.') : '',
                    'active' => true,
                ];
            })
            ->filter(fn ($item) => $item['title'] !== '')
            ->values()
            ->all();
    }

    private function customTreinamentosNrDraft(array $layout): array
    {
        $draft = collect($layout['treinamentos_nr']['items'] ?? [])
            ->keyBy(fn ($item) => $item['title'] ?? '');

        return collect(self::TREINAMENTOS_NR_PADRAO)
            ->map(function (string $nr) use ($draft) {
                $item = $draft->get($nr, []);

                return [
                    'title' => $nr,
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    'active' => !empty($item['active']),
                ];
            })
            ->values()
            ->all();
    }

    private function precosParaSegmento(Request $request, string $segmento)
    {
        $modelo = $this->findModelo($request, $segmento);

        if (!$modelo) {
            return collect();
        }

        return $modelo->precos()
            ->with(['tabelaPrecoItem.servico'])
            ->where('ativo', true)
            ->orderBy('ordem')
            ->get();
    }

    private function examesParaSegmento(Request $request, string $segmento)
    {
        $modelo = $this->findModelo($request, $segmento);

        if (!$modelo) {
            return collect();
        }

        if ($modelo->usar_todos_exames) {
            return ExamesTabPreco::query()
                ->where('empresa_id', $request->user()->empresa_id)
                ->where('ativo', true)
                ->orderBy('titulo')
                ->get()
                ->map(function ($exame) {
                    return (object) [
                        'exame_tab_preco_id' => $exame->id,
                        'quantidade' => 1,
                        'exame' => $exame,
                    ];
                });
        }

        return $modelo->exames()
            ->with('exame')
            ->where('ativo', true)
            ->orderBy('ordem')
            ->get();
    }

    private function treinamentosParaSegmento(Request $request, string $segmento)
    {
        $modelo = $this->findModelo($request, $segmento);

        if (!$modelo) {
            return collect();
        }

        $rows = $modelo->treinamentos()
            ->with('treinamento')
            ->where('ativo', true)
            ->orderBy('ordem')
            ->get();

        $empresaId = $request->user()->empresa_id;
        $precos = $this->treinamentoPrecosPorCodigo($empresaId);

        $rows->each(function ($row) use ($precos) {
            $codigo = $row->treinamento?->codigo;
            $row->tabelaItem = $codigo ? ($precos[$codigo] ?? null) : null;
        });

        return $rows;
    }

    private function tabelasManuaisParaSegmento(Request $request, string $segmento)
    {
        $modelo = $this->findModelo($request, $segmento);

        if (!$modelo) {
            return collect();
        }

        return $modelo->tabelas
            ->where('ativo', true)
            ->sortBy('ordem')
            ->values();
    }

    private function esocialDescricao(Request $request, string $segmento): ?string
    {
        $modelo = $this->findModelo($request, $segmento);
        return $modelo?->esocial_descricao;
    }

    private function esocialFaixas(Request $request)
    {
        $empresaId = $request->user()->empresa_id;
        $padrao = TabelaPrecoPadrao::firstOrCreate(
            ['empresa_id' => $empresaId, 'ativa' => true],
            ['nome' => 'Tabela Padrão', 'ativa' => true]
        );

        return EsocialTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where(function ($q) use ($padrao) {
                $q->where('tabela_preco_padrao_id', $padrao->id)
                    ->orWhereNull('tabela_preco_padrao_id');
            })
            ->orderByRaw('CASE WHEN tabela_preco_padrao_id = ? THEN 0 ELSE 1 END', [$padrao->id])
            ->orderBy('inicio')
            ->get();
    }

    private function examesTabela(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        return ExamesTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('titulo')
            ->get();
    }

    private function treinamentosTabela(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $precos = $this->treinamentoPrecosPorCodigo($empresaId);
        if (empty($precos)) {
            return collect();
        }

        $rows = TreinamentoNrsTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->whereNotNull('codigo')
            ->whereIn('codigo', array_keys($precos))
            ->orderBy('ordem')
            ->orderBy('titulo')
            ->get();

        $rows->each(function ($row) use ($precos) {
            $row->tabelaItem = $precos[$row->codigo] ?? null;
        });

        return $rows;
    }

    private function treinamentoServicoId(int $empresaId): ?int
    {
        $id = (int) (config('services.treinamento_id') ?? 0);
        if ($id > 0) {
            return $id;
        }

        return Servico::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where(function ($q) {
                $q->whereRaw('LOWER(tipo) = ?', ['treinamento'])
                    ->orWhereRaw('LOWER(nome) like ?', ['%treinamento%']);
            })
            ->orderBy('id')
            ->value('id');
    }

    private function treinamentoPrecosPorCodigo(int $empresaId): array
    {
        $servicoId = $this->treinamentoServicoId($empresaId);
        if (!$servicoId) {
            return [];
        }

        $padrao = TabelaPrecoPadrao::firstOrCreate(
            ['empresa_id' => $empresaId, 'ativa' => true],
            ['nome' => 'Tabela Padrão', 'ativa' => true]
        );

        return $padrao->itens()
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->whereNotNull('codigo')
            ->get()
            ->keyBy('codigo')
            ->all();
    }

    private function tabelaPrecoItens(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $padrao = TabelaPrecoPadrao::firstOrCreate(
            ['empresa_id' => $empresaId, 'ativa' => true],
            ['nome' => 'Tabela Padrão', 'ativa' => true]
        );

        $treinamentoId = $this->treinamentoServicoId($empresaId);
        $exameId = (int) (config('services.exame_id') ?? 0);

        return $padrao->itens()
            ->with('servico')
            ->where('ativo', true)
            ->when($treinamentoId, fn ($q) => $q->where('servico_id', '!=', $treinamentoId))
            ->when($exameId, fn ($q) => $q->where('servico_id', '!=', $exameId))
            ->orderBy('descricao')
            ->get();
    }

    private function conteudoPadrao(string $segmento): array
    {
        return [
            'construcao-civil' => [
                'intro' => [
                    'A FORMED apoia empresas da construção civil com uma apresentação clara e objetiva dos serviços de SST, alinhada às exigências legais e à rotina do canteiro.',
                    'Nosso foco é reduzir riscos, organizar documentação e manter a operação em conformidade de forma simples e contínua.',
                ],
                'servicos' => [
                    'PGR e inventário de riscos',
                    'PCMSO e gestão de exames ocupacionais',
                    'ASO (admissional, periódico e demissional)',
                    'Treinamentos obrigatórios (NRs)',
                    'Gestão de documentos e laudos (LTCAT, APR, PAE)',
                ],
                'beneficios' => 'Organização e previsibilidade no atendimento, redução de riscos e suporte contínuo para manter a obra em conformidade.',
                'rodape' => 'comercial@formed.com.br • (00) 0000-0000',
            ],
            'industria' => [
                'intro' => [
                    'Para operações industriais, a FORMED entrega uma solução robusta de SST com foco em controle de riscos, gestão documental e suporte contínuo.',
                    'Prontidão para auditorias, conformidade e rotina organizada para a equipe de segurança e RH.',
                ],
                'servicos' => [
                    'PGR (inventário e plano de ação)',
                    'PCMSO e gestão de ASOs',
                    'Gestão de exames complementares',
                    'Laudos técnicos e documentação (LTCAT)',
                    'Treinamentos e capacitações (NRs)',
                ],
                'beneficios' => 'Menos retrabalho, mais controle, prontidão para auditorias e melhoria contínua de segurança e produtividade.',
                'rodape' => 'comercial@formed.com.br • (00) 0000-0000',
            ],
            'comercio' => [
                'intro' => [
                    'A FORMED simplifica a gestão de SST para empresas de comércio e varejo com atendimento objetivo e documentação organizada.',
                    'Apoio recorrente para manter a empresa regular sem travar a operação.',
                ],
                'servicos' => [
                    'PGR e documentação obrigatória',
                    'PCMSO e ASO',
                    'Gestão de exames ocupacionais',
                    'Treinamentos aplicáveis (NRs)',
                    'Suporte para rotinas e fiscalizações',
                ],
                'beneficios' => 'Regularidade com agilidade, redução de riscos e tranquilidade para o gestor focar na operação.',
                'rodape' => 'comercial@formed.com.br • (00) 0000-0000',
            ],
            'restaurante' => [
                'intro' => [
                    'A FORMED oferece suporte completo em SST para o segmento de alimentação, com foco em conformidade e prevenção de riscos.',
                    'Documentação em dia e apoio contínuo para manter a operação segura e regular.',
                ],
                'servicos' => [
                    'PGR e documentação de SST',
                    'PCMSO e ASO',
                    'Gestão de exames ocupacionais',
                    'Treinamentos e orientações aplicáveis',
                    'Suporte contínuo para adequações',
                ],
                'beneficios' => 'Atendimento prático, documentação em dia e apoio para manter a operação segura e regular.',
                'rodape' => 'comercial@formed.com.br • (00) 0000-0000',
            ],
        ][$segmento] ?? [];
    }
}
