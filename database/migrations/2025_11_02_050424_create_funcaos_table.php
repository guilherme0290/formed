<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcoes', function (Blueprint $table) {
            $table->id();

            // multi-tenant
            $table->unsignedBigInteger('empresa_id');

            $table->string('nome');
            $table->string('cbo', 20)->nullable();        // código CBO se quiser usar
            $table->string('descricao', 500)->nullable(); // descrição opcional
            $table->boolean('ativo')->default(true);

            $table->timestamps();

            // índice/unique por empresa
            $table->foreign('empresa_id')
                ->references('id')
                ->on('empresas')
                ->onDelete('cascade');

            $table->unique(['empresa_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcoes');
    }
};
