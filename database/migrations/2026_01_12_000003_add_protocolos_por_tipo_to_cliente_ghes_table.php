<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente_ghes', function (Blueprint $table) {
            $table->unsignedBigInteger('protocolo_admissional_id')->nullable()->after('protocolo_id');
            $table->unsignedBigInteger('protocolo_periodico_id')->nullable()->after('protocolo_admissional_id');
            $table->unsignedBigInteger('protocolo_demissional_id')->nullable()->after('protocolo_periodico_id');
            $table->unsignedBigInteger('protocolo_mudanca_funcao_id')->nullable()->after('protocolo_demissional_id');
            $table->unsignedBigInteger('protocolo_retorno_trabalho_id')->nullable()->after('protocolo_mudanca_funcao_id');

            $table->foreign('protocolo_admissional_id')->references('id')->on('protocolos_exames')->nullOnDelete();
            $table->foreign('protocolo_periodico_id')->references('id')->on('protocolos_exames')->nullOnDelete();
            $table->foreign('protocolo_demissional_id')->references('id')->on('protocolos_exames')->nullOnDelete();
            $table->foreign('protocolo_mudanca_funcao_id')->references('id')->on('protocolos_exames')->nullOnDelete();
            $table->foreign('protocolo_retorno_trabalho_id')->references('id')->on('protocolos_exames')->nullOnDelete();
        });

        DB::table('cliente_ghes')
            ->whereNotNull('protocolo_id')
            ->update([
                'protocolo_admissional_id' => DB::raw('protocolo_id'),
                'protocolo_periodico_id' => DB::raw('protocolo_id'),
                'protocolo_demissional_id' => DB::raw('protocolo_id'),
                'protocolo_mudanca_funcao_id' => DB::raw('protocolo_id'),
                'protocolo_retorno_trabalho_id' => DB::raw('protocolo_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('cliente_ghes', function (Blueprint $table) {
            $table->dropForeign(['protocolo_admissional_id']);
            $table->dropForeign(['protocolo_periodico_id']);
            $table->dropForeign(['protocolo_demissional_id']);
            $table->dropForeign(['protocolo_mudanca_funcao_id']);
            $table->dropForeign(['protocolo_retorno_trabalho_id']);

            $table->dropColumn([
                'protocolo_admissional_id',
                'protocolo_periodico_id',
                'protocolo_demissional_id',
                'protocolo_mudanca_funcao_id',
                'protocolo_retorno_trabalho_id',
            ]);
        });
    }
};
