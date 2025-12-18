<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proposta_itens', function (Blueprint $table) {
            if (!Schema::hasColumn('proposta_itens', 'acrescimo')) {
                $table->decimal('acrescimo', 12, 2)->default(0)->after('valor_unitario');
            }
            if (!Schema::hasColumn('proposta_itens', 'desconto')) {
                $table->decimal('desconto', 12, 2)->default(0)->after('acrescimo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proposta_itens', function (Blueprint $table) {
            if (Schema::hasColumn('proposta_itens', 'acrescimo')) $table->dropColumn('acrescimo');
            if (Schema::hasColumn('proposta_itens', 'desconto'))  $table->dropColumn('desconto');
        });
    }
};
