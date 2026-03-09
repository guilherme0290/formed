<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
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
        $clienteId = (int) request()->query('cliente_id');

        $protocolos = ProtocoloExame::query()
            ->where('empresa_id', $empresaId)
            ->when($clienteId > 0, function ($query) use ($clienteId) {
                $query->where(function ($subQuery) use ($clienteId) {
                    $subQuery->whereNull('cliente_id')
                        ->orWhere('cliente_id', $clienteId);
                });
            }, function ($query) {
                $query->whereNull('cliente_id');
            })
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
                    'cliente_id' => $p->cliente_id ? (int) $p->cliente_id : null,
                    'escopo' => $p->cliente_id ? 'cliente' : 'generico',
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
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'exames' => ['array'],
            'exames.*' => ['integer', 'exists:exames_tab_preco,id'],
        ]);

        $clienteId = $this->resolveClienteId($empresaId, $data['cliente_id'] ?? null);

        return DB::transaction(function () use ($data, $empresaId, $clienteId) {
            $validIds = $this->resolveValidExameIds($data['exames'] ?? [], $empresaId);
            $this->assertNoDuplicateExamGroup($validIds, $empresaId, $clienteId, null);

            $protocolo = ProtocoloExame::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $clienteId,
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
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'exames' => ['array'],
            'exames.*' => ['integer', 'exists:exames_tab_preco,id'],
        ]);

        return DB::transaction(function () use ($data, $protocolo) {
            $empresaId = auth()->user()->empresa_id;
            $clienteId = $this->resolveClienteId($empresaId, $data['cliente_id'] ?? null);
            $validIds = $this->resolveValidExameIds($data['exames'] ?? [], $empresaId);
            $this->assertNoDuplicateExamGroup($validIds, $empresaId, $clienteId, $protocolo->id);

            $protocolo->update([
                'cliente_id' => $clienteId,
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

    private function assertNoDuplicateExamGroup(array $exameIds, int $empresaId, ?int $clienteId, ?int $currentProtocoloId): void
    {
        if (empty($exameIds)) {
            return;
        }

        $alvo = array_values(array_unique(array_map('intval', $exameIds)));
        sort($alvo);

        $query = ProtocoloExame::query()
            ->where('empresa_id', $empresaId)
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId), fn ($q) => $q->whereNull('cliente_id'))
            ->with('itens:protocolo_id,exame_id');

        if ($currentProtocoloId) {
            $query->where('id', '!=', $currentProtocoloId);
        }

        $grupoDuplicado = $query
            ->get(['id', 'titulo'])
            ->first(function (ProtocoloExame $protocolo) use ($alvo) {
                $ids = $protocolo->itens
                    ->pluck('exame_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                sort($ids);
                return $ids === $alvo;
            });

        if ($grupoDuplicado) {
            throw ValidationException::withMessages([
                'exames' => "Já existe um grupo com a mesma combinação de exames: {$grupoDuplicado->titulo}.",
            ]);
        }
    }

    private function resolveClienteId(int $empresaId, mixed $clienteId): ?int
    {
        $clienteId = (int) $clienteId;
        if ($clienteId <= 0) {
            return null;
        }

        $ok = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->where('id', $clienteId)
            ->exists();

        abort_if(!$ok, 403);

        return $clienteId;
    }

    private function authorizeEmpresa(ProtocoloExame $protocolo): void
    {
        abort_if($protocolo->empresa_id !== auth()->user()->empresa_id, 403);
    }
}
