<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tabela_preco_items', function (Blueprint $table) {
            if (!Schema::hasColumn('tabela_preco_items', 'tabela_preco_padrao_id')) {
                $table->foreignId('tabela_preco_padrao_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tabela_precos_padrao')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tabela_preco_items', function (Blueprint $table) {
            if (Schema::hasColumn('tabela_preco_items', 'tabela_preco_padrao_id')) {
                $table->dropConstrainedForeignId('tabela_preco_padrao_id');
            }
        });
    }
};
