<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Proposta;
use App\Models\ContaReceberBaixa;
use App\Models\ContaReceberItem;
use App\Models\Tarefa;
use App\Models\UnidadeClinica;
use App\Models\User;
use App\Models\Venda;
use App\Models\VendaItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $empresaId = auth()->user()->empresa_id ?? null;

        $visaoEmpresa  = $this->metricasEmpresa($empresaId);
        $operacionais = $this->metricasOperacionais($empresaId);
        $comerciais   = $this->metricasComerciais($empresaId);
        $financeiro   = $this->metricasFinanceiras($empresaId);
        $agendamentosHoje = $this->resumoAgendamentosHoje($empresaId);

        return view('master.dashboard', [
            'visaoEmpresa'  => $visaoEmpresa,
            'operacionais' => $operacionais,
            'comerciais'   => $comerciais,
            'financeiro'   => $financeiro,
            'agendamentosHoje' => $agendamentosHoje,
        ]);
    }

    public function agendamentos(\Illuminate\Http\Request $request)
    {
        $empresaId = auth()->user()->empresa_id ?? null;
        $agendamentos = $this->metricasAgendamentos($empresaId, $request);

        return view('master.agendamentos', [
            'agendamentos' => $agendamentos,
        ]);
    }

    public function relatorioTarefas(\Illuminate\Http\Request $request)
    {
        $empresaId = auth()->user()->empresa_id ?? null;
        $data = $this->dadosRelatorioTarefas($empresaId, $request);

        return view('master.relatorio-tarefas', $data);
    }

    public function relatorioTarefasPdf(\Illuminate\Http\Request $request)
    {
        $empresaId = auth()->user()->empresa_id ?? null;
        $data = $this->dadosRelatorioTarefas($empresaId, $request);
        $data['logoData'] = $this->logoData();
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        $pdf = Pdf::loadView('master.relatorio-tarefas-pdf', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'relatorio-tarefas-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function relatorios(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->isMaster(), 403);

        $empresaId = $user->empresa_id ?? null;
        $data = $this->dadosRelatoriosMaster($empresaId, $request);

        return view('master.relatorios', $data);
    }

    public function relatoriosPdf(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->isMaster(), 403);

        $empresaId = $user->empresa_id ?? null;
        $data = $this->dadosRelatoriosMaster($empresaId, $request);
        $data['logoData'] = $this->logoData();
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        $pdf = Pdf::loadView('master.relatorios-pdf', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'relatorios-master-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function relatorioProdutividade(\Illuminate\Http\Request $request)
    {
        $empresaId = auth()->user()->empresa_id ?? null;
        $data = $this->dadosRelatorioProdutividade($empresaId, $request);

        return view('master.relatorio-produtividade', $data);
    }

    public function relatorioProdutividadePdf(\Illuminate\Http\Request $request)
    {
        $empresaId = auth()->user()->empresa_id ?? null;
        $data = $this->dadosRelatorioProdutividade($empresaId, $request);
        $data['logoData'] = $this->logoData();
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        $pdf = Pdf::loadView('master.relatorio-produtividade-pdf', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'relatorio-produtividade-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    private function metricasEmpresa(?int $empresaId): array
    {
        $clientesAtivos = \App\Models\Cliente::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->where('ativo', true)
            ->count();

        $faturamentoGlobal = (float) Venda::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->sum('total');

        // tempo médio fica fixo como solicitado
        $tempoMedio = '48h';

        $servicosConsumidos = VendaItem::query()
            ->whereHas('venda', function ($q) use ($empresaId) {
                $q->when($empresaId, fn ($qq) => $qq->where('empresa_id', $empresaId));
            })
            ->count();

        return [
            'clientes_ativos'     => $clientesAtivos,
            'faturamento_global'  => $faturamentoGlobal,
            'tempo_medio'         => $tempoMedio,
            'servicos_consumidos' => $servicosConsumidos,
        ];
    }

    private function metricasOperacionais(?int $empresaId): array
    {
        $hoje = Carbon::today()->toDateString();

        $base = Tarefa::query()
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId));

        $total = (clone $base)->count();
        $finalizadas = (clone $base)->where('kanban_colunas.finaliza', true)->count();

        $taxaConclusao = $total > 0 ? round(($finalizadas / $total) * 100) : 0;

        $atrasadas = (clone $base)
            ->where('kanban_colunas.finaliza', false)
            ->whereDate('tarefas.fim_previsto', '<', $hoje)
            ->count();

        $slaBase = (clone $base)
            ->whereNotNull('tarefas.fim_previsto')
            ->whereNotNull('tarefas.finalizado_em')
            ->where('kanban_colunas.finaliza', true);

        $slaTotal = (clone $slaBase)->count();
        $slaDentro = (clone $slaBase)
            ->whereColumn('tarefas.finalizado_em', '<=', 'tarefas.fim_previsto')
            ->count();

        $slaPercentual = $slaTotal > 0 ? round(($slaDentro / $slaTotal) * 100) : null;

        return [
            'taxa_conclusao' => $taxaConclusao,
            'atrasadas'      => $atrasadas,
            'sla_percentual' => $slaPercentual,
        ];
    }

    private function metricasComerciais(?int $empresaId): array
    {
        $vendas = Venda::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId));

        $ticketMedio = (float) $vendas->avg('total');

        $propostas = Proposta::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId));

        $totalPropostas = (clone $propostas)->count();
        $propostasFechadas = (clone $propostas)
            ->where('status', 'FECHADA')
            ->count();
        $propostasEmAberto = (clone $propostas)
            ->whereNotIn('status', ['FECHADA', 'CANCELADA'])
            ->count();

        $taxaConversao = $totalPropostas > 0 ? round(($propostasFechadas / $totalPropostas) * 100) : 0;

        return [
            'ticket_medio'        => $ticketMedio,
            'taxa_conversao'      => $taxaConversao,
            'propostas_em_aberto' => $propostasEmAberto,
        ];
    }

    private function metricasFinanceiras(?int $empresaId): array
    {
        $totalEmAberto = (float) ContaReceberItem::query()
            ->where('contas_receber_itens.empresa_id', $empresaId)
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

        $totalRecebido = (float) ContaReceberBaixa::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->sum('valor');

        return [
            'total_aberto' => $totalEmAberto,
            'total_recebido' => $totalRecebido,
        ];
    }

    private function resumoAgendamentosHoje(?int $empresaId): array
    {
        $hoje = Carbon::today()->toDateString();

        $base = Tarefa::query()
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->whereDate('tarefas.inicio_previsto', $hoje);

        $abertas = (clone $base)->where('kanban_colunas.finaliza', false)->count();
        $fechadas = (clone $base)->where('kanban_colunas.finaliza', true)->count();

        return [
            'abertas' => $abertas,
            'fechadas' => $fechadas,
            'total' => $abertas + $fechadas,
            'data' => $hoje,
        ];
    }

    private function metricasAgendamentos(?int $empresaId, \Illuminate\Http\Request $request): array
    {
        $hoje = Carbon::today();
        $dataInicio = $this->parseDate($request->query('data_inicio'), $hoje);
        $dataFim = $this->parseDate($request->query('data_fim'), $dataInicio);
        if ($dataFim->lt($dataInicio)) {
            $dataFim = $dataInicio->copy();
        }

        $servicoSelecionadoRaw = $request->query('servico', 'todos');
        $servicoSelecionado = 'todos';
        if (is_numeric($servicoSelecionadoRaw) && (int) $servicoSelecionadoRaw > 0) {
            $servicoSelecionado = (int) $servicoSelecionadoRaw;
        }

        $responsavelSelecionadoRaw = $request->query('responsavel', 'todos');
        $responsavelSelecionado = 'todos';
        if (is_numeric($responsavelSelecionadoRaw) && (int) $responsavelSelecionadoRaw > 0) {
            $responsavelSelecionado = (int) $responsavelSelecionadoRaw;
        }

        $filtroPrestados = (string) $request->query('filtro_prestados', 'finalizadas');
        if (!in_array($filtroPrestados, ['inicio_previsto', 'finalizadas'], true)) {
            $filtroPrestados = 'finalizadas';
        }

        $servicosDisponiveis = \App\Models\Servico::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereRaw('LOWER(nome) NOT IN (?, ?)', ['exame', 'esocial'])
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $responsaveisDisponiveis = User::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereHas('papel', function ($query) {
                $query->where('nome', 'Operacional');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $base = Tarefa::query()
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->whereDate('tarefas.inicio_previsto', '>=', $dataInicio->toDateString())
            ->whereDate('tarefas.inicio_previsto', '<=', $dataFim->toDateString());

        if ($servicoSelecionado !== 'todos') {
            $base->where('tarefas.servico_id', $servicoSelecionado);
        }

        if ($responsavelSelecionado !== 'todos') {
            $base->where('tarefas.responsavel_id', $responsavelSelecionado);
        }

        $contagemPorSlug = (clone $base)
            ->selectRaw("COALESCE(LOWER(kanban_colunas.slug), '') as slug, COUNT(*) as total")
            ->groupBy('slug')
            ->pluck('total', 'slug')
            ->all();

        $pendentes = ($contagemPorSlug['pendente'] ?? 0) + ($contagemPorSlug['pendentes'] ?? 0);
        $emExecucao = ($contagemPorSlug['em-execucao'] ?? 0) + ($contagemPorSlug['em_execucao'] ?? 0);
        $aguardandoFornecedor = ($contagemPorSlug['aguardando-fornecedor'] ?? 0) + ($contagemPorSlug['aguardando'] ?? 0);
        $correcao = $contagemPorSlug['correcao'] ?? 0;
        $atrasados = ($contagemPorSlug['atrasado'] ?? 0) + ($contagemPorSlug['atrasados'] ?? 0);
        $finalizadas = ($contagemPorSlug['finalizada'] ?? 0) + ($contagemPorSlug['finalizadas'] ?? 0);

        $porServico = (clone $base)
            ->leftJoin('servicos', 'servicos.id', '=', 'tarefas.servico_id')
            ->selectRaw("COALESCE(servicos.nome, 'Sem servico') as servico_nome, COUNT(*) as total")
            ->groupBy('servico_nome')
            ->orderBy('servico_nome')
            ->get();

        $mesRelatorio = (int) $request->query('mes_relatorio', 0);
        $anoRelatorio = (int) $request->query('ano_relatorio', 0);
        if ($mesRelatorio >= 1 && $mesRelatorio <= 12 && $anoRelatorio >= 2000 && $anoRelatorio <= 2100) {
            $dataRelatorio = Carbon::createFromDate($anoRelatorio, $mesRelatorio, 1)->startOfMonth();
        } else {
            $dataRelatorio = $this->parseDate($request->query('data_relatorio'), $hoje);
        }
        $janelaInicio = $dataRelatorio->copy()->startOfMonth();
        $janelaFim = $dataRelatorio->copy()->endOfMonth();
        $datasJanela = [];
        for ($diaRef = $janelaInicio->copy(); $diaRef->lte($janelaFim); $diaRef->addDay()) {
            $datasJanela[] = $diaRef->toDateString();
        }

        $aplicarFiltrosTarefas = function ($query) use ($empresaId, $servicoSelecionado, $responsavelSelecionado) {
            $query->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId));
            if ($servicoSelecionado !== 'todos') {
                $query->where('tarefas.servico_id', $servicoSelecionado);
            }
            if ($responsavelSelecionado !== 'todos') {
                $query->where('tarefas.responsavel_id', $responsavelSelecionado);
            }
        };

        $inicioJanelaDataHora = $janelaInicio->copy()->startOfDay();
        $fimJanelaDataHora = $janelaFim->copy()->endOfDay();
        $inicioJanelaData = $janelaInicio->toDateString();
        $fimJanelaData = $janelaFim->toDateString();
        $temDataPrevista = Schema::hasColumn('tarefas', 'data_prevista');
        $dataRefSql = $temDataPrevista
            ? 'COALESCE(DATE(tarefas.inicio_previsto), DATE(tarefas.data_prevista))'
            : 'DATE(tarefas.inicio_previsto)';
        $aplicarJanelaTarefas = function ($query) use (
            $temDataPrevista,
            $inicioJanelaDataHora,
            $fimJanelaDataHora,
            $inicioJanelaData,
            $fimJanelaData
        ) {
            if (!$temDataPrevista) {
                return $query->whereBetween('tarefas.inicio_previsto', [$inicioJanelaDataHora, $fimJanelaDataHora]);
            }

            return $query->where(function ($q) use ($inicioJanelaDataHora, $fimJanelaDataHora, $inicioJanelaData, $fimJanelaData) {
                $q->whereBetween('tarefas.inicio_previsto', [$inicioJanelaDataHora, $fimJanelaDataHora])
                    ->orWhere(function ($qq) use ($inicioJanelaData, $fimJanelaData) {
                        $qq->whereNull('tarefas.inicio_previsto')
                            ->whereDate('tarefas.data_prevista', '>=', $inicioJanelaData)
                            ->whereDate('tarefas.data_prevista', '<=', $fimJanelaData);
                    });
            });
        };

        $tarefasJanelaQuery = Tarefa::query();
        $aplicarFiltrosTarefas($tarefasJanelaQuery);
        $aplicarJanelaTarefas($tarefasJanelaQuery);
        $tarefasJanela = $tarefasJanelaQuery
            ->with([
                'cliente:id,razao_social',
                'responsavel:id,name',
                'servico:id,nome',
                'coluna:id,nome,finaliza',
            ])
            ->orderBy('tarefas.inicio_previsto')
            ->orderBy('tarefas.id')
            ->get();

        $tarefasJanelaPorData = $tarefasJanela->groupBy(
            fn ($tarefa) => optional($tarefa->inicio_previsto)->toDateString()
                ?? ($temDataPrevista ? optional($tarefa->data_prevista)->toDateString() : null)
                ?? optional($tarefa->created_at)->toDateString()
        );

        $unidadesCredenciadasQuery = UnidadeClinica::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId));
        if (Schema::hasColumn('unidades_clinicas', 'ativo')) {
            $unidadesCredenciadasQuery->where('ativo', true);
        }
        $unidadesCredenciadas = $unidadesCredenciadasQuery
            ->orderBy('nome')
            ->get(['id', 'nome']);

        if ($unidadesCredenciadas->isEmpty()) {
            $unidadesCredenciadas = UnidadeClinica::query()
                ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
                ->orderBy('nome')
                ->get(['id', 'nome']);
        }

        $unidadesBase = $unidadesCredenciadas
            ->map(fn ($unidade) => [
                'id' => (int) $unidade->id,
                'nome' => (string) $unidade->nome,
                'total' => 0,
            ])
            ->values()
            ->all();

        $servicosBase = ['ASO', 'PGR', 'PCMSO', 'LTCAT', 'APR', 'PAE', 'Treinamentos NR'];

        $servicosPrestadosQuery = Tarefa::query();
        $aplicarFiltrosTarefas($servicosPrestadosQuery);
        $servicosPrestadosQuery->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id');
        $servicosPrestadosQuery->where(function ($query) {
            $query->where('kanban_colunas.finaliza', true)
                ->orWhere(function ($subQuery) {
                    $subQuery->where('kanban_colunas.finaliza', false)
                        ->whereRaw("COALESCE(LOWER(kanban_colunas.slug), '') NOT LIKE 'cancel%'");
                });
        });
        $aplicarJanelaTarefas($servicosPrestadosQuery);
        $servicosPrestadosBrutoPorDia = $servicosPrestadosQuery
            ->leftJoin('servicos', 'servicos.id', '=', 'tarefas.servico_id')
            ->selectRaw($dataRefSql . " as data_ref, COALESCE(servicos.nome, 'Sem servico') as nome, COUNT(*) as total")
            ->groupBy('data_ref', 'nome')
            ->orderBy('nome')
            ->get()
            ->groupBy('data_ref')
            ->map(function ($itens) {
                return $itens
                    ->map(fn ($item) => [
                        'nome' => (string) $item->nome,
                        'total' => (int) $item->total,
                    ])
                    ->values()
                    ->all();
            })
            ->all();

        $servicosPrestadosPorDia = [];
        foreach ($datasJanela as $dataRef) {
            $contadores = array_fill_keys($servicosBase, 0);
            foreach (($servicosPrestadosBrutoPorDia[$dataRef] ?? []) as $servicoDia) {
                $rotulo = $this->mapearServicoRelatorio($servicoDia['nome'] ?? null);
                if (!$rotulo || !array_key_exists($rotulo, $contadores)) {
                    continue;
                }
                $contadores[$rotulo] += (int) ($servicoDia['total'] ?? 0);
            }

            $servicosPrestadosPorDia[$dataRef] = collect($servicosBase)
                ->map(fn ($nome) => ['nome' => $nome, 'total' => (int) ($contadores[$nome] ?? 0)])
                ->values()
                ->all();
        }

        $vendasPorTarefa = DB::table('vendas')
            ->selectRaw(
                "tarefa_id,
                COALESCE(SUM(total), 0) as total_vendas,
                MAX(CASE WHEN UPPER(status) IN ('FECHADA', 'FINALIZADA') THEN 1 ELSE 0 END) as tem_status_fechado"
            )
            ->groupBy('tarefa_id');
        $valorContratoPorTarefaSql = "(SELECT cci.preco_unitario_snapshot
            FROM cliente_contratos cc
            INNER JOIN cliente_contrato_itens cci ON cci.cliente_contrato_id = cc.id
            WHERE cc.empresa_id = tarefas.empresa_id
              AND cc.cliente_id = tarefas.cliente_id
              AND cc.status = 'ATIVO'
              AND cci.servico_id = tarefas.servico_id
              AND cci.ativo = 1
              AND (cc.vigencia_inicio IS NULL OR cc.vigencia_inicio <= DATE(COALESCE(tarefas.inicio_previsto, tarefas.created_at)))
              AND (cc.vigencia_fim IS NULL OR cc.vigencia_fim >= DATE(COALESCE(tarefas.inicio_previsto, tarefas.created_at)))
            ORDER BY cc.vigencia_inicio DESC, cc.id DESC
            LIMIT 1)";

        $vendasFinalizadasPorDia = DB::table('tarefas')
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->leftJoinSub($vendasPorTarefa, 'vendas_tarefa', function ($join) {
                $join->on('vendas_tarefa.tarefa_id', '=', 'tarefas.id');
            })
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->when($servicoSelecionado !== 'todos', fn ($q) => $q->where('tarefas.servico_id', $servicoSelecionado))
            ->when($responsavelSelecionado !== 'todos', fn ($q) => $q->where('tarefas.responsavel_id', $responsavelSelecionado))
            ->where('kanban_colunas.finaliza', true)
            ->whereNull('tarefas.deleted_at');
        $aplicarJanelaTarefas($vendasFinalizadasPorDia);
        $vendasFinalizadasPorDia = $vendasFinalizadasPorDia
            ->selectRaw(
                $dataRefSql
                . " as data_ref,
                COALESCE(SUM(
                    CASE
                        WHEN tarefas.finalizado_em IS NOT NULL OR COALESCE(vendas_tarefa.tem_status_fechado, 0) = 1
                        THEN COALESCE(vendas_tarefa.total_vendas, COALESCE($valorContratoPorTarefaSql, 0))
                        ELSE 0
                    END
                ), 0) as total"
            )
            ->groupBy('data_ref')
            ->pluck('total', 'data_ref')
            ->all();

        $vendasPendentesPorDia = DB::table('tarefas')
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->leftJoinSub($vendasPorTarefa, 'vendas_tarefa', function ($join) {
                $join->on('vendas_tarefa.tarefa_id', '=', 'tarefas.id');
            })
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->when($servicoSelecionado !== 'todos', fn ($q) => $q->where('tarefas.servico_id', $servicoSelecionado))
            ->when($responsavelSelecionado !== 'todos', fn ($q) => $q->where('tarefas.responsavel_id', $responsavelSelecionado))
            ->where('kanban_colunas.finaliza', false)
            ->whereRaw("COALESCE(LOWER(kanban_colunas.slug), '') NOT LIKE 'cancel%'")
            ->whereNull('tarefas.deleted_at');
        $aplicarJanelaTarefas($vendasPendentesPorDia);
        $vendasPendentesPorDia = $vendasPendentesPorDia
            ->selectRaw($dataRefSql . " as data_ref, COALESCE(SUM(COALESCE(vendas_tarefa.total_vendas, COALESCE($valorContratoPorTarefaSql, 0))), 0) as total")
            ->groupBy('data_ref')
            ->pluck('total', 'data_ref')
            ->all();

        $atendimentosPorUnidade = [];
        $agendamentosAsoPorDia = collect();
            if (
                Schema::hasTable('aso_solicitacoes')
            && Schema::hasColumn('aso_solicitacoes', 'tarefa_id')
            && Schema::hasColumn('aso_solicitacoes', 'unidade_id')
        ) {
            $agendamentosAsoPorDiaQuery = DB::table('tarefas')
                ->join('aso_solicitacoes', 'aso_solicitacoes.tarefa_id', '=', 'tarefas.id')
                ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                ->when($servicoSelecionado !== 'todos', fn ($q) => $q->where('tarefas.servico_id', $servicoSelecionado))
                ->when($responsavelSelecionado !== 'todos', fn ($q) => $q->where('tarefas.responsavel_id', $responsavelSelecionado))
                ->whereNull('tarefas.deleted_at');
            $agendamentosAsoPorDiaQuery->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id');
            if ($filtroPrestados === 'finalizadas') {
                $agendamentosAsoPorDiaQuery->where('kanban_colunas.finaliza', true);
            } else {
                $agendamentosAsoPorDiaQuery
                    ->where('kanban_colunas.finaliza', false)
                    ->whereRaw("COALESCE(LOWER(kanban_colunas.slug), '') NOT LIKE 'cancel%'");
            }
            $aplicarJanelaTarefas($agendamentosAsoPorDiaQuery);
            $agendamentosAsoPorDia = $agendamentosAsoPorDiaQuery
                ->selectRaw($dataRefSql . ' as data_ref, aso_solicitacoes.unidade_id as unidade_id, tarefas.id as tarefa_id')
                ->whereNotNull('aso_solicitacoes.unidade_id')
                ->distinct()
                ->get();
        }

        $agendamentosTreinamentosPorDia = collect();
        if (
            Schema::hasTable('treinamento_nr_detalhes')
            && Schema::hasColumn('treinamento_nr_detalhes', 'tarefa_id')
            && Schema::hasColumn('treinamento_nr_detalhes', 'local_tipo')
            && Schema::hasColumn('treinamento_nr_detalhes', 'unidade_id')
        ) {
            $agendamentosTreinamentosPorDiaQuery = DB::table('tarefas')
                ->join('treinamento_nr_detalhes', 'treinamento_nr_detalhes.tarefa_id', '=', 'tarefas.id')
                ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                ->when($servicoSelecionado !== 'todos', fn ($q) => $q->where('tarefas.servico_id', $servicoSelecionado))
                ->when($responsavelSelecionado !== 'todos', fn ($q) => $q->where('tarefas.responsavel_id', $responsavelSelecionado))
                ->whereNull('tarefas.deleted_at');
            $agendamentosTreinamentosPorDiaQuery->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id');
            if ($filtroPrestados === 'finalizadas') {
                $agendamentosTreinamentosPorDiaQuery->where('kanban_colunas.finaliza', true);
            } else {
                $agendamentosTreinamentosPorDiaQuery
                    ->where('kanban_colunas.finaliza', false)
                    ->whereRaw("COALESCE(LOWER(kanban_colunas.slug), '') NOT LIKE 'cancel%'");
            }
            $aplicarJanelaTarefas($agendamentosTreinamentosPorDiaQuery);
            $agendamentosTreinamentosPorDia = $agendamentosTreinamentosPorDiaQuery
                ->where('treinamento_nr_detalhes.local_tipo', 'clinica')
                ->whereNotNull('treinamento_nr_detalhes.unidade_id')
                ->selectRaw($dataRefSql . ' as data_ref, treinamento_nr_detalhes.unidade_id as unidade_id, tarefas.id as tarefa_id')
                ->distinct()
                ->get();
        }

        $chavesContadas = [];
        foreach ([$agendamentosAsoPorDia, $agendamentosTreinamentosPorDia] as $colecao) {
            foreach ($colecao as $linha) {
                $dataRef = (string) $linha->data_ref;
                $unidadeId = (int) $linha->unidade_id;
                $tarefaId = (int) ($linha->tarefa_id ?? 0);
                if ($tarefaId <= 0 || $unidadeId <= 0 || $dataRef === '') {
                    continue;
                }
                $chaveContagem = $dataRef . '|' . $unidadeId . '|' . $tarefaId;
                if (isset($chavesContadas[$chaveContagem])) {
                    continue;
                }
                $chavesContadas[$chaveContagem] = true;
                if (!isset($atendimentosPorUnidade[$dataRef])) {
                    $atendimentosPorUnidade[$dataRef] = [];
                }
                if (!isset($atendimentosPorUnidade[$dataRef][$unidadeId])) {
                    $atendimentosPorUnidade[$dataRef][$unidadeId] = 0;
                }
                $atendimentosPorUnidade[$dataRef][$unidadeId] += 1;
            }
        }

        $atendimentosPorUnidadePorDia = [];
        foreach ($datasJanela as $dataRef) {
            $totaisDia = $atendimentosPorUnidade[$dataRef] ?? [];
            $listaDia = [];
            foreach ($unidadesBase as $unidadeBase) {
                $unidadeId = (int) $unidadeBase['id'];
                $listaDia[] = [
                    'nome' => $unidadeBase['nome'],
                    'total' => (int) ($totaisDia[$unidadeId] ?? 0),
                ];
            }
            usort($listaDia, fn ($a, $b) => $b['total'] <=> $a['total']);
            $atendimentosPorUnidadePorDia[$dataRef] = $listaDia;
        }

        $resumoPorDia = [];
        foreach ($datasJanela as $dataRef) {
            $tarefasDia = $tarefasJanelaPorData->get($dataRef, collect());
            $resumoPorDia[$dataRef] = [
                'total' => $tarefasDia->count(),
                'pendentes' => $tarefasDia->filter(fn ($tarefa) => !optional($tarefa->coluna)->finaliza)->count(),
                'concluidas' => $tarefasDia->filter(fn ($tarefa) => (bool) optional($tarefa->coluna)->finaliza)->count(),
                'unidades' => $atendimentosPorUnidadePorDia[$dataRef] ?? [],
                'servicos' => $servicosPrestadosPorDia[$dataRef] ?? [],
                'total_atendimentos' => collect($atendimentosPorUnidadePorDia[$dataRef] ?? [])->sum('total'),
                'total_servicos' => collect($servicosPrestadosPorDia[$dataRef] ?? [])->sum('total'),
                'vendas_finalizadas_valor' => (float) ($vendasFinalizadasPorDia[$dataRef] ?? 0),
                'vendas_pendentes_valor' => (float) ($vendasPendentesPorDia[$dataRef] ?? 0),
            ];
        }

        return [
            'data_inicio' => $dataInicio->toDateString(),
            'data_fim' => $dataFim->toDateString(),
            'data_relatorio' => $dataRelatorio->toDateString(),
            'servicos_disponiveis' => $servicosDisponiveis,
            'servico_selecionado' => $servicoSelecionado,
            'responsaveis_disponiveis' => $responsaveisDisponiveis,
            'responsavel_selecionado' => $responsavelSelecionado,
            'filtro_prestados' => $filtroPrestados,
            'pendentes' => $pendentes,
            'em_execucao' => $emExecucao,
            'aguardando_fornecedor' => $aguardandoFornecedor,
            'correcao' => $correcao,
            'atrasados' => $atrasados,
            'finalizadas' => $finalizadas,
            'por_servico' => $porServico,
            'janela_inicio' => $janelaInicio,
            'janela_fim' => $janelaFim,
            'tarefas_janela_por_data' => $tarefasJanelaPorData,
            'resumo_por_dia' => $resumoPorDia,
        ];
    }

    private function mapearServicoRelatorio(?string $nome): ?string
    {
        $servico = strtoupper(trim((string) $nome));
        if ($servico === '') {
            return null;
        }

        if (str_contains($servico, 'TREINAMENTO')) {
            return 'Treinamentos NR';
        }

        if (str_contains($servico, 'PCMSO')) {
            return 'PCMSO';
        }

        if (str_contains($servico, 'LTCAT')) {
            return 'LTCAT';
        }

        if (preg_match('/\bPGR\b/u', $servico) === 1) {
            return 'PGR';
        }

        if (preg_match('/\bAPR\b/u', $servico) === 1) {
            return 'APR';
        }

        if (preg_match('/\bPAE\b/u', $servico) === 1) {
            return 'PAE';
        }

        if (str_contains($servico, 'ASO')) {
            return 'ASO';
        }

        return null;
    }

    private function dadosRelatorioTarefas(?int $empresaId, \Illuminate\Http\Request $request): array
    {
        $hoje = Carbon::today();
        $dataInicio = $this->parseDate($request->query('data_inicio'), $hoje);
        $dataFim = $this->parseDate($request->query('data_fim'), $dataInicio);
        if ($dataFim->lt($dataInicio)) {
            $dataFim = $dataInicio->copy();
        }

        $servicoSelecionadoRaw = $request->query('servico', 'todos');
        $servicoSelecionado = 'todos';
        if (is_numeric($servicoSelecionadoRaw) && (int) $servicoSelecionadoRaw > 0) {
            $servicoSelecionado = (int) $servicoSelecionadoRaw;
        }

        $responsavelSelecionadoRaw = $request->query('responsavel', 'todos');
        $responsavelSelecionado = 'todos';
        if (is_numeric($responsavelSelecionadoRaw) && (int) $responsavelSelecionadoRaw > 0) {
            $responsavelSelecionado = (int) $responsavelSelecionadoRaw;
        }

        $statusSelecionado = (string) $request->query('status', 'todos');

        $servicosDisponiveis = \App\Models\Servico::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereRaw('LOWER(nome) NOT IN (?, ?)', ['exame', 'esocial'])
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $responsaveisDisponiveis = User::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereHas('papel', function ($query) {
                $query->where('nome', 'Operacional');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $colunas = \App\Models\KanbanColuna::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('ordem')
            ->get(['id', 'nome', 'slug']);

        $statusSlugs = [
            'pendente',
            'em-execucao',
            'aguardando-fornecedor',
            'correcao',
            'atrasado',
            'finalizada',
        ];

        $statusOpcoes = $colunas
            ->whereIn('slug', $statusSlugs)
            ->map(fn ($coluna) => ['slug' => $coluna->slug, 'label' => $coluna->nome])
            ->values()
            ->all();

        if (!empty($statusSelecionado) && $statusSelecionado !== 'todos') {
            $statusValido = collect($statusOpcoes)->pluck('slug')->contains($statusSelecionado);
            if (!$statusValido) {
                $statusSelecionado = 'todos';
            }
        }

        $base = Tarefa::query()
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->whereDate('tarefas.inicio_previsto', '>=', $dataInicio->toDateString())
            ->whereDate('tarefas.inicio_previsto', '<=', $dataFim->toDateString());

        if ($servicoSelecionado !== 'todos') {
            $base->where('tarefas.servico_id', $servicoSelecionado);
        }

        if ($responsavelSelecionado !== 'todos') {
            $base->where('tarefas.responsavel_id', $responsavelSelecionado);
        }

        if ($statusSelecionado !== 'todos') {
            $base->where('kanban_colunas.slug', $statusSelecionado);
        }

        $contagemPorSlug = (clone $base)
            ->selectRaw("COALESCE(LOWER(kanban_colunas.slug), '') as slug, COUNT(*) as total")
            ->groupBy('slug')
            ->pluck('total', 'slug')
            ->all();

        $pendentes = ($contagemPorSlug['pendente'] ?? 0) + ($contagemPorSlug['pendentes'] ?? 0);
        $emExecucao = ($contagemPorSlug['em-execucao'] ?? 0) + ($contagemPorSlug['em_execucao'] ?? 0);
        $aguardandoFornecedor = ($contagemPorSlug['aguardando-fornecedor'] ?? 0) + ($contagemPorSlug['aguardando'] ?? 0);
        $correcao = $contagemPorSlug['correcao'] ?? 0;
        $atrasados = ($contagemPorSlug['atrasado'] ?? 0) + ($contagemPorSlug['atrasados'] ?? 0);
        $finalizadas = ($contagemPorSlug['finalizada'] ?? 0) + ($contagemPorSlug['finalizadas'] ?? 0);

        $tarefas = (clone $base)
            ->select('tarefas.*')
            ->with(['cliente', 'servico', 'responsavel', 'coluna'])
            ->orderBy('tarefas.inicio_previsto')
            ->orderBy('tarefas.id')
            ->get();

        $servicosPorServico = (clone $base)
            ->leftJoin('servicos', 'servicos.id', '=', 'tarefas.servico_id')
            ->selectRaw("COALESCE(servicos.nome, 'Sem serviço') as servico_nome, COUNT(*) as total")
            ->groupBy('servico_nome')
            ->orderBy('servico_nome')
            ->get();

        return [
            'data_inicio' => $dataInicio->toDateString(),
            'data_fim' => $dataFim->toDateString(),
            'servicos_disponiveis' => $servicosDisponiveis,
            'responsaveis_disponiveis' => $responsaveisDisponiveis,
            'status_opcoes' => $statusOpcoes,
            'servico_selecionado' => $servicoSelecionado,
            'responsavel_selecionado' => $responsavelSelecionado,
            'status_selecionado' => $statusSelecionado,
            'tarefas' => $tarefas,
            'resumo' => [
                'pendentes' => $pendentes,
                'em_execucao' => $emExecucao,
                'aguardando_fornecedor' => $aguardandoFornecedor,
                'correcao' => $correcao,
                'atrasados' => $atrasados,
                'finalizadas' => $finalizadas,
                'total' => $tarefas->count(),
            ],
            'servicos_por_servico' => $servicosPorServico,
        ];
    }

    private function dadosRelatorioProdutividade(?int $empresaId, \Illuminate\Http\Request $request): array
    {
        $hoje = Carbon::today();
        $dataInicio = $this->parseDate($request->query('data_inicio'), $hoje);
        $dataFim = $this->parseDate($request->query('data_fim'), $dataInicio);
        if ($dataFim->lt($dataInicio)) {
            $dataFim = $dataInicio->copy();
        }

        $setorSelecionado = (string) $request->query('setor', 'todos');
        if (!in_array($setorSelecionado, ['todos', 'operacional', 'comercial'], true)) {
            $setorSelecionado = 'todos';
        }

        $usuarioSelecionadoRaw = $request->query('usuario', 'todos');
        $usuarioSelecionado = 'todos';
        if (is_numeric($usuarioSelecionadoRaw) && (int) $usuarioSelecionadoRaw > 0) {
            $usuarioSelecionado = (int) $usuarioSelecionadoRaw;
        }

        $statusPropostaSelecionado = (string) $request->query('status_proposta', 'FECHADA');

        $usuariosQuery = User::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereHas('papel', function ($query) use ($setorSelecionado) {
                if ($setorSelecionado === 'operacional') {
                    $query->where('nome', 'Operacional');
                } elseif ($setorSelecionado === 'comercial') {
                    $query->where('nome', 'Comercial');
                } else {
                    $query->whereIn('nome', ['Operacional', 'Comercial']);
                }
            })
            ->orderBy('name');

        $usuariosDisponiveis = $usuariosQuery->get(['id', 'name']);

        $statusPropostaOpcoes = Proposta::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->values()
            ->all();

        if (empty($statusPropostaOpcoes)) {
            $statusPropostaOpcoes = ['FECHADA'];
        }

        if (!in_array($statusPropostaSelecionado, $statusPropostaOpcoes, true)) {
            $statusPropostaSelecionado = 'FECHADA';
        }

        $servicosBase = Tarefa::query()
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->join('servicos', 'servicos.id', '=', 'tarefas.servico_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->whereDate('tarefas.finalizado_em', '>=', $dataInicio->toDateString())
            ->whereDate('tarefas.finalizado_em', '<=', $dataFim->toDateString())
            ->where('kanban_colunas.finaliza', true)
            ->where(function ($q) {
                $q->whereRaw('LOWER(servicos.nome) <> ?', ['exame'])
                    ->whereRaw('LOWER(servicos.tipo) <> ?', ['exame']);
            });

        if ($setorSelecionado !== 'comercial' && $usuarioSelecionado !== 'todos') {
            $servicosBase->where('tarefas.responsavel_id', $usuarioSelecionado);
        }

        $servicosTotal = (clone $servicosBase)->count();

        $servicosTarefas = (clone $servicosBase)
            ->select('tarefas.*')
            ->with(['cliente', 'servico', 'responsavel', 'coluna'])
            ->orderBy('tarefas.finalizado_em', 'desc')
            ->get();

        $servicosPorServico = (clone $servicosBase)
            ->selectRaw('servicos.nome as servico_nome, COUNT(*) as total')
            ->groupBy('servicos.nome')
            ->orderBy('servicos.nome')
            ->get();


        if ($tarefasAtivas) {
            $servicosResumoBase = null;
            if ($statusServicosSelecionado === 'concluido') {
                $servicosResumoBase = clone $tarefasBase;
            } elseif ($statusServicosSelecionado === 'todos') {
                $servicosResumoBase = Tarefa::query()
                    ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
                    ->leftJoin('servicos', 'servicos.id', '=', 'tarefas.servico_id')
                    ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                    ->whereDate('tarefas.inicio_previsto', '>=', $dataInicio->toDateString())
                    ->whereDate('tarefas.inicio_previsto', '<=', $dataFim->toDateString());

                if ($servicoSelecionado !== 'todos' && $servicoSelecionado !== 'proposta') {
                    $servicosResumoBase->where('tarefas.servico_id', $servicoSelecionado);
                }

                if ($usuarioSelecionado !== 'todos') {
                    $servicosResumoBase->where('tarefas.responsavel_id', $usuarioSelecionado);
                }
            } elseif ($tarefasPendentesBase) {
                $statusSlugsMap = [
                    'pendente' => ['pendente', 'pendentes'],
                    'em_execucao' => ['em-execucao', 'em_execucao'],
                    'atrasado' => ['atrasado', 'atrasados'],
                ];
                $statusSlugs = $statusSlugsMap[$statusServicosSelecionado] ?? [];
                $servicosResumoBase = clone $tarefasPendentesBase;
                if (!empty($statusSlugs)) {
                    $servicosResumoBase->whereIn('kanban_colunas.slug', $statusSlugs);
                }
            }

            if ($servicosResumoBase) {
                $servicosResumo = (clone $servicosResumoBase)
                    ->selectRaw("COALESCE(servicos.nome, 'Sem servico') as servico_nome, COUNT(*) as total")
                    ->groupBy('servico_nome')
                    ->orderBy('servico_nome')
                    ->get();
            }
        }

        $propostasBase = Proposta::query()
            ->with(['cliente', 'vendedor'])
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereDate('updated_at', '>=', $dataInicio->toDateString())
            ->whereDate('updated_at', '<=', $dataFim->toDateString());

        if ($statusPropostaSelecionado !== 'todos') {
            $propostasBase->where('status', $statusPropostaSelecionado);
        }

        if ($setorSelecionado !== 'operacional' && $usuarioSelecionado !== 'todos') {
            $propostasBase->where('vendedor_id', $usuarioSelecionado);
        }

        $propostasTotal = (clone $propostasBase)->count();
        $propostasValorTotal = (float) (clone $propostasBase)->sum('valor_total');

        $propostas = (clone $propostasBase)
            ->orderBy('updated_at', 'desc')
            ->get();

        return [
            'data_inicio' => $dataInicio->toDateString(),
            'data_fim' => $dataFim->toDateString(),
            'setor_selecionado' => $setorSelecionado,
            'usuario_selecionado' => $usuarioSelecionado,
            'usuarios_disponiveis' => $usuariosDisponiveis,
            'status_proposta_opcoes' => $statusPropostaOpcoes,
            'status_proposta_selecionado' => $statusPropostaSelecionado,
            'servicos_total' => $servicosTotal,
            'servicos_tarefas' => $servicosTarefas,
            'servicos_por_servico' => $servicosPorServico,
            'propostas_total' => $propostasTotal,
            'propostas_valor_total' => $propostasValorTotal,
            'propostas' => $propostas,
            'produtividade_setor' => [
                'operacional' => $servicosTotal,
                'comercial' => $propostasTotal,
            ],
        ];
    }

    private function dadosRelatoriosMaster(?int $empresaId, \Illuminate\Http\Request $request): array
    {
        $hoje = Carbon::today();
        $dataInicio = $this->parseDate($request->query('data_inicio'), $hoje);
        $dataFim = $this->parseDate($request->query('data_fim'), $dataInicio);
        if ($dataFim->lt($dataInicio)) {
            $dataFim = $dataInicio->copy();
        }

        $abaSelecionada = (string) $request->query('aba', 'operacional');
        if (!in_array($abaSelecionada, ['operacional', 'comercial'], true)) {
            $abaSelecionada = 'operacional';
        }

        $usuarioSelecionadoRaw = $request->query('usuario', 'todos');
        $usuarioSelecionado = 'todos';
        if (is_numeric($usuarioSelecionadoRaw) && (int) $usuarioSelecionadoRaw > 0) {
            $usuarioSelecionado = (int) $usuarioSelecionadoRaw;
        }

        $statusSelecionado = (string) $request->query('status', 'todos');
        if (!in_array($statusSelecionado, ['todos', 'pendente', 'em_execucao', 'concluido', 'atrasado'], true)) {
            $statusSelecionado = 'todos';
        }

        $statusServicosSelecionado = (string) $request->query('status_servicos', 'concluido');
        if (!in_array($statusServicosSelecionado, ['todos', 'pendente', 'em_execucao', 'concluido', 'atrasado'], true)) {
            $statusServicosSelecionado = 'concluido';
        }

        $statusUsuarioSelecionado = (string) $request->query('status_usuario', 'todos');
        if (!in_array($statusUsuarioSelecionado, ['todos', 'pendente', 'em_execucao', 'concluido', 'atrasado'], true)) {
            $statusUsuarioSelecionado = 'todos';
        }

        $statusPropostaSelecionado = (string) $request->query('status_proposta', 'todos');
        $statusPropostaOpcoes = Venda::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereNotNull('status')
            ->selectRaw('UPPER(status) as status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->values()
            ->all();
        array_unshift($statusPropostaOpcoes, 'todos');
        $statusPropostaOpcoes = collect($statusPropostaOpcoes)->unique()->values()->all();
        if (!in_array($statusPropostaSelecionado, $statusPropostaOpcoes, true)) {
            $statusPropostaSelecionado = 'todos';
        }

        $servicoSelecionadoRaw = $request->query('servico', 'todos');
        $servicoSelecionado = 'todos';
        if ($servicoSelecionadoRaw === 'proposta') {
            $servicoSelecionado = 'proposta';
        } elseif (is_numeric($servicoSelecionadoRaw) && (int) $servicoSelecionadoRaw > 0) {
            $servicoSelecionado = (int) $servicoSelecionadoRaw;
        }

        $servicosDisponiveis = \App\Models\Servico::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereRaw('LOWER(nome) NOT IN (?, ?)', ['exame', 'esocial'])
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $papelPermitidos = $abaSelecionada === 'operacional'
            ? ['Master', 'Operacional']
            : ['Master', 'Comercial'];

        $usuariosDisponiveis = User::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereHas('papel', fn ($q) => $q->whereIn('nome', $papelPermitidos))
            ->orderBy('name')
            ->get(['id', 'name']);

        $usuarioSelecionadoLabel = 'Todos';
        if ($usuarioSelecionado !== 'todos') {
            $usuarioSelecionadoLabel = $usuariosDisponiveis->firstWhere('id', (int) $usuarioSelecionado)?->name ?? 'Usuário';
        }

        $servicoSelecionadoLabel = 'Todos';
        if ($servicoSelecionado === 'proposta') {
            $servicoSelecionadoLabel = 'Proposta Comercial';
        } elseif ($servicoSelecionado !== 'todos') {
            $servicoSelecionadoLabel = $servicosDisponiveis->firstWhere('id', (int) $servicoSelecionado)?->nome ?? 'Serviço';
        }

        $statusSelecionadoLabel = match ($statusSelecionado) {
            'pendente' => 'Pendente',
            'em_execucao' => 'Em execução',
            'concluido' => 'Concluído',
            'atrasado' => 'Atrasado',
            default => 'Todos',
        };
        $statusPropostaSelecionadoLabel = $statusPropostaSelecionado;

        $tarefas = collect();
        $totalServicos = 0;
        $executados = 0;
        $atrasados = 0;
        $pendentes = 0;
        $servicosResumo = collect();
        $servicosLancadosResumo = collect();

        $tarefasAtivas = $abaSelecionada === 'operacional';
        $tarefasBase = Tarefa::query()
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->leftJoin('servicos', 'servicos.id', '=', 'tarefas.servico_id')
            ->leftJoin('users as responsavel', 'responsavel.id', '=', 'tarefas.responsavel_id')
            ->leftJoin('papeis as papel', 'papel.id', '=', 'responsavel.papel_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->whereDate('tarefas.finalizado_em', '>=', $dataInicio->toDateString())
            ->whereDate('tarefas.finalizado_em', '<=', $dataFim->toDateString())
            ->where('kanban_colunas.finaliza', true);

        if ($servicoSelecionado !== 'todos' && $servicoSelecionado !== 'proposta') {
            $tarefasBase->where('tarefas.servico_id', $servicoSelecionado);
        }

        if ($usuarioSelecionado !== 'todos') {
            $tarefasBase->where('tarefas.responsavel_id', $usuarioSelecionado);
        }

        if ($tarefasAtivas) {
            $totalServicos = (clone $tarefasBase)->count();
            $executados = $totalServicos;
            $atrasados = 0;
            $pendentes = 0;

            $tarefas = (clone $tarefasBase)
                ->select('tarefas.*')
                ->with(['cliente', 'servico', 'responsavel.papel', 'coluna'])
                ->orderBy('tarefas.finalizado_em', 'desc')
                ->orderBy('tarefas.id')
                ->get();
        }

        if ($tarefasAtivas) {
            $servicosLancadosBase = Tarefa::query()
                ->leftJoin('servicos', 'servicos.id', '=', 'tarefas.servico_id')
                ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                ->whereDate('tarefas.inicio_previsto', '>=', $dataInicio->toDateString())
                ->whereDate('tarefas.inicio_previsto', '<=', $dataFim->toDateString());

            if ($servicoSelecionado !== 'todos' && $servicoSelecionado !== 'proposta') {
                $servicosLancadosBase->where('tarefas.servico_id', $servicoSelecionado);
            }

            if ($usuarioSelecionado !== 'todos') {
                $servicosLancadosBase->where('tarefas.responsavel_id', $usuarioSelecionado);
            }

            $servicosLancadosResumo = (clone $servicosLancadosBase)
                ->selectRaw("COALESCE(servicos.nome, 'Sem servio') as servico_nome, COUNT(*) as total")
                ->groupBy('servico_nome')
                ->orderBy('servico_nome')
                ->get();
        }

        $tarefasProdBase = Tarefa::query()
            ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
            ->leftJoin('servicos', 'servicos.id', '=', 'tarefas.servico_id')
            ->leftJoin('users as responsavel', 'responsavel.id', '=', 'tarefas.responsavel_id')
            ->leftJoin('papeis as papel', 'papel.id', '=', 'responsavel.papel_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->where('kanban_colunas.finaliza', true)
            ->whereDate('tarefas.finalizado_em', '>=', $dataInicio->toDateString())
            ->whereDate('tarefas.finalizado_em', '<=', $dataFim->toDateString());

        if ($servicoSelecionado !== 'todos' && $servicoSelecionado !== 'proposta') {
            $tarefasProdBase->where('tarefas.servico_id', $servicoSelecionado);
        }

        if ($usuarioSelecionado !== 'todos') {
            $tarefasProdBase->where('tarefas.responsavel_id', $usuarioSelecionado);
        }

        $prodServicosTotal = $tarefasAtivas ? (clone $tarefasProdBase)->count() : 0;
        $prodServicosPorUsuario = $tarefasAtivas
            ? (clone $tarefasProdBase)
                ->selectRaw('tarefas.responsavel_id as usuario_id, COUNT(*) as total')
                ->groupBy('tarefas.responsavel_id')
                ->pluck('total', 'usuario_id')
                ->all()
            : [];
        $prodPendentesPorUsuario = [];
        $prodAtrasadosPorUsuario = [];
        $tarefasPendentesBase = null;
        if ($tarefasAtivas) {
            $tarefasPendentesBase = Tarefa::query()
                ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
                ->leftJoin('servicos', 'servicos.id', '=', 'tarefas.servico_id')
                ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                ->where('kanban_colunas.finaliza', false)
                ->whereDate('tarefas.inicio_previsto', '>=', $dataInicio->toDateString())
                ->whereDate('tarefas.inicio_previsto', '<=', $dataFim->toDateString());

            if ($servicoSelecionado !== 'todos' && $servicoSelecionado !== 'proposta') {
                $tarefasPendentesBase->where('tarefas.servico_id', $servicoSelecionado);
            }

            if ($usuarioSelecionado !== 'todos') {
                $tarefasPendentesBase->where('tarefas.responsavel_id', $usuarioSelecionado);
            }

            $prodPendentesPorUsuario = (clone $tarefasPendentesBase)
                ->selectRaw('tarefas.responsavel_id as usuario_id, COUNT(*) as total')
                ->groupBy('tarefas.responsavel_id')
                ->pluck('total', 'usuario_id')
                ->all();

            $prodAtrasadosPorUsuario = (clone $tarefasPendentesBase)
                ->whereIn('kanban_colunas.slug', ['atrasado', 'atrasados'])
                ->selectRaw('tarefas.responsavel_id as usuario_id, COUNT(*) as total')
                ->groupBy('tarefas.responsavel_id')
                ->pluck('total', 'usuario_id')
                ->all();
        }

        $servicosFinalizadosPorUsuario = [];
        if ($tarefasAtivas) {
            $servicosFinalizadosPorUsuario = (clone $tarefasBase)
                ->selectRaw("tarefas.responsavel_id as usuario_id, COALESCE(servicos.nome, 'Sem servico') as servico_nome")
                ->groupBy('usuario_id', 'servico_nome')
                ->orderBy('servico_nome')
                ->get()
                ->groupBy('usuario_id')
                ->map(fn ($rows) => $rows->pluck('servico_nome')->values()->all())
                ->all();
        }


        $chartServicosPorUsuario = $prodServicosPorUsuario;
        $chartPendentesPorUsuario = $prodPendentesPorUsuario;
        if ($tarefasAtivas && $statusUsuarioSelecionado !== 'todos') {
            if ($statusUsuarioSelecionado == 'concluido') {
                $chartPendentesPorUsuario = [];
            } else {
                $chartServicosPorUsuario = [];
                $statusSlugsMap = [
                    'pendente' => ['pendente', 'pendentes'],
                    'em_execucao' => ['em-execucao', 'em_execucao'],
                    'atrasado' => ['atrasado', 'atrasados'],
                ];
                $statusSlugs = $statusSlugsMap[$statusUsuarioSelecionado] ?? [];
                if ($tarefasPendentesBase && !empty($statusSlugs)) {
                    $chartPendentesPorUsuario = (clone $tarefasPendentesBase)
                        ->whereIn('kanban_colunas.slug', $statusSlugs)
                        ->selectRaw('tarefas.responsavel_id as usuario_id, COUNT(*) as total')
                        ->groupBy('tarefas.responsavel_id')
                        ->pluck('total', 'usuario_id')
                        ->all();
                } else {
                    $chartPendentesPorUsuario = [];
                }
            }
        }


        if ($tarefasAtivas) {
            $servicosResumoBase = null;
            if ($statusServicosSelecionado === 'concluido' || $statusServicosSelecionado === 'todos') {
                $servicosResumoBase = clone $tarefasBase;
            } elseif ($tarefasPendentesBase) {
                $statusSlugsMap = [
                    'pendente' => ['pendente', 'pendentes'],
                    'em_execucao' => ['em-execucao', 'em_execucao'],
                    'atrasado' => ['atrasado', 'atrasados'],
                ];
                $statusSlugs = $statusSlugsMap[$statusServicosSelecionado] ?? [];
                $servicosResumoBase = clone $tarefasPendentesBase;
                if (!empty($statusSlugs)) {
                    $servicosResumoBase->whereIn('kanban_colunas.slug', $statusSlugs);
                }
            }

            if ($servicosResumoBase) {
                $servicosResumo = (clone $servicosResumoBase)
                    ->selectRaw("COALESCE(servicos.nome, 'Sem servi??o') as servico_nome, COUNT(*) as total")
                    ->groupBy('servico_nome')
                    ->orderBy('servico_nome')
                    ->get();
            }
        }

        $vendasBase = Venda::query()
            ->leftJoin('clientes', 'clientes.id', '=', 'vendas.cliente_id')
            ->when($empresaId, fn ($q) => $q->where('vendas.empresa_id', $empresaId))
            ->whereDate('vendas.created_at', '>=', $dataInicio->toDateString())
            ->whereDate('vendas.created_at', '<=', $dataFim->toDateString());

        if ($usuarioSelecionado !== 'todos') {
            $vendasBase->where('clientes.vendedor_id', (int) $usuarioSelecionado);
        }

        $propostasAtivas = $abaSelecionada === 'comercial';
        if ($propostasAtivas && $statusPropostaSelecionado !== 'todos') {
            $vendasBase->whereRaw('UPPER(vendas.status) = ?', [mb_strtoupper($statusPropostaSelecionado)]);
        }

        $prodPropostasTotal = $propostasAtivas ? (clone $vendasBase)->count() : 0;
        $prodPropostasValorTotal = $propostasAtivas ? (float) (clone $vendasBase)->sum('vendas.total') : 0;
        $prodPropostasPorUsuario = $propostasAtivas
            ? (clone $vendasBase)
                ->selectRaw('clientes.vendedor_id as usuario_id, COUNT(*) as total')
                ->groupBy('clientes.vendedor_id')
                ->pluck('total', 'usuario_id')
                ->all()
            : [];
        $prodPropostasValorPorUsuario = $propostasAtivas
            ? (clone $vendasBase)
                ->selectRaw('clientes.vendedor_id as usuario_id, SUM(vendas.total) as total')
                ->groupBy('clientes.vendedor_id')
                ->pluck('total', 'usuario_id')
                ->all()
            : [];

        $usuariosPorId = $usuariosDisponiveis->keyBy('id');
        $topOperacionalUsuarios = collect($prodServicosPorUsuario)
            ->map(fn ($total, $id) => [
                'id' => $id,
                'label' => $usuariosPorId->get($id)?->name ?? 'UsuÃ¡rio',
                'total' => (int) $total,
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $topOperacionalServicos = $servicosResumo
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->map(fn ($row) => [
                'label' => $row->servico_nome,
                'total' => (int) $row->total,
            ]);

        $vendasBaseAll = Venda::query()
            ->leftJoin('clientes', 'clientes.id', '=', 'vendas.cliente_id')
            ->when($empresaId, fn ($q) => $q->where('vendas.empresa_id', $empresaId))
            ->whereDate('vendas.created_at', '>=', $dataInicio->toDateString())
            ->whereDate('vendas.created_at', '<=', $dataFim->toDateString());

        if ($usuarioSelecionado !== 'todos') {
            $vendasBaseAll->where('clientes.vendedor_id', (int) $usuarioSelecionado);
        }

        $comercialStatusResumo = [
            'fechadas' => 0,
            'canceladas' => 0,
            'abertas' => 0,
        ];

        if ($propostasAtivas) {
            $comercialStatus = (clone $vendasBaseAll)
                ->selectRaw('UPPER(vendas.status) as status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all();
            $fechadas = (int) ($comercialStatus['FECHADA'] ?? 0) + (int) ($comercialStatus['FATURADA'] ?? 0);
            $canceladas = (int) ($comercialStatus['CANCELADA'] ?? 0);
            $totalAll = array_sum($comercialStatus);
            $abertas = max(0, $totalAll - $fechadas - $canceladas);
            $comercialStatusResumo = [
                'fechadas' => $fechadas,
                'canceladas' => $canceladas,
                'abertas' => $abertas,
            ];
        }

        $topComercialVendedores = collect();
        $topComercialClientes = collect();
        $ticketMedio = 0;
        $topFaturamentoServicos = collect();
        if ($propostasAtivas) {
            $propostasTotalBase = (clone $vendasBase)->count();
            $propostasValorBase = (float) (clone $vendasBase)->sum('vendas.total');
            $ticketMedio = $propostasTotalBase > 0 ? ($propostasValorBase / $propostasTotalBase) : 0;

            $topComercialVendedores = (clone $vendasBase)
                ->leftJoin('users as vendedor', 'vendedor.id', '=', 'clientes.vendedor_id')
                ->selectRaw("COALESCE(vendedor.name, 'Sem vendedor') as nome, COUNT(*) as total, SUM(vendas.total) as valor_total")
                ->groupBy('nome')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $topComercialClientes = (clone $vendasBase)
                ->selectRaw("COALESCE(clientes.razao_social, 'Sem cliente') as nome, COUNT(*) as total, SUM(vendas.total) as valor_total")
                ->groupBy('nome')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $topFaturamentoServicos = VendaItem::query()
                ->join('vendas', 'vendas.id', '=', 'venda_itens.venda_id')
                ->leftJoin('clientes', 'clientes.id', '=', 'vendas.cliente_id')
                ->leftJoin('servicos', 'servicos.id', '=', 'venda_itens.servico_id')
                ->when($empresaId, fn ($q) => $q->where('vendas.empresa_id', $empresaId))
                ->whereDate('vendas.created_at', '>=', $dataInicio->toDateString())
                ->whereDate('vendas.created_at', '<=', $dataFim->toDateString())
                ->when($usuarioSelecionado !== 'todos', fn ($q) => $q->where('clientes.vendedor_id', (int) $usuarioSelecionado))
                ->when($statusPropostaSelecionado !== 'todos', fn ($q) => $q->whereRaw('UPPER(vendas.status) = ?', [mb_strtoupper($statusPropostaSelecionado)]))
                ->selectRaw("COALESCE(servicos.nome, venda_itens.descricao_snapshot, 'Sem serviço') as nome, COUNT(*) as total, SUM(venda_itens.subtotal_snapshot) as valor_total")
                ->groupBy('servicos.nome', 'venda_itens.descricao_snapshot')
                ->orderByDesc('valor_total')
                ->limit(5)
                ->get();
        }

        $produtividadeUsuariosAll = $usuariosDisponiveis->map(function ($usuario) use ($prodServicosPorUsuario, $prodPropostasPorUsuario, $prodPropostasValorPorUsuario, $prodPendentesPorUsuario, $prodAtrasadosPorUsuario) {
            $servicos = $prodServicosPorUsuario[$usuario->id] ?? 0;
            $propostas = $prodPropostasPorUsuario[$usuario->id] ?? 0;
            $pendentes = $prodPendentesPorUsuario[$usuario->id] ?? 0;
            $atrasadas = $prodAtrasadosPorUsuario[$usuario->id] ?? 0;
            return [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'servicos' => $servicos,
                'pendentes' => $pendentes,
                'atrasadas' => $atrasadas,
                'propostas' => $propostas,
                'propostas_valor' => $prodPropostasValorPorUsuario[$usuario->id] ?? 0,
                'total' => $servicos + $propostas + $pendentes + $atrasadas,
            ];
        });

        $produtividadeTopUsuarios = $produtividadeUsuariosAll
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $produtividadeTopUsuariosServicos = $produtividadeTopUsuarios
            ->map(fn ($row) => $servicosFinalizadosPorUsuario[$row['id']] ?? [])
            ->values();

        $agendamentosHoje = $this->agendamentosPorServico($empresaId, $hoje, $hoje);
        $agendamentosAmanha = $this->agendamentosPorServico($empresaId, $hoje->copy()->addDay(), $hoje->copy()->addDay());
        $agendamentosProximos = $this->agendamentosPorServico($empresaId, $hoje->copy()->addDays(2), null);

        $resumoPeriodo = [
            'pendentes' => 0,
            'finalizadas' => 0,
            'atrasadas' => 0,
        ];
        $operacionalAtivo = 0;
        $variacaoPeriodo = [
            'pendentes' => 0,
            'finalizadas' => 0,
            'atrasadas' => 0,
            'operacional_ativo' => 0,
        ];
        if ($abaSelecionada === 'operacional') {
            $tarefasPendentesPeriodo = Tarefa::query()
                ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
                ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                ->whereDate('tarefas.inicio_previsto', '>=', $dataInicio->toDateString())
                ->whereDate('tarefas.inicio_previsto', '<=', $dataFim->toDateString())
                ->where('kanban_colunas.finaliza', false);

            if ($servicoSelecionado !== 'todos' && $servicoSelecionado !== 'proposta') {
                $tarefasPendentesPeriodo->where('tarefas.servico_id', $servicoSelecionado);
            }

            if ($usuarioSelecionado !== 'todos') {
                $tarefasPendentesPeriodo->where('tarefas.responsavel_id', $usuarioSelecionado);
            }

            $tarefasFinalizadasPeriodo = Tarefa::query()
                ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
                ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                ->whereDate('tarefas.finalizado_em', '>=', $dataInicio->toDateString())
                ->whereDate('tarefas.finalizado_em', '<=', $dataFim->toDateString())
                ->where('kanban_colunas.finaliza', true);

            if ($servicoSelecionado !== 'todos' && $servicoSelecionado !== 'proposta') {
                $tarefasFinalizadasPeriodo->where('tarefas.servico_id', $servicoSelecionado);
            }

            if ($usuarioSelecionado !== 'todos') {
                $tarefasFinalizadasPeriodo->where('tarefas.responsavel_id', $usuarioSelecionado);
            }

            $resumoPeriodo['pendentes'] = (clone $tarefasPendentesPeriodo)->count();
            $resumoPeriodo['finalizadas'] = (clone $tarefasFinalizadasPeriodo)->count();

            $tarefasAtrasadasPeriodo = (clone $tarefasPendentesPeriodo)
                ->whereDate('tarefas.fim_previsto', '<', $hoje->toDateString());
            $resumoPeriodo['atrasadas'] = $tarefasAtrasadasPeriodo->count();

            $operacionalAtivo = Tarefa::query()
                ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
                ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                ->whereDate('tarefas.inicio_previsto', '>=', $dataInicio->toDateString())
                ->whereDate('tarefas.inicio_previsto', '<=', $dataFim->toDateString())
                ->where('kanban_colunas.finaliza', false)
                ->count();

            $periodDays = $dataInicio->diffInDays($dataFim) + 1;
            $periodoAnteriorFim = $dataInicio->copy()->subDay();
            $periodoAnteriorInicio = $periodoAnteriorFim->copy()->subDays(max(0, $periodDays - 1));

            $tarefasPendentesPeriodoAnterior = Tarefa::query()
                ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
                ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                ->whereDate('tarefas.inicio_previsto', '>=', $periodoAnteriorInicio->toDateString())
                ->whereDate('tarefas.inicio_previsto', '<=', $periodoAnteriorFim->toDateString())
                ->where('kanban_colunas.finaliza', false);

            if ($servicoSelecionado !== 'todos' && $servicoSelecionado !== 'proposta') {
                $tarefasPendentesPeriodoAnterior->where('tarefas.servico_id', $servicoSelecionado);
            }

            if ($usuarioSelecionado !== 'todos') {
                $tarefasPendentesPeriodoAnterior->where('tarefas.responsavel_id', $usuarioSelecionado);
            }

            $tarefasFinalizadasPeriodoAnterior = Tarefa::query()
                ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
                ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                ->whereDate('tarefas.finalizado_em', '>=', $periodoAnteriorInicio->toDateString())
                ->whereDate('tarefas.finalizado_em', '<=', $periodoAnteriorFim->toDateString())
                ->where('kanban_colunas.finaliza', true);

            if ($servicoSelecionado !== 'todos' && $servicoSelecionado !== 'proposta') {
                $tarefasFinalizadasPeriodoAnterior->where('tarefas.servico_id', $servicoSelecionado);
            }

            if ($usuarioSelecionado !== 'todos') {
                $tarefasFinalizadasPeriodoAnterior->where('tarefas.responsavel_id', $usuarioSelecionado);
            }

            $resumoPeriodoAnterior = [
                'pendentes' => (clone $tarefasPendentesPeriodoAnterior)->count(),
                'finalizadas' => (clone $tarefasFinalizadasPeriodoAnterior)->count(),
                'atrasadas' => (clone $tarefasPendentesPeriodoAnterior)
                    ->whereDate('tarefas.fim_previsto', '<', $periodoAnteriorFim->toDateString())
                    ->count(),
            ];

            $operacionalAtivoAnterior = Tarefa::query()
                ->join('kanban_colunas', 'kanban_colunas.id', '=', 'tarefas.coluna_id')
                ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
                ->whereDate('tarefas.inicio_previsto', '>=', $periodoAnteriorInicio->toDateString())
                ->whereDate('tarefas.inicio_previsto', '<=', $periodoAnteriorFim->toDateString())
                ->where('kanban_colunas.finaliza', false)
                ->count();

            $calcVariacao = function (int $atual, int $anterior): int {
                if ($anterior === 0) {
                    return $atual > 0 ? 100 : 0;
                }
                return (int) round((($atual - $anterior) / $anterior) * 100);
            };

            $variacaoPeriodo = [
                'pendentes' => $calcVariacao($resumoPeriodo['pendentes'], $resumoPeriodoAnterior['pendentes']),
                'finalizadas' => $calcVariacao($resumoPeriodo['finalizadas'], $resumoPeriodoAnterior['finalizadas']),
                'atrasadas' => $calcVariacao($resumoPeriodo['atrasadas'], $resumoPeriodoAnterior['atrasadas']),
                'operacional_ativo' => $calcVariacao($operacionalAtivo, $operacionalAtivoAnterior),
            ];
        }

        return [
            'data_inicio' => $dataInicio->toDateString(),
            'data_fim' => $dataFim->toDateString(),
            'aba_selecionada' => $abaSelecionada,
            'usuario_selecionado' => $usuarioSelecionado,
            'servico_selecionado' => $servicoSelecionado,
            'status_selecionado' => $statusSelecionado,
            'servicos_disponiveis' => $servicosDisponiveis,
            'usuarios_disponiveis' => $usuariosDisponiveis,
            'status_proposta_selecionado' => $statusPropostaSelecionado,
            'status_proposta_opcoes' => $statusPropostaOpcoes,
            'status_servicos_selecionado' => $statusServicosSelecionado,
            'status_servicos_opcoes' => [
                ['value' => 'concluido', 'label' => 'Concluido'],
                ['value' => 'pendente', 'label' => 'Pendente'],
                ['value' => 'em_execucao', 'label' => 'Em execucao'],
                ['value' => 'atrasado', 'label' => 'Atrasado'],
                ['value' => 'todos', 'label' => 'Todos'],
            ],
            'status_usuario_selecionado' => $statusUsuarioSelecionado,
            'status_usuario_opcoes' => [
                ['value' => 'todos', 'label' => 'Todos'],
                ['value' => 'pendente', 'label' => 'Pendente'],
                ['value' => 'em_execucao', 'label' => 'Em execução'],
                ['value' => 'concluido', 'label' => 'Concluído'],
                ['value' => 'atrasado', 'label' => 'Atrasado'],
            ],
            'filtros_label' => [
                'usuario' => $usuarioSelecionadoLabel,
                'servico' => $servicoSelecionadoLabel,
                'setor' => $abaSelecionada === 'operacional' ? 'Operacional' : 'Comercial',
                'status' => $statusSelecionadoLabel,
                'status_proposta' => $statusPropostaSelecionadoLabel,
            ],
            'tarefas' => $tarefas,
            'resumo' => [
                'pendentes' => $pendentes,
                'executados' => $executados,
                'atrasados' => $atrasados,
                'total' => $totalServicos,
            ],
            'servicos_resumo' => $servicosResumo,
            'servicos_lancados_resumo' => $servicosLancadosResumo,
            'produtividade_setor' => [
                'operacional' => $abaSelecionada === 'operacional' ? $prodServicosTotal : 0,
                'comercial' => $abaSelecionada === 'comercial' ? $prodPropostasTotal : 0,
            ],
            'produtividade_valor_total' => $prodPropostasValorTotal,
            'ticket_medio' => $ticketMedio,
            'comercial_status_resumo' => $comercialStatusResumo,
            'top_comercial_vendedores' => $topComercialVendedores,
            'top_comercial_clientes' => $topComercialClientes,
            'top_faturamento_servicos' => $topFaturamentoServicos,
            'top_operacional_usuarios' => $topOperacionalUsuarios,
            'top_operacional_servicos' => $topOperacionalServicos,
            'produtividade_usuarios' => [
                'labels' => $usuariosDisponiveis->map(fn ($usuario) => $usuario->name)->all(),
                'servicos' => $usuariosDisponiveis->pluck('id')->map(fn ($id) => $chartServicosPorUsuario[$id] ?? 0)->all(),
                'pendentes' => $usuariosDisponiveis->pluck('id')->map(fn ($id) => $chartPendentesPorUsuario[$id] ?? 0)->all(),
                'atrasadas' => $usuariosDisponiveis->pluck('id')->map(fn ($id) => $prodAtrasadosPorUsuario[$id] ?? 0)->all(),
                'propostas' => $usuariosDisponiveis->pluck('id')->map(fn ($id) => $prodPropostasPorUsuario[$id] ?? 0)->all(),
                'propostas_valor' => $usuariosDisponiveis->pluck('id')->map(fn ($id) => $prodPropostasValorPorUsuario[$id] ?? 0)->all(),
            ],
            'produtividade_top_usuarios' => [
                'labels' => $produtividadeTopUsuarios->pluck('name')->all(),
                'servicos' => $produtividadeTopUsuarios->pluck('servicos')->all(),
                'pendentes' => $produtividadeTopUsuarios->pluck('pendentes')->all(),
                'atrasadas' => $produtividadeTopUsuarios->pluck('atrasadas')->all(),
                'propostas' => $produtividadeTopUsuarios->pluck('propostas')->all(),
                'propostas_valor' => $produtividadeTopUsuarios->pluck('propostas_valor')->all(),
                'total' => $produtividadeTopUsuarios->pluck('total')->all(),
                'servicos_lista' => $produtividadeTopUsuariosServicos->all(),
            ],
            'agendamentos' => [
                'data' => $hoje->format('d/m'),
                'hoje' => $agendamentosHoje,
                'amanha' => $agendamentosAmanha,
                'proximos' => $agendamentosProximos,
            ],
            'resumo_periodo' => $resumoPeriodo,
            'variacao_periodo' => $variacaoPeriodo,
            'operacional_ativo' => $operacionalAtivo,
            'status_opcoes' => [
                ['value' => 'todos', 'label' => 'Todos'],
                ['value' => 'pendente', 'label' => 'Pendente'],
                ['value' => 'em_execucao', 'label' => 'Em execução'],
                ['value' => 'concluido', 'label' => 'Concluído'],
                ['value' => 'atrasado', 'label' => 'Atrasado'],
            ],
        ];
    }

    private function agendamentosPorServico(?int $empresaId, Carbon $dataInicio, ?Carbon $dataFim): array
    {
        $servicosChave = ['aso', 'ltcat', 'ltip', 'pcmso'];

        $query = Tarefa::query()
            ->join('servicos', 'servicos.id', '=', 'tarefas.servico_id')
            ->when($empresaId, fn ($q) => $q->where('tarefas.empresa_id', $empresaId))
            ->whereRaw('LOWER(servicos.nome) IN (?, ?, ?, ?)', $servicosChave);

        if ($dataFim) {
            $query->whereDate('tarefas.inicio_previsto', '>=', $dataInicio->toDateString())
                ->whereDate('tarefas.inicio_previsto', '<=', $dataFim->toDateString());
        } else {
            $query->whereDate('tarefas.inicio_previsto', '>=', $dataInicio->toDateString());
        }

        $porServico = (clone $query)
            ->selectRaw('servicos.nome as servico_nome, COUNT(*) as total')
            ->groupBy('servicos.nome')
            ->orderBy('servicos.nome')
            ->get();

        $total = (clone $query)->count();

        return [
            'total' => $total,
            'por_servico' => $porServico,
        ];
    }

    private function parseDate($raw, Carbon $fallback): Carbon
    {
        if (is_array($raw)) {
            $raw = collect($raw)->first(fn ($value) => is_string($value) && trim($value) !== '');
        }

        if (!is_string($raw)) {
            $raw = null;
        }

        try {
            if ($raw) {
                return Carbon::parse($raw)->startOfDay();
            }
        } catch (\Throwable $e) {
            // fallback below
        }

        return $fallback->copy()->startOfDay();
    }

    private function logoData(): ?string
    {
        $logoPath = storage_path('app/public/logo (1)-transparente.png');

        if (!is_file($logoPath)) {
            return null;
        }

        $data = file_get_contents($logoPath);
        if ($data === false) {
            return null;
        }

        $mime = 'image/png';
        if (function_exists('getimagesize')) {
            $info = @getimagesize($logoPath);
            if (!empty($info['mime'])) {
                $mime = $info['mime'];
            }
        }

        if (function_exists('imagecreatefromstring') && function_exists('imagescale')) {
            $image = @imagecreatefromstring($data);
            if ($image !== false) {
                $width = imagesx($image);
                if ($width > 320) {
                    $scaled = imagescale($image, 320);
                    if ($scaled !== false) {
                        ob_start();
                        if ($mime === 'image/png') {
                            imagepng($scaled);
                        } elseif ($mime === 'image/jpeg') {
                            imagejpeg($scaled, null, 85);
                            $mime = 'image/jpeg';
                        } else {
                            imagepng($scaled);
                            $mime = 'image/png';
                        }
                        $data = (string) ob_get_clean();
                        imagedestroy($scaled);
                    }
                }
                imagedestroy($image);
            }
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
