<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Proposta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $empresaId = $request->user()->empresa_id;

        $busca = trim((string) $request->query('q', ''));
        $statusFiltro = (array) $request->query('status', []);
        $vendedorFiltro = (int) $request->query('vendedor_id', 0);

        $statusFiltro = array_filter(array_map(fn($s) => strtoupper(trim((string) $s)), $statusFiltro));
        $filtroCustom = !empty($statusFiltro);

        $query = Proposta::query()
            ->where('empresa_id', $empresaId)
            ->with(['cliente', 'vendedor', 'itens'])
            ->orderByDesc('id');

        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                if (ctype_digit($busca)) {
                    $q->orWhere('id', (int) $busca);
                }
                $q->orWhere('codigo', 'like', '%' . $busca . '%')
                    ->orWhereHas('cliente', fn($c) => $c->where('razao_social', 'like', '%' . $busca . '%'));
            });
        }

        if (!empty($statusFiltro)) {
            $query->whereIn('pipeline_status', $statusFiltro);
        }

        if ($vendedorFiltro > 0) {
            $query->where('vendedor_id', $vendedorFiltro);
        }

        $propostas = $query->get();

        // KPIs
        $total = $propostas->count();
        $fechadas = $propostas->where('status', 'FECHADA')->count();
        $emAberto = $propostas->whereNotIn('status', ['FECHADA', 'CANCELADA'])->count();
        $taxaConversao = $total > 0 ? round(($fechadas / $total) * 100, 1) : 0;
        $emNegociacaoValor = $propostas->where('pipeline_status', 'EM_NEGOCIACAO')->sum('valor_total');

        // agrupa colunas
        $colunas = [];
        foreach ($this->colunas as $slug => $titulo) {
            $colunas[$slug] = [
                'titulo' => $titulo,
                'cards' => [],
            ];
        }

        foreach ($propostas as $p) {
            $status = strtoupper($p->pipeline_status ?? 'CONTATO_INICIAL');
            if (!array_key_exists($status, $colunas)) {
                $status = 'CONTATO_INICIAL';
            }
            $colunas[$status]['cards'][] = $p;
        }

        return view('comercial.pipeline.index', [
            'colunas' => $colunas,
            'busca' => $busca,
            'statusFiltro' => $statusFiltro,
            'vendedorFiltro' => $vendedorFiltro,
            'colunasMeta' => $this->colunas,
            'kpi' => [
                'total' => $total,
                'fechadas' => $fechadas,
                'emAberto' => $emAberto,
                'taxaConversao' => $taxaConversao,
                'emNegociacaoValor' => $emNegociacaoValor,
            ],
        ]);
    }

    public function mover(Request $request, Proposta $proposta)
    {
        $user = $request->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

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

        $payload = [
            'pipeline_status' => $novo,
            'pipeline_updated_at' => Carbon::now(),
            'pipeline_updated_by' => $user->id,
        ];

        if ($novo === 'PERDIDO') {
            $payload['perdido_motivo'] = $data['perdido_motivo'];
            $payload['perdido_observacao'] = $data['perdido_observacao'] ?? null;
        }

        $proposta->update($payload);

        return response()->json([
            'message' => 'Proposta atualizada.',
            'status' => $novo,
        ]);
    }
}
