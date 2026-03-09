<?php

namespace Database\Seeders;

use App\Models\ContratoClausula;
use App\Models\Empresa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ContratoClausulasBaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('contrato_clausulas') || !Schema::hasTable('empresas')) {
            return;
        }

        $empresas = Empresa::query()->pluck('id');

        if ($empresas->isEmpty()) {
            return;
        }

        $clausulas = $this->clausulasBase();

        foreach ($empresas as $empresaId) {
            foreach ($clausulas as $clausula) {
                ContratoClausula::query()->updateOrCreate(
                    [
                        'empresa_id' => (int) $empresaId,
                        'slug' => $clausula['slug'],
                    ],
                    [
                        'servico_tipo' => $clausula['servico_tipo'],
                        'titulo' => $clausula['titulo'],
                        'ordem' => $clausula['ordem'],
                        'html_template' => $clausula['html_template'],
                        'ativo' => true,
                        'versao' => 1,
                    ]
                );
            }
        }
    }

    private function clausulasBase(): array
    {
        return [
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'objeto',
                'titulo' => 'CLÁUSULA 1 - DO OBJETO',
                'ordem' => 10,
                'html_template' => '<h3>CLÁUSULA 1 - DO OBJETO</h3><p>A CONTRATADA prestará os serviços descritos neste instrumento para a CONTRATANTE, conforme itens e condições comerciais contratadas.</p>',
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'obrigacoes-contratada',
                'titulo' => 'CLÁUSULA 2 - OBRIGAÇÕES DA CONTRATADA',
                'ordem' => 20,
                'html_template' => '<h3>CLÁUSULA 2 - OBRIGAÇÕES DA CONTRATADA</h3><p>Executar os serviços com qualidade técnica, observando a legislação aplicável e os prazos acordados entre as partes.</p>',
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'obrigacoes-contratante',
                'titulo' => 'CLÁUSULA 3 - OBRIGAÇÕES DA CONTRATANTE',
                'ordem' => 30,
                'html_template' => '<h3>CLÁUSULA 3 - OBRIGAÇÕES DA CONTRATANTE</h3><p>Fornecer informações e documentos necessários para execução dos serviços, além de cumprir os prazos e condições financeiras pactuadas.</p>',
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'vigencia',
                'titulo' => 'CLÁUSULA 4 - VIGÊNCIA',
                'ordem' => 40,
                'html_template' => '<h3>CLÁUSULA 4 - VIGÊNCIA</h3><p>Este contrato inicia em {{VIGENCIA_INICIO}} e terá vigência até {{VIGENCIA_FIM}}, podendo ser renovado por acordo entre as partes.</p>',
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'valores-pagamento',
                'titulo' => 'CLÁUSULA 5 - VALORES E PAGAMENTO',
                'ordem' => 50,
                'html_template' => '<h3>CLÁUSULA 5 - VALORES E PAGAMENTO</h3><p>Os valores serão cobrados conforme os itens do contrato e condições de faturamento definidas comercialmente entre as partes.</p>',
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'protecao-dados',
                'titulo' => 'CLÁUSULA 6 - PROTEÇÃO DE DADOS',
                'ordem' => 60,
                'html_template' => '<h3>CLÁUSULA 6 - PROTEÇÃO DE DADOS</h3><p>As partes se comprometem a tratar dados pessoais em conformidade com a LGPD e demais normas aplicáveis.</p>',
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'foro',
                'titulo' => 'CLÁUSULA 7 - FORO',
                'ordem' => 70,
                'html_template' => '<h3>CLÁUSULA 7 - FORO</h3><p>Fica eleito o foro da comarca da sede da CONTRATADA para dirimir questões oriundas deste contrato, com renúncia de qualquer outro.</p>',
            ],
        ];
    }
}
