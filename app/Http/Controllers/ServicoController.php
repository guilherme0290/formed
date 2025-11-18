<?php // app/Http/Controllers/ServicoController.php
namespace App\Http\Controllers;

use App\Models\Servico;
use Illuminate\Http\Request;

class ServicoController extends Controller {
    public function index(){ $servicos = Servico::orderBy('nome')->paginate(20); return view('master.precos.servicos-index', compact('servicos')); }
    public function store(Request $r){
        $data = $r->validate(['nome'=>'required','descricao'=>'nullable','ativo'=>'boolean']);
        Servico::create($data + ['ativo'=>$r->boolean('ativo')]);
        return back()->with('ok','Serviço criado');
    }
    public function update(Request $r, Servico $servico){
        $data = $r->validate(['nome'=>'required','descricao'=>'nullable','ativo'=>'boolean']);
        $servico->update($data + ['ativo'=>$r->boolean('ativo')]);
        return back()->with('ok','Serviço atualizado');
    }
    public function destroy(Servico $servico){ $servico->delete(); return back()->with('ok','Serviço removido'); }
}
