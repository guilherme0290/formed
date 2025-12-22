<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\UnidadeClinica;
use Illuminate\Http\Request;

class UnidadeClinicaController extends Controller
{
    public function create(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        abort_if(!$empresaId, 404);

        $unidade = new UnidadeClinica();

        return view('master.empresa.unidades-form', [
            'unidade' => $unidade,
            'isEdit' => false,
        ]);
    }

    public function edit(Request $request, UnidadeClinica $unidade)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        abort_unless($unidade->empresa_id === $empresaId, 403);

        return view('master.empresa.unidades-form', [
            'unidade' => $unidade,
            'isEdit' => true,
        ]);
    }

    public function store(Request $request)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        abort_if(!$empresaId, 404);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $data['empresa_id'] = $empresaId;
        $data['ativo'] = $request->boolean('ativo');

        UnidadeClinica::create($data);

        return redirect()
            ->route('master.empresa.edit', ['tab' => 'unidades'])
            ->with('ok', 'Unidade clínica cadastrada.');
    }

    public function update(Request $request, UnidadeClinica $unidade)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        abort_unless($unidade->empresa_id === $empresaId, 403);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $data['ativo'] = $request->boolean('ativo');

        $unidade->update($data);

        return redirect()
            ->route('master.empresa.edit', ['tab' => 'unidades'])
            ->with('ok', 'Unidade clínica atualizada.');
    }

    public function destroy(Request $request, UnidadeClinica $unidade)
    {
        $empresaId = $request->user()->empresa_id ?? null;
        abort_unless($unidade->empresa_id === $empresaId, 403);

        $unidade->delete();

        return redirect()
            ->route('master.empresa.edit', ['tab' => 'unidades'])
            ->with('ok', 'Unidade clínica removida.');
    }
}
