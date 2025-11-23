<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pcmso_solicitacoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained();
            $table->foreignId('cliente_id')->constrained();
            $table->foreignId('tarefa_id')->nullable()->constrained('tarefas');
            $table->foreignId('responsavel_id')->nullable()->constrained('users');

            // matriz | especifico (obra)
            $table->enum('tipo', ['matriz', 'especifico']);

            // origem do PGR usado nesse PCMSO
            // - arquivo_cliente: cliente anexou PDF
            // - pgr_formed: PGR da própria FORMED (pgr_solicitacoes)
            $table->enum('pgr_origem', ['arquivo_cliente', 'pgr_formed'])->nullable();

            // Se for PGR feito pela FORMED
            $table->foreignId('pgr_solicitacao_id')
                ->nullable()
                ->constrained('pgr_solicitacoes');

            // Se for PGR anexado pelo cliente (PDF)
            $table->string('pgr_arquivo_path')->nullable();

            // Campos específicos para ESPECÍFICO (obra)
            $table->string('obra_nome')->nullable();
            $table->string('obra_cnpj_contratante')->nullable();
            $table->string('obra_cei_cno')->nullable();
            $table->string('obra_endereco')->nullable();

            // regra de prazo (tela diz 10 dias)
            $table->integer('prazo_dias')->default(10);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pcmso_solicitacoes');
    }
};
