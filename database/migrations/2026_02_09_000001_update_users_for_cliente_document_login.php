<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'email')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE users ALTER COLUMN email DROP NOT NULL');
            }
        }

        if (Schema::hasColumn('users', 'documento')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('documento', 'users_documento_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'documento')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_documento_unique');
            });
        }

        if (Schema::hasColumn('users', 'email')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE users ALTER COLUMN email SET NOT NULL');
            }
        }
    }
};
