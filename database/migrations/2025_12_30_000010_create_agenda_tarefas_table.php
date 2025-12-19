<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_tarefas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('tipo')->default('Tarefa'); // Retorno Cliente, Reunião, Follow-up, Tarefa, Outro
            $table->string('prioridade')->default('Media'); // Baixa, Media, Alta
            $table->date('data');
            $table->time('hora')->nullable();
            $table->string('cliente')->nullable();
            $table->string('status')->default('PENDENTE'); // PENDENTE | CONCLUIDA
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'data', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_tarefas');
    }
};
