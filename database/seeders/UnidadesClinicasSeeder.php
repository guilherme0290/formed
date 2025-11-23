<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadesClinicasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresaId = 1; // ajuste aqui se necessário

        $unidades = [
            [
                'empresa_id' => $empresaId,
                'nome'       => 'São Paulo - Bela Vista',
                'endereco'   => null,
                'telefone'   => null,
                'ativo'      => true,
            ],
            [
                'empresa_id' => $empresaId,
                'nome'       => 'Vila Mariana',
                'endereco'   => null,
                'telefone'   => null,
                'ativo'      => true,
            ],
            [
                'empresa_id' => $empresaId,
                'nome'       => 'Santo André',
                'endereco'   => null,
                'telefone'   => null,
                'ativo'      => true,
            ],
            [
                'empresa_id' => $empresaId,
                'nome'       => 'São José dos Campos',
                'endereco'   => null,
                'telefone'   => null,
                'ativo'      => true,
            ],
            [
                'empresa_id' => $empresaId,
                'nome'       => 'Campinas',
                'endereco'   => null,
                'telefone'   => null,
                'ativo'      => true,
            ],
        ];

        foreach ($unidades as $unidade) {
            DB::table('unidades_clinicas')->updateOrInsert(
                [
                    'empresa_id' => $unidade['empresa_id'],
                    'nome'       => $unidade['nome'],
                ],
                $unidade
            );
        }
    }
}
