<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contrato_clausulas')) {
            return;
        }

        Schema::create('contrato_clausulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('servico_tipo', 40)->default('GERAL');
            $table->string('slug', 80);
            $table->string('titulo', 160);
            $table->unsignedInteger('ordem')->default(0);
            $table->longText('html_template');
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('versao')->default(1);
            $table->timestamps();

            $table->unique(['empresa_id', 'slug']);
            $table->index(['empresa_id', 'servico_tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_clausulas');
    }
};
