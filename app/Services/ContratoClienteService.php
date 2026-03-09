<?php

namespace App\Services;

use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use Carbon\Carbon;
use Illuminate\Support\Str;

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

    public function findPcmsoItem(ClienteContrato $contrato, string $tipo = 'matriz'): ?ClienteContratoItem
    {
        $tipo = $tipo === 'especifico' ? 'especifico' : 'matriz';

        $itens = $contrato->relationLoaded('itens')
            ? $contrato->itens
            : $contrato->itens()->with('servico:id,nome')->get();

        return $itens
            ->filter(fn (ClienteContratoItem $item) => (bool) $item->ativo)
            ->map(function (ClienteContratoItem $item) use ($tipo) {
                $score = $this->scorePcmsoItem($item, $tipo);

                return [
                    'item' => $item,
                    'score' => $score,
                ];
            })
            ->filter(fn (array $entry) => $entry['score'] >= 0)
            ->sortByDesc('score')
            ->pluck('item')
            ->first();
    }

    private function scorePcmsoItem(ClienteContratoItem $item, string $tipo): int
    {
        $descricao = $this->normalizeText((string) ($item->descricao_snapshot ?? ''));
        $servicoNome = $this->normalizeText((string) ($item->servico?->nome ?? ''));
        $texto = trim($descricao . ' ' . $servicoNome);

        if ($texto === '' || !str_contains($texto, 'pcmso')) {
            return -1;
        }

        $isEspecifico = str_contains($texto, 'especific');
        if ($tipo === 'especifico' && !$isEspecifico) {
            return -1;
        }

        if ($tipo === 'matriz' && $isEspecifico) {
            return -1;
        }

        $score = 0;

        if ($tipo === 'especifico') {
            $score += 100;
            if ($descricao !== '' && str_contains($descricao, 'pcmso') && str_contains($descricao, 'especific')) {
                $score += 20;
            }
            if ($servicoNome !== '' && str_contains($servicoNome, 'pcmso') && str_contains($servicoNome, 'especific')) {
                $score += 10;
            }
        } else {
            $score += 50;
            if ($descricao !== '' && str_contains($descricao, 'pcmso')) {
                $score += 10;
            }
            if ($servicoNome === 'pcmso') {
                $score += 5;
            }
        }

        if ($descricao !== '') {
            $score += min(mb_strlen($descricao), 20);
        }

        return $score;
    }

    private function normalizeText(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->value();
    }
}
