<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aso_solicitacoes', function (Blueprint $table) {
            $table->json('treinamento_pacote')->nullable()->after('treinamentos');
        });
    }

    public function down(): void
    {
        Schema::table('aso_solicitacoes', function (Blueprint $table) {
            $table->dropColumn('treinamento_pacote');
        });
    }
};
