<?php

namespace Database\Seeders;

use App\Models\TreinamentoNrsTabPreco;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TreinamentoNRsSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dados = [
            ['codigo' => 'NR-01','empresa_id'=>1, 'titulo' => 'Introdução à segurança e medicina do trabalho', 'ordem' => 1, 'ativo' => true],
            ['codigo' => 'NR-02','empresa_id' =>1, 'titulo' => 'Comissão Interna de Prevenção de Acidentes (CIPA)', 'ordem' => 2, 'ativo' => true],
            ['codigo' => 'NR-03','empresa_id' =>1, 'titulo' => 'Equipamentos de Proteção Individual (EPIs)', 'ordem' => 3, 'ativo' => true],
            ['codigo' => 'NR-04','empresa_id' =>1, 'titulo' => 'Sistemas de gestão de segurança no trabalho', 'ordem' => 4, 'ativo' => true],
            ['codigo' => 'NR-05','empresa_id' =>1, 'titulo' => 'Treinamento em segurança para trabalhadores de áreas de risco', 'ordem' => 5, 'ativo' => true],
            ['codigo' => 'NR-06','empresa_id' =>1, 'titulo' => 'Medidas de segurança para uso de ferramentas manuais', 'ordem' => 6, 'ativo' => true],
            ['codigo' => 'NR-07','empresa_id' =>1, 'titulo' => 'Segurança em instalações de máquinas pesadas', 'ordem' => 7, 'ativo' => true],
            ['codigo' => 'NR-08','empresa_id' =>1, 'titulo' => 'Procedimentos de segurança para trabalhos em altura', 'ordem' => 8, 'ativo' => true],
            ['codigo' => 'NR-09','empresa_id' =>1, 'titulo' => 'Procedimentos de segurança em trabalho com eletricidade', 'ordem' => 9, 'ativo' => true],
            ['codigo' => 'NR-10','empresa_id' =>1, 'titulo' => 'Segurança em instalações e serviços com eletricidade', 'ordem' => 10, 'ativo' => true],
            ['codigo' => 'NR-11','empresa_id' =>1, 'titulo' => 'Trabalho em espaços confinados', 'ordem' => 11, 'ativo' => true],
            ['codigo' => 'NR-12','empresa_id' =>1, 'titulo' => 'Segurança no uso de máquinas e equipamentos', 'ordem' => 12, 'ativo' => true],
            ['codigo' => 'NR-13','empresa_id' =>1, 'titulo' => 'Trabalho em ambientes com risco biológico', 'ordem' => 13, 'ativo' => true],
            ['codigo' => 'NR-14','empresa_id' =>1, 'titulo' => 'Requisitos para realização de exames médicos ocupacionais', 'ordem' => 14, 'ativo' => true],
            ['codigo' => 'NR-15','empresa_id' =>1, 'titulo' => 'Exigências de segurança para locais de trabalho com riscos ambientais', 'ordem' => 15, 'ativo' => true],
            ['codigo' => 'NR-16','empresa_id' =>1, 'titulo' => 'Segurança no transporte de cargas perigosas', 'ordem' => 16, 'ativo' => true],
            ['codigo' => 'NR-17','empresa_id' =>1, 'titulo' => 'Segurança para operações de máquinas agrícolas', 'ordem' => 17, 'ativo' => true],
            ['codigo' => 'NR-18','empresa_id' =>1, 'titulo' => 'Requisitos de segurança para trabalho de construção civil', 'ordem' => 18, 'ativo' => true],
            ['codigo' => 'NR-19','empresa_id' =>1, 'titulo' => 'Segurança no trabalho com substâncias químicas', 'ordem' => 19, 'ativo' => true],
            ['codigo' => 'NR-20','empresa_id' =>1, 'titulo' => 'Normas de segurança em instalações de gás', 'ordem' => 20, 'ativo' => true],
            ['codigo' => 'NR-21','empresa_id' =>1, 'titulo' => 'Exigências de segurança para atividades de mineração', 'ordem' => 21, 'ativo' => true],
            ['codigo' => 'NR-22','empresa_id' =>1, 'titulo' => 'Segurança no trabalho em locais subterrâneos', 'ordem' => 22, 'ativo' => true],
            ['codigo' => 'NR-23','empresa_id' =>1, 'titulo' => 'Treinamento para emergência em serviços de combate a incêndios', 'ordem' => 23, 'ativo' => true],
            ['codigo' => 'NR-24','empresa_id' =>1, 'titulo' => 'Normas de higiene e segurança no trabalho de serviços gerais', 'ordem' => 24, 'ativo' => true],
            ['codigo' => 'NR-25','empresa_id' =>1, 'titulo' => 'Segurança no trabalho com equipamentos de combate a incêndio', 'ordem' => 25, 'ativo' => true],
        ];

        foreach ($dados as $item) {
            TreinamentoNrsTabPreco::create($item);
        }
    }
}
