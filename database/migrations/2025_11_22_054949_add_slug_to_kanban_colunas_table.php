<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kanban_colunas', function (Blueprint $table) {
            // se ainda não existir:
            if (!Schema::hasColumn('kanban_colunas', 'slug')) {
                $table->string('slug')
                    ->after('nome')
                    ->nullable(); // deixa nullable pra não quebrar dados antigos
            }
            if (!Schema::hasColumn('kanban_colunas', 'cor')) {
                $table->string('cor', 20)
                    ->after('slug')
                    ->nullable(); // ex: '#FDE68A', 'amber', etc.
            }
            if (!Schema::hasColumn('kanban_colunas', 'atraso')) {
                $table->boolean('atraso', false)
                    ->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kanban_colunas', function (Blueprint $table) {
            if (Schema::hasColumn('kanban_colunas', 'cor')) {
                $table->dropColumn('cor');
            }

            if (Schema::hasColumn('kanban_colunas', 'slug')) {
                $table->dropColumn('slug');
            }

            if (Schema::hasColumn('kanban_colunas', 'atraso')) {
                $table->dropColumn('atraso');
            }
        });
    }
};
