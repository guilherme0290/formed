<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('anexos', function (Blueprint $t) {
            $t->id();
            $t->nullableMorphs('anexavel'); // tarefa, proposta, contrato, fatura...
            $t->string('nome');
            $t->string('caminho'); // storage path
            $t->string('mime')->nullable();
            $t->unsignedBigInteger('tamanho')->default(0);
            $t->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('anexos'); }
};
