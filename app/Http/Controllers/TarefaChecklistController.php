<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tarefa;
use App\Models\TarefaChecklist;

class TarefaChecklistController extends Controller
{
    public function store(Request $r, Tarefa $tarefa)
    {
        $data = $r->validate([
            'titulo' => 'required|string|max:255',
        ]);
        $tarefa->checklists()->create($data);
        return back()->with('ok', 'Checklist adicionada.');
    }

    public function destroy(Tarefa $tarefa, TarefaChecklist $checklist)
    {
        abort_unless($checklist->tarefa_id === $tarefa->id, 404);
        $checklist->delete();
        return back()->with('ok', 'Checklist removida.');
    }
}
