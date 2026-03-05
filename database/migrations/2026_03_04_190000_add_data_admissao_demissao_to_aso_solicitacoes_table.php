<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aso_solicitacoes', function (Blueprint $table) {
            if (!Schema::hasColumn('aso_solicitacoes', 'data_admissao')) {
                $table->date('data_admissao')->nullable()->after('tipo_aso');
            }

            if (!Schema::hasColumn('aso_solicitacoes', 'data_demissao')) {
                $table->date('data_demissao')->nullable()->after('data_admissao');
            }
        });
    }

    public function down(): void
    {
        Schema::table('aso_solicitacoes', function (Blueprint $table) {
            if (Schema::hasColumn('aso_solicitacoes', 'data_demissao')) {
                $table->dropColumn('data_demissao');
            }

            if (Schema::hasColumn('aso_solicitacoes', 'data_admissao')) {
                $table->dropColumn('data_admissao');
            }
        });
    }
};
