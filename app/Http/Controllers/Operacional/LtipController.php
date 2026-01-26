<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\AsoGheService;
use App\Models\KanbanColuna;
use App\Models\LtipSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Services\TempoTarefaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LtipController extends Controller
{
    public function create(Cliente $cliente)
    {
        $user      = auth()->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);

        // base do formulário de funções
        $funcoesForm = old('funcoes');
        if (empty($funcoesForm) || !is_array($funcoesForm)) {
            $funcoesForm = [
                ['funcao_id' => null, 'quantidade' => 1],
            ];
        }

        return view('operacional.kanban.ltip.create', [
            'cliente'     => $cliente,
            'funcoes'     => $funcoes,
            'funcoesForm' => $funcoesForm,
            'ltip'        => null,
            'isEdit'      => false,
        ]);
    }

    /**
     * Editar LTIP a partir da tarefa do Kanban
     */
    public function edit(Tarefa $tarefa, Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        // relação na Tarefa: hasOne(LtipSolicitacoes::class, 'tarefa_id')
        $ltip = $tarefa->ltipSolicitacao;
        abort_if(!$ltip, 404);

        $cliente = $ltip->cliente;

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);

        // usa old() se teve erro, senão vem do banco
        $funcoesForm = old('funcoes', $ltip->funcoes ?? []);

        if (empty($funcoesForm) || !is_array($funcoesForm)) {
            $funcoesForm = [
                ['funcao_id' => null, 'quantidade' => 1],
            ];
        }

        return view('operacional.kanban.ltip.create', [
            'cliente'     => $cliente,
            'funcoes'     => $funcoes,
            'funcoesForm' => $funcoesForm,
            'ltip'        => $ltip,
            'isEdit'      => true,
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'endereco_avaliacoes'    => ['required', 'string', 'max:1000'],
            'funcoes'                => ['required', 'array', 'min:1'],
            'funcoes.*.funcao_id'    => ['required', 'integer', 'exists:funcoes,id'],
            'funcoes.*.quantidade'   => ['required', 'integer', 'min:1'],
        ]);

        $totalFuncionarios = collect($data['funcoes'])->sum(fn($f) => (int)($f['quantidade'] ?? 0));

        if ($totalFuncionarios <= 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'funcoes' => 'Informe pelo menos 1 funcionário nas funções.',
                ]);
        }

        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

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
            $inicioPrevisto = now();
            $fimPrevisto = app(TempoTarefaService::class)
                ->calcularFimPrevisto($inicioPrevisto, $empresaId, optional($servicoLtip)->id);

            $tarefa = Tarefa::create([
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'responsavel_id' => $user->id,
                'coluna_id'      => optional($colunaInicial)->id,
                'servico_id'     => optional($servicoLtip)->id,
                'titulo'         => 'LTIP - Insalubridade e Periculosidade',
                'descricao'      => 'LTIP - Insalubridade e Periculosidade',
                'inicio_previsto'=> $inicioPrevisto,
                'fim_previsto'   => $fimPrevisto,
            ]);

            $tarefaId = $tarefa->id;

            LtipSolicitacoes::create([
                'empresa_id'         => $empresaId,
                'cliente_id'         => $cliente->id,
                'tarefa_id'          => $tarefa->id,
                'responsavel_id'     => $user->id,
                'endereco_avaliacoes'=> $data['endereco_avaliacoes'],
                'funcoes'            => $data['funcoes'],
                'total_funcionarios' => $totalFuncionarios,
            ]);

            TarefaLog::create([
                'tarefa_id'    => $tarefa->id,
                'user_id'      => $user->id,
                'de_coluna_id' => null,
                'para_coluna_id'=> optional($colunaInicial)->id,
                'acao'         => 'criado',
                'observacao'   => 'Tarefa LTIP criada pelo usuário.',
            ]);
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa LTIP criada com sucesso!');
    }

    public function update(LtipSolicitacoes $ltip, Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($ltip->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'endereco_avaliacoes'    => ['required', 'string', 'max:1000'],
            'funcoes'                => ['required', 'array', 'min:1'],
            'funcoes.*.funcao_id'    => ['required', 'integer', 'exists:funcoes,id'],
            'funcoes.*.quantidade'   => ['required', 'integer', 'min:1'],
        ]);

        $totalFuncionarios = collect($data['funcoes'])->sum(fn($f) => (int)($f['quantidade'] ?? 0));

        if ($totalFuncionarios <= 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'funcoes' => 'Informe pelo menos 1 funcionário nas funções.',
                ]);
        }

        DB::transaction(function () use ($ltip, $data, $totalFuncionarios, $user) {
            $ltip->update([
                'endereco_avaliacoes' => $data['endereco_avaliacoes'],
                'funcoes'             => $data['funcoes'],
                'total_funcionarios'  => $totalFuncionarios,
            ]);

            if ($ltip->tarefa) {
                TarefaLog::create([
                    'tarefa_id'      => $ltip->tarefa_id,
                    'user_id'        => $user->id,
                    'de_coluna_id'   => $ltip->tarefa->coluna_id,
                    'para_coluna_id' => $ltip->tarefa->coluna_id,
                    'acao'           => 'atualizado',
                    'observacao'     => 'LTIP atualizado pelo usuário.',
                ]);
            }
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'LTIP atualizado com sucesso!');
    }
}
