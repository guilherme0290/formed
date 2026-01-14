<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelo_comercial_tabela_linhas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_comercial_tabela_id')
                ->constrained('modelo_comercial_tabelas', indexName: 'mct_linhas_tabela_fk')
                ->cascadeOnDelete();
            $table->json('valores');
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['modelo_comercial_tabela_id', 'ativo'], 'mct_linhas_tabela_ativo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelo_comercial_tabela_linhas');
    }
};
