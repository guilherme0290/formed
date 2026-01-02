<?php

namespace App\Services;

use App\Models\ClienteContrato;
use App\Models\ClienteGhe;
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
        $asoItem = $itens->first(fn ($item) => !empty($item->regras_snapshot['ghes']));
        if ($asoItem?->servico_id) {
            return (int) $asoItem->servico_id;
        }

        if (!$contrato->cliente_id) {
            return null;
        }

        $temGhe = ClienteGhe::query()
            ->where('empresa_id', $contrato->empresa_id)
            ->where('cliente_id', $contrato->cliente_id)
            ->where('ativo', true)
            ->exists();
        if (!$temGhe) {
            return null;
        }

        $asoItem = $itens->first(function ($item) {
            $descricao = strtoupper((string) ($item->descricao_snapshot ?? ''));
            return $descricao !== '' && str_contains($descricao, 'ASO');
        });

        return $asoItem?->servico_id ? (int) $asoItem->servico_id : null;
    }

    public function buildSnapshotForCliente(int $clienteId, int $empresaId): array
    {
        $ghes = ClienteGhe::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->with(['protocolo.itens.exame:id,titulo,preco,ativo', 'funcoes'])
            ->orderBy('nome')
            ->get();

        $snapshotGhes = [];
        $funcaoMap = [];

        foreach ($ghes as $ghe) {
            $exames = $ghe->protocolo?->itens
                ->map(fn ($it) => $it->exame)
                ->filter(fn ($ex) => $ex && $ex->ativo)
                ->values() ?? collect();

            $totalExames = (float) $exames->sum(fn ($ex) => (float) ($ex->preco ?? 0));

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
                $totalPorTipo[$tipo] = $fechado[$tipo] !== null
                    ? (float) $fechado[$tipo]
                    : (float) ($valorBase + $totalExames);
            }

            $funcoesIds = $ghe->funcoes->pluck('funcao_id')->filter()->values()->all();
            foreach ($funcoesIds as $funcaoId) {
                $funcaoMap[(string) $funcaoId] = $ghe->id;
            }

            $snapshotGhes[] = [
                'id' => $ghe->id,
                'nome' => $ghe->nome,
                'protocolo' => $ghe->protocolo ? [
                    'id' => $ghe->protocolo->id,
                    'titulo' => $ghe->protocolo->titulo,
                ] : null,
                'exames' => $exames->map(fn ($ex) => [
                    'id' => $ex->id,
                    'titulo' => $ex->titulo,
                    'preco' => (float) $ex->preco,
                ])->all(),
                'total_exames' => $totalExames,
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
}
