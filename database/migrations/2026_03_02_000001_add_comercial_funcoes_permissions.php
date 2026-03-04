<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissoes')->upsert([
            [
                'chave' => 'comercial.funcoes.view',
                'nome' => 'Ver funções',
                'escopo' => 'comercial',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'chave' => 'comercial.funcoes.create',
                'nome' => 'Criar funções',
                'escopo' => 'comercial',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'chave' => 'comercial.funcoes.update',
                'nome' => 'Editar funções',
                'escopo' => 'comercial',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'chave' => 'comercial.funcoes.delete',
                'nome' => 'Excluir funções',
                'escopo' => 'comercial',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['chave'], ['nome', 'escopo', 'updated_at']);
    }

    public function down(): void
    {
        DB::table('permissoes')
            ->whereIn('chave', [
                'comercial.funcoes.view',
                'comercial.funcoes.create',
                'comercial.funcoes.update',
                'comercial.funcoes.delete',
            ])
            ->delete();
    }
};
