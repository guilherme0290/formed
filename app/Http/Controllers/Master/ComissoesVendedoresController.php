<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Comissao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ComissoesVendedoresController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        [$anos, $anoSelecionado] = $this->anosDisponiveis($empresaId, $request->integer('ano'));

        $vendedorId = $request->integer('vendedor');
        $vendedores = $this->vendedoresDaEmpresa($empresaId);

        // se vendedor enviado não existe ou não pertence, zera
        if ($vendedorId && !$vendedores->has($vendedorId)) {
            $vendedorId = null;
        }

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

    private function rankingVendedores(?int $empresaId, int $ano, ?int $vendedorId = null, array $vendedoresIds = []): Collection
    {
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
}
