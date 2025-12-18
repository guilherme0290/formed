<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->string('pipeline_status', 40)->default('CONTATO_INICIAL')->after('status');
            $table->timestamp('pipeline_updated_at')->nullable()->after('pipeline_status');
            $table->foreignId('pipeline_updated_by')->nullable()->after('pipeline_updated_at')->constrained('users')->nullOnDelete();
            $table->string('perdido_motivo', 50)->nullable()->after('pipeline_updated_by');
            $table->text('perdido_observacao')->nullable()->after('perdido_motivo');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('propostas', function (Blueprint $table) {
            $table->dropColumn(['pipeline_status','pipeline_updated_at','pipeline_updated_by','perdido_motivo','perdido_observacao']);
        });
        Schema::enableForeignKeyConstraints();
    }
};
