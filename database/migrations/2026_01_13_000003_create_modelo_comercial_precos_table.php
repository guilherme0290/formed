<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelo_comercial_precos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_comercial_id')
                ->constrained('modelos_comerciais')
                ->cascadeOnDelete();
            $table->foreignId('tabela_preco_item_id')
                ->constrained('tabela_preco_items')
                ->cascadeOnDelete();
            $table->decimal('quantidade', 10, 2)->default(1);
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['modelo_comercial_id', 'tabela_preco_item_id'], 'modelo_comercial_precos_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelo_comercial_precos');
    }
};
