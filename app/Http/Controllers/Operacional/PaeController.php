<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\PaeSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaeController extends Controller
{
    public function create(Cliente $cliente)
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        return view('operacional.kanban.pae.create', [
            'cliente' => $cliente,
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'endereco_local' => ['required', 'string'],
            'total_funcionarios' => ['required', 'integer', 'min:1'],
            'descricao_instalacoes' => ['required', 'string'],
        ]);

        // serviço PAE
        $servicoPae = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'PAE')
            ->first();

        // coluna inicial (Pendente)
        $colunaInicial = \App\Models\KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? \App\Models\KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        $tarefaId = null;

        DB::transaction(function () use (
            $empresaId,
            $cliente,
            $user,
            $data,
            $servicoPae,
            $colunaInicial,
            &$tarefaId
        ) {

            $tarefa = Tarefa::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'responsavel_id' => $user->id,
                'coluna_id' => optional($colunaInicial)->id,
                'servico_id' => optional($servicoPae)->id,
                'titulo' => 'PAE - Plano de Atendimento a Emergências',
                'descricao' => 'Criação de PAE para o cliente.',
                'inicio_previsto' => now(),
            ]);

            $tarefaId = $tarefa->id;

            PaeSolicitacoes::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
                'responsavel_id' => $user->id,
                'endereco_local' => $data['endereco_local'],
                'total_funcionarios' => $data['total_funcionarios'],
                'descricao_instalacoes' => $data['descricao_instalacoes'],
            ]);

            TarefaLog::create([
                'tarefa_id' => $tarefa->id,
                'user_id' => $user->id,
                'de_coluna_id' => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao' => 'criado',
                'observacao' => 'Tarefa PAE criada pelo usuário.',
            ]);
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa PAE criada com sucesso.');
    }
}
