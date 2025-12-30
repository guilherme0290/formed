<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_ghe_funcoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_ghe_id')
                ->constrained('cliente_ghes')
                ->cascadeOnDelete();
            $table->foreignId('funcao_id')
                ->constrained('funcoes')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cliente_ghe_id', 'funcao_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_ghe_funcoes');
    }
};
