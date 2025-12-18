<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propostas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnDelete();

            $table->foreignId('vendedor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('codigo')->nullable(); // ex: PC-2025-0001 (se quiser usar depois)

            $table->string('forma_pagamento');

            // E-Social
            $table->boolean('incluir_esocial')->default(false);
            $table->unsignedInteger('esocial_qtd_funcionarios')->nullable();
            $table->decimal('esocial_valor_mensal', 10, 2)->default(0);

            // Totais
            $table->decimal('valor_total', 12, 2)->default(0);

            // rascunho | enviada | aceita | recusada
            $table->string('status')->default('rascunho');

            $table->text('observacoes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propostas');
    }
};
