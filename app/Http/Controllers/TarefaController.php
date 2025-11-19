<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tarefa;
use App\Models\KanbanColuna;

class TarefaController extends Controller
{
    public function index()
    {
        $tarefas = Tarefa::latest()->paginate(20);
        return view('tarefas.index', compact('tarefas'));
    }

    public function create()
    {
        $colunas = KanbanColuna::orderBy('ordem')->get();
        return view('tarefas.form', ['tarefa'=>new Tarefa(), 'colunas'=>$colunas]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'coluna_id' => 'required|exists:kanban_colunas,id',
        ]);

        $data['empresa_id'] = auth()->user()->empresa_id ?? null;
        $data['responsavel_id'] = auth()->id();

        $tarefa = Tarefa::create($data);
        return redirect()->route('tarefas.show', $tarefa)->with('ok', 'Tarefa criada.');
    }

    public function show(Tarefa $tarefa)
    {
        return view('tarefas.show', compact('tarefa'));
    }

    public function edit(Tarefa $tarefa)
    {
        $colunas = KanbanColuna::orderBy('ordem')->get();
        return view('tarefas.form', compact('tarefa','colunas'));
    }

    public function update(Request $r, Tarefa $tarefa)
    {
        $data = $r->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'coluna_id' => 'required|exists:kanban_colunas,id',
        ]);

        $tarefa->update($data);
        return redirect()->route('tarefas.show', $tarefa)->with('ok', 'Tarefa atualizada.');
    }

    public function destroy(Tarefa $tarefa)
    {
        $tarefa->delete();
        return redirect()->route('tarefas.index')->with('ok', 'Tarefa removida.');
    }

    public function mover(Request $r, Tarefa $tarefa)
    {
        $data = $r->validate([
            'coluna_id' => 'required|exists:kanban_colunas,id',
        ]);
        $tarefa->update(['coluna_id' => $data['coluna_id']]);
        return back()->with('ok', 'Tarefa movida.');
    }
}
