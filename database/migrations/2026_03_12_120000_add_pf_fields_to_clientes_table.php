<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'tipo_pessoa')) {
                $table->string('tipo_pessoa', 2)->default('PJ')->after('vendedor_id');
            }

            if (!Schema::hasColumn('clientes', 'cpf')) {
                $table->string('cpf', 14)->nullable()->after('nome_fantasia');
            }
        });

        DB::table('clientes')
            ->whereNull('tipo_pessoa')
            ->update(['tipo_pessoa' => 'PJ']);

        Schema::table('clientes', function (Blueprint $table) {
            $table->index('cpf');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['cpf']);

            if (Schema::hasColumn('clientes', 'cpf')) {
                $table->dropColumn('cpf');
            }

            if (Schema::hasColumn('clientes', 'tipo_pessoa')) {
                $table->dropColumn('tipo_pessoa');
            }
        });
    }
};
