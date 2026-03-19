<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exame_toxicologico_solicitacoes', function (Blueprint $table) {
            $table->unsignedBigInteger('funcionario_id')->nullable()->after('tarefa_id');
            $table->foreign('funcionario_id')->references('id')->on('funcionarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exame_toxicologico_solicitacoes', function (Blueprint $table) {
            $table->dropForeign(['funcionario_id']);
            $table->dropColumn('funcionario_id');
        });
    }
};
