<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\KanbanColuna;
use App\Models\LtcatSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LtcatController extends Controller
{
    // Tela "LTCAT - Selecione o Tipo"
    public function tipo(Cliente $cliente, Request $request)
    {
        $user = $request->user();

        abort_if($cliente->empresa_id !== $user->empresa_id, 403);

        return view('operacional.kanban.ltcat.tipo', [
            'cliente' => $cliente,
        ]);
    }

    // Formulário LTCAT (Matriz / Específico)
    public function create(Cliente $cliente, Request $request)
    {
        $user = $request->user();
        abort_if($cliente->empresa_id !== $user->empresa_id, 403);

        $tipo = $request->query('tipo'); // matriz | especifico

        abort_unless(in_array($tipo, ['matriz', 'especifico']), 404);

        $tipoLabel = $tipo === 'matriz' ? 'Matriz' : 'Específico';

        return view('operacional.kanban.ltcat.create', [
            'cliente' => $cliente,
            'tipo' => $tipo,
            'tipoLabel' => $tipoLabel,
        ]);
    }

    // Salvar LTCAT + criar Tarefa no Kanban
    public function store(Cliente $cliente, Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'tipo'                => ['required', 'in:matriz,especifico'],

            // Matriz
            'endereco_avaliacoes' => ['required_if:tipo,matriz', 'nullable', 'string'],

            // Específico
            'nome_obra'           => ['required_if:tipo,especifico', 'nullable', 'string', 'max:255'],
            'cnpj_contratante'    => ['required_if:tipo,especifico', 'nullable', 'string', 'max:20'],
            'cei_cno'             => ['required_if:tipo,especifico', 'nullable', 'string', 'max:50'],
            'endereco_obra'       => ['required_if:tipo,especifico', 'nullable', 'string'],

            // Funções
            'funcoes'             => ['required', 'array', 'min:1'],
            'funcoes.*.nome'      => ['required', 'string', 'max:255'],
            'funcoes.*.quantidade'=> ['required', 'integer', 'min:1'],
        ]);

        $totalFuncoes = count($data['funcoes']);
        $totalFuncionarios = collect($data['funcoes'])->sum(function ($f) {
            return (int)($f['quantidade'] ?? 0);
        });

        if ($totalFuncionarios <= 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'funcoes' => 'Informe pelo menos 1 funcionário nas funções.',
                ]);
        }

        // Coluna inicial (Pendente)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // Serviço LTCAT
        $servicoLtcat = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'LTCAT')
            ->first();

        $tipoLabel = $data['tipo'] === 'matriz' ? 'Matriz' : 'Específico';

        $tarefaId = null;



        DB::transaction(function () use (
            $data,
            $empresaId,
            $cliente,
            $user,
            $colunaInicial,
            $servicoLtcat,
            $tipoLabel,
            $totalFuncoes,
            $totalFuncionarios,
            &$tarefaId
        ) {
            // Cria Tarefa no Kanban
            $tarefa = Tarefa::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'responsavel_id' => $user->id,
                'coluna_id' => optional($colunaInicial)->id,
                'servico_id' => optional($servicoLtcat)->id,
                'titulo' => "LTCAT - {$tipoLabel}",
                'descricao' => "LTCAT - {$tipoLabel}",
                'inicio_previsto' => now(),
                // se tiver campo de prazo / SLA na sua model de tarefa, pode setar aqui:
                // 'prazo'        => now()->addDays(10),
            ]);

            $tarefaId = $tarefa->id;

            $enderecoAvaliacoes = null;
            if ($data['tipo'] === 'matriz') {
                $enderecoAvaliacoes = $data['endereco_avaliacoes'] ?? null;
            } else { // especifico
                $enderecoAvaliacoes = $data['endereco_obra'] ?? null;
            }

            // Cria registro LTCAT
            LtcatSolicitacoes::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
                'responsavel_id' => $user->id,

                'tipo' => $data['tipo'],
                'endereco_avaliacoes' => $enderecoAvaliacoes,

                'funcoes' => $data['funcoes'],
                'total_funcoes' => $totalFuncoes,
                'total_funcionarios' => $totalFuncionarios,
            ]);

            // Log inicial da tarefa
            TarefaLog::create([
                'tarefa_id' => $tarefa->id,
                'user_id' => $user->id,
                'de_coluna_id' => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao' => 'criado',
                'observacao' => 'Tarefa LTCAT criada pelo usuário.',
            ]);
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa LTCAT criada com sucesso!');
    }
}
