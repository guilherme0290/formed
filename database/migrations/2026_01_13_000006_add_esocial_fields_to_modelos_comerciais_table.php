<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modelos_comerciais', function (Blueprint $table) {
            $table->boolean('usar_todos_exames')->default(false)->after('rodape');
            $table->text('esocial_descricao')->nullable()->after('usar_todos_exames');
        });
    }

    public function down(): void
    {
        Schema::table('modelos_comerciais', function (Blueprint $table) {
            $table->dropColumn(['usar_todos_exames', 'esocial_descricao']);
        });
    }
};
