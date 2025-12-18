<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('funcionario_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('funcionario_id');

            $table->string('tipo', 50); // aso, pgr, pcmso, treinamento, certificado, outro...
            $table->string('titulo')->nullable(); // ex: "ASO Admissional 2025"
            $table->string('arquivo_path');       // storage path (disk public ou outro)
            $table->date('valido_ate')->nullable();
            $table->text('observacoes')->nullable();

            $table->timestamps();

            $table->foreign('funcionario_id')
                ->references('id')
                ->on('funcionarios')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcionario_documentos');
    }
};
