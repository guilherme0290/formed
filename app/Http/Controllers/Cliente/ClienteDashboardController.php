<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\AsoSolicitacoes;
use App\Models\Cliente;
use App\Models\ClienteContrato;
use App\Models\ClienteTabelaPreco;
use App\Models\ClienteTabelaPrecoItem;
use App\Models\ContaReceber;
use App\Models\ContaReceberBaixa;

use App\Models\ContaReceberItem;
use App\Models\PgrSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TreinamentoNR;
use App\Models\TreinamentoNrDetalhes;
use App\Models\Venda;
use App\Models\VendaItem;
use App\Services\AsoGheService;
use App\Services\ContaReceberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ClienteDashboardController extends Controller
{
    /**
     * Tela inicial do Portal do Cliente.
     */
    public function index(Request $request)
    {
        $contexto = $this->resolverCliente($request);
        if ($contexto instanceof RedirectResponse) {
            return $contexto;
        }

        [$user, $cliente] = $contexto;

        [$contratoAtivo, $servicosContrato, $servicosIds] = $this->servicosLiberadosPorContrato($cliente);

        $precos = $this->precosPorContrato($contratoAtivo, $servicosIds);
        $tabela = $this->tabelaAtiva($cliente);
        if (empty(array_filter($precos))) {
            $precos = $this->precosPorServico($cliente, $tabela);
        }
        $temTabela = (bool) $tabela;
        $faturaTotal = $this->faturaTotal($cliente);
        $tarefasEmAndamento = $this->tarefasEmAndamento($cliente, false);
        $totalEmAndamento = $this->totalEmAndamento($contratoAtivo, $tarefasEmAndamento);
        $faturaTotal += $totalEmAndamento;

        $totalPago = (float) ContaReceberItem::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->where('status', 'BAIXADO')
            ->sum('valor');
        $totalGeral = $faturaTotal + $totalPago;
        $vendedorTelefone = $this->telefoneVendedor($cliente, $contratoAtivo);
        $tarefasEmAndamento = $this->tarefasEmAndamento($cliente, false);
        $totalEmAndamento = $this->totalEmAndamento($contratoAtivo, $tarefasEmAndamento);

        $servicosExecutados = [];

        return view('clientes.dashboard', [
            'user'         => $user,
            'cliente'      => $cliente,
            'temTabela'    => $temTabela,
            'precos'       => $precos,
            'faturaTotal'  => $faturaTotal,
            'totalEmAndamento' => $totalEmAndamento,
            'contratoAtivo' => $contratoAtivo,
            'servicosContrato' => $servicosContrato,
            'servicosIds' => $servicosIds,
            'vendedorTelefone' => $vendedorTelefone,
            'servicosExecutados' => $servicosExecutados,
        ]);
    }

    /**
     * Lista de servicos em andamento para o portal do cliente.
     */
    public function andamento(Request $request)
    {
        return redirect()->route('cliente.faturas');
    }

    /**
     * Tela de detalhes de faturas/serviços faturados.
     */
    public function faturas(Request $request)
    {
        $contexto = $this->resolverCliente($request);
        if ($contexto instanceof RedirectResponse) {
            return $contexto;
        }

        [$user, $cliente] = $contexto;

        [$contratoAtivo, $servicosContrato, $servicosIds] = $this->servicosLiberadosPorContrato($cliente);
        $precos = $this->precosPorContrato($contratoAtivo, $servicosIds);
        $tabela = $this->tabelaAtiva($cliente);
        if (empty(array_filter($precos))) {
            $precos = $this->precosPorServico($cliente, $tabela);
        }
        $temTabela = (bool) $tabela;
        $contasAbertasNaoVencidas = (float) ContaReceberItem::query()
            ->where('contas_receber_itens.empresa_id', $cliente->empresa_id)
            ->where('contas_receber_itens.cliente_id', $cliente->id)
            ->whereNotIn('contas_receber_itens.status', ['BAIXADO', 'CANCELADO'])
            ->whereDate('contas_receber_itens.vencimento', '>=', now()->startOfDay())
            ->selectRaw('COALESCE(SUM(GREATEST(contas_receber_itens.valor - COALESCE(baixas.total_baixado, 0), 0)), 0) as total')
            ->leftJoinSub(
                ContaReceberBaixa::query()
                    ->selectRaw('conta_receber_item_id, SUM(valor) as total_baixado')
                    ->groupBy('conta_receber_item_id'),
                'baixas',
                fn ($join) => $join->on('contas_receber_itens.id', '=', 'baixas.conta_receber_item_id')
            )
            ->value('total');

        $tarefasEmAndamento = $this->tarefasEmAndamento($cliente, false);
        $totalEmAndamento = $this->totalEmAndamento($contratoAtivo, $tarefasEmAndamento);

        $totalFaturaAberto = $this->faturaTotal($cliente) + $totalEmAndamento;
        $totalPago = (float) ContaReceberItem::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->where('status', 'BAIXADO')
            ->sum('valor');
        $totalVencido = (float) ContaReceberItem::query()
            ->where('contas_receber_itens.empresa_id', $cliente->empresa_id)
            ->where('contas_receber_itens.cliente_id', $cliente->id)
            ->whereNotIn('contas_receber_itens.status', ['BAIXADO', 'CANCELADO'])
            ->whereDate('contas_receber_itens.vencimento', '<', now()->startOfDay())
            ->selectRaw('COALESCE(SUM(GREATEST(contas_receber_itens.valor - COALESCE(baixas.total_baixado, 0), 0)), 0) as total')
            ->leftJoinSub(
                ContaReceberBaixa::query()
                    ->selectRaw('conta_receber_item_id, SUM(valor) as total_baixado')
                    ->groupBy('conta_receber_item_id'),
                'baixas',
                fn ($join) => $join->on('contas_receber_itens.id', '=', 'baixas.conta_receber_item_id')
            )
            ->value('total');

        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');
        $status = $request->input('status');

        $baixasSub = DB::table('contas_receber_baixas')
            ->selectRaw('conta_receber_item_id, SUM(valor) as total_baixado')
            ->groupBy('conta_receber_item_id');

        $contaQuery = DB::table('contas_receber_itens as cri')
            ->leftJoin('servicos as s', 's.id', '=', 'cri.servico_id')
            ->leftJoin('venda_itens as vi', 'vi.id', '=', 'cri.venda_item_id')
            ->leftJoin('vendas as v', function ($join) {
                $join->on('v.id', '=', 'cri.venda_id')
                    ->orOn('v.id', '=', 'vi.venda_id');
            })
            ->leftJoinSub($baixasSub, 'baixas', function ($join) {
                $join->on('cri.id', '=', 'baixas.conta_receber_item_id');
            })
            ->where('cri.empresa_id', $cliente->empresa_id)
            ->where('cri.cliente_id', $cliente->id)
            ->where('cri.status', '!=', 'CANCELADO')
            ->selectRaw("'conta' as origem")
            ->selectRaw('cri.id as ref_id')
            ->selectRaw('COALESCE(s.nome, cri.descricao, vi.descricao_snapshot, "Serviço") as servico')
            ->selectRaw('v.tarefa_id as tarefa_id')
            ->selectRaw('cri.data_realizacao as data_realizacao')
            ->selectRaw('cri.vencimento as vencimento')
            ->selectRaw('cri.status as status')
            ->selectRaw('cri.valor as valor')
            ->selectRaw('COALESCE(baixas.total_baixado, 0) as total_baixado')
            ->selectRaw('GREATEST(cri.valor - COALESCE(baixas.total_baixado, 0), 0) as valor_real');

        if ($status) {
            $statusFiltro = strtoupper((string) $status);
            if ($statusFiltro === 'VENCIDO') {
                $contaQuery->whereNotIn('cri.status', ['BAIXADO', 'CANCELADO'])
                    ->whereDate('cri.vencimento', '<', now()->startOfDay());
            } elseif ($statusFiltro === 'ABERTO') {
                $contaQuery->whereNotIn('cri.status', ['BAIXADO', 'CANCELADO']);
            } else {
                $contaQuery->where('cri.status', $statusFiltro);
            }
        }
        if ($dataInicio) {
            $contaQuery->whereDate('cri.data_realizacao', '>=', $dataInicio);
        }
        if ($dataFim) {
            $contaQuery->whereDate('cri.data_realizacao', '<=', $dataFim);
        }

        $vendaQuery = DB::table('venda_itens as vi')
            ->join('vendas as v', 'v.id', '=', 'vi.venda_id')
            ->leftJoin('tarefas as t', 't.id', '=', 'v.tarefa_id')
            ->leftJoin('servicos as s', 's.id', '=', 'vi.servico_id')
            ->where('v.empresa_id', $cliente->empresa_id)
            ->where('v.cliente_id', $cliente->id)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('contas_receber_itens as cri')
                    ->whereColumn('cri.venda_item_id', 'vi.id')
                    ->where('cri.status', '!=', 'CANCELADO');
            })
            ->selectRaw("'venda' as origem")
            ->selectRaw('vi.id as ref_id')
            ->selectRaw('COALESCE(s.nome, vi.descricao_snapshot, "Serviço") as servico')
            ->selectRaw('t.id as tarefa_id')
            ->selectRaw('COALESCE(DATE(t.finalizado_em), DATE(v.created_at)) as data_realizacao')
            ->selectRaw('NULL as vencimento')
            ->selectRaw("'ABERTO' as status")
            ->selectRaw('vi.subtotal_snapshot as valor')
            ->selectRaw('0 as total_baixado')
            ->selectRaw('vi.subtotal_snapshot as valor_real');

        if ($status) {
            $statusFiltro = strtoupper((string) $status);
            if ($statusFiltro === 'BAIXADO' || $statusFiltro === 'VENCIDO') {
                $vendaQuery->whereRaw('1=0');
            }
        }
        if ($dataInicio) {
            $vendaQuery->whereDate(DB::raw('COALESCE(t.finalizado_em, v.created_at)'), '>=', $dataInicio);
        }
        if ($dataFim) {
            $vendaQuery->whereDate(DB::raw('COALESCE(t.finalizado_em, v.created_at)'), '<=', $dataFim);
        }

        $union = $contaQuery->unionAll($vendaQuery);

        $itens = DB::query()
            ->fromSub($union, 'reg')
            ->orderByDesc('data_realizacao')
            ->get();
        $itensEmAberto = $this->itensEmAndamento($contratoAtivo, $cliente);
        $itens = $this->anexarDetalhesServicos($itens);
        $itensEmAberto = $this->anexarDetalhesServicos($itensEmAberto);
        $itensEmAberto = $itensEmAberto
            ->filter(function ($item) use ($status, $dataInicio, $dataFim) {
                $statusFiltro = strtoupper((string) $status);
                if ($statusFiltro === 'BAIXADO' || $statusFiltro === 'VENCIDO') {
                    return false;
                }
                if ($statusFiltro !== '' && $statusFiltro !== 'ABERTO') {
                    return false;
                }

                if (!$dataInicio && !$dataFim) {
                    return true;
                }

                if (empty($item->data_realizacao)) {
                    return false;
                }

                $dataItem = \Carbon\Carbon::parse($item->data_realizacao)->toDateString();
                if ($dataInicio && $dataItem < $dataInicio) {
                    return false;
                }
                if ($dataFim && $dataItem > $dataFim) {
                    return false;
                }

                return true;
            })
            ->values();

        return view('clientes.portal.index', [
            'activeTab' => 'faturas',
            'user'         => $user,
            'cliente'      => $cliente,
            'temTabela'    => $temTabela,
            'precos'       => $precos,
            'totalFaturaAberto' => $totalFaturaAberto,
            'totalPago' => $totalPago,
            'totalVencido' => $totalVencido,
            'itens'        => $itens,
            'itensEmAberto' => $itensEmAberto,
            'filtros' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'status' => $status,
            ],
            'contratoAtivo' => $contratoAtivo,
            'servicosContrato' => $servicosContrato,
            'servicosIds' => $servicosIds,
        ]);
    }

    /**
     * Lista de agendamentos/tarefas do cliente no portal.
     */
    public function agendamentos(Request $request)
    {
        $contexto = $this->resolverCliente($request);
        if ($contexto instanceof RedirectResponse) {
            return $contexto;
        }

        [$user, $cliente] = $contexto;

        [$contratoAtivo, $servicosContrato, $servicosIds] = $this->servicosLiberadosPorContrato($cliente);
        $precos = $this->precosPorContrato($contratoAtivo, $servicosIds);
        $tabela = $this->tabelaAtiva($cliente);
        if (empty(array_filter($precos))) {
            $precos = $this->precosPorServico($cliente, $tabela);
        }
        $temTabela = (bool) $tabela;

        $agendamentos = Tarefa::query()
            ->withTrashed()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->with([
                'servico:id,nome',
                'coluna:id,nome,slug,finaliza',
                'asoSolicitacao:id,tarefa_id,funcionario_id,unidade_id,tipo_aso,data_aso,email_aso,treinamentos',
                'asoSolicitacao.funcionario:id,nome',
                'asoSolicitacao.unidade:id,nome',
                'pgrSolicitacao:id,tarefa_id,tipo,com_art,com_pcms0,contratante_nome,obra_nome,total_trabalhadores',
                'pcmsoSolicitacao:id,tarefa_id,tipo,pgr_origem,obra_nome,obra_cnpj_contratante',
                'aprSolicitacao:id,tarefa_id,contratante_razao_social,contratante_cnpj,obra_nome,obra_endereco,atividade_data_inicio,atividade_data_termino_prevista,endereco_atividade,funcoes_envolvidas,etapas_atividade,status',
                'anexos:id,tarefa_id,servico,nome_original,path,mime_type,tamanho,created_at,uploaded_by',
            ])
            ->orderByRaw("CASE WHEN EXISTS (SELECT 1 FROM kanban_colunas kc WHERE kc.id = tarefas.coluna_id AND kc.slug = 'pendente') THEN 0 ELSE 1 END")
            ->orderByDesc('inicio_previsto')
            ->orderByDesc('id')
            ->get();
        $agendamentos = $this->anexarDetalhesAgendamentos($agendamentos);

        return view('clientes.portal.index', [
            'activeTab' => 'agendamentos',
            'user' => $user,
            'cliente' => $cliente,
            'agendamentos' => $agendamentos,
            'temTabela' => $temTabela,
            'precos' => $precos,
            'contratoAtivo' => $contratoAtivo,
            'servicosContrato' => $servicosContrato,
            'servicosIds' => $servicosIds,
        ]);
    }

    /**
     * Exclui agendamento do cliente somente se estiver na coluna pendente.
     */
    public function destroyAgendamento(Request $request, Tarefa $tarefa, ContaReceberService $contaReceberService)
    {
        $contexto = $this->resolverCliente($request);
        if ($contexto instanceof RedirectResponse) {
            return $contexto;
        }

        [$user, $cliente] = $contexto;

        abort_unless((int) $tarefa->empresa_id === (int) $cliente->empresa_id, 403);
        abort_unless((int) $tarefa->cliente_id === (int) $cliente->id, 403);

        $tarefa->loadMissing('coluna');
        $slugColuna = mb_strtolower((string) optional($tarefa->coluna)->slug);

        if ($slugColuna !== 'pendente') {
            return back()->with('erro', 'Somente tarefas na coluna Pendente podem ser excluídas pelo cliente.');
        }

        $resultado = $this->excluirTarefaComFinanceiro(
            $tarefa,
            $contaReceberService,
            (int) $user->id,
            'Excluída pelo cliente no portal.'
        );

        if (!($resultado['ok'] ?? false)) {
            return back()->with('erro', $resultado['message'] ?? 'Não foi possível excluir o agendamento.');
        }

        return back()->with('ok', 'Agendamento excluído com sucesso.');
    }

    /**
     * Resolve o cliente do portal com base na sessão; redireciona pro login em caso de falha.
     */
    private function resolverCliente(Request $request): RedirectResponse|array
    {
        $user = $request->user();

        if (!$user || !$user->id) {
            return redirect()
                ->route('login', ['redirect' => 'cliente']);
        }

        $clienteId = (int) $request->session()->get('portal_cliente_id');

        if ($clienteId <= 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Nenhum cliente selecionado. Faca login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::with('vendedor')->find($clienteId);

        if (!$cliente) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Cliente inválido. Acesse novamente pelo portal do cliente.');
        }

        return [$user, $cliente];
    }

    private function excluirTarefaComFinanceiro(
        Tarefa $tarefa,
        ContaReceberService $contaReceberService,
        int $usuarioId,
        ?string $motivo = null
    ): array {
        return DB::transaction(function () use ($tarefa, $contaReceberService, $usuarioId, $motivo) {
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
                'motivo_exclusao' => $motivo ?: $tarefa->motivo_exclusao,
                'excluido_por' => $usuarioId,
            ]);

            $tarefa->delete();

            return ['ok' => true];
        });
    }

    private function tabelaAtiva(Cliente $cliente): ?ClienteTabelaPreco
    {
        return ClienteTabelaPreco::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->where('ativa', true)
            ->first();
    }

    private function servicoIdPorTipo(Cliente $cliente, string $tipo): ?int
    {
        if (mb_strtolower($tipo) === 'aso') {
            return app(\App\Services\AsoGheService::class)
                ->resolveServicoAsoId($cliente->id, $cliente->empresa_id);
        }

        return Servico::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->whereRaw('LOWER(tipo) = ?', [mb_strtolower($tipo)])
            ->value('id');
    }

    private function precoDoServico(?ClienteTabelaPreco $tabela, ?int $servicoId): ?float
    {
        if (!$tabela || !$servicoId) {
            return null;
        }

        $item = ClienteTabelaPrecoItem::query()
            ->where('cliente_tabela_preco_id', $tabela->id)
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->orderBy('descricao')
            ->first();

        return $item?->valor_unitario ? (float) $item->valor_unitario : null;
    }

    private function precosPorServico(Cliente $cliente, ?ClienteTabelaPreco $tabela): array
    {
        $servicos = [
            'aso'          => $this->servicoIdPorTipo($cliente, 'aso'),
            'pgr'          => $this->servicoIdPorTipo($cliente, 'pgr'),
            'pcmso'        => $this->servicoIdPorTipo($cliente, 'pcmso'),
            'ltcat'        => $this->servicoIdPorTipo($cliente, 'ltcat'),
            'apr'          => $this->servicoIdPorTipo($cliente, 'apr'),
            'treinamentos' => $this->servicoIdPorTipo($cliente, 'treinamento'),
        ];

        $precos = [];
        foreach ($servicos as $slug => $servicoId) {
            $precos[$slug] = $this->precoDoServico($tabela, $servicoId);
        }

        $precos['aso'] = null;

        return $precos;
    }

    private function faturaTotal(Cliente $cliente): float
    {
        $contasAberto = (float) ContaReceberItem::query()
            ->where('contas_receber_itens.empresa_id', $cliente->empresa_id)
            ->where('contas_receber_itens.cliente_id', $cliente->id)
            ->where('contas_receber_itens.status', '!=', 'CANCELADO')
            ->selectRaw('COALESCE(SUM(GREATEST(contas_receber_itens.valor - COALESCE(baixas.total_baixado, 0), 0)), 0) as total')
            ->leftJoinSub(
                ContaReceberBaixa::query()
                    ->selectRaw('conta_receber_item_id, SUM(valor) as total_baixado')
                    ->groupBy('conta_receber_item_id'),
                'baixas',
                fn ($join) => $join->on('contas_receber_itens.id', '=', 'baixas.conta_receber_item_id')
            )
            ->value('total');

        $vendasSemConta = (float) Venda::query()
            ->where('cliente_id', $cliente->id)
            ->whereHas('tarefa.coluna', function ($q) {
                $q->where('finaliza', true);
            })
            ->whereDoesntHave('itens.contasReceberItens', function ($q) {
                $q->where('status', '!=', 'CANCELADO');
            })
            ->sum('total');

        return $contasAberto + $vendasSemConta;
    }

    private function tarefasEmAndamento(Cliente $cliente, bool $withRelations): \Illuminate\Support\Collection
    {
        $query = Tarefa::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->whereNull('finalizado_em')
            ->whereHas('coluna', function ($q) {
                $q->where('finaliza', false);
            })
            ->orderByDesc('updated_at');

        if ($withRelations) {
            $query->with(['servico', 'coluna']);
        } else {
            $query->select(['id', 'cliente_id', 'empresa_id', 'servico_id', 'coluna_id', 'updated_at']);
        }

        return $query->get();
    }

    private function totalEmAndamento(?ClienteContrato $contratoAtivo, iterable $tarefas): float
    {
        if (!$contratoAtivo) {
            return 0.0;
        }

        $asoServicoId = app(AsoGheService::class)->resolveServicoAsoIdFromContrato($contratoAtivo);
        $dadosAso = $this->dadosAsoPorTarefaIds($tarefas, $asoServicoId);
        $dadosPgr = $this->dadosPgrPorTarefaIds($tarefas);
        $itensPorServico = $contratoAtivo->itens->keyBy('servico_id');
        $servicoTreinamentosNrId = (int) Servico::query()
            ->where('empresa_id', $contratoAtivo->empresa_id)
            ->where('nome', 'Treinamentos NRs')
            ->value('id');

        $tarefaIds = $tarefas->pluck('id')->map(fn ($id) => (int) $id)->all();
        $treinamentoDetalhes = TreinamentoNrDetalhes::query()
            ->whereIn('tarefa_id', $tarefaIds)
            ->get(['tarefa_id', 'treinamentos'])
            ->keyBy('tarefa_id');
        $treinamentoQtdParticipantes = TreinamentoNR::query()
            ->whereIn('tarefa_id', $tarefaIds)
            ->selectRaw('tarefa_id, COUNT(*) as total')
            ->groupBy('tarefa_id')
            ->pluck('total', 'tarefa_id');
        $servicoArtId = (int) Servico::query()
            ->where('empresa_id', $contratoAtivo->empresa_id)
            ->whereRaw('LOWER(nome) = ?', ['art'])
            ->value('id');
        $servicoPcmsoId = (int) Servico::query()
            ->where('empresa_id', $contratoAtivo->empresa_id)
            ->whereRaw('LOWER(nome) = ?', ['pcmso'])
            ->value('id');
        $total = 0.0;

        foreach ($tarefas as $tarefa) {
            if ($asoServicoId && (int) $tarefa->servico_id === (int) $asoServicoId) {
                $tipoAso = $dadosAso[$tarefa->id]['tipo_aso'] ?? null;
                $funcaoId = $dadosAso[$tarefa->id]['funcao_id'] ?? null;
                $valorAso = $this->valorAsoContratoPorFuncao($contratoAtivo, $funcaoId, $tipoAso);
                if ($valorAso !== null) {
                    $total += $valorAso;
                    continue;
                }
            }
            if ($servicoTreinamentosNrId > 0 && (int) $tarefa->servico_id === $servicoTreinamentosNrId) {
                $payload = $treinamentoDetalhes->get((int) $tarefa->id)?->treinamentos ?? [];
                $qtdParticipantes = (int) ($treinamentoQtdParticipantes[(int) $tarefa->id] ?? 0);
                $valor = $this->valorTreinamentoNrContrato($contratoAtivo, $servicoTreinamentosNrId, $payload, $qtdParticipantes);
                if ($valor !== null && $valor > 0) {
                    $total += $valor;
                }
            } else {
                $valor = (float) ($itensPorServico->get($tarefa->servico_id)->preco_unitario_snapshot ?? 0);
                if ($valor > 0) {
                    $total += $valor;
                }
            }

            $pgrComArt = (bool) ($dadosPgr[$tarefa->id]['com_art'] ?? false);
            if ($pgrComArt && $servicoArtId > 0) {
                $valorArt = (float) ($itensPorServico->get($servicoArtId)->preco_unitario_snapshot ?? 0);
                if ($valorArt > 0) {
                    $total += $valorArt;
                }
            }

            $pgrComPcmso = (bool) ($dadosPgr[$tarefa->id]['com_pcms0'] ?? false);
            if ($pgrComPcmso && $servicoPcmsoId > 0) {
                $valorPcmso = (float) ($itensPorServico->get($servicoPcmsoId)->preco_unitario_snapshot ?? 0);
                if ($valorPcmso > 0) {
                    $total += $valorPcmso;
                }
            }
        }

        return $total;
    }

    private function anexarValoresEmAndamento(?ClienteContrato $contratoAtivo, iterable $tarefas): float
    {
        $total = 0.0;

        if (!$contratoAtivo) {
            foreach ($tarefas as $tarefa) {
                $tarefa->setAttribute('valor_estimado', null);
            }

            return $total;
        }

        $asoServicoId = app(AsoGheService::class)->resolveServicoAsoIdFromContrato($contratoAtivo);
        $dadosAso = $this->dadosAsoPorTarefaIds($tarefas, $asoServicoId);
        $itensPorServico = $contratoAtivo->itens->keyBy('servico_id');
        foreach ($tarefas as $tarefa) {
            if ($asoServicoId && (int) $tarefa->servico_id === (int) $asoServicoId) {
                $tipoAso = $dadosAso[$tarefa->id]['tipo_aso'] ?? null;
                $funcaoId = $dadosAso[$tarefa->id]['funcao_id'] ?? null;
                $valorAso = $this->valorAsoContratoPorFuncao($contratoAtivo, $funcaoId, $tipoAso);
                if ($valorAso !== null) {
                    $tarefa->setAttribute('valor_estimado', $valorAso);
                    $total += $valorAso;
                    continue;
                }
            }
            $valor = (float) ($itensPorServico->get($tarefa->servico_id)->preco_unitario_snapshot ?? 0);
            if ($valor > 0) {
                $tarefa->setAttribute('valor_estimado', $valor);
                $total += $valor;
            } else {
                $tarefa->setAttribute('valor_estimado', null);
            }
        }

        return $total;
    }

    private function dadosAsoPorTarefaIds(iterable $tarefas, ?int $asoServicoId): array
    {
        if (!$asoServicoId) {
            return [];
        }

        $ids = collect($tarefas)
            ->filter(fn ($tarefa) => (int) $tarefa->servico_id === (int) $asoServicoId)
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        return AsoSolicitacoes::query()
            ->whereIn('tarefa_id', $ids)
            ->with('funcionario:id,funcao_id')
            ->get()
            ->mapWithKeys(function ($aso) {
                return [
                    $aso->tarefa_id => [
                        'tipo_aso' => $aso->tipo_aso,
                        'funcao_id' => $aso->funcionario?->funcao_id,
                    ],
                ];
            })
            ->all();
    }

    private function dadosPgrPorTarefaIds(iterable $tarefas): array
    {
        $ids = collect($tarefas)
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        return PgrSolicitacoes::query()
            ->whereIn('tarefa_id', $ids)
            ->get(['tarefa_id', 'com_art', 'com_pcms0'])
            ->mapWithKeys(function ($pgr) {
                return [
                    (int) $pgr->tarefa_id => [
                        'com_art' => (bool) $pgr->com_art,
                        'com_pcms0' => (bool) $pgr->com_pcms0,
                    ],
                ];
            })
            ->all();
    }

    private function valorAsoContratoPorFuncao(?ClienteContrato $contrato, ?int $funcaoId, ?string $tipoAso): ?float
    {
        if (!$contrato || !$funcaoId || !$tipoAso) {
            return null;
        }

        return app(AsoGheService::class)->resolvePrecoAsoPorFuncaoTipo($contrato, $funcaoId, $tipoAso);
    }

    private function itensEmAndamento(?ClienteContrato $contratoAtivo, Cliente $cliente): \Illuminate\Support\Collection
    {
        if (!$contratoAtivo) {
            return collect();
        }

        $tarefas = $this->tarefasEmAndamento($cliente, true);
        if ($tarefas->isEmpty()) {
            return collect();
        }

        $ids = $tarefas->pluck('id')->filter()->values()->all();
        if (!empty($ids)) {
            $idsComVenda = Venda::query()
                ->whereIn('tarefa_id', $ids)
                ->pluck('tarefa_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if (!empty($idsComVenda)) {
                $tarefas = $tarefas->reject(fn ($t) => in_array((int) $t->id, $idsComVenda, true));
            }
        }

        if ($tarefas->isEmpty()) {
            return collect();
        }

        $asoServicoId = app(AsoGheService::class)->resolveServicoAsoIdFromContrato($contratoAtivo);
        $dadosAso = $this->dadosAsoPorTarefaIds($tarefas, $asoServicoId);
        $dadosPgr = $this->dadosPgrPorTarefaIds($tarefas);
        $itensPorServico = $contratoAtivo->itens->keyBy('servico_id');
        $servicoTreinamentosNrId = (int) Servico::query()
            ->where('empresa_id', $contratoAtivo->empresa_id)
            ->where('nome', 'Treinamentos NRs')
            ->value('id');
        $tarefaIds = $tarefas->pluck('id')->map(fn ($id) => (int) $id)->all();
        $treinamentoDetalhes = TreinamentoNrDetalhes::query()
            ->whereIn('tarefa_id', $tarefaIds)
            ->get(['tarefa_id', 'treinamentos'])
            ->keyBy('tarefa_id');
        $treinamentoQtdParticipantes = TreinamentoNR::query()
            ->whereIn('tarefa_id', $tarefaIds)
            ->selectRaw('tarefa_id, COUNT(*) as total')
            ->groupBy('tarefa_id')
            ->pluck('total', 'tarefa_id');
        $servicoArtId = (int) Servico::query()
            ->where('empresa_id', $contratoAtivo->empresa_id)
            ->whereRaw('LOWER(nome) = ?', ['art'])
            ->value('id');
        $servicoPcmsoId = (int) Servico::query()
            ->where('empresa_id', $contratoAtivo->empresa_id)
            ->whereRaw('LOWER(nome) = ?', ['pcmso'])
            ->value('id');

        return $tarefas->map(function ($tarefa) use (
            $asoServicoId,
            $dadosAso,
            $dadosPgr,
            $itensPorServico,
            $contratoAtivo,
            $servicoArtId,
            $servicoPcmsoId,
            $servicoTreinamentosNrId,
            $treinamentoDetalhes,
            $treinamentoQtdParticipantes
        ) {
            $valor = null;
            if ($asoServicoId && (int) $tarefa->servico_id === (int) $asoServicoId) {
                $tipoAso = $dadosAso[$tarefa->id]['tipo_aso'] ?? null;
                $funcaoId = $dadosAso[$tarefa->id]['funcao_id'] ?? null;
                $valor = $this->valorAsoContratoPorFuncao($contratoAtivo, $funcaoId, $tipoAso);
            } elseif ($servicoTreinamentosNrId > 0 && (int) $tarefa->servico_id === $servicoTreinamentosNrId) {
                $payload = $treinamentoDetalhes->get((int) $tarefa->id)?->treinamentos ?? [];
                $qtdParticipantes = (int) ($treinamentoQtdParticipantes[(int) $tarefa->id] ?? 0);
                $valor = $this->valorTreinamentoNrContrato($contratoAtivo, $servicoTreinamentosNrId, $payload, $qtdParticipantes);
            } else {
                $valor = (float) ($itensPorServico->get($tarefa->servico_id)->preco_unitario_snapshot ?? 0);
            }

            $pgrComArt = (bool) ($dadosPgr[$tarefa->id]['com_art'] ?? false);
            if ($pgrComArt && $servicoArtId > 0) {
                $valorArt = (float) ($itensPorServico->get($servicoArtId)->preco_unitario_snapshot ?? 0);
                if ($valor !== null) {
                    $valor += $valorArt;
                } else {
                    $valor = $valorArt;
                }
            }

            $pgrComPcmso = (bool) ($dadosPgr[$tarefa->id]['com_pcms0'] ?? false);
            if ($pgrComPcmso && $servicoPcmsoId > 0) {
                $valorPcmso = (float) ($itensPorServico->get($servicoPcmsoId)->preco_unitario_snapshot ?? 0);
                if ($valor !== null) {
                    $valor += $valorPcmso;
                } else {
                    $valor = $valorPcmso;
                }
            }

            if ($valor === null) {
                return null;
            }

            return (object) [
                'origem' => 'andamento',
                'tarefa_id' => $tarefa->id,
                'servico' => $tarefa->servico?->nome ?? 'Serviço',
                'status' => 'EM ANDAMENTO',
                'data_realizacao' => $tarefa->created_at,
                'vencimento' => null,
                'valor' => $valor,
                'valor_real' => $valor,
            ];
        })->filter()->values();
    }

    private function valorTreinamentoNrContrato(
        ?ClienteContrato $contrato,
        int $servicoTreinamentosNrId,
        mixed $payloadRaw,
        int $qtdParticipantes
    ): ?float {
        if (!$contrato || $servicoTreinamentosNrId <= 0) {
            return null;
        }

        $qtd = max(1, $qtdParticipantes);
        $payload = is_array($payloadRaw) ? $payloadRaw : [];
        $modo = strtolower((string) ($payload['modo'] ?? 'avulso'));

        $contrato->loadMissing('itens', 'parametroOrigem.itens');

        if ($modo === 'pacote') {
            $contratoItemId = (int) ($payload['pacote']['contrato_item_id'] ?? 0);
            if ($contratoItemId > 0) {
                $itemPacote = $contrato->itens
                    ->first(fn ($it) => (int) $it->id === $contratoItemId && (bool) $it->ativo);
                if ($itemPacote && (float) $itemPacote->preco_unitario_snapshot > 0) {
                    return (float) $itemPacote->preco_unitario_snapshot * $qtd;
                }
            }
        }

        $codigos = [];
        if ($modo === 'pacote') {
            $codigos = (array) ($payload['pacote']['codigos'] ?? []);
        } elseif (array_key_exists('codigos', $payload)) {
            $codigos = (array) ($payload['codigos'] ?? []);
        } else {
            $codigos = (array) $payload;
        }

        $codigos = array_values(array_filter(array_map(
            fn ($codigo) => $this->normalizarCodigoTreinamento((string) $codigo),
            $codigos
        )));

        $itemGenerico = $contrato->itens
            ->first(fn ($it) => (int) $it->servico_id === $servicoTreinamentosNrId && (bool) $it->ativo);

        if (empty($codigos)) {
            return $itemGenerico ? (float) $itemGenerico->preco_unitario_snapshot * $qtd : null;
        }

        $mapa = $this->buildMapaContratoTreinamentos($contrato, $servicoTreinamentosNrId);
        $soma = 0.0;
        foreach ($codigos as $codigo) {
            $item = $mapa[$codigo] ?? null;
            if ($item && (float) $item->preco_unitario_snapshot > 0) {
                $soma += (float) $item->preco_unitario_snapshot;
            } elseif ($itemGenerico && (float) $itemGenerico->preco_unitario_snapshot > 0) {
                // Regra comercial: cada NR selecionada soma 1x o valor unitário do treinamento.
                $soma += (float) $itemGenerico->preco_unitario_snapshot;
            }
        }

        if ($soma <= 0) {
            if ($itemGenerico && (float) $itemGenerico->preco_unitario_snapshot > 0) {
                return (float) $itemGenerico->preco_unitario_snapshot * count($codigos) * $qtd;
            }
            return null;
        }

        return $soma * $qtd;
    }

    /**
     * @return array<string, \App\Models\ClienteContratoItem>
     */
    private function buildMapaContratoTreinamentos(ClienteContrato $contrato, int $servicoId): array
    {
        $contrato->loadMissing('itens', 'parametroOrigem.itens');
        $itensContrato = $contrato->itens
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->values();

        $mapa = [];
        $itensOrigem = $contrato->parametroOrigem?->itens ?? collect();
        if ($itensOrigem->isNotEmpty()) {
            foreach ($itensOrigem as $origem) {
                if (strtoupper((string) ($origem->tipo ?? '')) !== 'TREINAMENTO_NR') {
                    continue;
                }

                $codigo = $origem->meta['codigo'] ?? null;
                if (!$codigo) {
                    $nome = (string) ($origem->nome ?? $origem->descricao ?? '');
                    if ($nome !== '' && preg_match('/^(NR[-\\s]?\\d+[A-Z]?)/i', $nome, $m)) {
                        $codigo = str_replace(' ', '-', $m[1]);
                    }
                }

                $codigo = $this->normalizarCodigoTreinamento((string) $codigo);
                if ($codigo === '') {
                    continue;
                }

                $descricaoSnapshot = $origem->descricao ?? $origem->nome;
                $contratoItem = $itensContrato->first(function ($item) use ($descricaoSnapshot) {
                    return trim((string) ($item->descricao_snapshot ?? '')) === trim((string) $descricaoSnapshot);
                });

                if ($contratoItem) {
                    $mapa[$codigo] = $contratoItem;
                }
            }
        }

        foreach ($itensContrato as $item) {
            $descricao = (string) ($item->descricao_snapshot ?? '');
            if ($descricao !== '' && preg_match('/(NR[-\\s]?\\d+[A-Z]?)/i', $descricao, $m)) {
                $codigo = $this->normalizarCodigoTreinamento((string) str_replace(' ', '-', $m[1]));
                if ($codigo !== '') {
                    $mapa[$codigo] = $item;
                }
            }
        }

        return $mapa;
    }

    private function normalizarCodigoTreinamento(string $codigo): string
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') {
            return '';
        }

        if (preg_match('/^NR[-_]?\\d+$/i', $codigo)) {
            $numero = preg_replace('/\\D/', '', $codigo);
            $codigo = 'NR-' . str_pad((string) $numero, 2, '0', STR_PAD_LEFT);
        }

        return $codigo;
    }

    private function anexarDetalhesAgendamentos(\Illuminate\Support\Collection $tarefas): \Illuminate\Support\Collection
    {
        if ($tarefas->isEmpty()) {
            return $tarefas;
        }

        $itens = $tarefas->map(function ($tarefa) {
            return (object) [
                'tarefa_id' => (int) $tarefa->id,
                'servico' => (string) ($tarefa->servico?->nome ?? 'Serviço'),
            ];
        });

        $detalhesPorTarefa = $this->anexarDetalhesServicos($itens)->keyBy('tarefa_id');

        return $tarefas->map(function ($tarefa) use ($detalhesPorTarefa) {
            $detalhes = $detalhesPorTarefa->get((int) $tarefa->id);
            if (!$detalhes) {
                return $tarefa;
            }

            foreach ([
                'servico_detalhe',
                'aso_colaborador',
                'aso_tipo',
                'aso_data',
                'aso_unidade',
                'aso_email',
                'pgr_tipo',
                'pgr_obra',
                'pgr_com_art',
                'pgr_com_pcms0',
                'pgr_total',
                'pgr_contratante',
                'treinamento_modo',
                'treinamento_codigos',
                'treinamento_pacote',
                'treinamento_local',
                'treinamento_unidade',
                'treinamento_participantes',
                'treinamento_qtd',
            ] as $campo) {
                if (property_exists($detalhes, $campo)) {
                    $tarefa->setAttribute($campo, $detalhes->{$campo});
                }
            }

            return $tarefa;
        });
    }

    private function anexarDetalhesServicos(\Illuminate\Support\Collection $itens): \Illuminate\Support\Collection
    {
        if ($itens->isEmpty()) {
            return $itens;
        }

        $tarefaIds = $itens->pluck('tarefa_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($tarefaIds)) {
            return $itens;
        }

        $dadosAso = AsoSolicitacoes::query()
            ->whereIn('tarefa_id', $tarefaIds)
            ->with(['funcionario:id,nome', 'unidade:id,nome'])
            ->get()
            ->keyBy('tarefa_id');

        $dadosPgr = PgrSolicitacoes::query()
            ->whereIn('tarefa_id', $tarefaIds)
            ->get()
            ->keyBy('tarefa_id');

        $treinamentoDetalhes = TreinamentoNrDetalhes::query()
            ->whereIn('tarefa_id', $tarefaIds)
            ->with('unidade:id,nome')
            ->get()
            ->keyBy('tarefa_id');

        $treinamentoParticipantes = TreinamentoNR::query()
            ->whereIn('tarefa_id', $tarefaIds)
            ->with('funcionario:id,nome')
            ->get()
            ->groupBy('tarefa_id');

        $mapTipo = [
            'admissional' => 'Admissional',
            'periodico' => 'Periódico',
            'demissional' => 'Demissional',
            'mudanca_funcao' => 'Mudança de Função',
            'retorno_trabalho' => 'Retorno ao Trabalho',
        ];

        return $itens->map(function ($item) use ($dadosAso, $dadosPgr, $treinamentoDetalhes, $treinamentoParticipantes, $mapTipo) {
            $tarefaId = (int) ($item->tarefa_id ?? 0);
            if ($tarefaId > 0 && $dadosAso->has($tarefaId)) {
                $aso = $dadosAso->get($tarefaId);
                $tipo = $mapTipo[$aso->tipo_aso] ?? ($aso->tipo_aso ? ucfirst($aso->tipo_aso) : null);
                $nome = $aso->funcionario?->nome;
                $item->aso_colaborador = $nome;
                $item->aso_tipo = $tipo;
                $item->aso_data = $aso->data_aso;
                $item->aso_unidade = $aso->unidade?->nome;
                $item->aso_email = $aso->email_aso;
                $item->aso_treinamentos = is_array($aso->treinamentos) ? $aso->treinamentos : [];
                if ($nome || $tipo) {
                    $item->servico_detalhe = 'ASO' . ($nome ? ' - ' . $nome : '') . ($tipo ? ' | ' . $tipo : '');
                }
            }

            if ($tarefaId > 0 && $dadosPgr->has($tarefaId)) {
                $pgr = $dadosPgr->get($tarefaId);
                $tipoLabel = $pgr->tipo === 'especifico'
                    ? 'Específico'
                    : ($pgr->tipo === 'matriz' ? 'Matriz' : ($pgr->tipo ? ucfirst($pgr->tipo) : null));
                $item->pgr_tipo = $tipoLabel;
                $item->pgr_obra = $pgr->obra_nome;
                $item->pgr_com_art = (bool) $pgr->com_art;
                $item->pgr_com_pcms0 = (bool) $pgr->com_pcms0;
                $item->pgr_total = $pgr->total_trabalhadores;
                $item->pgr_contratante = $pgr->contratante_nome;
                $servicoAtual = mb_strtolower((string) ($item->servico ?? ''));
                if (str_contains($servicoAtual, 'pgr')) {
                    $tituloBase = $item->pgr_com_pcms0 ? 'PCMSO' : 'PGR';
                    $composicoes = [];
                    if ($item->pgr_com_art) {
                        $composicoes[] = 'COM ART';
                    }
                    if ($item->pgr_com_pcms0) {
                        $composicoes[] = $tituloBase === 'PCMSO' ? 'COM PGR' : 'COM PCMSO';
                    }
                    $sufixoComposicao = !empty($composicoes) ? ' (' . implode(' + ', $composicoes) . ')' : '';
                    if ($pgr->obra_nome) {
                        $item->servico_detalhe = $tituloBase . ' - ' . $pgr->obra_nome . $sufixoComposicao;
                    } elseif ($tipoLabel) {
                        $item->servico_detalhe = $tituloBase . ' | ' . $tipoLabel . $sufixoComposicao;
                    }
                } elseif (str_contains($servicoAtual, 'pcms')) {
                    if ($pgr->obra_nome) {
                        $item->servico_detalhe = 'PCMSO - ' . $pgr->obra_nome;
                    }
                } elseif (str_contains($servicoAtual, 'art')) {
                    if ($pgr->obra_nome) {
                        $item->servico_detalhe = 'ART - ' . $pgr->obra_nome;
                    }
                }
            }

            if ($tarefaId > 0 && $treinamentoDetalhes->has($tarefaId)) {
                $det = $treinamentoDetalhes->get($tarefaId);
                $payload = $det->treinamentos ?? [];
                $modo = is_array($payload) ? ($payload['modo'] ?? 'avulso') : 'avulso';
                $codigos = [];
                $pacote = null;
                if ($modo === 'pacote') {
                    $pacote = (array) ($payload['pacote'] ?? []);
                    $codigos = (array) ($pacote['codigos'] ?? []);
                } else {
                    $codigos = (array) ($payload['codigos'] ?? $payload);
                }
                $codigos = array_values(array_filter(array_map('strval', $codigos)));
                $participantes = $treinamentoParticipantes->get($tarefaId, collect())
                    ->pluck('funcionario.nome')
                    ->filter()
                    ->sort()
                    ->values()
                    ->all();

                $item->treinamento_modo = $modo;
                $item->treinamento_codigos = $codigos;
                $item->treinamento_pacote = $pacote['nome'] ?? null;
                $item->treinamento_local = $det->local_tipo;
                $item->treinamento_unidade = $det->unidade?->nome ?? null;
                $item->treinamento_participantes = $participantes;
                $item->treinamento_qtd = count($participantes);

                $descricao = 'Treinamentos NRs';
                if ($modo === 'pacote' && !empty($item->treinamento_pacote)) {
                    $descricao .= ' - ' . $item->treinamento_pacote;
                }
                if (!empty($codigos)) {
                    $descricao .= ' | ' . implode(', ', $codigos);
                }
                $item->servico_detalhe = $descricao;
            }

            return $item;
        });
    }


    private function contratoAtivo(Cliente $cliente): ?ClienteContrato
    {
        $hoje = now()->toDateString();

        return ClienteContrato::query()
            ->where('empresa_id', $cliente->empresa_id)
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
    }

    private function servicosIdsContrato(Cliente $cliente): array
    {
        $servicosIds = [
            'aso' => app(\App\Services\AsoGheService::class)
                ->resolveServicoAsoId($cliente->id, $cliente->empresa_id),
        ];

        $tipos = [
            'pgr' => ['pgr', 'pgr'],
            'pcmso' => ['pcmso', 'pcmso'],
            'ltcat' => ['ltcat', 'ltcat'],
            'apr' => ['apr', 'apr'],
            'treinamentos' => ['treinamento', 'treinamentos nrs'],
        ];

        foreach ($tipos as $slug => $variants) {
            $variants = array_map(fn ($v) => mb_strtolower($v), $variants);
            $id = Servico::query()
                ->where('empresa_id', $cliente->empresa_id)
                ->where(function ($q) use ($variants) {
                    foreach ($variants as $v) {
                        $q->orWhereRaw('LOWER(tipo) = ?', [$v])
                          ->orWhereRaw('LOWER(nome) = ?', [$v]);
                    }
                })
                ->value('id');
            $servicosIds[$slug] = $id;
        }

        return $servicosIds;
    }

    private function servicosLiberadosPorContrato(Cliente $cliente): array
    {
        $hoje = now()->toDateString();

        $contratoAtivo = ClienteContrato::query()
            ->where('empresa_id', $cliente->empresa_id)
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
                ->where('empresa_id', $cliente->empresa_id)
                ->where(function ($q) use ($variants) {
                    foreach ($variants as $v) {
                        $q->orWhereRaw('LOWER(tipo) = ?', [$v])
                          ->orWhereRaw('LOWER(nome) = ?', [$v]);
                    }
                })
                ->value('id');
            $servicosIds[$slug] = $id;
        }

        return [$contratoAtivo, $servicosContrato, $servicosIds];
    }

    private function precosPorContrato(?ClienteContrato $contrato, array $servicosIds): array
    {
        $precos = [];
        if (!$contrato) {
            foreach (array_keys($servicosIds) as $slug) {
                $precos[$slug] = null;
            }
            return $precos;
        }

        foreach ($servicosIds as $slug => $servicoId) {
            if (!$servicoId) {
                $precos[$slug] = null;
                continue;
            }

            if ($slug === 'aso') {
                $precos[$slug] = null;
                continue;
            }

            if ($slug === 'treinamentos') {
                $totalTreinamentos = (float) $contrato->itens()
                    ->where('servico_id', $servicoId)
                    ->where('ativo', true)
                    ->sum('preco_unitario_snapshot');
                $precos[$slug] = $totalTreinamentos > 0 ? $totalTreinamentos : null;
                continue;
            }

            $item = $contrato->itens()
                ->where('servico_id', $servicoId)
                ->where('ativo', true)
                ->orderBy('descricao_snapshot')
                ->first();

            $precos[$slug] = $item?->preco_unitario_snapshot ? (float) $item->preco_unitario_snapshot : null;
        }

        return $precos;
    }

    private function telefoneVendedor(Cliente $cliente, ?ClienteContrato $contratoAtivo): string
    {
        $vendedorId = (int) ($contratoAtivo?->vendedor_id ?? 0);
        $vendedor = $vendedorId > 0 ? \App\Models\User::find($vendedorId) : null;
        $telefone = preg_replace('/\D+/', '', $vendedor?->telefone ?? '');
        if ($telefone !== '') {
            return $telefone;
        }

        return preg_replace('/\D+/', '', optional($cliente->vendedor)->telefone ?? '');
    }

    private function servicosExecutadosNoContrato(?ClienteContrato $contratoAtivo, Cliente $cliente): array
    {
        if (!$contratoAtivo) {
            return [];
        }

        $itensPorServico = $contratoAtivo->itens
            ->where('ativo', true)
            ->filter(fn ($it) => !empty($it->servico_id))
            ->groupBy('servico_id')
            ->map(fn ($rows) => $rows->count());

        if ($itensPorServico->isEmpty()) {
            return [];
        }

        $query = Tarefa::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('finalizado_em')
            ->whereIn('servico_id', $itensPorServico->keys()->all());

        if (!empty($contratoAtivo->vigencia_inicio)) {
            $query->whereDate('finalizado_em', '>=', $contratoAtivo->vigencia_inicio);
        } elseif (!empty($contratoAtivo->created_at)) {
            $query->whereDate('finalizado_em', '>=', $contratoAtivo->created_at);
        }

        $executadosPorServico = $query
            ->select('servico_id', DB::raw('COUNT(*) as total'))
            ->groupBy('servico_id')
            ->pluck('total', 'servico_id');

        $bloqueados = [];
        foreach ($itensPorServico as $servicoId => $qtdContratada) {
            $qtdExecutada = (int) ($executadosPorServico[$servicoId] ?? 0);
            if ($qtdExecutada >= $qtdContratada) {
                $bloqueados[] = (int) $servicoId;
            }
        }

        return $bloqueados;
    }
}
