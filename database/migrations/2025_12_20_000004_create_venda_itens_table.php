<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venda_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venda_id')->constrained('vendas')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
            $table->string('descricao_snapshot', 500)->nullable();
            $table->decimal('preco_unitario_snapshot', 12, 2)->default(0);
            $table->unsignedInteger('quantidade')->default(1);
            $table->decimal('subtotal_snapshot', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('venda_itens');
        Schema::enableForeignKeyConstraints();
    }
};
