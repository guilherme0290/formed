<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            if (!Schema::hasColumn('tarefas', 'motivo_exclusao')) {
                $table->text('motivo_exclusao')->nullable()->after('observacao_interna');
            }
            if (!Schema::hasColumn('tarefas', 'excluido_por')) {
                $table->foreignId('excluido_por')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('motivo_exclusao');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            if (Schema::hasColumn('tarefas', 'excluido_por')) {
                $table->dropForeign(['excluido_por']);
                $table->dropColumn('excluido_por');
            }
            if (Schema::hasColumn('tarefas', 'motivo_exclusao')) {
                $table->dropColumn('motivo_exclusao');
            }
        });
    }
};
