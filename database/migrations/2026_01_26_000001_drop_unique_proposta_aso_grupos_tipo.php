<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposta_aso_grupos', function (Blueprint $table) {
            $table->dropUnique('proposta_aso_grupos_proposta_id_tipo_aso_unique');
        });
    }

    public function down(): void
    {
        Schema::table('proposta_aso_grupos', function (Blueprint $table) {
            $table->unique(['proposta_id', 'tipo_aso']);
        });
    }
};
