<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->text('observacao_interna')->nullable()->after('descricao');
            $table->unsignedInteger('ordem')->default(0)->after('coluna_id');
        });
    }

    public function down(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->dropColumn('observacao_interna');
            $table->dropColumn('ordem');
        });
    }
};
