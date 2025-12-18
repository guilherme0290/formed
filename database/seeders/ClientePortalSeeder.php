<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Papel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ClientePortalSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Empresa "padrão" da FORMED
        $empresaId = 1; // ajuste se necessário

        // 2) Papel "Cliente"
        $papelClienteId = Papel::where('nome', 'Cliente')->value('id');

        if (!$papelClienteId) {
            // se ainda não existir, cria na hora
            $papel = Papel::firstOrCreate(
                ['nome' => 'Cliente'],
                ['descricao' => 'Acesso ao portal do Cliente']
            );
            $papelClienteId = $papel->id;
        }

        // 3) Cliente para usar no portal
        $cliente = Cliente::firstOrCreate(
            [
                'empresa_id' => $empresaId,
                'cnpj'       => '00000000000191', // fictício, pode trocar
            ],
            [
                'razao_social'  => 'Cliente Portal Teste LTDA',
                'nome_fantasia' => 'Cliente Portal',
                'email'         => 'cliente@formed.test',
                'telefone'      => '(38) 0000-0000',
                'ativo'         => true,
            ]
        );

        // 4) Usuário do portal cliente
        $user = User::updateOrCreate(
            ['email' => 'cliente@formed.test'],
            [
                'name'       => 'Usuário Portal Cliente',
                'telefone'   => '(38) 0000-0000',
                'empresa_id' => $empresaId,
                'papel_id'   => $papelClienteId,
                'ativo'      => true,
                'password'   => Hash::make('cliente123'),
            ]
        );

        // 5) Se a tabela clientes tiver coluna user_id, vincula 1:1
        if (Schema::hasColumn($cliente->getTable(), 'user_id')) {
            $cliente->user_id = $user->id;
            $cliente->save();
        }

        $this->command->info('✅ Usuário do portal cliente criado:');
        $this->command->info('   Email: cliente@formed.test');
        $this->command->info('   Senha: cliente123');
    }
}

