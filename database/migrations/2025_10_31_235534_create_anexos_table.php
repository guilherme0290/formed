<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration {
    public function up(): void {
        Schema::create('anexos', function (Blueprint $t) {
            $t->id();

            // vínculo polimórfico: tarefa, proposta, contrato, etc.
            $t->nullableMorphs('anexavel');

            $t->string('nome');
            $t->string('caminho'); // storage path
            $t->string('mime')->nullable();
            $t->unsignedBigInteger('tamanho')->default(0);

            // usuário responsável pelo upload
            $t->foreignIdFor(User::class)
                ->nullable()
                ->constrained()      // usa automaticamente a tabela "users"
                ->nullOnDelete();

            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('anexos');
    }
};
