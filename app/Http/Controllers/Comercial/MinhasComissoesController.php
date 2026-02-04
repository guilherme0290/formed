<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Comissao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MinhasComissoesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $user = $request->user();
            if (!$user) {
                abort(403);
            }

            if ($user->hasPapel('Master')) {
                return redirect()->route('master.comissoes.vendedores');
            }

            if (!$user->hasPapel('Comercial')) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request, ?int $ano = null): View
    {
        [$anosDisponiveis, $anoSelecionado] = $this->anosDisponiveis($request, $ano);

        $meses = $this->mesesResumo($request->user()->id, $request->user()->empresa_id, $anoSelecionado);

        return view('comercial.comissoes.index', [
            'anos' => $anosDisponiveis,
            'anoSelecionado' => $anoSelecionado,
            'meses' => $meses,
        ]);
    }

    public function mes(Request $request, int $ano, int $mes): View
    {
        $user = $request->user();
        [$anosDisponiveis, $anoSelecionado] = $this->anosDisponiveis($request, $ano);

        $base = $this->baseQuery($user->id, $user->empresa_id, $anoSelecionado, $mes);

        $totais = $base->clone()
            ->selectRaw("SUM(CASE WHEN status != 'CANCELADA' THEN valor_comissao ELSE 0 END) as previsao")
            ->selectRaw("SUM(CASE WHEN status = 'PAGA' THEN valor_comissao ELSE 0 END) as efetivada")
            ->selectRaw("COUNT(DISTINCT CASE WHEN status = 'PENDENTE' THEN cliente_id END) as inadimplentes")
            ->first();

        return view('comercial.comissoes.mes', [
            'anos' => $anosDisponiveis,
            'anoSelecionado' => $anoSelecionado,
            'mes' => $mes,
            'totais' => $totais,
        ]);
    }

    public function previsao(Request $request, int $ano, int $mes): View
    {
        $user = $request->user();
        $clientes = $this->agrupadoPorCliente($user->id, $user->empresa_id, $ano, $mes, ['PENDENTE', 'PAGA']);

        return view('comercial.comissoes.previsao', compact('clientes', 'ano', 'mes'));
    }

    public function efetivada(Request $request, int $ano, int $mes): View
    {
        $user = $request->user();
        $clientes = $this->agrupadoPorCliente($user->id, $user->empresa_id, $ano, $mes, ['PAGA']);

        return view('comercial.comissoes.efetivada', compact('clientes', 'ano', 'mes'));
    }

    public function inadimplentes(Request $request, int $ano, int $mes): View
    {
        $user = $request->user();
        $clientes = $this->agrupadoPorCliente($user->id, $user->empresa_id, $ano, $mes, ['PENDENTE']);

        return view('comercial.comissoes.inadimplentes', compact('clientes', 'ano', 'mes'));
    }

    private function anosDisponiveis(Request $request, ?int $ano = null): array
    {
        $user = $request->user();

        $anos = Comissao::query()
            ->selectRaw('YEAR(COALESCE(gerada_em, created_at)) as ano')
            ->where('empresa_id', $user->empresa_id)
            ->where('vendedor_id', $user->id)
            ->distinct()
            ->orderByDesc('ano')
            ->pluck('ano')
            ->values();

        $anoAtual = now()->year;
        if (!$anos->contains($anoAtual)) {
            $anos->push($anoAtual);
        }
        $anos = $anos->unique()->sortDesc()->values();

        $anoSelecionado = $ano ?? (int) ($request->integer('ano') ?: $anoAtual);
        if ($anos->isNotEmpty() && !$anos->contains($anoSelecionado)) {
            $anoSelecionado = (int) $anos->first();
        }

        return [$anos, $anoSelecionado];
    }

    private function mesesResumo(int $vendedorId, int $empresaId, int $ano): Collection
    {
        $data = $this->baseQuery($vendedorId, $empresaId, $ano)
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
                'status' => $statusAberto ? 'ABERTO' : 'FECHADO',
            ];
        });
    }

    private function agrupadoPorCliente(int $vendedorId, int $empresaId, int $ano, int $mes, array $status): Collection
    {
        $rows = $this->baseQuery($vendedorId, $empresaId, $ano, $mes)
            ->whereIn('status', $status)
            ->select('cliente_id')
            ->selectRaw('SUM(valor_comissao) as total')
            ->groupBy('cliente_id')
            ->orderByDesc('total')
            ->get();

        $clientes = Cliente::whereIn('id', $rows->pluck('cliente_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($clientes) {
            $row->cliente = $clientes->get($row->cliente_id);
            return $row;
        });
    }

    private function baseQuery(int $vendedorId, int $empresaId, int $ano, ?int $mes = null)
    {
        $query = Comissao::query()
            ->where('empresa_id', $empresaId)
            ->where('vendedor_id', $vendedorId)
            ->whereYear(DB::raw('COALESCE(gerada_em, created_at)'), $ano);

        if ($mes) {
            $query->whereMonth(DB::raw('COALESCE(gerada_em, created_at)'), $mes);
        }

        return $query;
    }
}
