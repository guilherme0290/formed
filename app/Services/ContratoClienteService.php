<?php

namespace App\Services;

use App\Models\ClienteContrato;
use Carbon\Carbon;

class ContratoClienteService
{
    public function getContratoAtivo(int $clienteId, int $empresaId, ?Carbon $data = null): ?ClienteContrato
    {
        $dataRef = $data ? $data->copy()->startOfDay() : Carbon::now()->startOfDay();

        return ClienteContrato::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('status', 'ATIVO')
            ->where(function ($q) use ($dataRef) {
                $q->whereNull('vigencia_inicio')->orWhere('vigencia_inicio', '<=', $dataRef);
            })
            ->where(function ($q) use ($dataRef) {
                $q->whereNull('vigencia_fim')->orWhere('vigencia_fim', '>=', $dataRef);
            })
            ->latest('vigencia_inicio')
            ->first();
    }
}
