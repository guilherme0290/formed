<?php

namespace App\Services;

use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use App\Models\ClienteContratoLog;
use App\Models\Proposta;
use App\Models\PropostaItens;
use App\Models\User;
use App\Services\AsoGheService;
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
        $proposta->loadMissing('cliente');
        $proposta->loadMissing('itens.servico');

        if (strtoupper((string) $proposta->status) === 'FECHADA') {
            return $proposta;
        }

        if (!$proposta->cliente_id) {
            throw ValidationException::withMessages(['cliente_id' => 'Proposta sem cliente.']);
        }

        if ($proposta->itens->isEmpty()) {
            throw ValidationException::withMessages(['itens' => 'Proposta sem itens.']);
        }

        $asoGheService = app(AsoGheService::class);
        $gheSnapshot = $asoGheService->buildSnapshotForCliente($proposta->cliente_id, $proposta->empresa_id);
        $temGhe = !empty($gheSnapshot['ghes']);
        $isAsoItem = function (PropostaItens $item): bool {
            if (strtoupper((string) $item->tipo) === 'ASO_TIPO') {
                return true;
            }

            if (!empty($item->meta['aso_tipo'])) {
                return true;
            }

            $nomeBase = strtoupper((string) ($item->nome ?? $item->descricao ?? ''));
            return $nomeBase !== '' && str_contains($nomeBase, 'ASO');
        };

        foreach ($proposta->itens as $it) {
            if ($it->valor_unitario > 0) {
                continue;
            }

            if ($isAsoItem($it)) {
                continue;
            }

            throw ValidationException::withMessages(['itens' => 'Itens precisam de serviço e valor válido.']);
        }

        $hoje = Carbon::now()->startOfDay();

        return DB::transaction(function () use ($proposta, $hoje, $userId, $temGhe, $gheSnapshot, $isAsoItem) {
            $usuario = User::find($userId);
            $usuarioNome = $usuario?->name ?? 'Sistema';
            $clienteNome = $proposta->cliente?->razao_social ?? 'Cliente';

            $contrato = ClienteContrato::query()
                ->where('cliente_id', $proposta->cliente_id)
                ->where('empresa_id', $proposta->empresa_id)
                ->where('status', 'ATIVO')
                ->latest('id')
                ->first();

            if (!$contrato) {
                $contrato = ClienteContrato::create([
                    'empresa_id' => $proposta->empresa_id,
                    'cliente_id' => $proposta->cliente_id,
                    'vendedor_id' => $proposta->cliente?->vendedor_id ?: $userId,
                    'proposta_id_origem' => $proposta->id,
                    'status' => 'ATIVO',
                    'vigencia_inicio' => $hoje,
                    'vigencia_fim' => null,
                    'vencimento_servicos' => $proposta->vencimento_servicos,
                    'created_by' => $userId,
                ]);

                ClienteContratoLog::create([
                    'cliente_contrato_id' => $contrato->id,
                    'user_id' => $userId,
                    'acao' => 'CRIACAO',
                    'descricao' => sprintf(
                        'USUARIO: %s CRIOU o contrato da empresa %s a partir da proposta #%s.',
                        $usuarioNome,
                        $clienteNome,
                        $proposta->id
                    ),
                ]);
            } else {
                if (empty($contrato->vencimento_servicos) && $proposta->vencimento_servicos) {
                    $contrato->update(['vencimento_servicos' => $proposta->vencimento_servicos]);
                }
                ClienteContratoLog::create([
                    'cliente_contrato_id' => $contrato->id,
                    'user_id' => $userId,
                    'acao' => 'MERGE_PROPOSTA',
                    'descricao' => sprintf(
                        'USUARIO: %s MERGOU a proposta #%s no contrato da empresa %s.',
                        $usuarioNome,
                        $proposta->id,
                        $clienteNome
                    ),
                ]);
            }

            // Copia itens
            $asoSnapshot = null;
            if ($temGhe && $proposta->itens->contains(fn (PropostaItens $it) => $isAsoItem($it))) {
                $asoSnapshot = !empty($gheSnapshot['ghes']) ? $gheSnapshot : null;
            }

            foreach ($proposta->itens as $it) {
                $regrasSnapshot = null;
                $servicoId = $it->servico_id;
                if ($isAsoItem($it)) {
                    if (!$servicoId) {
                        $servicoId = (int) (config('services.aso_id') ?? 0);
                        if ($servicoId <= 0) {
                            throw ValidationException::withMessages([
                                'itens' => 'Serviço ASO não configurado. Defina FORMED_SERVICO_ASO_ID.',
                            ]);
                        }
                    }
                    $regrasSnapshot = $this->buildRegrasSnapshotAso($it, $asoSnapshot);
                }
                if (!$servicoId && strtoupper((string) $it->tipo) === 'ESOCIAL') {
                    $servicoId = (int) (config('services.esocial_id') ?? 0);
                    if ($servicoId <= 0) {
                        throw ValidationException::withMessages([
                            'itens' => 'ServiÃ§o eSocial nÃ£o configurado. Defina FORMED_SERVICO_ESOCIAL_ID.',
                        ]);
                    }
                }

                $descricaoSnapshot = $it->descricao ?? $it->nome;
                if ($isAsoItem($it) && !empty($it->meta['aso_tipo'])) {
                    $descricaoSnapshot = $it->nome ?? $it->descricao;
                }

                ClienteContratoItem::create([
                    'cliente_contrato_id' => $contrato->id,
                    'servico_id' => $servicoId,
                    'descricao_snapshot' => $descricaoSnapshot,
                    'preco_unitario_snapshot' => $it->valor_total ?? $it->valor_unitario,
                    'unidade_cobranca' => 'unidade',
                    'regras_snapshot' => $regrasSnapshot,
                    'ativo' => true,
                ]);

                $servicoNome = $it->nome ?? $it->descricao ?? 'Serviço';
                $valorNovo = (float) ($it->valor_total ?? $it->valor_unitario);

                ClienteContratoLog::create([
                    'cliente_contrato_id' => $contrato->id,
                    'user_id' => $userId,
                    'servico_id' => $it->servico_id,
                    'acao' => 'SERVICO_CRIADO',
                    'descricao' => sprintf(
                        'USUARIO: %s DEFINIU o serviço %s para a empresa %s. Valor: R$ %s.',
                        $usuarioNome,
                        $servicoNome,
                        $clienteNome,
                        number_format($valorNovo, 2, ',', '.')
                    ),
                    'valor_novo' => $valorNovo,
                ]);
            }

            $proposta->update([
                'status' => 'FECHADA',
                'pipeline_status' => 'FECHAMENTO',
                'pipeline_updated_at' => now(),
                'pipeline_updated_by' => $userId,
                'perdido_motivo' => null,
                'perdido_observacao' => null,
            ]);

            return $proposta->fresh(['itens']);
        });
    }

    private function buildRegrasSnapshotAso(PropostaItens $item, ?array $asoSnapshot): ?array
    {
        $meta = $item->meta ?? [];
        $asoTipo = $meta['aso_tipo'] ?? null;
        if ($asoTipo) {
            $snapshot = ['aso_tipo' => $asoTipo];
            if (!empty($meta['grupo_id'])) {
                $snapshot['grupo_id'] = (int) $meta['grupo_id'];
            }
            return $snapshot;
        }

        return $asoSnapshot;
    }
}
