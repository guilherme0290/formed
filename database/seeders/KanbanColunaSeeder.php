<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\KanbanColuna;

class KanbanColunaSeeder extends Seeder
{
    public function run(): void
    {
        // pega uma empresa existente
        $empresaId = Empresa::query()->value('id') ?? 1;

        $ordem = 1;
        $colunas = ['A Fazer', 'Em Andamento', 'Aprovação', 'Concluído'];

        foreach ($colunas as $nome) {
            KanbanColuna::updateOrCreate(
                ['empresa_id' => $empresaId, 'nome' => $nome],
                ['ordem' => $ordem++, 'finaliza' => $nome === 'Concluído']
            );
        }
    }
}
