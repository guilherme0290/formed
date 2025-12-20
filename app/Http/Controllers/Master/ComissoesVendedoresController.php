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

        $meses = $this->mesesResumo($empresaId, $anoSelecionado, $vendedorId);
        $ranking = $this->rankingVendedores($empresaId, $anoSelecionado);

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
        $ids = Comissao::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->distinct()
            ->pluck('vendedor_id');

        return User::whereIn('id', $ids)->get()->keyBy('id');
    }

    private function mesesResumo(?int $empresaId, int $ano, ?int $vendedorId = null): Collection
    {
        $base = Comissao::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereYear(DB::raw('COALESCE(gerada_em, created_at)'), $ano);

        if ($vendedorId) {
            $base->where('vendedor_id', $vendedorId);
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

    private function rankingVendedores(?int $empresaId, int $ano): Collection
    {
        return Comissao::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereYear(DB::raw('COALESCE(gerada_em, created_at)'), $ano)
            ->select('vendedor_id')
            ->selectRaw("SUM(CASE WHEN status != 'CANCELADA' THEN valor_comissao ELSE 0 END) as total")
            ->groupBy('vendedor_id')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $row->vendedor = $row->vendedor_id ? \App\Models\User::find($row->vendedor_id) : null;
                return $row;
            });
    }
}
