<?php // 2025_01_01_000200_create_comercial_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('propostas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $t->foreignId('vendedor_id')->constrained('usuarios')->cascadeOnDelete();
            $t->string('codigo')->nullable();
            $t->enum('status',['Rascunho','Enviada','Aprovada','Rejeitada','Expirada'])->default('Rascunho');
            $t->date('data_emissao')->nullable();
            $t->date('validade')->nullable();
            $t->text('observacoes')->nullable();
            $t->decimal('valor_total', 12, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('proposta_itens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('proposta_id')->constrained('propostas')->cascadeOnDelete();
            $t->enum('tipo',['servico','combo']);
            $t->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $t->foreignId('combo_id')->nullable()->constrained('combos')->nullOnDelete();
            $t->unsignedInteger('quantidade')->default(1);
            $t->decimal('valor_unitario', 12, 2);
            $t->decimal('desconto', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->timestamps();
        });

        Schema::create('contratos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $t->foreignId('proposta_id')->nullable()->constrained('propostas')->nullOnDelete();
            $t->enum('status',['Ativo','Suspenso','Cancelado','Encerrado'])->default('Ativo');
            $t->date('data_inicio');
            $t->date('data_fim')->nullable();
            $t->boolean('recorrente')->default(true);
            $t->decimal('valor_mensal', 12, 2)->default(0);
            $t->text('observacoes')->nullable();
            $t->timestamps();
        });

        Schema::create('contrato_itens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $t->enum('tipo',['servico','combo']);
            $t->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $t->foreignId('combo_id')->nullable()->constrained('combos')->nullOnDelete();
            $t->unsignedInteger('quantidade')->default(1);
            $t->decimal('valor_unitario',12,2);
            $t->decimal('total',12,2);
            $t->timestamps();
        });

        Schema::create('comissoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete(); // vendedor
            $t->nullableMorphs('referencia'); // proposta ou contrato (polimórfico)
            $t->string('competencia', 7); // AAAA-MM
            $t->decimal('percentual',5,2)->default(0); // ex. 5.00 (%)
            $t->decimal('base',12,2)->default(0);
            $t->decimal('valor',12,2)->default(0);
            $t->enum('status',['Prevista','Aprovada','Paga'])->default('Prevista');
            $t->timestamps();
            $t->index(['empresa_id','usuario_id','competencia']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('comissoes');
        Schema::dropIfExists('contrato_itens');
        Schema::dropIfExists('contratos');
        Schema::dropIfExists('proposta_itens');
        Schema::dropIfExists('propostas');
    }
};
