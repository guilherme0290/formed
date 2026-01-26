<?php

namespace App\Services;

use App\Models\ServicoTempo;
use Carbon\Carbon;

class TempoTarefaService
{
    public function calcularFimPrevisto(?Carbon $inicio, int $empresaId, ?int $servicoId): ?Carbon
    {
        if (!$inicio || !$servicoId) {
            return null;
        }

        $servicoExameId = (int) (config('services.exame_id') ?? 0);
        $servicoEsocialId = (int) (config('services.esocial_id') ?? 0);

        if ($servicoId === $servicoExameId || $servicoId === $servicoEsocialId) {
            return null;
        }

        $tempo = ServicoTempo::query()
            ->where('empresa_id', $empresaId)
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->first();

        $minutos = (int) ($tempo?->tempo_minutos ?? 0);
        if ($minutos <= 0) {
            return null;
        }

        return (clone $inicio)->addMinutes($minutos);
    }
}
