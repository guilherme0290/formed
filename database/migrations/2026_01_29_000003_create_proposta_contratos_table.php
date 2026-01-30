<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposta_contratos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('proposta_id');
            $table->string('status', 30)->default('RASCUNHO');
            $table->longText('html')->nullable();
            $table->longText('html_original')->nullable();
            $table->json('clausulas_snapshot')->nullable();
            $table->text('prompt_custom')->nullable();
            $table->unsignedBigInteger('gerado_por')->nullable();
            $table->unsignedBigInteger('atualizado_por')->nullable();
            $table->timestamps();

            $table->unique('proposta_id');
            $table->index(['empresa_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposta_contratos');
    }
};
