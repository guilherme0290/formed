<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ExamesTabPreco;
use App\Models\ProtocoloExame;
use App\Models\ProtocoloExameItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProtocolosExamesController extends Controller
{
    public function indexJson(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;
        $supportsClienteScope = $this->supportsClienteScope();
        $clienteId = (int) $request->query('cliente_id');
        $visao = (string) $request->query('visao', '');

        if ($supportsClienteScope && $clienteId > 0) {
            $this->authorizeCliente($empresaId, $clienteId);
        }

        $query = ProtocoloExame::query()
            ->where('empresa_id', $empresaId)
            ->with(['itens.exame:id,titulo,preco,ativo']);

        if ($supportsClienteScope) {
            if ($visao === 'all') {
                $query->orderByRaw('case when cliente_id is null then 0 else 1 end');
            } elseif (($visao === 'cliente' || ($visao === '' && $clienteId > 0)) && $clienteId > 0) {
                $query
                    ->where(function ($scope) use ($clienteId) {
                        $scope->whereNull('cliente_id')
                            ->orWhere('cliente_id', $clienteId);
                    })
                    ->orderByRaw('case when cliente_id is null then 0 else 1 end');
            } else {
                $query->whereNull('cliente_id');
            }
        }

        $protocolos = $query
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
                    'cliente_id' => $this->supportsClienteScope() && $p->cliente_id ? (int) $p->cliente_id : null,
                    'escopo' => $this->supportsClienteScope() && $p->cliente_id ? 'cliente' : 'generico',
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

    public function clientesJson(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $clientes = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('razao_social')
            ->get(['id', 'razao_social', 'nome_fantasia'])
            ->map(fn (Cliente $cliente) => [
                'id' => (int) $cliente->id,
                'nome' => (string) ($cliente->razao_social ?: $cliente->nome_fantasia ?: ('Cliente #' . $cliente->id)),
            ])
            ->values();

        return response()->json(['data' => $clientes]);
    }

    public function store(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;
        $supportsClienteScope = $this->supportsClienteScope();

        $data = $request->validate([
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['nullable', 'boolean'],
            'exames' => ['array'],
            'exames.*' => ['integer', 'exists:exames_tab_preco,id'],
        ]);

        $clienteId = $supportsClienteScope && !empty($data['cliente_id']) ? (int) $data['cliente_id'] : null;
        if ($clienteId) {
            $this->authorizeCliente($empresaId, $clienteId);
        }

        return DB::transaction(function () use ($data, $empresaId, $clienteId, $supportsClienteScope) {
            $validIds = $this->resolveValidExameIds($data['exames'] ?? [], $empresaId);
            $this->assertNoDuplicateExamGroup($validIds, $empresaId, $clienteId, null);

            $payload = [
                'empresa_id' => $empresaId,
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'] ?? null,
                'ativo' => $data['ativo'] ?? true,
            ];

            if ($supportsClienteScope) {
                $payload['cliente_id'] = $clienteId;
            }

            $protocolo = ProtocoloExame::create($payload);

            $this->syncExames($protocolo, $validIds);

            return response()->json(['data' => $protocolo->fresh()], 201);
        });
    }

    public function update(Request $request, ProtocoloExame $protocolo)
    {
        $this->authorizeEmpresa($protocolo);
        $empresaId = auth()->user()->empresa_id;
        $supportsClienteScope = $this->supportsClienteScope();

        $data = $request->validate([
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['nullable', 'boolean'],
            'exames' => ['array'],
            'exames.*' => ['integer', 'exists:exames_tab_preco,id'],
        ]);

        $clienteId = $supportsClienteScope && !empty($data['cliente_id']) ? (int) $data['cliente_id'] : null;
        if ($clienteId) {
            $this->authorizeCliente($empresaId, $clienteId);
        }

        return DB::transaction(function () use ($data, $protocolo, $clienteId, $empresaId, $supportsClienteScope) {
            $validIds = $this->resolveValidExameIds($data['exames'] ?? [], $empresaId);

            $currentIds = $protocolo->itens()
                ->pluck('exame_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            sort($currentIds);
            $nextIds = array_values(array_unique(array_map('intval', $validIds)));
            sort($nextIds);
            $scopeChanged = $supportsClienteScope
                && (int) ($protocolo->cliente_id ?? 0) !== (int) ($clienteId ?? 0);

            if ($currentIds !== $nextIds || $scopeChanged) {
                $this->assertNoDuplicateExamGroup($validIds, $empresaId, $clienteId, $protocolo->id);
            }

            $payload = [
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'] ?? null,
                'ativo' => $data['ativo'] ?? false,
            ];

            if ($supportsClienteScope) {
                $payload['cliente_id'] = $clienteId;
            }

            $protocolo->update($payload);

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
            ->with('itens:protocolo_id,exame_id');

        if ($this->supportsClienteScope()) {
            $query->where(function ($scope) use ($clienteId) {
                if ($clienteId) {
                    $scope->whereNull('cliente_id')
                        ->orWhere('cliente_id', $clienteId);
                    return;
                }

                $scope->whereNull('cliente_id');
            });
        }

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

    private function authorizeCliente(int $empresaId, int $clienteId): void
    {
        $ok = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->where('id', $clienteId)
            ->exists();

        abort_if(!$ok, 403);
    }

    private function authorizeEmpresa(ProtocoloExame $protocolo): void
    {
        abort_if($protocolo->empresa_id !== auth()->user()->empresa_id, 403);
    }

    private function supportsClienteScope(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = Schema::hasColumn('protocolos_exames', 'cliente_id');
        }

        return $supports;
    }
}
