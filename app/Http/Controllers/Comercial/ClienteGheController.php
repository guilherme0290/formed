<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteGhe;
use App\Models\ClienteGheFuncao;
use App\Models\Funcao;
use App\Models\ProtocoloExame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteGheController extends Controller
{
    public function indexJson(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;
        $clienteId = (int) $request->query('cliente_id');

        $cliente = Cliente::where('empresa_id', $empresaId)->findOrFail($clienteId);

        $ghes = ClienteGhe::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->with(['protocolo.itens.exame:id,titulo,preco,ativo', 'funcoes.funcao:id,nome'])
            ->orderBy('nome')
            ->get()
            ->map(function (ClienteGhe $ghe) {
                $exames = $ghe->protocolo?->itens
                    ->map(fn ($it) => $it->exame)
                    ->filter()
                    ->values() ?? collect();

                $totalExames = $exames->sum(fn ($ex) => (float) ($ex->preco ?? 0));

                return [
                    'id' => $ghe->id,
                    'nome' => $ghe->nome,
                    'ativo' => (bool) $ghe->ativo,
                    'protocolo' => $ghe->protocolo ? [
                        'id' => $ghe->protocolo->id,
                        'titulo' => $ghe->protocolo->titulo,
                        'descricao' => $ghe->protocolo->descricao,
                    ] : null,
                    'funcoes' => $ghe->funcoes->map(fn ($f) => [
                        'id' => $f->funcao_id,
                        'nome' => $f->funcao?->nome,
                    ])->filter(fn ($f) => !empty($f['id']))->values()->all(),
                    'base' => $this->formatBase($ghe),
                    'preco_fechado' => $this->formatFechados($ghe),
                    'exames' => $exames->map(fn ($ex) => [
                        'id' => $ex->id,
                        'titulo' => $ex->titulo,
                        'preco' => (float) $ex->preco,
                        'ativo' => (bool) $ex->ativo,
                    ])->all(),
                    'total_exames' => (float) $totalExames,
                ];
            });

        return response()->json(['data' => $ghes]);
    }

    public function store(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'nome' => ['required', 'string', 'max:255'],
            'protocolo_id' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'funcoes' => ['array'],
            'funcoes.*' => ['integer', 'exists:funcoes,id'],
            'base' => ['array'],
            'base.admissional' => ['nullable', 'numeric', 'min:0'],
            'base.periodico' => ['nullable', 'numeric', 'min:0'],
            'base.demissional' => ['nullable', 'numeric', 'min:0'],
            'base.mudanca_funcao' => ['nullable', 'numeric', 'min:0'],
            'base.retorno_trabalho' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado' => ['array'],
            'preco_fechado.admissional' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.periodico' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.demissional' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.mudanca_funcao' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.retorno_trabalho' => ['nullable', 'numeric', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $this->authorizeCliente($empresaId, (int) $data['cliente_id']);
        $this->authorizeProtocolo($empresaId, $data['protocolo_id'] ?? null);
        $this->authorizeFuncoes($empresaId, $data['funcoes'] ?? []);

        return DB::transaction(function () use ($data, $empresaId) {
            $ghe = ClienteGhe::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $data['cliente_id'],
                'nome' => $data['nome'],
                'protocolo_id' => $data['protocolo_id'] ?? null,
                'base_aso_admissional' => $data['base']['admissional'] ?? 0,
                'base_aso_periodico' => $data['base']['periodico'] ?? 0,
                'base_aso_demissional' => $data['base']['demissional'] ?? 0,
                'base_aso_mudanca_funcao' => $data['base']['mudanca_funcao'] ?? 0,
                'base_aso_retorno_trabalho' => $data['base']['retorno_trabalho'] ?? 0,
                'preco_fechado_admissional' => $data['preco_fechado']['admissional'] ?? null,
                'preco_fechado_periodico' => $data['preco_fechado']['periodico'] ?? null,
                'preco_fechado_demissional' => $data['preco_fechado']['demissional'] ?? null,
                'preco_fechado_mudanca_funcao' => $data['preco_fechado']['mudanca_funcao'] ?? null,
                'preco_fechado_retorno_trabalho' => $data['preco_fechado']['retorno_trabalho'] ?? null,
                'ativo' => $data['ativo'] ?? true,
            ]);

            $this->syncFuncoes($ghe, $data['funcoes'] ?? []);

            return response()->json(['data' => $ghe->fresh()], 201);
        });
    }

    public function update(Request $request, ClienteGhe $ghe)
    {
        $this->authorizeEmpresa($ghe);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'protocolo_id' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'funcoes' => ['array'],
            'funcoes.*' => ['integer', 'exists:funcoes,id'],
            'base' => ['array'],
            'base.admissional' => ['nullable', 'numeric', 'min:0'],
            'base.periodico' => ['nullable', 'numeric', 'min:0'],
            'base.demissional' => ['nullable', 'numeric', 'min:0'],
            'base.mudanca_funcao' => ['nullable', 'numeric', 'min:0'],
            'base.retorno_trabalho' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado' => ['array'],
            'preco_fechado.admissional' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.periodico' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.demissional' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.mudanca_funcao' => ['nullable', 'numeric', 'min:0'],
            'preco_fechado.retorno_trabalho' => ['nullable', 'numeric', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $this->authorizeProtocolo($ghe->empresa_id, $data['protocolo_id'] ?? null);
        $this->authorizeFuncoes($ghe->empresa_id, $data['funcoes'] ?? []);

        return DB::transaction(function () use ($data, $ghe) {
            $ghe->update([
                'nome' => $data['nome'],
                'protocolo_id' => $data['protocolo_id'] ?? null,
                'base_aso_admissional' => $data['base']['admissional'] ?? 0,
                'base_aso_periodico' => $data['base']['periodico'] ?? 0,
                'base_aso_demissional' => $data['base']['demissional'] ?? 0,
                'base_aso_mudanca_funcao' => $data['base']['mudanca_funcao'] ?? 0,
                'base_aso_retorno_trabalho' => $data['base']['retorno_trabalho'] ?? 0,
                'preco_fechado_admissional' => $data['preco_fechado']['admissional'] ?? null,
                'preco_fechado_periodico' => $data['preco_fechado']['periodico'] ?? null,
                'preco_fechado_demissional' => $data['preco_fechado']['demissional'] ?? null,
                'preco_fechado_mudanca_funcao' => $data['preco_fechado']['mudanca_funcao'] ?? null,
                'preco_fechado_retorno_trabalho' => $data['preco_fechado']['retorno_trabalho'] ?? null,
                'ativo' => $data['ativo'] ?? false,
            ]);

            $this->syncFuncoes($ghe, $data['funcoes'] ?? []);

            return response()->json(['data' => $ghe->fresh()]);
        });
    }

    public function destroy(ClienteGhe $ghe)
    {
        $this->authorizeEmpresa($ghe);

        $ghe->delete();

        return response()->json(['ok' => true]);
    }

    private function syncFuncoes(ClienteGhe $ghe, array $funcoesIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $funcoesIds)));

        ClienteGheFuncao::where('cliente_ghe_id', $ghe->id)
            ->whereNotIn('funcao_id', $ids)
            ->delete();

        $existing = ClienteGheFuncao::query()
            ->where('cliente_ghe_id', $ghe->id)
            ->pluck('funcao_id')
            ->all();

        $toInsert = array_diff($ids, $existing);
        foreach ($toInsert as $funcaoId) {
            ClienteGheFuncao::create([
                'cliente_ghe_id' => $ghe->id,
                'funcao_id' => $funcaoId,
            ]);
        }
    }

    private function authorizeEmpresa(ClienteGhe $ghe): void
    {
        abort_if($ghe->empresa_id !== auth()->user()->empresa_id, 403);
    }

    private function authorizeCliente(int $empresaId, int $clienteId): void
    {
        $ok = Cliente::where('empresa_id', $empresaId)
            ->where('id', $clienteId)
            ->exists();
        abort_if(!$ok, 403);
    }

    private function authorizeProtocolo(int $empresaId, ?int $protocoloId): void
    {
        if (!$protocoloId) {
            return;
        }
        $ok = ProtocoloExame::where('empresa_id', $empresaId)
            ->where('id', $protocoloId)
            ->exists();
        abort_if(!$ok, 403);
    }

    private function authorizeFuncoes(int $empresaId, array $funcoes): void
    {
        if (empty($funcoes)) {
            return;
        }
        $count = Funcao::where('empresa_id', $empresaId)
            ->whereIn('id', $funcoes)
            ->count();
        abort_if($count !== count($funcoes), 403);
    }

    private function formatBase(ClienteGhe $ghe): array
    {
        return [
            'admissional' => (float) $ghe->base_aso_admissional,
            'periodico' => (float) $ghe->base_aso_periodico,
            'demissional' => (float) $ghe->base_aso_demissional,
            'mudanca_funcao' => (float) $ghe->base_aso_mudanca_funcao,
            'retorno_trabalho' => (float) $ghe->base_aso_retorno_trabalho,
        ];
    }

    private function formatFechados(ClienteGhe $ghe): array
    {
        return [
            'admissional' => $ghe->preco_fechado_admissional !== null ? (float) $ghe->preco_fechado_admissional : null,
            'periodico' => $ghe->preco_fechado_periodico !== null ? (float) $ghe->preco_fechado_periodico : null,
            'demissional' => $ghe->preco_fechado_demissional !== null ? (float) $ghe->preco_fechado_demissional : null,
            'mudanca_funcao' => $ghe->preco_fechado_mudanca_funcao !== null ? (float) $ghe->preco_fechado_mudanca_funcao : null,
            'retorno_trabalho' => $ghe->preco_fechado_retorno_trabalho !== null ? (float) $ghe->preco_fechado_retorno_trabalho : null,
        ];
    }
}
