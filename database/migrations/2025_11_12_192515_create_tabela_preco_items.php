<?php

// database/migrations/2025_01_01_000900_create_tabela_preco_items.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tabela_preco_items', function (Blueprint $t) {
            $t->id();
            // global (sem empresa_id), mas se quiser multi-empresa futuramente, adicione aqui
            $t->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
            $t->string('codigo')->nullable();
            $t->text('descricao')->nullable();
            $t->decimal('preco', 12, 2)->default(0);
            $t->boolean('ativo')->default(true);
            $t->timestamps();
            $t->unique(['servico_id']); // 1 preço por serviço (global). Remova se quiser múltiplas linhas por serviço.
        });
    }
    public function down(): void {
        Schema::dropIfExists('tabela_preco_items');
    }
};
