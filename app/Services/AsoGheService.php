<?php

namespace App\Services;

use App\Models\ClienteAsoGrupo;
use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use App\Models\ClienteGhe;
use App\Models\Funcao;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class AsoGheService
{
    public function resolveServicoAsoId(int $clienteId, int $empresaId): ?int
    {
        $contrato = ClienteContrato::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('status', 'ATIVO')
            ->with('itens')
            ->orderByDesc('vigencia_inicio')
            ->first();

        return $this->resolveServicoAsoIdFromContrato($contrato);
    }

    public function resolveServicoAsoIdFromContrato(?ClienteContrato $contrato): ?int
    {
        if (!$contrato) {
            return null;
        }

        $itens = $contrato->relationLoaded('itens') ? $contrato->itens : $contrato->itens()->get();
        $asoItem = $itens->first(fn ($item) => $this->isAsoItemContrato($item));
        if ($asoItem?->servico_id) {
            return (int) $asoItem->servico_id;
        }

        $asoItem = $itens->first(function ($item) {
            $descricao = strtoupper((string) ($item->descricao_snapshot ?? ''));
            return $descricao !== '' && str_contains($descricao, 'ASO');
        });

        return $asoItem?->servico_id ? (int) $asoItem->servico_id : null;
    }

    public function resolveTiposAsoContrato(?ClienteContrato $contrato): array
    {
        if (!$contrato) {
            return [];
        }

        $itens = $contrato->relationLoaded('itens') ? $contrato->itens : $contrato->itens()->get();
        $tipos = [];

        foreach ($itens as $item) {
            $tipo = $item->regras_snapshot['aso_tipo'] ?? null;
            if (!$tipo) {
                $tipo = $this->inferTipoAsoFromDescricao($item->descricao_snapshot ?? null);
            }

            if ($tipo) {
                $tipos[$tipo] = true;
            }
        }

        return array_keys($tipos);
    }

    public function resolveItensContratoAsoPorTipo(ClienteContrato $contrato, int $servicoId, string $tipoAso): Collection
    {
        $itens = $contrato->relationLoaded('itens')
            ? $contrato->itens->filter(fn ($item) => (int) $item->servico_id === $servicoId && $item->ativo)
            : $contrato->itens()
                ->where('servico_id', $servicoId)
                ->where('ativo', true)
                ->get();

        if ($itens->isEmpty()) {
            return collect();
        }

        $itensTipo = $itens->filter(fn ($item) => ($item->regras_snapshot['aso_tipo'] ?? null) === $tipoAso);
        if ($itensTipo->isNotEmpty()) {
            return $itensTipo->values();
        }

        $itensTipo = $itens->filter(function ($item) use ($tipoAso) {
            $tipoInferido = $this->inferTipoAsoFromDescricao($item->descricao_snapshot ?? null);
            return $tipoInferido === $tipoAso;
        });
        if ($itensTipo->isNotEmpty()) {
            return $itensTipo->values();
        }

        return $itens->count() === 1 ? $itens->values() : collect();
    }

    public function resolveItensContratoAsoPorFuncaoTipo(ClienteContrato $contrato, int $servicoId, int $funcaoId, string $tipoAso): Collection
    {
        $snapshot = $this->resolveAsoSnapshotFromContrato($contrato);
        $ghe = $this->resolveGheSnapshotByFuncao($snapshot, $funcaoId);

        if (!$ghe) {
            return collect();
        }

        $preco = $ghe['total_por_tipo'][$tipoAso] ?? null;
        if (!is_numeric($preco)) {
            $base = $ghe['base'][$tipoAso] ?? 0;
            $totalExames = $ghe['total_exames_por_tipo'][$tipoAso] ?? 0;
            $preco = (float) $base + (float) $totalExames;
        }

        if (!is_numeric($preco)) {
            return collect();
        }

        $itemBase = $this->resolveItensContratoAsoPorTipo($contrato, $servicoId, $tipoAso)->first();
        if (!$itemBase) {
            $itemBase = $contrato->itens()
                ->where('servico_id', $servicoId)
                ->where('ativo', true)
                ->orderBy('descricao_snapshot')
                ->first();
        }

        if (!$itemBase) {
            return collect();
        }

        $clone = clone $itemBase;
        $clone->preco_unitario_snapshot = (float) $preco;

        return collect([$clone]);
    }

    public function isAsoItemContrato(?ClienteContratoItem $item): bool
    {
        if (!$item) {
            return false;
        }

        if (!empty($item->regras_snapshot['ghes']) || !empty($item->regras_snapshot['aso_tipo'])) {
            return true;
        }

        $descricao = strtoupper((string) ($item->descricao_snapshot ?? ''));
        return $descricao !== '' && str_contains($descricao, 'ASO');
    }

    public function buildSnapshotForCliente(int $clienteId, int $empresaId): array
    {
        $ghes = ClienteGhe::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->with([
                'protocolo.itens.exame:id,titulo,preco,ativo',
                'protocoloAdmissional.itens.exame:id,titulo,preco,ativo',
                'protocoloPeriodico.itens.exame:id,titulo,preco,ativo',
                'protocoloDemissional.itens.exame:id,titulo,preco,ativo',
                'protocoloMudancaFuncao.itens.exame:id,titulo,preco,ativo',
                'protocoloRetornoTrabalho.itens.exame:id,titulo,preco,ativo',
                'asoGrupos.grupo.itens.exame:id,titulo,preco,ativo',
                'funcoes'
            ])
            ->orderBy('nome')
            ->get();

        $snapshotGhes = [];
        $funcaoMap = [];

        foreach ($ghes as $ghe) {
            $protocolos = $this->resolveProtocolosPorTipo($ghe);
            $examesPorTipo = [];
            $totalExamesPorTipo = [];

            foreach ($protocolos as $tipo => $protocolo) {
                $exames = $protocolo?->itens
                    ->map(fn ($it) => $it->exame)
                    ->filter(fn ($ex) => $ex && $ex->ativo)
                    ->values() ?? collect();
                $examesPorTipo[$tipo] = $exames;
                $totalExamesPorTipo[$tipo] = (float) $exames->sum(fn ($ex) => (float) ($ex->preco ?? 0));
            }

            $totalExames = (float) ($totalExamesPorTipo['admissional'] ?? 0);

            $base = [
                'admissional' => (float) $ghe->base_aso_admissional,
                'periodico' => (float) $ghe->base_aso_periodico,
                'demissional' => (float) $ghe->base_aso_demissional,
                'mudanca_funcao' => (float) $ghe->base_aso_mudanca_funcao,
                'retorno_trabalho' => (float) $ghe->base_aso_retorno_trabalho,
            ];

            $fechado = [
                'admissional' => $ghe->preco_fechado_admissional !== null ? (float) $ghe->preco_fechado_admissional : null,
                'periodico' => $ghe->preco_fechado_periodico !== null ? (float) $ghe->preco_fechado_periodico : null,
                'demissional' => $ghe->preco_fechado_demissional !== null ? (float) $ghe->preco_fechado_demissional : null,
                'mudanca_funcao' => $ghe->preco_fechado_mudanca_funcao !== null ? (float) $ghe->preco_fechado_mudanca_funcao : null,
                'retorno_trabalho' => $ghe->preco_fechado_retorno_trabalho !== null ? (float) $ghe->preco_fechado_retorno_trabalho : null,
            ];

            $totalPorTipo = [];
            foreach ($base as $tipo => $valorBase) {
                $totalTipoExames = (float) ($totalExamesPorTipo[$tipo] ?? 0);
                $totalPorTipo[$tipo] = $fechado[$tipo] !== null
                    ? (float) $fechado[$tipo]
                    : (float) ($valorBase + $totalTipoExames);
            }

            $funcoesIds = $ghe->funcoes->pluck('funcao_id')->filter()->values()->all();
            foreach ($funcoesIds as $funcaoId) {
                $funcaoMap[(string) $funcaoId] = $ghe->id;
            }

            $snapshotGhes[] = [
                'id' => $ghe->id,
                'nome' => $ghe->nome,
                'protocolo' => $protocolos['admissional'] ? [
                    'id' => $protocolos['admissional']->id,
                    'titulo' => $protocolos['admissional']->titulo,
                ] : null,
                'protocolos' => collect($protocolos)->map(function ($protocolo) {
                    return $protocolo ? [
                        'id' => $protocolo->id,
                        'titulo' => $protocolo->titulo,
                    ] : null;
                })->all(),
                'exames' => ($examesPorTipo['admissional'] ?? collect())->map(fn ($ex) => [
                    'id' => $ex->id,
                    'titulo' => $ex->titulo,
                    'preco' => (float) $ex->preco,
                ])->all(),
                'exames_por_tipo' => collect($examesPorTipo)->map(function ($exames) {
                    return $exames->map(fn ($ex) => [
                        'id' => $ex->id,
                        'titulo' => $ex->titulo,
                        'preco' => (float) $ex->preco,
                    ])->all();
                })->all(),
                'total_exames' => $totalExames,
                'total_exames_por_tipo' => $totalExamesPorTipo,
                'base' => $base,
                'preco_fechado' => $fechado,
                'total_por_tipo' => $totalPorTipo,
                'funcoes' => $funcoesIds,
            ];
        }

        return [
            'versao' => 1,
            'cliente_id' => $clienteId,
            'gerado_em' => Carbon::now()->toDateTimeString(),
            'ghes' => $snapshotGhes,
            'funcao_ghe_map' => $funcaoMap,
        ];
    }

    public function resolveAsoSnapshotFromContrato(?ClienteContrato $contrato): ?array
    {
        if (!$contrato) {
            return null;
        }

        $itens = $contrato->relationLoaded('itens') ? $contrato->itens : $contrato->itens()->get();
        $item = $itens->first(function ($item) {
            $snapshot = $item->regras_snapshot ?? [];
            return !empty($snapshot['ghes']) || !empty($snapshot['funcao_ghe_map']);
        });

        $snapshot = $item?->regras_snapshot;
        if (!is_array($snapshot)) {
            return null;
        }

        $overrides = ClienteAsoGrupo::query()
            ->where('empresa_id', $contrato->empresa_id)
            ->where('cliente_id', $contrato->cliente_id)
            ->get();
        if ($overrides->isNotEmpty()) {
            $snapshot = $this->applyAsoGrupoOverrides($snapshot, $overrides);
        }

        return $snapshot;
    }

    public function funcoesDisponiveisParaContrato(?ClienteContrato $contrato, int $empresaId): Collection
    {
        $snapshot = $this->resolveAsoSnapshotFromContrato($contrato);
        $map = $snapshot['funcao_ghe_map'] ?? [];

        $ids = collect(array_keys($map))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Funcao::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('id', $ids)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
    }

    public function resolveGheSnapshotByFuncao(?array $snapshot, ?int $funcaoId): ?array
    {
        if (!$snapshot || !$funcaoId) {
            return null;
        }

        $map = $snapshot['funcao_ghe_map'] ?? [];
        $gheId = $map[(string) $funcaoId] ?? null;
        if (!$gheId) {
            return null;
        }

        foreach (($snapshot['ghes'] ?? []) as $ghe) {
            if ((string) ($ghe['id'] ?? '') === (string) $gheId) {
                return $ghe;
            }
        }

        return null;
    }

    public function resolvePrecoAsoPorFuncaoTipo(?ClienteContrato $contrato, ?int $funcaoId, ?string $tipoAso): ?float
    {
        if (!$contrato || !$funcaoId || !$tipoAso) {
            return null;
        }

        $snapshot = $this->resolveAsoSnapshotFromContrato($contrato);
        $ghe = $this->resolveGheSnapshotByFuncao($snapshot, $funcaoId);

        if (!$ghe) {
            return null;
        }

        $total = $ghe['total_por_tipo'][$tipoAso] ?? null;
        if (is_numeric($total)) {
            return (float) $total;
        }

        $base = $ghe['base'][$tipoAso] ?? 0;
        $totalExames = $ghe['total_exames_por_tipo'][$tipoAso] ?? null;

        if (!is_numeric($base) && !is_numeric($totalExames)) {
            return null;
        }

        return (float) ($base ?? 0) + (float) ($totalExames ?? 0);
    }

    public function resolveExamesAsoPorFuncaoTipo(?ClienteContrato $contrato, ?int $funcaoId, ?string $tipoAso): array
    {
        if (!$contrato || !$funcaoId || !$tipoAso) {
            return [];
        }

        $snapshot = $this->resolveAsoSnapshotFromContrato($contrato);
        $ghe = $this->resolveGheSnapshotByFuncao($snapshot, $funcaoId);

        if (!$ghe) {
            return [];
        }

        $exames = $ghe['exames_por_tipo'][$tipoAso] ?? ($ghe['exames'] ?? []);

        return is_array($exames) ? $exames : [];
    }

    public function applyAsoGrupoOverrides(array $snapshot, iterable $asoGrupos): array
    {
        if (empty($snapshot['ghes'])) {
            return $snapshot;
        }

        $map = [];
        foreach ($asoGrupos as $row) {
            $clienteGheId = (int) ($row['cliente_ghe_id'] ?? $row->cliente_ghe_id ?? 0);
            $tipo = (string) ($row['tipo_aso'] ?? $row->tipo_aso ?? '');
            $total = (float) ($row['total_exames'] ?? $row->total_exames ?? 0);
            if ($clienteGheId <= 0 || $tipo === '') {
                continue;
            }
            $map[$clienteGheId][$tipo] = $total;
        }

        if (empty($map)) {
            return $snapshot;
        }

        foreach ($snapshot['ghes'] as $idx => $ghe) {
            $gheId = (int) ($ghe['id'] ?? 0);
            if (!$gheId || empty($map[$gheId])) {
                continue;
            }

            foreach ($map[$gheId] as $tipo => $novoTotal) {
                $exames = $ghe['exames_por_tipo'][$tipo] ?? [];
                if (!is_array($exames)) {
                    $exames = [];
                }

                $totalAtual = (float) ($ghe['total_exames_por_tipo'][$tipo] ?? 0);
                if (abs($totalAtual - $novoTotal) < 0.01) {
                    continue;
                }

                $examesRateados = $this->ratearExames($exames, $novoTotal);
                $snapshot['ghes'][$idx]['exames_por_tipo'][$tipo] = $examesRateados;
                if ($tipo === 'admissional') {
                    $snapshot['ghes'][$idx]['exames'] = $examesRateados;
                }

                $snapshot['ghes'][$idx]['total_exames_por_tipo'][$tipo] = $novoTotal;
                if ($tipo === 'admissional') {
                    $snapshot['ghes'][$idx]['total_exames'] = $novoTotal;
                }

                $snapshot['ghes'][$idx]['total_por_tipo'][$tipo] = $novoTotal;
                $snapshot['ghes'][$idx]['rateado_por_tipo'][$tipo] = true;
            }
        }

        return $snapshot;
    }

    private function ratearExames(array $exames, float $novoTotal): array
    {
        $count = count($exames);
        if ($count === 0) {
            return [];
        }

        $somaOriginal = 0.0;
        foreach ($exames as $ex) {
            $somaOriginal += (float) ($ex['preco'] ?? 0);
        }

        $result = [];
        $acumulado = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $precoBase = (float) ($exames[$i]['preco'] ?? 0);
            if ($somaOriginal > 0) {
                $novoPreco = $precoBase / $somaOriginal * $novoTotal;
            } else {
                $novoPreco = $novoTotal / $count;
            }
            $novoPreco = round($novoPreco, 2);
            $acumulado += $novoPreco;
            $item = $exames[$i];
            $item['preco'] = $novoPreco;
            $result[] = $item;
        }

        $diff = round($novoTotal - $acumulado, 2);
        if (abs($diff) >= 0.01 && !empty($result)) {
            $result[$count - 1]['preco'] = round(($result[$count - 1]['preco'] ?? 0) + $diff, 2);
        }

        return $result;
    }

    public function funcoesDisponiveisParaCliente(int $empresaId, int $clienteId): Collection
    {
        $funcoesIds = ClienteGhe::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->with('funcoes')
            ->get()
            ->pluck('funcoes')
            ->flatten()
            ->pluck('funcao_id')
            ->filter()
            ->unique()
            ->values();

        if ($funcoesIds->isEmpty()) {
            return collect();
        }

        return Funcao::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('id', $funcoesIds)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
    }

    private function resolveProtocolosPorTipo(ClienteGhe $ghe): array
    {
        if ($ghe->relationLoaded('asoGrupos') && $ghe->asoGrupos->count()) {
            $map = $ghe->asoGrupos
                ->filter(fn ($row) => !empty($row->grupo_exames_id))
                ->keyBy(fn ($row) => (string) $row->tipo_aso);
            return [
                'admissional' => $map->get('admissional')?->grupo,
                'periodico' => $map->get('periodico')?->grupo,
                'demissional' => $map->get('demissional')?->grupo,
                'mudanca_funcao' => $map->get('mudanca_funcao')?->grupo,
                'retorno_trabalho' => $map->get('retorno_trabalho')?->grupo,
            ];
        }

        return [
            'admissional' => $ghe->protocoloAdmissional ?: $ghe->protocolo,
            'periodico' => $ghe->protocoloPeriodico ?: $ghe->protocolo,
            'demissional' => $ghe->protocoloDemissional ?: $ghe->protocolo,
            'mudanca_funcao' => $ghe->protocoloMudancaFuncao ?: $ghe->protocolo,
            'retorno_trabalho' => $ghe->protocoloRetornoTrabalho ?: $ghe->protocolo,
        ];
    }

    private function inferTipoAsoFromDescricao(?string $descricao): ?string
    {
        $texto = mb_strtolower(trim((string) $descricao));
        if ($texto === '') {
            return null;
        }
        if (!str_contains($texto, 'aso')) {
            return null;
        }

        $map = [
            'admissional' => ['admissional'],
            'periodico' => ['periodico', 'periódico'],
            'demissional' => ['demissional'],
            'mudanca_funcao' => ['mudanca', 'mudança'],
            'retorno_trabalho' => ['retorno'],
        ];

        foreach ($map as $tipo => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($texto, $keyword)) {
                    return $tipo;
                }
            }
        }

        return null;
    }
}
