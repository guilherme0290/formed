<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_receber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('status', 20)->default('FECHADA');
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('total_baixado', 12, 2)->default(0);
            $table->date('vencimento')->nullable();
            $table->date('pago_em')->nullable();
            $table->string('boleto_status', 20)->nullable();
            $table->string('boleto_id')->nullable();
            $table->string('boleto_url')->nullable();
            $table->timestamp('boleto_emitido_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'cliente_id', 'status']);
        });

        Schema::create('contas_receber_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_receber_id')->constrained('contas_receber')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('venda_id')->nullable()->constrained('vendas')->nullOnDelete();
            $table->foreignId('venda_item_id')->nullable()->constrained('venda_itens')->nullOnDelete();
            $table->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $table->string('descricao')->nullable();
            $table->date('data_realizacao')->nullable();
            $table->date('vencimento')->nullable();
            $table->string('status', 20)->default('ABERTO');
            $table->decimal('valor', 12, 2)->default(0);
            $table->timestamp('baixado_em')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'status', 'vencimento']);
            $table->index(['empresa_id', 'venda_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_receber_itens');
        Schema::dropIfExists('contas_receber');
    }
};
