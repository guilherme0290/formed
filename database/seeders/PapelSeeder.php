<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Papel;

class PapelSeeder extends Seeder
{
    public function run(): void
    {
        $papeis = [
            ['nome' => 'Master', 'descricao' => 'Acesso total ao sistema'],
            ['nome' => 'Operacional', 'descricao' => 'Acesso às tarefas e kanban'],
            ['nome' => 'Financeiro', 'descricao' => 'Controle de lançamentos e pagamentos'],
        ];

        foreach ($papeis as $papel) {
            Papel::firstOrCreate(['nome' => $papel['nome']], $papel);
        }
    }
}
