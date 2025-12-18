<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Funcao;
use App\Models\KanbanColuna;
use App\Models\PgrSolicitacoes;
use App\Models\Tarefa;
use App\Models\Servico;
use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\TarefaLog;
use App\Models\TreinamentoNR;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psy\Util\Str;
use App\Services\PrecificacaoService;
use App\Services\VendaService;

class PainelController extends Controller
{



    // ==========================
    // PAINEL / KANBAN
    // ==========================
    public function index(Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        // Filtros
        $filtroServico     = $request->input('servico_id');
        $filtroResponsavel = $request->input('responsavel_id');
        $filtroColuna      = $request->input('coluna_id');
        $filtroDe          = $request->input('de');
        $filtroAte         = $request->input('ate');

        $filtroCanceladas = ($filtroColuna === 'canceladas');

        // Colunas do Kanban
        $colunas = KanbanColuna::where('empresa_id', $empresaId)
            ->orderBy('ordem')
            ->get();

        // Descobre a coluna "finalizada" (slug = finalizada)
        $colunaFinalizada = $colunas->firstWhere('slug', 'finalizada');

        /**
         * Query base das tarefas
         * - Se "apenas canceladas" estiver marcado, usamos onlyTrashed()
         *   para trazer apenas tarefas softdeletadas.
         * - Caso contrário, usamos a query normal (sem trashed).
         */
        if ($filtroCanceladas) {
            $tarefasQuery = Tarefa::onlyTrashed()
                ->with([
                    'cliente',
                    'servico',
                    'responsavel',
                    'coluna',
                    'funcionario',
                    'logs.deColuna',
                    'logs.paraColuna',
                    'logs.user',
                ])
                ->where('empresa_id', $empresaId);
        } else {
            $tarefasQuery = Tarefa::with([
                'cliente',
                'servico',
                'responsavel',
                'coluna',
                'funcionario',
                'logs.deColuna',
                'logs.paraColuna',
                'logs.user',
            ])
                ->where('empresa_id', $empresaId);
        }

        // Aplica filtros
        if ($filtroServico) {
            $tarefasQuery->where('servico_id', $filtroServico);
        }

        if ($filtroResponsavel) {
            $tarefasQuery->where('responsavel_id', $filtroResponsavel);
        }

        if ($filtroColuna && $filtroCanceladas == null) {
            $tarefasQuery->where('coluna_id', $filtroColuna);
        }

        if ($filtroDe) {
            $tarefasQuery->whereDate('inicio_previsto', '>=', $filtroDe);
        }

        if ($filtroAte) {
            $tarefasQuery->whereDate('inicio_previsto', '<=', $filtroAte);
        }

        /**
         * Regra: por padrão, NÃO listar tarefas finalizadas de dias anteriores.
         * - Outras colunas continuam normais.
         * - Na coluna "finalizada", só entram tarefas com finalizado_em HOJE.
         * - Se o usuário filtrou por data (de/ate), por coluna ou por canceladas,
         *   NÃO aplicamos essa restrição (mostrar exatamente o que ele pediu).
         */
        if (
            !$filtroDe &&
            !$filtroAte &&
            !$filtroColuna &&        // 👈 se estiver filtrando coluna, não restringe
            !$filtroCanceladas &&    // 👈 se estiver vendo canceladas (onlyTrashed), não restringe
            $colunaFinalizada
        ) {
            $hoje = now()->toDateString();

            $tarefasQuery->where(function ($q) use ($colunaFinalizada, $hoje) {
                $q->where('coluna_id', '<>', $colunaFinalizada->id)
                    ->orWhere(function ($q2) use ($colunaFinalizada, $hoje) {
                        $q2->where('coluna_id', $colunaFinalizada->id)
                            ->whereNotNull('finalizado_em')
                            ->whereDate('finalizado_em', $hoje);
                    });
            });
        }

        $tarefas = $tarefasQuery->orderBy('coluna_id')
            ->orderBy('ordem')
            ->orderBy('id')
            ->get();

        // Agrupa tarefas por coluna
        $tarefasPorColuna = $tarefas->groupBy('coluna_id');

        // Stats por slug de coluna (para os cards do topo)
        $stats = [];
        foreach ($colunas as $coluna) {
            $colecaoColuna = $tarefasPorColuna->get($coluna->id); // pode ser null
            $stats[$coluna->slug] = $colecaoColuna ? $colecaoColuna->count() : 0;
        }

        // Listas para filtros
        $servicos = Servico::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        $responsaveis = User::where('empresa_id', $empresaId)
            ->orderBy('name')
            ->get();

        $funcoes = Funcao::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        return view('operacional.kanban.index', [
            'usuario'           => $usuario,
            'colunas'           => $colunas,
            'tarefasPorColuna'  => $tarefasPorColuna,
            'stats'             => $stats,
            'funcoes'           => $funcoes,

            // filtros atuais (pra dar @selected e preencher inputs)
            'servicos'          => $servicos,
            'responsaveis'      => $responsaveis,
            'filtroServico'     => $filtroServico,
            'filtroResponsavel' => $filtroResponsavel,
            'filtroColuna'      => $filtroColuna,
            'filtroDe'          => $filtroDe,
            'filtroAte'         => $filtroAte,
            'filtroCanceladas'  => $filtroCanceladas,
        ]);
    }





