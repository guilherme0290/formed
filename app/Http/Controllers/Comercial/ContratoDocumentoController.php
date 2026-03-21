<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ClienteContrato;
use App\Models\ClienteContratoDocumento;
use App\Models\ContratoClausula;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContratoDocumentoController extends Controller
{
    public function edit(ClienteContrato $contrato): View
    {
        $this->authorizeContrato($contrato);

        $documento = ClienteContratoDocumento::query()
            ->where('cliente_contrato_id', $contrato->id)
            ->first();

        if (!$documento) {
            $documento = $this->gerarDocumentoTemplate($contrato);
        }

        return view('comercial.contratos.documento-edit', [
            'contrato' => $contrato,
            'documento' => $documento,
        ]);
    }

    public function generate(ClienteContrato $contrato): RedirectResponse
    {
        $this->authorizeContrato($contrato);

        $this->gerarDocumentoTemplate($contrato);

        return redirect()
            ->route('comercial.contratos.documento.edit', $contrato)
            ->with('ok', 'Contrato gerado com sucesso.');
    }

    public function update(Request $request, ClienteContrato $contrato): RedirectResponse
    {
        $this->authorizeContrato($contrato);

        $data = $request->validate([
            'html' => ['required', 'string'],
        ]);

        $user = $request->user();

        $documento = ClienteContratoDocumento::query()
            ->firstOrNew(['cliente_contrato_id' => $contrato->id], [
                'empresa_id' => $contrato->empresa_id,
                'cliente_id' => $contrato->cliente_id,
                'gerado_por' => $user->id,
            ]);

        $documento->fill([
            'status' => 'EDITADO',
            'html' => $data['html'],
            'html_original' => $documento->html_original ?: $data['html'],
            'atualizado_por' => $user->id,
        ]);

        $documento->save();

        return back()->with('ok', 'Documento atualizado com sucesso.');
    }

    private function gerarDocumentoTemplate(ClienteContrato $contrato): ClienteContratoDocumento
    {
        $user = auth()->user();

        $contrato->loadMissing(['cliente', 'empresa', 'itens.servico', 'parametroOrigem.itens']);

        $itens = $contrato->itens
            ->where('ativo', true)
            ->map(function ($item) {
                $servicoNome = (string) ($item->servico->nome ?? $item->descricao_snapshot ?? 'Serviço');
                $tipoAso = data_get($item->regras_snapshot, 'aso_tipo');

                return [
                    'nome' => $servicoNome,
                    'descricao' => $item->descricao_snapshot,
                    'quantidade' => 1,
                    'valor_unitario' => (float) ($item->preco_unitario_snapshot ?? 0),
                    'valor_total' => (float) ($item->preco_unitario_snapshot ?? 0),
                    'servico' => strtoupper($servicoNome),
                    'servico_id' => $item->servico_id,
                    'regras_snapshot' => $item->regras_snapshot,
                    'aso_tipo' => is_string($tipoAso) ? $tipoAso : null,
                ];
            })
            ->values()
            ->all();

        $servicos = $this->resolverServicosContrato($itens);

        $clausulasBase = ContratoClausula::query()
            ->where('empresa_id', $contrato->empresa_id)
            ->where('ativo', true)
            ->get()
            ->filter(function (ContratoClausula $clausula) use ($servicos) {
                $tipo = strtoupper((string) $clausula->servico_tipo);
                return $tipo === 'GERAL' || in_array($tipo, $servicos, true);
            })
            ->values();

        $clausulas = $this->buildClauseSequence($clausulasBase);

        $clausulasSnapshot = collect($clausulas)->map(fn (array $c) => [
            'id' => $c['id'] ?? null,
            'parent_id' => $c['parent_id'] ?? null,
            'numero' => $c['numero'] ?? null,
            'slug' => $c['slug'] ?? null,
            'titulo' => $c['titulo'] ?? null,
            'servico_tipo' => $c['servico_tipo'] ?? null,
            'ordem' => $c['ordem_local'] ?? null,
            'versao' => $c['versao'] ?? null,
        ])->all();

        $unidades = [];
        if ($contrato->cliente) {
            $unidades = $contrato->cliente->unidadesPermitidas()
                ->where('unidades_clinicas.empresa_id', $contrato->empresa_id)
                ->where('unidades_clinicas.ativo', true)
                ->orderBy('unidades_clinicas.nome')
                ->get(['unidades_clinicas.nome', 'unidades_clinicas.endereco', 'unidades_clinicas.telefone'])
                ->all();
        }

        $payload = $this->buildPayload($contrato, $itens, $unidades);
        $html = $this->buildHtmlTemplate($payload, $clausulas);

        $documento = ClienteContratoDocumento::query()
            ->firstOrNew(['cliente_contrato_id' => $contrato->id], [
                'empresa_id' => $contrato->empresa_id,
                'cliente_id' => $contrato->cliente_id,
            ]);

        $documento->fill([
            'status' => 'GERADO',
            'html' => $html,
            'html_original' => $documento->html_original ?: $html,
            'clausulas_snapshot' => $clausulasSnapshot,
            'gerado_por' => $documento->gerado_por ?: $user->id,
            'atualizado_por' => $user->id,
        ]);

        $documento->save();

        return $documento;
    }

    private function buildPayload(ClienteContrato $contrato, array $itens, array $unidades): array
    {
        $cliente = $contrato->cliente;
        $empresa = $contrato->empresa;
        $parametro = $contrato->parametroOrigem;

        $totalItens = array_reduce($itens, fn ($carry, $item) => $carry + (float) ($item['valor_total'] ?? 0), 0.0);

        $treinamentosParametro = [];
        if ($parametro && $parametro->relationLoaded('itens')) {
            $treinamentosParametro = $parametro->itens
                ->filter(function ($item) {
                    $tipo = strtoupper((string) ($item->tipo ?? ''));
                    return in_array($tipo, ['TREINAMENTO_NR', 'PACOTE_TREINAMENTOS'], true);
                })
                ->map(function ($item) {
                    $tipo = strtoupper((string) ($item->tipo ?? ''));
                    return [
                        'categoria' => $tipo === 'PACOTE_TREINAMENTOS' ? 'PACOTE' : 'AVULSO',
                        'nome' => (string) ($item->nome ?? $item->descricao ?? 'Treinamento'),
                        'quantidade' => (int) ($item->quantidade ?? 1),
                        'valor_unitario' => (float) ($item->valor_unitario ?? 0),
                        'valor_total' => (float) ($item->valor_total ?? $item->valor_unitario ?? 0),
                    ];
                })
                ->values()
                ->all();
        }

        return [
            'meta' => [
                'titulo' => 'Contrato de Prestação de Serviços',
                'data_hoje' => now()->format('d/m/Y'),
                'contrato_id' => $contrato->id,
                'vigencia_inicio' => optional($contrato->vigencia_inicio)->format('d/m/Y'),
                'vigencia_fim' => optional($contrato->vigencia_fim)->format('d/m/Y'),
            ],
            'contratada' => [
                'razao' => $empresa?->razao_social ?? $empresa?->nome ?? '—',
                'cnpj' => $empresa?->cnpj ?? '—',
                'endereco' => $empresa?->endereco ?? '—',
            ],
            'contratante' => [
                'razao' => $cliente?->razao_social ?? '—',
                'cnpj' => $cliente?->cnpj ?? '—',
                'endereco' => $cliente?->endereco ?? '—',
            ],
            'itens' => $itens,
            'unidades' => array_map(function ($u) {
                return [
                    'nome' => $u->nome ?? 'Unidade',
                    'endereco' => $u->endereco ?? '',
                    'telefone' => $u->telefone ?? '',
                ];
            }, $unidades),
            'totais' => [
                'total_itens' => $totalItens,
                'total_geral' => $totalItens,
            ],
            'esocial' => [
                'qtd_funcionarios' => (int) ($parametro?->esocial_qtd_funcionarios ?? 0),
                'valor_mensal' => (float) ($parametro?->esocial_valor_mensal ?? 0),
            ],
            'treinamentos_parametro' => $treinamentosParametro,
        ];
    }

    private function buildHtmlTemplate(array $payload, array $clausulas): string
    {
        $meta = $payload['meta'];
        $contratada = $payload['contratada'];
        $contratante = $payload['contratante'];
        $itens = $payload['itens'];
        $unidades = $payload['unidades'];
        $totais = $payload['totais'];
        $esocialPayload = $payload['esocial'] ?? [];
        $treinamentosParametro = $payload['treinamentos_parametro'] ?? [];

        $dataHoje = $meta['data_hoje'] ?? now()->format('d/m/Y');
        $contratadaRazao = e((string) ($contratada['razao'] ?? '—'));
        $contratadaCnpj = e((string) ($contratada['cnpj'] ?? '—'));
        $contratadaEndereco = e((string) ($contratada['endereco'] ?? '—'));
        $contratanteRazao = e((string) ($contratante['razao'] ?? '—'));
        $contratanteCnpj = e((string) ($contratante['cnpj'] ?? '—'));
        $contratanteEndereco = e((string) ($contratante['endereco'] ?? '—'));
        $titulo = e((string) ($meta['titulo'] ?? 'Contrato de Prestação de Serviços'));

        $generalItems = [];
        $trainingItems = [];
        $asoItems = [];
        $esocialItems = [];

        foreach ($itens as $item) {
            if ($this->isEsocialItem($item)) {
                $esocialItems[] = $item;
                continue;
            }

            if ($this->isAsoItem($item)) {
                $asoItems[] = $item;
                continue;
            }

            if ($this->isTrainingItem($item)) {
                $trainingItems[] = $item;
                continue;
            }

            $generalItems[] = $item;
        }

        $rowsGeneral = $this->buildRowsItens($generalItems, true);
        $rowsTraining = $this->buildRowsTreinamentosResumo($trainingItems);
        $sectionAsoConsolidado = $this->renderAsoConsolidadoSection($asoItems);

        $cardsUnidades = '';
        foreach ($unidades as $unidade) {
            $cardsUnidades .= '<article class="unit-card">'
                . '<div class="unit-head">' . e((string) ($unidade['nome'] ?? 'Unidade')) . '</div>'
                . '<div class="unit-line"><span class="unit-label">Endereço:</span> ' . e((string) ($unidade['endereco'] ?? 'Não informado')) . '</div>'
                . '<div class="unit-line"><span class="unit-label">Telefone:</span> ' . e((string) ($unidade['telefone'] ?? 'Não informado')) . '</div>'
                . '</article>';
        }
        if ($cardsUnidades === '') {
            $cardsUnidades = '<article class="unit-card"><div class="unit-head">Sem unidades cadastradas</div><div class="unit-line">Nenhuma unidade permitida configurada para este cliente.</div></article>';
        }

        $clausulasHtml = '';
        foreach ($clausulas as $clausula) {
            $numeroClausula = trim((string) ($clausula['numero'] ?? ''));
            $tituloBase = (string) ($clausula['titulo'] ?? 'CLÁUSULA');
            $tituloClausula = e($this->resolveClauseTitle($tituloBase, $numeroClausula));

            $conteudoBruto = (string) ($clausula['html_template'] ?? '');
            $conteudoBruto = str_replace('{{NUMERO_CLAUSULA}}', $numeroClausula, $conteudoBruto);
            $conteudo = $this->aplicarPlaceholders($conteudoBruto, $contratada, $contratante, $meta);
            $conteudo = $this->normalizeClauseHtml($conteudo);
            $clausulasHtml .= '<section class="clause"><h3>' . $tituloClausula . '</h3>' . $conteudo . '</section>';
        }

        if ($clausulasHtml === '') {
            $clausulasHtml = '<section class="clause"><h3>CLÁUSULAS</h3><p>Nenhuma cláusula ativa para esta empresa.</p></section>';
        }

        $sectionEsocial = $this->renderEsocialTable($esocialPayload, !empty($esocialItems));
        $rowsTreinamentosDetalhados = $this->buildRowsTreinamentosDetalhados($treinamentosParametro);
        $sectionTreinamentos = $rowsTreinamentosDetalhados !== ''
            ? $this->renderSectionTable('Tabela de Treinamentos', $rowsTreinamentosDetalhados, ['Categoria', 'Treinamento/Pacote', 'Total'])
            : $this->renderSectionTable('Tabela de Treinamentos', $rowsTraining, ['Treinamento/Pacote', 'Total']);
        $logoHtml = '';
        $logoPath = public_path('favicon.png');
        if (is_file($logoPath) && is_readable($logoPath)) {
            $logoBin = @file_get_contents($logoPath);
            if ($logoBin !== false) {
                $logoSrc = 'data:image/png;base64,' . base64_encode($logoBin);
                $logoHtml = '<img src="' . $logoSrc . '" alt="Formed" class="brand-logo" />';
            }
        }

        return <<<HTML
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<title>Contrato de Prestação de Serviços</title>
<style>
:root{
    --ink:#1f2937;
    --muted:#64748b;
    --line:#dbe4f0;
    --soft:#f4f7fb;
    --brand:#1f2a7c;
    --brand-strong:#1e3a8a;
    --brand-soft:#eef4ff;
    --brand-text-soft:#dbeafe;
}
*{box-sizing:border-box;}
html,body,*{
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
@page{margin:7mm 7mm 12mm;}
body{margin:0;font-family:Arial,Helvetica,sans-serif;color:var(--ink);background:#fff;}
.page{width:210mm;min-height:297mm;margin:0 auto;padding:8mm 7mm 12mm;}
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    padding:10px 12px;
    margin-bottom:14px;
    background:var(--brand) !important;
    color:#fff;
    border:1px solid var(--brand-strong);
}
.brand-wrap{display:flex;align-items:center;gap:8px;}
.brand-logo{
    width:30px;
    height:30px;
    object-fit:contain;
}
.brand{font-weight:700;font-size:14px;color:#fff;}
.meta{text-align:right;font-size:11px;color:var(--brand-text-soft);line-height:1.35;}
h1{
    margin:10px 0 14px;
    text-align:center;
    font-size:16px;
    color:var(--brand-strong);
}
h2{
    margin:16px 0 8px;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#475569;
    background:var(--soft) !important;
    border:1px solid var(--line);
    padding:6px 9px;
}
p{margin:8px 0;font-size:12px;line-height:1.45;text-align:justify;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;}
.box{border:1px solid var(--line);border-radius:10px;padding:10px;background:#fff;}
.box h2{margin:0 0 10px;border-radius:8px;}
.kv{display:grid;grid-template-columns:140px 1fr;gap:6px 10px;font-size:12px;}
.divider{height:1px;background:var(--line);margin:14px 0;}
table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;}
th,td{border:1px solid var(--line);padding:8px;vertical-align:top;}
th{
    background:var(--brand) !important;
    color:#ffffff;
    text-align:left;
    font-weight:700;
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.06em;
}
.right{text-align:right;}
.clause{margin-top:18px;}
.clause h3{
    margin:0 0 8px;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#475569;
    background:var(--brand-soft) !important;
    border:1px solid #c9d9ff;
    padding:6px 9px;
}
.sig-area{margin-top:56px;padding-top:20px;display:grid;grid-template-columns:1fr 1fr;gap:24px;}
.sig{text-align:center;font-size:12px;}
.sig-line{height:64px;border-bottom:1px solid #94a3b8;margin-bottom:8px;}
.small{font-size:11px;color:var(--muted);}
.ghe-block{border:1px solid var(--line);border-radius:10px;padding:10px;margin-top:10px;break-inside:avoid;}
.ghe-head{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:8px;}
.ghe-title{font-size:12px;font-weight:700;}
.ghe-total{font-size:11px;color:var(--muted);}
.units-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.unit-card{border:1px solid var(--line);border-radius:10px;padding:10px 12px;background:#fff;break-inside:avoid;}
.unit-head{font-size:12px;font-weight:700;margin-bottom:6px;color:var(--brand-strong);}
.unit-line{font-size:11px;color:#334155;line-height:1.45;margin:2px 0;}
.unit-label{font-weight:700;color:#111827;}
@media print{
    body{background:#fff !important;}
    .topbar,th,h2,.clause h3{
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div class="brand-wrap">
            {$logoHtml}
            <div class="brand">CONTRATO DE PRESTAÇÃO DE SERVIÇOS</div>
        </div>
        <div class="meta">
            <div><b>Gerado em:</b> {$dataHoje}</div>
            <div><b>Contrato:</b> #{$meta['contrato_id']}</div>
        </div>
    </div>

    <h1>{$titulo}</h1>

    <div class="grid2">
        <div class="box">
                <h2>Contratada</h2>
                <div class="kv">
                    <b>Razão social</b><span>{$contratadaRazao}</span>
                    <b>CNPJ</b><span>{$contratadaCnpj}</span>
                    <b>Endereço</b><span>{$contratadaEndereco}</span>
                </div>
            </div>
            <div class="box">
                <h2>Contratante</h2>
                <div class="kv">
                    <b>Razão social</b><span>{$contratanteRazao}</span>
                    <b>CNPJ</b><span>{$contratanteCnpj}</span>
                    <b>Endereço</b><span>{$contratanteEndereco}</span>
                </div>
            </div>
    </div>

    <div class="divider"></div>

    <h2>Tabela de Serviços Contratados</h2>
    <table>
        <thead>
            <tr>
                <th>Serviço</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            {$rowsGeneral}
        </tbody>
    </table>

    {$sectionAsoConsolidado}
    {$sectionEsocial}
    {$sectionTreinamentos}

    <div class="divider"></div>

    <h2>Clínicas credenciadas (unidades permitidas)</h2>
    <div class="units-grid">{$cardsUnidades}</div>

    {$clausulasHtml}

    <div class="sig-area">
        <div class="sig">
            <div class="sig-line"></div>
            <b>CONTRATADA</b><br>{$contratadaRazao}
        </div>
        <div class="sig">
            <div class="sig-line"></div>
            <b>CONTRATANTE</b><br>{$contratanteRazao}
        </div>
    </div>
</div>
</body>
</html>
HTML;
    }

    private function renderSectionTable(string $titulo, string $rows, array $headers): string
    {
        if (trim($rows) === '') {
            return '';
        }

        $thead = '<tr>';
        foreach ($headers as $idx => $head) {
            $align = $idx >= (count($headers) - 1) ? ' class="right"' : '';
            $thead .= '<th' . $align . '>' . e($head) . '</th>';
        }
        $thead .= '</tr>';

        return '<div class="divider"></div>'
            . '<h2>' . e($titulo) . '</h2>'
            . '<table><thead>' . $thead . '</thead><tbody>' . $rows . '</tbody></table>';
    }

    private function renderAsoConsolidadoSection(array $asoItems): string
    {
        if (empty($asoItems)) {
            return '';
        }

        $gheMap = [];
        foreach ($asoItems as $item) {
            $ghes = data_get($item, 'regras_snapshot.ghes', []);
            if (!is_array($ghes)) {
                continue;
            }

            foreach ($ghes as $ghe) {
                $gheId = (string) ($ghe['id'] ?? '');
                $key = $gheId !== '' ? $gheId : md5((string) ($ghe['nome'] ?? 'GHE'));
                $gheMap[$key] = $ghe;
            }
        }

        if (empty($gheMap)) {
            return '';
        }

        $tipoOrder = ['admissional', 'periodico', 'demissional', 'mudanca_funcao', 'retorno_trabalho'];
        $rows = '';
        $seen = [];

        foreach ($gheMap as $ghe) {
            foreach ($tipoOrder as $tipo) {
                $exames = data_get($ghe, 'exames_por_tipo.' . $tipo, []);
                if (!is_array($exames)) {
                    continue;
                }

                foreach ($exames as $exame) {
                    $id = (int) ($exame['id'] ?? 0);
                    $titulo = trim((string) ($exame['titulo'] ?? 'Exame'));
                    $key = $id > 0 ? 'id:' . $id : 'titulo:' . mb_strtolower($titulo);

                    // Regra solicitada: manter apenas a primeira ocorrência.
                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $rows .= '<tr>'
                        . '<td>' . e($titulo !== '' ? $titulo : 'Exame') . '</td>'
                        . '<td class="right">R$ ' . $this->formatMoney((float) ($exame['preco'] ?? 0)) . '</td>'
                        . '</tr>';
                }
            }
        }

        if ($rows === '') {
            return '';
        }

        return $this->renderSectionTable('Tabela de Exames ASO', $rows, ['Descrição do exame', 'Valor']);
    }

    private function renderEsocialTable(array $esocialPayload, bool $hasEsocialItem): string
    {
        if (!$hasEsocialItem) {
            return '';
        }

        $qtd = (int) ($esocialPayload['qtd_funcionarios'] ?? 0);
        $valorMensal = (float) ($esocialPayload['valor_mensal'] ?? 0);

        $descricao = 'Envio das Informações ao E-social:<br>'
            . 'S-2210 - CAT – comunicado de acidente do trabalho<br>'
            . 'S-2220 – ASO – Atestado de saude ocupacional<br>'
            . 'S-2240 – LTCAT Laudo técnico das condições de ambiente do trabalho (caso tenha)';

        $quantidadeLabel = $qtd > 0
            ? 'Até ' . e((string) $qtd) . ' colaboradores'
            : 'Conforme parametrização';

        return '<div class="divider"></div>'
            . '<h2>Tabela de eSocial</h2>'
            . '<table>'
            . '<thead><tr>'
            . '<th>E-SOCIAL</th>'
            . '<th class="right">QUANTIDADE</th>'
            . '<th class="right">MENSALIDADE</th>'
            . '</tr></thead>'
            . '<tbody><tr>'
            . '<td>' . $descricao . '</td>'
            . '<td class="right"><b>' . $quantidadeLabel . '</b></td>'
            . '<td class="right"><b>R$ ' . $this->formatMoney($valorMensal) . '</b></td>'
            . '</tr></tbody>'
            . '</table>';
    }

    private function buildRowsItens(array $items, bool $renderEmptyRow): string
    {
        if (empty($items)) {
            return $renderEmptyRow
                ? '<tr><td colspan="2" class="small">Nenhum item nesta seção.</td></tr>'
                : '';
        }

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>'
                . '<td>' . e((string) ($item['nome'] ?? 'Serviço')) . '</td>'
                . '<td class="right">R$ ' . $this->formatMoney((float) ($item['valor_total'] ?? 0)) . '</td>'
                . '</tr>';
        }

        return $rows;
    }

    private function buildRowsTreinamentosDetalhados(array $treinamentos): string
    {
        if (empty($treinamentos)) {
            return '';
        }

        $rows = '';
        foreach ($treinamentos as $item) {
            $rows .= '<tr>'
                . '<td>' . e((string) ($item['categoria'] ?? '')) . '</td>'
                . '<td>' . e((string) ($item['nome'] ?? 'Treinamento')) . '</td>'
                . '<td class="right">R$ ' . $this->formatMoney((float) ($item['valor_total'] ?? 0)) . '</td>'
                . '</tr>';
        }

        return $rows;
    }

    private function buildRowsTreinamentosResumo(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>'
                . '<td>' . e((string) ($item['nome'] ?? 'Treinamento')) . '</td>'
                . '<td class="right">R$ ' . $this->formatMoney((float) ($item['valor_total'] ?? 0)) . '</td>'
                . '</tr>';
        }

        return $rows;
    }

    private function isTrainingItem(array $item): bool
    {
        $nome = strtoupper((string) ($item['nome'] ?? $item['servico'] ?? ''));
        return str_contains($nome, 'TREINAMENTO') || str_contains($nome, 'PACOTE') || str_contains($nome, 'NR-');
    }

    private function isEsocialItem(array $item): bool
    {
        $nome = strtoupper((string) ($item['nome'] ?? $item['servico'] ?? ''));
        return str_contains($nome, 'ESOCIAL') || str_contains($nome, 'E-SOCIAL');
    }

    private function isAsoItem(array $item): bool
    {
        if (!empty($item['aso_tipo'])) {
            return true;
        }

        if (!empty(data_get($item, 'regras_snapshot.aso_tipo')) || !empty(data_get($item, 'regras_snapshot.ghes'))) {
            return true;
        }

        $nome = strtoupper((string) ($item['nome'] ?? $item['servico'] ?? ''));
        return str_contains($nome, 'ASO');
    }

    private function aplicarPlaceholders(string $html, array $contratada, array $contratante, array $meta): string
    {
        $replace = [
            '{{CONTRATADA_RAZAO}}' => $contratada['razao'] ?? '—',
            '{{CONTRATADA_CNPJ}}' => $contratada['cnpj'] ?? '—',
            '{{CONTRATADA_ENDERECO}}' => $contratada['endereco'] ?? '—',
            '{{CONTRATANTE_RAZAO}}' => $contratante['razao'] ?? '—',
            '{{CONTRATANTE_CNPJ}}' => $contratante['cnpj'] ?? '—',
            '{{CONTRATANTE_ENDERECO}}' => $contratante['endereco'] ?? '—',
            '{{DATA_HOJE}}' => $meta['data_hoje'] ?? now()->format('d/m/Y'),
            '{{VIGENCIA_INICIO}}' => $meta['vigencia_inicio'] ?? '—',
            '{{VIGENCIA_FIM}}' => $meta['vigencia_fim'] ?? '—',
        ];

        return str_replace(array_keys($replace), array_values($replace), $html);
    }

    private function normalizeClauseHtml(string $html): string
    {
        $normalized = trim($html);
        $normalized = preg_replace('/^\s*<h3\b[^>]*>.*?<\/h3>\s*/is', '', $normalized) ?? $normalized;
        if ($normalized === '') {
            return '<p>Conteúdo da cláusula.</p>';
        }

        return $normalized;
    }

    private function resolverServicosContrato(array $itens): array
    {
        $servicos = [];

        foreach ($itens as $item) {
            $nome = strtoupper((string) ($item['servico'] ?? $item['nome'] ?? ''));

            if (str_contains($nome, 'ESOCIAL') || str_contains($nome, 'E-SOCIAL')) {
                $servicos[] = 'ESOCIAL';
            }
            if (str_contains($nome, 'ASO')) {
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
            if (str_contains($nome, 'TREINAMENTO') || str_contains($nome, 'NR-')) {
                $servicos[] = 'TREINAMENTO';
            }
        }

        $servicos = array_values(array_unique($servicos));
        sort($servicos);

        return $servicos;
    }

    private function resolveClauseTitle(string $tituloBase, string $numeroClausula): string
    {
        $numero = trim($numeroClausula);
        if ($numero === '') {
            return trim($tituloBase) !== '' ? trim($tituloBase) : 'CLÁUSULA';
        }

        if (str_contains($tituloBase, '{{NUMERO_CLAUSULA}}')) {
            return trim(str_replace('{{NUMERO_CLAUSULA}}', $numero, $tituloBase));
        }

        $limpo = preg_replace('/^\s*CL[ÁA]USULA\s+\d+(\.\d+)?\s*-\s*/iu', '', $tituloBase) ?? $tituloBase;
        $limpo = trim($limpo);
        if ($limpo === '') {
            return 'CLÁUSULA ' . $numero;
        }

        return 'CLÁUSULA ' . $numero . ' - ' . $limpo;
    }

    private function buildClauseSequence($clausulas): array
    {
        $roots = $clausulas
            ->whereNull('parent_id')
            ->sortBy(fn (ContratoClausula $c) => sprintf('%010d-%010d', (int) ($c->ordem_local ?? $c->ordem ?? 0), (int) $c->id))
            ->values();

        $childrenByParent = $clausulas
            ->whereNotNull('parent_id')
            ->groupBy('parent_id')
            ->map(fn ($group) => $group
                ->sortBy(fn (ContratoClausula $c) => sprintf('%010d-%010d', (int) ($c->ordem_local ?? $c->ordem ?? 0), (int) $c->id))
                ->values());

        $result = [];
        foreach ($roots as $rootIndex => $root) {
            $numeroRoot = (string) ($rootIndex + 1);
            $result[] = $this->mapClauseToArray($root, $numeroRoot);

            $children = $childrenByParent->get($root->id, collect());
            foreach ($children as $childIndex => $child) {
                $result[] = $this->mapClauseToArray($child, $numeroRoot . '.' . ($childIndex + 1));
            }
        }

        return $result;
    }

    private function mapClauseToArray(ContratoClausula $clausula, string $numero): array
    {
        return [
            'id' => $clausula->id,
            'parent_id' => $clausula->parent_id,
            'numero' => $numero,
            'slug' => $clausula->slug,
            'titulo' => $clausula->titulo,
            'servico_tipo' => $clausula->servico_tipo,
            'ordem_local' => $clausula->ordem_local ?? $clausula->ordem ?? 0,
            'versao' => $clausula->versao,
            'html_template' => $clausula->html_template,
        ];
    }

    private function authorizeContrato(ClienteContrato $contrato): void
    {
        $user = auth()->user();
        abort_unless($contrato->empresa_id === $user->empresa_id, 403);

        if (!$user->hasPapel('Master')) {
            abort_unless((int) $contrato->vendedor_id === (int) $user->id, 403);
        }
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}
