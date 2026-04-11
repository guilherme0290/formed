<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'is_protected')) {
            return;
        }

        if (Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_protected')->default(false)->after('must_change_password');
            });

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_protected')->default(false);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'is_protected')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_protected');
        });
    }
};
