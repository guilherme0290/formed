<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelo_comercial_exames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_comercial_id')
                ->constrained('modelos_comerciais')
                ->cascadeOnDelete();
            $table->foreignId('exame_tab_preco_id')
                ->constrained('exames_tab_preco')
                ->cascadeOnDelete();
            $table->decimal('quantidade', 10, 2)->default(1);
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['modelo_comercial_id', 'exame_tab_preco_id'], 'modelo_comercial_exames_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelo_comercial_exames');
    }
};
