<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_caixas', function (Blueprint $table) {
            $table->dropUnique('email_caixas_empresa_id_email_unique');
            $table->dropColumn(['email', 'nome_remetente', 'reply_to']);
        });
    }

    public function down(): void
    {
        Schema::table('email_caixas', function (Blueprint $table) {
            $table->string('email')->after('nome');
            $table->string('nome_remetente')->nullable()->after('email');
            $table->string('reply_to')->nullable()->after('nome_remetente');
            $table->unique(['empresa_id', 'email']);
        });
    }
};
