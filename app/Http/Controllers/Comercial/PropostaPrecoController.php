<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ClienteTabelaPreco;
use App\Models\ClienteTabelaPrecoItem;
use App\Models\EsocialTabPreco;
use App\Models\Servico;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use Illuminate\Http\Request;

class PropostaPrecoController extends Controller
{
    private function tabelaAtiva(int $empresaId): TabelaPrecoPadrao
    {
        return TabelaPrecoPadrao::where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->firstOrFail();
    }

    private function tabelaClienteAtiva(int $empresaId, int $clienteId): ?ClienteTabelaPreco
    {
        if ($clienteId <= 0) {
            return null;
        }

        return ClienteTabelaPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('ativa', true)
            ->first();
    }

    private function treinamentoServicoId(int $empresaId): ?int
    {
        $id = (int) (config('services.treinamento_id') ?? 0);
        if ($id > 0) {
            return $id;
        }

        // Fallback "feijão com arroz": tenta achar o serviço de Treinamento no cadastro.
        return Servico::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where(function ($q) {
                $q->whereRaw('LOWER(tipo) = ?', ['treinamento'])
                    ->orWhereRaw('LOWER(nome) like ?', ['%treinamento%']);
            })
            ->orderBy('id')
            ->value('id');
    }

    public function precoServico(Request $request, Servico $servico)
    {
        $empresaId = auth()->user()->empresa_id;
        abort_if($servico->empresa_id !== $empresaId, 403);

        $clienteId = (int) $request->query('cliente_id', 0);

        $origem = 'padrao';
        $itemId = null;
        $codigo = null;
        $descricao = null;
        $preco = 0.0;

        $tabelaCliente = $this->tabelaClienteAtiva($empresaId, $clienteId);
        if ($tabelaCliente) {
            $clienteItem = ClienteTabelaPrecoItem::query()
                ->where('cliente_tabela_preco_id', $tabelaCliente->id)
                ->where('servico_id', $servico->id)
                ->where('tipo', 'SERVICO')
                ->where('ativo', true)
                ->orderBy('descricao')
                ->first();

            if ($clienteItem) {
                $origem = 'cliente';
                $itemId = $clienteItem->id;
                $codigo = $clienteItem->codigo;
                $descricao = $clienteItem->descricao;
                $preco = (float) $clienteItem->valor_unitario;
            }
        }

        if ($origem === 'padrao') {
            $padrao = $this->tabelaAtiva($empresaId);

            $item = TabelaPrecoItem::query()
                ->where('tabela_preco_padrao_id', $padrao->id)
                ->where('servico_id', $servico->id)
                ->where('ativo', true)
                ->orderBy('descricao')
                ->first();

            $preco = (float) ($item?->preco ?? 0);
            $itemId = $item?->id;
            $codigo = $item?->codigo;
            $descricao = $item?->descricao;
        }

        return response()->json([
            'data' => [
                'servico_id' => $servico->id,
                'servico_nome' => $servico->nome,
                'preco' => $preco,
                'origem' => $origem,
                'tabela_item_id' => $itemId,
                'codigo' => $codigo,
                'descricao' => $descricao,
            ]
        ]);
    }

    public function precoTreinamento(Request $request, string $codigo)
    {
        $empresaId = auth()->user()->empresa_id;
        $clienteId = (int) $request->query('cliente_id', 0);

        $treinamentoId = $this->treinamentoServicoId($empresaId);
        abort_if(!$treinamentoId, 422, 'Serviço de treinamento não configurado.');

        $origem = 'padrao';
        $itemId = null;
        $descricao = null;
        $preco = 0.0;

        $tabelaCliente = $this->tabelaClienteAtiva($empresaId, $clienteId);
        if ($tabelaCliente) {
            $clienteItem = ClienteTabelaPrecoItem::query()
                ->where('cliente_tabela_preco_id', $tabelaCliente->id)
                ->where('servico_id', $treinamentoId)
                ->where('codigo', $codigo)
                ->where('ativo', true)
                ->orderBy('descricao')
                ->first();

            if ($clienteItem) {
                $origem = 'cliente';
                $itemId = $clienteItem->id;
                $descricao = $clienteItem->descricao;
                $preco = (float) $clienteItem->valor_unitario;
            }
        }

        if ($origem === 'padrao') {
            $padrao = $this->tabelaAtiva($empresaId);

            $item = TabelaPrecoItem::query()
                ->where('tabela_preco_padrao_id', $padrao->id)
                ->where('servico_id', $treinamentoId)
                ->where('codigo', $codigo)
                ->where('ativo', true)
                ->orderBy('descricao')
                ->first();

            $preco = (float) ($item?->preco ?? 0);
            $itemId = $item?->id;
            $descricao = $item?->descricao;
        }

        return response()->json([
            'data' => [
                'codigo' => $codigo,
                'preco' => $preco,
                'origem' => $origem,
                'tabela_item_id' => $itemId,
                'descricao' => $descricao,
            ]
        ]);
    }

    public function esocialPreco(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;
        $qtd = (int)$request->query('qtd', 0);

        if ($qtd <= 0) {
            return response()->json(['data' => ['qtd' => $qtd, 'preco' => 0, 'faixa' => null]]);
        }

        $padrao = $this->tabelaAtiva($empresaId);

        $faixa = EsocialTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where('inicio', '<=', $qtd)
            ->where(function ($q) use ($qtd) {
                $q->whereNull('fim')
                    ->orWhere('fim', '>=', $qtd);
            })
            // se houver faixa vinculada à tabela ativa, prioriza ela; senão usa faixa "global" (null)
            ->where(function ($q) use ($padrao) {
                $q->where('tabela_preco_padrao_id', $padrao->id)
                    ->orWhereNull('tabela_preco_padrao_id');
            })
            ->orderByRaw('CASE WHEN tabela_preco_padrao_id = ? THEN 0 ELSE 1 END', [$padrao->id])
            ->orderByDesc('inicio')
            ->orderByRaw('COALESCE(fim, 999999999) ASC')
            ->first();

        return response()->json([
            'data' => [
                'qtd' => $qtd,
                'preco' => (float)($faixa?->preco ?? 0),
                'faixa' => $faixa ? [
                    'id' => $faixa->id,
                    'inicio' => $faixa->inicio,
                    'fim' => $faixa->fim,
                    'descricao' => $faixa->descricao,
                ] : null,
                'aviso' => $faixa ? null : 'Acima do limite – contatar comercial',
            ]
        ]);
    }

    public function treinamentosJson()
    {
        $empresaId = auth()->user()->empresa_id;

        $treinamentoId = $this->treinamentoServicoId($empresaId);
        if (!$treinamentoId) {
            return response()->json(['data' => []]);
        }

        $padrao = $this->tabelaAtiva($empresaId);

        $rows = TabelaPrecoItem::query()
            ->where('tabela_preco_padrao_id', $padrao->id)
            ->where('servico_id', $treinamentoId)
            ->where('ativo', true)
            ->whereNotNull('codigo')
            ->orderBy('codigo')
            ->selectRaw('id, codigo, descricao as titulo')
            ->get();

        return response()->json(['data' => $rows]);
    }
}
