<?php

namespace App\Services;

use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PrecificacaoService
{
    public function __construct(private readonly ContratoClienteService $contratoClienteService)
    {
    }

    /**
     * Valida se existe contrato ativo e item de serviço para o cliente na data.
     *
     * @return array{contrato: ClienteContrato, item: ClienteContratoItem}
     */
    public function validarServicoNoContrato(int $clienteId, int $servicoId, int $empresaId, ?Carbon $dataRef = null): array
    {
        $contrato = $this->contratoClienteService->getContratoAtivo($clienteId, $empresaId, $dataRef);

        if (!$contrato) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui contrato ativo.',
            ]);
        }

        $item = $contrato->itens()
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->first();

        if (!$item) {
            throw ValidationException::withMessages([
                'contrato' => 'Não é possível concluir esta tarefa porque o cliente não possui preço definido para este serviço na proposta/contrato ativo. Solicite ao Comercial para ajustar a proposta e fechar novamente, ou cadastrar o valor do serviço no contrato do cliente.',
            ]);
        }

        return ['contrato' => $contrato, 'item' => $item];
    }
}
