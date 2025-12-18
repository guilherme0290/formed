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
         Schema::create('treinamento_nrs_tab_preco', function (Blueprint $table) {
             $table->id();

             $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
             // Ex: NR-35, NR-10...
             $table->string('codigo', 20)->unique();

             // Ex: Trabalho em Altura
             $table->string('titulo', 255);

             // Opcional: ordenação na listagem dos chips
             $table->unsignedInteger('ordem')->default(0);

             $table->boolean('ativo')->default(true);

             $table->timestamps();

             $table->index(['ativo', 'ordem']);
         });
     }

    public function down(): void
    {
        Schema::dropIfExists('treinamento_nrs_tab_preco');
    }
};
