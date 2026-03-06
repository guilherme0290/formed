<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ExamesTabPreco;
use App\Models\ProtocoloExame;
use App\Models\ProtocoloExameItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProtocolosExamesController extends Controller
{
    public function indexJson()
    {
        $empresaId = auth()->user()->empresa_id;

        $protocolos = ProtocoloExame::query()
            ->where('empresa_id', $empresaId)
            ->with(['itens.exame:id,titulo,preco,ativo'])
            ->orderBy('titulo')
            ->get()
            ->map(function (ProtocoloExame $p) {
                $exames = $p->itens
                    ->map(fn ($it) => $it->exame)
                    ->filter()
                    ->values();

                $total = $exames->sum(fn ($ex) => (float) ($ex->preco ?? 0));

                return [
                    'id' => $p->id,
                    'titulo' => $p->titulo,
                    'descricao' => $p->descricao,
                    'ativo' => (bool) $p->ativo,
                    'exames' => $exames->map(fn ($ex) => [
                        'id' => $ex->id,
                        'titulo' => $ex->titulo,
                        'preco' => (float) $ex->preco,
                        'ativo' => (bool) $ex->ativo,
                    ])->all(),
                    'total' => (float) $total,
                ];
            });

        return response()->json(['data' => $protocolos]);
    }

    public function store(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['nullable', 'boolean'],
            'exames' => ['array'],
            'exames.*' => ['integer', 'exists:exames_tab_preco,id'],
        ]);

        return DB::transaction(function () use ($data, $empresaId) {
            $validIds = $this->resolveValidExameIds($data['exames'] ?? [], $empresaId);
            $this->assertNoExamOverlap($validIds, $empresaId, null);

            $protocolo = ProtocoloExame::create([
                'empresa_id' => $empresaId,
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'] ?? null,
                'ativo' => $data['ativo'] ?? true,
            ]);

            $this->syncExames($protocolo, $validIds);

            return response()->json(['data' => $protocolo->fresh()], 201);
        });
    }

    public function update(Request $request, ProtocoloExame $protocolo)
    {
        $this->authorizeEmpresa($protocolo);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['nullable', 'boolean'],
            'exames' => ['array'],
            'exames.*' => ['integer', 'exists:exames_tab_preco,id'],
        ]);

        return DB::transaction(function () use ($data, $protocolo) {
            $empresaId = auth()->user()->empresa_id;
            $validIds = $this->resolveValidExameIds($data['exames'] ?? [], $empresaId);
            $this->assertNoExamOverlap($validIds, $empresaId, $protocolo->id);

            $protocolo->update([
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'] ?? null,
                'ativo' => $data['ativo'] ?? false,
            ]);

            $this->syncExames($protocolo, $validIds);

            return response()->json(['data' => $protocolo->fresh()]);
        });
    }

    public function destroy(ProtocoloExame $protocolo)
    {
        $this->authorizeEmpresa($protocolo);

        $protocolo->delete();

        return response()->json(['ok' => true]);
    }

    private function syncExames(ProtocoloExame $protocolo, array $validIds): void
    {
        ProtocoloExameItem::where('protocolo_id', $protocolo->id)
            ->whereNotIn('exame_id', $validIds)
            ->delete();

        $existing = ProtocoloExameItem::query()
            ->where('protocolo_id', $protocolo->id)
            ->pluck('exame_id')
            ->all();

        $toInsert = array_diff($validIds, $existing);
        foreach ($toInsert as $exameId) {
            ProtocoloExameItem::create([
                'protocolo_id' => $protocolo->id,
                'exame_id' => $exameId,
            ]);
        }
    }

    private function resolveValidExameIds(array $examesIds, int $empresaId): array
    {
        $ids = array_values(array_unique(array_map('intval', $examesIds)));

        if (!empty($ids)) {
            return ExamesTabPreco::query()
                ->where('empresa_id', $empresaId)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->all();
        }

        return [];
    }

    private function assertNoExamOverlap(array $exameIds, int $empresaId, ?int $currentProtocoloId): void
    {
        if (empty($exameIds)) {
            return;
        }

        $query = ProtocoloExameItem::query()
            ->select('exames_tab_preco.titulo')
            ->join('protocolos_exames', 'protocolos_exames.id', '=', 'protocolo_exame_itens.protocolo_id')
            ->join('exames_tab_preco', 'exames_tab_preco.id', '=', 'protocolo_exame_itens.exame_id')
            ->where('protocolos_exames.empresa_id', $empresaId)
            ->whereIn('protocolo_exame_itens.exame_id', $exameIds);

        if ($currentProtocoloId) {
            $query->where('protocolo_exame_itens.protocolo_id', '!=', $currentProtocoloId);
        }

        $duplicados = $query
            ->distinct()
            ->pluck('exames_tab_preco.titulo')
            ->filter()
            ->values()
            ->all();

        if (!empty($duplicados)) {
            $lista = implode(', ', array_slice($duplicados, 0, 3));
            throw ValidationException::withMessages([
                'exames' => "Não é permitido repetir exame em outro grupo. Já vinculado: {$lista}.",
            ]);
        }
    }

    private function authorizeEmpresa(ProtocoloExame $protocolo): void
    {
        abort_if($protocolo->empresa_id !== auth()->user()->empresa_id, 403);
    }
}
