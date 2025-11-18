<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tarefas')) {
            Schema::table('tarefas', function (Blueprint $t) {

                // fk da empresa
                if (!Schema::hasColumn('tarefas','empresa_id')) {
                    $t->foreignId('empresa_id')
                        ->after('id')
                        ->constrained('empresas')
                        ->cascadeOnDelete();
                }

                // fk da coluna (kanban)
                if (!Schema::hasColumn('tarefas','coluna_id')) {
                    $t->foreignId('coluna_id')
                        ->nullable()
                        ->after('empresa_id')
                        ->constrained('kanban_colunas')
                        ->nullOnDelete();
                }

                // fk do responsável (usuário)
                if (!Schema::hasColumn('tarefas','responsavel_id')) {
                    $t->foreignId('responsavel_id')
                        ->nullable()
                        ->after('coluna_id')
                        ->constrained('users')
                        ->nullOnDelete();
                }

                // campos complementares usados pelo seeder
                if (!Schema::hasColumn('tarefas','titulo')) {
                    $t->string('titulo')->after('responsavel_id');
                }
                if (!Schema::hasColumn('tarefas','descricao')) {
                    $t->text('descricao')->nullable()->after('titulo');
                }
                if (!Schema::hasColumn('tarefas','prioridade')) {
                    $t->string('prioridade', 20)->default('Normal')->after('descricao');
                }
                if (!Schema::hasColumn('tarefas','status')) {
                    $t->string('status', 20)->default('Ativa')->after('prioridade');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tarefas')) {
            Schema::table('tarefas', function (Blueprint $t) {
                foreach (['status','prioridade','descricao','titulo','responsavel_id','coluna_id','empresa_id'] as $col) {
                    if (Schema::hasColumn('tarefas', $col)) {
                        if (in_array($col, ['empresa_id','coluna_id','responsavel_id'])) {
                            $t->dropConstrainedForeignId($col);
                        } else {
                            $t->dropColumn($col);
                        }
                    }
                }
            });
        }
    }
};
