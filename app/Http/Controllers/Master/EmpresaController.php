<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Empresa;
use App\Models\UnidadeClinica;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function edit(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        abort_if(!$empresaId, 404);

        $empresa = Empresa::with('cidade.estado')->findOrFail($empresaId);
        $estados = Estado::orderBy('uf')->get(['uf', 'nome']);

        $ufSelecionada = optional(optional($empresa->cidade)->estado)->uf;
        $cidadesDoEstado = collect();
        if ($ufSelecionada) {
            $estado = Estado::where('uf', $ufSelecionada)->first();
            $cidadesDoEstado = $estado
                ? $estado->cidades()->orderBy('nome')->get(['id', 'nome'])
                : collect();
        }

        $unidades = UnidadeClinica::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        return view('master.empresa.edit', compact(
            'empresa',
            'estados',
            'cidadesDoEstado',
            'ufSelecionada',
            'unidades'
        ));
    }

    public function update(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        abort_if(!$empresaId, 404);

        $empresa = Empresa::findOrFail($empresaId);

        $data = $request->validate([
            'nome'     => ['required', 'string', 'max:255'],
            'cnpj'     => ['nullable', 'string', 'max:18'],
            'email'    => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'cidade_id' => ['nullable', 'exists:cidades,id'],
            'ativo'    => ['nullable', 'boolean'],
        ]);

        $data['ativo'] = $request->boolean('ativo');

        $empresa->update($data);

        return redirect()
            ->route('master.empresa.edit')
            ->with('ok', 'Dados da empresa atualizados.');
    }
}
