<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\Empresa;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // empresa padrão (se a coluna existir)
        $empresaId = null;
        if (Schema::hasTable('empresas')) {
            $empresaId = Empresa::query()->value('id');
        }

        // monta payload somente com colunas existentes
        $payload = [
            'name'     => 'Administrador Master',
            'email'    => 'admin@formed.com.br',
            'password' => Hash::make('12345678'),
        ];

        if (Schema::hasColumn('users', 'empresa_id') && $empresaId) {
            $payload['empresa_id'] = $empresaId;
        }
        if (Schema::hasColumn('users', 'email_verified_at')) {
            $payload['email_verified_at'] = now();
        }

        // cria se não existir pelo e-mail
        User::firstOrCreate(['email' => $payload['email']], $payload);
    }
}
