<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aso_solicitacoes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('tarefa_id')->unique();
            $table->unsignedBigInteger('funcionario_id');
            $table->unsignedBigInteger('unidade_id');

            // campos específicos do ASO
            $table->string('tipo_aso', 50);          // admissional, periodico, ...
            $table->date('data_aso');
            $table->string('email_aso')->nullable();

            $table->boolean('vai_fazer_treinamento')->default(false);
            $table->json('treinamentos')->nullable(); // ["nr_12","nr_05"]

            $table->timestamps();

            // indexes / fks simples (pode refinar depois)
            $table->index('empresa_id');
            $table->index('cliente_id');
            $table->index('funcionario_id');
            $table->index('unidade_id');

            // se quiser FKs explícitas:
            // $table->foreign('tarefa_id')->references('id')->on('tarefas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aso_solicitacoes');
    }
};
