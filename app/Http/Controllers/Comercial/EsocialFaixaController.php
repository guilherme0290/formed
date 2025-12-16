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
        $hasOverlap = EsocialTabPreco::where('empresa_id', $empresaId)
            ->where(function ($q) use ($data) {
                $q->whereBetween('inicio', [$data['inicio'], $data['fim']])
                    ->orWhereBetween('fim', [$data['inicio'], $data['fim']])
                    ->orWhere(function ($q2) use ($data) {
                        $q2->where('inicio', '<=', $data['inicio'])
                            ->where('fim', '>=', $data['fim']);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            return back()->withErrors(['inicio' => 'Existe uma faixa que conflita com esse intervalo.']);
        }

        $data['empresa_id'] = $empresaId;
        $data['ativo'] = $data['ativo'] ?? true;

        EsocialTabPreco::create($data);

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

        // regra de sobreposição ignorando a própria faixa
        $hasOverlap = EsocialTabPreco::where('empresa_id', $faixa->empresa_id)
            ->where('id', '!=', $faixa->id)
            ->where(function ($q) use ($data) {
                $q->whereBetween('inicio', [$data['inicio'], $data['fim']])
                    ->orWhereBetween('fim', [$data['inicio'], $data['fim']])
                    ->orWhere(function ($q2) use ($data) {
                        $q2->where('inicio', '<=', $data['inicio'])
                            ->where('fim', '>=', $data['fim']);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            return back()->withErrors(['inicio' => 'Existe uma faixa que conflita com esse intervalo.']);
        }

        $faixa->update([
            'inicio' => $data['inicio'],
            'fim' => $data['fim'],
            'preco' => $data['preco'],
            'descricao' => $data['descricao'] ?? null,
            'ativo' => $data['ativo'] ?? false,
        ]);

        return back()->with('ok', 'Faixa do eSocial atualizada.');
    }

    public function destroy(EsocialTabPreco $faixa)
    {
        $this->authorizeFaixa($faixa);

        $faixa->delete();

        return back()->with('ok', 'Faixa removida.');
    }

    private function authorizeFaixa(EsocialTabPreco $faixa): void
    {
        abort_if($faixa->empresa_id !== auth()->user()->empresa_id, 403);
    }
}
