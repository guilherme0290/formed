<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('faturas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $t->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            $t->string('numero')->unique();
            $t->date('emissao');
            $t->date('vencimento');
            $t->decimal('valor',12,2);
            $t->enum('status',['Aguardando Cliente','Aprovado','Emitido','Pago','Vencido','Cancelado'])->default('Aguardando Cliente');
            $t->timestamps();
            $t->index(['empresa_id','cliente_id','status','vencimento']);
        });

        Schema::create('fatura_itens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fatura_id')->constrained('faturas')->cascadeOnDelete();
            $t->enum('tipo',['servico','combo']);
            $t->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $t->foreignId('combo_id')->nullable()->constrained('combos')->nullOnDelete();
            $t->unsignedInteger('quantidade')->default(1);
            $t->decimal('valor_unitario',12,2);
            $t->decimal('total',12,2);
            $t->timestamps();
        });

        Schema::create('aprovacoes_clientes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fatura_id')->constrained('faturas')->cascadeOnDelete();
            $t->string('token', 64)->unique();
            $t->timestamp('aprovado_em')->nullable();
            $t->timestamp('rejeitado_em')->nullable();
            $t->timestamps();
        });

        Schema::create('pagamentos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fatura_id')->constrained('faturas')->cascadeOnDelete();
            $t->enum('meio',['PIX','Boleto','Cartao'])->default('PIX');
            $t->decimal('valor',12,2);
            $t->timestamp('pago_em')->nullable();
            $t->string('comprovante')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('pagamentos');
        Schema::dropIfExists('aprovacoes_clientes');
        Schema::dropIfExists('fatura_itens');
        Schema::dropIfExists('faturas');
    }
};
