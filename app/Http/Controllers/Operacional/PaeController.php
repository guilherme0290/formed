<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\KanbanColuna;
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
        $user      = auth()->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        return view('operacional.kanban.pae.create', [
            'cliente' => $cliente,
            'pae'     => null,
            'isEdit'  => false,
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'endereco_local'       => ['required', 'string'],
            'total_funcionarios'   => ['required', 'integer', 'min:1'],
            'descricao_instalacoes'=> ['required', 'string'],
        ]);

        // serviço PAE
        $servicoPae = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'PAE')
            ->first();

        // coluna inicial (Pendente)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        DB::transaction(function () use (
            $empresaId,
            $cliente,
            $user,
            $data,
            $servicoPae,
            $colunaInicial
        ) {
            $tarefa = Tarefa::create([
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'responsavel_id' => $user->id,
                'coluna_id'      => optional($colunaInicial)->id,
                'servico_id'     => optional($servicoPae)->id,
                'titulo'         => 'PAE - Plano de Atendimento a Emergências',
                'descricao'      => 'Criação de PAE para o cliente.',
                'inicio_previsto'=> now(),
            ]);

            PaeSolicitacoes::create([
                'empresa_id'           => $empresaId,
                'cliente_id'           => $cliente->id,
                'tarefa_id'            => $tarefa->id,
                'responsavel_id'       => $user->id,
                'endereco_local'       => $data['endereco_local'],
                'total_funcionarios'   => $data['total_funcionarios'],
                'descricao_instalacoes'=> $data['descricao_instalacoes'],
            ]);

            TarefaLog::create([
                'tarefa_id'      => $tarefa->id,
                'user_id'        => $user->id,
                'de_coluna_id'   => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao'           => 'criado',
                'observacao'     => 'Tarefa PAE criada pelo usuário.',
            ]);
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa PAE criada com sucesso.');
    }

    /**
     * Editar PAE a partir da tarefa do Kanban
     */
    public function edit(Tarefa $tarefa, Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $pae = PaeSolicitacoes::where('tarefa_id', $tarefa->id)->firstOrFail();
        $cliente = $pae->cliente;

        return view('operacional.kanban.pae.create', [
            'cliente' => $cliente,
            'pae'     => $pae,
            'isEdit'  => true,
        ]);
    }

    /**
     * Atualizar PAE
     */
    public function update(PaeSolicitacoes $pae, Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($pae->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'endereco_local'       => ['required', 'string'],
            'total_funcionarios'   => ['required', 'integer', 'min:1'],
            'descricao_instalacoes'=> ['required', 'string'],
        ]);

        DB::transaction(function () use ($pae, $data, $user) {
            $pae->update([
                'endereco_local'       => $data['endereco_local'],
                'total_funcionarios'   => $data['total_funcionarios'],
                'descricao_instalacoes'=> $data['descricao_instalacoes'],
            ]);

            if ($pae->tarefa) {
                TarefaLog::create([
                    'tarefa_id'      => $pae->tarefa_id,
                    'user_id'        => $user->id,
                    'de_coluna_id'   => $pae->tarefa->coluna_id,
                    'para_coluna_id' => $pae->tarefa->coluna_id,
                    'acao'           => 'atualizado',
                    'observacao'     => 'PAE atualizado pelo usuário.',
                ]);
            }
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'PAE atualizado com sucesso.');
    }
}
