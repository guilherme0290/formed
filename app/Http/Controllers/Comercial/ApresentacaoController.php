<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ModeloComercial;
use App\Models\ModeloComercialExame;
use App\Models\ModeloComercialItem;
use App\Models\ModeloComercialPreco;
use App\Models\ModeloComercialTreinamento;
use App\Models\ExamesTabPreco;
use App\Models\EsocialTabPreco;
use App\Models\Proposta;
use App\Models\Servico;
use App\Models\TabelaPrecoPadrao;
use App\Models\TreinamentoNrsTabPreco;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

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

    public function cliente(Request $request)
    {
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

        return view('comercial.apresentacao.show', [
            'cliente' => $cliente,
            'segmento' => $segmento,
            'segmentoNome' => self::SEGMENTOS[$segmento],
            'conteudo' => $this->conteudoParaSegmento($request, $segmento),
            'tituloSegmento' => $this->tituloParaSegmento($request, $segmento),
            'precos' => $this->precosParaSegmento($request, $segmento),
            'exames' => $this->examesParaSegmento($request, $segmento),
            'treinamentos' => $this->treinamentosParaSegmento($request, $segmento),
            'esocialFaixas' => $this->esocialFaixas($request),
            'esocialDescricao' => $this->esocialDescricao($request, $segmento),
        ]);
    }

    public function pdf(Request $request, string $segmento)
    {
        $cliente = $request->session()->get(self::SESSION_KEY . '.cliente');
        if (!$cliente) {
            return redirect()->route('comercial.apresentacao.cliente');
        }

        abort_unless(array_key_exists($segmento, self::SEGMENTOS), 404);

        $logoPath = public_path('storage/logo.png');
        $logoData = is_file($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('comercial.apresentacao.pdf', [
            'cliente' => $cliente,
            'segmento' => $segmento,
            'segmentoNome' => self::SEGMENTOS[$segmento],
            'logoData' => $logoData,
            'conteudo' => $this->conteudoParaSegmento($request, $segmento),
            'tituloSegmento' => $this->tituloParaSegmento($request, $segmento),
            'precos' => $this->precosParaSegmento($request, $segmento),
            'exames' => $this->examesParaSegmento($request, $segmento),
            'treinamentos' => $this->treinamentosParaSegmento($request, $segmento),
            'esocialFaixas' => $this->esocialFaixas($request),
            'esocialDescricao' => $this->esocialDescricao($request, $segmento),
        ])->setPaper('a4');

        return $pdf->stream('apresentacao-' . $segmento . '.pdf');
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

        return view('comercial.apresentacao.modelo', [
            'segmento' => $segmento,
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
        ]);
    }

    public function modeloStore(Request $request, string $segmento)
    {
        abort_unless(array_key_exists($segmento, self::SEGMENTOS), 404);

        $data = $request->validate([
            'titulo' => ['nullable', 'string', 'max:150'],
            'intro_1' => ['nullable', 'string', 'max:1000'],
            'intro_2' => ['nullable', 'string', 'max:1000'],
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
        ]);

        $empresaId = $request->user()->empresa_id;

        $linhas = collect(preg_split("/\r\n|\n|\r/", (string) ($data['servicos'] ?? '')))
            ->map(fn ($linha) => trim($linha))
            ->filter();

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

        DB::transaction(function () use ($empresaId, $segmento, $data, $linhas, $selecionados, $examesSelecionados, $treinamentosSelecionados) {
            $modelo = ModeloComercial::updateOrCreate(
                ['empresa_id' => $empresaId, 'segmento' => $segmento],
                [
                    'titulo' => $data['titulo'] ?? null,
                    'intro_1' => $data['intro_1'] ?? null,
                    'intro_2' => $data['intro_2'] ?? null,
                    'beneficios' => $data['beneficios'] ?? null,
                    'rodape' => $data['rodape'] ?? null,
                    'usar_todos_exames' => !empty($data['usar_todos_exames']),
                    'esocial_descricao' => $data['esocial_descricao'] ?? null,
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
        });

        return redirect()
            ->route('comercial.apresentacao.modelo', $segmento)
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
            ])
            ->where('empresa_id', $empresaId)
            ->where('segmento', $segmento)
            ->where('ativo', true)
            ->first();
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

        return TreinamentoNrsTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->orderBy('titulo')
            ->get();
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
