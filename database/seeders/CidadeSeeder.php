<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use App\Models\Cidade;
use App\Models\Estado;

class CidadeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📥 Baixando lista de municípios do IBGE...');

        $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios';

        $response = Http::get($url);

        if (! $response->successful()) {
            $this->command->error('❌ Erro ao acessar API do IBGE.');
            return;
        }

        $municipios = $response->json();

        $this->command->info('📌 Inserindo cidades no banco...');

        foreach ($municipios as $m) {

            // Nome da cidade
            $nomeCidade = $m['nome'] ?? null;

            // Tentando pegar a UF de forma segura
            $uf = $m['microrregiao']['mesorregiao']['UF']['sigla']
                ?? $m['regiao-imediata']['regiao-intermediaria']['UF']['sigla']
                ?? null;

            if (! $nomeCidade || ! $uf) {
                // Se não tiver nome ou UF, pula esse registro
                $this->command->warn('⚠️ Município sem nome ou UF, pulando...');
                continue;
            }

            // Procura o estado na tabela "estados"
            $estado = Estado::where('uf', $uf)->first();

            if (! $estado) {
                $this->command->warn("⚠️ Estado não encontrado para UF: {$uf}");
                continue;
            }

            // Respeita o unique(estado_id, nome)
            Cidade::updateOrCreate(
                [
                    'estado_id' => $estado->id,
                    'nome'      => $nomeCidade,
                ],
                []
            );
        }

        $this->command->info('✅ Cidades carregadas com sucesso!');
    }
}
