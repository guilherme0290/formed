<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_instancias', function (Blueprint $table) {
            $table->string('tipo', 40)->default('operacional')->after('empresa_id');
        });

        DB::table('whatsapp_instancias')
            ->whereNull('tipo')
            ->update(['tipo' => 'operacional']);

        Schema::table('whatsapp_instancias', function (Blueprint $table) {
            $table->dropUnique('whatsapp_instancias_empresa_id_unique');
            $table->unique(['empresa_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_instancias', function (Blueprint $table) {
            $table->dropUnique('whatsapp_instancias_empresa_id_tipo_unique');
            $table->unique('empresa_id');
            $table->dropColumn('tipo');
        });
    }
};
