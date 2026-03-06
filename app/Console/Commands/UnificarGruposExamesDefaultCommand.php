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
use App\Models\ProtocoloExameItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UnificarGruposExamesDefaultCommand extends Command
{
    protected $signature = 'protocolos-exames:unificar-default
        {--empresa_id=* : IDs de empresa para processar}
        {--dry-run : Simula sem persistir}';

    protected $description = 'Unifica grupos de exames em um único grupo default "ASO- ADMINSTRATIVO" por empresa.';

    private const DEFAULT_TITLE = 'ASO- ADMINSTRATIVO';

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
        $this->line($dryRun ? 'Executando em DRY-RUN (sem persistir).' : 'Executando com persistência de dados.');

        foreach ($empresaIds as $empresaId) {
            $this->line('');
            $this->info("Empresa #{$empresaId}");

            $execute = function () use ($empresaId, $dryRun): void {
                $default = ProtocoloExame::query()
                    ->where('empresa_id', $empresaId)
                    ->where('titulo', self::DEFAULT_TITLE)
                    ->orderBy('id')
                    ->first();

                if (!$default) {
                    $default = ProtocoloExame::create([
                        'empresa_id' => $empresaId,
                        'titulo' => self::DEFAULT_TITLE,
                        'descricao' => 'Grupo default unificado automaticamente',
                        'ativo' => true,
                    ]);
                    $this->line(" - Grupo default criado: #{$default->id}");
                } else {
                    $this->line(" - Grupo default existente: #{$default->id}");
                }

                $allProtocolIds = ProtocoloExame::query()
                    ->where('empresa_id', $empresaId)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                $oldIds = collect($allProtocolIds)
                    ->filter(fn ($id) => (int) $id !== (int) $default->id)
                    ->values()
                    ->all();

                $allExamIds = ProtocoloExameItem::query()
                    ->whereIn('protocolo_id', $allProtocolIds)
                    ->pluck('exame_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                $existingDefaultExamIds = ProtocoloExameItem::query()
                    ->where('protocolo_id', $default->id)
                    ->pluck('exame_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $toInsert = array_diff($allExamIds, $existingDefaultExamIds);
                foreach ($toInsert as $exameId) {
                    ProtocoloExameItem::create([
                        'protocolo_id' => $default->id,
                        'exame_id' => $exameId,
                    ]);
                }

                $this->line(' - Exames no grupo default: ' . count($allExamIds));

                if (!empty($oldIds)) {
                    Ghe::query()
                        ->where('empresa_id', $empresaId)
                        ->whereIn('grupo_exames_id', $oldIds)
                        ->update(['grupo_exames_id' => $default->id]);

                    ClienteGhe::query()
                        ->where('empresa_id', $empresaId)
                        ->whereIn('protocolo_id', $oldIds)
                        ->update(['protocolo_id' => $default->id]);

                    foreach ([
                        'protocolo_admissional_id',
                        'protocolo_periodico_id',
                        'protocolo_demissional_id',
                        'protocolo_mudanca_funcao_id',
                        'protocolo_retorno_trabalho_id',
                    ] as $column) {
                        ClienteGhe::query()
                            ->where('empresa_id', $empresaId)
                            ->whereIn($column, $oldIds)
                            ->update([$column => $default->id]);
                    }

                    ClienteAsoGrupo::query()
                        ->where('empresa_id', $empresaId)
                        ->whereIn('grupo_exames_id', $oldIds)
                        ->update(['grupo_exames_id' => $default->id]);

                    ParametroClienteAsoGrupo::query()
                        ->where('empresa_id', $empresaId)
                        ->whereIn('grupo_exames_id', $oldIds)
                        ->update(['grupo_exames_id' => $default->id]);

                    PropostaAsoGrupo::query()
                        ->where('empresa_id', $empresaId)
                        ->whereIn('grupo_exames_id', $oldIds)
                        ->update(['grupo_exames_id' => $default->id]);
                }

                $this->rewriteMetaGrupoIds($empresaId, $default->id, $oldIds);
                $this->rewriteContratoSnapshots($empresaId, $default->id, $oldIds);
                $this->rewriteVigenciaSnapshots($empresaId, $default->id, $oldIds);

                $this->line(' - Grupos antigos mantidos: ' . count($oldIds));
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
        }

        if ($dryRun) {
            $this->warn('Dry-run finalizado. Nenhuma alteração persistida.');
        } else {
            $this->info('Unificação concluída com sucesso.');
        }

        return self::SUCCESS;
    }

    private function rewriteMetaGrupoIds(int $empresaId, int $defaultId, array $oldIds): void
    {
        PropostaItens::query()
            ->whereHas('proposta', fn ($q) => $q->where('empresa_id', $empresaId))
            ->chunkById(200, function ($rows) use ($defaultId, $oldIds): void {
                foreach ($rows as $row) {
                    $meta = is_array($row->meta) ? $row->meta : [];
                    if (!$this->shouldRewriteMeta($meta, $oldIds)) {
                        continue;
                    }
                    $meta['grupo_id'] = $defaultId;
                    $row->meta = $meta;
                    $row->save();
                }
            });

        ParametroClienteItem::query()
            ->whereHas('parametro', fn ($q) => $q->where('empresa_id', $empresaId))
            ->chunkById(200, function ($rows) use ($defaultId, $oldIds): void {
                foreach ($rows as $row) {
                    $meta = is_array($row->meta) ? $row->meta : [];
                    if (!$this->shouldRewriteMeta($meta, $oldIds)) {
                        continue;
                    }
                    $meta['grupo_id'] = $defaultId;
                    $row->meta = $meta;
                    $row->save();
                }
            });
    }

    private function rewriteContratoSnapshots(int $empresaId, int $defaultId, array $oldIds): void
    {
        ClienteContratoItem::query()
            ->whereHas('contrato', fn ($q) => $q->where('empresa_id', $empresaId))
            ->chunkById(200, function ($rows) use ($defaultId, $oldIds): void {
                foreach ($rows as $row) {
                    $snapshot = is_array($row->regras_snapshot) ? $row->regras_snapshot : [];
                    $updated = $this->rewriteSnapshotArray($snapshot, $defaultId, $oldIds);
                    if ($updated === $snapshot) {
                        continue;
                    }
                    $row->regras_snapshot = $updated;
                    $row->save();
                }
            });
    }

    private function rewriteVigenciaSnapshots(int $empresaId, int $defaultId, array $oldIds): void
    {
        ClienteContratoVigenciaItem::query()
            ->whereHas('vigencia.contrato', fn ($q) => $q->where('empresa_id', $empresaId))
            ->chunkById(200, function ($rows) use ($defaultId, $oldIds): void {
                foreach ($rows as $row) {
                    $snapshot = is_array($row->regras_snapshot) ? $row->regras_snapshot : [];
                    $updated = $this->rewriteSnapshotArray($snapshot, $defaultId, $oldIds);
                    if ($updated === $snapshot) {
                        continue;
                    }
                    $row->regras_snapshot = $updated;
                    $row->save();
                }
            });
    }

    private function shouldRewriteMeta(array $meta, array $oldIds): bool
    {
        $hasAsoTipo = !empty($meta['aso_tipo']);
        if (!$hasAsoTipo) {
            return false;
        }

        $grupoId = (int) ($meta['grupo_id'] ?? 0);
        if ($grupoId <= 0) {
            return true;
        }

        return in_array($grupoId, $oldIds, true);
    }

    private function rewriteSnapshotArray(array $snapshot, int $defaultId, array $oldIds): array
    {
        $grupoId = (int) ($snapshot['grupo_id'] ?? 0);
        if (!empty($snapshot['aso_tipo']) && ($grupoId <= 0 || in_array($grupoId, $oldIds, true))) {
            $snapshot['grupo_id'] = $defaultId;
        }

        if (!empty($snapshot['ghes']) && is_array($snapshot['ghes'])) {
            $snapshot['ghes'] = array_map(function ($ghe) use ($defaultId, $oldIds) {
                if (!is_array($ghe)) {
                    return $ghe;
                }

                if (!empty($ghe['protocolo']['id']) && in_array((int) $ghe['protocolo']['id'], $oldIds, true)) {
                    $ghe['protocolo']['id'] = $defaultId;
                    $ghe['protocolo']['titulo'] = self::DEFAULT_TITLE;
                }

                if (!empty($ghe['protocolos']) && is_array($ghe['protocolos'])) {
                    foreach ($ghe['protocolos'] as $tipo => $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        if (!empty($row['id']) && in_array((int) $row['id'], $oldIds, true)) {
                            $ghe['protocolos'][$tipo]['id'] = $defaultId;
                            $ghe['protocolos'][$tipo]['titulo'] = self::DEFAULT_TITLE;
                        }
                    }
                }

                return $ghe;
            }, $snapshot['ghes']);
        }

        return $snapshot;
    }
}
