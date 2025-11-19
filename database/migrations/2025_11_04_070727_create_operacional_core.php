<?php // create_operacional_core.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('kanban_colunas', function (Blueprint $t) {
            $t->id();

            // adicione esta linha se ainda não existir
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $t->string('nome');
            $t->unsignedInteger('ordem')->default(0);
            $t->boolean('finaliza')->default(false);
            $t->timestamps();
        });

        Schema::create('tarefas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coluna_id')->constrained('kanban_colunas')->cascadeOnDelete();
            $t->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('titulo');
            $t->text('descricao')->nullable();
            $t->dateTime('inicio_previsto')->nullable();
            $t->dateTime('fim_previsto')->nullable();
            $t->dateTime('finalizado_em')->nullable();
            $t->timestamps();
            $t->index(['coluna_id','responsavel_id']);
        });

        Schema::create('tarefa_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('acao'); // criado, movido, editado, finalizado
            $t->json('dados')->nullable();
            $t->timestamps();
        });

        Schema::create('tarefa_checklists', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $t->string('titulo');
            $t->boolean('feito')->default(false);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('tarefa_checklists');
        Schema::dropIfExists('tarefa_logs');
        Schema::dropIfExists('tarefas');
        Schema::dropIfExists('kanban_colunas');
    }
};
