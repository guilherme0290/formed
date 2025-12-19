<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('vendedor_id')->nullable()->after('empresa_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('vendedor_id');
        });
        Schema::enableForeignKeyConstraints();
    }
};
