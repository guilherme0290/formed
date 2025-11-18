<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuário de teste (opcional)
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        // Ordem recomendada (respeita dependências)
        $this->call([
            EstadoSeeder::class,
            CidadeSeeder::class,
            EmpresaSeeder::class,

            PapelSeeder::class,
            PermissaoSeeder::class,

            UserSeeder::class,

            ClienteSeeder::class,
            ServicoSeeder::class,

            KanbanColunaSeeder::class,
            TarefaSeeder::class,
        ]);
    }
}
