<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('papeis') && Schema::hasTable('users') && !Schema::hasColumn('users','papel_id')) {
            Schema::table('users', function (Blueprint $t) {
                $t->foreignId('papel_id')->nullable()->constrained('papeis')->nullOnDelete();
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users','papel_id')) {
            Schema::table('users', function (Blueprint $t) {
                $t->dropConstrainedForeignId('papel_id');
            });
        }
    }
};
