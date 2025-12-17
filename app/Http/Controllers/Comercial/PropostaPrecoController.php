<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\EsocialTabPreco;
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

    public function precoServico(Servico $servico)
    {
        $empresaId = auth()->user()->empresa_id;
        abort_if($servico->empresa_id !== $empresaId, 403);

        $padrao = $this->tabelaAtiva($empresaId);

        $item = TabelaPrecoItem::query()
            ->where('tabela_preco_padrao_id', $padrao->id)
            ->where('servico_id', $servico->id)
            ->where('ativo', true)
            ->orderBy('descricao')
            ->first();

        return response()->json([
            'data' => [
                'servico_id' => $servico->id,
                'servico_nome' => $servico->nome,
                'preco' => (float)($item?->preco ?? 0),
                'tabela_item_id' => $item?->id,
                'codigo' => $item?->codigo,
                'descricao' => $item?->descricao,
            ]
        ]);
    }

    public function precoTreinamento(string $codigo)
    {
        $empresaId = auth()->user()->empresa_id;
        $padrao = $this->tabelaAtiva($empresaId);

        $treinamentoId = config('services.treinamento_id');
        abort_if(!$treinamentoId, 422);

        $item = TabelaPrecoItem::query()
            ->where('tabela_preco_padrao_id', $padrao->id)
            ->where('servico_id', $treinamentoId)
            ->where('codigo', $codigo)
            ->where('ativo', true)
            ->orderBy('descricao')
            ->first();

        return response()->json([
            'data' => [
                'codigo' => $codigo,
                'preco' => (float)($item?->preco ?? 0),
                'tabela_item_id' => $item?->id,
                'descricao' => $item?->descricao,
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

        $faixa = EsocialTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where('inicio', '<=', $qtd)
            ->where('fim', '>=', $qtd)
            ->orderBy('inicio')
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
        // Se você já tem endpoint pronto, pode reaproveitar.
        // Aqui fica compatível com seu protótipo e com o que você já listou na tabela.
        $empresaId = auth()->user()->empresa_id;

        $rows = \DB::table('treinamentos_nrs')
            ->where('empresa_id', $empresaId)
            ->where('ativo', 1)
            ->orderBy('ordem')
            ->get(['id', 'codigo', 'titulo']);

        return response()->json(['data' => $rows]);
    }
}
