<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Flowforge\TarefaBoard;
use App\Models\Tarefa;
use App\Models\TarefaLog;

class KanbanController extends Controller
{
    public function index(Request $r, TarefaBoard $board)
    {
        // Renderiza o board do Flowforge em uma view
        return view('operacional.kanban', [
            'board' => $board,
        ]);
    }

    public function mover(Request $r)
    {
        // Exemplo de log custom ao mover (Flowforge já atualiza coluna/ordem)
        $data = $r->validate([
            'tarefa_id' => 'required|integer|exists:tarefas,id',
            'de_coluna' => 'nullable|integer',
            'para_coluna' => 'required|integer',
        ]);

        TarefaLog::create([
            'tarefa_id' => $data['tarefa_id'],
            'usuario_id' => auth()->id(),
            'coluna_origem_id' => $data['de_coluna'] ?? null,
            'coluna_destino_id' => $data['para_coluna'],
            'acao' => 'moveu_coluna',
            'meta' => null,
        ]);

        return response()->json(['ok'=>true]);
    }
}
