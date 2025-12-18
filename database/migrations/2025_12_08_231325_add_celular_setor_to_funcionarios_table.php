<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            // depois do CPF
            $table->string('celular', 20)
                ->nullable()
                ->after('cpf');

            // depois da função (id)
            $table->string('setor', 100)
                ->nullable()
                ->after('funcao_id');
        });
    }

    public function down(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropColumn('celular');
            $table->dropColumn('setor');
        });
    }
};
