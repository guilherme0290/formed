<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();

            // Identificação básica
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();

            // Autenticação
            $t->string('password');
            $t->rememberToken();

            // Campos adicionais úteis no seu fluxo
            $t->string('telefone')->nullable();
            $t->string('documento')->nullable();   // CPF/CNPJ ou similar
            $t->string('avatar')->nullable();      // caminho/URL de foto

            // Status/telemetria
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_login_at')->nullable();

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
