<?php

namespace App\Http\Controllers;

use App\Models\Papel;
use App\Models\Permissao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PapelController extends Controller
{
    public function index()
    {
        $papeis = Papel::orderBy('nome')->get();
        return view('master.acessos.papeis-index', compact('papeis'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nome'      => ['required','string','max:255', Rule::unique('papeis','nome')],
            'descricao' => ['nullable','string','max:255'],
            'ativo'     => ['nullable','boolean'],
        ]);

        $data['ativo'] = (bool) ($data['ativo'] ?? true);

        Papel::create($data);

        return back()->with('ok', 'Papel criado.');
    }

    public function update(Request $r, Papel $papel)
    {
        $data = $r->validate([
            'nome'      => ['sometimes','required','string','max:255', Rule::unique('papeis','nome')->ignore($papel->id)],
            'descricao' => ['sometimes','nullable','string','max:255'],
            'ativo'     => ['sometimes','boolean'],
        ]);

        $papel->fill($data)->save();

        return back()->with('ok', 'Papel atualizado.');
    }

    public function destroy(Papel $papel)
    {
        $papel->delete();
        return back()->with('ok', 'Papel excluído.');
    }

    public function syncPermissoes(Request $r, Papel $papel)
    {
        $data = $r->validate([
            'permissoes'   => ['array'],
            'permissoes.*' => ['integer','exists:permissoes,id'],
        ]);

        $papel->permissoes()->sync($data['permissoes'] ?? []);

        return back()->with('ok', 'Permissões atualizadas para o papel '.$papel->nome.'.');
    }
}
