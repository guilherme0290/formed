<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametro_clientes', function (Blueprint $table) {
            $table->string('email_envio_fatura')->nullable()->after('forma_pagamento');
        });
    }

    public function down(): void
    {
        Schema::table('parametro_clientes', function (Blueprint $table) {
            $table->dropColumn('email_envio_fatura');
        });
    }
};

