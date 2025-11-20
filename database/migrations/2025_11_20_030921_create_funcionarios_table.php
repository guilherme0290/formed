<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('funcionarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            $table->string('nome');
            $table->string('cpf', 14)->nullable();
            $table->string('rg', 20)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->date('data_admissao')->nullable();
            $table->string('funcao')->nullable();

            $table->boolean('treinamento_nr')->default(false);

            // motivos de exame (checkbox)
            $table->boolean('exame_admissional')->default(false);
            $table->boolean('exame_periodico')->default(false);
            $table->boolean('exame_demissional')->default(false);
            $table->boolean('exame_mudanca_funcao')->default(false);
            $table->boolean('exame_retorno_trabalho')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcionarios');
    }
};
