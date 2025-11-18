<?php // create_servicos_tabelas_preco.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
                Schema::create('tabelas_preco', function (Blueprint $t) {
            $t->id();
            $t->string('nome');
            $t->boolean('ativa')->default(true);
            $t->timestamps();
        });

        Schema::create('tabela_preco_itens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tabela_preco_id')->constrained('tabelas_preco')->cascadeOnDelete();
            $t->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
            $t->decimal('valor', 12, 2);
            $t->string('unidade')->default('servico'); // ex: servico, hora, unidade
            $t->timestamps();
            $t->unique(['tabela_preco_id','servico_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('tabela_preco_itens');
        Schema::dropIfExists('tabelas_preco');
        Schema::dropIfExists('servicos');
    }
};
