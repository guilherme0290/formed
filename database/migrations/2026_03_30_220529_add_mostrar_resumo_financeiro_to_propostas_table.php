<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            if (!Schema::hasColumn('propostas', 'mostrar_resumo_financeiro')) {
                $table->boolean('mostrar_resumo_financeiro')->default(true)->after('valor_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            if (Schema::hasColumn('propostas', 'mostrar_resumo_financeiro')) {
                $table->dropColumn('mostrar_resumo_financeiro');
            }
        });
    }
};
