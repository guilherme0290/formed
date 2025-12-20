<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cliente_contrato_vigencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_contrato_id');
            $table->date('vigencia_inicio');
            $table->date('vigencia_fim')->nullable();
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->string('observacao')->nullable();
            $table->timestamps();

            $table->foreign('cliente_contrato_id')->references('id')->on('cliente_contratos')->onDelete('cascade');
        });

        Schema::create('cliente_contrato_vigencia_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vigencia_id');
            $table->unsignedBigInteger('servico_id')->nullable();
            $table->string('descricao_snapshot')->nullable();
            $table->decimal('preco_unitario_snapshot', 12, 2)->default(0);
            $table->string('unidade_cobranca')->nullable();
            $table->json('regras_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('vigencia_id', 'fk_vigencia_item')
                ->references('id')
                ->on('cliente_contrato_vigencias')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_contrato_vigencia_itens');
        Schema::dropIfExists('cliente_contrato_vigencias');
    }
};
