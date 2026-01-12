<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            if (Schema::hasColumn('propostas', 'vencimento_servicos')) {
                $table->dropColumn('vencimento_servicos');
            }
        });

        Schema::table('propostas', function (Blueprint $table) {
            if (!Schema::hasColumn('propostas', 'vencimento_servicos')) {
                $table->unsignedTinyInteger('vencimento_servicos')->nullable()->after('prazo_dias');
            }
        });
    }

    public function down(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            if (Schema::hasColumn('propostas', 'vencimento_servicos')) {
                $table->dropColumn('vencimento_servicos');
            }
        });

        Schema::table('propostas', function (Blueprint $table) {
            if (!Schema::hasColumn('propostas', 'vencimento_servicos')) {
                $table->date('vencimento_servicos')->nullable()->after('prazo_dias');
            }
        });
    }
};
