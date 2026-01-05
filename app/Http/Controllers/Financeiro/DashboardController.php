<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\ClienteContrato;
use App\Models\ContaReceberItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
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

        $contratosQuery = ClienteContrato::query()
            ->where('empresa_id', $empresaId)
            ->with(['cliente'])
            ->withSum(['itens as valor_mensal' => function ($q) {
                $q->where('ativo', true);
            }], 'preco_unitario_snapshot');

        $contratos = (clone $contratosQuery)
            ->orderByDesc('vigencia_inicio')
            ->orderByDesc('id')
            ->get();

        $ativos = (clone $contratosQuery)->where('status', 'ATIVO')->count();
        $pendentes = (clone $contratosQuery)->where('status', 'PENDENTE')->count();
        $faturamentoMensal = (clone $contratosQuery)
            ->where('status', 'ATIVO')
            ->get()
            ->sum('valor_mensal');

        // Aprovados: contratos marcados como ATIVO (consideramos aprovados = ativos)
        $aprovados = $ativos;

        $itensEmAberto = ContaReceberItem::query()
            ->where('empresa_id', $empresaId)
            ->where('status', 'ABERTO')
            ->count();

        $itensFaturados = ContaReceberItem::query()
            ->where('empresa_id', $empresaId)
            ->where('status', 'BAIXADO')
            ->count();

        $cards = [
            'contratos_ativos' => $ativos,
            'faturamento_mensal' => $faturamentoMensal,
            'aprovados' => $aprovados,
            'pendentes' => $pendentes,
            'itens_aberto' => $itensEmAberto,
            'itens_faturado' => $itensFaturados,
        ];

        return view('financeiro.dashboard', [
            'cards' => $cards,
            'contratos' => $contratos,
        ]);
    }
}
