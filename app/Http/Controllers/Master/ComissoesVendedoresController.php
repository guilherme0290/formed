<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Comissao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ComissoesVendedoresController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id ?? null;
        [$anos, $anoSelecionado] = $this->anosDisponiveis($empresaId, $request->integer('ano'));

        $vendedorId = $this->vendedorSelecionado($request, $empresaId);
        $vendedores = $this->vendedoresDaEmpresa($empresaId);
        $vendedoresIds = $vendedores->keys()->filter()->values()->all();

        $meses = $this->mesesResumo($empresaId, $anoSelecionado, $vendedorId, $vendedoresIds);
        $ranking = $this->rankingVendedores($empresaId, $anoSelecionado, $vendedorId, $vendedoresIds);

        return view('master.comissoes.vendedores', [
            'anos' => $anos,
            'anoSelecionado' => $anoSelecionado,
            'vendedores' => $vendedores,
            'vendedorSelecionado' => $vendedorId,
            'meses' => $meses,
            'ranking' => $ranking,
        ]);
    }

    public function mes(Request $request, int $ano, int $mes): RedirectResponse
    {
        return redirect()->route('master.comissoes.vendedores.previsao', [
            'ano' => $ano,
            'mes' => $mes,
            'vendedor' => $request->integer('vendedor') ?: null,
        ]);
    }

    public function previsao(Request $request, int $ano, int $mes): View
    {
        $empresaId = $request->user()->empresa_id ?? null;
        $vendedorId = $this->vendedorSelecionado($request, $empresaId);
        $vendedoresIds = $this->vendedoresDaEmpresa($empresaId)->keys()->filter()->values()->all();

        $clientes = $this->agrupadoPorCliente($empresaId, $ano, $mes, ['PENDENTE', 'PAGA'], $vendedorId, $vendedoresIds);
        $detalhesPorCliente = $this->agrupadoPorClienteEServico($empresaId, $ano, $mes, ['PENDENTE', 'PAGA'], $vendedorId, $vendedoresIds);

        return view('master.comissoes.previsao', compact('clientes', 'detalhesPorCliente', 'ano', 'mes', 'vendedorId'));
    }

    public function efetivada(Request $request, int $ano, int $mes): View
    {
        $empresaId = $request->user()->empresa_id ?? null;
        $vendedorId = $this->vendedorSelecionado($request, $empresaId);
        $vendedoresIds = $this->vendedoresDaEmpresa($empresaId)->keys()->filter()->values()->all();

        $clientes = $this->agrupadoPorCliente($empresaId, $ano, $mes, ['PAGA'], $vendedorId, $vendedoresIds);

        return view('master.comissoes.efetivada', compact('clientes', 'ano', 'mes', 'vendedorId'));
    }

    public function inadimplentes(Request $request, int $ano, int $mes): View
    {
        $empresaId = $request->user()->empresa_id ?? null;
        $vendedorId = $this->vendedorSelecionado($request, $empresaId);
        $vendedoresIds = $this->vendedoresDaEmpresa($empresaId)->keys()->filter()->values()->all();

        $clientes = $this->agrupadoPorCliente($empresaId, $ano, $mes, ['PENDENTE'], $vendedorId, $vendedoresIds);

        return view('master.comissoes.inadimplentes', compact('clientes', 'ano', 'mes', 'vendedorId'));
    }

    private function vendedorSelecionado(Request $request, ?int $empresaId): ?int
    {
        $vendedores = $this->vendedoresDaEmpresa($empresaId);
        $vendedorId = $request->integer('vendedor');

        if ($vendedorId && !$vendedores->has($vendedorId)) {
            return null;
        }

        return $vendedorId ?: null;
    }

    private function anosDisponiveis(?int $empresaId, ?int $anoInput): array
    {
        $anos = Comissao::query()
            ->selectRaw('YEAR(COALESCE(gerada_em, created_at)) as ano')
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->distinct()
            ->orderByDesc('ano')
            ->pluck('ano')
            ->values();

        $anoSelecionado = $anoInput ?: now()->year;
        if ($anos->isNotEmpty() && !$anos->contains($anoSelecionado)) {
            $anoSelecionado = (int) $anos->first();
        }

        return [$anos, (int) $anoSelecionado];
    }

    private function vendedoresDaEmpresa(?int $empresaId): Collection
    {
        $comerciais = User::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereHas('papel', fn ($q) => $q->whereRaw('LOWER(nome) LIKE ?', ['%comercial%']))
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        if ($comerciais->isNotEmpty()) {
            return $comerciais;
        }

        $ids = Comissao::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->distinct()
            ->pluck('vendedor_id');

        return User::whereIn('id', $ids)->get()->keyBy('id');
    }

    private function mesesResumo(?int $empresaId, int $ano, ?int $vendedorId = null, array $vendedoresIds = []): Collection
    {
        if (!$vendedorId && empty($vendedoresIds)) {
            return collect(range(1, 12))->map(function ($mes) use ($ano) {
                return (object) [
                    'mes' => $mes,
                    'nome' => Carbon::createFromDate($ano, $mes, 1)->locale('pt_BR')->isoFormat('MMM'),
                    'total' => 0,
                    'total_efetivado' => 0,
                    'total_previsto' => 0,
                    'status' => 'FECHADO',
                ];
            });
        }

        $base = Comissao::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereYear(DB::raw('COALESCE(gerada_em, created_at)'), $ano);

        if ($vendedorId) {
            $base->where('vendedor_id', $vendedorId);
        } elseif (!empty($vendedoresIds)) {
            $base->whereIn('vendedor_id', $vendedoresIds);
        }

        $data = $base
            ->selectRaw('MONTH(COALESCE(gerada_em, created_at)) as mes')
            ->selectRaw("SUM(CASE WHEN status != 'CANCELADA' THEN valor_comissao ELSE 0 END) as total")
            ->selectRaw("SUM(CASE WHEN status = 'PAGA' THEN valor_comissao ELSE 0 END) as total_efetivado")
            ->selectRaw("SUM(CASE WHEN status = 'PENDENTE' THEN valor_comissao ELSE 0 END) as total_previsto")
            ->groupBy('mes')
            ->get()
            ->keyBy('mes');

        $agora = Carbon::now();

        return collect(range(1, 12))->map(function ($mes) use ($data, $agora, $ano) {
            $registro = $data->get($mes);
            $statusAberto = ($ano === (int) $agora->year && $mes === (int) $agora->month) ||
                (($registro->total_previsto ?? 0) > 0 && ($registro->total_efetivado ?? 0) < ($registro->total_previsto ?? 0));

            return (object) [
                'mes' => $mes,
                'nome' => Carbon::createFromDate($ano, $mes, 1)->locale('pt_BR')->isoFormat('MMM'),
                'total' => $registro->total ?? 0,
                'total_efetivado' => $registro->total_efetivado ?? 0,
                'total_previsto' => $registro->total_previsto ?? 0,
                'status' => $statusAberto ? 'ABERTO' : 'FECHADO',
            ];
        });
    }

    private function rankingVendedores(
        ?int $empresaId,
        int $ano,
        ?int $vendedorId = null,
        array $vendedoresIds = []
    ): Collection {
        $comerciais = $this->vendedoresDaEmpresa($empresaId);
        $ids = !empty($vendedoresIds)
            ? $vendedoresIds
            : $comerciais->keys()->filter()->toArray();

        if ($vendedorId) {
            $ids = array_values(array_intersect($ids, [$vendedorId]));
        }

        if (empty($ids)) {
            return collect();
        }

        $totais = Comissao::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereYear(DB::raw('COALESCE(gerada_em, created_at)'), $ano)
            ->when(!empty($ids), fn ($q) => $q->whereIn('vendedor_id', $ids))
            ->select('vendedor_id')
            ->selectRaw("SUM(CASE WHEN status != 'CANCELADA' THEN valor_comissao ELSE 0 END) as total")
            ->groupBy('vendedor_id')
            ->get()
            ->keyBy('vendedor_id');

        return $comerciais->map(function ($user) use ($totais) {
            $row = (object) [
                'vendedor_id' => $user->id,
                'total' => $totais[$user->id]->total ?? 0,
            ];
            $row->vendedor = $user;

            return $row;
        })->sortByDesc('total')->values();
    }

    private function agrupadoPorCliente(
        ?int $empresaId,
        int $ano,
        int $mes,
        array $status,
        ?int $vendedorId,
        array $vendedoresIds
    ): Collection {
        $rows = $this->baseQuery($empresaId, $ano, $mes, $vendedorId, $vendedoresIds)
            ->whereIn('comissoes.status', $status)
            ->select('comissoes.cliente_id')
            ->selectRaw('SUM(comissoes.valor_comissao) as total')
            ->groupBy('comissoes.cliente_id')
            ->orderByDesc('total')
            ->get();

        $clientes = Cliente::whereIn('id', $rows->pluck('cliente_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($clientes) {
            $row->cliente = $clientes->get($row->cliente_id);

            return $row;
        });
    }

    private function agrupadoPorClienteEServico(
        ?int $empresaId,
        int $ano,
        int $mes,
        array $status,
        ?int $vendedorId,
        array $vendedoresIds
    ): Collection {
        $rows = $this->baseQuery($empresaId, $ano, $mes, $vendedorId, $vendedoresIds)
            ->leftJoin('servicos', 'servicos.id', '=', 'comissoes.servico_id')
            ->whereIn('comissoes.status', $status)
            ->select('comissoes.cliente_id')
            ->selectRaw("COALESCE(servicos.nome, CONCAT('Serviço #', comissoes.servico_id)) as servico_nome")
            ->selectRaw('SUM(comissoes.valor_comissao) as total')
            ->selectRaw('COUNT(comissoes.id) as quantidade')
            ->groupBy('comissoes.cliente_id', 'comissoes.servico_id', 'servicos.nome')
            ->orderByDesc('total')
            ->get();

        return $rows->groupBy('cliente_id');
    }

    private function baseQuery(?int $empresaId, int $ano, int $mes, ?int $vendedorId, array $vendedoresIds)
    {
        $query = Comissao::query()
            ->leftJoin('clientes', 'clientes.id', '=', 'comissoes.cliente_id')
            ->when($empresaId, fn ($q) => $q->where('comissoes.empresa_id', $empresaId))
            ->whereYear(DB::raw('COALESCE(comissoes.gerada_em, comissoes.created_at)'), $ano)
            ->whereMonth(DB::raw('COALESCE(comissoes.gerada_em, comissoes.created_at)'), $mes);

        if ($vendedorId) {
            $query->where(function ($q) use ($vendedorId) {
                $q->where('comissoes.vendedor_id', $vendedorId)
                    ->orWhere(function ($sub) use ($vendedorId) {
                        $sub->whereNull('comissoes.vendedor_id')
                            ->where('clientes.vendedor_id', $vendedorId);
                    });
            });
        } elseif (!empty($vendedoresIds)) {
            $query->where(function ($q) use ($vendedoresIds) {
                $q->whereIn('comissoes.vendedor_id', $vendedoresIds)
                    ->orWhere(function ($sub) use ($vendedoresIds) {
                        $sub->whereNull('comissoes.vendedor_id')
                            ->whereIn('clientes.vendedor_id', $vendedoresIds);
                    });
            });
        }

        return $query;
    }
}



