<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exame_toxicologico_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('tarefa_id')->unique();
            $table->unsignedBigInteger('responsavel_id')->nullable();
            $table->unsignedBigInteger('unidade_id');

            $table->string('tipo_exame', 50);
            $table->string('nome_completo');
            $table->string('cpf', 20);
            $table->string('rg', 30);
            $table->date('data_nascimento');
            $table->string('telefone', 30);
            $table->string('email_envio');
            $table->date('data_realizacao');

            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
            $table->foreign('tarefa_id')->references('id')->on('tarefas')->cascadeOnDelete();
            $table->foreign('responsavel_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('unidade_id')->references('id')->on('unidades_clinicas')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exame_toxicologico_solicitacoes');
    }
};
