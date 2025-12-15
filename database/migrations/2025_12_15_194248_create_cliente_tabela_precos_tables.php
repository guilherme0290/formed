<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cliente_tabela_precos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();

            $table->foreignId('origem_proposta_id')->nullable()->constrained('propostas')->nullOnDelete();

            $table->dateTime('vigencia_inicio');
            $table->dateTime('vigencia_fim')->nullable(); // null = ativa
            $table->boolean('ativa')->default(true);

            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'ativa']);
            $table->index(['cliente_id', 'vigencia_inicio', 'vigencia_fim'], 'ctp_cli_vig_idx');
        });

        Schema::create('cliente_tabela_preco_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_tabela_preco_id')->constrained('cliente_tabela_precos')->cascadeOnDelete();

            $table->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $table->string('tipo');      // genérico (ASO, TREINAMENTO, DOCUMENTO, ESOCIAL etc.)
            $table->string('codigo')->nullable(); // opcional (ex: ASO_ALTURA, NR35)
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('valor_unitario', 12, 2)->default(0);
            $table->json('meta')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['cliente_tabela_preco_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_tabela_preco_itens');
        Schema::dropIfExists('cliente_tabela_precos');
    }
};
