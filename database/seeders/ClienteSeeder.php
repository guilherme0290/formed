<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Empresa;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('clientes')) {
            $this->command?->warn('Tabela "clientes" não existe. Pulei ClienteSeeder.');
            return;
        }

        $empresa = DB::table('empresas')->first();
        if (!$empresa) {
            $this->command?->warn('Nenhuma empresa encontrada. Pulei ClienteSeeder.');
            return;
        }

        // Monta registros "flexíveis" – só usa colunas que existem
        $baseRows = [
            [
                'nome'        => 'João da Silva',
                'email'       => 'joao.silva@email.com',
                'telefone'    => '(11) 98888-1111',
                'cpf_cnpj'    => '11122233344',
                'endereco'    => 'Rua das Flores, 100',
                'cidade'      => 'São Paulo',
                'uf'          => 'SP',
            ],
            [
                'nome'        => 'Maria Oliveira',
                'email'       => 'maria.oliveira@email.com',
                'telefone'    => '(11) 97777-2222',
                'cpf_cnpj'    => '55566677788',
                'endereco'    => 'Av. Paulista, 500',
                'cidade'      => 'São Paulo',
                'uf'          => 'SP',
            ],
        ];

        // Sinônimos possíveis para "nome" no seu schema
        $nomeAliases = ['nome', 'razao_social', 'nome_completo', 'fantasia', 'apelido', 'contato'];

        foreach ($baseRows as $row) {
            // Começa payload vazio e vai adicionando só o que existe na tabela
            $payload = [];

            // empresa_id
            if (Schema::hasColumn('clientes', 'empresa_id')) {
                $payload['empresa_id'] = $empresa->id;
            }

            // nome (usa primeiro alias existente)
            foreach ($nomeAliases as $alias) {
                if (Schema::hasColumn('clientes', $alias)) {
                    $payload[$alias] = $row['nome'];
                    break;
                }
            }

            // campos usuais
            foreach (['email','telefone','cpf_cnpj','endereco','cidade','uf'] as $col) {
                if (Schema::hasColumn('clientes', $col)) {
                    $payload[$col] = $row[$col] ?? null;
                }
            }

            // timestamps se existirem
            if (Schema::hasColumn('clientes','created_at')) $payload['created_at'] = now();
            if (Schema::hasColumn('clientes','updated_at')) $payload['updated_at'] = now();

            // Evita duplicar por email (se coluna existir), senão usa documento
            $exists = false;
            if (Schema::hasColumn('clientes','email')) {
                $exists = DB::table('clientes')->where('email', $row['email'])->exists();
            } elseif (Schema::hasColumn('clientes','cpf_cnpj')) {
                $exists = DB::table('clientes')->where('cpf_cnpj', $row['cpf_cnpj'])->exists();
            }

            if (!$exists) {
                DB::table('clientes')->insert($payload);
            }
        }

        $this->command?->info('✅ Clientes inseridos (modo adaptativo).');
    }
}
