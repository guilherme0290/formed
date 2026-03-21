<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrato_clausulas', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('contrato_clausulas')
                ->nullOnDelete();

            $table->unsignedInteger('ordem_local')
                ->default(0)
                ->after('ordem');

            $table->index(['empresa_id', 'parent_id', 'ordem_local'], 'cc_empresa_parent_ordem_idx');
        });

        DB::table('contrato_clausulas')->update([
            'ordem_local' => DB::raw('ordem'),
        ]);
    }

    public function down(): void
    {
        Schema::table('contrato_clausulas', function (Blueprint $table) {
            $table->dropIndex('cc_empresa_parent_ordem_idx');
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'ordem_local']);
        });
    }
};

