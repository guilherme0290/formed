<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modelos_comerciais', function (Blueprint $table) {
            $table->string('nome_modelo', 150)->nullable()->after('segmento');
            $table->text('mensagem_principal')->nullable()->after('intro_2');
            $table->decimal('comissao_vendedor', 5, 2)->nullable()->after('mensagem_principal');
            $table->string('contato_email')->nullable()->after('comissao_vendedor');
            $table->string('contato_telefone', 50)->nullable()->after('contato_email');
            $table->string('catalogo_preco', 40)->nullable()->after('contato_telefone');
        });
    }

    public function down(): void
    {
        Schema::table('modelos_comerciais', function (Blueprint $table) {
            $table->dropColumn([
                'nome_modelo',
                'mensagem_principal',
                'comissao_vendedor',
                'contato_email',
                'contato_telefone',
                'catalogo_preco',
            ]);
        });
    }
};
