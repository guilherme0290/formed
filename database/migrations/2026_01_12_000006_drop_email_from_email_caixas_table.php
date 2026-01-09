<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('email_caixas', 'email')) {
            return;
        }

        Schema::table('email_caixas', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'email']);
            $table->dropColumn('email');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('email_caixas', 'email')) {
            return;
        }

        Schema::table('email_caixas', function (Blueprint $table) {
            $table->string('email')->after('nome');
            $table->unique(['empresa_id', 'email']);
        });
    }
};
