<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\TreinamentoNrsTabPreco;
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
        $empresaId = Empresa::query()->orderBy('id')->value('id');
        if (!$empresaId) {
            $empresaId = Empresa::query()->create(['nome' => 'Empresa Seed'])->id;
        }

        $dados = [
            ['codigo' => 'NR-01', 'titulo' => 'Disposições gerais e gerenciamento de riscos ocupacionais', 'ordem' => 1, 'ativo' => true],
            ['codigo' => 'NR-05', 'titulo' => 'CIPA', 'ordem' => 5, 'ativo' => true],
            ['codigo' => 'NR-06', 'titulo' => 'EPI', 'ordem' => 6, 'ativo' => true],
            ['codigo' => 'NR-07', 'titulo' => 'PCMSO', 'ordem' => 7, 'ativo' => true],
            ['codigo' => 'NR-10', 'titulo' => 'Segurança em instalações e serviços com eletricidade', 'ordem' => 10, 'ativo' => true],
            ['codigo' => 'NR-12', 'titulo' => 'Segurança no trabalho em máquinas e equipamentos', 'ordem' => 12, 'ativo' => true],
            ['codigo' => 'NR-33', 'titulo' => 'Espaços confinados', 'ordem' => 33, 'ativo' => true],
            ['codigo' => 'NR-35', 'titulo' => 'Trabalho em altura', 'ordem' => 35, 'ativo' => true],
        ];

        foreach ($dados as $item) {
            TreinamentoNrsTabPreco::updateOrCreate(
                ['codigo' => $item['codigo']],
                [
                    'empresa_id' => $empresaId,
                    'titulo' => $item['titulo'],
                    'ordem' => $item['ordem'] ?? 0,
                    'ativo' => $item['ativo'] ?? true,
                ]
            );
        }
    }
}
