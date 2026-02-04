<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente_ghes', function (Blueprint $table) {
            $table->unsignedBigInteger('ghe_id')->nullable()->after('cliente_id');
            $table->index(['ghe_id']);
            $table->foreign('ghe_id')
                ->references('id')
                ->on('ghes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cliente_ghes', function (Blueprint $table) {
            $table->dropForeign(['ghe_id']);
            $table->dropIndex(['ghe_id']);
            $table->dropColumn('ghe_id');
        });
    }
};
