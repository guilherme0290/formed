<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_clausulas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('servico_tipo', 40)->default('GERAL');
            $table->string('slug', 80);
            $table->string('titulo', 160);
            $table->unsignedInteger('ordem')->default(0);
            $table->longText('html_template');
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('versao')->default(1);
            $table->timestamps();

            $table->index(['empresa_id', 'servico_tipo']);
            $table->index(['empresa_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_clausulas');
    }
};
