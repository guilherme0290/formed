<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Funcao;
use App\Models\KanbanColuna;
use App\Models\LtipSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LtipController extends Controller
{
    public function create(Cliente $cliente)
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $funcoes = Funcao::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        return view('operacional.kanban.ltip.create', [
            'cliente' => $cliente,
            'funcoes' => $funcoes,
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'endereco_avaliacoes' => ['required', 'string', 'max:1000'],

            'funcoes'                 => ['required', 'array', 'min:1'],
            'funcoes.*.funcao_id'     => ['required', 'integer', 'exists:funcoes,id'],
            'funcoes.*.quantidade'    => ['required', 'integer', 'min:1'],
        ]);

        $totalFuncionarios = collect($data['funcoes'])->sum(function ($funcao) {
            return (int)($funcao['quantidade'] ?? 0);
        });

        // coluna inicial (Pendente)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // serviço LTIP (garante ter um registro "LTIP" na tabela servicos)
        $servicoLtip = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'LTIP')
            ->first();

        $tarefaId = null;

        DB::transaction(function () use (
            $data,
            $empresaId,
            $cliente,
            $user,
            $colunaInicial,
            $servicoLtip,
            $totalFuncionarios,
            &$tarefaId
        ) {
            $tarefa = Tarefa::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'responsavel_id' => $user->id,
                'coluna_id' => optional($colunaInicial)->id,
                'servico_id' => optional($servicoLtip)->id,
                'titulo' => 'LTIP - Insalubridade e Periculosidade',
                'descricao' => 'LTIP - Insalubridade e Periculosidade',
                'inicio_previsto' => now(),
            ]);

            $tarefaId = $tarefa->id;

            LtipSolicitacoes::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
                'responsavel_id' => $user->id,
                'endereco_avaliacoes' => $data['endereco_avaliacoes'],
                'funcoes' => $data['funcoes'],
                'total_funcionarios' => $totalFuncionarios,
            ]);

            TarefaLog::create([
                'tarefa_id' => $tarefa->id,
                'user_id' => $user->id,
                'de_coluna_id' => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao' => 'criado',
                'observacao' => 'Tarefa LTIP criada pelo usuário.',
            ]);
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa LTIP criada com sucesso!');
    }
}
