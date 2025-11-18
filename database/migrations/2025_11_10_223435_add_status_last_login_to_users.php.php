<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ativo
        if (!Schema::hasColumn('users', 'ativo')) {
            Schema::table('users', function (Blueprint $t) {
                $t->boolean('ativo')->default(true)->after('remember_token');
            });
        }

        // last_login_at
        if (!Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $t) {
                $t->timestamp('last_login_at')->nullable()->after('ativo');
            });
        }

        // papel_id
        if (!Schema::hasColumn('users', 'papel_id')) {
            Schema::table('users', function (Blueprint $t) {
                $t->foreignId('papel_id')->nullable()
                    ->constrained('papeis')->nullOnDelete();
            });
        }

        // telefone
        if (!Schema::hasColumn('users', 'telefone')) {
            Schema::table('users', function (Blueprint $t) {
                $t->string('telefone')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'papel_id')) {
                $t->dropConstrainedForeignId('papel_id');
            }
            if (Schema::hasColumn('users', 'last_login_at')) {
                $t->dropColumn('last_login_at');
            }
            if (Schema::hasColumn('users', 'ativo')) {
                $t->dropColumn('ativo');
            }
            if (Schema::hasColumn('users', 'telefone')) {
                $t->dropColumn('telefone');
            }
        });
    }
};
