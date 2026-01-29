<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ClienteContrato;
use App\Models\ClienteContratoLog;
use App\Models\ClienteContratoVigencia;
use App\Models\ProtocoloExame;
use App\Services\AsoGheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContratoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;
        $isMaster = $user->hasPapel('Master');

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

        if (!$isMaster) {
            $query->where('vendedor_id', $user->id);
        }

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
            ->when(!$isMaster, fn ($q) => $q->where('vendedor_id', $user->id))
            ->where('status', 'ATIVO')
            ->count();

        $totalPendentes = ClienteContrato::where('empresa_id', $empresaId)
            ->when(!$isMaster, fn ($q) => $q->where('vendedor_id', $user->id))
            ->where('status', 'PENDENTE')
            ->count();

        $faturamentoAtivo = ClienteContrato::where('empresa_id', $empresaId)
            ->when(!$isMaster, fn ($q) => $q->where('vendedor_id', $user->id))
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
        $user = auth()->user();
        $empresaId = $user->empresa_id;
        abort_unless($contrato->empresa_id === $empresaId, 403);
        if (!$user->hasPapel('Master')) {
            abort_unless((int) $contrato->vendedor_id === (int) $user->id, 403);
        }

        $contrato->load([
            'cliente',
            'itens.servico',
            'vigencias.itens.servico',
            'logs.user',
            'logs.servico',
        ]);

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

        return view('comercial.contratos.show', compact('contrato', 'protocolosAso'));
    }

    public function novaVigencia(ClienteContrato $contrato)
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id;
        abort_unless($contrato->empresa_id === $empresaId, 403);
        if (!$user->hasPapel('Master')) {
            abort_unless((int) $contrato->vendedor_id === (int) $user->id, 403);
        }

        $contrato->load(['cliente', 'itens.servico']);

        return view('comercial.contratos.vigencia', compact('contrato'));
    }

    public function storeVigencia(Request $request, ClienteContrato $contrato)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;
        abort_unless($contrato->empresa_id === $empresaId, 403);
        if (!$user->hasPapel('Master')) {
            abort_unless((int) $contrato->vendedor_id === (int) $user->id, 403);
        }

        $contrato->load(['cliente', 'itens.servico']);

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
            $usuarioNome = $request->user()?->name ?? 'Sistema';
            $clienteNome = $contrato->cliente?->razao_social ?? 'Cliente';
            $motivo = $validated['observacao'] ?? null;
            $precosAntigos = $contrato->itens->mapWithKeys(function ($item) {
                return [$item->id => [
                    'preco' => (float) $item->preco_unitario_snapshot,
                    'servico_id' => $item->servico_id,
                    'servico_nome' => $item->servico?->nome ?? $item->descricao_snapshot ?? 'Serviço',
                ]];
            });

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

            $asoServicoId = app(AsoGheService::class)->resolveServicoAsoIdFromContrato($contrato);
            if ($asoServicoId) {
                $asoItens = $contrato->itens()->where('servico_id', $asoServicoId)->get();
                $temTipos = $asoItens->contains(fn ($item) => !empty($item->regras_snapshot['aso_tipo']));
                if (!$temTipos) {
                    $asoSnapshot = app(AsoGheService::class)
                        ->buildSnapshotForCliente($contrato->cliente_id, $empresaId);
                    if (empty($asoSnapshot['ghes'])) {
                        $asoSnapshot = null;
                    }
                    foreach ($asoItens as $asoItem) {
                        $asoItem->update(['regras_snapshot' => $asoSnapshot]);
                    }
                }
            }

            ClienteContratoLog::create([
                'cliente_contrato_id' => $contrato->id,
                'user_id' => $request->user()?->id,
                'acao' => 'VIGENCIA',
                'motivo' => $motivo,
                'descricao' => sprintf(
                    'USUARIO: %s ALTEROU a vigência do contrato da empresa %s. Início: %s. Fim: %s.',
                    $usuarioNome,
                    $clienteNome,
                    $novoInicio,
                    $novoFim ?: 'em aberto'
                ),
            ]);

            foreach ($contrato->itens as $item) {
                $anterior = $precosAntigos[$item->id]['preco'] ?? null;
                $novoValor = (float) $item->preco_unitario_snapshot;
                if ($anterior === null || abs($anterior - $novoValor) < 0.01) {
                    continue;
                }

                $servicoNome = $precosAntigos[$item->id]['servico_nome'] ?? 'Serviço';

                ClienteContratoLog::create([
                    'cliente_contrato_id' => $contrato->id,
                    'user_id' => $request->user()?->id,
                    'servico_id' => $precosAntigos[$item->id]['servico_id'] ?? $item->servico_id,
                    'acao' => 'ALTERACAO',
                    'motivo' => $motivo,
                    'descricao' => sprintf(
                        'USUARIO: %s ALTEROU o contrato da empresa %s. SERVICO %s. Valor antigo: R$ %s. Novo valor: R$ %s.',
                        $usuarioNome,
                        $clienteNome,
                        $servicoNome,
                        number_format($anterior, 2, ',', '.'),
                        number_format($novoValor, 2, ',', '.')
                    ),
                    'valor_anterior' => $anterior,
                    'valor_novo' => $novoValor,
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
