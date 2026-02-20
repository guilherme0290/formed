<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contas_pagar')) {
            Schema::create('contas_pagar', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->foreignId('fornecedor_id')->constrained('fornecedores')->restrictOnDelete();
                $table->string('status', 20)->default('FECHADA');
                $table->decimal('total', 12, 2)->default(0);
                $table->decimal('total_baixado', 12, 2)->default(0);
                $table->date('vencimento')->nullable();
                $table->date('pago_em')->nullable();
                $table->text('observacao')->nullable();
                $table->timestamps();

                $table->index(['empresa_id', 'fornecedor_id', 'status']);
            });
        }

        if (!Schema::hasTable('contas_pagar_itens')) {
            Schema::create('contas_pagar_itens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conta_pagar_id')->constrained('contas_pagar')->cascadeOnDelete();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();
                $table->string('categoria', 80)->nullable();
                $table->string('descricao');
                $table->date('data_competencia')->nullable();
                $table->date('vencimento')->nullable();
                $table->string('status', 20)->default('ABERTO');
                $table->decimal('valor', 12, 2)->default(0);
                $table->timestamp('baixado_em')->nullable();
                $table->timestamps();

                $table->index(
                    ['empresa_id', 'fornecedor_id', 'status', 'vencimento'],
                    'cpi_emp_forn_status_venc_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_pagar_itens');
        Schema::dropIfExists('contas_pagar');
    }
};
