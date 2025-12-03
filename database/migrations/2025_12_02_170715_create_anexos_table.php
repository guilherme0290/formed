<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('anexos', function (Blueprint $table) {
            $table->id();

            // Escopo padrão da Formed
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('tarefa_id')->nullable();
            $table->unsignedBigInteger('funcionario_id')->nullable();

            // Usuário responsável pelo upload
            $table->foreignIdFor(\App\Models\User::class, 'uploaded_by')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Ex: 'ASO', 'PGR', 'PCMSO' (só pra facilitar filtro/relatórios)
            $table->string('servico')->nullable();

            // Informações do arquivo (S3)
            $table->string('nome_original');             // nome que o usuário subiu
            $table->string('path');                      // caminho no S3 (ex: formed/anexos/123/arquivo.pdf)
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamanho')->default(0); // bytes

            $table->timestamps();

            // FKs (sem polimórfico agora)
            $table->foreign('cliente_id')
                ->references('id')->on('clientes')
                ->onDelete('cascade');

            $table->foreign('tarefa_id')
                ->references('id')->on('tarefas')
                ->onDelete('cascade');

            $table->foreign('funcionario_id')
                ->references('id')->on('funcionarios')
                ->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::dropIfExists('anexos');
    }
};
