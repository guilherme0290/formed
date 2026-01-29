<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\MedicoesTabPreco;
use Illuminate\Database\Seeder;

class MedicoesTabPrecoSeeder extends Seeder
{
    public function run(): void
    {
        $itens = [
            ['Avaliação de poeira total', 230.00],
            ['Avaliação de poeira respirável + sílica livre', 440.00],
            ['Avaliação de fumos metálicos', 780.00],
            ['Avaliação de poeiras metálicas', 400.00],
            ['Avaliação de poeira de madeira', 280.00],
            ['Dosímetro de ruído', 240.00],
            ['Medidor acústico', 100.00],
            ['Termômetro de globo - calor', 400.00],
            ['Medidor de vibração', 1000.00],
            ['Fumos de borracha', 460.00],
            ['Avaliação de vapores orgânicos', 780.00],
            ['Elaboração do LTCAT', 1500.00],
            ['Elaboração do PGR com ART', 750.00],
            ['Visita do técnico para realização das avaliações em Campinas (diária)', 300.00],
        ];

        foreach (Empresa::query()->get(['id']) as $empresa) {
            foreach ($itens as [$titulo, $preco]) {
                MedicoesTabPreco::updateOrCreate(
                    [
                        'empresa_id' => $empresa->id,
                        'titulo' => $titulo,
                    ],
                    [
                        'descricao' => null,
                        'preco' => $preco,
                        'ativo' => true,
                    ]
                );
            }
        }
    }
}
