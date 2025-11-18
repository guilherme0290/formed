<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Adiciona 'endereco' se não existir
        Schema::table('empresas', function (Blueprint $t) {
            if (!Schema::hasColumn('empresas', 'endereco')) {
                // Coloco depois de 'nome' (que existe), mas pode tirar o ->after('nome') se quiser
                $t->string('endereco')->nullable()->after('nome');
            }
        });

        // Adiciona 'cidade_id' sem depender de 'after endereco'
        Schema::table('empresas', function (Blueprint $t) {
            if (!Schema::hasColumn('empresas', 'cidade_id')) {
                $t->unsignedBigInteger('cidade_id')->nullable(); // sem 'after', para não quebrar
            }
        });

        // (Opcional) FK, só se a tabela 'cidades' existir
        if (Schema::hasTable('cidades') && Schema::hasColumn('empresas', 'cidade_id')) {
            Schema::table('empresas', function (Blueprint $t) {
                // use try para não quebrar se a FK já existir
                try {
                    $t->foreign('cidade_id')->references('id')->on('cidades')->nullOnDelete();
                } catch (\Throwable $e) {
                    // ignora se já existir
                }
            });
        }
    }

    public function down(): void
    {
        // Remove FK e colunas (se existirem)
        Schema::table('empresas', function (Blueprint $t) {
            if (Schema::hasColumn('empresas', 'cidade_id')) {
                try { $t->dropForeign(['cidade_id']); } catch (\Throwable $e) {}
                $t->dropColumn('cidade_id');
            }
            if (Schema::hasColumn('empresas', 'endereco')) {
                $t->dropColumn('endereco');
            }
        });
    }
};
