<?php

namespace App\Services;

use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CriarTarefaService
{
    /**
     * Cria uma tarefa no Kanban vinculada a um cliente,
     * marcando como originada pelo portal do cliente.
     */
    public function criarParaCliente(
        int $empresaId,
        Cliente $cliente,
        ?User $usuarioResponsavel,
        ?Servico $servico,
        string $titulo,
        string $descricao,
        ?string $inicioPrevisto = null,
        array $extraCamposTarefa = []
    ): Tarefa {
        return DB::transaction(function () use (
            $empresaId,
            $cliente,
            $usuarioResponsavel,
            $servico,
            $titulo,
            $descricao,
            $inicioPrevisto,
            $extraCamposTarefa
        ) {
            // coluna inicial (pendente)
            $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
                ->where('slug', 'pendente')
                ->first()
                ?? KanbanColuna::where('empresa_id', $empresaId)
                    ->orderBy('ordem')
                    ->first();

            $tarefa = Tarefa::create(array_merge([
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'responsavel_id' => $usuarioResponsavel?->id, // por enquanto null
                'coluna_id'      => optional($colunaInicial)->id,
                'servico_id'     => optional($servico)->id,
                'titulo'         => $titulo,
                'descricao'      => $descricao,
                'inicio_previsto'=> $inicioPrevisto ?? now(),
                'criado_via_portal_cliente' => true,
            ], $extraCamposTarefa));

            TarefaLog::create([
                'tarefa_id'      => $tarefa->id,
                'user_id'        => $usuarioResponsavel?->id,
                'de_coluna_id'   => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao'           => 'criado',
                'observacao'     => 'Tarefa criada pelo Portal do Cliente.',
            ]);

            return $tarefa;
        });
    }
}
