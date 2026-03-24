<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('protocolos_exames', function (Blueprint $table) {
            $table->foreignId('cliente_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('clientes')
                ->cascadeOnDelete();

            $table->index(['empresa_id', 'cliente_id', 'ativo'], 'protocolos_exames_empresa_cliente_ativo_idx');
        });
    }

    public function down(): void
    {
        Schema::table('protocolos_exames', function (Blueprint $table) {
            $table->dropIndex('protocolos_exames_empresa_cliente_ativo_idx');
            $table->dropConstrainedForeignId('cliente_id');
        });
    }
};
