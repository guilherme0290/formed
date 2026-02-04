<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghe_funcoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ghe_id');
            $table->unsignedBigInteger('funcao_id');
            $table->timestamps();

            $table->unique(['ghe_id', 'funcao_id']);
            $table->foreign('ghe_id')->references('id')->on('ghes')->cascadeOnDelete();
            $table->foreign('funcao_id')->references('id')->on('funcoes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghe_funcoes');
    }
};
