<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('treinamento_nrs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tarefa_id')
                ->constrained('tarefas')
                ->cascadeOnDelete();

            $table->foreignId('funcionario_id')
                ->constrained('funcionarios') // ajuste se o nome da tabela for outro
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treinamento_nrs');
    }
};
