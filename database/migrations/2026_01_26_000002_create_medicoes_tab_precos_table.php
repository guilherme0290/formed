<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicoes_tab_preco', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->index();

            $table->string('titulo', 255);
            $table->string('descricao', 255)->nullable();
            $table->decimal('preco', 10, 2)->default(0);
            $table->boolean('ativo')->default(true);

            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
            $table->index(['empresa_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicoes_tab_preco');
    }
};
