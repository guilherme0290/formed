<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\KanbanColuna;
use Illuminate\Support\Facades\DB;

class KanbanColunaSeeder extends Seeder
{
    public function run(): void
    {
        // Defina aqui a empresa que vai usar esse kanban
        $empresaId = 1;

        // Remove colunas existentes dessa empresa
        DB::table('kanban_colunas')
            ->where('empresa_id', $empresaId)
            ->delete();

        // Novas colunas conforme protótipo
        $colunas = [
            [
                'nome'     => 'Pendente',
                'slug'     => 'pendente',
                'cor'      => '#FDE68A', // amarelo
                'finaliza' => false,
                'atraso'   => false,
            ],
            [
                'nome'     => 'Em Execução',
                'slug'     => 'em-execucao',
                'cor'      => '#BFDBFE', // azul claro
                'finaliza' => false,
                'atraso'   => false,
            ],
            [
                'nome'     => 'Aguardando Fornecedor',
                'slug'     => 'aguardando-fornecedor',
                'cor'      => '#E9D5FF', // roxo claro
                'finaliza' => false,
                'atraso'   => false,
            ],
            [
                'nome'     => 'Finalizada',
                'slug'     => 'finalizada',
                'cor'      => '#A7F3D0', // verde claro
                'finaliza' => true,      // marca como coluna de conclusão
                'atraso'   => false,
            ],
            [
                'nome'     => 'Correção',
                'slug'     => 'correcao',
                'cor'      => '#FED7AA', // laranja claro
                'finaliza' => false,
                'atraso'   => false,
            ],
            [
                'nome'     => 'Atrasado',
                'slug'     => 'atrasado',
                'cor'      => '#FECACA', // vermelho claro
                'finaliza' => false,
                'atraso'   => true,      // coluna de atraso
            ],
        ];

        foreach ($colunas as $index => $coluna) {
            DB::table('kanban_colunas')->insert([
                'empresa_id' => $empresaId,
                'nome'       => $coluna['nome'],
                'slug'       => $coluna['slug'],
                'cor'        => $coluna['cor'],
                'ordem'      => $index + 1,
                'finaliza'   => $coluna['finaliza'],
                'atraso'     => $coluna['atraso'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
