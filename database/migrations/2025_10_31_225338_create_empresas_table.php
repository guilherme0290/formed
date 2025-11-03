<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('empresas', function (Blueprint $t) {
            $t->id();
            $t->string('razao_social');
            $t->string('nome_fantasia')->nullable();
            $t->string('cnpj', 18)->unique();
            $t->string('email')->nullable();
            $t->string('telefone')->nullable();
            $t->string('endereco')->nullable();
            $t->boolean('ativo')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('empresas'); }
};
