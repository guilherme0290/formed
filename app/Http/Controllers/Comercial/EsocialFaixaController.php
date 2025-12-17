<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\EsocialTabPreco;
use Illuminate\Http\Request;

class EsocialFaixaController extends Controller
{
    public function index()
    {
        // Se você quiser uma tela própria pro popup do eSocial (ou partial)
        return view('comercial.tabela-precos.esocial.faixas');
    }

    public function indexJson(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $faixas = EsocialTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('inicio')
            ->orderBy('fim')
            ->get(['id', 'inicio', 'fim', 'preco', 'descricao', 'ativo']);

        return response()->json(['data' => $faixas]);
    }

    public function store(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'inicio' => ['required', 'integer', 'min:1'],
            'fim' => ['required', 'integer', 'gte:inicio'],
            'preco' => ['required', 'numeric', 'min:0'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        // regra: não permitir sobreposição (recomendado)
        $hasOverlap = $this->hasOverlap(
            empresaId: $empresaId,
            inicio: $data['inicio'],
            fim: $data['fim'],
        );

        if ($hasOverlap) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Existe uma faixa que conflita com esse intervalo.',
                    'errors' => ['inicio' => ['Existe uma faixa que conflita com esse intervalo.']],
                ], 422);
            }

            return back()->withErrors(['inicio' => 'Existe uma faixa que conflita com esse intervalo.']);
        }

        $data['empresa_id'] = $empresaId;
        $data['ativo'] = $data['ativo'] ?? true;

        $faixa = EsocialTabPreco::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Faixa do eSocial criada com sucesso.',
                'data' => $faixa->only(['id', 'inicio', 'fim', 'preco', 'descricao', 'ativo']),
            ], 201);
        }

        return back()->with('ok', 'Faixa do eSocial criada com sucesso.');
    }

    public function update(Request $request, EsocialTabPreco $faixa)
    {
        $this->authorizeFaixa($faixa);

        $data = $request->validate([
            'inicio' => ['required', 'integer', 'min:1'],
            'fim' => ['required', 'integer', 'gte:inicio'],
            'preco' => ['required', 'numeric', 'min:0'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $rangeChanged = ((int) $faixa->inicio !== (int) $data['inicio'])
            || ((int) $faixa->fim !== (int) $data['fim']);

        // Regra de sobreposição só precisa rodar quando o range mudar.
        // Isso evita "prender" o usuário caso existam dados legados sobrepostos
        // e ele queira apenas ajustar preço/descrição/status.
        $hasOverlap = $rangeChanged
            ? $this->hasOverlap(
                empresaId: (int) $faixa->empresa_id,
                inicio: $data['inicio'],
                fim: $data['fim'],
                ignoreId: (int) $faixa->id,
            )
            : false;

        if ($hasOverlap) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Existe uma faixa que conflita com esse intervalo.',
                    'errors' => ['inicio' => ['Existe uma faixa que conflita com esse intervalo.']],
                ], 422);
            }

            return back()->withErrors(['inicio' => 'Existe uma faixa que conflita com esse intervalo.']);
        }

        $faixa->update([
            'inicio' => $data['inicio'],
            'fim' => $data['fim'],
            'preco' => $data['preco'],
            'descricao' => $data['descricao'] ?? null,
            'ativo' => $data['ativo'] ?? false,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Faixa do eSocial atualizada.',
                'data' => $faixa->fresh()->only(['id', 'inicio', 'fim', 'preco', 'descricao', 'ativo']),
            ]);
        }

        return back()->with('ok', 'Faixa do eSocial atualizada.');
    }

    public function destroy(EsocialTabPreco $faixa)
    {
        $this->authorizeFaixa($faixa);

        $faixa->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Faixa removida.']);
        }

        return back()->with('ok', 'Faixa removida.');
    }

    private function authorizeFaixa(EsocialTabPreco $faixa): void
    {
        abort_if($faixa->empresa_id !== auth()->user()->empresa_id, 403);
    }

    private function hasOverlap(int $empresaId, int $inicio, int $fim, ?int $ignoreId = null): bool
    {
        $query = EsocialTabPreco::query()
            ->where('empresa_id', $empresaId);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        // Overlap (range inclusivo):
        // existente.inicio <= novoFim && (existente.fim é nulo/aberto || existente.fim >= novoInicio)
        return $query
            ->where('inicio', '<=', $fim)
            ->where(function ($q) use ($inicio) {
                $q->whereNull('fim')
                    ->orWhere('fim', '>=', $inicio);
            })
            ->exists();
    }
}
