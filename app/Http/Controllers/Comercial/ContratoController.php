<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ClienteContrato;
use Illuminate\Http\Request;

class ContratoController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $buscaCliente = trim((string) $request->query('q', ''));
        $statusInput  = $request->query('status', []);
        $vigenciaDe   = $request->query('vigencia_de');
        $vigenciaAte  = $request->query('vigencia_ate');
        $valorMin     = $request->query('valor_min');
        $valorMax     = $request->query('valor_max');

        $statusFiltro = [];
        if (is_array($statusInput)) {
            $statusFiltro = array_filter(array_map(fn($s) => strtoupper(trim((string) $s)), $statusInput));
        } elseif (is_string($statusInput) && $statusInput !== '') {
            $statusFiltro = [strtoupper(trim($statusInput))];
        }

        $selecionouTodos = in_array('TODOS', $statusFiltro, true);

        // por padrão, somente ATIVO e PENDENTE
        $usarDefaultStatus = empty($statusFiltro);
        if ($usarDefaultStatus) {
            $statusFiltro = ['ATIVO', 'PENDENTE'];
        }

        if ($selecionouTodos) {
            $statusFiltro = [];
            $usarDefaultStatus = false;
        }

        $query = ClienteContrato::query()
            ->where('empresa_id', $empresaId)
            ->with(['cliente'])
            ->withSum(['itens as valor_mensal' => function ($q) {
                $q->where('ativo', true);
            }], 'preco_unitario_snapshot');

        if ($buscaCliente !== '') {
            $query->whereHas('cliente', function ($q) use ($buscaCliente) {
                $q->where('razao_social', 'like', '%' . $buscaCliente . '%');
            });
        }

        if (!empty($statusFiltro)) {
            $query->whereIn('status', $statusFiltro);
        }

        if ($vigenciaDe) {
            $query->whereDate('vigencia_inicio', '>=', $vigenciaDe);
        }

        if ($vigenciaAte) {
            $query->where(function ($q) use ($vigenciaAte) {
                $q->whereNotNull('vigencia_fim')
                    ->whereDate('vigencia_fim', '<=', $vigenciaAte);
            });
        }

        if ($valorMin !== null && $valorMin !== '') {
            $query->having('valor_mensal', '>=', (float) $valorMin);
        }

        if ($valorMax !== null && $valorMax !== '') {
            $query->having('valor_mensal', '<=', (float) $valorMax);
        }

        $contratos = $query
            ->orderByDesc('vigencia_inicio')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Totalizadores (não dependem de filtros)
        $totalAtivos = ClienteContrato::where('empresa_id', $empresaId)
            ->where('status', 'ATIVO')
            ->count();

        $totalPendentes = ClienteContrato::where('empresa_id', $empresaId)
            ->where('status', 'PENDENTE')
            ->count();

        $faturamentoAtivo = ClienteContrato::where('empresa_id', $empresaId)
            ->where('status', 'ATIVO')
            ->withSum(['itens as valor_mensal' => function ($q) {
                $q->where('ativo', true);
            }], 'preco_unitario_snapshot')
            ->get()
            ->sum('valor_mensal');

        return view('comercial.contratos.index', [
            'contratos' => $contratos,
            'buscaCliente' => $buscaCliente,
            'statusFiltro' => $statusFiltro,
            'vigenciaDe' => $vigenciaDe,
            'vigenciaAte' => $vigenciaAte,
            'valorMin' => $valorMin,
            'valorMax' => $valorMax,
            'totalAtivos' => $totalAtivos,
            'totalPendentes' => $totalPendentes,
            'faturamentoAtivo' => $faturamentoAtivo,
            'usandoFiltroCustom' => !$usarDefaultStatus,
        ]);
    }

    public function show(ClienteContrato $contrato)
    {
        $empresaId = auth()->user()->empresa_id;
        abort_unless($contrato->empresa_id === $empresaId, 403);

        $contrato->load(['cliente', 'itens']);

        return view('comercial.contratos.show', compact('contrato'));
    }
}
