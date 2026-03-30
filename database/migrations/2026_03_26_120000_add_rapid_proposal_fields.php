<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            if (!Schema::hasColumn('propostas', 'tipo_modelo')) {
                $table->string('tipo_modelo', 20)->nullable()->after('codigo');
            }

            if (!Schema::hasColumn('propostas', 'valor_bruto')) {
                $table->decimal('valor_bruto', 12, 2)->default(0)->after('esocial_valor_mensal');
            }

            if (!Schema::hasColumn('propostas', 'desconto_percentual')) {
                $table->decimal('desconto_percentual', 5, 2)->default(0)->after('valor_bruto');
            }

            if (!Schema::hasColumn('propostas', 'desconto_valor')) {
                $table->decimal('desconto_valor', 12, 2)->default(0)->after('desconto_percentual');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'proposta_desconto_max_percentual')) {
                $table->decimal('proposta_desconto_max_percentual', 5, 2)->default(0)->after('telefone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            if (Schema::hasColumn('propostas', 'desconto_valor')) {
                $table->dropColumn('desconto_valor');
            }

            if (Schema::hasColumn('propostas', 'desconto_percentual')) {
                $table->dropColumn('desconto_percentual');
            }

            if (Schema::hasColumn('propostas', 'valor_bruto')) {
                $table->dropColumn('valor_bruto');
            }

            if (Schema::hasColumn('propostas', 'tipo_modelo')) {
                $table->dropColumn('tipo_modelo');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'proposta_desconto_max_percentual')) {
                $table->dropColumn('proposta_desconto_max_percentual');
            }
        });
    }
};
