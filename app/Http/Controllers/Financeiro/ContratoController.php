<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\ClienteContrato;
use App\Models\ProtocoloExame;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class ContratoController extends Controller
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

        $contratos = ClienteContrato::query()
            ->where('empresa_id', $empresaId)
            ->with(['cliente'])
            ->withSum(['itens as valor_mensal' => function ($q) {
                $q->where('ativo', true);
            }], 'preco_unitario_snapshot')
            ->orderByDesc('vigencia_inicio')
            ->orderByDesc('id')
            ->paginate(20);

        return view('financeiro.contratos', compact('contratos'));
    }

    public function show(Request $request, ClienteContrato $contrato): View
    {
        $empresaId = $request->user()->empresa_id ?? null;
        abort_unless($contrato->empresa_id === $empresaId, 403);

        $contrato->load(['cliente', 'itens.servico']);

        $asoItens = $contrato->itens->filter(fn ($item) => !empty($item->regras_snapshot['aso_tipo']));
        $grupoIds = $asoItens
            ->map(fn ($item) => (int) ($item->regras_snapshot['grupo_id'] ?? 0))
            ->filter()
            ->unique()
            ->values();

        $protocolosAso = collect();
        if ($grupoIds->isNotEmpty()) {
            $protocolosAso = ProtocoloExame::query()
                ->where('empresa_id', $empresaId)
                ->whereIn('id', $grupoIds)
                ->with('itens.exame')
                ->get()
                ->keyBy('id');
        }

        return view('financeiro.contratos-show', compact('contrato', 'protocolosAso'));
    }
}
