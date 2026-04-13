<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_instancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('provider')->default('evolution');
            $table->string('base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->string('numero', 30)->nullable();
            $table->string('instance_name')->nullable();
            $table->boolean('ativo')->default(true);
            $table->string('last_state', 40)->nullable();
            $table->timestamp('last_status_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('empresa_id');
            $table->unique(['empresa_id', 'instance_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_instancias');
    }
};
