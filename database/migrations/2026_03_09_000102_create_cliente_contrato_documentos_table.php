<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cliente_contrato_documentos')) {
            return;
        }

        Schema::create('cliente_contrato_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('cliente_contrato_id')->constrained('cliente_contratos')->cascadeOnDelete();
            $table->string('status', 30)->default('RASCUNHO');
            $table->longText('html')->nullable();
            $table->longText('html_original')->nullable();
            $table->json('clausulas_snapshot')->nullable();
            $table->foreignId('gerado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('atualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('cliente_contrato_id');
            $table->index(['empresa_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_contrato_documentos');
    }
};
