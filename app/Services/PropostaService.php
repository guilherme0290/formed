<?php

namespace App\Services;

use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use App\Models\Proposta;
use App\Models\PropostaItens;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropostaService
{
    /**
     * Fecha a proposta e gera contrato/tabela de preço do cliente (snapshot).
     */
    public function fechar(int $propostaId, int $userId): Proposta
    {
        $proposta = Proposta::with('itens')->findOrFail($propostaId);

        if (strtoupper((string) $proposta->status) === 'FECHADA') {
            return $proposta;
        }

        if (!$proposta->cliente_id) {
            throw ValidationException::withMessages(['cliente_id' => 'Proposta sem cliente.']);
        }

        if ($proposta->itens->isEmpty()) {
            throw ValidationException::withMessages(['itens' => 'Proposta sem itens.']);
        }

        foreach ($proposta->itens as $it) {
            if ($it->valor_unitario <= 0) {
                throw ValidationException::withMessages(['itens' => 'Itens precisam de serviço e valor válido.']);
            }
        }

        $hoje = Carbon::now()->startOfDay();

        return DB::transaction(function () use ($proposta, $hoje, $userId) {
            // Inativa contrato ativo anterior
            ClienteContrato::where('cliente_id', $proposta->cliente_id)
                ->where('empresa_id', $proposta->empresa_id)
                ->where('status', 'ATIVO')
                ->update([
                    'status' => 'SUBSTITUIDO',
                    'vigencia_fim' => $hoje->copy()->subDay(),
                ]);

            // Cria novo contrato
            $contrato = ClienteContrato::create([
                'empresa_id' => $proposta->empresa_id,
                'cliente_id' => $proposta->cliente_id,
                'proposta_id_origem' => $proposta->id,
                'status' => 'ATIVO',
                'vigencia_inicio' => $hoje,
                'vigencia_fim' => null,
                'created_by' => $userId,
            ]);

            // Copia itens
            foreach ($proposta->itens as $it) {
                ClienteContratoItem::create([
                    'cliente_contrato_id' => $contrato->id,
                    'servico_id' => $it->servico_id,
                    'descricao_snapshot' => $it->descricao ?? $it->nome,
                    'preco_unitario_snapshot' => $it->valor_total ?? $it->valor_unitario,
                    'unidade_cobranca' => 'unidade',
                    'regras_snapshot' => null,
                    'ativo' => true,
                ]);
            }

            $proposta->update(['status' => 'FECHADA']);

            return $proposta->fresh(['itens']);
        });
    }
}
