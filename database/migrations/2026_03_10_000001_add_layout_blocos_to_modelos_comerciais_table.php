<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modelos_comerciais', function (Blueprint $table) {
            $table->json('layout')->nullable()->after('rodape');
        });
    }

    public function down(): void
    {
        Schema::table('modelos_comerciais', function (Blueprint $table) {
            $table->dropColumn('layout');
        });
    }
};
