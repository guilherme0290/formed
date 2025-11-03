<?php // 2025_01_01_000110_create_clientes_tabela_precos_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clientes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->string('razao_social');
            $t->string('nome_fantasia')->nullable();
            $t->string('cnpj', 18)->nullable()->index();
            $t->string('email')->nullable();
            $t->string('telefone')->nullable();
            $t->string('endereco')->nullable();
            $t->boolean('ativo')->default(true);
            $t->timestamps();
        });

        Schema::create('tabela_precos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $t->enum('tipo', ['servico', 'combo']);
            $t->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $t->foreignId('combo_id')->nullable()->constrained('combos')->nullOnDelete();
            $t->string('codigo')->nullable();
            $t->text('descricao')->nullable();
            $t->decimal('preco', 12, 2);
            $t->date('vigencia_inicio');
            $t->date('vigencia_fim')->nullable();
            $t->boolean('ativo')->default(true);
            $t->timestamps();

            $t->index(
                ['empresa_id', 'cliente_id', 'tipo', 'servico_id', 'combo_id', 'vigencia_inicio'],
                'idx_tp_busca'
            );
        });
    }
    public function down(): void {
        Schema::dropIfExists('tabela_precos');
        Schema::dropIfExists('clientes');
    }
};
