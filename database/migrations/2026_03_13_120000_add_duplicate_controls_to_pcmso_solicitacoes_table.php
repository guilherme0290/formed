<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pcmso_solicitacoes', function (Blueprint $table) {
            $table->string('pgr_arquivo_nome')->nullable()->after('pgr_arquivo_path');
            $table->string('pgr_arquivo_checksum', 64)->nullable()->after('pgr_arquivo_nome');
            $table->boolean('duplicidade_confirmada')->default(false)->after('prazo_dias');
            $table->foreignId('duplicidade_confirmada_por')
                ->nullable()
                ->after('duplicidade_confirmada')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('duplicidade_confirmada_em')->nullable()->after('duplicidade_confirmada_por');
            $table->foreignId('duplicidade_referencia_tarefa_id')
                ->nullable()
                ->after('duplicidade_confirmada_em')
                ->constrained('tarefas')
                ->nullOnDelete();

            $table->index(['cliente_id', 'tipo', 'pgr_arquivo_checksum'], 'pcmso_cliente_tipo_checksum_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pcmso_solicitacoes', function (Blueprint $table) {
            $table->dropIndex('pcmso_cliente_tipo_checksum_idx');
            $table->dropConstrainedForeignId('duplicidade_referencia_tarefa_id');
            $table->dropColumn('duplicidade_confirmada_em');
            $table->dropConstrainedForeignId('duplicidade_confirmada_por');
            $table->dropColumn('duplicidade_confirmada');
            $table->dropColumn('pgr_arquivo_checksum');
            $table->dropColumn('pgr_arquivo_nome');
        });
    }
};
