<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_ghes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id');
            $table->string('nome');
            $table->foreignId('protocolo_id')
                ->nullable()
                ->constrained('protocolos_exames')
                ->nullOnDelete();

            $table->decimal('base_aso_admissional', 10, 2)->default(0);
            $table->decimal('base_aso_periodico', 10, 2)->default(0);
            $table->decimal('base_aso_demissional', 10, 2)->default(0);
            $table->decimal('base_aso_mudanca_funcao', 10, 2)->default(0);
            $table->decimal('base_aso_retorno_trabalho', 10, 2)->default(0);

            $table->decimal('preco_fechado_admissional', 10, 2)->nullable();
            $table->decimal('preco_fechado_periodico', 10, 2)->nullable();
            $table->decimal('preco_fechado_demissional', 10, 2)->nullable();
            $table->decimal('preco_fechado_mudanca_funcao', 10, 2)->nullable();
            $table->decimal('preco_fechado_retorno_trabalho', 10, 2)->nullable();

            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'cliente_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_ghes');
    }
};
