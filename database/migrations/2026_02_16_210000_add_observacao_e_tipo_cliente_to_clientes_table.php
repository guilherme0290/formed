<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'observacao')) {
                $table->text('observacao')->nullable()->after('contato');
            }

            if (!Schema::hasColumn('clientes', 'tipo_cliente')) {
                $table->string('tipo_cliente', 20)->default('final')->after('observacao');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'tipo_cliente')) {
                $table->dropColumn('tipo_cliente');
            }

            if (Schema::hasColumn('clientes', 'observacao')) {
                $table->dropColumn('observacao');
            }
        });
    }
};
