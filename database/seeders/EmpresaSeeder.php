<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('empresas')) {
            $this->command?->warn('Tabela "empresas" não existe. Pulei EmpresaSeeder.');
            return;
        }

        // Monta os dados apenas com as colunas que EXISTEM
        $data = [
            // nomes possíveis
            Schema::hasColumn('empresas','nome')          ? ['nome' => 'Formed Matriz']               : [],
            Schema::hasColumn('empresas','razao_social')  ? ['razao_social' => 'Formed Matriz Ltda']  : [],
            Schema::hasColumn('empresas','fantasia')      ? ['fantasia' => 'Formed']                  : [],

            // docs/contato
            Schema::hasColumn('empresas','cnpj')          ? ['cnpj' => '00000000000100']              : [],
            Schema::hasColumn('empresas','email')         ? ['email' => 'contato@formed.com.br']      : [],
            Schema::hasColumn('empresas','telefone')      ? ['telefone' => '(11) 99999-0000']         : [],

            // endereço (variações comuns)
            Schema::hasColumn('empresas','endereco')      ? ['endereco' => 'Rua Principal, 100']      : [],
            Schema::hasColumn('empresas','logradouro')    ? ['logradouro' => 'Rua Principal']         : [],
            Schema::hasColumn('empresas','numero')        ? ['numero' => '100']                       : [],
            Schema::hasColumn('empresas','bairro')        ? ['bairro' => 'Centro']                    : [],
            Schema::hasColumn('empresas','cidade')        ? ['cidade' => 'São Paulo']                 : [],
            Schema::hasColumn('empresas','uf')            ? ['uf' => 'SP']                            : [],
            Schema::hasColumn('empresas','cep')           ? ['cep' => '01000000']                     : [],
        ];

        // Achata o array
        $payload = [];
        foreach ($data as $row) { $payload += $row; }

        // Garante timestamps se a tabela tiver
        if (Schema::hasColumn('empresas','created_at')) $payload['created_at'] = now();
        if (Schema::hasColumn('empresas','updated_at')) $payload['updated_at'] = now();

        // Já existe alguma empresa? então não duplica
        $exists = DB::table('empresas')->count() > 0;
        if (!$exists) {
            DB::table('empresas')->insert($payload);
            $this->command?->info('✅ Empresa padrão criada.');
        } else {
            $this->command?->line('ℹ️ Empresa já existe. Pulando criação.');
        }
    }
}
