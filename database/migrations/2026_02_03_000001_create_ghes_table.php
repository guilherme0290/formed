<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('nome');
            $table->unsignedBigInteger('grupo_exames_id')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'ativo']);
            $table->foreign('grupo_exames_id')
                ->references('id')
                ->on('protocolos_exames')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghes');
    }
};
