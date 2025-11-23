<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Funcao;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FuncaoSeeder extends Seeder
{
    public function run(): void
    {
        // lista base de funções
        $funcoesBase = [
            [
                'nome'      => 'Operador de Produção',
                'cbo'       => '8211-15',
                'descricao' => 'Atua na linha de produção realizando operações diversas.',
            ],
            [
                'nome'      => 'Técnico de Segurança do Trabalho',
                'cbo'       => '3516-05',
                'descricao' => 'Responsável pela implementação das normas de segurança.',
            ],
            [
                'nome'      => 'Engenheiro de Segurança do Trabalho',
                'cbo'       => '2149-40',
                'descricao' => 'Coordena ações de segurança e saúde ocupacional.',
            ],
            [
                'nome'      => 'Auxiliar Administrativo',
                'cbo'       => '4110-10',
                'descricao' => 'Atua em rotinas administrativas gerais.',
            ],
            [
                'nome'      => 'Analista de RH',
                'cbo'       => '2524-05',
                'descricao' => 'Responsável por processos de recrutamento, seleção e folha.',
            ],
            [
                'nome'      => 'Motorista',
                'cbo'       => '7823-10',
                'descricao' => 'Conduz veículos leves para transporte de pessoas ou cargas.',
            ],
            [
                'nome'      => 'Motorista de Caminhão',
                'cbo'       => '7825-10',
                'descricao' => 'Conduz caminhões para transporte rodoviário de cargas.',
            ],
            [
                'nome'      => 'Operador de Empilhadeira',
                'cbo'       => '7822-05',
                'descricao' => 'Opera empilhadeira em áreas de armazenagem.',
            ],
            [
                'nome'      => 'Encarregado de Obra',
                'cbo'       => '9102-05',
                'descricao' => 'Supervisiona equipes de construção civil.',
            ],
            [
                'nome'      => 'Mestre de Obras',
                'cbo'       => '7102-05',
                'descricao' => 'Coordena e orienta execução dos serviços de obra.',
            ],
            [
                'nome'      => 'Pedreiro',
                'cbo'       => '7152-10',
                'descricao' => 'Executa alvenarias, rebocos e acabamentos.',
            ],
            [
                'nome'      => 'Servente de Obra',
                'cbo'       => '7170-20',
                'descricao' => 'Auxilia nas atividades gerais da obra.',
            ],
            [
                'nome'      => 'Eletricista Industrial',
                'cbo'       => '7313-10',
                'descricao' => 'Executa manutenção elétrica em instalações industriais.',
            ],
            [
                'nome'      => 'Eletricista de Manutenção',
                'cbo'       => '7311-15',
                'descricao' => 'Realiza manutenção preventiva e corretiva em sistemas elétricos.',
            ],
            [
                'nome'      => 'Mecânico Industrial',
                'cbo'       => '7311-05',
                'descricao' => 'Realiza manutenção em máquinas e equipamentos industriais.',
            ],
            [
                'nome'      => 'Soldador',
                'cbo'       => '7243-15',
                'descricao' => 'Executa soldagens em peças metálicas.',
            ],
            [
                'nome'      => 'Caldeireiro',
                'cbo'       => '7243-05',
                'descricao' => 'Trabalha com fabricação e reparos de caldeiraria.',
            ],
            [
                'nome'      => 'Enfermeiro do Trabalho',
                'cbo'       => '2235-05',
                'descricao' => 'Atua na assistência de enfermagem ocupacional.',
            ],
            [
                'nome'      => 'Médico do Trabalho',
                'cbo'       => '2251-33',
                'descricao' => 'Responsável pelos exames ocupacionais e PCMSO.',
            ],
            [
                'nome'      => 'Auxiliar de Almoxarifado',
                'cbo'       => '4141-05',
                'descricao' => 'Auxilia no controle e organização de estoque.',
            ],
            [
                'nome'      => 'Almoxarife',
                'cbo'       => '4141-10',
                'descricao' => 'Responsável pela gestão do almoxarifado.',
            ],
            [
                'nome'      => 'Operador de Máquinas Pesadas',
                'cbo'       => '7159-05',
                'descricao' => 'Opera máquinas como retroescavadeira, pá-carregadeira, etc.',
            ],
            [
                'nome'      => 'Supervisor de Produção',
                'cbo'       => '7101-05',
                'descricao' => 'Supervisiona equipes e processos produtivos.',
            ],
            [
                'nome'      => 'Coordenador de Segurança',
                'cbo'       => null,
                'descricao' => 'Coordena projetos e programas de segurança corporativa.',
            ],
        ];

        $empresas = Empresa::all();

        if ($empresas->isEmpty()) {
            $this->command?->warn('Nenhuma empresa encontrada. Crie empresas antes de rodar o FuncaoSeeder.');
            return;
        }

        foreach ($empresas as $empresa) {
            foreach ($funcoesBase as $func) {
                Funcao::firstOrCreate(
                    [
                        'empresa_id' => $empresa->id,
                        'nome'       => $func['nome'],
                    ],
                    [
                        'cbo'       => $func['cbo'],
                        'descricao' => $func['descricao'],
                        'ativo'     => true,
                    ]
                );
            }
        }

        $this->command?->info('Funções padrão criadas para todas as empresas.');
    }
}
