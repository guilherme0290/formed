<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('tabela_preco_item_historicos');
    }

    public function down(): void
    {
        // não recria (você pediu para excluir)
    }
};
