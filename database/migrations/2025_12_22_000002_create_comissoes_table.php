<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comissoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('venda_id')->constrained('vendas')->cascadeOnDelete();
            $table->foreignId('venda_item_id')->constrained('venda_itens')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
            $table->decimal('valor_base', 12, 2)->default(0);
            $table->decimal('percentual', 5, 2)->default(0);
            $table->decimal('valor_comissao', 12, 2)->default(0);
            $table->string('status', 20)->default('PENDENTE'); // PENDENTE | PAGA | CANCELADA
            $table->timestamp('gerada_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'vendedor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('comissoes');
        Schema::enableForeignKeyConstraints();
    }
};
