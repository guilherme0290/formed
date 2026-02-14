<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contas_pagar_baixas')) {
            Schema::create('contas_pagar_baixas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conta_pagar_id')->constrained('contas_pagar')->cascadeOnDelete();
                $table->foreignId('conta_pagar_item_id')->constrained('contas_pagar_itens')->cascadeOnDelete();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();
                $table->decimal('valor', 12, 2)->default(0);
                $table->date('pago_em')->nullable();
                $table->string('meio_pagamento', 80)->nullable();
                $table->text('observacao')->nullable();
                $table->string('comprovante_path')->nullable();
                $table->string('comprovante_nome')->nullable();
                $table->string('comprovante_mime', 120)->nullable();
                $table->unsignedBigInteger('comprovante_tamanho')->nullable();
                $table->timestamps();

                $table->index(['empresa_id', 'fornecedor_id', 'pago_em']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_pagar_baixas');
    }
};
