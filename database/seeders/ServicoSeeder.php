<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Empresa;

class ServicoSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('servicos')) {
            $this->command?->warn('Tabela "servicos" não existe. Pulei ServicoSeeder.');
            return;
        }

        $empresa = DB::table('empresas')->first();
        if (!$empresa) {
            $this->command?->warn('Nenhuma empresa encontrada. Pulei ServicoSeeder.');
            return;
        }

        $itens = [
            ['nome' => 'Exame de Admissão',  'descricao' => 'Exame ocupacional na admissão', 'preco' => 120.00],
            ['nome' => 'Exame Periódico',    'descricao' => 'Avaliação médica anual',        'preco' => 100.00],
            ['nome' => 'Laudo Técnico NR-10','descricao' => 'Laudo de segurança elétrica',   'preco' => 250.00],
        ];

        // aliases possíveis
        $nomeAliases   = ['nome','titulo','descricao_curta'];
        $descAliases   = ['descricao','detalhes','observacao'];
        $precoAliases  = ['valor','preco','preco_base','preco_venda','valor_unitario'];

        foreach ($itens as $item) {
            $payload = [];

            if (Schema::hasColumn('servicos','empresa_id')) {
                $payload['empresa_id'] = $empresa->id;
            }

            foreach ($nomeAliases as $a) {
                if (Schema::hasColumn('servicos', $a)) { $payload[$a] = $item['nome']; break; }
            }
            foreach ($descAliases as $a) {
                if (Schema::hasColumn('servicos', $a)) { $payload[$a] = $item['descricao']; break; }
            }
            foreach ($precoAliases as $a) {
                if (Schema::hasColumn('servicos', $a)) { $payload[$a] = $item['preco']; break; }
            }

            if (Schema::hasColumn('servicos','created_at')) $payload['created_at'] = now();
            if (Schema::hasColumn('servicos','updated_at')) $payload['updated_at'] = now();

            // Evita duplicar por nome (se houver alguma coluna de nome)
            $uniqueQuery = DB::table('servicos');
            $uniqueKeySet = false;
            foreach ($nomeAliases as $a) {
                if (Schema::hasColumn('servicos', $a)) {
                    $uniqueQuery = $uniqueQuery->where($a, $item['nome']);
                    $uniqueKeySet = true;
                    break;
                }
            }
            if ($uniqueKeySet && Schema::hasColumn('servicos','empresa_id')) {
                $uniqueQuery = $uniqueQuery->where('empresa_id', $empresa->id);
            }
            $exists = $uniqueKeySet ? $uniqueQuery->exists() : false;

            if (!$exists) {
                DB::table('servicos')->insert($payload);
            }
        }

        $this->command?->info('✅ Serviços inseridos (modo adaptativo).');
    }
}
