<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ltip_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('tarefa_id')->nullable();
            $table->unsignedBigInteger('responsavel_id')->nullable();

            $table->string('endereco_avaliacoes', 1000);
            $table->json('funcoes');
            $table->unsignedInteger('total_funcionarios')->default(0);

            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
            $table->foreign('tarefa_id')->references('id')->on('tarefas')->nullOnDelete();
            $table->foreign('responsavel_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ltip_solicitacoes');
    }
};
