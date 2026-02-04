<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_aso_grupos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('cliente_ghe_id');
            $table->string('tipo_aso');
            $table->unsignedBigInteger('grupo_exames_id')->nullable();
            $table->decimal('total_exames', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['cliente_ghe_id', 'tipo_aso'], 'cliente_aso_grupos_unique');
            $table->index(['empresa_id', 'cliente_id']);

            $table->foreign('cliente_ghe_id')
                ->references('id')
                ->on('cliente_ghes')
                ->cascadeOnDelete();
            $table->foreign('grupo_exames_id')
                ->references('id')
                ->on('protocolos_exames')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_aso_grupos');
    }
};
