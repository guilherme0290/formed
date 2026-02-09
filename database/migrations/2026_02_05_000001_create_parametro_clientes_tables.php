<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametro_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('forma_pagamento');
            $table->boolean('incluir_esocial')->default(false);
            $table->unsignedInteger('esocial_qtd_funcionarios')->nullable();
            $table->decimal('esocial_valor_mensal', 10, 2)->default(0);
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->unsignedInteger('prazo_dias')->nullable();
            $table->unsignedTinyInteger('vencimento_servicos')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'cliente_id']);
        });

        Schema::create('parametro_cliente_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parametro_cliente_id')->constrained('parametro_clientes')->cascadeOnDelete();
            $table->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();

            $table->string('tipo')->default('servico');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('valor_unitario', 10, 2)->default(0);
            $table->unsignedInteger('quantidade')->default(1);
            $table->string('prazo')->nullable();
            $table->decimal('acrescimo', 10, 2)->default(0);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['parametro_cliente_id', 'servico_id']);
        });

        Schema::create('parametro_cliente_aso_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('cliente_ghe_id')->nullable()->constrained('cliente_ghes')->nullOnDelete();
            $table->foreignId('parametro_cliente_id')->constrained('parametro_clientes')->cascadeOnDelete();
            $table->string('tipo_aso', 40);
            $table->foreignId('grupo_exames_id')->nullable()->constrained('protocolos_exames')->nullOnDelete();
            $table->decimal('total_exames', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['empresa_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametro_cliente_aso_grupos');
        Schema::dropIfExists('parametro_cliente_itens');
        Schema::dropIfExists('parametro_clientes');
    }
};
