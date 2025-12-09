<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposta_itens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('proposta_id')
                ->constrained('propostas')
                ->cascadeOnDelete();

            $table->foreignId('servico_id')
                ->nullable()
                ->constrained('servicos')
                ->nullOnDelete();

            // servico | treinamento | pacote_exames | esocial | outro
            $table->string('tipo')->default('servico');

            $table->string('nome');             // nome exibido na proposta
            $table->text('descricao')->nullable();

            $table->decimal('valor_unitario', 10, 2)->default(0);
            $table->unsignedInteger('quantidade')->default(1);
            $table->string('prazo')->nullable(); // Ex: "15 dias"

            $table->decimal('valor_total', 10, 2)->default(0);

            // para guardar composição de pacotes (exames, treinamentos, etc.)
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposta_itens');
    }
};
