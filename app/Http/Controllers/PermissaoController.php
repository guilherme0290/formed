<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permissao;

class PermissaoController extends Controller
{
    public function index()
    {
        $permissoes = Permissao::orderBy('chave')->get();
        return view('master.acessos.permissoes-index', [
            'permissoes' => \App\Models\Permissao::orderBy('chave')->get()
        ]);
    }

    public function store(Request $r) {
        $data = $r->validate([
            'chave' => 'required|string|unique:permissoes,chave',
            'nome'  => 'required|string',
            'escopo'=> 'nullable|string'
        ]);
        \App\Models\Permissao::create($data);
        return back()->with('ok','Permissão criada');
    }

    public function update(Request $r, \App\Models\Permissao $permissao) {
        $data = $r->validate([
            'chave' => "required|string|unique:permissoes,chave,{$permissao->id}",
            'nome'  => 'required|string',
            'escopo'=> 'nullable|string'
        ]);
        $permissao->update($data);
        return back()->with('ok','Permissão atualizada');
    }

    public function destroy(Permissao $permissao)
    {
        $permissao->delete();
        return back()->with('ok','Permissão removida.');
    }
}
