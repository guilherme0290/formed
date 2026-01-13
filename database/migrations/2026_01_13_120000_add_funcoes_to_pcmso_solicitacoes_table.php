<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pcmso_solicitacoes', function (Blueprint $table) {
            $table->json('funcoes')->nullable()->after('obra_endereco');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pcmso_solicitacoes', function (Blueprint $table) {
            $table->dropColumn('funcoes');
        });
    }
};
