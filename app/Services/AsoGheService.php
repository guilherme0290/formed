<?php

namespace App\Services;

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

    public function totalAsoContratoPorTipo(ClienteContrato $contrato, int $servicoId, string $tipoAso): ?float
    {
        $itens = $this->resolveItensContratoAsoPorTipo($contrato, $servicoId, $tipoAso);
        if ($itens->isEmpty()) {
            return null;
        }

        $total = (float) $itens->sum(fn ($item) => (float) $item->preco_unitario_snapshot);

        return $total > 0 ? $total : null;
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
