<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente_contratos', function (Blueprint $table) {
            if (!Schema::hasColumn('cliente_contratos', 'vencimento_servicos')) {
                $table->unsignedTinyInteger('vencimento_servicos')
                    ->nullable()
                    ->after('vigencia_fim');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cliente_contratos', function (Blueprint $table) {
            if (Schema::hasColumn('cliente_contratos', 'vencimento_servicos')) {
                $table->dropColumn('vencimento_servicos');
            }
        });
    }
};