    public function detalhesAjax(Request $r)
    {
        $tarefa = Tarefa::with([
            'cliente',
            'servico',
            'responsavel',
            'pgrSolicitacao',
            'treinamentoNrs.funcionario',
            'treinamentoNrDetalhe',
        ])->findOrFail($r->id);

        return view('operacional.kanban.modals.tarefa-detalhes', [
            't' => $tarefa
        ]);
    }




    // ==========================
    // MOVER CARD NO KANBAN
    // ==========================
    public function mover(Request $request, Tarefa $tarefa, PrecificacaoService $precificacaoService, VendaService $vendaService)
    {
        $data = $request->validate([
            'coluna_id' => ['required', 'exists:kanban_colunas,id'],
        ]);

        $novaColunaId  = (int) $data['coluna_id'];
        $colunaAtualId = (int) $tarefa->coluna_id;

        if ($novaColunaId === $colunaAtualId) {
            return response()->json(['ok' => true]);
        }

        $novaColuna = KanbanColuna::findOrFail($novaColunaId);
        $finalizando = $novaColuna->slug === 'finalizada';

        if ($finalizando) {
            try {
                $resultado = $precificacaoService->validarServicoNoContrato(
                    (int) $tarefa->cliente_id,
                    (int) $tarefa->servico_id,
                    (int) $tarefa->empresa_id
                );
                $vendaService->criarVendaPorTarefa($tarefa, $resultado['contrato'], $resultado['item']);
            } catch (\Throwable $e) {
                $mensagem = 'Não é possível concluir esta tarefa porque o cliente não possui preço definido para este serviço na proposta/contrato ativo. Solicite ao Comercial para ajustar a proposta e fechar novamente, ou cadastrar o valor do serviço no contrato do cliente.';
                if (method_exists($e, 'errors')) {
                    $mensagem = collect($e->errors())->flatten()->first() ?? $mensagem;
                }

                return response()->json([
                    'ok' => false,
                    'error' => $mensagem,
                ], 422);
            }
        }

        // Atualiza coluna
        $tarefa->update([
            'coluna_id' => $novaColunaId,
        ]);

        // Recarrega coluna para pegar o nome
        $tarefa->load('coluna');

        if($tarefa->coluna->slug == 'finalizada'){
            $tarefa->update([
                'finalizado_em' => now(),
            ]);
        }


        // Cria log de movimentação
        $log = TarefaLog::create([
            'tarefa_id'      => $tarefa->id,
            'user_id'        => Auth::id(),
            'de_coluna_id'   => $colunaAtualId,
            'para_coluna_id' => $novaColunaId,
            'acao'           => 'movido',
            'observacao'     => null,
        ]);

        $log->load(['deColuna', 'paraColuna', 'user']);

        return response()->json([
            'ok'           => true,
            'status_label' => $tarefa->coluna->nome ?? '',
            'log'          => [
                'de'   => optional($log->deColuna)->nome ?? 'Início',
                'para' => optional($log->paraColuna)->nome ?? '-',
                'user' => optional($log->user)->name ?? 'Sistema',
                'data' => optional($log->created_at)->format('d/m H:i'),
            ],
        ]);
    }


    public function asoSelecionarCliente(Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;
        $q         = $request->query('q');

        $clientes = Cliente::where('empresa_id', $empresaId)
            ->when($q, fn($query) =>
            $query->where('razao_social', 'like', "%{$q}%")
                ->orWhere('nome_fantasia', 'like', "%{$q}%")
            )
            ->orderBy('razao_social')
            ->paginate(12);

        return view('operacional.kanban.clientes', compact('clientes', 'q'));
    }

    public function selecionarServico(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        return view('operacional.kanban.servicos', compact('cliente'));
    }





    public function salvarObservacao(Request $request, Tarefa $tarefa)
    {
        $data = $request->validate([
            'observacao_interna' => ['nullable', 'string', 'max:2000'],
        ]);

        $tarefa->update([
            'observacao_interna' => $data['observacao_interna'] ?? null,
        ]);

        return response()->json([
            'ok'                 => true,
            'observacao_interna' => $tarefa->observacao_interna,
        ]);
    }


    public function destroy(Tarefa $tarefa, Request $request)
    {
        $usuario = $request->user();

        // só master pode excluir
        abort_unless($usuario && $usuario->isMaster(), 403);

        // se quiser, garante que é da mesma empresa:
        abort_unless($tarefa->empresa_id === $usuario->empresa_id, 403);

        $tarefa->delete(); // Soft delete

        return response()->json([
            'ok'      => true,
            'message' => 'Tarefa excluída com sucesso.',
        ]);
    }




}
