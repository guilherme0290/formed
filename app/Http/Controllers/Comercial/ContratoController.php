<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ClienteContrato;
use App\Models\ClienteContratoVigencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $contrato->load([
            'cliente',
            'itens.servico',
            'vigencias.itens.servico',
        ]);

        return view('comercial.contratos.show', compact('contrato'));
    }

    public function novaVigencia(ClienteContrato $contrato)
    {
        $empresaId = auth()->user()->empresa_id;
        abort_unless($contrato->empresa_id === $empresaId, 403);

        $contrato->load(['cliente', 'itens.servico']);

        return view('comercial.contratos.vigencia', compact('contrato'));
    }

    public function storeVigencia(Request $request, ClienteContrato $contrato)
    {
        $empresaId = $request->user()->empresa_id;
        abort_unless($contrato->empresa_id === $empresaId, 403);

        $contrato->load(['itens']);

        $validated = $request->validate([
            'vigencia_inicio' => ['required', 'date', 'after_or_equal:today'],
            'vigencia_fim' => ['nullable', 'date', 'after:vigencia_inicio'],
            'observacao' => ['nullable', 'string', 'max:255'],
            'itens' => ['required', 'array'],
            'itens.*.id' => ['required', Rule::exists('cliente_contrato_itens', 'id')->where('cliente_contrato_id', $contrato->id)],
            'itens.*.preco_unitario_snapshot' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($contrato, $validated, $request) {
            $novoInicio = $validated['vigencia_inicio'];
            $novoFim = $validated['vigencia_fim'] ?? null;

            // 1) Salva snapshot da vigência atual (se houver)
            if ($contrato->vigencia_inicio) {
                $vigenciaAnterior = ClienteContratoVigencia::create([
                    'cliente_contrato_id' => $contrato->id,
                    'vigencia_inicio' => $contrato->vigencia_inicio,
                    'vigencia_fim' => $novoInicio ? date('Y-m-d', strtotime($novoInicio . ' -1 day')) : $contrato->vigencia_fim,
                    'criado_por' => $request->user()->id ?? null,
                    'observacao' => 'Snapshot automático antes da nova vigência',
                ]);

                foreach ($contrato->itens as $item) {
                    $vigenciaAnterior->itens()->create([
                        'servico_id' => $item->servico_id,
                        'descricao_snapshot' => $item->descricao_snapshot,
                        'preco_unitario_snapshot' => $item->preco_unitario_snapshot,
                        'unidade_cobranca' => $item->unidade_cobranca,
                        'regras_snapshot' => $item->regras_snapshot,
                    ]);
                }
            }

            // 2) Atualiza contrato para nova vigência + novos preços
            $contrato->update([
                'vigencia_inicio' => $novoInicio,
                'vigencia_fim' => $novoFim,
            ]);

            $precos = collect($validated['itens'])->keyBy('id');
            foreach ($contrato->itens as $item) {
                $novoPreco = $precos[$item->id]['preco_unitario_snapshot'] ?? $item->preco_unitario_snapshot;
                $item->update([
                    'preco_unitario_snapshot' => $novoPreco,
                ]);
            }

            // 3) Guarda registro da nova vigência (histórico)
            $vigenciaNova = ClienteContratoVigencia::create([
                'cliente_contrato_id' => $contrato->id,
                'vigencia_inicio' => $novoInicio,
                'vigencia_fim' => $novoFim,
                'criado_por' => $request->user()->id ?? null,
                'observacao' => $validated['observacao'] ?? null,
            ]);

            foreach ($contrato->itens as $item) {
                $vigenciaNova->itens()->create([
                    'servico_id' => $item->servico_id,
                    'descricao_snapshot' => $item->descricao_snapshot,
                    'preco_unitario_snapshot' => $item->preco_unitario_snapshot,
                    'unidade_cobranca' => $item->unidade_cobranca,
                    'regras_snapshot' => $item->regras_snapshot,
                ]);
            }
        });

        return redirect()
            ->route('comercial.contratos.show', $contrato)
            ->with('ok', 'Nova vigência registrada e preços atualizados.');
    }
}
