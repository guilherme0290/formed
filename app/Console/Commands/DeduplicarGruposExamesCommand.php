<?php

namespace App\Console\Commands;

use App\Models\ClienteAsoGrupo;
use App\Models\ClienteContratoItem;
use App\Models\ClienteContratoVigenciaItem;
use App\Models\ClienteGhe;
use App\Models\Ghe;
use App\Models\ParametroClienteAsoGrupo;
use App\Models\ParametroClienteItem;
use App\Models\PropostaAsoGrupo;
use App\Models\PropostaItens;
use App\Models\ProtocoloExame;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicarGruposExamesCommand extends Command
{
    protected $signature = 'protocolos-exames:deduplicar
        {--empresa_id=* : IDs de empresa para processar}
        {--dry-run : Simula sem persistir}';

    protected $description = 'Deduplica grupos de exames com combinacao identica de exames por empresa.';

    public function handle(): int
    {
        $empresaIds = collect($this->option('empresa_id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($empresaIds->isEmpty()) {
            $empresaIds = ProtocoloExame::query()
                ->select('empresa_id')
                ->distinct()
                ->orderBy('empresa_id')
                ->pluck('empresa_id');
        }

        if ($empresaIds->isEmpty()) {
            $this->warn('Nenhuma empresa com grupos de exames encontrada.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->line($dryRun ? 'Executando em DRY-RUN (sem persistir).' : 'Executando com persistencia de dados.');

        foreach ($empresaIds as $empresaId) {
            $this->line('');
            $this->info("Empresa #{$empresaId}");

            $protocolos = ProtocoloExame::query()
                ->where('empresa_id', $empresaId)
                ->with('itens:protocolo_id,exame_id')
                ->orderBy('id')
                ->get(['id', 'titulo']);

            if ($protocolos->isEmpty()) {
                $this->line(' - Sem grupos de exames.');
                continue;
            }

            $assinaturas = [];
            foreach ($protocolos as $protocolo) {
                $ids = $protocolo->itens
                    ->pluck('exame_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($ids)) {
                    continue;
                }

                sort($ids);
                $chave = implode(',', $ids);
                $assinaturas[$chave] ??= [];
                $assinaturas[$chave][] = $protocolo;
            }

            $idMap = [];
            $duplicados = 0;

            foreach ($assinaturas as $grupos) {
                if (count($grupos) < 2) {
                    continue;
                }

                $duplicados++;
                $keeper = $grupos[0];
                $this->line(" - Mantendo grupo #{$keeper->id} ({$keeper->titulo})");

                foreach (array_slice($grupos, 1) as $dup) {
                    $idMap[(int) $dup->id] = (int) $keeper->id;
                    $this->line("   - Duplicado #{$dup->id} ({$dup->titulo}) -> #{$keeper->id}");
                }
            }

            if (empty($idMap)) {
                $this->line(' - Nenhuma duplicidade de combinacao encontrada.');
                continue;
            }

            $execute = function () use ($empresaId, $idMap): void {
                $oldIds = array_keys($idMap);

                foreach ($idMap as $oldId => $newId) {
                    Ghe::query()
                        ->where('empresa_id', $empresaId)
                        ->where('grupo_exames_id', $oldId)
                        ->update(['grupo_exames_id' => $newId]);

                    ClienteGhe::query()
                        ->where('empresa_id', $empresaId)
                        ->where('protocolo_id', $oldId)
                        ->update(['protocolo_id' => $newId]);

                    foreach ([
                        'protocolo_admissional_id',
                        'protocolo_periodico_id',
                        'protocolo_demissional_id',
                        'protocolo_mudanca_funcao_id',
                        'protocolo_retorno_trabalho_id',
                    ] as $column) {
                        ClienteGhe::query()
                            ->where('empresa_id', $empresaId)
                            ->where($column, $oldId)
                            ->update([$column => $newId]);
                    }

                    ClienteAsoGrupo::query()
                        ->where('empresa_id', $empresaId)
                        ->where('grupo_exames_id', $oldId)
                        ->update(['grupo_exames_id' => $newId]);

                    ParametroClienteAsoGrupo::query()
                        ->where('empresa_id', $empresaId)
                        ->where('grupo_exames_id', $oldId)
                        ->update(['grupo_exames_id' => $newId]);

                    PropostaAsoGrupo::query()
                        ->where('empresa_id', $empresaId)
                        ->where('grupo_exames_id', $oldId)
                        ->update(['grupo_exames_id' => $newId]);
                }

                $titleMap = ProtocoloExame::query()
                    ->whereIn('id', array_values(array_unique(array_values($idMap))))
                    ->pluck('titulo', 'id')
                    ->mapWithKeys(fn ($titulo, $id) => [(int) $id => (string) $titulo])
                    ->all();

                $this->rewriteMetaGrupoIds($empresaId, $idMap);
                $this->rewriteContratoSnapshots($empresaId, $idMap, $titleMap);
                $this->rewriteVigenciaSnapshots($empresaId, $idMap, $titleMap);

                ProtocoloExame::query()
                    ->where('empresa_id', $empresaId)
                    ->whereIn('id', $oldIds)
                    ->delete();
            };

            if ($dryRun) {
                try {
                    DB::transaction(function () use ($execute): void {
                        $execute();
                        throw new \RuntimeException('__DRY_RUN_ROLLBACK__');
                    });
                } catch (\RuntimeException $e) {
                    if ($e->getMessage() !== '__DRY_RUN_ROLLBACK__') {
                        throw $e;
                    }
                    $this->line(' - Dry-run rollback aplicado.');
                }
            } else {
                DB::transaction($execute);
            }

            $this->line(" - Combinacoes deduplicadas: {$duplicados}");
            $this->line(' - Grupos removidos: ' . count($idMap));
        }

        if ($dryRun) {
            $this->warn('Dry-run finalizado. Nenhuma alteracao persistida.');
        } else {
            $this->info('Deduplicacao concluida com sucesso.');
        }

        return self::SUCCESS;
    }

    private function rewriteMetaGrupoIds(int $empresaId, array $idMap): void
    {
        PropostaItens::query()
            ->whereHas('proposta', fn ($q) => $q->where('empresa_id', $empresaId))
            ->chunkById(200, function ($rows) use ($idMap): void {
                foreach ($rows as $row) {
                    $meta = is_array($row->meta) ? $row->meta : [];
                    if (!$this->shouldRewriteMeta($meta, $idMap)) {
                        continue;
                    }

                    $grupoId = (int) ($meta['grupo_id'] ?? 0);
                    if ($grupoId > 0 && isset($idMap[$grupoId])) {
                        $meta['grupo_id'] = $idMap[$grupoId];
                        $row->meta = $meta;
                        $row->save();
                    }
                }
            });

        ParametroClienteItem::query()
            ->whereHas('parametro', fn ($q) => $q->where('empresa_id', $empresaId))
            ->chunkById(200, function ($rows) use ($idMap): void {
                foreach ($rows as $row) {
                    $meta = is_array($row->meta) ? $row->meta : [];
                    if (!$this->shouldRewriteMeta($meta, $idMap)) {
                        continue;
                    }

                    $grupoId = (int) ($meta['grupo_id'] ?? 0);
                    if ($grupoId > 0 && isset($idMap[$grupoId])) {
                        $meta['grupo_id'] = $idMap[$grupoId];
                        $row->meta = $meta;
                        $row->save();
                    }
                }
            });
    }

    private function rewriteContratoSnapshots(int $empresaId, array $idMap, array $titleMap): void
    {
        ClienteContratoItem::query()
            ->whereHas('contrato', fn ($q) => $q->where('empresa_id', $empresaId))
            ->chunkById(200, function ($rows) use ($idMap, $titleMap): void {
                foreach ($rows as $row) {
                    $snapshot = is_array($row->regras_snapshot) ? $row->regras_snapshot : [];
                    $updated = $this->rewriteSnapshotArray($snapshot, $idMap, $titleMap);
                    if ($updated === $snapshot) {
                        continue;
                    }
                    $row->regras_snapshot = $updated;
                    $row->save();
                }
            });
    }

    private function rewriteVigenciaSnapshots(int $empresaId, array $idMap, array $titleMap): void
    {
        ClienteContratoVigenciaItem::query()
            ->whereHas('vigencia.contrato', fn ($q) => $q->where('empresa_id', $empresaId))
            ->chunkById(200, function ($rows) use ($idMap, $titleMap): void {
                foreach ($rows as $row) {
                    $snapshot = is_array($row->regras_snapshot) ? $row->regras_snapshot : [];
                    $updated = $this->rewriteSnapshotArray($snapshot, $idMap, $titleMap);
                    if ($updated === $snapshot) {
                        continue;
                    }
                    $row->regras_snapshot = $updated;
                    $row->save();
                }
            });
    }

    private function shouldRewriteMeta(array $meta, array $idMap): bool
    {
        if (empty($meta['aso_tipo'])) {
            return false;
        }

        $grupoId = (int) ($meta['grupo_id'] ?? 0);
        return $grupoId > 0 && isset($idMap[$grupoId]);
    }

    private function rewriteSnapshotArray(array $snapshot, array $idMap, array $titleMap): array
    {
        $grupoId = (int) ($snapshot['grupo_id'] ?? 0);
        if (!empty($snapshot['aso_tipo']) && $grupoId > 0 && isset($idMap[$grupoId])) {
            $snapshot['grupo_id'] = $idMap[$grupoId];
        }

        if (!empty($snapshot['ghes']) && is_array($snapshot['ghes'])) {
            $snapshot['ghes'] = array_map(function ($ghe) use ($idMap, $titleMap) {
                if (!is_array($ghe)) {
                    return $ghe;
                }

                if (!empty($ghe['protocolo']['id'])) {
                    $id = (int) $ghe['protocolo']['id'];
                    if (isset($idMap[$id])) {
                        $newId = $idMap[$id];
                        $ghe['protocolo']['id'] = $newId;
                        if (isset($titleMap[$newId])) {
                            $ghe['protocolo']['titulo'] = $titleMap[$newId];
                        }
                    }
                }

                if (!empty($ghe['protocolos']) && is_array($ghe['protocolos'])) {
                    foreach ($ghe['protocolos'] as $tipo => $row) {
                        if (!is_array($row) || empty($row['id'])) {
                            continue;
                        }

                        $id = (int) $row['id'];
                        if (!isset($idMap[$id])) {
                            continue;
                        }

                        $newId = $idMap[$id];
                        $ghe['protocolos'][$tipo]['id'] = $newId;
                        if (isset($titleMap[$newId])) {
                            $ghe['protocolos'][$tipo]['titulo'] = $titleMap[$newId];
                        }
                    }
                }

                return $ghe;
            }, $snapshot['ghes']);
        }

        return $snapshot;
    }
}
