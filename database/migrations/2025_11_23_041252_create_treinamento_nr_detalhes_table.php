<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treinamento_nr_detalhes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tarefa_id')
                ->constrained('tarefas')
                ->cascadeOnDelete();

            // clinica | empresa (in company)
            $table->enum('local_tipo', ['clinica', 'empresa']);

            // obrigatório só se local_tipo = clinica (regra na validação)
            $table->foreignId('unidade_id')
                ->nullable()
                ->constrained('unidades_clinicas') // ajuste o nome se for diferente
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treinamento_nr_detalhes');
    }
};
