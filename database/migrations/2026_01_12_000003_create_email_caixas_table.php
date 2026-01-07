<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_caixas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nome', 120);
            $table->string('email');
            $table->string('nome_remetente')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('host');
            $table->unsignedInteger('porta');
            $table->string('criptografia', 10);
            $table->unsignedInteger('timeout')->nullable();
            $table->boolean('requer_autenticacao')->default(true);
            $table->string('usuario')->nullable();
            $table->text('senha')->nullable();
            $table->boolean('ativo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['empresa_id', 'email']);
            $table->index(['empresa_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('email_caixas');
        Schema::enableForeignKeyConstraints();
    }
};
