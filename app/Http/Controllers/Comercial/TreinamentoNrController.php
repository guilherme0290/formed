<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\TreinamentoNrsTabPreco;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TreinamentoNrController extends Controller
{
    public function indexJson(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $query = TreinamentoNrsTabPreco::query()
            ->where('empresa_id', $empresaId);

        // opcional: filtrar só ativos
        if ($request->boolean('somente_ativos', true)) {
            $query->where('ativo', true);
        }

        // opcional: busca
        if ($term = trim((string)$request->get('q'))) {
            $query->where(function ($q2) use ($term) {
                $q2->where('codigo', 'like', "%{$term}%")
                    ->orWhere('titulo', 'like', "%{$term}%");
            });
        }

        $nrs = $query
            ->orderByRaw("CAST(REPLACE(REPLACE(codigo,'NR',''),'-','') AS UNSIGNED) asc")
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'titulo', 'ativo']);

        return response()->json([
            'data' => $nrs,
        ]);
    }

    public function store(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('treinamento_nrs_tab_preco', 'codigo')->where('empresa_id', $empresaId),
            ],
            'titulo' => ['required', 'string', 'max:255'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $nr = TreinamentoNrsTabPreco::create([
            'empresa_id' => $empresaId,
            'codigo' => strtoupper(trim($data['codigo'])),
            'titulo' => trim($data['titulo']),
            'ordem' => $data['ordem'] ?? 0,
            'ativo' => (bool)($data['ativo'] ?? true),
        ]);

        return response()->json(['data' => $nr], 201);
    }

    public function update(Request $request, TreinamentoNrsTabPreco $nr)
    {
        $empresaId = auth()->user()->empresa_id;
        abort_unless($nr->empresa_id == $empresaId, 403);

        $data = $request->validate([
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('treinamento_nrs_tab_preco', 'codigo')
                    ->where('empresa_id', $empresaId)
                    ->ignore($nr->id),
            ],
            'titulo' => ['required', 'string', 'max:255'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $nr->update([
            'codigo' => strtoupper(trim($data['codigo'])),
            'titulo' => trim($data['titulo']),
            'ordem' => $data['ordem'] ?? 0,
            'ativo' => (bool)($data['ativo'] ?? false),
        ]);

        return response()->json(['data' => $nr]);
    }

    public function destroy(TreinamentoNrsTabPreco $nr)
    {
        $empresaId = auth()->user()->empresa_id;
        abort_unless($nr->empresa_id == $empresaId, 403);

        $nr->delete();

        return response()->json(['data' => true]);
    }
}
