<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelo_comercial_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_comercial_id')
                ->constrained('modelos_comerciais')
                ->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->text('descricao');
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['modelo_comercial_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelo_comercial_itens');
    }
};
