<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ltcat_solicitacoes', function (Blueprint $table) {
            // Campos específicos da obra (para LTCAT ESPECÍFICO)
            $table->string('nome_obra', 255)->nullable()->after('endereco_avaliacoes');
            $table->string('cnpj_contratante', 20)->nullable()->after('nome_obra');

            $table->string('cei_cno', 50)->nullable()->after('cnpj_contratante');
            $table->string('endereco_obra', 50)->nullable();



        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ltcat_solicitacoes', function (Blueprint $table) {
            $table->dropColumn([
                'nome_obra',
                'cnpj_contratante',
                'cei_cno',
                'endereco_obra'
            ]);
        });
    }
};
