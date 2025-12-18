<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esocial_faixas_tab_preco', function (Blueprint $table) {
            $table->id();

            // Dono da faixa (multi-tenant)
            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnDelete();

            // Vincula na tabela padrão (pra ficar consistente com seu modelo atual)
            $table->foreignId('tabela_preco_padrao_id')
                ->nullable()
                ->constrained('tabela_precos_padrao')
                ->nullOnDelete();

            // Range de colaboradores
            $table->unsignedInteger('inicio');       // ex: 1
            $table->unsignedInteger('fim')->nullable(); // ex: 10 | null = "Acima de X"

            // Texto livre (ex: "01 até 10 colaboradores")
            $table->string('descricao', 255)->nullable();

            $table->decimal('preco', 12, 2)->default(0);

            $table->boolean('ativo')->default(true);

            $table->timestamps();

            // Índices úteis (busca por faixa)
            $table->index(['empresa_id', 'ativo']);
            $table->index(['tabela_preco_padrao_id', 'ativo']);
            $table->index(['inicio', 'fim']);

            // Opcional: evita duplicar exatamente a mesma faixa dentro da mesma tabela padrão
            // (NÃO impede sobreposição; isso deve ser validado no backend)
            $table->unique(['tabela_preco_padrao_id', 'inicio', 'fim'], 'uq_esocial_faixa_padrao_inicio_fim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esocial_faixas_tab_preco');
    }
};
