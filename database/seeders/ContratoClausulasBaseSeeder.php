<?php

namespace Database\Seeders;

use App\Models\ContratoClausula;
use Illuminate\Database\Seeder;

class ContratoClausulasBaseSeeder extends Seeder
{
    public function run(): void
    {
        $empresaId = (int) (config('app.contrato_empresa_id') ?? 0);
        if ($empresaId <= 0) {
            return;
        }

        $slugsRemover = [
            'precos-cabecalho',
            'precos-aso',
            'precos-pcmso',
            'precos-pgr',
            'precos-exames-avulsos',
            'precos-esocial',
            'precos-treinamentos',
        ];

        ContratoClausula::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('slug', $slugsRemover)
            ->delete();

        $clausulas = [
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'objeto-introducao',
                'titulo' => 'CLÁUSULA PRIMEIRA – DO OBJETO',
                'ordem' => 9,
                'html_template' => $this->htmlObjetoIntroducao(),
            ],
            [
                'servico_tipo' => 'ASO',
                'slug' => 'objeto-aso',
                'titulo' => 'Do Objeto (ASO)',
                'ordem' => 10,
                'html_template' => $this->htmlObjetoAso(),
            ],
            [
                'servico_tipo' => 'PCMSO',
                'slug' => 'objeto-pcmso',
                'titulo' => 'Do Objeto (PCMSO)',
                'ordem' => 12,
                'html_template' => $this->htmlObjetoPcmso(),
            ],
            [
                'servico_tipo' => 'ESOCIAL',
                'slug' => 'objeto-esocial',
                'titulo' => 'Do Objeto (eSocial)',
                'ordem' => 14,
                'html_template' => $this->htmlObjetoEsocial(),
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'obrigacoes-contratada',
                'titulo' => 'CLÁUSULA 2 – OBRIGAÇÕES DA CONTRATADA',
                'ordem' => 20,
                'html_template' => $this->htmlObrigacoesContratada(),
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'obrigacoes-contratante',
                'titulo' => 'CLÁUSULA 3 – OBRIGAÇÕES DA CONTRATANTE',
                'ordem' => 30,
                'html_template' => $this->htmlObrigacoesContratante(),
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'vigencia-validade',
                'titulo' => 'CLÁUSULA 5 – VALIDADE, VIGÊNCIA E PRAZO',
                'ordem' => 50,
                'html_template' => $this->htmlVigencia(),
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'disposicoes-gerais',
                'titulo' => 'CLÁUSULA 7 – DISPOSIÇÕES GERAIS',
                'ordem' => 70,
                'html_template' => $this->htmlDisposicoesGerais(),
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'lgpd',
                'titulo' => 'CLÁUSULA 8 - DA PROTEÇÃO DE DADOS',
                'ordem' => 80,
                'html_template' => $this->htmlLgpd(),
            ],
            [
                'servico_tipo' => 'GERAL',
                'slug' => 'foro',
                'titulo' => 'CLÁUSULA 9 – DISPOSIÇÃO FINAL',
                'ordem' => 90,
                'html_template' => $this->htmlForo(),
            ],
        ];

