<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\AprSolicitacoes;
use App\Models\Cliente;
use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AprController extends Controller
{
    public function create(Cliente $cliente)
    {
        $user = auth()->user();
        abort_if($cliente->empresa_id !== $user->empresa_id, 403);

        return view('operacional.kanban.apr.create', [
            'cliente' => $cliente,
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'endereco_atividade' => ['required', 'string', 'max:255'],
            'funcoes_envolvidas' => ['required', 'string'],
            'etapas_atividade' => ['required', 'string'],
        ]);

        // coluna inicial (Pendente)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // serviço APR
        $servicoApr = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'APR')
            ->first();

        $tarefaId = null;

        DB::transaction(function () use (
            $data,
            $empresaId,
            $cliente,
            $user,
            $colunaInicial,
            $servicoApr,
            &$tarefaId
        ) {
            // cria Tarefa no Kanban
            $tarefa = Tarefa::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'responsavel_id' => $user->id,
                'coluna_id' => optional($colunaInicial)->id,
                'servico_id' => optional($servicoApr)->id,
                'titulo' => 'APR - Análise Preliminar de Riscos',
                'descricao' => 'APR solicitada pelo cliente.',
                'inicio_previsto' => now(),
            ]);

            $tarefaId = $tarefa->id;

            // cria registro APR
            AprSolicitacoes::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
                'responsavel_id' => $user->id,
                'endereco_atividade' => $data['endereco_atividade'],
                'funcoes_envolvidas' => $data['funcoes_envolvidas'],
                'etapas_atividade' => $data['etapas_atividade'],
            ]);

            // log inicial
            TarefaLog::create([
                'tarefa_id' => $tarefa->id,
                'user_id' => $user->id,
                'de_coluna_id' => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao' => 'criado',
                'observacao' => 'Tarefa APR criada pelo usuário.',
            ]);
        });

        // redireciona para o kanban ou detalhe da tarefa
        return redirect()
            ->route('operacional.kanban', $tarefaId)
            ->with('ok', 'Tarefa APR criada com sucesso!');
    }
}
