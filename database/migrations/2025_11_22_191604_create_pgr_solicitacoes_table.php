<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pgr_solicitacoes', function (Blueprint $table) {
            $table->id();

            // chaves de referência
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('tarefa_id')->unique(); // 1 tarefa -> 1 PGR

            // tipo do PGR
            $table->enum('tipo', ['matriz', 'especifico']);

            // ART
            $table->boolean('com_art')->default(false);
            $table->decimal('valor_art', 10, 2)->nullable();

            // trabalhadores
            $table->unsignedInteger('qtd_homens')->default(0);
            $table->unsignedInteger('qtd_mulheres')->default(0);
            $table->unsignedInteger('total_trabalhadores')->default(0);

            // lista de funções/cargos (JSON)
            // Ex: [{"nome":"Carpinteiro","quantidade":1,"descricao":"...","cbo":"0000-00"}, ...]
            $table->json('funcoes')->nullable();

            // se também terá PCMSO usando os mesmos dados
            $table->boolean('com_pcms0')->default(false);

            // 2. CONTRATANTE (somente PGR específico)
            $table->string('contratante_nome')->nullable();
            $table->string('contratante_cnpj', 20)->nullable();

            // 3. OBRA (somente PGR específico)
            $table->string('obra_nome')->nullable();
            $table->string('obra_endereco')->nullable();
            $table->string('obra_cej_cno')->nullable();
            $table->string('obra_turno_trabalho')->nullable();

            $table->timestamps();

            // FKs (ajuste nomes de tabelas se forem diferentes)
            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
            $table->foreign('tarefa_id')->references('id')->on('tarefas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pgr_solicitacoes');
    }
};
