<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('usuarios', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->string('nome');
            $t->string('email')->unique();
            $t->string('telefone')->nullable();
            $t->string('password');
            $t->boolean('ativo')->default(true);
            $t->timestamp('ultimo_acesso_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('usuarios'); }
};
