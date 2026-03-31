<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Proposta;
use App\Models\User;
use App\Services\PropostaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    private array $colunas = [
        'CONTATO_INICIAL'   => 'Contato Inicial',
        'PROPOSTA_ENVIADA'  => 'Proposta Enviada',
        'EM_NEGOCIACAO'     => 'Em Negociação',
        'FECHAMENTO'        => 'Fechamento',
        'PERDIDO'           => 'Perdido',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;
        $isMaster = $user->hasPapel('Master');

        $busca = trim((string) $request->query('q', ''));
        $statusFiltro = '';
        if ($request->has('status')) {
            $statusInput = $request->query('status', '');
            if (is_array($statusInput)) {
                $statusInput = $statusInput[0] ?? '';
            }
            $statusFiltro = strtoupper(trim((string) $statusInput));
        }
        $vendedorFiltro = (int) $request->query('vendedor_id', 0);

        $query = Proposta::query()
            ->where('empresa_id', $empresaId)
            ->with([
                'cliente:id,razao_social,telefone,email,vendedor_id',
                'vendedor:id,name',
                'itens:id,proposta_id,nome,quantidade,valor_total,tipo',
            ])
            ->orderByDesc('id');

        if (!$isMaster) {
            $query->where(function ($q) use ($user) {
                $q->where('vendedor_id', $user->id)
                    ->orWhereHas('cliente', fn ($cliente) => $cliente->where('vendedor_id', $user->id));
            });
        }

        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                if (ctype_digit($busca)) {
                    $q->orWhere('id', (int) $busca);
                }
                $q->orWhere('codigo', 'like', '%' . $busca . '%')
                    ->orWhereHas('cliente', fn($c) => $c->where('razao_social', 'like', '%' . $busca . '%'));
            });
        }

        if ($vendedorFiltro > 0) {
            $query->where('vendedor_id', $vendedorFiltro);
        }

        $propostas = $query->get();
        $agora = Carbon::now();

        $propostas->each(function (Proposta $proposta) use ($agora) {
            $effectiveStatus = $this->resolvePipelineStatus($proposta, $agora);
            $proposta->setAttribute('effective_pipeline_status', $effectiveStatus);
            $proposta->setAttribute('effective_pipeline_label', $this->colunas[$effectiveStatus] ?? $effectiveStatus);

            if ($effectiveStatus === 'PERDIDO' && strtoupper((string) $proposta->status) !== 'CANCELADA' && empty($proposta->perdido_motivo)) {
                $proposta->setAttribute('pipeline_perdido_motivo', 'Prazo expirado');
            } else {
                $proposta->setAttribute('pipeline_perdido_motivo', $proposta->perdido_motivo);
            }
        });

        if ($statusFiltro !== '') {
            $propostas = $propostas
                ->filter(fn (Proposta $proposta) => strtoupper((string) $proposta->effective_pipeline_status) === $statusFiltro)
                ->values();
        }

        // KPIs
        $total = $propostas->count();
        $fechadas = $propostas->where('status', 'FECHADA')->count();
        $emAberto = $propostas->whereNotIn('status', ['FECHADA', 'CANCELADA'])->count();
        $taxaConversao = $total > 0 ? round(($fechadas / $total) * 100, 1) : 0;
        $emNegociacaoValor = $propostas->where('effective_pipeline_status', 'EM_NEGOCIACAO')->sum('valor_total');

        // agrupa colunas
        $colunas = [];
        foreach ($this->colunas as $slug => $titulo) {
            $colunas[$slug] = [
                'titulo' => $titulo,
                'cards' => [],
            ];
        }

        foreach ($propostas as $p) {
            $status = strtoupper((string) $p->effective_pipeline_status);

            if (!array_key_exists($status, $colunas)) {
                $status = 'CONTATO_INICIAL';
            }
            $colunas[$status]['cards'][] = $p;
        }

        $vendedores = $isMaster
            ? User::query()
                ->where('empresa_id', $empresaId)
                ->whereHas('papel', fn ($q) => $q->whereRaw('lower(nome) = ?', ['comercial']))
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect([$user]);

        $pipelineAutocomplete = $propostas
            ->flatMap(function ($proposta) {
                return array_filter([
                    $proposta->codigo,
                    $proposta->id ? '#'.$proposta->id : null,
                    $proposta->cliente?->razao_social,
                ]);
            })
            ->unique()
            ->values();

        return view('comercial.pipeline.index', [
            'colunas' => $colunas,
            'busca' => $busca,
            'statusFiltro' => $statusFiltro,
            'vendedorFiltro' => $vendedorFiltro,
            'vendedores' => $vendedores,
            'colunasMeta' => $this->colunas,
            'pipelineAutocomplete' => $pipelineAutocomplete,
            'kpi' => [
                'total' => $total,
                'fechadas' => $fechadas,
                'emAberto' => $emAberto,
                'taxaConversao' => $taxaConversao,
                'emNegociacaoValor' => $emNegociacaoValor,
            ],
        ]);
    }

    private function resolvePipelineStatus(Proposta $proposta, Carbon $agora): string
    {
        $statusProposta = strtoupper((string) $proposta->status);
        $prazoDias = (int) ($proposta->prazo_dias ?? 7);
        $pipelineStatus = strtoupper((string) ($proposta->pipeline_status ?? 'CONTATO_INICIAL'));

        if ($statusProposta === 'CANCELADA') {
            return 'PERDIDO';
        }

        if ($statusProposta === 'FECHADA') {
            return 'FECHAMENTO';
        }

        if ($statusProposta === 'ENVIADA') {
            if (in_array($pipelineStatus, ['EM_NEGOCIACAO', 'FECHAMENTO'], true)) {
                return $pipelineStatus;
            }

            return 'PROPOSTA_ENVIADA';
        }

        if ($prazoDias > 0 && $proposta->created_at) {
            $expiraEm = $proposta->created_at->copy()->addDays($prazoDias)->endOfDay();
            if ($agora->greaterThan($expiraEm)) {
                return 'PERDIDO';
            }
        }

        return array_key_exists($pipelineStatus, $this->colunas) ? $pipelineStatus : 'CONTATO_INICIAL';
    }

    public function mover(Request $request, Proposta $proposta, PropostaService $service)
    {
        $user = $request->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);
        if (!$user->hasPapel('Master')) {
            $clienteVendedorId = (int) optional($proposta->cliente)->vendedor_id;
            $podeMover = (int) $proposta->vendedor_id === (int) $user->id
                || $clienteVendedorId === (int) $user->id;
            abort_unless($podeMover, 403);
        }

        if (in_array(strtoupper((string) $proposta->status), ['FECHADA','CANCELADA'], true)) {
            return response()->json(['message' => 'Proposta fechada/cancelada não pode ser movimentada.'], 422);
        }

        $data = $request->validate([
            'pipeline_status' => ['required', 'string', 'in:CONTATO_INICIAL,PROPOSTA_ENVIADA,EM_NEGOCIACAO,FECHAMENTO,PERDIDO'],
            'perdido_motivo' => ['nullable', 'string', 'max:50'],
            'perdido_observacao' => ['nullable', 'string', 'max:1000'],
        ]);

        $novo = $data['pipeline_status'];
        if ($novo === 'PERDIDO' && empty($data['perdido_motivo'])) {
            return response()->json(['message' => 'Informe o motivo de perda.'], 422);
        }

        if ($novo === 'FECHAMENTO') {
            $service->fechar($proposta->id, $user->id);
            $proposta->refresh();
        } else {
            $payload = [
                'status' => in_array($novo, ['PROPOSTA_ENVIADA', 'EM_NEGOCIACAO'], true) ? 'ENVIADA' : 'PENDENTE',
                'pipeline_status' => $novo,
                'pipeline_updated_at' => Carbon::now(),
                'pipeline_updated_by' => $user->id,
                'perdido_motivo' => null,
                'perdido_observacao' => null,
            ];

            if ($novo === 'PERDIDO') {
                $payload['status'] = 'CANCELADA';
                $payload['perdido_motivo'] = $data['perdido_motivo'];
                $payload['perdido_observacao'] = $data['perdido_observacao'] ?? null;
            }

            $proposta->update($payload);
            $proposta->refresh();
        }

        return response()->json([
            'message' => 'Proposta atualizada.',
            'status' => strtoupper((string) $proposta->status),
            'status_label' => ucfirst(strtolower((string) $proposta->status)),
            'pipeline_status' => strtoupper((string) $proposta->pipeline_status),
            'pipeline_label' => $this->colunas[strtoupper((string) $proposta->pipeline_status)] ?? strtoupper((string) $proposta->pipeline_status),
        ]);
    }
}
