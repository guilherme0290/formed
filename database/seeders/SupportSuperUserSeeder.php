<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Papel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SupportSuperUserSeeder extends Seeder
{
    public function run(): void
    {
        $papelMaster = Papel::query()
            ->whereRaw('lower(nome) = ?', ['master'])
            ->first();

        if (!$papelMaster) {
            return;
        }

        $email = (string) env('SUPPORT_SUPERUSER_EMAIL', 'suporte@formedseg.com.br');
        $nome = (string) env('SUPPORT_SUPERUSER_NAME', 'S');
        $senha = (string) env('SUPPORT_SUPERUSER_PASSWORD', 'Formed@0290');

        $empresaId = null;
        if (Schema::hasTable('empresas')) {
            $empresaId = Empresa::query()->value('id');
        }

        $payload = [
            'name' => $nome,
            'email' => $email,
            'password' => Hash::make($senha),
            'papel_id' => $papelMaster->id,
            'ativo' => true,
            'is_active' => true,
            'is_protected' => true,
            'must_change_password' => false,
        ];

        if ($empresaId && Schema::hasColumn('users', 'empresa_id')) {
            $payload['empresa_id'] = $empresaId;
        }

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $payload['email_verified_at'] = now();
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill($payload);
        $user->save();
    }
}

