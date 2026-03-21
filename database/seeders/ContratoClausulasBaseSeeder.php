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

        $hasHierarchy = Schema::hasColumn('contrato_clausulas', 'parent_id')
            && Schema::hasColumn('contrato_clausulas', 'ordem_local');

        $empresas = Empresa::query()->pluck('id');
        if ($empresas->isEmpty()) {
            return;
        }

        foreach ($empresas as $empresaId) {
            foreach ($this->clausulasBase() as $rootIndex => $root) {
                $rootClause = $this->upsertClause(
                    (int) $empresaId,
                    $root,
                    null,
                    $rootIndex + 1,
                    $hasHierarchy
                );

                foreach (($root['children'] ?? []) as $childIndex => $child) {
                    $this->upsertClause(
                        (int) $empresaId,
                        $child,
                        $hasHierarchy ? (int) $rootClause->id : null,
                        $childIndex + 1,
                        $hasHierarchy
                    );
                }
            }
        }
    }

    private function upsertClause(int $empresaId, array $data, ?int $parentId, int $ordemLocal, bool $hasHierarchy): ContratoClausula
    {
        $payload = [
            'servico_tipo' => (string) ($data['servico_tipo'] ?? 'GERAL'),
            'titulo' => (string) ($data['titulo'] ?? 'CLÁUSULA {{NUMERO_CLAUSULA}}'),
            'html_template' => (string) ($data['html_template'] ?? '<p>Conteúdo da cláusula.</p>'),
            'ativo' => true,
            'versao' => 2,
            'ordem' => $ordemLocal,
        ];

        if ($hasHierarchy) {
            $payload['parent_id'] = $parentId;
            $payload['ordem_local'] = $ordemLocal;
        }

        return ContratoClausula::query()->updateOrCreate(
            [
                'empresa_id' => $empresaId,
                'slug' => (string) $data['slug'],
            ],
            $payload
        );
    }

    private function clausulasBase(): array
    {
        return [
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'objeto',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - DO OBJETO',
                'html_template' => '<p>O presente contrato tem por objeto a prestação de serviços em Medicina e Segurança do Trabalho, incluindo atendimento ocupacional, exames clínicos e complementares, programas legais e serviços correlatos, conforme escopo comercial contratado.</p><p>Os serviços poderão abranger, entre outros: ASO admissional, periódico, demissional, mudança de função, retorno ao trabalho, PCMSO, PGR, eSocial e treinamentos, conforme itens efetivamente contratados.</p>',
                'children' => [
                    [
                        'servico_tipo' => 'GERAL',
                        'slug' => 'objeto-unidades-atendimento',
                        'titulo' => '{{NUMERO_CLAUSULA}} - UNIDADES E FORMA DE ATENDIMENTO',
                        'html_template' => '<p>Os atendimentos poderão ocorrer nas unidades credenciadas da CONTRATADA e/ou em regime in company, quando aplicável, mediante prévio alinhamento operacional entre as partes.</p>',
                    ],
                    [
                        'servico_tipo' => 'GERAL',
                        'slug' => 'objeto-escopo-variavel',
                        'titulo' => '{{NUMERO_CLAUSULA}} - ESCOPO CONFORME PACOTE CONTRATADO',
                        'html_template' => '<p>O escopo e os valores observam os pacotes e itens avulsos contratados, podendo incluir exames, mensageria e treinamentos, de acordo com proposta comercial vigente.</p>',
                    ],
                ],
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'vigencia-renovacao-reajuste',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - VIGÊNCIA, RENOVAÇÃO E REAJUSTE',
                'html_template' => '<p>Este contrato entra em vigor na data de assinatura e permanecerá vigente por 12 (doze) meses, com renovação automática por iguais períodos, salvo manifestação contrária por escrito com antecedência mínima de 30 (trinta) dias.</p><p>Os valores poderão ser reajustados anualmente pelo IPCA acumulado, acrescido de fator de recomposição operacional, quando previsto comercialmente.</p>',
                'children' => [
                    [
                        'servico_tipo' => 'GERAL',
                        'slug' => 'vigencia-entrega-servicos',
                        'titulo' => '{{NUMERO_CLAUSULA}} - CONTINUIDADE ATÉ ENTREGA DOS SERVIÇOS',
                        'html_template' => '<p>Sem prejuízo das condições financeiras pactuadas, a execução seguirá até a efetiva entrega dos serviços demandados dentro do período contratual.</p>',
                    ],
                ],
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'precos-pagamento-faturamento',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - PREÇOS, FATURAMENTO E PAGAMENTO',
                'html_template' => '<p>A CONTRATANTE pagará os valores previstos nas tabelas e condições comerciais acordadas entre as partes, mediante emissão de Nota Fiscal e comprovação dos serviços executados.</p><p>O faturamento observará os prazos de competência e vencimento definidos comercialmente.</p>',
                'children' => [
                    [
                        'servico_tipo' => 'GERAL',
                        'slug' => 'pagamento-multa-juros',
                        'titulo' => '{{NUMERO_CLAUSULA}} - INADIMPLEMENTO',
                        'html_template' => '<p>Em caso de atraso de pagamento, incidirão multa de 2% (dois por cento) e juros de mora de 1% (um por cento) ao mês sobre os valores em aberto, além de atualização monetária quando aplicável.</p>',
                    ],
                    [
                        'servico_tipo' => 'GERAL',
                        'slug' => 'pagamento-vencimento-antecipado',
                        'titulo' => '{{NUMERO_CLAUSULA}} - VENCIMENTO ANTECIPADO',
                        'html_template' => '<p>O não pagamento da fatura por período superior a 30 (trinta) dias poderá ensejar vencimento antecipado das demais obrigações financeiras, suspensão de atendimentos não urgentes e adoção das medidas de cobrança cabíveis.</p>',
                    ],
                ],
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'obrigacoes-contratada',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - OBRIGAÇÕES DA CONTRATADA',
                'html_template' => '<p>Executar os serviços com qualidade técnica, zelo, continuidade e observância da legislação vigente, mantendo equipe habilitada e recursos adequados para o atendimento contratual.</p>',
                'children' => [
                    [
                        'servico_tipo' => 'GERAL',
                        'slug' => 'contratada-prazos-entrega',
                        'titulo' => '{{NUMERO_CLAUSULA}} - PRAZOS E ENTREGAS',
                        'html_template' => '<p>Realizar e disponibilizar ASOs, prontuários e documentos correlatos nos prazos acordados para cada tipo de atendimento e exame, considerando dependências laboratoriais e operacionais.</p>',
                    ],
                    [
                        'servico_tipo' => 'GERAL',
                        'slug' => 'contratada-confidencialidade',
                        'titulo' => '{{NUMERO_CLAUSULA}} - SIGILO E CONFIDENCIALIDADE',
                        'html_template' => '<p>Manter confidencialidade sobre informações e documentos da CONTRATANTE e de seus colaboradores, adotando medidas técnicas e administrativas adequadas de proteção.</p>',
                    ],
                    [
                        'servico_tipo' => 'PCMSO',
                        'slug' => 'contratada-pcmso',
                        'titulo' => '{{NUMERO_CLAUSULA}} - EXECUÇÃO DE PCMSO',
                        'html_template' => '<p>Quando contratado, designar responsável técnico habilitado, elaborar/atualizar documentos do PCMSO e conduzir atividades clínicas correlatas conforme NR-07 e normas aplicáveis.</p>',
                    ],
                    [
                        'servico_tipo' => 'ESOCIAL',
                        'slug' => 'contratada-esocial',
                        'titulo' => '{{NUMERO_CLAUSULA}} - MENSAGERIA eSOCIAL',
                        'html_template' => '<p>Quando contratado, executar os serviços de mensageria eSocial (incluindo eventos S-2210, S-2220 e S-2240) com base nas informações fornecidas pela CONTRATANTE, dentro dos prazos operacionais pactuados.</p>',
                    ],
                ],
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'obrigacoes-contratante',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - OBRIGAÇÕES DA CONTRATANTE',
                'html_template' => '<p>Fornecer informações, documentos e acessos necessários para execução dos serviços, garantindo a veracidade e atualização dos dados encaminhados à CONTRATADA.</p><p>Efetuar os pagamentos nos prazos pactuados e acompanhar suas obrigações legais perante os órgãos competentes.</p>',
                'children' => [
                    [
                        'servico_tipo' => 'GERAL',
                        'slug' => 'contratante-base-documental',
                        'titulo' => '{{NUMERO_CLAUSULA}} - BASE DOCUMENTAL E TÉCNICA',
                        'html_template' => '<p>A CONTRATANTE deverá fornecer laudos, inventários, informações de função/risco e demais documentos técnicos necessários para correta emissão de programas, laudos e eventos legais.</p>',
                    ],
                    [
                        'servico_tipo' => 'ESOCIAL',
                        'slug' => 'contratante-esocial-prazos',
                        'titulo' => '{{NUMERO_CLAUSULA}} - RESPONSABILIDADES EM eSOCIAL',
                        'html_template' => '<p>A CONTRATANTE é responsável pelo envio tempestivo das informações e documentos de origem legal para processamento dos eventos, inclusive emissão de procurações e validação cadastral quando exigido.</p>',
                    ],
                ],
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'treinamentos-exames-avulsos-pacotes',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - PACOTES, EXAMES AVULSOS E TREINAMENTOS',
                'html_template' => '<p>Pacotes de exames, itens avulsos e treinamentos terão composição, prazo e precificação conforme tabela vigente da CONTRATADA e/ou proposta comercial aprovada pela CONTRATANTE.</p>',
                'children' => [
                    [
                        'servico_tipo' => 'TREINAMENTO',
                        'slug' => 'treinamentos-agendamento-validade',
                        'titulo' => '{{NUMERO_CLAUSULA}} - AGENDAMENTO E VALIDADE DE TREINAMENTOS',
                        'html_template' => '<p>Treinamentos deverão ser pré-agendados e seguirão validade técnica/legal conforme NR aplicável e conteúdo programático contratado.</p>',
                    ],
                ],
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'itens-nao-cobertos',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - ITENS NÃO COBERTOS',
                'html_template' => '<p>Não estão cobertos por este contrato, salvo contratação expressa: atendimentos não ocupacionais, terapias, internações, remoções, visitas técnicas extraordinárias, medições específicas não previstas, segunda via documental além da franquia e serviços que contrariem normas éticas ou legais.</p>',
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'rescisao-extincao',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - RESCISÃO E EXTINÇÃO',
                'html_template' => '<p>O contrato poderá ser rescindido por qualquer das partes mediante notificação prévia por escrito com antecedência mínima de 30 (trinta) dias, sem prejuízo da quitação dos serviços já executados.</p><p>Também poderá ocorrer extinção por hipótese legal que inviabilize a continuidade da prestação.</p>',
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'inexistencia-vinculo',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - INEXISTÊNCIA DE VÍNCULO',
                'html_template' => '<p>O presente instrumento não gera vínculo societário, trabalhista ou de representação entre as partes, sendo cada qual responsável por seus empregados, tributos, encargos e obrigações legais.</p>',
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'lgpd-protecao-dados',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - PROTEÇÃO DE DADOS (LGPD)',
                'html_template' => '<p>As partes declaram ciência da Lei nº 13.709/2018 (LGPD) e comprometem-se a tratar dados pessoais e sensíveis apenas para finalidades legítimas e contratuais, com medidas de segurança compatíveis, observando princípios de necessidade, confidencialidade, rastreabilidade e retenção mínima.</p><p>Havendo incidente de segurança, a parte responsável deverá comunicar a outra tempestivamente e adotar medidas para mitigação e conformidade regulatória.</p>',
                'children' => [
                    [
                        'servico_tipo' => 'GERAL',
                        'slug' => 'lgpd-retencao-eliminacao',
                        'titulo' => '{{NUMERO_CLAUSULA}} - RETENÇÃO, ELIMINAÇÃO E DEVOLUÇÃO',
                        'html_template' => '<p>Ao término da relação contratual, os dados pessoais deverão ser devolvidos, eliminados ou anonimizados, ressalvadas hipóteses legais de retenção para cumprimento de obrigações regulatórias e exercício regular de direitos.</p>',
                    ],
                ],
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'disposicoes-gerais',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - DISPOSIÇÕES GERAIS',
                'html_template' => '<p>Qualquer tolerância quanto ao descumprimento contratual não importará novação. Alterações deste instrumento somente terão validade por termo aditivo escrito e assinado pelas partes.</p><p>As comunicações formais serão preferencialmente realizadas pelos canais eletrônicos definidos entre as partes.</p>',
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'foro',
                'titulo' => 'CLÁUSULA {{NUMERO_CLAUSULA}} - FORO',
                'html_template' => '<p>Fica eleito o foro da comarca definida comercialmente no instrumento de contratação, com renúncia a qualquer outro, por mais privilegiado que seja, para dirimir questões oriundas deste contrato.</p>',
            ],
        ];
    }
}
