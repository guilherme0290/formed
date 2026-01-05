<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treinamento_nr_detalhes', function (Blueprint $table) {
            $table->json('treinamentos')->nullable()->after('unidade_id');
        });
    }

    public function down(): void
    {
        Schema::table('treinamento_nr_detalhes', function (Blueprint $table) {
            $table->dropColumn('treinamentos');
        });
    }
};
