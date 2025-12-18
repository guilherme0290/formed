<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tabela_preco_items', function (Blueprint $t) {

            $t->index('servico_id', 'tp_items_servico_id_idx');
            $t->dropUnique('tabela_preco_items_servico_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tabela_preco_items', function (Blueprint $t) {
            $t->unique(['servico_id'], 'tabela_preco_items_servico_id_unique');
            $t->dropIndex('tp_items_servico_id_idx');
        });
    }
};
