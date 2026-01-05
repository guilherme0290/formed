<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_receber_baixas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_receber_id')->constrained('contas_receber')->cascadeOnDelete();
            $table->foreignId('conta_receber_item_id')->constrained('contas_receber_itens')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->decimal('valor', 12, 2)->default(0);
            $table->date('pago_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'cliente_id', 'pago_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_receber_baixas');
    }
};
