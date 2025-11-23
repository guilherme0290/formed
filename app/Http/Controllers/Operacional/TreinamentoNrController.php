<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Funcao;
use App\Models\Funcionario;
use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TreinamentoNR;
use App\Models\TreinamentoNrDetalhes;
use App\Models\UnidadeClinica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TreinamentoNrController extends Controller
{
    public function create(Cliente $cliente)
    {
        $user = Auth::user();

        // Funcionários já vinculados ao cliente
        $funcionarios = Funcionario::where('cliente_id', $cliente->id)
            ->orderBy('nome')
            ->get();

        $funcoes = Funcao::where('empresa_id',$user->empresa_id)
            ->orderBy('nome')
            ->get();

        // Unidades da FORMED (ou o que fizer sentido aí)
        $unidades = UnidadeClinica::orderBy('nome')->get();

        return view('operacional.kanban.treinamentos-nr.create', [
            'cliente' => $cliente,
            'funcionarios' => $funcionarios,
            'unidades' => $unidades,
            'funcoes' => $funcoes,
            'user' => $user,
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $usuario   = Auth::user();
        $empresaId = $usuario->empresa_id;

        // Validação única
        $data = $request->validate([
            'funcionarios'   => ['required', 'array', 'min:1'],
            'funcionarios.*' => ['integer', 'exists:funcionarios,id'],

            'local_tipo' => ['required', 'in:clinica,empresa'],
            'unidade_id' => ['required_if:local_tipo,clinica', 'nullable', 'integer', 'exists:unidades_clinicas,id'],
        ], [
            'funcionarios.required' => 'Selecione pelo menos um participante.',
        ]);

        // Coluna inicial do Kanban (igual você usa em outros serviços)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // Serviço Treinamentos NR
        $servicoTreinamentosNr = Servico::where('nome', 'Treinamentos NRs')->first();
        $tipoLabel             = 'Treinamentos de NRs';

        DB::transaction(function () use (
            $data,
            $cliente,
            $usuario,
            $empresaId,
            $colunaInicial,
            $servicoTreinamentosNr,
            $tipoLabel
        ) {
            // Cria a tarefa no padrão das demais
            $tarefa = Tarefa::create([
                'empresa_id'      => $empresaId,
                'cliente_id'      => $cliente->id,
                'responsavel_id'  => $usuario->id,
                'coluna_id'       => optional($colunaInicial)->id,
                'servico_id'      => optional($servicoTreinamentosNr)->id,
                'titulo'          => "Treinamento NR",
                'descricao'       => "Treinamento NR - {$tipoLabel} · Local: {$data['local_tipo']}"
                    . ($data['local_tipo'] === 'clinica'
                        ? " · Unidade ID: {$data['unidade_id']}"
                        : ' · In Company'),
                'inicio_previsto' => now(),
            ]);

            // Participantes
            foreach ($data['funcionarios'] as $funcionarioId) {
                TreinamentoNR::create([
                    'tarefa_id'      => $tarefa->id,
                    'funcionario_id' => $funcionarioId,
                ]);
            }

            // Detalhes de local/unidade
            TreinamentoNrDetalhes::create([
                'tarefa_id'  => $tarefa->id,
                'local_tipo' => $data['local_tipo'],
                'unidade_id' => $data['unidade_id'] ?? null,
            ]);
        });

        return redirect()
            ->route('operacional.painel')
            ->with('ok', 'Tarefa de Treinamento de NRs criada com sucesso.');
    }

    public function storeFuncionario(Cliente $cliente, Request $request)
    {

        $usuario   = Auth::user();
        $empresaId = $usuario->empresa_id;

        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:255'],
            'cpf'       => ['required', 'string', 'max:20'],
            'nascimento'=> ['nullable', 'date'],
            'funcao_id' => ['required', 'integer', 'exists:funcoes,id'],
        ]);


        $funcionario = Funcionario::create([
            'empresa_id'  => $empresaId,
            'cliente_id'  => $cliente->id,
            'nome'        => $data['nome'],
            'cpf'         => $data['cpf'],
            'data_nascimento'  => $data['nascimento'] ?? null,
            'funcao_id'   => $data['funcao_id'],
        ]);

        return response()->json([
            'ok' => true,
            'funcionario' => [
                'id' => $funcionario->id,
                'nome' => $funcionario->nome,
                'nascimento' => $funcionario->data_nascimento,
                'funcao_nome' => optional($funcionario->funcao)->nome,
            ],
        ], 201);
    }
}
