<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('tipo_pessoa', 2)->default('PJ');
            $table->string('cpf_cnpj', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('contato_nome')->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'cpf_cnpj']);
            $table->index(['empresa_id', 'razao_social']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedores');
    }
};
