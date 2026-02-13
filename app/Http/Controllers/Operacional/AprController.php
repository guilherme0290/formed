<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Operacional\Concerns\ValidatesClientePortalTaskEditing;
use App\Http\Controllers\Controller;
use App\Models\AprSolicitacoes;
use App\Models\Cliente;
use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Services\TempoTarefaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AprController extends Controller
{
    use ValidatesClientePortalTaskEditing;

    public function create(Cliente $cliente)
    {
        $user = auth()->user();
        abort_if($cliente->empresa_id !== $user->empresa_id, 403);

        return view('operacional.kanban.apr.create', [
            'cliente' => $cliente,
            'apr'     => null,
            'isEdit'  => false,
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'endereco_atividade'  => ['required', 'string', 'max:255'],
            'funcoes_envolvidas'  => ['required', 'string'],
            'etapas_atividade'    => ['required', 'string'],
        ], $this->mensagensValidacao(), $this->atributosValidacao());

        // coluna inicial (Pendente)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // serviço APR
        $servicoApr = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'APR')
            ->first();

        DB::transaction(function () use (
            $data,
            $empresaId,
            $cliente,
            $user,
            $colunaInicial,
            $servicoApr
        ) {
            // cria Tarefa no Kanban
            $inicioPrevisto = now();
            $fimPrevisto = app(TempoTarefaService::class)
                ->calcularFimPrevisto($inicioPrevisto, $empresaId, optional($servicoApr)->id);

            $tarefa = Tarefa::create([
                'empresa_id'      => $empresaId,
                'cliente_id'      => $cliente->id,
                'responsavel_id'  => $user->id,
                'coluna_id'       => optional($colunaInicial)->id,
                'servico_id'      => optional($servicoApr)->id,
                'titulo'          => 'APR - Análise Preliminar de Riscos',
                'descricao'       => 'APR solicitada pelo cliente.',
                'inicio_previsto' => $inicioPrevisto,
                'fim_previsto'    => $fimPrevisto,
            ]);

            // cria registro APR
            AprSolicitacoes::create([
                'empresa_id'         => $empresaId,
                'cliente_id'         => $cliente->id,
                'tarefa_id'          => $tarefa->id,
                'responsavel_id'     => $user->id,
                'endereco_atividade' => $data['endereco_atividade'],
                'funcoes_envolvidas' => $data['funcoes_envolvidas'],
                'etapas_atividade'   => $data['etapas_atividade'],
            ]);

            // log inicial
            TarefaLog::create([
                'tarefa_id'      => $tarefa->id,
                'user_id'        => $user->id,
                'de_coluna_id'   => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao'           => 'criado',
                'observacao'     => 'Tarefa APR criada pelo usuário.',
            ]);
        });

        if (request()->query('origem') === 'cliente') {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'Tarefa de Treinamento de NRs criada com sucesso.');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa de Treinamento de NRs criada com sucesso.');
    }

    /**
     * Editar APR a partir da tarefa do Kanban
     */
    public function edit(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        // Busca a APR vinculada à tarefa
        $apr = AprSolicitacoes::where('tarefa_id', $tarefa->id)->firstOrFail();

        $cliente = $apr->cliente;

        return view('operacional.kanban.apr.create', [
            'cliente' => $cliente,
            'apr'     => $apr,
            'isEdit'  => true,
        ]);
    }

    /**
     * Update APR
     */
    public function update(AprSolicitacoes $apr, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $apr->tarefa)) {
            return $redirect;
        }

        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($apr->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'endereco_atividade'  => ['required', 'string', 'max:255'],
            'funcoes_envolvidas'  => ['required', 'string'],
            'etapas_atividade'    => ['required', 'string'],
        ], $this->mensagensValidacao(), $this->atributosValidacao());

        DB::transaction(function () use ($apr, $data, $user) {
            $apr->update([
                'endereco_atividade' => $data['endereco_atividade'],
                'funcoes_envolvidas' => $data['funcoes_envolvidas'],
                'etapas_atividade'   => $data['etapas_atividade'],
            ]);

            if ($apr->tarefa) {
                TarefaLog::create([
                    'tarefa_id'      => $apr->tarefa_id,
                    'user_id'        => $user->id,
                    'de_coluna_id'   => $apr->tarefa->coluna_id,
                    'para_coluna_id' => $apr->tarefa->coluna_id,
                    'acao'           => 'atualizado',
                    'observacao'     => 'APR atualizada pelo usuário.',
                ]);
            }
        });

        if ($request->query('origem') === 'cliente') {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'APR atualizada com sucesso!');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'APR atualizada com sucesso!');
    }

    private function mensagensValidacao(): array
    {
        return [
            'required' => 'O campo :attribute e obrigatorio.',
            'string' => 'O campo :attribute deve ser um texto valido.',
            'max' => 'O campo :attribute deve ter no maximo :max caracteres.',
        ];
    }

    private function atributosValidacao(): array
    {
        return [
            'endereco_atividade' => 'endereco da atividade',
            'funcoes_envolvidas' => 'funcoes envolvidas',
            'etapas_atividade' => 'etapas da atividade',
        ];
    }
}

