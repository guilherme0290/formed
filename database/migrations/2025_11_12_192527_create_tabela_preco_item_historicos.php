<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tabela_preco_item_historicos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('tabela_preco_items')->cascadeOnDelete();
            $t->decimal('preco_anterior', 12, 2);
            $t->decimal('preco_novo', 12, 2);
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // quem alterou
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('tabela_preco_item_historicos');
    }
};
