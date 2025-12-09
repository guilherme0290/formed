<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // A ordem importa: primeiro as tabelas "filhas"
        Schema::dropIfExists('comissoes');
        Schema::dropIfExists('contrato_itens');

        Schema::dropIfExists('proposta_itens');
        Schema::dropIfExists('propostas');

        // Reabilita as FKs
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
