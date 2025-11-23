<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pae_solicitacoes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('tarefa_id')->nullable();
            $table->unsignedBigInteger('responsavel_id')->nullable();

            $table->text('endereco_local');
            $table->integer('total_funcionarios');
            $table->text('descricao_instalacoes');

            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->foreign('tarefa_id')->references('id')->on('tarefas');
            $table->foreign('responsavel_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_solicitacoes');
    }
};
