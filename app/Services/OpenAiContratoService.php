<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiContratoService
{
    public function gerarHtml(array $payload, ?string $promptCustom = null): ?array
    {
        $apiKey = (string) config('services.openai.key');
        $model = (string) config('services.openai.model');

        if ($apiKey === '' || $model === '') {
            return [
                'error' => 'Integração com IA não configurada. Verifique OPENAI_API_KEY e OPENAI_MODEL.',
                'error_details' => [
                    'code' => 'missing_config',
                ],
            ];
        }

        $prompt = $this->buildPrompt($payload, $promptCustom);

        $body = [
            'model' => $model,
            'input' => $prompt,
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'contrato_html',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'html' => ['type' => 'string'],
                            'clausulas_incluidas' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'warnings' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                        'required' => ['html', 'clausulas_incluidas', 'warnings'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];

        $response = Http::timeout(9000)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/responses', $body);

        if (!$response->successful()) {
            Log::warning('Falha ao gerar contrato via OpenAI', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'error' => 'Falha ao gerar contrato via IA.',
                'error_details' => [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            ];
        }

        $data = $response->json();
        $text = $this->extractText($data);
        if ($text === null) {
            return [
                'error' => 'Resposta da IA inválida (sem conteúdo).',
                'error_details' => [
                    'code' => 'empty_output',
                ],
            ];
        }

        $decoded = json_decode($text, true);
        if (!is_array($decoded) || empty($decoded['html'])) {
            return [
                'error' => 'Resposta da IA inválida (JSON inesperado).',
                'error_details' => [
                    'raw' => $text,
                ],
            ];
        }

        return $decoded;
    }

    private function extractText(array $response): ?string
    {
        $output = $response['output'][0]['content'][0]['text'] ?? null;
        if (is_string($output) && $output !== '') {
            return $output;
        }

        return null;
    }

    private function buildPrompt(array $payload, ?string $promptCustom): string
    {
        $custom = $promptCustom ? "\n\nInstruções adicionais do usuário:\n" . $promptCustom : '';

        return "Você é um assistente que gera contratos em HTML com base em um JSON.\n"
            . "Use SOMENTE as cláusulas informadas e mantenha a ordem fornecida.\n"
            . "Não invente cláusulas. Não altere valores numéricos fornecidos.\n"
            . "Regra de preços:\n"
            . "- Monte a seção de preços usando os itens da proposta (payload.itens) e os totais (payload.totais).\n"
            . "- Tabela de exames avulsos deve vir de payload.tabelas_avulsas.exames.\n"
            . "- Tabela de treinamentos avulsos deve vir de payload.tabelas_avulsas.treinamentos.\n"
            . "Formatação obrigatória:\n"
            . "- Entregue HTML pronto para impressão, elegante e moderno.\n"
            . "- Títulos de cláusulas em negrito e separados do conteúdo por margem.\n"
            . "- Espaçamento claro entre parágrafos, itens e seções.\n"
            . "- Quando houver tabela de valores, gere tabela HTML com thead/tbody, bordas leves e alinhamento consistente.\n"
            . "- Use listas (<ul>/<ol>) para itens e subitens quando fizer sentido.\n"
            . "- Não deixe textos grudados; sempre use parágrafos (<p>) ou itens de lista.\n"
            . "Inclua um CSS base no <head> com uma aparência moderna e limpa:\n"
            . "- Fonte: \"Inter\", \"Segoe UI\", Arial, sans-serif\n"
            . "- Texto em #0f172a, fundo branco, margens generosas\n"
            . "- Títulos com cor #0f172a, subtítulos #334155\n"
            . "- Tabelas com cabeçalho #f1f5f9, borda #e2e8f0, zebra leve #f8fafc\n"
            . "- Espaçamento vertical padrão entre seções (16-24px)\n"
            . "Obrigatório: sempre retornar HTML completo com <html>, <head>, <body> e <style> embutido contendo classes padrão.\n"
            . "Template visual obrigatório (use estes nomes e estrutura base):\n"
            . "- Container principal: <div class=\"page\"> com largura A4 (210mm) e padding 16-18mm.\n"
            . "- Topo com cabeçalho: <div class=\"topbar\"> contendo marca/título e meta à direita.\n"
            . "- Seções em caixas: .box e .grid2 para dados da contratada/contratante, com layout semelhante ao DOCX (duas caixas lado a lado, campos alinhados em grid com rótulo e valor).\n"
            . "- Divisores: <div class=\"divider\"></div> entre grandes blocos.\n"
            . "- Tabelas com cabeçalho sombreado e linhas suaves.\n"
            . "- Assinaturas em .sig-area com duas colunas lado a lado (CONTRATADA e CONTRATANTE), linhas superiores e alinhamento central.\n"
            . "Use o seguinte CSS como base (adapte se necessário, mas preserve a estética):\n"
            . ":root{--ink:#111827;--muted:#6b7280;--line:#e5e7eb;--soft:#f9fafb;}*{box-sizing:border-box;}body{margin:0;font-family:Arial,Helvetica,sans-serif;color:var(--ink);background:#fff;}.page{width:210mm;min-height:297mm;margin:0 auto;padding:18mm 16mm;}.topbar{display:flex;justify-content:space-between;gap:16px;border-bottom:2px solid var(--ink);padding-bottom:10px;margin-bottom:14px;}.brand{font-weight:700;letter-spacing:.2px;font-size:14px;}.brand small{display:block;font-weight:400;color:var(--muted);margin-top:3px;font-size:11px;letter-spacing:0;}.meta{text-align:right;font-size:11px;color:var(--muted);line-height:1.35;}h1{margin:10px 0 14px;text-align:center;font-size:16px;letter-spacing:.2px;}h2{margin:16px 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:.4px;}p{margin:8px 0;font-size:12px;line-height:1.45;text-align:justify;}.grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;margin-top:8px;}.box{border:1px solid var(--line);border-radius:10px;padding:10px;background:#fff;}.kv{display:grid;grid-template-columns:140px 1fr;gap:6px 10px;font-size:12px;line-height:1.35;}.kv b{color:var(--ink);}.kv span{color:var(--muted);}.divider{height:1px;background:var(--line);margin:14px 0;}table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;}th,td{border:1px solid var(--line);padding:8px 8px;vertical-align:top;}th{background:var(--soft);text-align:left;font-weight:700;}.right{text-align:right;}.small{font-size:11px;color:var(--muted);}.pill{display:inline-block;padding:2px 8px;border:1px solid var(--line);border-radius:999px;font-size:11px;color:var(--muted);background:#fff;}.sig-area{margin-top:24px;display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:end;}.sig{border-top:1px solid var(--ink);padding-top:8px;text-align:center;font-size:12px;}.witness{margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:18px;}.wbox{border:1px solid var(--line);border-radius:10px;padding:10px;}.wline{border-bottom:1px dashed var(--line);height:18px;margin:6px 0 8px;}.footnote{margin-top:14px;font-size:10.5px;color:var(--muted);line-height:1.4;}@media print{body{background:#fff;}.page{margin:0;padding:14mm 12mm;width:auto;min-height:auto;}.box,.wbox{break-inside:avoid;}table{break-inside:avoid;}}\n"
            . "Retorne JSON no schema solicitado.\n"
            . $custom
            . "\n\nJSON:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
