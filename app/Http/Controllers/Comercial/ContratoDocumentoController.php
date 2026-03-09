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

        $contrato->loadMissing(['cliente', 'empresa', 'itens.servico', 'parametroOrigem']);

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

        $clausulas = ContratoClausula::query()
            ->where('empresa_id', $contrato->empresa_id)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->get()
            ->filter(function (ContratoClausula $clausula) use ($servicos) {
                $tipo = strtoupper((string) $clausula->servico_tipo);
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

        $unidades = [];
        if ($contrato->cliente) {
            $unidades = $contrato->cliente->unidadesPermitidas()
                ->where('unidades_clinicas.empresa_id', $contrato->empresa_id)
                ->where('unidades_clinicas.ativo', true)
                ->orderBy('unidades_clinicas.nome')
                ->get(['unidades_clinicas.nome', 'unidades_clinicas.endereco'])
                ->all();
        }

        $payload = $this->buildPayload($contrato, $itens, $unidades);
        $html = $this->buildHtmlTemplate($payload, $clausulas->all());

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

        $dataHoje = $meta['data_hoje'] ?? now()->format('d/m/Y');
        $totalItensFmt = $this->formatMoney((float) ($totais['total_itens'] ?? 0));
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
        $rowsTraining = $this->buildRowsItens($trainingItems, false);
        $rowsEsocial = $this->buildRowsItens($esocialItems, false);
        $sectionAsoPorGhe = $this->renderAsoPorGheSection($asoItems);

        $rowsUnidades = '';
        foreach ($unidades as $unidade) {
            $rowsUnidades .= '<li><strong>' . e($unidade['nome']) . '</strong><br>' . e($unidade['endereco']) . '</li>';
        }
        if ($rowsUnidades === '') {
            $rowsUnidades = '<li>Nenhuma unidade permitida configurada para este cliente.</li>';
        }

        $clausulasHtml = '';
        foreach ($clausulas as $clausula) {
            $tituloClausula = e((string) ($clausula->titulo ?? 'CLÁUSULA'));
            $conteudo = $this->aplicarPlaceholders($clausula->html_template, $contratada, $contratante, $meta);
            $conteudo = $this->normalizeClauseHtml($conteudo);
            $clausulasHtml .= '<section class="clause"><h3>' . $tituloClausula . '</h3>' . $conteudo . '</section>';
        }

        if ($clausulasHtml === '') {
            $clausulasHtml = '<section class="clause"><h3>CLÁUSULAS</h3><p>Nenhuma cláusula ativa para esta empresa.</p></section>';
        }

        $sectionEsocial = $this->renderSectionTable('Tabela de eSocial', $rowsEsocial, ['Serviço', 'Qtd', 'Valor unitário', 'Total']);
        $sectionEsocialResumo = $this->renderEsocialResumo($esocialPayload, !empty($esocialItems));
        $sectionTreinamentos = $this->renderSectionTable('Tabela de Treinamentos', $rowsTraining, ['Treinamento/Pacote', 'Qtd', 'Valor unitário', 'Total']);

        return <<<HTML
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<title>Contrato de Prestação de Serviços</title>
<style>
:root{--ink:#111827;--muted:#6b7280;--line:#e5e7eb;--soft:#f8fafc;}
*{box-sizing:border-box;}
body{margin:0;font-family:Arial,Helvetica,sans-serif;color:var(--ink);background:#fff;}
.page{width:210mm;min-height:297mm;margin:0 auto;padding:18mm 16mm;}
.topbar{display:flex;justify-content:space-between;gap:16px;border-bottom:2px solid var(--ink);padding-bottom:10px;margin-bottom:14px;}
.brand{font-weight:700;font-size:14px;}
.meta{text-align:right;font-size:11px;color:var(--muted);line-height:1.35;}
h1{margin:10px 0 14px;text-align:center;font-size:16px;}
h2{margin:16px 0 8px;font-size:13px;text-transform:uppercase;}
p{margin:8px 0;font-size:12px;line-height:1.45;text-align:justify;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;}
.box{border:1px solid var(--line);border-radius:10px;padding:10px;background:#fff;}
.kv{display:grid;grid-template-columns:140px 1fr;gap:6px 10px;font-size:12px;}
.divider{height:1px;background:var(--line);margin:14px 0;}
table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;}
th,td{border:1px solid var(--line);padding:8px;vertical-align:top;}
th{background:var(--soft);text-align:left;font-weight:700;}
.right{text-align:right;}
.clause{margin-top:18px;}
.sig-area{margin-top:24px;display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.sig{border-top:1px solid var(--ink);padding-top:8px;text-align:center;font-size:12px;}
.small{font-size:11px;color:var(--muted);}
.ghe-block{border:1px solid var(--line);border-radius:10px;padding:10px;margin-top:10px;break-inside:avoid;}
.ghe-head{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:8px;}
.ghe-title{font-size:12px;font-weight:700;}
.ghe-total{font-size:11px;color:var(--muted);}
</style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div class="brand">CONTRATO DE PRESTAÇÃO DE SERVIÇOS</div>
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
                <th class="right">Qtd</th>
                <th class="right">Valor unitário</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            {$rowsGeneral}
            <tr>
                <td colspan="3" class="right"><b>Total</b></td>
                <td class="right"><b>R$ {$totalItensFmt}</b></td>
            </tr>
        </tbody>
    </table>

    {$sectionAsoPorGhe}
    {$sectionEsocial}
    {$sectionEsocialResumo}
    {$sectionTreinamentos}

    <div class="divider"></div>

    <h2>Clínicas credenciadas (unidades permitidas)</h2>
    <ul class="small">{$rowsUnidades}</ul>

    {$clausulasHtml}

    <div class="sig-area">
        <div class="sig"><b>CONTRATADA</b><br>{$contratadaRazao}</div>
        <div class="sig"><b>CONTRATANTE</b><br>{$contratanteRazao}</div>
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

    private function renderAsoPorGheSection(array $asoItems): string
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
            $rowsFallback = '';
            foreach ($asoItems as $item) {
                $tipo = (string) ($item['aso_tipo'] ?? '');
                $tipo = $tipo !== '' ? strtoupper(str_replace('_', ' ', $tipo)) : 'ASO';
                $rowsFallback .= '<tr>'
                    . '<td>' . e($tipo) . '</td>'
                    . '<td>' . e((string) ($item['nome'] ?? 'ASO')) . '</td>'
                    . '<td class="right">R$ ' . $this->formatMoney((float) ($item['valor_total'] ?? 0)) . '</td>'
                    . '</tr>';
            }

            return $this->renderSectionTable('Tabela de ASO por Tipo', $rowsFallback, ['Tipo ASO', 'Descrição', 'Valor']);
        }

        $tipoOrder = ['admissional', 'periodico', 'demissional', 'mudanca_funcao', 'retorno_trabalho'];
        $html = '<div class="divider"></div><h2>Tabela de ASO por GHE</h2>';

        foreach ($gheMap as $ghe) {
            $gheNome = (string) ($ghe['nome'] ?? 'GHE');
            $totalGhe = 0.0;
            $tiposHtml = '';

            foreach ($tipoOrder as $tipo) {
                $exames = data_get($ghe, 'exames_por_tipo.' . $tipo, []);
                $subtotal = data_get($ghe, 'total_por_tipo.' . $tipo);

                if (!is_array($exames)) {
                    $exames = [];
                }

                if (!is_numeric($subtotal)) {
                    $subtotal = array_reduce($exames, fn ($carry, $ex) => $carry + (float) ($ex['preco'] ?? 0), 0.0);
                } else {
                    $subtotal = (float) $subtotal;
                }

                if ($subtotal <= 0 && empty($exames)) {
                    continue;
                }

                $totalGhe += $subtotal;
                $tipoLabel = strtoupper(str_replace('_', ' ', $tipo));

                $rows = '';
                foreach ($exames as $exame) {
                    $rows .= '<tr>'
                        . '<td>' . e((string) ($exame['titulo'] ?? 'Exame')) . '</td>'
                        . '<td class="right">R$ ' . $this->formatMoney((float) ($exame['preco'] ?? 0)) . '</td>'
                        . '</tr>';
                }
                if ($rows === '') {
                    $rows = '<tr><td colspan="2" class="small">Sem exames detalhados para este tipo.</td></tr>';
                }

                $tiposHtml .= '<h3 style="margin-top:12px;">' . e($tipoLabel) . '</h3>'
                    . '<table><thead><tr><th>Exame</th><th class="right">Valor</th></tr></thead><tbody>'
                    . $rows
                    . '<tr><td class="right"><b>Subtotal ' . e($tipoLabel) . '</b></td><td class="right"><b>R$ ' . $this->formatMoney($subtotal) . '</b></td></tr>'
                    . '</tbody></table>';
            }

            if ($tiposHtml === '') {
                continue;
            }

            $html .= '<section class="ghe-block">'
                . '<div class="ghe-head">'
                . '<div class="ghe-title">GHE: ' . e($gheNome) . '</div>'
                . '<div class="ghe-total">Total GHE: R$ ' . $this->formatMoney($totalGhe) . '</div>'
                . '</div>'
                . $tiposHtml
                . '</section>';
        }

        return $html;
    }

    private function renderEsocialResumo(array $esocialPayload, bool $hasEsocialItem): string
    {
        if (!$hasEsocialItem) {
            return '';
        }

        $qtd = (int) ($esocialPayload['qtd_funcionarios'] ?? 0);
        $valorMensal = (float) ($esocialPayload['valor_mensal'] ?? 0);

        return '<p class="small"><b>eSocial:</b> Quantidade de funcionários: '
            . e((string) $qtd)
            . ' • Mensalidade: R$ '
            . $this->formatMoney($valorMensal)
            . '</p>';
    }

    private function buildRowsItens(array $items, bool $renderEmptyRow): string
    {
        if (empty($items)) {
            return $renderEmptyRow
                ? '<tr><td colspan="4" class="small">Nenhum item nesta seção.</td></tr>'
                : '';
        }

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>'
                . '<td>' . e((string) ($item['nome'] ?? 'Serviço')) . '</td>'
                . '<td class="right">' . e((string) ($item['quantidade'] ?? 1)) . '</td>'
                . '<td class="right">R$ ' . $this->formatMoney((float) ($item['valor_unitario'] ?? 0)) . '</td>'
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
