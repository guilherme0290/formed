<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('tarefa_id')->nullable()->constrained('tarefas')->nullOnDelete();
            $table->foreignId('contrato_id')->nullable()->constrained('cliente_contratos')->nullOnDelete();
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status', 30)->default('ABERTA'); // ABERTA/FECHADA/AGUARDANDO
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('vendas');
        Schema::enableForeignKeyConstraints();
    }
};
