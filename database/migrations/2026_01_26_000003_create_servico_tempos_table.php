<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servico_tempos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('servico_id');
            $table->unsignedInteger('tempo_minutos')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'servico_id']);
            $table->index(['empresa_id', 'servico_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servico_tempos');
    }
};
