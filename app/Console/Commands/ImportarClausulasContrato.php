<?php

namespace App\Console\Commands;

use App\Models\ContratoClausula;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ZipArchive;

class ImportarClausulasContrato extends Command
{
    protected $signature = 'contratos:importar-clausulas {empresa_id} {arquivo} {--servico=GERAL} {--auto-servico}';
    protected $description = 'Importa cláusulas de um arquivo .docx para o catálogo de cláusulas.';

    public function handle(): int
    {
        $empresaId = (int) $this->argument('empresa_id');
        $arquivo = (string) $this->argument('arquivo');
        $servicoTipoPadrao = strtoupper((string) $this->option('servico'));
        $autoServico = (bool) $this->option('auto-servico');

        if ($empresaId <= 0) {
            $this->error('Empresa inválida.');
            return self::FAILURE;
        }

        if (!is_file($arquivo)) {
            $this->error('Arquivo não encontrado: ' . $arquivo);
            return self::FAILURE;
        }

        $texto = $this->extrairTextoDocx($arquivo);
        if ($texto === '') {
            $this->error('Não foi possível extrair texto do documento.');
            return self::FAILURE;
        }

        $blocos = $this->extrairClausulas($texto);
        if (empty($blocos)) {
            $this->error('Nenhuma cláusula encontrada.');
            return self::FAILURE;
        }

        $ordem = 1;
        $inseridas = 0;
        foreach ($blocos as $bloco) {
            [$titulo, $corpo] = $this->separarTituloCorpo($bloco);

            $servicoTipo = $autoServico
                ? $this->detectarServicoTipo($titulo . ' ' . $corpo)
                : $servicoTipoPadrao;

            $slugBase = Str::slug($titulo);
            if ($slugBase === '') {
                $slugBase = 'clausula-' . $ordem;
            }
            $slug = $this->slugDisponivel($empresaId, $slugBase);

            $html = $this->montarHtml($titulo, $corpo);

            $existe = ContratoClausula::query()
                ->where('empresa_id', $empresaId)
                ->where('slug', $slug)
                ->exists();
            if ($existe) {
                $ordem++;
                continue;
            }

            ContratoClausula::create([
                'empresa_id' => $empresaId,
                'servico_tipo' => $servicoTipo,
                'slug' => $slug,
                'titulo' => $titulo,
                'ordem' => $ordem,
                'html_template' => $html,
                'ativo' => true,
                'versao' => 1,
            ]);

            $inseridas++;
            if ($autoServico) {
                $this->line(sprintf(' - %s => %s', $titulo, $servicoTipo));
            }
            $ordem++;
        }

        $this->info("Cláusulas importadas: {$inseridas}");

        return self::SUCCESS;
    }

    private function extrairTextoDocx(string $arquivo): string
    {
        $zip = new ZipArchive();
        if ($zip->open($arquivo) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return '';
        }

        $texto = strip_tags($xml);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $texto = preg_replace('/\s+/', ' ', $texto) ?? '';
        $texto = trim($texto);

        return $texto;
    }

    private function extrairClausulas(string $texto): array
    {
        preg_match_all('/CLÁUSULA[^C]+(?=CLÁUSULA|$)/u', $texto, $matches);
        $blocos = $matches[0] ?? [];

        return array_values(array_filter(array_map('trim', $blocos)));
    }

    private function separarTituloCorpo(string $bloco): array
    {
        $partes = preg_split('/\s[–-]\s/', $bloco, 2);
        $titulo = trim($partes[0] ?? 'Cláusula');
        $corpo = trim($partes[1] ?? '');

        return [$titulo, $corpo];
    }

    private function montarHtml(string $titulo, string $corpo): string
    {
        $titulo = htmlspecialchars($titulo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $corpo = htmlspecialchars($corpo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($corpo === '') {
            return '<h3>' . $titulo . '</h3>';
        }

        return '<h3>' . $titulo . '</h3><p>' . $corpo . '</p>';
    }

    private function slugDisponivel(int $empresaId, string $slugBase): string
    {
        $slug = $slugBase;
        $i = 2;

        while (ContratoClausula::query()
            ->where('empresa_id', $empresaId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $slugBase . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function detectarServicoTipo(string $texto): string
    {
        $texto = strtoupper($texto);

        $map = [
            'ESOCIAL' => ['ESOCIAL', 'E-SOCIAL', 'S-2210', 'S-2220', 'S-2240', 'CAT'],
            'LTCAT' => ['LTCAT', 'LAUDO TÉCNICO'],
            'PGR' => ['PGR', 'PROGRAMA DE GERENCIAMENTO DE RISCOS'],
            'PCMSO' => ['PCMSO', 'PROGRAMA DE CONTROLE MÉDICO', 'CONTROLE MÉDICO'],
            'ASO' => ['ASO', 'ADMISSIONAL', 'DEMISSIONAL', 'PERIÓDICO', 'MUDANÇA DE FUNÇÃO', 'RETORNO AO TRABALHO'],
            'TREINAMENTO' => ['TREINAMENTO', 'NR-'],
        ];

        foreach ($map as $servico => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($texto, $keyword)) {
                    return $servico;
                }
            }
        }

        return 'GERAL';
    }
}
