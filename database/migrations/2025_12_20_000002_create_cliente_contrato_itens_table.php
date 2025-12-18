<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_contrato_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_contrato_id')->constrained('cliente_contratos')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
            $table->string('descricao_snapshot', 500)->nullable();
            $table->decimal('preco_unitario_snapshot', 12, 2)->default(0);
            $table->string('unidade_cobranca', 50)->default('unidade');
            $table->json('regras_snapshot')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['cliente_contrato_id', 'servico_id', 'ativo'],'cliente_service_ativo_idx');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('cliente_contrato_itens');
        Schema::enableForeignKeyConstraints();
    }
};
