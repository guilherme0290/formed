<?php // database/migrations/2025_01_01_000300_create_operacional_core.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('kanban_colunas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->string('nome');
            $t->unsignedInteger('ordem')->default(0);
            $t->boolean('finaliza')->default(false);
            $t->timestamps();
        });

        Schema::create('tarefas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $t->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $t->foreignId('coluna_id')->constrained('kanban_colunas')->cascadeOnDelete();
            $t->foreignId('responsavel_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $t->string('titulo');
            $t->text('descricao')->nullable();
            $t->enum('prioridade',['Baixa','Média','Alta','Crítica'])->default('Média');
            $t->unsignedInteger('sla_horas')->default(0);
            $t->dateTime('prazo')->nullable();
            $t->unsignedInteger('ordem')->default(0);
            $t->enum('status',['Ativa','Pausada','Concluida','Cancelada'])->default('Ativa');
            $t->timestamps();
            $t->index(['empresa_id','coluna_id','responsavel_id']);
        });

        Schema::create('tarefas_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $t->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $t->foreignId('coluna_origem_id')->nullable()->constrained('kanban_colunas')->nullOnDelete();
            $t->foreignId('coluna_destino_id')->nullable()->constrained('kanban_colunas')->nullOnDelete();
            $t->string('acao');
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        Schema::create('checklists', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $t->string('nome');
            $t->boolean('ativo')->default(true);
            $t->timestamps();
        });

        Schema::create('checklist_itens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('checklist_id')->constrained('checklists')->cascadeOnDelete();
            $t->string('descricao');
            $t->unsignedInteger('ordem')->default(0);
            $t->timestamps();
        });

        Schema::create('tarefa_checklist_itens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $t->foreignId('checklist_item_id')->constrained('checklist_itens')->cascadeOnDelete();
            $t->boolean('feito')->default(false);
            $t->timestamp('feito_em')->nullable();
            $t->foreignId('feito_por_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $t->timestamps();
            $t->unique(['tarefa_id','checklist_item_id']);
        });

        Schema::create('anexos', function (Blueprint $t) {
            $t->id();
            $t->nullableMorphs('anexavel');
            $t->string('nome');
            $t->string('caminho');
            $t->string('mime')->nullable();
            $t->unsignedBigInteger('tamanho')->default(0);
            $t->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('anexos');
        Schema::dropIfExists('tarefa_checklist_itens');
        Schema::dropIfExists('checklist_itens');
        Schema::dropIfExists('checklists');
        Schema::dropIfExists('tarefas_logs');
        Schema::dropIfExists('tarefas');
        Schema::dropIfExists('kanban_colunas');
    }
};
