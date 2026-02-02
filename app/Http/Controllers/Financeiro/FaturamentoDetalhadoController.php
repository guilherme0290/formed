<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ContaReceberBaixa;
use App\Models\ContaReceberItem;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FaturamentoDetalhadoController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $user = $request->user();
            if (!$user || (!$user->hasPapel('Master') && !$user->hasPapel('Financeiro'))) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id ?? null;
        $filters = $this->resolveFilters($request, $empresaId);

        $base = $this->buildBaseQuery($empresaId, $filters['baixas_sub']);
        $this->applyFilters($base, $filters['data_inicio'], $filters['data_fim'], $filters['cliente_selecionado'], $filters['status_selecionado']);

        $totalsBase = $this->buildTotalsQuery($empresaId, $filters['baixas_sub']);
        $this->applyFilters($totalsBase, $filters['data_inicio'], $filters['data_fim'], $filters['cliente_selecionado'], $filters['status_selecionado']);

        $totals = $this->computeTotals($totalsBase);

        $itens = (clone $base)
            ->orderByDesc(DB::raw('COALESCE(contas_receber_itens.data_realizacao, contas_receber_itens.vencimento, contas_receber_itens.created_at)'))
            ->orderByDesc('contas_receber_itens.id')
            ->paginate(20)
            ->withQueryString();

        return view('financeiro.faturamento-detalhado', array_merge($filters, $totals, [
            'data_inicio' => $filters['data_inicio']->toDateString(),
            'data_fim' => $filters['data_fim']->toDateString(),
            'itens' => $itens,
        ]));
    }

    public function exportarPdf(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        $filters = $this->resolveFilters($request, $empresaId);

        $base = $this->buildBaseQuery($empresaId, $filters['baixas_sub']);
        $this->applyFilters($base, $filters['data_inicio'], $filters['data_fim'], $filters['cliente_selecionado'], $filters['status_selecionado']);

        $totalsBase = $this->buildTotalsQuery($empresaId, $filters['baixas_sub']);
        $this->applyFilters($totalsBase, $filters['data_inicio'], $filters['data_fim'], $filters['cliente_selecionado'], $filters['status_selecionado']);

        $totals = $this->computeTotals($totalsBase);

        $itens = (clone $base)
            ->orderByDesc(DB::raw('COALESCE(contas_receber_itens.data_realizacao, contas_receber_itens.vencimento, contas_receber_itens.created_at)'))
            ->orderByDesc('contas_receber_itens.id')
            ->get();

        $pdf = Pdf::loadView('financeiro.faturamento-detalhado-pdf', array_merge($filters, $totals, [
            'data_inicio' => $filters['data_inicio']->toDateString(),
            'data_fim' => $filters['data_fim']->toDateString(),
            'itens' => $itens,
        ]))->setPaper('a4', 'landscape');

        $filename = 'faturamento-detalhado-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportarExcel(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        $filters = $this->resolveFilters($request, $empresaId);

        $base = $this->buildBaseQuery($empresaId, $filters['baixas_sub']);
        $this->applyFilters($base, $filters['data_inicio'], $filters['data_fim'], $filters['cliente_selecionado'], $filters['status_selecionado']);

        $itens = (clone $base)
            ->orderByDesc(DB::raw('COALESCE(contas_receber_itens.data_realizacao, contas_receber_itens.vencimento, contas_receber_itens.created_at)'))
            ->orderByDesc('contas_receber_itens.id')
            ->get();

        $filename = 'faturamento-detalhado-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($itens, $filters) {
            $handle = fopen('php://output', 'w');
            $sep = ';';

            fputcsv($handle, [
                'Periodo',
                'Cliente (filtro)',
                'Status (filtro)',
                'Cliente',
                'Servico',
                'Descricao',
                'Data',
                'Valor',
                'Recebido',
                'Pendente',
                'Status',
            ], $sep);

            $periodo = $filters['data_inicio']->format('d/m/Y') . ' a ' . $filters['data_fim']->format('d/m/Y');
            $clienteFiltro = $filters['cliente_selecionado_label'] ?? 'Todos os clientes';
            $statusFiltro = $filters['status_selecionado_label'] ?? 'Todos';

            foreach ($itens as $item) {
                $valor = (float) ($item->valor ?? 0);
                $recebido = (float) ($item->total_baixado ?? 0);
                $pendente = max($valor - $recebido, 0);
                $status = $pendente <= 0 ? 'Recebido' : 'Pendente';
                $dataRef = $item->data_realizacao ?? $item->vencimento ?? $item->created_at;
                $dataFmt = $dataRef ? Carbon::parse($dataRef)->format('d/m/Y') : '';

                fputcsv($handle, [
                    $periodo,
                    $clienteFiltro,
                    $statusFiltro,
                    $item->cliente->razao_social ?? 'Cliente',
                    $item->servico->nome ?? 'Servico',
                    $item->descricao ?? '',
                    $dataFmt,
                    number_format($valor, 2, ',', '.'),
                    number_format($recebido, 2, ',', '.'),
                    number_format($pendente, 2, ',', '.'),
                    $status,
                ], $sep);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function parseDate(?string $raw, Carbon $fallback): Carbon
    {
        try {
            if ($raw) {
                return Carbon::parse($raw)->startOfDay();
            }
        } catch (\Throwable $e) {
            // fallback below
        }

        return $fallback->copy()->startOfDay();
    }

    private function resolveFilters(Request $request, ?int $empresaId): array
    {
        $hoje = Carbon::today();
        $dataInicio = $this->parseDate($request->query('data_inicio'), $hoje->copy()->startOfMonth());
        $dataFim = $this->parseDate($request->query('data_fim'), $hoje);
        if ($dataFim->lt($dataInicio)) {
            $dataFim = $dataInicio->copy();
        }

        $clienteSelecionadoRaw = trim((string) $request->query('cliente', 'todos'));
        $clienteSelecionado = 'todos';
        if ($clienteSelecionadoRaw !== '') {
            $clienteSelecionadoRawLower = strtolower($clienteSelecionadoRaw);
            if (!in_array($clienteSelecionadoRawLower, ['todos', 'todos os clientes'], true)) {
                if (is_numeric($clienteSelecionadoRaw) && (int) $clienteSelecionadoRaw > 0) {
                    $clienteSelecionado = (int) $clienteSelecionadoRaw;
                } else {
                    $clienteSelecionado = $clienteSelecionadoRaw;
                }
            }
        }

        $statusSelecionado = 'todos';
        if ($request->has('filtrar')) {
            $statusSelecionado = strtolower(trim((string) $request->query('status', 'todos')));
            if ($statusSelecionado === '') {
                $statusSelecionado = 'todos';
            }
            if (!in_array($statusSelecionado, ['todos', 'recebido', 'pendente'], true)) {
                $statusSelecionado = 'todos';
            }
        }

        $clientes = Cliente::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('razao_social')
            ->get(['id', 'razao_social']);

        $clienteAutocomplete = $clientes
            ->pluck('razao_social')
            ->filter()
            ->unique()
            ->values()
            ->prepend('Todos os clientes')
            ->values();

        $clienteSelecionadoLabel = 'Todos os clientes';
        if ($clienteSelecionado !== 'todos') {
            if (is_int($clienteSelecionado)) {
                $clienteSelecionadoLabel = optional($clientes->firstWhere('id', $clienteSelecionado))->razao_social ?? 'Cliente';
            } else {
                $clienteSelecionadoLabel = $clienteSelecionado;
            }
        }
        $statusSelecionadoLabel = match ($statusSelecionado) {
            'recebido' => 'Recebido',
            'pendente' => 'Pendente',
            default => 'Todos',
        };

        $baixasSub = ContaReceberBaixa::query()
            ->selectRaw('conta_receber_item_id, SUM(valor) as total_baixado')
            ->groupBy('conta_receber_item_id');

        return [
            'clientes' => $clientes,
            'cliente_autocomplete' => $clienteAutocomplete,
            'cliente_selecionado' => $clienteSelecionado,
            'cliente_selecionado_label' => $clienteSelecionadoLabel,
            'status_selecionado' => $statusSelecionado,
            'status_selecionado_label' => $statusSelecionadoLabel,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'baixas_sub' => $baixasSub,
        ];
    }

    private function buildBaseQuery(?int $empresaId, $baixasSub)
    {
        return ContaReceberItem::query()
            ->where('contas_receber_itens.empresa_id', $empresaId)
            ->where('contas_receber_itens.status', '!=', 'CANCELADO')
            ->leftJoinSub($baixasSub, 'baixas', function ($join) {
                $join->on('contas_receber_itens.id', '=', 'baixas.conta_receber_item_id');
            })
            ->select('contas_receber_itens.*', DB::raw('COALESCE(baixas.total_baixado, 0) as total_baixado'))
            ->with(['cliente', 'servico']);
    }

    private function buildTotalsQuery(?int $empresaId, $baixasSub)
    {
        return ContaReceberItem::query()
            ->where('contas_receber_itens.empresa_id', $empresaId)
            ->where('contas_receber_itens.status', '!=', 'CANCELADO')
            ->leftJoinSub($baixasSub, 'baixas', function ($join) {
                $join->on('contas_receber_itens.id', '=', 'baixas.conta_receber_item_id');
            });
    }

    private function computeTotals($totalsBase): array
    {
        $totalRecebido = (float) (clone $totalsBase)
            ->selectRaw('COALESCE(SUM(LEAST(COALESCE(baixas.total_baixado, 0), contas_receber_itens.valor)), 0) as total')
            ->value('total');

        $totalPendente = (float) (clone $totalsBase)
            ->selectRaw('COALESCE(SUM(GREATEST(contas_receber_itens.valor - COALESCE(baixas.total_baixado, 0), 0)), 0) as total')
            ->value('total');

        $totalServicos = (int) (clone $totalsBase)->count('contas_receber_itens.id');
        $totalClientes = (int) (clone $totalsBase)->distinct('contas_receber_itens.cliente_id')->count('contas_receber_itens.cliente_id');

        return [
            'total_recebido' => $totalRecebido,
            'total_pendente' => $totalPendente,
            'total_servicos' => $totalServicos,
            'total_clientes' => $totalClientes,
        ];
    }

    private function applyFilters($query, Carbon $dataInicio, Carbon $dataFim, $clienteSelecionado, string $statusSelecionado): void
    {
        $query->whereRaw(
            'COALESCE(contas_receber_itens.data_realizacao, contas_receber_itens.vencimento, contas_receber_itens.created_at) >= ?',
            [$dataInicio->toDateString()]
        );
        $query->whereRaw(
            'COALESCE(contas_receber_itens.data_realizacao, contas_receber_itens.vencimento, contas_receber_itens.created_at) <= ?',
            [$dataFim->toDateString()]
        );

        if ($clienteSelecionado !== 'todos') {
            if (is_int($clienteSelecionado)) {
                $query->where('contas_receber_itens.cliente_id', $clienteSelecionado);
            } else {
                $termo = trim((string) $clienteSelecionado);
                if ($termo !== '') {
                    $query->whereHas('cliente', function ($q) use ($termo) {
                        $q->where('razao_social', 'like', '%' . $termo . '%');
                    });
                }
            }
        }

        if ($statusSelecionado === 'recebido') {
            $query->whereRaw('COALESCE(baixas.total_baixado, 0) > 0');
        } elseif ($statusSelecionado === 'pendente') {
            $query->whereRaw('COALESCE(baixas.total_baixado, 0) < contas_receber_itens.valor');
        }
    }
}
