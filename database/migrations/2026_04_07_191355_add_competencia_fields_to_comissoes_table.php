<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comissoes', function (Blueprint $table) {
            $table->date('competencia_em')->nullable()->after('status');
            $table->timestamp('paga_em')->nullable()->after('gerada_em');

            $table->index(['empresa_id', 'vendedor_id', 'competencia_em'], 'comissoes_empresa_vendedor_competencia_idx');
        });

        DB::statement("UPDATE comissoes SET competencia_em = DATE(COALESCE(gerada_em, created_at)) WHERE competencia_em IS NULL");
    }

    public function down(): void
    {
        Schema::table('comissoes', function (Blueprint $table) {
            $table->dropIndex('comissoes_empresa_vendedor_competencia_idx');
            $table->dropColumn(['competencia_em', 'paga_em']);
        });
    }
};
