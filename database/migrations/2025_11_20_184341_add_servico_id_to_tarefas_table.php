<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            // se já existir, não cria de novo
            if (! Schema::hasColumn('tarefas', 'servico_id')) {
                $table->foreignId('servico_id')
                    ->nullable()
                    ->after('cliente_id')
                    ->constrained('servicos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            if (Schema::hasColumn('tarefas', 'servico_id')) {
                $table->dropForeign(['servico_id']);
                $table->dropColumn('servico_id');
            }
        });
    }
};
