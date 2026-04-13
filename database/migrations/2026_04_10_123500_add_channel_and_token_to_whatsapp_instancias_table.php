<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_instancias', function (Blueprint $table) {
            $table->string('channel', 60)->nullable()->after('api_key');
            $table->text('token')->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_instancias', function (Blueprint $table) {
            $table->dropColumn(['channel', 'token']);
        });
    }
};
