<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tabela_preco_items', function (Blueprint $t) {
            $t->unique(
                ['tabela_preco_padrao_id', 'servico_id', 'codigo'],
                'tp_items_unique_padrao_servico_codigo'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tabela_preco_items', function (Blueprint $t) {
            $t->dropUnique('tp_items_unique_padrao_servico_codigo');
        });
    }
};
