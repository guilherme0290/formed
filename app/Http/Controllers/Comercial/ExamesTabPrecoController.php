<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ExamesTabPreco;
use Illuminate\Http\Request;

class ExamesTabPrecoController extends Controller
{
    public function indexJson()
    {
        $empresaId = auth()->user()->empresa_id;

        $exames = ExamesTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('titulo')
            ->get(['id', 'titulo', 'descricao', 'preco', 'ativo']);

        return response()->json(['data' => $exames]);
    }

    public function store(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'preco' => ['required', 'numeric', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $data['empresa_id'] = $empresaId;
        $data['ativo'] = $data['ativo'] ?? true;


        $exame = ExamesTabPreco::create($data);

        return response()->json(['data' => $exame], 201);
    }

    public function update(Request $request, ExamesTabPreco $exame)
    {
        $this->authorizeEmpresa($exame);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'preco' => ['required', 'numeric', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $exame->update([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'preco' => $data['preco'],
            'ativo' => $data['ativo'] ?? false,
        ]);

        return response()->json(['data' => $exame]);
    }

    public function destroy(ExamesTabPreco $exame)
    {
        $this->authorizeEmpresa($exame);

        $exame->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeEmpresa(ExamesTabPreco $exame): void
    {
        abort_if($exame->empresa_id !== auth()->user()->empresa_id, 403);
    }
}
