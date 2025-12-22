<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_contrato_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_contrato_id')->constrained('cliente_contratos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $table->string('acao', 40);
            $table->string('motivo')->nullable();
            $table->text('descricao');
            $table->decimal('valor_anterior', 12, 2)->nullable();
            $table->decimal('valor_novo', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_contrato_logs');
    }
};
