<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_caixas', function (Blueprint $table) {
            $table->string('imap_host')->nullable()->after('senha');
            $table->unsignedInteger('imap_porta')->nullable()->after('imap_host');
            $table->string('imap_criptografia', 10)->nullable()->after('imap_porta');
            $table->string('imap_usuario')->nullable()->after('imap_criptografia');
            $table->text('imap_senha')->nullable()->after('imap_usuario');
            $table->string('imap_sent_folder')->nullable()->after('imap_senha');
        });
    }

    public function down(): void
    {
        Schema::table('email_caixas', function (Blueprint $table) {
            $table->dropColumn([
                'imap_host',
                'imap_porta',
                'imap_criptografia',
                'imap_usuario',
                'imap_senha',
                'imap_sent_folder',
            ]);
        });
    }
};
