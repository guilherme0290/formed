<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposta_aso_grupos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('proposta_id');
            $table->string('tipo_aso', 40);
            $table->unsignedBigInteger('grupo_exames_id')->nullable();
            $table->decimal('total_exames', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['empresa_id', 'cliente_id']);
            $table->unique(['proposta_id', 'tipo_aso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposta_aso_grupos');
    }
};
