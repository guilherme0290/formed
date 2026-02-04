<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cliente_ghes') || !Schema::hasTable('ghes')) {
            return;
        }

        $rows = DB::table('cliente_ghes')->get();
        if ($rows->isEmpty()) {
            return;
        }

        $funcoesByGhe = DB::table('cliente_ghe_funcoes')
            ->get(['cliente_ghe_id', 'funcao_id'])
            ->groupBy('cliente_ghe_id')
            ->map(function ($items) {
                return $items->pluck('funcao_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            });

        $map = [];

        foreach ($rows as $ghe) {
            if (!empty($ghe->ghe_id)) {
                continue;
            }
            $funcoes = $funcoesByGhe->get($ghe->id, []);
            $key = $ghe->empresa_id . '|' . $ghe->nome . '|' . implode(',', $funcoes);

            if (!isset($map[$key])) {
                $gheId = DB::table('ghes')->insertGetId([
                    'empresa_id' => $ghe->empresa_id,
                    'nome' => $ghe->nome,
                    'grupo_exames_id' => $ghe->protocolo_id ?: null,
                    'base_aso_admissional' => $ghe->base_aso_admissional ?? 0,
                    'base_aso_periodico' => $ghe->base_aso_periodico ?? 0,
                    'base_aso_demissional' => $ghe->base_aso_demissional ?? 0,
                    'base_aso_mudanca_funcao' => $ghe->base_aso_mudanca_funcao ?? 0,
                    'base_aso_retorno_trabalho' => $ghe->base_aso_retorno_trabalho ?? 0,
                    'preco_fechado_admissional' => $ghe->preco_fechado_admissional ?? null,
                    'preco_fechado_periodico' => $ghe->preco_fechado_periodico ?? null,
                    'preco_fechado_demissional' => $ghe->preco_fechado_demissional ?? null,
                    'preco_fechado_mudanca_funcao' => $ghe->preco_fechado_mudanca_funcao ?? null,
                    'preco_fechado_retorno_trabalho' => $ghe->preco_fechado_retorno_trabalho ?? null,
                    'ativo' => $ghe->ativo ?? true,
                    'created_at' => $ghe->created_at ?? now(),
                    'updated_at' => $ghe->updated_at ?? now(),
                ]);

                if (!empty($funcoes)) {
                    $now = now();
                    $insert = array_map(function ($funcaoId) use ($gheId, $now) {
                        return [
                            'ghe_id' => $gheId,
                            'funcao_id' => $funcaoId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }, $funcoes);
                    DB::table('ghe_funcoes')->insertOrIgnore($insert);
                }

                $map[$key] = $gheId;
            }

            DB::table('cliente_ghes')
                ->where('id', $ghe->id)
                ->update(['ghe_id' => $map[$key]]);
        }
    }

    public function down(): void
    {
        // Sem rollback seguro para os dados de backfill.
    }
};
