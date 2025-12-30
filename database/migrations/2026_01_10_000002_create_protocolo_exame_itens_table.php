<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocolo_exame_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocolo_id')
                ->constrained('protocolos_exames')
                ->cascadeOnDelete();
            $table->foreignId('exame_id')
                ->constrained('exames_tab_preco')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['protocolo_id', 'exame_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocolo_exame_itens');
    }
};
