<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PapelSeeder::class,
            PermissoesSeeder::class,
            SupportSuperUserSeeder::class,
            MedicoesTabPrecoSeeder::class,
            RemoveAsoTabelaPrecoSeeder::class,
            ContratoClausulasBaseSeeder::class,
        ]);
    }
}
