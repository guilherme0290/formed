<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TarefaSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('tarefas')) {
            $this->command?->warn('Tabela "tarefas" não existe. Pulei TarefaSeeder.');
            return;
        }

        $empresa = Schema::hasTable('empresas') ? DB::table('empresas')->first() : null;
        $coluna  = Schema::hasTable('kanban_colunas') ? DB::table('kanban_colunas')->orderBy('ordem')->first() : null;
        $cliente = Schema::hasTable('clientes') ? DB::table('clientes')->first() : null;
        $servico = Schema::hasTable('servicos') ? DB::table('servicos')->first() : null;

        // agora buscamos em users (não mais "usuarios")
        $responsavelId = Schema::hasTable('users') ? DB::table('users')->value('id') : null;

        $base = [
            [
                'titulo'     => 'Agendar Exame Admissional - João da Silva',
                'descricao'  => 'Cliente solicitou agendamento para amanhã às 10h.',
                'prioridade' => 'Alta',
                'status'     => 'Ativa',
            ],
            [
                'titulo'     => 'Emitir Laudo NR-10 - Maria Oliveira',
                'descricao'  => 'Finalizar laudo técnico de segurança elétrica.',
                'prioridade' => 'Média',
                'status'     => 'Ativa',
            ],
            [
                'titulo'     => 'Enviar resultado de exame periódico - João da Silva',
                'descricao'  => 'Enviar por e-mail o resultado e anexar no sistema.',
                'prioridade' => 'Baixa',
                'status'     => 'Concluida',
            ],
        ];

        foreach ($base as $row) {
            $payload = [
                'titulo'      => $row['titulo'],
                'descricao'   => $row['descricao'],
                'prioridade'  => $row['prioridade'],
                'status'      => $row['status'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            // adiciona apenas se a coluna existir (evita SQLSTATE 42S22)
            if (Schema::hasColumn('tarefas', 'empresa_id'))    $payload['empresa_id']    = $empresa?->id;
            if (Schema::hasColumn('tarefas', 'cliente_id'))    $payload['cliente_id']    = $cliente?->id;
            if (Schema::hasColumn('tarefas', 'servico_id'))    $payload['servico_id']    = $servico?->id;
            if (Schema::hasColumn('tarefas', 'coluna_id'))     $payload['coluna_id']     = $coluna?->id;
            if (Schema::hasColumn('tarefas', 'responsavel_id'))$payload['responsavel_id']= $responsavelId;

            DB::table('tarefas')->insert($payload);
        }

        $this->command?->info('✅ Tarefas criadas (campos opcionais adicionados apenas se existirem).');
    }
}
