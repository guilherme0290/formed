<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Funcao;
use App\Models\KanbanColuna;
use App\Models\PgrSolicitacoes;
use App\Models\Tarefa;
use App\Models\ClienteContrato;
use App\Models\Servico;
use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\ContaReceber;
use App\Models\ContaReceberItem;
use App\Models\Venda;
use App\Models\TarefaLog;
use App\Models\TreinamentoNR;
use App\Models\User;
use App\Models\Anexos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psy\Util\Str;
use App\Services\PrecificacaoService;
use App\Services\VendaService;
use App\Services\ComissaoService;
use App\Services\ContaReceberService;
use Illuminate\Http\JsonResponse;

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
        $filtroBusca       = trim((string) $request->input('q', ''));

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
                    'anexos.uploader',
                    'excluidoPor',
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
                'anexos.uploader',
                'excluidoPor',
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
            $tarefasQuery->whereHas('logs', function ($logQuery) use ($filtroResponsavel) {
                $logQuery->where('acao', 'criado')
                    ->where('user_id', $filtroResponsavel);
            });
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

        if ($filtroBusca !== '') {
            $tarefasQuery->where(function ($q) use ($filtroBusca) {
                $q->where('titulo', 'like', '%' . $filtroBusca . '%')
                    ->orWhere('descricao', 'like', '%' . $filtroBusca . '%')
                    ->orWhereHas('cliente', function ($clienteQuery) use ($filtroBusca) {
                        $clienteQuery->where('razao_social', 'like', '%' . $filtroBusca . '%')
                            ->orWhere('nome_fantasia', 'like', '%' . $filtroBusca . '%')
                            ->orWhere('cnpj', 'like', '%' . $filtroBusca . '%');
                    })
                    ->orWhereHas('servico', function ($servicoQuery) use ($filtroBusca) {
                        $servicoQuery->where('nome', 'like', '%' . $filtroBusca . '%');
                    })
                    ->orWhereHas('funcionario', function ($funcionarioQuery) use ($filtroBusca) {
                        $funcionarioQuery->where('nome', 'like', '%' . $filtroBusca . '%')
                            ->orWhere('cpf', 'like', '%' . $filtroBusca . '%');
                    });
            });
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

        $isMaster = $usuario->hasPapel('Master');

        $responsaveis = User::where('empresa_id', $empresaId)
            ->whereHas('papel', function ($query) {
                $query->where('nome', 'Operacional');
            })
            ->when(!$isMaster, function ($query) use ($empresaId) {
                $query->whereExists(function ($subQuery) use ($empresaId) {
                    $subQuery->select(DB::raw(1))
                        ->from('tarefa_logs as tl')
                        ->join('tarefas as t', 't.id', '=', 'tl.tarefa_id')
                        ->whereColumn('tl.user_id', 'users.id')
                        ->where('tl.acao', 'criado')
                        ->where('t.empresa_id', $empresaId);
                });
            })
            ->orderBy('name')
            ->get();

        $clienteAutocomplete = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('razao_social')
            ->get(['razao_social', 'nome_fantasia', 'cnpj'])
            ->flatMap(function ($cliente) {
                return array_filter([
                    $cliente->razao_social,
                    $cliente->nome_fantasia,
                    $cliente->cnpj,
                ]);
            })
            ->unique()
            ->values();

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
            'clienteAutocomplete' => $clienteAutocomplete,
            'filtroServico'     => $filtroServico,
            'filtroResponsavel' => $filtroResponsavel,
            'filtroColuna'      => $filtroColuna,
            'filtroDe'          => $filtroDe,
            'filtroAte'         => $filtroAte,
            'filtroBusca'       => $filtroBusca,
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

    public function prazos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $ids = $request->input('ids', []);
        $ids = is_array($ids) ? array_filter(array_map('intval', $ids)) : [];

        if (empty($ids)) {
            return response()->json([
                'ok' => true,
                'now' => now()->toIso8601String(),
                'coluna_atraso_id' => null,
                'tarefas' => [],
            ]);
        }

        $colunaAtraso = KanbanColuna::query()
            ->where('empresa_id', $empresaId)
            ->where('atraso', true)
            ->first()
            ?? KanbanColuna::query()
                ->where('empresa_id', $empresaId)
                ->where('slug', 'atrasado')
                ->first();

        $agora = now();
        $tarefas = Tarefa::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('id', $ids)
            ->with('coluna')
            ->get();

        $ordemAtraso = $colunaAtraso
            ? (int) (Tarefa::where('coluna_id', $colunaAtraso->id)->max('ordem') ?? 0)
            : 0;

        $payload = [];
        foreach ($tarefas as $tarefa) {
            $fimPrevisto = $tarefa->fim_previsto;
            $estaFinalizada = !empty($tarefa->finalizado_em) || ($tarefa->coluna?->finaliza ?? false);
            $estaAtrasada = $fimPrevisto && $fimPrevisto->lt($agora) && !$estaFinalizada;

            if ($estaAtrasada && $colunaAtraso && (int) $tarefa->coluna_id !== (int) $colunaAtraso->id) {
                $ordemAtraso += 1;
                $colunaOrigem = $tarefa->coluna_id;
                $tarefa->update([
                    'coluna_id' => $colunaAtraso->id,
                    'ordem' => $ordemAtraso,
                ]);

                TarefaLog::create([
                    'tarefa_id' => $tarefa->id,
                    'user_id' => $request->user()->id,
                    'de_coluna_id' => $colunaOrigem,
                    'para_coluna_id' => $colunaAtraso->id,
                    'acao' => 'atrasado',
                    'observacao' => 'Tarefa movida automaticamente para Atrasado (SLA excedido).',
                ]);

                $tarefa->coluna_id = $colunaAtraso->id;
                $tarefa->setRelation('coluna', $colunaAtraso);
            }

            $payload[] = [
                'id' => $tarefa->id,
                'coluna_id' => $tarefa->coluna_id,
                'coluna_nome' => $tarefa->coluna?->nome,
                'fim_previsto' => $tarefa->fim_previsto?->toIso8601String(),
            ];
        }

        return response()->json([
            'ok' => true,
            'now' => $agora->toIso8601String(),
            'coluna_atraso_id' => $colunaAtraso?->id,
            'tarefas' => $payload,
        ]);
    }




    // ==========================
    // MOVER CARD NO KANBAN
    // ==========================
    public function mover(Request $request, Tarefa $tarefa, PrecificacaoService $precificacaoService, VendaService $vendaService, ComissaoService $comissaoService)
    {
        $data = $request->validate([
            'coluna_id' => ['required', 'exists:kanban_colunas,id'],
            'ordem' => ['nullable', 'array'],
            'ordem.*' => ['integer', 'exists:tarefas,id'],
            'coluna_origem_id' => ['nullable', 'integer', 'exists:kanban_colunas,id'],
            'ordem_origem' => ['nullable', 'array'],
            'ordem_origem.*' => ['integer', 'exists:tarefas,id'],
        ]);

        $novaColunaId  = (int) $data['coluna_id'];
        $colunaAtualId = (int) $tarefa->coluna_id;
        $ordemDestino = $data['ordem'] ?? [];
        $colunaOrigemId = (int) ($data['coluna_origem_id'] ?? 0);
        $ordemOrigem = $data['ordem_origem'] ?? [];

        if ($novaColunaId === $colunaAtualId) {
            $this->atualizarOrdem($ordemDestino, $novaColunaId);
            return response()->json(['ok' => true]);
        }

        $novaColuna = KanbanColuna::findOrFail($novaColunaId);
        $finalizando = $novaColuna->slug === 'finalizada';

        if ($finalizando) {
            try {
                $dataRef = now()->startOfDay();
                $contratoAtivo = ClienteContrato::query()
                    ->where('empresa_id', $tarefa->empresa_id)
                    ->where('cliente_id', $tarefa->cliente_id)
                    ->where('status', 'ATIVO')
                    ->where(function ($q) use ($dataRef) {
                        $q->whereNull('vigencia_inicio')->orWhere('vigencia_inicio', '<=', $dataRef);
                    })
                    ->where(function ($q) use ($dataRef) {
                        $q->whereNull('vigencia_fim')->orWhere('vigencia_fim', '>=', $dataRef);
                    })
                    ->with('itens')
                    ->latest('vigencia_inicio')
                    ->first();
                $asoServicoId = app(\App\Services\AsoGheService::class)->resolveServicoAsoIdFromContrato($contratoAtivo);
                $isAso = $asoServicoId && (int) $tarefa->servico_id === (int) $asoServicoId;

                $servicoTreinamentoId = (int) Servico::where('empresa_id', $tarefa->empresa_id)
                    ->where('nome', 'Treinamentos NRs')
                    ->value('id');

                if ($isAso) {
                    $resultado = $precificacaoService->precificarAso($tarefa);
                    $venda = $vendaService->criarVendaPorTarefaItens($tarefa, $resultado['contrato'], $resultado['itensVenda']);

                    $vendedorId = optional($tarefa->cliente)->vendedor_id;
                    $comissaoService->gerarPorVenda($venda, $resultado['itemContrato'], $vendedorId ?: auth()->id());
                } elseif ($servicoTreinamentoId && (int) $tarefa->servico_id === $servicoTreinamentoId) {
                    $resultado = $precificacaoService->precificarTreinamentosNr($tarefa);
                    $venda = $vendaService->criarVendaPorTarefaItens($tarefa, $resultado['contrato'], $resultado['itensVenda']);

                    $vendedorId = optional($tarefa->cliente)->vendedor_id;
                    $comissaoService->gerarPorVenda($venda, $resultado['itemContrato'], $vendedorId ?: auth()->id());
                } else {
                    $resultado = $precificacaoService->validarServicoNoContrato(
                        (int) $tarefa->cliente_id,
                        (int) $tarefa->servico_id,
                        (int) $tarefa->empresa_id
                    );
                    $venda = $vendaService->criarVendaPorTarefa($tarefa, $resultado['contrato'], $resultado['item']);

                    $vendedorId = optional($tarefa->cliente)->vendedor_id;
                    $comissaoService->gerarPorVenda($venda, $resultado['item'], $vendedorId ?: auth()->id());
                }
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

        $this->atualizarOrdem($ordemDestino, $novaColunaId);
        if ($colunaOrigemId && $colunaOrigemId !== $novaColunaId) {
            $this->atualizarOrdem($ordemOrigem, $colunaOrigemId);
        }

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

    private function atualizarOrdem(array $ids, int $colunaId): void
    {
        if (empty($ids)) {
            return;
        }

        foreach ($ids as $index => $id) {
            Tarefa::query()
                ->where('id', $id)
                ->where('coluna_id', $colunaId)
                ->update(['ordem' => $index + 1]);
        }
    }


    public function asoSelecionarCliente(Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;
        $q         = trim((string) $request->query('q', ''));
        $qDigits   = preg_replace('/\D+/', '', $q);

        $hoje = now()->toDateString();

        $clientes = Cliente::where('empresa_id', $empresaId)
            ->when($q !== '', function ($query) use ($q, $qDigits) {
                $query->where(function ($subQuery) use ($q, $qDigits) {
                    $subQuery->where('razao_social', 'like', "%{$q}%")
                        ->orWhere('nome_fantasia', 'like', "%{$q}%")
                        ->orWhere('cnpj', 'like', "%{$q}%");

                    if ($qDigits !== '') {
                        $subQuery->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', '') LIKE ?",
                            ["%{$qDigits}%"]
                        );
                    }
                });
            })
            ->with('cidade')
            ->select('clientes.*')
            ->addSelect(['tem_contrato_ativo' => ClienteContrato::selectRaw('1')
                ->whereColumn('cliente_contratos.cliente_id', 'clientes.id')
                ->where('cliente_contratos.empresa_id', $empresaId)
                ->where('status', 'ATIVO')
                ->where(function ($q) use ($hoje) {
                    $q->whereNull('vigencia_inicio')->orWhereDate('vigencia_inicio', '<=', $hoje);
                })
                ->where(function ($q) use ($hoje) {
                    $q->whereNull('vigencia_fim')->orWhereDate('vigencia_fim', '>=', $hoje);
                })
                ->limit(1)
            ])
            ->orderByRaw(
                'CASE
                    WHEN clientes.ativo = 1 AND tem_contrato_ativo = 1 THEN 3
                    WHEN clientes.ativo = 1 THEN 2
                    WHEN tem_contrato_ativo = 1 THEN 1
                    ELSE 0
                 END DESC'
            )
            ->orderByDesc('clientes.ativo')
            ->orderByDesc('tem_contrato_ativo')
            ->orderBy('razao_social')
            ->paginate(12);

        $clienteAutocomplete = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('razao_social')
            ->get(['razao_social', 'nome_fantasia', 'cnpj'])
            ->flatMap(function ($cliente) {
                return array_filter([
                    $cliente->razao_social,
                    $cliente->nome_fantasia,
                    $cliente->cnpj,
                ]);
            })
            ->unique()
            ->values();

        return view('operacional.kanban.clientes', compact('clientes', 'q', 'clienteAutocomplete'));
    }

    public function selecionarServico(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $hoje = now()->toDateString();

        $contratoAtivo = ClienteContrato::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->where('status', 'ATIVO')
            ->where(function ($q) use ($hoje) {
                $q->whereNull('vigencia_inicio')->orWhereDate('vigencia_inicio', '<=', $hoje);
            })
            ->where(function ($q) use ($hoje) {
                $q->whereNull('vigencia_fim')->orWhereDate('vigencia_fim', '>=', $hoje);
            })
            ->with('itens')
            ->first();

        $servicosContrato = $contratoAtivo
            ? $contratoAtivo->itens->pluck('servico_id')->filter()->unique()->values()->all()
            : [];

        $asoService = app(\App\Services\AsoGheService::class);
        $asoServicoId = $asoService->resolveServicoAsoIdFromContrato($contratoAtivo);
        $tiposAsoPermitidos = $asoService->resolveTiposAsoContrato($contratoAtivo);
        if (empty($tiposAsoPermitidos)) {
            $asoServicoId = null;
        }

        $tipos = [
            'pgr' => ['pgr', 'pgr'],
            'pcmso' => ['pcmso', 'pcmso'],
            'ltcat' => ['ltcat', 'ltcat'],
            'ltip' => ['ltip', 'ltip'],
            'apr' => ['apr', 'apr'],
            'pae' => ['pae', 'pae'],
            'treinamentos' => ['treinamento', 'treinamentos nrs'],
        ];

        $servicosIds = [
            'aso' => $asoServicoId ? (int) $asoServicoId : null,
        ];
        foreach ($tipos as $slug => $variants) {
            $variants = array_map(fn ($v) => mb_strtolower($v), $variants);
            $id = Servico::query()
                ->where('empresa_id', $empresaId)
                ->where(function ($q) use ($variants) {
                    foreach ($variants as $v) {
                        $q->orWhereRaw('LOWER(tipo) = ?', [$v])
                          ->orWhereRaw('LOWER(nome) = ?', [$v]);
                    }
                })
                ->value('id');
            $servicosIds[$slug] = $id;
        }

        return view('operacional.kanban.servicos', [
            'cliente' => $cliente,
            'contratoAtivo' => $contratoAtivo,
            'servicosContrato' => $servicosContrato,
            'servicosIds' => $servicosIds,
        ]);
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
            'ok'=> true,
            'observacao_interna' => $tarefa->observacao_interna,
        ]);
    }


    public function destroy(Tarefa $tarefa, Request $request, ContaReceberService $contaReceberService)
    {
        $usuario = $request->user();

        // master ou operacional podem excluir
        abort_unless($usuario && $usuario->hasPapel(['Master', 'Operacional']), 403);

        // se quiser, garante que é da mesma empresa:
        abort_unless($tarefa->empresa_id === $usuario->empresa_id, 403);

        $data = $request->validate([
            'motivo_exclusao' => ['required', 'string', 'max:2000'],
            'arquivo_exclusao' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $resultado = DB::transaction(function () use ($tarefa, $contaReceberService, $usuario, $data, $request) {
            $vendas = Venda::query()
                ->where('tarefa_id', $tarefa->id)
                ->get();

            foreach ($vendas as $venda) {
                $itensConta = ContaReceberItem::query()
                    ->where('venda_id', $venda->id);

                $temItensNaoAbertos = (clone $itensConta)
                    ->where('status', '!=', 'ABERTO')
                    ->exists();

                if ($temItensNaoAbertos) {
                    return [
                        'ok' => false,
                        'message' => 'Não é possível excluir: existem contas a receber já baixadas ou faturadas.',
                    ];
                }

                $itensAbertos = (clone $itensConta)
                    ->where('status', 'ABERTO')
                    ->get();

                $contaIds = $itensAbertos->pluck('conta_receber_id')->filter()->unique();

                if ($itensAbertos->isNotEmpty()) {
                    ContaReceberItem::query()
                        ->whereIn('id', $itensAbertos->pluck('id'))
                        ->delete();
                }

                foreach ($contaIds as $contaId) {
                    $conta = ContaReceber::find($contaId);
                    if (!$conta) {
                        continue;
                    }

                    $temItensRestantes = $conta->itens()
                        ->where('status', '!=', 'CANCELADO')
                        ->exists();

                    if (!$temItensRestantes) {
                        $conta->delete();
                        continue;
                    }

                    $contaReceberService->recalcularConta($conta->fresh());
                }

                $venda->delete();
            }

            $tarefa->update([
                'motivo_exclusao' => $data['motivo_exclusao'],
                'excluido_por' => $usuario->id,
            ]);

            if ($request->hasFile('arquivo_exclusao')) {
                Anexos::salvarDoRequest($request, 'arquivo_exclusao', [
                    'empresa_id' => $tarefa->empresa_id,
                    'cliente_id' => $tarefa->cliente_id,
                    'tarefa_id' => $tarefa->id,
                    'uploaded_by' => $usuario->id,
                    'servico' => 'cancelamento_tarefa',
                    'subpath' => 'anexos/' . $tarefa->id . '/cancelamento',
                ]);
            }

            $tarefa->delete(); // Soft delete

            return ['ok' => true];
        });

        if (!$resultado['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $resultado['message'],
            ], 422);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Tarefa excluída com sucesso.',
        ]);
    }




}
