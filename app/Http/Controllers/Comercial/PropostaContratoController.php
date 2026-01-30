<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ContratoClausula;
use App\Models\ExamesTabPreco;
use App\Models\Proposta;
use App\Models\PropostaContrato;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Models\UnidadeClinica;
use App\Services\OpenAiContratoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PropostaContratoController extends Controller
{
    public function edit(Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        $contrato = PropostaContrato::query()
            ->where('proposta_id', $proposta->id)
            ->first();

        if (!$contrato) {
            $resultado = $this->gerarContrato($proposta, null, true);
            $contrato = $resultado['contrato'];
            if (!empty($resultado['erro_ai'])) {
                session()->flash('error', $resultado['erro_ai']);
                if (!empty($resultado['erro_ai_detalhes'])) {
                    session()->flash('error_details', $resultado['erro_ai_detalhes']);
                }
            }
        }

        return view('comercial.propostas.contrato-edit', [
            'proposta' => $proposta,
            'contrato' => $contrato,
        ]);
    }

    public function generate(Proposta $proposta): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        $resultado = $this->gerarContrato($proposta, null, true);
        $contrato = $resultado['contrato'];

        $redirect = redirect()
            ->route('comercial.propostas.contrato.edit', $proposta)
            ->with('ok', $contrato->wasRecentlyCreated ? 'Contrato gerado com sucesso.' : 'Contrato atualizado com sucesso.');

        if (!empty($resultado['erro_ai'])) {
            $redirect->with('error', $resultado['erro_ai']);
            if (!empty($resultado['erro_ai_detalhes'])) {
                $redirect->with('error_details', $resultado['erro_ai_detalhes']);
            }
        }

        return $redirect;
    }

    public function update(Request $request, Proposta $proposta): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        $data = $request->validate([
            'html' => ['required', 'string'],
        ]);

        $contrato = PropostaContrato::query()
            ->where('proposta_id', $proposta->id)
            ->firstOrFail();

        $contrato->update([
            'html' => $data['html'],
            'status' => 'EDITADO',
            'atualizado_por' => $user->id,
        ]);

        return back()->with('ok', 'Contrato atualizado com sucesso.');
    }

    public function regenerate(Request $request, Proposta $proposta): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        $data = $request->validate([
            'prompt_custom' => ['nullable', 'string', 'max:4000'],
        ]);

        $resultado = $this->gerarContrato($proposta, $data['prompt_custom'] ?? null, true);

        $redirect = redirect()
            ->route('comercial.propostas.contrato.edit', $proposta)
            ->with('ok', 'Contrato regerado com sucesso.');

        if (!empty($resultado['erro_ai'])) {
            $redirect->with('error', $resultado['erro_ai']);
            if (!empty($resultado['erro_ai_detalhes'])) {
                $redirect->with('error_details', $resultado['erro_ai_detalhes']);
            }
        }

        return $redirect;
    }

    public function generateTemplate(Proposta $proposta): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        $resultado = $this->gerarContrato($proposta, null, false);
        $contrato = $resultado['contrato'];

        return redirect()
            ->route('comercial.propostas.contrato.edit', $proposta)
            ->with('ok', $contrato->wasRecentlyCreated ? 'Contrato gerado com sucesso.' : 'Contrato atualizado com sucesso.');
    }

    private function gerarContrato(Proposta $proposta, ?string $promptCustom = null, bool $useAi = true): array
    {
        $user = auth()->user();
        $proposta->loadMissing(['cliente', 'empresa', 'itens.servico']);

        $empresa = $proposta->empresa;
        $cliente = $proposta->cliente;
        $empresaId = $proposta->empresa_id;

        $itens = $proposta->itens->map(function ($item) {
            return [
                'nome' => $item->nome ?? $item->descricao ?? 'Serviço',
                'descricao' => $item->descricao ?? null,
                'quantidade' => (int) ($item->quantidade ?? 1),
                'valor_unitario' => (float) ($item->valor_unitario ?? 0),
                'valor_total' => (float) ($item->valor_total ?? 0),
                'tipo' => strtoupper((string) ($item->tipo ?? '')),
                'servico' => strtoupper((string) ($item->servico?->nome ?? '')),
            ];
        })->values();

        $servicos = $this->resolverServicosContrato($itens->all());

        $clausulas = ContratoClausula::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->get()
            ->filter(function (ContratoClausula $clausula) use ($servicos) {
                $tipo = strtoupper((string) $clausula->servico_tipo);
                if ($this->isClausulaPreco($clausula->slug)) {
                    return false;
                }
                return $tipo === 'GERAL' || in_array($tipo, $servicos, true);
            })
            ->values();

        $clausulasSnapshot = $clausulas->map(fn (ContratoClausula $c) => [
            'slug' => $c->slug,
            'titulo' => $c->titulo,
            'servico_tipo' => $c->servico_tipo,
            'ordem' => $c->ordem,
            'versao' => $c->versao,
        ])->all();

        $unidades = UnidadeClinica::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        $payload = $this->buildContratoPayload($proposta, $empresa, $cliente, $itens->all(), $unidades->all(), $clausulas->all());
        $html = $this->buildContratoHtmlTemplate($payload, $clausulas->all());

        $erroAi = null;
        $erroAiDetalhes = null;
        if ($useAi) {
            $openAi = app(OpenAiContratoService::class);
            $aiResponse = $openAi->gerarHtml($payload, $promptCustom);
            if (!empty($aiResponse['error'])) {
                $erroAi = $aiResponse['error'];
                $erroAiDetalhes = $aiResponse['error_details'] ?? null;
            }
            if (!empty($aiResponse['html'])) {
                $html = $aiResponse['html'];
            }
        }

        $contrato = PropostaContrato::query()
            ->firstOrNew(['proposta_id' => $proposta->id], [
                'empresa_id' => $proposta->empresa_id,
                'cliente_id' => $proposta->cliente_id,
            ]);

        $contrato->fill([
            'status' => 'GERADO',
            'html' => $html,
            'html_original' => $contrato->html_original ?: $html,
            'clausulas_snapshot' => $clausulasSnapshot,
            'prompt_custom' => $promptCustom,
            'gerado_por' => $contrato->gerado_por ?: $user->id,
            'atualizado_por' => $user->id,
        ]);

        $contrato->save();

        return [
            'contrato' => $contrato,
            'erro_ai' => $erroAi,
            'erro_ai_detalhes' => $erroAiDetalhes,
        ];
    }

    private function buildContratoPayload(Proposta $proposta, $empresa, $cliente, array $itens, array $unidades, array $clausulas): array
    {
        $dataHoje = now()->format('d/m/Y');
        $empresaId = $proposta->empresa_id;

        $contratante = [
            'razao' => $cliente?->razao_social ?? '—',
            'cnpj' => $cliente?->cnpj ?? '—',
            'endereco' => $cliente?->endereco ?? ($cliente?->logradouro ?? '—'),
        ];

        $contratada = [
            'razao' => $empresa?->razao_social ?? $empresa?->nome ?? '—',
            'cnpj' => $empresa?->cnpj ?? '—',
            'endereco' => $empresa?->endereco ?? '—',
        ];

        $clausulasPayload = array_map(function ($clausula) {
            return [
                'slug' => $clausula->slug,
                'titulo' => $clausula->titulo,
                'ordem' => $clausula->ordem,
                'servico_tipo' => $clausula->servico_tipo,
                'html_template' => $clausula->html_template,
            ];
        }, $clausulas);

        $tabelaPadrao = TabelaPrecoPadrao::query()
            ->where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->first();

        if (!$tabelaPadrao) {
            $tabelaPadrao = TabelaPrecoPadrao::query()
                ->where('empresa_id', $empresaId)
                ->first();
        }

        $treinamentoServicoId = (int) (config('services.treinamento_id') ?? 0);
        $treinamentosAvulsos = [];
        if ($tabelaPadrao && $treinamentoServicoId > 0) {
            $treinamentosAvulsos = TabelaPrecoItem::query()
                ->where('tabela_preco_padrao_id', $tabelaPadrao->id)
                ->where('servico_id', $treinamentoServicoId)
                ->where('ativo', true)
                ->orderBy('codigo')
                ->get()
                ->map(fn ($row) => [
                    'codigo' => $row->codigo,
                    'descricao' => $row->descricao,
                    'preco' => (float) ($row->preco ?? 0),
                ])
                ->all();
        }

        $examesAvulsos = ExamesTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('titulo')
            ->get()
            ->map(fn ($row) => [
                'titulo' => $row->titulo,
                'descricao' => $row->descricao,
                'preco' => (float) ($row->preco ?? 0),
            ])
            ->all();

        $totalItens = array_reduce($itens, function ($carry, $item) {
            return $carry + (float) ($item['valor_total'] ?? 0);
        }, 0.0);

        return [
            'meta' => [
                'titulo' => 'Contrato de Prestação de Serviços',
                'data_hoje' => $dataHoje,
                'proposta_id' => $proposta->id,
            ],
            'contratada' => $contratada,
            'contratante' => $contratante,
            'itens' => $itens,
            'unidades' => array_map(function ($u) {
                return [
                    'nome' => $u->nome ?? 'Unidade',
                    'endereco' => $u->endereco ?? '',
                ];
            }, $unidades),
            'tabelas_avulsas' => [
                'exames' => $examesAvulsos,
                'treinamentos' => $treinamentosAvulsos,
            ],
            'totais' => [
                'total_itens' => $totalItens,
                'total_geral' => $totalItens,
            ],
            'clausulas' => $clausulasPayload,
        ];
    }

    private function buildContratoHtmlTemplate(array $payload, array $clausulas): string
    {
        $meta = $payload['meta'] ?? [];
        $contratada = $payload['contratada'] ?? [];
        $contratante = $payload['contratante'] ?? [];
        $itens = $payload['itens'] ?? [];
        $unidades = $payload['unidades'] ?? [];
        $tabelasAvulsas = $payload['tabelas_avulsas'] ?? [];
        $totais = $payload['totais'] ?? [];

        $titulo = $meta['titulo'] ?? 'Contrato de Prestação de Serviços';
        $dataHoje = $meta['data_hoje'] ?? now()->format('d/m/Y');
        $totalItens = (float) ($totais['total_itens'] ?? 0);
        $totalGeral = (float) ($totais['total_geral'] ?? $totalItens);
        $totalItensFmt = $this->formatMoney($totalItens);
        $totalGeralFmt = $this->formatMoney($totalGeral);

        $clausulasHtml = '';
        if (!empty($clausulas)) {
            foreach ($clausulas as $clausula) {
                $conteudo = $this->aplicarPlaceholders($clausula->html_template, $contratada, $contratante, $dataHoje);
                $clausulasHtml .= '<section class="clause" data-clause="' . e($clausula->slug) . '">' . $conteudo . '</section>';
            }
        } else {
            $clausulasHtml = '<section class="clause"><h3>Cláusulas</h3><p>Catálogo de cláusulas não configurado para esta empresa.</p></section>';
        }

        $itensRows = '';
        foreach ($itens as $item) {
            $itensRows .= '<tr>'
                . '<td>' . e($item['nome']) . '</td>'
                . '<td>' . e((string) $item['quantidade']) . '</td>'
                . '<td>R$ ' . number_format((float) $item['valor_unitario'], 2, ',', '.') . '</td>'
                . '<td>R$ ' . number_format((float) $item['valor_total'], 2, ',', '.') . '</td>'
                . '</tr>';
        }

        $unidadesHtml = '';
        foreach ($unidades as $unidade) {
            $unidadesHtml .= '<li><strong>' . e($unidade['nome'] ?? 'Unidade') . '</strong><br>'
                . e($unidade['endereco'] ?? '')
                . '</li>';
        }

        $examesAvulsos = $tabelasAvulsas['exames'] ?? [];
        $treinamentosAvulsos = $tabelasAvulsas['treinamentos'] ?? [];
        $examesRows = '';
        foreach ($examesAvulsos as $exame) {
            $examesRows .= '<tr>'
                . '<td>' . e($exame['titulo'] ?? '') . '</td>'
                . '<td class="right">R$ ' . number_format((float) ($exame['preco'] ?? 0), 2, ',', '.') . '</td>'
                . '</tr>';
        }
        $treinamentosRows = '';
        foreach ($treinamentosAvulsos as $treinamento) {
            $label = trim((string) ($treinamento['descricao'] ?? ''));
            if ($label === '') {
                $label = (string) ($treinamento['codigo'] ?? 'Treinamento');
            }
            $treinamentosRows .= '<tr>'
                . '<td>' . e($label) . '</td>'
                . '<td class="right">R$ ' . number_format((float) ($treinamento['preco'] ?? 0), 2, ',', '.') . '</td>'
                . '</tr>';
        }

        return <<<HTML
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<title>{$titulo}</title>
<style>
    :root{--ink:#111827;--muted:#6b7280;--line:#e5e7eb;--soft:#f9fafb;}
    *{box-sizing:border-box;}
    body{margin:0;font-family:Arial, Helvetica, sans-serif;color:var(--ink);background:#fff;}
    .page{width:210mm;min-height:297mm;margin:0 auto;padding:18mm 16mm;}
    .topbar{display:flex;justify-content:space-between;gap:16px;border-bottom:2px solid var(--ink);padding-bottom:10px;margin-bottom:14px;}
    .brand{font-weight:700;letter-spacing:.2px;font-size:14px;}
    .brand small{display:block;font-weight:400;color:var(--muted);margin-top:3px;font-size:11px;letter-spacing:0;}
    .meta{text-align:right;font-size:11px;color:var(--muted);line-height:1.35;}
    h1{margin:10px 0 14px;text-align:center;font-size:16px;letter-spacing:.2px;}
    h2{margin:16px 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:.4px;}
    p{margin:8px 0;font-size:12px;line-height:1.45;text-align:justify;}
    .grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;margin-top:8px;}
    .box{border:1px solid var(--line);border-radius:10px;padding:10px;background:#fff;}
    .kv{display:grid;grid-template-columns:140px 1fr;gap:6px 10px;font-size:12px;line-height:1.35;}
    .kv b{color:var(--ink);}
    .kv span{color:var(--muted);}
    .divider{height:1px;background:var(--line);margin:14px 0;}
    table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;}
    th,td{border:1px solid var(--line);padding:8px 8px;vertical-align:top;}
    th{background:var(--soft);text-align:left;font-weight:700;}
    .right{text-align:right;}
    .small{font-size:11px;color:var(--muted);}
    .sig-area{margin-top:24px;display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:end;}
    .sig{border-top:1px solid var(--ink);padding-top:8px;text-align:center;font-size:12px;}
    .clause{margin-top:18px;}
    @media print{body{background:#fff;}.page{margin:0;padding:14mm 12mm;width:auto;min-height:auto;}table{break-inside:avoid;}}
</style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <div class="brand">CONTRATO DE PRESTAÇÃO DE SERVIÇOS
                <small>Medicina e Segurança do Trabalho</small>
            </div>
            <div class="meta">
                <div><b>Gerado em:</b> {$dataHoje}</div>
                <div><b>Proposta:</b> #{$meta['proposta_id']}</div>
            </div>
        </div>

        <h1>{$titulo}</h1>

        <div class="grid2">
            <div class="box">
                <h2>Contratada</h2>
                <div class="kv">
                    <b>Razão social</b><span>{$contratada['razao']}</span>
                    <b>CNPJ</b><span>{$contratada['cnpj']}</span>
                    <b>Endereço</b><span>{$contratada['endereco']}</span>
                </div>
            </div>
            <div class="box">
                <h2>Contratante</h2>
                <div class="kv">
                    <b>Razão social</b><span>{$contratante['razao']}</span>
                    <b>CNPJ</b><span>{$contratante['cnpj']}</span>
                    <b>Endereço</b><span>{$contratante['endereco']}</span>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <h2>Itens da Proposta</h2>
        <table>
            <thead>
                <tr>
                    <th>Serviço</th>
                    <th class="right">Qtd</th>
                    <th class="right">Valor unitário</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                {$itensRows}
                <tr>
                    <td colspan="3" class="right"><b>Total</b></td>
                    <td class="right"><b>R$ {$totalItensFmt}</b></td>
                </tr>
                <tr>
                    <td colspan="3" class="right"><b>Total geral</b></td>
                    <td class="right"><b>R$ {$totalGeralFmt}</b></td>
                </tr>
            </tbody>
        </table>

        <div class="divider"></div>

        <h2>Clínicas credenciadas</h2>
        <ul class="small">
            {$unidadesHtml}
        </ul>

        {$clausulasHtml}

        <div class="divider"></div>

        <h2>Tabela de Exames Avulsos</h2>
        <table>
            <thead>
                <tr>
                    <th>Exame</th>
                    <th class="right">Valor</th>
                </tr>
            </thead>
            <tbody>
                {$examesRows}
            </tbody>
        </table>

        <div class="divider"></div>

        <h2>Tabela de Treinamentos Avulsos</h2>
        <table>
            <thead>
                <tr>
                    <th>Treinamento</th>
                    <th class="right">Valor</th>
                </tr>
            </thead>
            <tbody>
                {$treinamentosRows}
            </tbody>
        </table>

        <div class="sig-area">
            <div class="sig">
                <b>CONTRATADA</b><br>
                {$contratada['razao']}
            </div>
            <div class="sig">
                <b>CONTRATANTE</b><br>
                {$contratante['razao']}
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function aplicarPlaceholders(string $html, array $contratada, array $contratante, string $dataHoje): string
    {
        $replace = [
            '{{CONTRATADA_RAZAO}}' => $contratada['razao'] ?? '—',
            '{{CONTRATADA_CNPJ}}' => $contratada['cnpj'] ?? '—',
            '{{CONTRATADA_ENDERECO}}' => $contratada['endereco'] ?? '—',
            '{{CONTRATANTE_RAZAO}}' => $contratante['razao'] ?? '—',
            '{{CONTRATANTE_CNPJ}}' => $contratante['cnpj'] ?? '—',
            '{{CONTRATANTE_ENDERECO}}' => $contratante['endereco'] ?? '—',
            '{{DATA_HOJE}}' => $dataHoje,
        ];

        return str_replace(array_keys($replace), array_values($replace), $html);
    }

    private function resolverServicosContrato(array $itens): array
    {
        $servicos = [];
        foreach ($itens as $item) {
            $tipo = strtoupper((string) ($item['tipo'] ?? ''));
            $nome = strtoupper((string) ($item['servico'] ?? $item['nome'] ?? ''));

            if ($tipo === 'ESOCIAL' || str_contains($nome, 'ESOCIAL')) {
                $servicos[] = 'ESOCIAL';
            }
            if ($tipo === 'ASO_TIPO' || str_contains($nome, 'ASO')) {
                $servicos[] = 'ASO';
            }
            if (str_contains($nome, 'PCMSO')) {
                $servicos[] = 'PCMSO';
            }
            if (str_contains($nome, 'PGR')) {
                $servicos[] = 'PGR';
            }
            if (str_contains($nome, 'LTCAT')) {
                $servicos[] = 'LTCAT';
            }
            if (str_contains($nome, 'TREINAMENTO') || $tipo === 'TREINAMENTO_NR') {
                $servicos[] = 'TREINAMENTO';
            }
        }

        $servicos = array_unique($servicos);
        sort($servicos);

        return $servicos;
    }

    private function isClausulaPreco(string $slug): bool
    {
        return str_starts_with($slug, 'precos-') || $slug === 'precos-cabecalho';
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}
