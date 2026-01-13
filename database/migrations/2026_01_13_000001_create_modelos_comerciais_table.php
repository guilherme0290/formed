<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelos_comerciais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('segmento', 60);
            $table->string('titulo', 150)->nullable();
            $table->text('intro_1')->nullable();
            $table->text('intro_2')->nullable();
            $table->text('beneficios')->nullable();
            $table->text('rodape')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'segmento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_comerciais');
    }
};
