<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $t) {

            // REMOVER COLUNA ERRADA
            if (Schema::hasColumn('clientes', 'nome')) {
                $t->dropColumn('nome');
            }

            // ADICIONAR COLUNAS CORRETAS
            if (!Schema::hasColumn('clientes', 'razao_social')) {
                $t->string('razao_social')->after('empresa_id');
            }

            if (!Schema::hasColumn('clientes', 'nome_fantasia')) {
                $t->string('nome_fantasia')->nullable()->after('razao_social');
            }

            if (!Schema::hasColumn('clientes', 'cep')) {
                $t->string('cep', 10)->nullable()->after('telefone');
            }

            if (!Schema::hasColumn('clientes', 'numero')) {
                $t->string('numero', 30)->nullable()->after('endereco');
            }

            if (!Schema::hasColumn('clientes', 'bairro')) {
                $t->string('bairro', 120)->nullable()->after('numero');
            }

            if (!Schema::hasColumn('clientes', 'complemento')) {
                $t->string('complemento', 120)->nullable()->after('bairro');
            }

            if (!Schema::hasColumn('clientes', 'cidade_id')) {
                $t->foreignId('cidade_id')
                    ->nullable()
                    ->after('complemento')
                    ->constrained('cidades')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $t) {

            // reverter caso necessário
            if (Schema::hasColumn('clientes', 'razao_social')) {
                $t->dropColumn('razao_social');
            }

            if (Schema::hasColumn('clientes', 'nome_fantasia')) {
                $t->dropColumn('nome_fantasia');
            }

            if (Schema::hasColumn('clientes', 'cep')) {
                $t->dropColumn('cep');
            }

            if (Schema::hasColumn('clientes', 'numero')) {
                $t->dropColumn('numero');
            }

            if (Schema::hasColumn('clientes', 'bairro')) {
                $t->dropColumn('bairro');
            }

            if (Schema::hasColumn('clientes', 'complemento')) {
                $t->dropColumn('complemento');
            }

            if (Schema::hasColumn('clientes', 'cidade_id')) {
                $t->dropConstrainedForeignId('cidade_id');
            }

            // recolocar a coluna antiga caso precise
            if (!Schema::hasColumn('clientes', 'nome')) {
                $t->string('nome')->nullable();
            }
        });
    }
};
