<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ClienteContrato;
use App\Models\ClienteGhe;
use App\Models\ClienteGheFuncao;
use App\Models\Ghe;
use App\Models\GheFuncao;
use App\Models\ProtocoloExame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GheController extends Controller
{
    public function indexJson(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $ghes = Ghe::query()
            ->where('empresa_id', $empresaId)
            ->with(['grupoExames.itens.exame:id,titulo,preco,ativo', 'funcoes.funcao:id,nome'])
            ->orderBy('nome')
            ->get()
            ->map(function (Ghe $ghe) {
                $exames = $ghe->grupoExames?->itens
                    ->map(fn ($it) => $it->exame)
                    ->filter(fn ($ex) => $ex && $ex->ativo)
                    ->values() ?? collect();

                return [
                    'id' => $ghe->id,
                    'nome' => $ghe->nome,
                    'ativo' => (bool) $ghe->ativo,
                    'grupo_exames_id' => $ghe->grupo_exames_id,
                    'grupo' => $ghe->grupoExames ? [
                        'id' => $ghe->grupoExames->id,
                        'titulo' => $ghe->grupoExames->titulo,
                    ] : null,
                    'funcoes' => $ghe->funcoes->map(fn ($f) => [
                        'id' => $f->funcao_id,
                        'nome' => $f->funcao?->nome,
                    ])->filter(fn ($f) => !empty($f['id']))->values()->all(),
                    'base' => [
                        'admissional' => (float) $ghe->base_aso_admissional,
                        'periodico' => (float) $ghe->base_aso_periodico,
                        'demissional' => (float) $ghe->base_aso_demissional,
                        'mudanca_funcao' => (float) $ghe->base_aso_mudanca_funcao,
                        'retorno_trabalho' => (float) $ghe->base_aso_retorno_trabalho,
                    ],
                    'preco_fechado' => [
                        'admissional' => $ghe->preco_fechado_admissional !== null ? (float) $ghe->preco_fechado_admissional : null,
                        'periodico' => $ghe->preco_fechado_periodico !== null ? (float) $ghe->preco_fechado_periodico : null,
                        'demissional' => $ghe->preco_fechado_demissional !== null ? (float) $ghe->preco_fechado_demissional : null,
                        'mudanca_funcao' => $ghe->preco_fechado_mudanca_funcao !== null ? (float) $ghe->preco_fechado_mudanca_funcao : null,
                        'retorno_trabalho' => $ghe->preco_fechado_retorno_trabalho !== null ? (float) $ghe->preco_fechado_retorno_trabalho : null,
                    ],
                    'exames' => $exames->map(fn ($ex) => [
                        'id' => $ex->id,
                        'titulo' => $ex->titulo,
                        'preco' => (float) $ex->preco,
                    ])->all(),
                    'total_exames' => (float) $exames->sum(fn ($ex) => (float) ($ex->preco ?? 0)),
                ];
            })
            ->values();

        return response()->json(['data' => $ghes]);
    }

    public function store(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'grupo_exames_id' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
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
        ]);

        if (!empty($data['grupo_exames_id'])) {
            $ok = ProtocoloExame::query()
                ->where('empresa_id', $empresaId)
                ->where('id', $data['grupo_exames_id'])
                ->exists();
            abort_if(!$ok, 403);
        }

        return DB::transaction(function () use ($empresaId, $data) {
            $ghe = Ghe::create([
                'empresa_id' => $empresaId,
                'nome' => $data['nome'],
                'grupo_exames_id' => $data['grupo_exames_id'] ?? null,
                'base_aso_admissional' => (float) ($data['base']['admissional'] ?? 0),
                'base_aso_periodico' => (float) ($data['base']['periodico'] ?? 0),
                'base_aso_demissional' => (float) ($data['base']['demissional'] ?? 0),
                'base_aso_mudanca_funcao' => (float) ($data['base']['mudanca_funcao'] ?? 0),
                'base_aso_retorno_trabalho' => (float) ($data['base']['retorno_trabalho'] ?? 0),
                'preco_fechado_admissional' => $data['preco_fechado']['admissional'] ?? null,
                'preco_fechado_periodico' => $data['preco_fechado']['periodico'] ?? null,
                'preco_fechado_demissional' => $data['preco_fechado']['demissional'] ?? null,
                'preco_fechado_mudanca_funcao' => $data['preco_fechado']['mudanca_funcao'] ?? null,
                'preco_fechado_retorno_trabalho' => $data['preco_fechado']['retorno_trabalho'] ?? null,
                'ativo' => true,
            ]);

            $this->syncFuncoes($ghe, $data['funcoes'] ?? []);

            return response()->json(['data' => $ghe->fresh()], 201);
        });
    }

    public function update(Request $request, Ghe $ghe)
    {
        $empresaId = $request->user()->empresa_id;
        abort_if($ghe->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'grupo_exames_id' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
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
        ]);

        if (!empty($data['grupo_exames_id'])) {
            $ok = ProtocoloExame::query()
                ->where('empresa_id', $empresaId)
                ->where('id', $data['grupo_exames_id'])
                ->exists();
            abort_if(!$ok, 403);
        }

        return DB::transaction(function () use ($ghe, $data) {
            $funcoesIds = collect($data['funcoes'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $ghe->update([
                'nome' => $data['nome'],
                'grupo_exames_id' => $data['grupo_exames_id'] ?? null,
                'base_aso_admissional' => (float) ($data['base']['admissional'] ?? 0),
                'base_aso_periodico' => (float) ($data['base']['periodico'] ?? 0),
                'base_aso_demissional' => (float) ($data['base']['demissional'] ?? 0),
                'base_aso_mudanca_funcao' => (float) ($data['base']['mudanca_funcao'] ?? 0),
                'base_aso_retorno_trabalho' => (float) ($data['base']['retorno_trabalho'] ?? 0),
                'preco_fechado_admissional' => $data['preco_fechado']['admissional'] ?? null,
                'preco_fechado_periodico' => $data['preco_fechado']['periodico'] ?? null,
                'preco_fechado_demissional' => $data['preco_fechado']['demissional'] ?? null,
                'preco_fechado_mudanca_funcao' => $data['preco_fechado']['mudanca_funcao'] ?? null,
                'preco_fechado_retorno_trabalho' => $data['preco_fechado']['retorno_trabalho'] ?? null,
            ]);

            $this->syncFuncoes($ghe, $funcoesIds);
            $clienteIds = $this->syncClienteGheFuncoes($ghe, $funcoesIds);
            $this->syncContratoSnapshotsFuncoes($ghe->empresa_id, $clienteIds);

            return response()->json(['data' => $ghe->fresh()]);
        });
    }

    public function destroy(Request $request, Ghe $ghe)
    {
        $empresaId = $request->user()->empresa_id;
        abort_if($ghe->empresa_id !== $empresaId, 403);

        $ghe->delete();

        return response()->json(['ok' => true]);
    }

    private function syncFuncoes(Ghe $ghe, array $funcoesIds): void
    {
        $funcoesIds = collect($funcoesIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        GheFuncao::where('ghe_id', $ghe->id)
            ->whereNotIn('funcao_id', $funcoesIds)
            ->delete();

        $existing = GheFuncao::query()
            ->where('ghe_id', $ghe->id)
            ->whereIn('funcao_id', $funcoesIds)
            ->pluck('funcao_id')
            ->all();

        $toCreate = array_diff($funcoesIds, $existing);
        foreach ($toCreate as $funcaoId) {
            GheFuncao::create([
                'ghe_id' => $ghe->id,
                'funcao_id' => $funcaoId,
            ]);
        }
    }

    /**
     * Replica as funções do GHE global para os GHEs já vinculados a clientes.
     * Retorna os IDs de clientes afetados para atualizar snapshots de contrato.
     */
    private function syncClienteGheFuncoes(Ghe $ghe, array $funcoesIds): array
    {
        $clienteGhes = ClienteGhe::query()
            ->where('empresa_id', $ghe->empresa_id)
            ->where('ghe_id', $ghe->id)
            ->get(['id', 'cliente_id']);

        if ($clienteGhes->isEmpty()) {
            return [];
        }

        foreach ($clienteGhes as $clienteGhe) {
            ClienteGheFuncao::where('cliente_ghe_id', $clienteGhe->id)
                ->whereNotIn('funcao_id', $funcoesIds)
                ->delete();

            $existing = ClienteGheFuncao::query()
                ->where('cliente_ghe_id', $clienteGhe->id)
                ->whereIn('funcao_id', $funcoesIds)
                ->pluck('funcao_id')
                ->all();

            $toCreate = array_diff($funcoesIds, $existing);
            foreach ($toCreate as $funcaoId) {
                ClienteGheFuncao::create([
                    'cliente_ghe_id' => $clienteGhe->id,
                    'funcao_id' => $funcaoId,
                ]);
            }
        }

        return $clienteGhes
            ->pluck('cliente_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Atualiza o funcao_ghe_map e lista de funções do snapshot de contratos ativos.
     */
    private function syncContratoSnapshotsFuncoes(int $empresaId, array $clienteIds): void
    {
        if (empty($clienteIds)) {
            return;
        }

        $clienteGhes = ClienteGhe::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('cliente_id', $clienteIds)
            ->with('funcoes')
            ->get()
            ->keyBy('id');

        if ($clienteGhes->isEmpty()) {
            return;
        }

        $contratos = ClienteContrato::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('cliente_id', $clienteIds)
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

                $currentMap = $snapshot['funcao_ghe_map'] ?? [];
                if ($currentMap !== $map) {
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
}
