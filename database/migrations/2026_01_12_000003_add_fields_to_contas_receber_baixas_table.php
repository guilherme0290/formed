<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_receber_baixas', function (Blueprint $table) {
            $table->string('meio_pagamento', 80)->nullable()->after('pago_em');
            $table->text('observacao')->nullable()->after('meio_pagamento');
            $table->string('comprovante_path')->nullable()->after('observacao');
            $table->string('comprovante_nome')->nullable()->after('comprovante_path');
            $table->string('comprovante_mime', 120)->nullable()->after('comprovante_nome');
            $table->unsignedBigInteger('comprovante_tamanho')->nullable()->after('comprovante_mime');
        });
    }

    public function down(): void
    {
        Schema::table('contas_receber_baixas', function (Blueprint $table) {
            $table->dropColumn([
                'meio_pagamento',
                'observacao',
                'comprovante_path',
                'comprovante_nome',
                'comprovante_mime',
                'comprovante_tamanho',
            ]);
        });
    }
};
