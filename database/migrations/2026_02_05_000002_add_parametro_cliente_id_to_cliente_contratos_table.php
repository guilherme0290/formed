<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente_contratos', function (Blueprint $table) {
            $table->dropForeign(['proposta_id_origem']);
        });

        Schema::table('cliente_contratos', function (Blueprint $table) {
            $table->unsignedBigInteger('proposta_id_origem')->nullable()->change();
            $table->foreign('proposta_id_origem')->references('id')->on('propostas')->nullOnDelete();
            $table->foreignId('parametro_cliente_id_origem')
                ->nullable()
                ->constrained('parametro_clientes')
                ->nullOnDelete()
                ->after('proposta_id_origem');
        });
    }

    public function down(): void
    {
        Schema::table('cliente_contratos', function (Blueprint $table) {
            $table->dropForeign(['parametro_cliente_id_origem']);
            $table->dropColumn('parametro_cliente_id_origem');
        });

        Schema::table('cliente_contratos', function (Blueprint $table) {
            $table->dropForeign(['proposta_id_origem']);
            $table->unsignedBigInteger('proposta_id_origem')->nullable(false)->change();
            $table->foreign('proposta_id_origem')->references('id')->on('propostas')->cascadeOnDelete();
        });
    }
};
