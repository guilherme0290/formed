<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\MedicoesTabPreco;
use Illuminate\Http\Request;

class MedicoesTabPrecoController extends Controller
{
    public function indexJson()
    {
        $empresaId = auth()->user()->empresa_id;

        $medicoes = MedicoesTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('titulo')
            ->get(['id', 'titulo', 'descricao', 'preco', 'ativo']);

        return response()->json(['data' => $medicoes]);
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

        $medicao = MedicoesTabPreco::create($data);

        return response()->json(['data' => $medicao], 201);
    }

    public function update(Request $request, MedicoesTabPreco $medicao)
    {
        $this->authorizeEmpresa($medicao);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'preco' => ['required', 'numeric', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $medicao->update([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'preco' => $data['preco'],
            'ativo' => $data['ativo'] ?? false,
        ]);

        return response()->json(['data' => $medicao]);
    }

    public function destroy(MedicoesTabPreco $medicao)
    {
        $this->authorizeEmpresa($medicao);

        $medicao->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeEmpresa(MedicoesTabPreco $medicao): void
    {
        abort_if($medicao->empresa_id !== auth()->user()->empresa_id, 403);
    }
}