        foreach ($clausulas as $clausula) {
            $registro = ContratoClausula::query()->firstOrNew([
                'empresa_id' => $empresaId,
                'slug' => $clausula['slug'],
            ]);

            if (! $registro->exists) {
                $registro->ativo = $clausula['ativo'] ?? true;
                $registro->versao = 1;
            }

            $registro->fill([
                'servico_tipo' => $clausula['servico_tipo'],
                'titulo' => $clausula['titulo'],
                'ordem' => $clausula['ordem'],
                'html_template' => $clausula['html_template'],
            ]);
            if (array_key_exists('ativo', $clausula)) {
                $registro->ativo = (bool) $clausula['ativo'];
            }

            $registro->save();
        }
    }

    private function htmlObjetoAso(): string
    {
        return <<<HTML
<p>Atendimento clínico especializado de Medicina Ocupacional para atendimento e realização de exames Admissionais, Demissionais, Periódicos, Mudança de Risco Ocupacional, Retorno ao Trabalho e exames complementares que se fizerem indicados pelo PCMSO da CONTRATANTE.</p>
HTML;
    }

    private function htmlObjetoPcmso(): string
    {
        return <<<HTML
<p>Elaboração e coordenação, do Programa de Controle Médico de Saúde Ocupacional PCMSO relativo aos empregados da CONTRATANTE, com base NA LEGISLAÇÃO VIGENTE;</p>
HTML;
    }

    private function htmlObjetoEsocial(): string
    {
        return <<<HTML
<p>Prestação de serviços de “Mensageria” sendo o envio das obrigatoriedades constantes no sistema informatizado do Governo Federal – E-Social, referente aos eventos:</p>
<p>S-2210 - Comunicação de Acidente de Trabalho (CAT)</p>
<p>S-2220 – Monitoramento da Saúde do Trabalhador;</p>
<p>S-2240 – Condições Ambientais do Trabalho – Agentes Nocivos.</p>
HTML;
    }

    private function htmlObjetoIntroducao(): string
    {
        return <<<HTML
<h3>CLÁUSULA PRIMEIRA – DO OBJETO</h3>
<p>Prestação de Serviços de Atendimento Específico em Saúde e Medicina Ocupacional e Elaboração de Programas relacionados à Gestão Saúde e Segurança do Trabalho, para as funções determinadas pela CONTRATANTE:</p>
HTML;
    }

    private function htmlObrigacoesContratada(): string
    {
        return <<<HTML
<h3>CLÁUSULA 2 – OBRIGAÇÕES DA CONTRATADA</h3>
<p>São obrigações da CONTRATADA:</p>
<p>No que tange ao Programa de Controle Médico de Saúde Ocupacional – PCMSO</p>
<p>Designar um Coordenador Médico devidamente habilitado para proceder a Elaboração do PCMSO;</p>
<p>Elaborar o Relatório Inicial e Anual do Programa de Controle Médico de Saúde Ocupacional, combase no que estabelece a NR 07;</p>
<p>Renovação do Programa de Controle Médico de Saúde Ocupacional - PCMSO</p>
<p>Realizar os Exames Clínicos contratados, com recursos próprios ou de terceiros em local indicado pela CONTRATADA: Admissionais; Demissionais; Periódicos; Mudança de Risco Ocupacional; Retorno ao Trabalho. Os equipamentos e os profissionais são de responsabilidade da CONTRATADA.</p>
<p>Se a CONTRATANTE optar por realizar os referidos exames diretamente com qualquer outra medicina do trabalho que não for a CONTRATADA, assumirá integral responsabilidade quanto à sua execução, sendo que somente após sua apresentação à CONTRATADA será estabelecida conclusivamentea aptidão ou inaptidão do empregado;</p>
<p>Desde que a CONTRATANTE tenha mais que 40 (quarenta) empregados para exames clínicos no mesmo dia, turno, local e disponha de ambiente adequado para tal, o atendimento poderá a critério da CONTRATANTE ser realizado nas instalações dessa última CONTRATANTE.</p>
<p>Para a realização de exames nas instalações da CONTRATANTE, esta deverá fornecer local indevassável, higienizado e mobiliado conforme  orientação  prévia  da  CONTRATADA. FONO: A CONTRATANTE deverá disponibilizar de uma mesa e duas cadeiras. A CONTRATADA levará a cabine móvel de audiometria, audiômetro e as fichas de audiometrias a serem realizadas. MÉDICO: A CONTRATANTE deverá disponibilizar de uma mesa e sete cadeiras. A CONTRATADA levará os ASOS previamente preenchidos e ficha clínica para o colaborador preencher juntamente com 5 pranchetas.</p>
<p>A CONTRATANTE deverá zelar para que seus empregados compareçam no local, data e horários estabelecidos para a realização dos exames, rigorosamente dentro do cronograma estabelecido com a CONTRATADA, não podendo a CONTRATANTE ser responsabilizada em caso da ausência de seu empregado.</p>
<p>Exames marcados nas instalações da CONTRATANTE e que não ocorram por responsabilidade desta e desde que seja comprovada a culpa, somente serão remarcados para execução em local indicado pela CONTRATADA, CONFORME TABELA DE VALORES prevista na cláusula 4 do presente instrumento.</p>
<p>Elaborar Prontuário Individual e fornecer Atestado de Saúde Ocupacional – ASO – em três vias, liberadas após o término dos exames e assim sendo entregues: 1ª via para a CONTRATANTE arquivar no prontuário do funcionário, 2ª via será entregue ao empregado e a 3ª via ficará no arquivo da CONTRATADA.</p>
<p>A convocação dos colaboradores para realização dos exames Periódicos</p>
<p>No que tange ao Programa de Gerenciamento de Riscos.</p>
<p>As obrigações da CONTRATADA restringem-se única e exclusivamente às condiçõescontempladas na documentação encaminhada pela CONTRATANTE, quer nas condições ambientais, temporais e funcionais.</p>
HTML;
    }

    private function htmlObrigacoesContratante(): string
    {
        return <<<HTML
<h3>CLÁUSULA 3 – OBRIGAÇÕES DA CONTRATANTE</h3>
<p>São obrigações da CONTRATANTE:</p>
<p>Fornecer o PGR para a elaboração do PCMSO para o cadastramaneto da empresa que vier fazer os exames em nossa unidade.</p>
<p>Fornecer à CONTRATADA, dados para o desenvolvimento do PCMSO bem como permitir livre acesso às suas dependências desde que previamente agendado para análise cabível;</p>
<p>Fornecer a CONTRATADA as cópias dos últimos Laudos Ambientais de Agentes Físicos, Químicos, Biológicos e Ergonômicos, Individuais, Setoriais ou Gerais, ou outros Laudos complementares, Certificados de Treinamento de cunho prevencionista, relação dos Equipamentos de Proteção Coletiva e Proteção Individual e outras implementações anteriores neste sentido, a fim de dar melhor embasamento à atividade técnica a ser desenvolvida, que tenham sido realizados anteriormente, bem como cópia do Documento Base do Programa de Gerenciamento de Riscos – P.G.R desde que o mesmo não tenha sido realizado, mediante outros Contratos, pela CONTRATADA;</p>
<p>Fica a cargo da CONTRATANTE a implementação dos planos de controle e ação previstos no PGR.</p>
<p>Arquivar, após as implementações cabíveis, os Relatórios, Laudos Complementares, Ordens de Serviço, Normas e Procedimentos de Segurança, Documentação referente à treinamento etc. do Programa de Gerenciamento de Riscos de forma que fiquem à disposição das autoridades competentes por um período mínimo de 20 (vinte) anos.</p>
<p>Comunicar formalmente à CONTRATADA todas as alterações ambientais ou funcionais queocorrerem em suas instalações.</p>
<p>A CONTRATANTE se responsabiliza pelas informações que prestar ou deixar de prestar, que venham a interferir nas avaliações e resultados dos trabalhos ora contratados, quer na quantificação e ou demais especificações de funções/setores e os respectivos Agentes Ambientais (Físicos, Químicos e Biológicos, Ergonômicos e de Acidentes), em consonância com os Laudos e demais Documentos pertinentes à questão.</p>
<p>A CONTRATANTE se responsabiliza pela realização e ou custeio dos demais Laudos Ambientais complementares, não cobertos por este Contrato e que devam, eventualmente, ser realizados a expensas da CONTRATANTE, sem os quais não haverá definição quanto à caracterização de Insalubridade, Nocividade e até mesmo Nível de Ação (para ruído e agentes químicos quantificáveis) e seus reflexos no monitoramento biológico de seus empregados.</p>
<p>No que tange ao eSocial, cabe a CONTRATANTE a responsabilidade para realização das obrigações nos prazos cabíveis para o envio das informações, tais como:</p>
<p>Realização dos ASOS dentro dos prazos estabelecidos na legislação vigente (evento S-2220);</p>
<p>Apresentação do LTCAT – Laudo Técnico das Condições Ambientais de Trabalho (evento S-2240), contemplando todas as funções constantes na CONTRATANTE no momento da prestação do referido serviço;</p>
<p>Perfil Profissiográfico Previdenciário (PPP); caberá a CONTRATANTE fornecer todo histórico laboral do colaborador até a data do início deste contrato:</p>
<p>Comunicação de Acidente de Trabalho (evento S-2210)</p>
<p>CAT serve para comunicar e constatar a ocorrência de um acidente de trabalho ou de trajeto e de uma doença ocupacional ou profissional.</p>
<p>A comunicação do acidente de trabalho deve ser registrada até 24 horas do momento da ocorrência e em caso de morte, de imediato.</p>
<p>Para o serviço de MENSAGERIA do eSocial (S-2210, S-2220 e S-2240), é necessário que a CONTRATANTE emita a procuração para a CONTRATADA com prazo mínimo de vigência de 12 MÊSES; podendo este ser revogado e isentando a CONTRATADA de MULTAS pela lei vingente caso venha ocorrer atraso nos PAGAMENTOS para a CONTRATADA ou seu envio com atraso, se submentendo a CONTRATANTE as multas e penalidades previstas em lei.</p>
<p>Para Realização do serviço de Mansageria será necessário o envio dos dados através de planilha modelo disponibilizada pela CONTRATADA obedecendo fielmente o modelo disposto, onde qualqer alteração fora do padrão implicará no atraso do processamento dos dados conforme os prazos determinados abaixo;</p>
<p>Emissão da Procuração: Até o 5º dia útil de cada mês e no caso de Renovação segue o mesmo prazo.</p>
<p>EnviodaPlanilhamodeloatéo5ºdiaútildecadamês</p>
<p>Atualizações Cadastrais e Complementos de informações até o dia 10º dia de cada mês.</p>
<p>Os documentos mencionados acima NÃO CONTEMPLAM nenhum tipo de Medição e Visita Técnica.</p>
<p>O faturamento dos SERVIÇOS PACOTE e PONTUAIS que forem realizados será efetuado com os faturados mensalmente. Com vencimento todo dia 10 do mês subsequente.</p>
<p>Ocorrendo atraso no pagamento, haverá incidência de juros de 1% (um por cento ao mês) e multa de 2% (dois por cento) sobre o valor total da NF.</p>
<p>O NÃO PAGAMENTO DA NOTA, EM ATÉ 30 DIAS DE SEU VENCIMENTO, IMPLICARÁ NO VENCIMENTO AANTECIPADO DAS DEMAIS.</p>
HTML;
    }

    private function htmlPrecosCabecalho(): string
    {
        return <<<HTML
<h3>CLÁUSULA 4 – PREÇOS E CONDIÇÕES DE PAGAMENTO</h3>
HTML;
    }

    private function htmlPrecosAso(): string
    {
        return <<<HTML
<p>ASO ADMISSIONAL / PERIODICO</p>
<p>QUANTIDADE</p>
<p>VALOR R$</p>
<p>ASO – Trabalho em Altura ou espaço confinado: Exame clínico, Acuidade Visual, Audiometria, ECG, EEG, Espirometria, Glicemia de Jejum, Hemograma, Raio X Tórax e Avaliação</p>
<p>Psicossocial.</p>
<p>01</p>
<p>R$ 240,00</p>
<p>Prazo de entrega: até 2 dias </p>
HTML;
    }

    private function htmlPrecosPcmso(): string
    {
        return <<<HTML
<p>ASO ADMISSIONAL / PERIODICO</p>
<p>QUANTIDADE</p>
<p>VALOR R$</p>
<p>PCMSO – PROGRAMA DE CONSTROLE MÉDICO DE SAUDE OCUPACIOANL</p>
<p>1</p>
<p>R$ 350,00</p>
HTML;
    }

    private function htmlPrecosPgr(): string
    {
        return <<<HTML
<p>PGR – PROGRMA DE CONTROLE MEDICO DE SAUDE OCUPACIONAL</p>
<p>1</p>
<p>R$ 650,00</p>
<p>Prazo de entrga: até 2 dias úteis</p>
HTML;
    }

    private function htmlPrecosExamesAvulsos(): string
    {
        return <<<HTML
<p>TABELA DE EXAMES AVULSOS</p>
<p>EXAMES AVULSOS</p>
<p>VALOR R$ UNITÁRIO</p>
<p>EXAMES AVULSOS</p>
<p>VALOR R$ UNITÁRIO</p>
<p>Exame Clínico – ASO</p>
<p>R$ 50,00</p>
<p>Glicemia de Jejum</p>
<p>R$ 9,20</p>
<p>Ácido Hipúrico</p>
<p>R$ 18,20</p>
<p>Hemograma Completo</p>
<p>R$ 11,20</p>
<p>Ácido Metil Hipúrico</p>
<p>R$ 18,20</p>
<p>Micológico de Unha</p>
<p>R$ 9,20</p>
<p>Ácido Transmucônico</p>
<p>R$ 37,30</p>
<p>Niquél</p>
<p>R$ 45,20</p>
<p>Acuidade Visual</p>
<p>R$ 13,00</p>
<p>PPF</p>
<p>R$ 10,40</p>
<p>Audiometria Ocupacional</p>
<p>R$ 17,00</p>
<p>Raio-X Coluna Lombar</p>
<p>R$ 63,00</p>
<p>Avaliação Oftalmológica</p>
<p>R$ 120,00</p>
<p>Raio-X Coluna Lombo SACRA</p>
<p>R$ 63,00</p>
<p>Avaliação Psicossocial</p>
<p>R$ 30,00</p>
<p>Raio-X da Perna</p>
<p>R$ 63,00</p>
<p>Chumbo Sangue</p>
<p>R$ 24,00</p>
<p>Raio-X do Braço</p>
<p>R$ 63,00</p>
<p>Coprocultura</p>
<p>R$ 15,20</p>
<p>Raio-X do Tórax PA</p>
<p>R$ 63,00</p>
<p>Creatinina</p>
<p>R$ 23,00</p>
<p>TGO</p>
<p>R$ 10,20</p>
<p>Eletrocardiograma (ECG)</p>
<p>R$ 20,30</p>
<p>TGP</p>
<p>R$ 10,20</p>
<p>Eletroencefalograma (EEG)</p>
<p>R$ 20,30</p>
<p>Ureia Creatinina</p>
<p>R$ 22,00</p>
<p>Espirometria</p>
<p>R$ 18,00</p>
<p>Urina 1</p>
<p>R$ 11,00</p>
<p>Gama GT</p>
<p>R$ 14,00</p>
<p>VDRL</p>
<p>R$ 12,60</p>
HTML;
    }

    private function htmlPrecosEsocial(): string
    {
        return <<<HTML
<p>E-SOCIAL</p>
<p>QUANTIDADE</p>
<p>MENSALIDADE</p>
<p>Envio das Informações ao E-social:</p>
<p>S-2210- Cat – comunicado de acidente do trabalho</p>
<p>S-2220 – ASO – Atestado de saude ocupacional</p>
<p>S-2240 – LTCAT Laudo tecnico das condições de ambiente do trabalho (caso tenha)</p>
<p>Até 50 colaboradores</p>
<p>R$ 200,00</p>
HTML;
    }

    private function htmlPrecosTreinamentos(): string
    {
        return <<<HTML
<p>TREINAMENTOS </p>
<p>QTD</p>
<p>VALOR</p>
<p>NR-35 – Trabalho em altura  - validade de 2 anos </p>
<p>NR-18 – Integração </p>
<p>NR-01 – Ordem de serviço</p>
<p>1</p>
<p>R$ 180,00</p>
<p>NR-12 – Máquinas – validade de 2 anos</p>
<p>1</p>
<p>R$ 180,00</p>
<p>NR-35 – Trabalho em altura – validade de 2 anos</p>
<p>1</p>
<p>R$ 120,00</p>
<p>NR-06 – Ficha de EPI – validade de 2 anos</p>
<p>1</p>
<p>R$ 60,00</p>
<p>NR-33 – Espaço confinado – validade de 1 ano</p>
<p>1</p>
<p>R$ 180,00</p>
<p>NR-05 - CIPA DESIGNADA</p>
<p>1</p>
<p>R$ 350,00</p>
<p>PACOTE POR FUNCIONARIO</p>
<p>NR-35, NR-18, NR-01, NR-12, NR-06, NR-33</p>
<p>1</p>
<p>R$ 450,00</p>
<p>NR-10 – Eletricidade  - validade de 2 anos </p>
<p>1</p>
<p>R$ 300,00</p>
<p>PRAZO DE ENTREGA: 1 DIA</p>
HTML;
    }

    private function htmlVigencia(): string
    {
        return <<<HTML
<h3>CLÁUSULA 5 – VALIDADE, VIGÊNCIA E PRAZO</h3>
<p>– Este contrato terá início de vigência a partir de 09/09/2025, com validade de 1 (um) ano, sendo renovado automaticamente por iguais períodos, caso qualquer uma das partes não se manifeste em contrário, por escrito, com antecedência mínima de 30 (trinta) dias do término de cada período.</p>
<p>Fica estabelecido que, a cada renovação anual, os valores contratados poderão ser reajustados conforme o índice IPCA acumulado dos últimos 12 meses, ou outro índice oficial que venha a substituí-lo, visando a manutenção do equilíbrio econômico-financeiro do contrato.</p>
<p>– Sem prejuízo às condições de pagamento pactuadas, o contrato vigorará até a efetiva entrega dos serviços contratados.</p>
<p>A CONTRATADA todo final de mês enviará por e-mail estabelecido pela CONTRATANTE o Demonstrativo dos Serviços Prestados, Nota Fiscal e Boleto. Nosso departamento Financeiro poderá ser acionado nos e-mails:</p>
<p>financeiro@athuar.com.br,financeiro1@athuar.com.br;financeiro2@athuar.com.br; faturamento@athuar.com.br ou no telefone: (11) 9 7259-6061 (faturamento) e (11) 9 9277-929048.</p>
HTML;
    }

    private function htmlDisposicoesGerais(): string
    {
        return <<<HTML
<h3>CLÁUSULA 7 – DISPOSIÇÕES GERAIS</h3>
<p>– Não estão cobertas por este contrato, quaisquer atividades a serem realizadas posteriormente, tais como: implementação de ações corretivas, elaboração de Laudos Ambientais e Laudos Complementares, treinamentos, fornecimento e controle de equipamentos de proteção coletiva ou individual e outras, as quais são de inteira e exclusiva responsabilidade da CONTRATANTE.</p>
<p>A contratante concorda em arcar com todas as despesas relacionadas ao frete e transporte dos prontuários e do ASO A contratante se compromete a escolher um método de transporte adequado e a providenciar o pagamento integral das taxas e encargos associados ao envio dos documentos. A contratante será responsável por assegurar que os documentos sejam entregues de forma segura e dentro dos prazos estabelecidos. A contratante também assume a responsabilidade por qualquer perda, dano ou atraso que possa ocorrer durante o transporte dos documentos.</p>
<p>Quaisquer alterações ambientais ou na estrutura funcional da CONTRATANTE, posteriores ao levantamento estabelecido na Cláusula 7.1 e que afetem os Documentos Base do PCMSO – NR-7 em pauta, serão de exclusiva responsabilidade da CONTRATANTE.</p>
<p>Nenhuma das partes responderá solidariamente por quaisquer indenizações, multas ou encargos exigidos por empregados da outra Parte, por terceiros ou por órgãos governamentais ou judiciais, em decorrência dos serviços cobertos por este Contrato.</p>
<p>Caso uma das partes venha a responder por quaisquer condições acima, a outra parte se obriga a proceder com a restituição de todos os valores até então gastos pela outra Parte, bem como proceder com a restituição de valores que possam ser pagos pela outra Parte em momento posterior.</p>
<p>A CONTRATADA se exime de toda responsabilidade por qualquer indenização, multa ou outro encargo exigível por empregados da CONTRATANTE ou pelos órgãos Governamentais, cuja responsabilidade é exclusivamente da CONTRATANTE. Poderá a CONTRATADA, na hipótese de litígio, auxiliar a empresa CONTRATANTE, por meio de apresentação de comprovantes de realização dos exames médicos e outros documentos que se fizerem necessários e estiverem ao seu alcance.</p>
<p>Não estão inclusas as implementações das ações corretivas preconizadas nos trabalhos ora contratados, nem a elaboração de outros Laudos, audiodosimetrias, excluindo-se, também, treinamentos de quaisquer naturezas, fornecimento e controle de E.P.I.’s e E.P.C.’s, os quais são de inteira e exclusiva responsabilidade da CONTRATANTE.</p>
<p>As avaliações quantitativas a serem realizadas pela CONTRATADA limitam-se àqueles referentes à Ruído, Calor e luminosidade. Todos os demais agentes ambientais passíveis de avaliações quantitativas correrão a expensas da CONTRATANTE.</p>
<p>Não estão cobertos por este Contrato:<br>
Acidentes de trabalho, exceto o exame de retorno ao trabalho;<br>
Atendimento Médico por ocorrência que não caracterize atividade profissional;<br>
Tratamento Terapêutico de quaisquer naturezas;<br>
Internações, procedimentos, consultas domiciliares e remoções;<br>
Quaisquer atividades que contrariem a ética.</p>
<p>As comunicações a serem feitas entre as Partes deverão ser documentadas através do e-mail ou ainda através de correspondência a ser enviada para o endereço constante do início deste Contrato, especificando o tipo da movimentação ocorrida, local, data e identificação do emitente da correspondência.</p>
<p>Na eventualidade da CONTRATANTE desejar realizar exames espontâneos, assim entendidos como aqueles não obrigatórios (à semelhança daqueles para comprovação de faltas, efetivação de estagiários, mudança de setor sem mudança de risco) tais exames serão cobrados pela CONTRATADA.</p>
<p>A CONTRATANTE desde já fica ciente que diante da necessidade de fornecimento de segundas vias documentos, este serão cobrados ao preço de:</p>
<p>Laudos - R$ 100,00 (cem reais) mais custos de correio ou transporte;</p>
<p>ASOS – R$ 10,00 (dez reais) mais custos de correio ou transporte se houver.</p>
<p>A CONTRATANTE fica igualmente ciente que todas as despesas decorrentes de entrega rápida de documentos (no mesmo dia) ser-lhe-ão repassadas pela CONTRATADA, desde que previamente e expressamente autorizado pela CONTRATANTE. Esta condição de repasse do valor será feito se o setor responsável pela elaboração das documentações esteja sobrecaregado de tarefas.</p>
<p>A CONTRATANTE fica ciente que pagará à CONTRATADA R$ 350,00 (trezentos e cinquenta reais) a título de Taxa de Atendimento Urgente em casos de Fiscalizações, Inspeções ou Auditorias Oficiais e não oficiais ou ainda quaisquer outras situações a que a CONTRATADA não tiver dado causa. Desde que a CONTRATANTE estejam com as documentações vencidas.</p>
<p>A CONTRATANTE avisará previamente a CONTRADADA toda cobrança de taxa que será feita de acordo com a entrega de documentos urgentes conforme a clausula 7.11 e 7.12</p>
<p>Não estão cobertas por este contrato, quaisquer atividades posteriores à elaboração e entrega do Laudo Ambiental de Ruído, objeto deste contrato;</p>
<p>Quaisquer alterações ambientais ou na estrutura funcional da CONTRATANTE, posteriores ao levantamento estabelecido na Cláusula 1 e que afetem o Laudo em questão, serão de exclusiva responsabilidade da CONTRATANTE;</p>
<p>Qualquer liberalidade das partes na exigência do cumprimento dos termos deste Contrato não implicará em novação, sendo que qualquer alteração do mesmo se fará através de TERMO ADITIVO.</p>
<p>Este contrato é totalmente por satisfação, ou seja, a CONTRATADA não irá cobrar multa em caso de encerramento por parte da CONTRATANTE;</p>
HTML;
    }

    private function htmlLgpd(): string
    {
        return <<<HTML
<h3>CLÁUSULA 8 - DA PROTEÇÃO DE DADOS</h3>
<p>Visando estabelecer regras de proteção de dados (pessoais e/ou sensíveis) ao presente Contrato, as partes declaram-se cientes dos direitos, obrigações e penalidades aplicáveis constantes da Lei Geral de Proteção de Dados (Lei 13.709/2018) e obrigam-se a adotar todas as medidas razoáveis para garantir a correta utilização dos Dados Protegidos na extensão autorizada na referida norma e que cumprirão a legislação e todas as demais leis, normas e regulamentos aplicáveis, assim como cumprirão suas respectivas atualizações e atenderão os padrões aplicáveis em seu segmento em relação ao tratamento de dados pessoais, especialmente aos dados pessoais disponibilizados de uma parte a outra, garantindo que:</p>
<p>Possuem todos os direitos, consentimentos e/ou autorizações necessários exigidos pela LGPD, e demais leis aplicáveis, para divulgar, compartilhar e/ou autorizar o tratamento dos dados pessoais para o cumprimento de suas obrigações contratuais e/ou legais;<br>
Não conservarão dados pessoais que excedam as finalidades previstas no instrumento, e seus eventuais anexos;<br>
Informarão e instruirão os seus empregados, prestadores de serviços e/ou terceiros sobre o tratamento dos dados pessoais, observando todas as condições deste instrumento, inclusive na hipótese de os titulares de dados terem acesso direto a qualquer sistema (online ou não) para preenchimento de informações que possam conter os dados pessoais, garantindo a privacidade e confidencialidade dos dados pessoais, e mantendo um controle rigoroso sobre o acesso aos dados pessoais;<br>
Não fornecerão ou compartilharão, em qualquer hipótese, dados pessoais sensíveis de seus empregados, prestadores de serviços e/ou terceiros, salvo se expressamente Página 11 de 13 solicitado por uma parte à outra, caso o objeto do instrumento justifique o recebimento de tais dados, os quais serão utilizados estritamente para estes fins;<br>
Nenhuma das partes autoriza a comercialização de quaisquer informações pessoais;<br>
Informarão uma Parte à outra sobre qualquer incidente de segurança, relacionado ao presente instrumento, por quaisquer meios, do respectivo incidente;<br>
Se for o caso, quando deter dados pessoais, irão alterar, corrigir, apagar, dar acesso, anonimizar ou realizar a portabilidade para terceiros de dados pessoais, mediante solicitação da Parte requerente;<br>
Excluirão, de forma irreversível, os dados pessoais retidos em seus registros, mediante solicitação da outra parte ou dos titulares dos dados, a qualquer momento, salvo para cumprimento de obrigação determinado por lei ou ordem judicial;<br>
Manterão e utilizarão medidas de segurança administrativas, técnicas e físicas apropriadas e suficientes para proteger a confidencialidade e integridade de todos os dados pessoais mantidos ou consultados/transmitidos eletronicamente, para garantir a proteção desses dados contra acesso não autorizado, destruição, uso, modificação, divulgação ou perda acidental ou indevida;<br>
Colaborarão com a outra Parte, mediante solicitação deste, no cumprimento das obrigações de responder a solicitações e reivindicações de pessoa e/ou autoridade governamental, a respeito de Dados Pessoais;<br>
Ao término da vigência do presente instrumento cessará todo e qualquer tratamento dos dados, com a devolução de quaisquer dados pessoais à outra Parte, ou destruição deles e de todas as cópias existentes, exceto se necessário para o cumprimento de obrigação contratual, legal ou regulatória e para o exercício do regular de direito em processo judicial, administrativo ou arbitral.<br>
Orientarão seus empregados, prestadores de serviços, terceiros, parceiros e membros da equipe técnica que venham ter acesso aos dados durante a execução contratual para que cumpram as disposições legais aplicáveis em matéria de proteção de dados pessoais, nunca cedendo ou divulgando tais dados a terceiros, salvo se expressamente autorizado pelo titular, por força de lei ou determinação judicial;<br>
m)As Partes não poderão subcontratar nem delegar o Tratamento dos Dados Pessoais sem a previa e expressa concordância, por escrito da outra parte, mas podem preservar e conservar os dados por si ou por empresa contratada especialmente para este fim durante a vigência do presente contrato e pelo prazo necessário para cumprimento alínea “k”;<br>
As Partes declaram ciência de que os dados fornecidos, uma vez anonimizados, não são considerados DADOS PESSOAIS, como estabelece o artigo 12 da Lei Geral de Proteção de Dados - Lei nº 13.709/2018) 2018); Página 12 de 13 o) As Partes se comprometem a tratar qualquer Dado Pessoal obtido apenas para finalidades  específicas  e  legítimas,  devendo  ser  armazenados  apenas  pelo  tempo  necessário.</p>
HTML;
    }

    private function htmlForo(): string
    {
        return <<<HTML
<h3>CLÁUSULA 9 – DISPOSIÇÃO FINAL</h3>
<p>9.1 Fica eleito o Foro da Comarca do Município de São Caetano do Sul – Estado de São Paulo, em detrimento de qualquer outro, por mais privilegiado que seja para dirimir quaisquer dúvidas oriundas deste Contrato.</p>
<p>E, por estarem justas e contratadas, assinam o presente contrato em duas vias de igual teor, na presença das testemunhas.</p>
<p>São Paulo, 09 de Setembro de 2025</p>
<p>CONTRATADA                                                                                                        CONTRATANTE</p>
<p>ATHUAR MEDICINA E SEGURANÇA DO TRABALHO                                                        H2L ENGENHARIA LTDA</p>
<p>Testemunha1:Testemunha2:</p>
<p>Nome:Nome: </p>
<p>RG:RG:</p>
<p>Assinatura TestemunhaAssinatura Testemunha</p>
HTML;
    }
}
