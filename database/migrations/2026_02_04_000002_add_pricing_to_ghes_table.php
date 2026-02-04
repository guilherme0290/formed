<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ghes', function (Blueprint $table) {
            $table->decimal('base_aso_admissional', 10, 2)->default(0);
            $table->decimal('base_aso_periodico', 10, 2)->default(0);
            $table->decimal('base_aso_demissional', 10, 2)->default(0);
            $table->decimal('base_aso_mudanca_funcao', 10, 2)->default(0);
            $table->decimal('base_aso_retorno_trabalho', 10, 2)->default(0);

            $table->decimal('preco_fechado_admissional', 10, 2)->nullable();
            $table->decimal('preco_fechado_periodico', 10, 2)->nullable();
            $table->decimal('preco_fechado_demissional', 10, 2)->nullable();
            $table->decimal('preco_fechado_mudanca_funcao', 10, 2)->nullable();
            $table->decimal('preco_fechado_retorno_trabalho', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ghes', function (Blueprint $table) {
            $table->dropColumn([
                'base_aso_admissional',
                'base_aso_periodico',
                'base_aso_demissional',
                'base_aso_mudanca_funcao',
                'base_aso_retorno_trabalho',
                'preco_fechado_admissional',
                'preco_fechado_periodico',
                'preco_fechado_demissional',
                'preco_fechado_mudanca_funcao',
                'preco_fechado_retorno_trabalho',
            ]);
        });
    }
};
