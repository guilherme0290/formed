<?php

namespace Database\Seeders;

use App\Models\Servico;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Empresa;

class ServicoSeeder extends Seeder
{
    public function run(): void
    {
        $servicos = [
            [
                'nome'      => 'ASO',
                'descricao' => 'Atestado de Saúde Ocupacional para colaboradores.',
            ],
            [
                'nome'      => 'PGR',
                'descricao' => 'Programa de Gerenciamento de Riscos.',
            ],
            [
                'nome'      => 'PCMSO',
                'descricao' => 'Programa de Controle Médico Ocupacional.',
            ],
            [
                'nome'      => 'LTCAT',
                'descricao' => 'Laudo Técnico das Condições Ambientais.',
            ],
            [
                'nome'      => 'LTIP',
                'descricao' => 'Laudo de Insalubridade e Periculosidade.',
            ],
            [
                'nome'      => 'APR',
                'descricao' => 'Análise Preliminar de Riscos.',
            ],
            [
                'nome'      => 'ART',
                'descricao' => 'Anotação de Responsabilidade Técnica.',
            ],
            [
                'nome'      => 'PAE',
                'descricao' => 'Plano de Atendimento a Emergências.',
            ],
            [
                'nome'      => 'Treinamentos NRs',
                'descricao' => 'Normas regulamentadoras e capacitações.',
            ],
        ];

        foreach ($servicos as $servico) {
            Servico::updateOrCreate(
                ['nome' => $servico['nome']], // chave de procura
                [
                    'empresa_id' =>1,
                    'descricao' => $servico['descricao'],
                    'ativo'     => true,
                ]
            );
        }
    }
}
