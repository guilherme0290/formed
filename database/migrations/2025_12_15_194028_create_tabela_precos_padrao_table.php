<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tabela_precos_padrao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nome')->default('Tabela Padrão');
            $table->boolean('ativa')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'ativa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabela_precos_padrao');
    }
};
