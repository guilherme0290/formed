<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteContrato;
use App\Models\ClienteGhe;
use App\Models\ClienteGheFuncao;
use App\Models\Funcao;
use App\Models\ProtocoloExame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteGheController extends Controller
{
    public function indexJson(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;
        $clienteId = (int) $request->query('cliente_id');

        $cliente = Cliente::where('empresa_id', $empresaId)->findOrFail($clienteId);

        $ghes = ClienteGhe::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->with([
                'protocolo.itens.exame:id,titulo,preco,ativo',
                'protocoloAdmissional.itens.exame:id,titulo,preco,ativo',
                'protocoloPeriodico.itens.exame:id,titulo,preco,ativo',
                'protocoloDemissional.itens.exame:id,titulo,preco,ativo',
                'protocoloMudancaFuncao.itens.exame:id,titulo,preco,ativo',
                'protocoloRetornoTrabalho.itens.exame:id,titulo,preco,ativo',
                'funcoes.funcao:id,nome'
            ])
            ->orderBy('nome')
            ->get()
            ->map(function (ClienteGhe $ghe) {
                $protocolos = $this->resolveProtocolosPorTipo($ghe);
                $examesPorTipo = [];
                $totalExamesPorTipo = [];

                foreach ($protocolos as $tipo => $protocolo) {
                    $exames = $protocolo?->itens
                        ->map(fn ($it) => $it->exame)
                        ->filter()
                        ->values() ?? collect();
                    $examesPorTipo[$tipo] = $exames;
                    $totalExamesPorTipo[$tipo] = (float) $exames->sum(fn ($ex) => (float) ($ex->preco ?? 0));
                }

                $totalExames = (float) ($totalExamesPorTipo['admissional'] ?? 0);

                return [
                    'id' => $ghe->id,
                    'nome' => $ghe->nome,
                    'ativo' => (bool) $ghe->ativo,
                    'protocolo' => $protocolos['admissional'] ? [
                        'id' => $protocolos['admissional']->id,
                        'titulo' => $protocolos['admissional']->titulo,
                        'descricao' => $protocolos['admissional']->descricao,
                    ] : null,
                    'protocolos' => collect($protocolos)->map(function ($protocolo) {
                        return $protocolo ? [
                            'id' => $protocolo->id,
                            'titulo' => $protocolo->titulo,
                            'descricao' => $protocolo->descricao,
                        ] : null;
                    })->all(),
                    'funcoes' => $ghe->funcoes->map(fn ($f) => [
                        'id' => $f->funcao_id,
                        'nome' => $f->funcao?->nome,
                    ])->filter(fn ($f) => !empty($f['id']))->values()->all(),
                    'base' => $this->formatBase($ghe),
                    'preco_fechado' => $this->formatFechados($ghe),
                    'exames' => ($examesPorTipo['admissional'] ?? collect())->map(fn ($ex) => [
                        'id' => $ex->id,
                        'titulo' => $ex->titulo,
                        'preco' => (float) $ex->preco,
                        'ativo' => (bool) $ex->ativo,
                    ])->all(),
                    'exames_por_tipo' => collect($examesPorTipo)->map(function ($exames) {
                        return $exames->map(fn ($ex) => [
                            'id' => $ex->id,
                            'titulo' => $ex->titulo,
                            'preco' => (float) $ex->preco,
                            'ativo' => (bool) $ex->ativo,
                        ])->all();
                    })->all(),
                    'total_exames' => (float) $totalExames,
                    'total_exames_por_tipo' => $totalExamesPorTipo,
                ];
            });

        return response()->json(['data' => $ghes]);
    }

    public function store(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'nome' => ['required', 'string', 'max:255'],
            'protocolo_id' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'protocolos' => ['array'],
            'protocolos.admissional' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'protocolos.periodico' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'protocolos.demissional' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'protocolos.mudanca_funcao' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'protocolos.retorno_trabalho' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'funcoes' => ['array'],
            'funcoes.*' => ['integer', 'exists:funcoes,id'],
            'base' => ['array'],
            'base.admissional' => ['nullable', 'numeric', 'min:0'],
            'base.periodico' => ['nullable', 'numeric', 'min:0'],
            'base.demissional' => ['nullable', 'numeric', 'min:0'],
            'base.mudanca_funcao' => ['nullable', 'numeric', 'min:0'],
            'base.retorno_trabalho' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado' => ['array'],
            'preco_fechado.admissional' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.periodico' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.demissional' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.mudanca_funcao' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.retorno_trabalho' => ['nullable', 'numeric', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $this->authorizeCliente($empresaId, (int) $data['cliente_id']);
        $protocolos = $this->resolveProtocolosInput($data['protocolos'] ?? [], $data['protocolo_id'] ?? null);
        foreach ($protocolos as $protocoloId) {
            $this->authorizeProtocolo($empresaId, $protocoloId);
        }
        $this->authorizeFuncoes($empresaId, $data['funcoes'] ?? []);

        return DB::transaction(function () use ($data, $empresaId, $protocolos) {
            $ghe = ClienteGhe::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $data['cliente_id'],
                'nome' => $data['nome'],
                'protocolo_id' => $data['protocolo_id'] ?? null,
                'protocolo_admissional_id' => $protocolos['admissional'],
                'protocolo_periodico_id' => $protocolos['periodico'],
                'protocolo_demissional_id' => $protocolos['demissional'],
                'protocolo_mudanca_funcao_id' => $protocolos['mudanca_funcao'],
                'protocolo_retorno_trabalho_id' => $protocolos['retorno_trabalho'],
                'base_aso_admissional' => $data['base']['admissional'] ?? 0,
                'base_aso_periodico' => $data['base']['periodico'] ?? 0,
                'base_aso_demissional' => $data['base']['demissional'] ?? 0,
                'base_aso_mudanca_funcao' => $data['base']['mudanca_funcao'] ?? 0,
                'base_aso_retorno_trabalho' => $data['base']['retorno_trabalho'] ?? 0,
                'preco_fechado_admissional' => $data['preco_fechado']['admissional'] ?? null,
                'preco_fechado_periodico' => $data['preco_fechado']['periodico'] ?? null,
                'preco_fechado_demissional' => $data['preco_fechado']['demissional'] ?? null,
                'preco_fechado_mudanca_funcao' => $data['preco_fechado']['mudanca_funcao'] ?? null,
                'preco_fechado_retorno_trabalho' => $data['preco_fechado']['retorno_trabalho'] ?? null,
                'ativo' => $data['ativo'] ?? true,
            ]);

            $this->syncFuncoes($ghe, $data['funcoes'] ?? []);
            $this->syncContratoSnapshotsFuncoes($ghe->empresa_id, $ghe->cliente_id);

            return response()->json(['data' => $ghe->fresh()], 201);
        });
    }

    public function update(Request $request, ClienteGhe $ghe)
    {
        $this->authorizeEmpresa($ghe);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'protocolo_id' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'protocolos' => ['array'],
            'protocolos.admissional' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'protocolos.periodico' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'protocolos.demissional' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'protocolos.mudanca_funcao' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'protocolos.retorno_trabalho' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'funcoes' => ['array'],
            'funcoes.*' => ['integer', 'exists:funcoes,id'],
            'base' => ['array'],
            'base.admissional' => ['nullable', 'numeric', 'min:0'],
            'base.periodico' => ['nullable', 'numeric', 'min:0'],
            'base.demissional' => ['nullable', 'numeric', 'min:0'],
            'base.mudanca_funcao' => ['nullable', 'numeric', 'min:0'],
            'base.retorno_trabalho' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado' => ['array'],
            'preco_fechado.admissional' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.periodico' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.demissional' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.mudanca_funcao' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.retorno_trabalho' => ['nullable', 'numeric', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $shouldUpdateProtocolos = $request->has('protocolos') || $request->has('protocolo_id');
        $protocolos = null;
        if ($shouldUpdateProtocolos) {
            $protocolos = $this->resolveProtocolosInput($data['protocolos'] ?? [], $data['protocolo_id'] ?? null);
            foreach ($protocolos as $protocoloId) {
                $this->authorizeProtocolo($ghe->empresa_id, $protocoloId);
            }
        }
        $this->authorizeFuncoes($ghe->empresa_id, $data['funcoes'] ?? []);

        return DB::transaction(function () use ($data, $ghe, $protocolos, $shouldUpdateProtocolos) {
            $updates = [
                'nome' => $data['nome'],
                'base_aso_admissional' => $data['base']['admissional'] ?? 0,
                'base_aso_periodico' => $data['base']['periodico'] ?? 0,
                'base_aso_demissional' => $data['base']['demissional'] ?? 0,
                'base_aso_mudanca_funcao' => $data['base']['mudanca_funcao'] ?? 0,
                'base_aso_retorno_trabalho' => $data['base']['retorno_trabalho'] ?? 0,
                'preco_fechado_admissional' => $data['preco_fechado']['admissional'] ?? null,
                'preco_fechado_periodico' => $data['preco_fechado']['periodico'] ?? null,
                'preco_fechado_demissional' => $data['preco_fechado']['demissional'] ?? null,
                'preco_fechado_mudanca_funcao' => $data['preco_fechado']['mudanca_funcao'] ?? null,
                'preco_fechado_retorno_trabalho' => $data['preco_fechado']['retorno_trabalho'] ?? null,
                'ativo' => $data['ativo'] ?? false,
            ];

            if ($shouldUpdateProtocolos && $protocolos) {
                $updates = array_merge($updates, [
                    'protocolo_id' => $data['protocolo_id'] ?? null,
                    'protocolo_admissional_id' => $protocolos['admissional'],
                    'protocolo_periodico_id' => $protocolos['periodico'],
                    'protocolo_demissional_id' => $protocolos['demissional'],
                    'protocolo_mudanca_funcao_id' => $protocolos['mudanca_funcao'],
                    'protocolo_retorno_trabalho_id' => $protocolos['retorno_trabalho'],
                ]);
            }

            $ghe->update($updates);

            $this->syncFuncoes($ghe, $data['funcoes'] ?? []);
            $this->syncContratoSnapshotsFuncoes($ghe->empresa_id, $ghe->cliente_id);

            return response()->json(['data' => $ghe->fresh()]);
        });
    }

    public function destroy(ClienteGhe $ghe)
    {
        $this->authorizeEmpresa($ghe);

        $ghe->delete();

        return response()->json(['ok' => true]);
    }

    private function syncFuncoes(ClienteGhe $ghe, array $funcoesIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $funcoesIds)));

        ClienteGheFuncao::where('cliente_ghe_id', $ghe->id)
            ->whereNotIn('funcao_id', $ids)
            ->delete();

        $existing = ClienteGheFuncao::query()
            ->where('cliente_ghe_id', $ghe->id)
            ->pluck('funcao_id')
            ->all();

        $toInsert = array_diff($ids, $existing);
        foreach ($toInsert as $funcaoId) {
            ClienteGheFuncao::create([
                'cliente_ghe_id' => $ghe->id,
                'funcao_id' => $funcaoId,
            ]);
        }
    }

    /**
     * Mantém o snapshot do contrato em sincronia com as funções atuais dos GHEs do cliente.
     */
    private function syncContratoSnapshotsFuncoes(int $empresaId, int $clienteId): void
    {
        $clienteGhes = ClienteGhe::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->with('funcoes')
            ->get()
            ->keyBy('id');

        if ($clienteGhes->isEmpty()) {
            return;
        }

        $contratos = ClienteContrato::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('status', 'ATIVO')
            ->with('itens')
            ->get();

        foreach ($contratos as $contrato) {
            foreach ($contrato->itens as $item) {
                $snapshot = $item->regras_snapshot;
                if (!is_array($snapshot) || empty($snapshot['ghes']) || !is_array($snapshot['ghes'])) {
                    continue;
                }

                $changed = false;
                foreach ($snapshot['ghes'] as $idx => $snapGhe) {
                    $clienteGheId = (int) ($snapGhe['id'] ?? 0);
                    if ($clienteGheId <= 0) {
                        continue;
                    }

                    $clienteGhe = $clienteGhes->get($clienteGheId);
                    if (!$clienteGhe) {
                        continue;
                    }

                    $funcoesIds = $clienteGhe->funcoes
                        ->pluck('funcao_id')
                        ->map(fn ($id) => (int) $id)
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();

                    $currentFuncoes = collect($snapGhe['funcoes'] ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();

                    if ($currentFuncoes !== $funcoesIds) {
                        $snapshot['ghes'][$idx]['funcoes'] = $funcoesIds;
                        $changed = true;
                    }
                }

                $map = [];
                foreach ($snapshot['ghes'] as $snapGhe) {
                    $gheId = (int) ($snapGhe['id'] ?? 0);
                    if ($gheId <= 0) {
                        continue;
                    }
                    foreach ((array) ($snapGhe['funcoes'] ?? []) as $funcaoId) {
                        $funcaoId = (int) $funcaoId;
                        if ($funcaoId > 0) {
                            $map[(string) $funcaoId] = $gheId;
                        }
                    }
                }

                if (($snapshot['funcao_ghe_map'] ?? []) !== $map) {
                    $snapshot['funcao_ghe_map'] = $map;
                    $changed = true;
                }

                if ($changed) {
                    $item->regras_snapshot = $snapshot;
                    $item->save();
                }
            }
        }
    }

    private function authorizeEmpresa(ClienteGhe $ghe): void
    {
        abort_if($ghe->empresa_id !== auth()->user()->empresa_id, 403);
    }

    private function authorizeCliente(int $empresaId, int $clienteId): void
    {
        $ok = Cliente::where('empresa_id', $empresaId)
            ->where('id', $clienteId)
            ->exists();
        abort_if(!$ok, 403);
    }

    private function authorizeProtocolo(int $empresaId, ?int $protocoloId): void
    {
        if (!$protocoloId) {
            return;
        }
        $ok = ProtocoloExame::where('empresa_id', $empresaId)
            ->where('id', $protocoloId)
            ->exists();
        abort_if(!$ok, 403);
    }

    private function authorizeFuncoes(int $empresaId, array $funcoes): void
    {
        if (empty($funcoes)) {
            return;
        }
        $count = Funcao::where('empresa_id', $empresaId)
            ->whereIn('id', $funcoes)
            ->count();
        abort_if($count !== count($funcoes), 403);
    }

    private function formatBase(ClienteGhe $ghe): array
    {
        return [
            'admissional' => (float) $ghe->base_aso_admissional,
            'periodico' => (float) $ghe->base_aso_periodico,
            'demissional' => (float) $ghe->base_aso_demissional,
            'mudanca_funcao' => (float) $ghe->base_aso_mudanca_funcao,
            'retorno_trabalho' => (float) $ghe->base_aso_retorno_trabalho,
        ];
    }

    private function formatFechados(ClienteGhe $ghe): array
    {
        return [
            'admissional' => $ghe->preco_fechado_admissional !== null ? (float) $ghe->preco_fechado_admissional : null,
            'periodico' => $ghe->preco_fechado_periodico !== null ? (float) $ghe->preco_fechado_periodico : null,
            'demissional' => $ghe->preco_fechado_demissional !== null ? (float) $ghe->preco_fechado_demissional : null,
            'mudanca_funcao' => $ghe->preco_fechado_mudanca_funcao !== null ? (float) $ghe->preco_fechado_mudanca_funcao : null,
            'retorno_trabalho' => $ghe->preco_fechado_retorno_trabalho !== null ? (float) $ghe->preco_fechado_retorno_trabalho : null,
        ];
    }

    private function resolveProtocolosInput(array $protocolos, ?int $fallback): array
    {
        return [
            'admissional' => $protocolos['admissional'] ?? $fallback,
            'periodico' => $protocolos['periodico'] ?? $fallback,
            'demissional' => $protocolos['demissional'] ?? $fallback,
            'mudanca_funcao' => $protocolos['mudanca_funcao'] ?? $fallback,
            'retorno_trabalho' => $protocolos['retorno_trabalho'] ?? $fallback,
        ];
    }

    private function resolveProtocolosPorTipo(ClienteGhe $ghe): array
    {
        return [
            'admissional' => $ghe->protocoloAdmissional ?: $ghe->protocolo,
            'periodico' => $ghe->protocoloPeriodico ?: $ghe->protocolo,
            'demissional' => $ghe->protocoloDemissional ?: $ghe->protocolo,
            'mudanca_funcao' => $ghe->protocoloMudancaFuncao ?: $ghe->protocolo,
            'retorno_trabalho' => $ghe->protocoloRetornoTrabalho ?: $ghe->protocolo,
        ];
    }
}
