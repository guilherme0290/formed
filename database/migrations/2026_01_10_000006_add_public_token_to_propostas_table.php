<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            if (!Schema::hasColumn('propostas', 'public_token')) {
                $table->string('public_token', 80)->nullable()->unique()->after('codigo');
            }
            if (!Schema::hasColumn('propostas', 'public_responded_at')) {
                $table->timestamp('public_responded_at')->nullable()->after('status');
            }
        });

        DB::table('propostas')
            ->whereIn('status', ['rascunho', 'RASCUNHO'])
            ->update(['status' => 'PENDENTE']);
    }

    public function down(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            if (Schema::hasColumn('propostas', 'public_token')) {
                $table->dropUnique(['public_token']);
                $table->dropColumn('public_token');
            }
            if (Schema::hasColumn('propostas', 'public_responded_at')) {
                $table->dropColumn('public_responded_at');
            }
        });
    }
};
