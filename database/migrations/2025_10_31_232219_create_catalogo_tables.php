<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('servicos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->string('nome');
            $t->string('tipo')->nullable(); // Exame, Programa, Treinamento, Laudo...
            $t->string('esocial')->nullable(); // S-2220, S-2240, etc
            $t->decimal('valor_base', 12, 2)->default(0);
            $t->boolean('ativo')->default(true);
            $t->timestamps();
        });

        Schema::create('combos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->string('nome');
            $t->string('codigo')->nullable();
            $t->text('descricao')->nullable();
            $t->boolean('ativo')->default(true);
            $t->timestamps();
        });

        Schema::create('combo_itens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('combo_id')->constrained('combos')->cascadeOnDelete();
            $t->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
            $t->unsignedInteger('quantidade')->default(1);
            $t->timestamps();
            $t->unique(['combo_id','servico_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('combo_itens');
        Schema::dropIfExists('combos');
        Schema::dropIfExists('servicos');
    }
};
