<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelo_comercial_treinamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_comercial_id')
                ->constrained('modelos_comerciais')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('treinamento_nr_tab_preco_id');
            $table->decimal('quantidade', 10, 2)->default(1);
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['modelo_comercial_id', 'treinamento_nr_tab_preco_id'], 'modelo_comercial_treinamentos_unique');
            $table->foreign('treinamento_nr_tab_preco_id', 'modelo_comercial_treinamentos_nr_fk')
                ->references('id')
                ->on('treinamento_nrs_tab_preco')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelo_comercial_treinamentos');
    }
};
