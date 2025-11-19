<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index()
    {
        $empresas = Empresa::latest()->paginate(15);
        return view('empresas.index', compact('empresas')); // <- empesas.*
    }

    public function create()
    {
        return view('empresas.create'); // <- empesas.*
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'     => 'required|string|max:255',
            'cnpj'     => 'nullable|string|max:18',
            'email'    => 'nullable|email',
            'telefone' => 'nullable|string|max:30',
            'ativo'    => 'nullable|boolean',
        ]);

        // garante boolean do checkbox
        $data['ativo'] = $request->boolean('ativo');

        Empresa::create($data);

        // suas rotas continuam master.empresas.* por causa do prefix('master')
        return redirect()->route('master.empresas.index')->with('ok', 'Empresa criada.');
    }

    public function show(Empresa $empresa)
    {
        return view('empresas.show', compact('empresa')); // <- empesas.*
    }

    public function edit(Empresa $empresa)
    {
        return view('empresas.edit', compact('empresa')); // <- empesas.*
    }

    public function update(Request $request, Empresa $empresa)
    {
        $data = $request->validate([
            'nome'     => 'required|string|max:255',
            'cnpj'     => 'nullable|string|max:18',
            'email'    => 'nullable|email',
            'telefone' => 'nullable|string|max:30',
            'ativo'    => 'nullable|boolean',
        ]);

        $data['ativo'] = $request->boolean('ativo');

        $empresa->update($data);

        return redirect()->route('master.empresas.index')->with('ok', 'Empresa atualizada.');
    }

    public function destroy(Empresa $empresa)
    {
        $empresa->delete();
        return back()->with('ok', 'Empresa removida.');
    }
}
