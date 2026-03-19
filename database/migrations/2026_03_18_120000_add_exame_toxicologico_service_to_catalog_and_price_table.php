<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $empresaIds = DB::table('empresas')->pluck('id');

        foreach ($empresaIds as $empresaId) {
            DB::table('servicos')->updateOrInsert(
                [
                    'empresa_id' => $empresaId,
                    'nome' => 'Exame toxicológico',
                ],
                [
                    'descricao' => 'Exame toxicológico ocupacional.',
                    'ativo' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $servico = DB::table('servicos')
                ->where('empresa_id', $empresaId)
                ->where('nome', 'Exame toxicológico')
                ->first();

            if (!$servico) {
                continue;
            }

            $tabelaPadrao = DB::table('tabela_precos_padrao')
                ->where('empresa_id', $empresaId)
                ->where('ativa', true)
                ->orderBy('id')
                ->first();

            if (!$tabelaPadrao) {
                $tabelaPadraoId = DB::table('tabela_precos_padrao')->insertGetId([
                    'empresa_id' => $empresaId,
                    'nome' => 'Tabela Padrão',
                    'ativa' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $tabelaPadraoId = $tabelaPadrao->id;
            }

            DB::table('tabela_preco_items')->updateOrInsert(
                [
                    'tabela_preco_padrao_id' => $tabelaPadraoId,
                    'servico_id' => $servico->id,
                    'codigo' => 'TOX',
                ],
                [
                    'descricao' => 'Exame toxicológico',
                    'preco' => 0,
                    'ativo' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $empresaIds = DB::table('empresas')->pluck('id');

        foreach ($empresaIds as $empresaId) {
            $servico = DB::table('servicos')
                ->where('empresa_id', $empresaId)
                ->where('nome', 'Exame toxicológico')
                ->first();

            if (!$servico) {
                continue;
            }

            DB::table('tabela_preco_items')
                ->where('servico_id', $servico->id)
                ->where('codigo', 'TOX')
                ->delete();

            DB::table('servicos')
                ->where('id', $servico->id)
                ->delete();
        }
    }
};
