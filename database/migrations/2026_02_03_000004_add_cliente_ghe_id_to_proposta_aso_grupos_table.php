<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposta_aso_grupos', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_ghe_id')->nullable()->after('cliente_id');
            $table->index(['cliente_ghe_id']);
            $table->foreign('cliente_ghe_id')
                ->references('id')
                ->on('cliente_ghes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proposta_aso_grupos', function (Blueprint $table) {
            $table->dropForeign(['cliente_ghe_id']);
            $table->dropIndex(['cliente_ghe_id']);
            $table->dropColumn('cliente_ghe_id');
        });
    }
};
