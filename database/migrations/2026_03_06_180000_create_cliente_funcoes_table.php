<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_funcoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('funcao_id')->constrained('funcoes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cliente_id', 'funcao_id']);
            $table->index(['empresa_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_funcoes');
    }
};
