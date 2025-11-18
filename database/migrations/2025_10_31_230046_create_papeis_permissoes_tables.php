<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('papeis', function (Blueprint $t) {
            $t->id();
            $t->string('nome');
            $t->string('descricao')->nullable();
            $t->boolean('ativo')->default(true);
            $t->timestamps();
        });

        Schema::create('permissoes', function (Blueprint $t) {
            $t->id();
            $t->string('chave')->unique(); // ex: comercial.propostas.view
            $t->string('nome');
            $t->string('escopo')->nullable(); // Comercial, Operacional, etc.
            $t->timestamps();
        });

        Schema::create('papel_permissao', function (Blueprint $t) {
            $t->id();
            $t->foreignId('papel_id')->constrained('papeis')->cascadeOnDelete();
            $t->foreignId('permissao_id')->constrained('permissoes')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['papel_id', 'permissao_id']);
        });

        // Pivot entre usuários e papéis
        Schema::create('papel_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('papel_id')->constrained('papeis')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'papel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('papel_user');
        Schema::dropIfExists('papel_permissao');
    }
};
