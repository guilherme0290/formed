<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aso_solicitacoes', function (Blueprint $table) {
            if (!Schema::hasColumn('aso_solicitacoes', 'pcmso_elaborado_formed')) {
                $table->boolean('pcmso_elaborado_formed')
                    ->default(true)
                    ->after('email_aso');
            }

            if (!Schema::hasColumn('aso_solicitacoes', 'pcmso_externo_anexo_id')) {
                $table->foreignId('pcmso_externo_anexo_id')
                    ->nullable()
                    ->after('pcmso_elaborado_formed')
                    ->constrained('anexos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('aso_solicitacoes', function (Blueprint $table) {
            if (Schema::hasColumn('aso_solicitacoes', 'pcmso_externo_anexo_id')) {
                $table->dropConstrainedForeignId('pcmso_externo_anexo_id');
            }

            if (Schema::hasColumn('aso_solicitacoes', 'pcmso_elaborado_formed')) {
                $table->dropColumn('pcmso_elaborado_formed');
            }
        });
    }
};
